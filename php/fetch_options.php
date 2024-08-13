<?php
require __DIR__ . '/../connection.php';

$value = $_GET['value'];
$type = $_GET['type'];

switch ($type) {
    case 'work_center':
        $query = "SELECT DISTINCT Work_Center FROM lot WHERE Facility_ID IN ('" . implode("','", $value) . "')";
        break;
    case 'device_name':
        $query = "SELECT DISTINCT Part_Type FROM lot WHERE Work_Center IN ('" . implode("','", $value) . "')";
        break;
    case 'test_program':
        $query = "SELECT DISTINCT l.Program_Name
                    FROM lot l
                    JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
                    WHERE Part_Type IN ('" . implode("','", $value) . "')
                    ORDER BY l.Program_Name ASC";
        break;
    case 'lot':
        $query = "SELECT DISTINCT l.Lot_ID
                    FROM lot l
                    JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
                    WHERE l.Program_Name IN ('" . implode("','", $value) . "')";
        break;
    case 'wafer':
        $query = "SELECT DISTINCT wafer.Wafer_ID 
                  FROM wafer 
                  JOIN lot ON lot.Lot_Sequence = wafer.Lot_Sequence 
                  WHERE lot.Lot_ID IN ('" . implode("','", $value) . "')
                  ORDER BY wafer.Wafer_ID";
        break;
    case 'parameter':
        $query = "SELECT DISTINCT tm.Column_Name, tm.Test_Name 
                  FROM TEST_PARAM_MAP tm 
                  JOIN wafer ON wafer.Lot_Sequence = tm.Lot_Sequence 
                  WHERE wafer.Wafer_ID IN ('" . implode("','", $value) . "') 
                  AND tm.Column_Name LIKE 'T%'";
        break;
    default:
        $query = "";
}

$options = [];
if ($query) {
    $stmt = sqlsrv_query($conn, $query);
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if ($type == 'parameter') {
            $options[] = ['value' => $row['Column_Name'], 'display' => $row['Test_Name']];
        } else {
            $options[] = array_values($row)[0];
        }
    }
    sqlsrv_free_stmt($stmt);
}

echo json_encode($options);

sqlsrv_close($conn);
?>
