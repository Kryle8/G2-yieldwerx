<?php
require __DIR__ . '/../connection.php';

// Pagination logic
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// Filters from selection_criteria.php
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

// Count total number of records with filters
$count_sql = "SELECT COUNT(*) AS total 
              FROM DEVICE_1_CP1_V1_0_001 d1
              JOIN WAFER w ON w.Wafer_Sequence = d1.Wafer_Sequence
              JOIN LOT l ON l.Lot_Sequence = w.Lot_Sequence
              JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
              JOIN DEVICE_1_CP1_V1_0_002 d2 ON d1.Die_Sequence = d2.Die_Sequence
              $where_clause";  // Append WHERE clause if it exists

$count_stmt = sqlsrv_query($conn, $count_sql, $params);
if ($count_stmt === false) {
    die('Query failed: ' . print_r(sqlsrv_errors(), true));
}
$total_rows = sqlsrv_fetch_array($count_stmt, SQLSRV_FETCH_ASSOC)['total'];
$total_pages = ceil($total_rows / $records_per_page);

// Dynamically construct the column part of the SQL query
$column_list = !empty($filters['tm.Column_Name']) ? implode(', ', array_map(function($col) { return "d1.$col"; }, $filters['tm.Column_Name'])) : '*';

// Retrieve records for the current page with filters
$tsql = "SELECT l.Facility_ID, l.Work_Center, l.Part_Type, l.Program_Name, l.Test_Temprature, l.Lot_ID,
                w.Wafer_ID, w.Wafer_Start_Time, w.Wafer_Finish_Time, d1.Unit_Number, d1.X, d1.Y, d1.Head_Number,
                d1.Site_Number, d1.HBin_Number, d1.SBin_Number, d1.Tests_Executed, d1.Test_Time, $column_list
         FROM DEVICE_1_CP1_V1_0_001 d1
         JOIN WAFER w ON w.Wafer_Sequence = d1.Wafer_Sequence
         JOIN LOT l ON l.Lot_Sequence = w.Lot_Sequence
         JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
         JOIN DEVICE_1_CP1_V1_0_002 d2 ON d1.Die_Sequence = d2.Die_Sequence
         $where_clause
         ORDER BY w.Wafer_ID
         OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";

$params = array_merge($params, [$offset, $records_per_page]);
$stmt = sqlsrv_query($conn, $tsql, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Generate query string for pagination links
$query_string = http_build_query(array_merge($_GET, ['page' => null]));
$query_string = preg_replace('/(&?page=null)|(&?page=\d+)/', '', $query_string);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wafer Dashboard</title>
    <link rel="stylesheet" href="../src/output.css">
    <script src="../path/to/flowbite/dist/flowbite.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/flowbite@2.4.1/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.4.1/dist/flowbite.min.js"></script>
    <style>
        .table-container {
            overflow-x: auto;
        }
    </style>
</head>
<body class="bg-gray-100 text-black">
<?php include('navbar.php'); ?>
<div class="flex justify-center items-center h-screen">
    <div class="w-full max-w-7xl p-6 rounded-lg shadow-lg bg-white mt-10">
        <h1 class="text-start text-2xl font-bold mb-4">Data Extraction [Total: <?php echo $total_rows; ?>]</h1>
        <div class="mb-4 text-right">
            <a href="export.php?<?php echo http_build_query($_GET); ?>" class="px-4 py-2 bg-green-500 text-white rounded">Export to CSV</a>
        </div>
        <div class="table-container">
            <table class="w-full border-collapse table-auto p-4">
                <thead>
                    <tr class="bg-blue-200">
                        <?php
                        $columns = [
                            'Facility_ID', 'Work_Center', 'Part_Type', 'Program_Name', 'Test_Temprature', 'Lot_ID',
                            'Wafer_ID', 'Wafer_Start_Time', 'Wafer_Finish_Time', 'Unit_Number', 'X', 'Y', 'Head_Number',
                            'Site_Number', 'HBin_Number', 'SBin_Number', 'Tests_Executed', 'Test_Time'
                        ];
                        // Merge static columns with dynamic columns
                        $all_columns = array_merge($columns, $filters['tm.Column_Name']);
                        foreach ($all_columns as $column) {
                            echo "<th class='border p-2'>$column</th>";
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                        echo "<tr>";
                        foreach ($all_columns as $column) {
                            $value = isset($row[$column]) ? $row[$column] : '';
                            if ($value instanceof DateTime) {
                                $value = $value->format('Y-m-d H:i:s'); // Adjust format as needed
                            }
                            echo "<td class='border p-2'>$value</td>";
                        }
                        echo "</tr>";
                    }                    
                    ?>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="mt-4 flex justify-center space-x-1">
            <?php if ($current_page > 1): ?>
                <a href="?<?php echo $query_string . '&page=' . ($current_page - 1); ?>" class="px-4 py-2 bg-gray-500 text-white rounded-l">Previous</a>
            <?php endif; ?>

            <?php
            // Show pages with "..." for large numbers of pages
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);
            if ($start_page > 1) {
                echo '<a href="?' . $query_string . '&page=1" class="px-4 py-2 bg-gray-200 text-black">1</a>';
                if ($start_page > 2) {
                    echo '<span class="px-4 py-2">...</span>';
                }
            }
            for ($i = $start_page; $i <= $end_page; $i++) {
                echo '<a href="?' . $query_string . '&page=' . $i . '" class="px-4 py-2 ' . ($i == $current_page ? 'bg-blue-500 text-white' : 'bg-gray-200 text-black') . '">' . $i . '</a>';
            }
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<span class="px-4 py-2">...</span>';
                }
                echo '<a href="?' . $query_string . '&page=' . $total_pages . '" class="px-4 py-2 bg-gray-200 text-black">' . $total_pages . '</a>';
            }
            ?>

            <?php if ($current_page < $total_pages): ?>
                <a href="?<?php echo $query_string . '&page=' . ($current_page + 1); ?>" class="px-4 py-2 bg-gray-500 text-white rounded-r">Next</a>
            <?php endif; ?>
        </div>

    </div>
</div>
</body>
</html>

<?php sqlsrv_free_stmt($stmt); ?>