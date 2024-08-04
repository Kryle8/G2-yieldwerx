<?php
require __DIR__ . '/../connection.php';

// Pagination logic
$records_per_page = 10; // Number of records per page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// Count total number of records
$count_sql = "SELECT COUNT(*) AS total FROM (SELECT COUNT(*) AS total
         FROM DEVICE_1_CP1_V1_0_001 d1
         JOIN WAFER w ON w.Wafer_Sequence = d1.Wafer_Sequence
         JOIN LOT l ON l.Lot_Sequence = w.Lot_Sequence
         JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
         JOIN DEVICE_1_CP1_V1_0_002 d2 ON d1.Die_Sequence = d2.Die_Sequence
         GROUP BY l.Facility_ID, l.Work_Center, l.Part_Type, l.Program_Name, l.Test_Temprature, l.Lot_ID,
                  w.Wafer_ID, w.Wafer_Start_Time, w.Wafer_Finish_Time, d1.Unit_Number, d1.X, d1.Y, d1.Head_Number,
                  d1.Site_Number, d1.HBin_Number, d1.SBin_Number, d1.Tests_Executed, d1.Test_Time, d1.T4, d1.T5, d1.T6, d1.T7) AS grouped_data";
$count_stmt = sqlsrv_query($conn, $count_sql);
$total_rows = sqlsrv_fetch_array($count_stmt, SQLSRV_FETCH_ASSOC)['total'];
$total_pages = ceil($total_rows / $records_per_page);

// Retrieve records for the current page
$tsql = "SELECT l.Facility_ID, l.Work_Center, l.Part_Type, l.Program_Name, l.Test_Temprature, l.Lot_ID,
                w.Wafer_ID, w.Wafer_Start_Time, w.Wafer_Finish_Time, d1.Unit_Number, d1.X, d1.Y, d1.Head_Number,
                d1.Site_Number, d1.HBin_Number, d1.SBin_Number, d1.Tests_Executed, d1.Test_Time, d1.T4, d1.T5, d1.T6, d1.T7
         FROM DEVICE_1_CP1_V1_0_001 d1
         JOIN WAFER w ON w.Wafer_Sequence = d1.Wafer_Sequence
         JOIN LOT l ON l.Lot_Sequence = w.Lot_Sequence
         JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
         JOIN DEVICE_1_CP1_V1_0_002 d2 ON d1.Die_Sequence = d2.Die_Sequence
         GROUP BY l.Facility_ID, l.Work_Center, l.Part_Type, l.Program_Name, l.Test_Temprature, l.Lot_ID,
                  w.Wafer_ID, w.Wafer_Start_Time, w.Wafer_Finish_Time, d1.Unit_Number, d1.X, d1.Y, d1.Head_Number,
                  d1.Site_Number, d1.HBin_Number, d1.SBin_Number, d1.Tests_Executed, d1.Test_Time, d1.T4, d1.T5, d1.T6, d1.T7
         ORDER BY w.Wafer_ID
         OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";

$params = array($offset, $records_per_page);
$stmt = sqlsrv_query($conn, $tsql, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
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
<body class="bg-gray-100 flex justify-center text-black">
<div class="flex flex-col">
<?php include('navbar.php'); ?>
    <div class="w-full max-w-7xl p-6 rounded-lg shadow-lg bg-white">
        <h1 class="text-start text-2xl font-bold mb-4">Data Extraction [Total: <?php echo $total_rows; ?>]</h1>
        <div class="mb-4 text-right">
            <a href="export.php" class="px-4 py-2 bg-green-500 text-white rounded">Export to CSV</a>
        </div>
        <div class="table-container">
            <table class="w-full border-collapse table-auto p-4">
                <thead>
                    <tr class="bg-gray-200">
                        <?php
                        $columns = [
                            'Facility_ID', 'Work_Center', 'Part_Type', 'Program_Name', 'Test_Temprature', 'Lot_ID',
                            'Wafer_ID', 'Wafer_Start_Time', 'Wafer_Finish_Time', 'Unit_Number', 'X', 'Y', 'Head_Number',
                            'Site_Number', 'HBin_Number', 'SBin_Number', 'Tests_Executed', 'Test_Time', 'T4', 'T5', 'T6', 'T7'
                        ];
                        foreach ($columns as $column) {
                            echo "<th class='border px-4 py-2 whitespace-nowrap'>$column</th>";
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                    <tr class="even:bg-gray-100">
                        <?php foreach ($columns as $column): ?>
                        <td class="border px-4 py-2 whitespace-nowrap">
                            <?php 
                            $value = $row[$column] ?? '';
                            if ($value instanceof DateTime) {
                                echo htmlspecialchars($value->format('Y-m-d H:i:s'));
                            } else {
                                echo htmlspecialchars($value);
                            }
                            ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="mt-4 flex justify-center space-x-1">
            <?php if ($current_page > 1): ?>
                <a href="?page=<?php echo $current_page - 1; ?>" class="px-4 py-2 bg-blue-500 text-white rounded-l">Previous</a>
            <?php endif; ?>

            <?php
            // Show pages with "..." for large numbers of pages
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);
            if ($start_page > 1) {
                echo '<a href="?page=1" class="px-4 py-2 bg-gray-200 text-black">1</a>';
                if ($start_page > 2) {
                    echo '<span class="px-4 py-2">...</span>';
                }
            }
            for ($i = $start_page; $i <= $end_page; $i++) {
                echo '<a href="?page=' . $i . '" class="px-4 py-2 ' . ($i == $current_page ? 'bg-blue-500 text-white' : 'bg-gray-200 text-black') . '">' . $i . '</a>';
            }
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<span class="px-4 py-2">...</span>';
                }
                echo '<a href="?page=' . $total_pages . '" class="px-4 py-2 bg-gray-200 text-black">' . $total_pages . '</a>';
            }
            ?>

            <?php if ($current_page < $total_pages): ?>
                <a href="?page=<?php echo $current_page + 1; ?>" class="px-4 py-2 bg-blue-500 text-white rounded-r">Next</a>
            <?php endif; ?>
        </div>
    </div>
</div>
    <?php
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    ?>
</body>
</html>
