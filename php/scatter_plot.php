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

// Fetch data and prepare for Chart.js
$data1 = [];
$data2 = [];
$xLabel1 = '';
$yLabel1 = '';
$xLabel2 = '';
$yLabel2 = '';

// Assuming there are at least 2 columns for plotting
if (count($filters['tm.Column_Name']) >= 4) {
    $xLabel1 = $filters['tm.Column_Name'][0];
    $yLabel1 = $filters['tm.Column_Name'][1];
    $xLabel2 = $filters['tm.Column_Name'][2];
    $yLabel2 = $filters['tm.Column_Name'][3];
    
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $data1[] = ['x' => floatval($row[$xLabel1]), 'y' => floatval($row[$yLabel1])];
        $data2[] = ['x' => floatval($row[$xLabel2]), 'y' => floatval($row[$yLabel2])];
    }
}
sqlsrv_free_stmt($stmt); // Free the statement here after fetching the data
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Dashboard</title>
   <link rel="stylesheet" href="../src/output.css">
   <script src="../path/to/flowbite/dist/flowbite.min.js"></script>
   <link href="https://cdn.jsdelivr.net/npm/flowbite@2.4.1/dist/flowbite.min.css" rel="stylesheet" />
   <script src="https://cdn.jsdelivr.net/npm/flowbite@2.4.1/dist/flowbite.min.js"></script>
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   <style>
       .chart-container {
           position: relative;
           width: 100%;
           height: 60vh;
           margin: auto;
       }
       .nav-tabs .nav-link {
           cursor: pointer;
       }
   </style>
</head>
<body class="bg-gray-100">
<nav class="fixed top-0 z-50 w-full bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
  <!-- Your navigation code here -->
</nav>

<div class="p-4 mt-20">
    <div class="p-4 bg-white rounded-lg shadow-md">
        <h2 class="text-xl font-bold mb-4">Scatter Plots</h2>
        <ul class="nav nav-tabs" id="chartTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="tab1" data-bs-toggle="tab" href="#scatter1" role="tab">Plot 1</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="tab2" data-bs-toggle="tab" href="#scatter2" role="tab">Plot 2</a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="scatter1" role="tabpanel">
                <div class="chart-container">
                    <canvas id="scatterPlot1"></canvas>
                </div>
            </div>
            <div class="tab-pane fade" id="scatter2" role="tabpanel">
                <div class="chart-container">
                    <canvas id="scatterPlot2"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
   document.addEventListener("DOMContentLoaded", function() {
       // Initialize Scatter Plot 1
       var ctx1 = document.getElementById('scatterPlot1').getContext('2d');
       var scatterData1 = <?php echo json_encode($data1); ?>;
       
       new Chart(ctx1, {
           type: 'scatter',
           data: {
               datasets: [{
                   label: 'Scatter Dataset 1',
                   data: scatterData1,
                   backgroundColor: 'rgba(75, 192, 192, 0.6)'
               }]
           },
           options: {
               responsive: true,
               maintainAspectRatio: false,
               scales: {
                   x: {
                       type: 'linear',
                       position: 'bottom',
                       title: {
                           display: true,
                           text: '<?php echo $xLabel1; ?>'
                       }
                   },
                   y: {
                       title: {
                           display: true,
                           text: '<?php echo $yLabel1; ?>'
                       }
                   }
               }
           }
       });

       // Initialize Scatter Plot 2
       var ctx2 = document.getElementById('scatterPlot2').getContext('2d');
       var scatterData2 = <?php echo json_encode($data2); ?>;
       
       new Chart(ctx2, {
           type: 'scatter',
           data: {
               datasets: [{
                   label: 'Scatter Dataset 2',
                   data: scatterData2,
                   backgroundColor: 'rgba(153, 102, 255, 0.6)'
               }]
           },
           options: {
               responsive: true,
               maintainAspectRatio: false,
               scales: {
                   x: {
                       type: 'linear',
                       position: 'bottom',
                       title: {
                           display: true,
                           text: '<?php echo $xLabel2; ?>'
                       }
                   },
                   y: {
                       title: {
                           display: true,
                           text: '<?php echo $yLabel2; ?>'
                       }
                   }
               }
           }
       });
   });
</script>
</body>
</html>
