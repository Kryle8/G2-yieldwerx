<?php
    require_once '../controllers/TableController.php';
    $tableController = new TableController();
    $tableController->init();
    
    $chart = isset($_GET['chart']) ? $_GET['chart'] : null;
?>
<style>
    .table-container {
        overflow-y: auto;
        overflow-x: auto;
        max-height: 65vh;
    }
</style>

<div class="flex justify-center items-center h-full">
    <div class="w-full max-w-7xl p-6 rounded-lg shadow-lg bg-white mt-10">
        <div class="mb-4 text-right">
            <?php if ($chart == 1): ?>
                <a href="graph.php?<?php echo http_build_query($_GET); ?>" target="_blank" class="px-4 py-2 bg-yellow-400 text-white rounded mr-2">
                    <i class="fa-solid fa-chart-area"></i>&nbsp;XY Scatter Plot
                </a>
            <?php else: ?>
                <a href="line_chart.php?<?php echo http_build_query($_GET); ?>" target="_blank" class="px-4 py-2 bg-yellow-400 text-white rounded mr-2">
                    <i class="fa-solid fa-chart-line"></i>&nbsp;Line Chart
                </a>
            <?php endif; ?>
            <a href="export.php?<?php echo http_build_query($_GET); ?>" class="px-5 py-2 bg-green-500 text-white rounded">
                <i class="fa-regular fa-file-excel"></i>&nbsp;Export
            </a>
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
