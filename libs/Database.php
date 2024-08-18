<?php
    require_once '../config.php';

// $serverName = $_ENV['DB_SERVERNAME'];
// $database = $_ENV['DB_DATABASE'];
// $uid = $_ENV['DB_USERNAME'];
// $pass = $_ENV['DB_PASSWORD'];

// $connection = [
//     "Database" => $database,
//     "UID" => $uid,
//     "PWD" => $pass,
//     "TrustServerCertificate" => true // Trust the server certificate
// ];

// $conn = sqlsrv_connect($serverName, $connection);

// if (!$conn) {
//     die(print_r(sqlsrv_errors(), true));
// }

    $env = parse_ini_file('../.env');

    define('SERVER', $env['DB_SERVERNAME']);
    define('DATABASE', $env['DB_DATABASE']);
    define('UID', $env['DB_USERNAME']);
    define('PWD', $env['DB_PASSWORD']);

    class Database {
        public $serverName = SERVER;
        public $connectionInfo = [
            "Database" => DATABASE,
            "UID" => UID,
            "PWD" => PWD
        ];

        public function connect() {
            $conn = sqlsrv_connect( $this->serverName, $this->connectionInfo);

            if( $conn == false ) {
                die( print_r( sqlsrv_errors(), true));
            }

            return $conn;
        }
    }
?>
