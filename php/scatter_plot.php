<?php
require __DIR__ . '/../connection.php';

// Filters from URL parameters
$filters = [
    "l.Facility_ID" => isset($_GET['facility']) ? $_GET['facility'] : [],
    "l.work_center" => isset($_GET['work_center']) ? $_GET['work_center'] : [],
    "l.part_type" => isset($_GET['device_name']) ? $_GET['device_name'] : [],
    "l.program_name" => isset($_GET['test_program']) ? $_GET['test_program'] : [],
    "l.lot_ID" => isset($_GET['lot']) ? $_GET['lot'] : [],
    "w.wafer_ID" => isset($_GET['wafer']) ? $_GET['wafer'] : [],
    "tm.Column_Name" => isset($_GET['parameter']) ? $_GET['parameter'] : []
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

// Create the WHERE clause if filters exist
$where_clause = '';
if (!empty($sql_filters)) {
    $where_clause = 'WHERE ' . implode(' AND ', $sql_filters);
}

// Dynamically construct the column part of the SQL query
$column_list = !empty($filters['tm.Column_Name']) ? implode(', ', array_map(function($col) { return "d1.$col"; }, $filters['tm.Column_Name'])) : '*';

// Retrieve all records with filters
$tsql = "SELECT l.Facility_ID, l.Work_Center, l.Part_Type, l.Program_Name, l.Test_Temprature, l.Lot_ID,
                w.Wafer_ID, w.Wafer_Start_Time, w.Wafer_Finish_Time, d1.Unit_Number, d1.X, d1.Y, d1.Head_Number,
                d1.Site_Number, d1.HBin_Number, d1.SBin_Number, d1.Tests_Executed, d1.Test_Time, 
                tm.Column_Name, tm.Test_Name, $column_list
         FROM DEVICE_1_CP1_V1_0_001 d1
         JOIN WAFER w ON w.Wafer_Sequence = d1.Wafer_Sequence
         JOIN LOT l ON l.Lot_Sequence = w.Lot_Sequence
         JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
         JOIN DEVICE_1_CP1_V1_0_002 d2 ON d1.Die_Sequence = d2.Die_Sequence
         $where_clause
         ORDER BY w.Wafer_ID";

$stmt = sqlsrv_query($conn, $tsql, $params);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Fetch 'group by' selections
$groupByLot = isset($_GET['group_lot']) ? true : false;
$groupByWafer = isset($_GET['group_wafer']) ? true : false;
$groupByParam = isset($_GET['group_by_param']) ? true : false;
$groupByParamSelect = $_GET['group_by_param_select'] ?? null;
$groupByDirection = $_GET['group_by_direction'] ?? null;

// Fetch data and prepare for Chart.js
$dataSets = [];
$xLabels = [];
$yLabels = [];

if ($groupByLot) {
    $groupedData = [];

    // Process rows from the SQL query
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $lotID = $row['Lot_ID'];
        foreach ($filters['tm.Column_Name'] as $param) {
            if (!isset($groupedData[$lotID])) {
                $groupedData[$lotID] = [];
            }
            $groupedData[$lotID]['data'][] = ['x' => floatval($row['X']), 'y' => floatval($row[$param])];
        }
    }

    // Prepare datasets for Chart.js
    foreach ($groupedData as $lotID => $dataSet) {
        $label = "Lot ID: $lotID";
        $dataSets[] = [
            'x' => 'X',
            'y' => $filters['tm.Column_Name'][0], // Assuming you group by the first selected parameter
            'data' => $dataSet['data'],
            'label' => $label
        ];
        $xLabels[] = 'X';
        $yLabels[] = $filters['tm.Column_Name'][0];
    }
} elseif ($groupByWafer) {
    $groupedData = [];

    // Process rows from the SQL query
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $waferID = $row['Wafer_ID'];
        foreach ($filters['tm.Column_Name'] as $param) {
            if (!isset($groupedData[$waferID])) {
                $groupedData[$waferID] = [];
            }
            $groupedData[$waferID]['data'][] = ['x' => floatval($row['X']), 'y' => floatval($row[$param])];
        }
    }

    // Prepare datasets for Chart.js
    foreach ($groupedData as $waferID => $dataSet) {
        $label = "Wafer ID: $waferID";
        $dataSets[] = [
            'x' => 'X',
            'y' => $filters['tm.Column_Name'][0], // Assuming you group by the first selected parameter
            'data' => $dataSet['data'],
            'label' => $label
        ];
        $xLabels[] = 'X';
        $yLabels[] = $filters['tm.Column_Name'][0];
    }
} elseif ($groupByParam && $groupByParamSelect && $groupByDirection) {
    $groupedData = [];

    // Process rows from the SQL query
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        foreach ($filters['tm.Column_Name'] as $param) {
            if ($groupByDirection == 'horizontal' && $param != $groupByParamSelect && isset($row[$param])) {
                $groupedData[$param]['data'][] = ['x' => floatval($row[$param]), 'y' => floatval($row[$groupByParamSelect])];
            } elseif ($groupByDirection == 'vertical' && $param != $groupByParamSelect && isset($row[$param])) {
                $groupedData[$param]['data'][] = ['x' => floatval($row[$groupByParamSelect]), 'y' => floatval($row[$param])];
            }
        }
    }

    // Prepare datasets for Chart.js
    foreach ($groupedData as $param => $dataSet) {
        $label = ($groupByDirection == 'horizontal' ? "$param vs $groupByParamSelect" : "$groupByParamSelect vs $param") ?: 'Default Label';
        $dataSets[] = [
            'x' => $groupByDirection == 'horizontal' ? $param : $groupByParamSelect,
            'y' => $groupByDirection == 'horizontal' ? $groupByParamSelect : $param,
            'data' => $dataSet['data'],
            'label' => $label
        ];
        $xLabels[] = $groupByDirection == 'horizontal' ? $param : $groupByParamSelect;
        $yLabels[] = $groupByDirection == 'horizontal' ? $groupByParamSelect : $param;
    }
} else {
    // Handle non-grouped scatter plots
    if (count($filters['tm.Column_Name']) >= 1) {
        // Generate all combinations of selected parameters
        $params = $filters['tm.Column_Name'];
        $numParams = count($params);
        for ($i = $numParams - 1; $i >= 0; $i--) {
            for ($j = 0; $j < $numParams; $j++) {
                $xLabel = $params[$i];
                $yLabel = $params[$j];
                $xLabels[] = $xLabel;
                $yLabels[] = $yLabel;
                $dataSets[] = [
                    'x' => $xLabel,
                    'y' => $yLabel,
                    'data' => [],
                    'label' => "$xLabel vs $yLabel"
                ];
            }
        }

        // Process rows from the SQL query
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            foreach ($dataSets as $i => $dataSet) {
                $xValue = floatval($row[$dataSet['x']]);
                $yValue = floatval($row[$dataSet['y']]);
                $dataSets[$i]['data'][] = ['x' => $xValue, 'y' => $yValue];
            }
        }
    }
}

// Free the statement after fetching the data
sqlsrv_free_stmt($stmt);

// Encode the datasets for JSON output
$datasetsJson = json_encode($dataSets);
$xLabelsJson = json_encode($xLabels);
$yLabelsJson = json_encode($yLabels);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Scatter Plots</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        #chartsContainer {
            display: grid;
            justify-content: center;
            grid-gap: 5px; /* Reduce gap between charts */
        }
        .chart-container {
            width: 100%; /* Allow width to adjust dynamically */
            height: 100%; /* Allow height to adjust dynamically */
        }
    </style>
</head>
<body>
    <div id="chartsContainer"></div>

    <script>
        const dataSets = <?= $datasetsJson; ?>;
        const chartsContainer = document.getElementById('chartsContainer');

        // Calculate the number of columns based on the number of charts
        const numCharts = dataSets.length;
        const numColumns = Math.ceil(Math.sqrt(numCharts));
        const chartSize = 100 / numColumns; // Dynamic size calculation

        chartsContainer.style.gridTemplateColumns = `repeat(${numColumns}, ${chartSize}%)`;
        chartsContainer.style.gridTemplateRows = `repeat(${numColumns}, ${chartSize}vh)`; // Use viewport height for responsive design

        dataSets.forEach((dataSet, index) => {
            const div = document.createElement('div');
            div.className = 'chart-container';
            div.style.width = `${chartSize}vw`;  // Set width based on available space
            div.style.height = `${chartSize}vh`; // Set height based on available space
            const canvas = document.createElement('canvas');
            canvas.id = `chart-${index}`;
            div.appendChild(canvas);
            chartsContainer.appendChild(div);

            new Chart(canvas, {
                type: 'scatter',
                data: {
                    datasets: [{
                        label: dataSet.label || `${dataSet.x} vs ${dataSet.y}`,
                        data: dataSet.data,
                        backgroundColor: 'rgba(100, 130, 173, 0.6)', // Using #6482AD with some opacity
                        borderColor: 'rgba(100, 130, 173, 1)',       // Using #6482AD
                        pointRadius: 2,
                        showLine: false
                    }]
                },
                options: {
                    scales: {
                        x: {
                            type: 'linear',
                            position: 'bottom',
                            title: {
                                display: true,
                                text: dataSet.x
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: dataSet.y
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
