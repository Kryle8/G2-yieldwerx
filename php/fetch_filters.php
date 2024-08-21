<?php
    require_once('../controllers/SelectionController.php');

    $value = $_GET['value'];

    $controller = new SelectionController();
    $filter = $controller->getFilters($value);
    
    echo json_encode($filter);
