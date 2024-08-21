<?php 
// Include the required files
require __DIR__ . '/../connection.php';
require __DIR__ . '/../controllers/TableController.php'; // Update this path to where TableController.php is located

// Extract parameters from the request
$xIndex = isset($_GET['x']) ? $_GET['x'] : null;
$yIndex = isset($_GET['y']) ? $_GET['y'] : null;
$orderX = isset($_GET['order-x']) ? $_GET['order-x'] : null;
$orderY = isset($_GET['order-y']) ? $_GET['order-y'] : null;

$columns = [
    'l.Facility_ID', 'l.Work_Center', 'l.Part_Type', 'l.Program_Name', 'l.Test_Temprature', 'l.Lot_ID',
    'w.Wafer_ID', 'p.abbrev', 'w.Wafer_Start_Time', 'w.Wafer_Finish_Time', 'd1.Unit_Number', 'd1.X', 'd1.Y', 'd1.Head_Number',
    'd1.Site_Number', 'd1.HBin_Number', 'd1.SBin_Number', 'd1.Tests_Executed', 'd1.Test_Time'
];

$xColumn = $xIndex !== null && isset($columns[$xIndex]) ? $columns[$xIndex] : null;
$yColumn = $yIndex !== null && isset($columns[$yIndex]) ? $columns[$yIndex] : null;

$filters = [
    "l.Facility_ID" => isset($_GET['facility']) ? $_GET['facility'] : [],
    "l.work_center" => isset($_GET['work_center']) ? $_GET['work_center'] : [],
    "l.part_type" => isset($_GET['device_name']) ? $_GET['device_name'] : [],
    "l.Program_Name" => isset($_GET['test_program']) ? $_GET['test_program'] : [],
    "l.lot_ID" => isset($_GET['lot']) ? $_GET['lot'] : [],
    "w.wafer_ID" => isset($_GET['wafer']) ? $_GET['wafer'] : [],
    "tm.Column_Name" => isset($_GET['parameter']) ? $_GET['parameter'] : [],
    "p.abbrev" => isset($_GET['abbrev']) ? $_GET['abbrev'] : []
];

// Prepare SQL filters
$sql_filters = [];
$params = [];
foreach ($filters as $key => $values) {
    if (!empty($values)) {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $sql_filters[] = "$key IN ($placeholders)";
        $params = array_merge($params, $values);
    }
}

$where_clause = '';
if (!empty($sql_filters)) {
    $where_clause = 'WHERE ' . implode(' AND ', $sql_filters);
}

$orderDirectionX = $orderX == 1 ? 'DESC' : 'ASC';
$orderDirectionY = $orderY == 1 ? 'DESC' : 'ASC';

$orderByClause = '';
if ($xColumn && $yColumn) {
    $orderByClause = "ORDER BY $xColumn $orderDirectionX, $yColumn $orderDirectionY";
} elseif ($xColumn && !$yColumn) {
    $orderByClause = "ORDER BY $xColumn $orderDirectionX";
} elseif (!$xColumn && $yColumn) {
    $orderByClause = "ORDER BY $yColumn $orderDirectionY";
}

$parameters = $filters['tm.Column_Name'];
$data = [];
$groupedData = [];

foreach ($parameters as $parameter) {

    $globalCounters = [
        'all' => 0,
        'xcol' => [],
        'ycol' => []
    ];

    $xLabel = 'Series';
    $yLabel = $parameter;

    $testNameQuery = "SELECT test_name FROM TEST_PARAM_MAP WHERE Column_Name = ?";
    $testNameStmtY = sqlsrv_query($conn, $testNameQuery, [$yLabel]);
    $testNameY = sqlsrv_fetch_array($testNameStmtY, SQLSRV_FETCH_ASSOC)['test_name'];
    $testNameX = $xLabel;
    sqlsrv_free_stmt($testNameStmtY);

    // Dynamically retrieve tables based on the program name
    $tableController = new TableController($filters["l.Program_Name"]); // Instantiate the TableController
    $tableController->init(); // Initialize to get tables
    $tables = $tableController->tables; // Retrieve the tables

    if (count($tables) < 2) {
        die('Not enough tables retrieved to form a valid query.');
    }

    $tableAliases = ['d1', 'd2'];
    $joins = "JOIN WAFER w ON w.Wafer_Sequence = d1.Wafer_Sequence
              JOIN LOT l ON l.Lot_Sequence = w.Lot_Sequence
              JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
              JOIN ProbingSequenceOrder p ON p.probing_sequence = w.probing_sequence";

    for ($i = 0; $i < count($tables); $i++) {
        if ($i > 0) {
            $joins .= " JOIN {$tables[$i]} {$tableAliases[$i]} ON {$tableAliases[$i-1]}.Die_Sequence = {$tableAliases[$i]}.Die_Sequence";
        }
    }

    $tsql = "
    SELECT 
        w.Wafer_ID, 
        d1.{$parameter} AS Y, 
        " . ($xColumn ? "$xColumn AS xGroup" : "'No xGroup' AS xGroup") . ", 
        " . ($yColumn ? "$yColumn AS yGroup" : "'No yGroup' AS yGroup") . ",
        ROW_NUMBER() OVER(PARTITION BY " . ($xColumn ?: "'No xGroup'") . " ORDER BY d1.Die_Sequence) AS row_num
    FROM {$tables[0]} d1
    $joins
    $where_clause
    $orderByClause";

    $stmt = sqlsrv_query($conn, $tsql, $params);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $xGroup = $row['xGroup'];
        $yGroup = $row['yGroup'];
        $yValue = floatval($row['Y']);

        if ($xColumn && $yColumn) {
            if (!isset($globalCounters['ycol'][$yGroup][$xGroup])) {
                $globalCounters['ycol'][$yGroup][$xGroup] = count($groupedData[$yGroup][$xGroup] ?? []) + 1;
            } else {
                $globalCounters['ycol'][$yGroup][$xGroup]++;
            }
            $groupedData[$parameter][$yGroup][$xGroup][] = ['x' => $globalCounters['ycol'][$yGroup][$xGroup], 'y' => $yValue];
        } elseif ($xColumn && !$yColumn) {
            if (!isset($globalCounters['xcol'][$yGroup][$xGroup])) {
                $globalCounters['xcol'][$yGroup][$xGroup] = count($groupedData[$yGroup][$xGroup] ?? []) + 1;
            } else {
                $globalCounters['xcol'][$yGroup][$xGroup]++;
            }
            $groupedData[$parameter][$xGroup][$yGroup][] = ['x' => $globalCounters['xcol'][$yGroup][$xGroup], 'y' => $yValue];
        } elseif (!$xColumn && $yColumn) {
            if (!isset($globalCounters['ycol'][$yGroup])) {
                $globalCounters['ycol'][$yGroup] = count($groupedData[$yGroup] ?? []) + 1;
            } else {
                $globalCounters['ycol'][$yGroup]++;
            }
            $groupedData[$parameter][$yGroup][] = ['x' => $globalCounters['ycol'][$yGroup], 'y' => $yValue];
        } else {
            $globalCounters['all']++;
            $groupedData[$parameter]['all'][] = ['x' => $globalCounters['all'], 'y' => $yValue];
        }
    }
    sqlsrv_free_stmt($stmt);
}

$numDistinctGroups = count($groupedData);
