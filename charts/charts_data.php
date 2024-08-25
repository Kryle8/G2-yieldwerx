<?php
require_once('../controllers/TableController.php');

// Initialize the TableController to fetch data
$tableController = new TableController();
$tableController->init();

// Define the response format for the JavaScript front-end
header('Content-Type: application/json');

// Fetch data using your existing method
$data = []; // This will hold your formatted data

// Adjust the query and data extraction based on your need
$query = "SELECT your_column FROM your_table"; // Replace with your actual query
$stmt = sqlsrv_query($tableController->conn, $query, $tableController->params);
if ($stmt === false) {
    die(json_encode(['error' => 'Query failed: ' . print_r(sqlsrv_errors(), true)]));
}

// Example: Aggregate data for the cumulative probability chart
$values = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $values[] = $row['your_column'];
}

sqlsrv_free_stmt($stmt);

// Sort the values for cumulative distribution calculation
sort($values);

// Calculate cumulative probabilities
$total = count($values);
$cumulative_data = [];
foreach ($values as $index => $value) {
    $cumulative_data[] = [
        'value' => $value,
        'probability' => ($index + 1) / $total
    ];
}

echo json_encode($cumulative_data);
?>
