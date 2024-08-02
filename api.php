<?php
    require_once('controllers/DataController.php');



    if($_SERVER['REQUEST_METHOD'] == 'GET') {
        if (isset($_GET['action'])) {
            $action = $_GET['action'];

            switch ($action) {
                case 'getAllData':
                    $dataController = new DataController();
                    $response = $dataController->getAllData();

                    echo json_encode($response);
                    break;
            }
        }
    }
?>