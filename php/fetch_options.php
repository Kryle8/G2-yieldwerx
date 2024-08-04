<?php
require __DIR__ . '/../connection.php';

if (isset($_GET['query'])) {
    $query = $_GET['query'];
    $stmt = sqlsrv_query($conn, $query);

    $options = [];
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $options[] = array_values($row)[0];
        }
    }

    echo json_encode($options);
}
?>
