<?php 
require __DIR__ . '/../connection.php';

$xIndex = isset($_GET['x']) ? $_GET['x'] : null;
$yIndex = isset($_GET['y']) ? $_GET['y'] : null;

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
    "l.program_name" => isset($_GET['test_program']) ? $_GET['test_program'] : [],
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

// Determine if we are working with one parameter or two
$isSingleParameter = count($filters['tm.Column_Name']) === 1;
$parameter = $filters['tm.Column_Name'][0] ?? '';
$data = [];
if ($isSingleParameter) {
    $xLabel = 'X';
    $yLabel = $filters['tm.Column_Name'][0];

    // Fetch the test_name corresponding to yLabel
    $testNameQuery = "SELECT test_name FROM TEST_PARAM_MAP WHERE Column_Name = ?";
    $testNameStmtY = sqlsrv_query($conn, $testNameQuery, [$yLabel]);
    $testNameY = sqlsrv_fetch_array($testNameStmtY, SQLSRV_FETCH_ASSOC)['test_name'];
    $testNameX = $xLabel;
    sqlsrv_free_stmt($testNameStmtY);
} else {
    $xLabel = $filters['tm.Column_Name'][0];
    $yLabel = $filters['tm.Column_Name'][1];

    // Fetch the test_name corresponding to xLabel and yLabel
    $testNameQuery = "SELECT test_name FROM TEST_PARAM_MAP WHERE Column_Name = ?";
    $testNameStmtX = sqlsrv_query($conn, $testNameQuery, [$xLabel]);
    $testNameX = sqlsrv_fetch_array($testNameStmtX, SQLSRV_FETCH_ASSOC)['test_name'];

    $testNameStmtY = sqlsrv_query($conn, $testNameQuery, [$yLabel]);
    $testNameY = sqlsrv_fetch_array($testNameStmtY, SQLSRV_FETCH_ASSOC)['test_name'];

    sqlsrv_free_stmt($testNameStmtX);
    sqlsrv_free_stmt($testNameStmtY);
}
$count = 0;

// Query to fetch data for the chart
if ($isSingleParameter) {
    $tsql = "
        SELECT 
            w.Wafer_ID, 
            d1.{$parameter} AS Y, 
            " . ($xColumn ? "$xColumn AS xGroup" : "'No xGroup' AS xGroup") . ", 
            " . ($yColumn ? "$yColumn AS yGroup" : "'No yGroup' AS yGroup") . ",
            ROW_NUMBER() OVER(PARTITION BY " . ($xColumn ?: "'No xGroup'") . " ORDER BY d1.Die_Sequence) AS row_num
        FROM DEVICE_1_CP1_V1_0_001 d1
        JOIN WAFER w ON w.Wafer_Sequence = d1.Wafer_Sequence
        JOIN LOT l ON l.Lot_Sequence = w.Lot_Sequence
        JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
        JOIN DEVICE_1_CP1_V1_0_002 d2 ON d1.Die_Sequence = d2.Die_Sequence
        JOIN ProbingSequenceOrder p ON p.probing_sequence = w.probing_sequence
        $where_clause";
} else {
    $tsql = "
        SELECT 
            d1.{$filters['tm.Column_Name'][0]} AS X, 
            d1.{$filters['tm.Column_Name'][1]} AS Y, 
            " . ($xColumn ? "$xColumn AS xGroup" : "'No xGroup' AS xGroup") . ", 
            " . ($yColumn ? "$yColumn AS yGroup" : "'No yGroup' AS yGroup") . "
        FROM DEVICE_1_CP1_V1_0_001 d1
        JOIN WAFER w ON w.Wafer_Sequence = d1.Wafer_Sequence
        JOIN LOT l ON l.Lot_Sequence = w.Lot_Sequence
        JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
        JOIN DEVICE_1_CP1_V1_0_002 d2 ON d1.Die_Sequence = d2.Die_Sequence
        JOIN ProbingSequenceOrder p ON p.probing_sequence = w.probing_sequence
        $where_clause";
}

$stmt = sqlsrv_query($conn, $tsql, $params);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$groupedData = [];
$globalCounters = [
    'all' => 0,
    'xcol' => [],
    'ycol' => []
];

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $xGroup = $row['xGroup'];
    $yGroup = $row['yGroup'];
    if ($isSingleParameter) {
        $yValue = floatval($row['Y']);
        
        if ($xColumn && $yColumn) {
            // Increment or initialize the global counter for the combination of abbrev and waferID
            if (!isset($globalCounters['ycol'][$yGroup][$xGroup])) {
                $globalCounters['ycol'][$yGroup][$xGroup] = count($groupedData[$yGroup][$xGroup] ?? []) + 1;
            } else {
                $globalCounters['ycol'][$yGroup][$xGroup]++;
            }
            $groupedData[$yGroup][$xGroup][] = ['x' => $globalCounters['ycol'][$yGroup][$xGroup], 'y' => $yValue];
        } elseif ($xColumn) {
            // Increment or initialize the global counter for the waferID
            if (!isset($globalCounters['xcol'][$xGroup])) {
                $globalCounters['xcol'][$xGroup] = count($groupedData[$xGroup] ?? []) + 1;
            } else {
                $globalCounters['xcol'][$xGroup]++;
            }
            $groupedData[$xGroup][] = ['x' => $globalCounters['xcol'][$xGroup], 'y' => $yValue];
        } elseif ($yColumn) {
            // Increment or initialize the global counter for the abbrev
            if (!isset($globalCounters['ycol'][$yGroup])) {
                $globalCounters['ycol'][$yGroup] = count($groupedData[$yGroup] ?? []) + 1;
            } else {
                $globalCounters['ycol'][$yGroup]++;
            }
            $groupedData[$yGroup][] = ['x' => $globalCounters['ycol'][$yGroup], 'y' => $yValue];
        } else {
            // Increment the global counter for all data
            $globalCounters['all']++;
            $groupedData['all'][] = ['x' => $globalCounters['all'], 'y' => $yValue];
        }
    } else {
        $xValue = floatval($row['X']);
        $yValue = floatval($row['Y']);

        if ($xColumn && $yColumn) {
            $groupedData[$yGroup][$xGroup][] = ['x' => $xValue, 'y' => $yValue];
        } elseif ($xColumn) {
            $groupedData[$xGroup][] = ['x' => $xValue, 'y' => $yValue];
        } elseif ($yColumn) {
            $groupedData[$yGroup][] = ['x' => $xValue, 'y' => $yValue];
        } else {
            $groupedData['all'][] = ['x' => $xValue, 'y' => $yValue];
        }
    }
}

sqlsrv_free_stmt($stmt);

$numDistinctGroups = count($groupedData);
?>