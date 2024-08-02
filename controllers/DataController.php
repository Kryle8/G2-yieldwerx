<?php
    require_once('libs/Database.php');
    require_once('models/Data.php');

    class DataController {
        private $conn;

        public function __construct() {
            $database = new Database();
            $this->conn = $database->connect();
        }
        
        public function getAllData() {
            $sql = "SELECT * FROM DEVICE_1_CP1_V1_0_001 d1 ORDER BY Wafer_ID ASC";
            $result = mysqli_query($this->conn, $sql);

            $data = [];
            while($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }

            $response['data'] = $data;
            $response['message'] = 'Data from DEVICE_1_CP1_V1_0_001 d1.';

            return $response;
        }
    }
?>