<?php 
require __DIR__ . '/../connection.php';

$xIndex = isset($_GET['x']) ? $_GET['x'] : null;
$yIndex = isset($_GET['y']) ? $_GET['y'] : null;

$orderX = isset($_GET['order-x']) ? $_GET['order-x'] : null;
$orderY = isset($_GET['order-y']) ? $_GET['order-y'] : null;

$columns = [
    'l.Facility_ID', 'l.Work_Center', 'l.Part_Type', 'l.Program_Name', 'l.Test_Temprature', 'l.Lot_ID',
    'w.Wafer_ID', 'p.abbrev', 'w.Wafer_Start_Time', 'w.Wafer_Finish_Time', 'd1.Unit_Number', 'd1.X', 'd1.Y', 'd1.Head_Number',
    'd1.Site_Number', 'd1.HBin_Number', 'd1.SBin_Number'
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

// Determine if you need to include an ORDER BY clause
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

$combinations = [];
foreach ($parameters as $i => $xParam) {
    for ($j = $i + 1; $j < count($parameters); $j++) {
        $combinations[] = [$xParam, $parameters[$j]];
    }
}

foreach ($combinations as $combination) {
    $globalCounters = [
        'all' => 0,
        'xcol' => [],
        'ycol' => []
    ];

    $xLabel = $combination[0];
    $yLabel = $combination[1];

    // Convert $combination array to a string key
    $combinationKey = implode('_', $combination);

    $testNameQuery = "SELECT test_name FROM TEST_PARAM_MAP WHERE Column_Name = ?";
    $testNameStmtX = sqlsrv_query($conn, $testNameQuery, [$xLabel]);
    $testNameX = sqlsrv_fetch_array($testNameStmtX, SQLSRV_FETCH_ASSOC)['test_name'];

    $testNameStmtY = sqlsrv_query($conn, $testNameQuery, [$yLabel]);
    $testNameY = sqlsrv_fetch_array($testNameStmtY, SQLSRV_FETCH_ASSOC)['test_name'];

    sqlsrv_free_stmt($testNameStmtX);
    sqlsrv_free_stmt($testNameStmtY);

    $tsql = "
    SELECT 
        d1.{$xLabel} AS X, 
        d1.{$yLabel} AS Y, 
        " . ($xColumn ? "$xColumn AS xGroup" : "'No xGroup' AS xGroup") . ", 
        " . ($yColumn ? "$yColumn AS yGroup" : "'No yGroup' AS yGroup") . "
    FROM DEVICE_1_CP1_V1_0_001 d1
    JOIN WAFER w ON w.Wafer_Sequence = d1.Wafer_Sequence
    JOIN LOT l ON l.Lot_Sequence = w.Lot_Sequence
    JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
    JOIN DEVICE_1_CP1_V1_0_002 d2 ON d1.Die_Sequence = d2.Die_Sequence
    JOIN ProbingSequenceOrder p ON p.probing_sequence = w.probing_sequence
    $where_clause
    $orderByClause";

    $stmt = sqlsrv_query($conn, $tsql, $params);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $xGroup = $row['xGroup'];
        $yGroup = $row['yGroup'];
        $xValue = floatval($row['X']);
        $yValue = floatval($row['Y']);

        if ($xColumn && $yColumn) {
            $groupedData[$combinationKey][$yGroup][$xGroup][] = ['x' => $xValue, 'y' => $yValue];
        } elseif ($xColumn && !$yColumn) {
            $groupedData[$combinationKey][$xGroup][$yGroup][] = ['x' => $xValue, 'y' => $yValue];
        } elseif (!$xColumn && $yColumn) {
            $groupedData[$combinationKey][$yGroup][] = ['x' => $xValue, 'y' => $yValue];
        } else {
            $groupedData[$combinationKey]['all'][] = ['x' => $xValue, 'y' => $yValue];
        }
    }

    sqlsrv_free_stmt($stmt);
}

$numDistinctGroups = count($groupedData);

?>
