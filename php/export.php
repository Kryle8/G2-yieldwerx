<?php
require __DIR__ . '/../connection.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=wafer_data.csv');

$output = fopen('php://output', 'w');

// Get filters from the GET parameters
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

// Fetch all data from the query
$tsql = "SELECT l.Facility_ID, l.Work_Center, l.Part_Type, l.Program_Name, l.Test_Temprature, l.Lot_ID,
                w.Wafer_ID, w.Wafer_Start_Time, w.Wafer_Finish_Time, d1.Unit_Number, d1.X, d1.Y, d1.Head_Number,
                d1.Site_Number, d1.HBin_Number, d1.SBin_Number, d1.Tests_Executed, d1.Test_Time, $column_list
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

// Add column headers
$columns = [
    'Facility_ID', 'Work_Center', 'Part_Type', 'Program_Name', 'Test_Temprature', 'Lot_ID', 'Wafer_ID', 
    'Wafer_Start_Time', 'Wafer_Finish_Time', 'Unit_Number', 'X', 'Y', 'Head_Number', 'Site_Number', 
    'HBin_Number', 'SBin_Number', 'Tests_Executed', 'Test_Time'
];
// Merge static columns with dynamic columns
$all_columns = array_merge($columns, $filters['tm.Column_Name']);

fputcsv($output, $all_columns);

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $csv_row = [];
    foreach ($all_columns as $column) {
        $value = $row[$column] ?? '';
        if ($value instanceof DateTime) {
            $csv_row[] = $value->format('Y-m-d H:i:s');
        } else {
            $csv_row[] = (string)$value;
        }
    }
    fputcsv($output, $csv_row);
}

fclose($output);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>