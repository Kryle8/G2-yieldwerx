<?php
require __DIR__ . '/../connection.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=wafer_data.csv');

$output = fopen('php://output', 'w');

// Add column headers
$columns = [
    'Facility_ID', 'Work_Center', 'Part_Type', 'Program_Name', 'Test_Temprature', 'Lot_ID', 'Wafer_ID', 
    'Wafer_Start_Time', 'Wafer_Finish_Time', 'Unit_Number', 'X', 'Y', 'Head_Number', 'Site_Number', 
    'HBin_Number', 'SBin_Number', 'Tests_Executed', 'Test_Time', 'T4', 'T5', 'T6', 'T7'
];
fputcsv($output, $columns);

// Fetch all data from the query
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
                  d1.Site_Number, d1.HBin_Number, d1.SBin_Number, d1.Tests_Executed, d1.Test_Time, d1.T4, d1.T5, d1.T6, d1.T7";

$stmt = sqlsrv_query($conn, $tsql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $csv_row = [];
    foreach ($columns as $column) {
        $value = $row[$column] ?? '';
        if ($value instanceof DateTime) {
            $csv_row[] = $value->format('Y-m-d H:i:s');
        } else {
            // Convert all values to string
            $csv_row[] = (string)$value;
        }
    }
    fputcsv($output, $csv_row);
}

fclose($output);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
