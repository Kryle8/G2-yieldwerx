<?php
require __DIR__ . '/../connection.php';

// Queries to populate selection options
$queries = [
    "facility" => "SELECT DISTINCT Facility_ID FROM lot",
    "work_center" => "SELECT DISTINCT Work_Center FROM lot",
    "device_name" => "SELECT DISTINCT Part_Type FROM lot",
    "test_program" => "SELECT DISTINCT Program_Name FROM lot",
    "lot" => "SELECT DISTINCT Lot_ID FROM lot",
    "wafer" => "SELECT DISTINCT Wafer_ID FROM wafer ORDER BY Wafer_ID ASC",
    "parameter" => "SELECT DISTINCT Column_Name FROM TEST_PARAM_MAP"
];

$options = [];
foreach ($queries as $key => $query) {
    $stmt = sqlsrv_query($conn, $query);
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $options[$key][] = $row;
    }
    sqlsrv_free_stmt($stmt);
}

// Custom natural sorting function
function naturalSort($a, $b) {
    return strnatcmp($a['Column_Name'], $b['Column_Name']);
}

// Sort parameters using natural sorting
usort($options['parameter'], 'naturalSort');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selection Criteria</title>
    <link rel="stylesheet" href="../src/output.css">
    <script src="../path/to/flowbite/dist/flowbite.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/flowbite@2.4.1/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.4.1/dist/flowbite.min.js"></script>
</head>
<style>
    body {
        background-color: #F5EDED;
    }
    select:not([size]) {
        background: white !important;
    }
</style>
<?php include('navbar.php'); ?>
<div class="container mx-auto p-6">
    <form action="dashboard.php" method="GET">
        <div class="grid grid-cols-2 gap-4 mb-4">
            <?php
            $fields = [
                "facility" => "Facility",
                "work_center" => "Work Center",
                "device_name" => "Device Name",
                "test_program" => "Test Program",
                "lot" => "Lot",
                "wafer" => "Wafer",
                "parameter" => "Parameter"
            ];

            foreach ($fields as $key => $label) {
                echo "<div>
                        <label for='$key' class='block text-sm font-medium text-gray-700'>$label</label>
                        <select id='$key' name='{$key}[]' class='mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md' multiple>";
                if (isset($options[$key])) {
                    foreach ($options[$key] as $option) {
                        $value = array_values($option)[0];
                        echo "<option value='$value'>$value</option>";
                    }
                }
                echo "  </select>
                      </div>";
            }
            ?>
        </div>
        <div class="text-center">
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">Execute</button>
        </div>
    </form>
</div>
</body>
</html>
