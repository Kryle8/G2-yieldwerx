<?php

require __DIR__ . '/config.php';

$serverName = $_ENV['DB_SERVERNAME'];
$database = $_ENV['DB_DATABASE'];
$uid = $_ENV['DB_USERNAME'];
$pass = $_ENV['DB_PASSWORD'];

$connection = [
    "Database" => $database,
    "UID" => $uid,
    "PWD" => $pass,
    "TrustServerCertificate" => true // Trust the server certificate
];

$conn = sqlsrv_connect($serverName, $connection);

if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}
?>
