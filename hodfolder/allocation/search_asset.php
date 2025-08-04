<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require "../../admindashboard/include/config.php";

header('Content-Type: application/json');

$query = isset($_GET['q']) ? $_GET['q'] : '';
// Modified query to include quantity and cast it as integer
$sql = "SELECT asset_name, reg_no, category, description, CAST(quantity AS SIGNED) as quantity 
        FROM asset_table WHERE asset_name LIKE ?";
$stmt = $conn->prepare($sql);
$searchTerm = "%" . $query . "%";
$stmt->bind_param("s", $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$assets = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Ensure quantity is treated as a number
        $row['quantity'] = intval($row['quantity']);
        $assets[] = $row;
    }
}

echo json_encode($assets);
?>
