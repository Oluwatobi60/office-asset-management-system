<?php
require_once "../include/config.php";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get table structure
$result = $conn->query("DESCRIBE department_table");
echo "Table structure:\n";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}

// Get department data
$result = $conn->query("SELECT * FROM department_table");
echo "\n\nDepartment data:\n";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}

$conn->close();
?>
