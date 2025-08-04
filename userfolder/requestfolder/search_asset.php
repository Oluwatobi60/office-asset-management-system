<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require "../../admindashboard/include/config.php";

$query = isset($_GET['q']) ? $_GET['q'] : '';
$sql = "SELECT asset_name, reg_no, category, description FROM asset_table WHERE asset_name LIKE ?";
$stmt = $conn->prepare($sql);
$searchTerm = "%" . $query . "%";
$stmt->bind_param("s", $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$assets = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $assets[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($assets);
?>
