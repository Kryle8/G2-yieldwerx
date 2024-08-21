<?php
    require_once('../controllers/TableController.php');
    $tableController = new TableController();
    $tableController->init();

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

<div class="flex justify-center items-center h-full">
    <div class="w-full max-w-7xl p-6 rounded-lg shadow-lg bg-white mt-10">
        <div class="mb-4 text-right">
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
        </div>
        <h1 class="text-start text-2xl font-bold mb-4">Data Extraction [Total: <?php echo $tableController->getCount(); ?>]</h1>
        <div class="table-container">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <?php
                            $tableController->writeTableHeaders();
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $tableController->writeTableData();
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
