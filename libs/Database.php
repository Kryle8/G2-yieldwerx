<?php
$env = parse_ini_file('.env');

define('HOST', $env['HOST']);
define('USER', $env['USER']);
define('PASSWORD', $env['PASSWORD']);
define('DATABASE', $env['DATABASE']);
define('PORT', $env['PORT']);

class Database {
    public $host = HOST;
    public $user = USER;
    public $pass = PASSWORD;
    public $database = DATABASE;
    public $port = PORT;

    public function connect() {
        $conn = new mysqli($this->host, $this->user, $this->pass, $this->database, $this->port);

        if (mysqli_connect_errno()) {
            printf("Connection failed: %s", mysqli_connect_error());
            exit();
        } 

        return $conn;
    }
}
?>