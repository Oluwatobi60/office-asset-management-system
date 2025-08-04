<?php
require "../include/config.php";

if (isset($_GET['asset'])) {
    $assetName = $_GET['asset'];
    $sql = "SELECT quantity FROM asset_table WHERE asset_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $assetName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['quantity' => $row['quantity']]);
    } else {
        echo json_encode(['error' => 'Asset not found']);
    }
} else {
    echo json_encode(['error' => 'No asset specified']);
}
?>
