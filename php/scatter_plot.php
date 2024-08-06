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

// Debug: Check if any rows are fetched
$rowCount = 0;

// Fetch data and prepare for Chart.js
$dataSets = [];
$xLabels = [];
$yLabels = [];

// Check if at least one parameter is selected
if (!empty($filters['tm.Column_Name'])) {
    // Generate all combinations of selected parameters
    foreach ($filters['tm.Column_Name'] as $xLabel) {
        foreach ($filters['tm.Column_Name'] as $yLabel) {
            $xLabels[] = $xLabel;
            $yLabels[] = $yLabel;
            $dataSets[] = ['x' => $xLabel, 'y' => $yLabel, 'data' => []];
        }
    }

    // Process rows from the SQL query
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $rowCount++;
        foreach ($dataSets as $i => $dataSet) {
            $xValue = floatval($row[$dataSet['x']]);
            $yValue = floatval($row[$dataSet['y']]);
            $dataSets[$i]['data'][] = ['x' => $xValue, 'y' => $yValue];
        }
    }
}

// Free the statement after fetching the data
sqlsrv_free_stmt($stmt);

// Debug: Output row count
if ($rowCount == 0) {
    echo "No rows fetched.";
}

// Encode the datasets for JSON output
$datasetsJson = json_encode($dataSets);
$xLabelsJson = json_encode($xLabels);
$yLabelsJson = json_encode($yLabels);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Scatter Plot</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container mx-auto p-6">
        <h1 class="text-center text-2xl font-bold mb-4 w-full">Scatter Plot</h1>
        <div class="grid grid-cols-3 gap-4">
            <?php foreach ($dataSets as $index => $dataSet): ?>
                <div>
                    <canvas id="scatterChart<?= $index ?>"></canvas>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const dataSets = <?= $datasetsJson ?>;
        dataSets.forEach((dataSet, index) => {
            const ctx = document.getElementById('scatterChart' + index).getContext('2d');
            new Chart(ctx, {
                type: 'scatter',
                data: {
                    datasets: [{
                        label: dataSet.x + ' vs ' + dataSet.y,
                        data: dataSet.data,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        pointRadius: 3,
                        pointHoverRadius: 5
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
    });
    </script>
</body>
</html>
