<?php
require_once '../controllers/TableController.php';

// Instantiate the TableController with the test program parameter
$testProgram = isset($_GET['test_program']) ? $_GET['test_program'] : [];
$controller = new TableController($testProgram);

// Initialize the controller
$controller->init();

// Retrieve headers and data
$headers = $controller->getHeaders();
$data = $controller->fetchData(0, 100); // Example: Fetch first 100 rows

// Determine the chart type
$chart = isset($_GET['chart']) ? $_GET['chart'] : null;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Extracted Table</title>
    <style>
        /* Ensure consistent layout and design with the old version */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 20px;
        }
        h1 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f9f9f9;
            color: #333;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .table-container {
            overflow-y: auto;
            overflow-x: auto;
            max-height: 65vh;
        }
        .button-container {
            margin-bottom: 20px;
            text-align: right;
        }
        .button-container a {
            padding: 10px 20px;
            text-decoration: none;
            color: #fff;
            border-radius: 4px;
            margin-left: 10px;
        }
        .button-container a.chart {
            background-color: #fbbf24; /* Yellow */
        }
        .button-container a.export {
            background-color: #10b981; /* Green */
        }
    </style>
</head>
<body>

<div class="button-container">
    <?php if ($chart == 1): ?>
        <a href="graph.php?<?php echo http_build_query($_GET); ?>" target="_blank" class="chart">
            <i class="fa-solid fa-chart-area"></i>&nbsp;XY Scatter Plot
        </a>
    <?php else: ?>
        <a href="line_chart.php?<?php echo http_build_query($_GET); ?>" target="_blank" class="chart">
            <i class="fa-solid fa-chart-line"></i>&nbsp;Line Chart
        </a>
    <?php endif; ?>
    <a href="export.php?<?php echo http_build_query($_GET); ?>" class="export">
        <i class="fa-regular fa-file-excel"></i>&nbsp;Export
    </a>
</div>

<h1>Extracted Data Table</h1>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <?php foreach ($headers as $header): ?>
                    <th><?php echo htmlspecialchars($header); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data)): ?>
                <tr>
                    <td colspan="<?php echo count($headers); ?>">No data found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <?php foreach ($headers as $header): ?>
                            <td><?php echo htmlspecialchars($row[$header]); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
