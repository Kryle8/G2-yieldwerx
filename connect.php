<?php
// phpinfo();
$serverName = "RYL";
$connectionOptions = [
    "Database"=>"yieldWerx_OJT2024",
    "Uid"=>"",
    "PWD"=>""
];
$conn = sqlsrv_connect($serverName, $connectionOptions);
if($conn == false)
    die( print_r( sqlsrv_errors(), true));