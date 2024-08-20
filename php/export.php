<?php
require_once '../controllers/TableController.php';

// Set headers for CSV export
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=wafer_data.csv');

// Instantiate the TableController with the test program parameter
$testProgram = isset($_GET['test_program']) ? $_GET['test_program'] : [];
$controller = new TableController($testProgram);

// Initialize the controller to build the query
$controller->init();

// Retrieve headers and data
$headers = $controller->getHeaders();
$data = $controller->fetchData(0, $controller->getTotalRows());

// Open the output stream
$output = fopen('php://output', 'w');

// Add column headers to the CSV
fputcsv($output, $headers);

// Add rows of data to the CSV
foreach ($data as $row) {
    fputcsv($output, $row);
}

// Close the output stream
fclose($output);
