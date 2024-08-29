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
        <div class="flex justify-between items-center my-4 px-4">
            <div class="flex left items-center my-4 px-4">
                <button onclick="window.history.back()" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-75 transition duration-150 ease-in-out flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Go Back
                </button>
                <a href="selection_page.php" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-opacity-75 transition duration-150 ease-in-out flex items-center">
                    <i class="fas fa-redo mr-2"></i> Reset Selection
                </a>
            </div>
        <!-- <div class="mb-4 text-right"> -->
            <div class="button-container">
                <?php if ($chart == 1): ?>
                    <a href="graph.php?<?php echo http_build_query($_GET); ?>" target="_blank" class="chart">
                        <i class="fa-solid fa-chart-area"></i>&nbsp;XY Scatter Plot
                    </a>
                <?php elseif ($chart == 0): ?>
                    <a href="line_chart.php?<?php echo http_build_query($_GET); ?>" target="_blank" class="chart">
                        <i class="fa-solid fa-chart-line"></i>&nbsp;Line Chart
                    </a>
                <?php elseif ($chart == 2): ?>
                    <a href="cumulative.php?<?php echo http_build_query($_GET); ?>" target="_blank" class="chart">
                    <i class="fa-solid fa-chart-area"></i>&nbsp;Cumulative Chart
                    </a>
                <?php endif; ?>
                <a href="export.php?<?php echo http_build_query($_GET); ?>" class="export">
                    <i class="fa-regular fa-file-excel"></i>&nbsp;Export
                </a>
            </div>
        </div>
        <h1 class="text-start text-2xl font-bold mb-4">Data Extraction [Total: <?php echo $tableController->getCount(); ?>]</h1>
        <div class="table-container">
            
            <table id="data-table" class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead>
                <?php
                    // Include and initialize the TableController
                    require_once('../controllers/TableController.php');
                    $tableController = new TableController();
                    $tableController->init();  // Initialize with filters, joins, and clauses
                    
                    // Call the method to write the table headers
                    $tableController->writeTableHeaders();
                ?>
            </thead>
            <tbody>
                <?php
                    $tableController->writeTableData();  // Write data rows to table
                ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

</body>
</html>
