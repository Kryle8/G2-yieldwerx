<?php
require __DIR__ . '/../connection.php';

$groupLot = isset($_GET['group_lot']) ? true : false;
$groupWafer = isset($_GET['group_wafer']) ? true : false;

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

// Initialize the data array
$data = [];
$xLabel = $filters['tm.Column_Name'][0] ?? 'X';
$yLabel = $filters['tm.Column_Name'][1] ?? 'Y';
$xTestName = '';
$yTestName = '';

// Query to fetch data for the chart
$tsql = "SELECT w.Wafer_ID, d1.{$filters['tm.Column_Name'][0]} AS X, d1.{$filters['tm.Column_Name'][1]} AS Y, tm.Test_Name
         FROM DEVICE_1_CP1_V1_0_001 d1
         JOIN WAFER w ON w.Wafer_Sequence = d1.Wafer_Sequence
         JOIN LOT l ON l.Lot_Sequence = w.Lot_Sequence
         JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
         JOIN DEVICE_1_CP1_V1_0_002 d2 ON d1.Die_Sequence = d2.Die_Sequence
         $where_clause";

$stmt = sqlsrv_query($conn, $tsql, $params);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Fetch and prepare data for Chart.js
$groupedData = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    if (!$xTestName) {
        $xTestName = $row['Test_Name'];
    } elseif (!$yTestName) {
        $yTestName = $row['Test_Name'];
    }
    $waferID = $row['Wafer_ID'];
    $xValue = floatval($row['X']);
    $yValue = floatval($row['Y']);
    
    if ($groupWafer) {
        $groupedData[$waferID][] = ['x' => $xValue, 'y' => $yValue];
    } else {
        $groupedData['all'][] = ['x' => $xValue, 'y' => $yValue];
    }
}
sqlsrv_free_stmt($stmt);

// Sort groupedData by wafer ID
if ($groupWafer) {
    ksort($groupedData);
}

// Calculate the number of distinct wafer IDs
$numDistinctWafers = count($groupedData);
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
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   <style>
       .chart-container {
           overflow: auto;
           max-height: 75vh;
           max-width: 100%;
       }
       table {
           width: 100%;
           border-collapse: collapse;
       }
       td {
           padding: 16px;
       }
       canvas{
        height:300px;
       }
   </style>
</head>
<body class="bg-gray-50">
<?php include('admin_components.php'); ?>
<div class="p-4 sm:ml-32">
    <div class="p-4 rounded-lg dark:border-gray-700 mt-14">
        <h1 class="text-center text-2xl font-bold mb-4 w-full">XY Scatter Plot</h1>
        <div class="chart-container">
            <table>
                <tbody>
                    <tr>
                    <?php
                    if ($groupWafer) {
                        foreach ($groupedData as $waferID => $data) {
                            echo '<td><div class="flex items-center justify-start flex-col"><h2 class="text-center text-xl font-semibold">Wafer ID: ' . $waferID . '</h2>';
                            echo '<canvas id="chartXY_' . $waferID . '"></canvas></div></td>';
                        }
                    } else {
                        echo '<td><canvas id="chartXY_all"></canvas></td>';
                    }
                    ?>
                    </tr>
                </tbody>
            </table>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const groupedData = <?php echo json_encode($groupedData); ?>;
            const xTestName = <?php echo json_encode($xTestName); ?>;
            const yTestName = <?php echo json_encode($yTestName); ?>;

            if (groupedData['all']) {
                new Chart(document.getElementById('chartXY_all').getContext('2d'), {
                    type: 'scatter',
                    data: {
                        datasets: [{
                            label: xTestName + ' vs. ' + yTestName,
                            data: groupedData['all'].map(d => ({ x: d.x, y: d.y })),
                            backgroundColor: 'rgba(192, 192, 75, 0.6)'
                        }]
                    },
                    options: {
                        scales: {
                            x: {
                                type: 'linear',
                                position: 'bottom',
                                title: {
                                    display: true,
                                    text: xTestName
                                }
                            },
                            y: {
                                type: 'linear',
                                title: {
                                    display: true,
                                    text: yTestName
                                }
                            }
                        }
                    }
                });
            } else {
                for (const waferID in groupedData) {
                    const data = groupedData[waferID];

                    new Chart(document.getElementById('chartXY_' + waferID).getContext('2d'), {
                        type: 'scatter',
                        data: {
                            datasets: [{
                                label: xTestName + ' vs. ' + yTestName,
                                data: data.map(d => ({ x: d.x, y: d.y })),
                                backgroundColor: 'rgba(192, 192, 75, 0.6)'
                            }]
                        },
                        options: {
                            scales: {
                                x: {
                                    type: 'linear',
                                    position: 'bottom',
                                    title: {
                                        display: true,
                                        text: xTestName
                                    }
                                },
                                y: {
                                    type: 'linear',
                                    title: {
                                        display: true,
                                        text: yTestName
                                    }
                                }
                            }
                        }
                    });
                }
            }
        });
        </script>
    </div>
</div>
</body>
</html>
