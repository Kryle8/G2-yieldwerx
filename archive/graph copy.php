<?php
include('graph_backend.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>XY Scatter Plot</title>
   <link rel="stylesheet" href="../src/output.css">
   <link href="https://cdn.jsdelivr.net/npm/flowbite@2.4.1/dist/flowbite.min.css" rel="stylesheet" />
   <script src="https://cdn.jsdelivr.net/npm/flowbite@2.4.1/dist/flowbite.min.js"></script>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.0"></script>
   <style>
       .chart-container {
           overflow: auto;
           max-width: 100%;
       }
       td {
           padding: 16px;
       }
       canvas {
           height: 400px;
           width: 450px;
       }
       .-rotate-90 {
            --tw-rotate: -90deg;
            transform: translate(var(--tw-translate-x), var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y));
        }
        .mt-24 {
            margin-top: 6rem /* 96px */;
        }
        .max-w-fit {
            max-width: fit-content;
        }
        .customize-text-header{
            margin-top:-28px;
        }

        .right-4 {
            right: 2.5rem /* 16px */;
        }
        .top-24 {
            top: 6rem /* 96px */;
        }
        .ml-16 {
            margin-left: 4rem /* 64px */;
        }
   </style>
</head>
<body class="bg-gray-50">
<?php include('admin_components.php'); ?>
<?php include('settings.php'); ?>
<!-- Iterate this layout -->
    <div class="p-4">
    <div class="dark:border-gray-700 flex flex-col items-center">
    <div class="max-w-fit p-6 border-b-2 border-2">
        <div class="mb-4 text-sm italic">Combination of <b><?php echo $testNameX; ?></b> and <b><?php echo $testNameY; ?></b></div>
        <?php
        if (isset($xColumn) && isset($yColumn)) {
            $yGroupKeys = array_keys($groupedData);
            $lastYGroup = end($yGroupKeys);
            foreach ($groupedData as $yGroup => $xGroupData) {
                echo '<div class="flex flex-row items-center justify-center w-full">';
                echo '<div><h2 class="text-center text-xl font-semibold mb-4 -rotate-90">' . $yGroup . '</h2></div>';
                echo '<div class="grid gap-2 grid-cols-' . count($xGroupData) . '">';

                foreach ($xGroupData as $xGroup => $data) {
                    echo '<div class="flex items-center justify-center flex-col">';
                    echo '<canvas id="chartXY_' . $yGroup . '_' . $xGroup . '"></canvas>';
                    if ($yGroup === $lastYGroup) {
                        echo '<h3 class="text-center text-lg font-semibold">' . $xGroup . '</h3>';
                    }
                    echo '</div>';
                }
                echo '</div></div>';
            }
        } elseif (isset($xColumn) && !isset($yColumn)) {
            echo '<div class="flex flex-row items-center justify-center w-full">';
            echo '<div class="grid gap-2 grid-cols-' . $numDistinctGroups . '">';
            foreach ($groupedData as $xGroup => $data) {
                echo '<div class="flex items-center justify-center flex-col">';
                echo '<canvas id="chartXY_' . $xGroup . '"></canvas>
                <h3 class="text-center text-lg font-semibold">' . $xGroup . '</h3></div>';
            }
            echo '</div></div>';
        } elseif (!isset($xColumn) && isset($yColumn)) {
            echo '<div class="flex flex-row items-center justify-center w-full">';
            echo '<div class="grid gap-2 grid-cols-1">';
            echo '<div class="flex items-center justify-center flex-col">';
            foreach ($groupedData as $yGroup => $data) {
                echo '<div class="flex flex-row justify-center items-center">
                <div class="text-center">
                    <h2 class="text-center text-xl font-semibold mb-4 -rotate-90"">' . $yGroup . '</h2>
                    </div>';
                    echo '<canvas id="chartXY_' . $yGroup . '"></canvas>
                </div>';
            }
            echo '</div></div>';
        } else {
            echo '<div class="flex items-center justify-center w-full">';
            echo '<div><canvas id="chartXY_all"></canvas></div>';
            echo '</div>';
        }
        ?>
    </div>
    </div>
</div>
<!-- Iterate until here -->
<script>
    const groupedData = <?php echo json_encode($groupedData); ?>;
    const xLabel = '<?php echo $testNameX; ?>';
    const yLabel = '<?php echo $testNameY; ?>';
    const hasXColumn = <?php echo json_encode(isset($xColumn)); ?>;
    const hasYColumn = <?php echo json_encode(isset($yColumn)); ?>;
    const isSingleParameter = <?php echo json_encode($isSingleParameter); ?>;
</script>
<script src="../js/chart_dynamic.js"></script>
</body>
</html>
