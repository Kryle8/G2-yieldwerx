<?php
require __DIR__ . '/../connection.php';

$query = $_GET['query'];
$result = sqlsrv_query($conn, $query);

$options = [];

if ($query === "SELECT DISTINCT Wafer_ID, Wafer_Sequence FROM wafer ORDER BY Wafer_ID ASC") {
    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        $options[] = $row['Wafer_ID'] . '-' . $row['Wafer_Sequence'];
    }
} else {
    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        $options[] = reset($row);
    }
}

echo json_encode($options);

sqlsrv_free_stmt($result);
sqlsrv_close($conn);
?>
