<?php
include('line_chart_backend.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Graphs</title>
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
            transform: rotate(var(--tw-rotate));
        }
        .mt-24 {
            margin-top: 6rem;
        }
        .max-w-fit {
            max-width: fit-content;
        }
        .customize-text-header {
            margin-top: -28px;
        }
        .right-4 {
            right: 2.5rem;
        }
        .top-24 {
            top: 6rem;
        }
        .ml-16 {
            margin-left: 4rem;
        }
        .max-w-5xl {
            max-width: 64rem /* 1024px */;
        }
        .w-custom {
            width: 32rem /* 512px */;
        }
   </style>
</head>
<body class="bg-gray-50">
<?php include('admin_components.php'); ?>
<?php include('settings.php'); ?>
<script>const groupedData = <?php echo json_encode($groupedData); ?>;</script>
<h1 class="text-center text-2xl font-bold w-full mb-6">Line Chart</h1>
<!-- Iterate and generate chart canvases -->
<div class="max-w-5xl p-4 my-4 flex items-center justify-center mx-auto">
    <div class="w-full">
        <?php include('received_parameters.php'); ?>
    </div>
</div>
<?php
foreach ($groupedData as $parameter => $data) {
    echo '<div class="p-4">';
    echo '<div class="dark:border-gray-700 flex flex-col items-center">';
    echo '<div class="max-w-fit p-6 border-b-2 border-2 bg-white shadow-md rounded-md">';
    echo '<div class="mb-4 text-sm italic">';
    echo 'Series of <b>' . $testNameY . '</b>';
    echo '</div>';

    if (isset($xColumn) && isset($yColumn)) {
        $yGroupKeys = array_keys($data);
        $lastYGroup = end($yGroupKeys);
        foreach ($data as $yGroup => $xGroupData) {
            echo '<div class="flex flex-row items-center justify-center w-full">';
            echo '<div><h2 class="text-center text-xl font-semibold mb-4 -rotate-90">' . $yGroup . '</h2></div>';
            echo '<div class="grid gap-2 grid-cols-' . count($xGroupData) . '">';
            foreach ($xGroupData as $xGroup => $chartData) {
                $chartId = "chartXY_{$parameter}_{$yGroup}_{$xGroup}";
                echo '<div class="flex items-center justify-center flex-col">';
                echo "<canvas id='{$chartId}'></canvas>";
                if ($yGroup === $lastYGroup) {
                    echo '<h3 class="text-center text-lg font-semibold">' . $xGroup . '</h3>';
                }
                echo '</div>';
            }
            echo '</div></div>';
        }
    } elseif (isset($xColumn)) {
        echo '<div class="flex flex-row items-center justify-center w-full">';
        echo '<div class="grid gap-2 grid-cols-' . count($data) . '">';
        foreach ($data as $xGroup => $chartData) {
            $chartId = "chartXY_{$parameter}_{$xGroup}";
            echo '<div class="flex items-center justify-center flex-col">';
            echo "<canvas id='{$chartId}'></canvas>";
            echo '<h3 class="text-center text-lg font-semibold">' . $xGroup . '</h3></div>';
        }
        echo '</div></div>';
    } elseif (isset($yColumn)) {
        echo '<div class="flex flex-row items-center justify-center w-full">';
        echo '<div class="grid gap-2 grid-cols-1">';
        foreach ($data as $yGroup => $chartData) {
            $chartId = "chartXY_{$parameter}_{$yGroup}";
            echo '<div class="flex flex-row justify-center items-center w-custom">';
            echo '<div class="text-center"><h2 class="text-center text-xl font-semibold mb-4 -rotate-90">' . $yGroup . '</h2></div>';
            echo "<canvas id='{$chartId}'></canvas>";
            echo '</div>';
        }
        echo '</div></div>';
    } else {
        $chartId = "chartXY_{$parameter}_all";
        echo '<div class="flex items-center justify-center w-full">';
        echo "<div><canvas id='{$chartId}'></canvas></div>";
        echo '</div>';
    }

    echo '</div>';
    echo '</div>';
    echo '</div>';
}
?>

<!-- End Iterate here-->
<script>
    const xLabel = '<?php echo $testNameX; ?>';
    const yLabel = '<?php echo $testNameY; ?>';
    const xColumn = <?php echo json_encode($xColumn); ?>;
    const yColumn = <?php echo json_encode($yColumn); ?>;
    const hasXColumn = <?php echo json_encode(isset($xColumn)); ?>;
    const hasYColumn = <?php echo json_encode(isset($yColumn)); ?>;
    console.log(groupedData);
</script>
<script src="../js/chart_line.js"></script>
</body>
</html>
