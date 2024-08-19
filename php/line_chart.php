<?php
require_once '../controllers/TableController.php';

// Instantiate the TableController with the test program parameter
$testProgram = isset($_GET['test_program']) ? $_GET['test_program'] : [];
$controller = new TableController($testProgram);

// Initialize the controller
$controller->init();

// Retrieve data (you might want to retrieve more data depending on the chart's needs)
$data = $controller->fetchData(0, 100);

// Prepare data for the chart
$labels = [];
$datasets = [];
foreach ($data as $row) {
    $labels[] = $row['Wafer_Start_Time']; // assuming time or sequential ID is the x-axis
    foreach ($_GET['parameter'] as $param) {
        if (!isset($datasets[$param])) {
            $datasets[$param] = [];
        }
        $datasets[$param][] = $row[$param];
    }
}

// Convert datasets to the format needed by Chart.js
$chartDatasets = [];
foreach ($datasets as $param => $values) {
    $chartDatasets[] = [
        'label' => $param,
        'data' => $values,
        'borderColor' => 'rgba(75, 192, 192, 1)',
        'fill' => false,
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Line Chart</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<h1>Line Chart of Selected Parameters</h1>

<canvas id="lineChart"></canvas>

<script>
    var ctx = document.getElementById('lineChart').getContext('2d');
    var chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: <?php echo json_encode($chartDatasets); ?>
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Wafer Start Time'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Parameter Value'
                    }
                }
            }
        }
    });
</script>

</body>
</html>
