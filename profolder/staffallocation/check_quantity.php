<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require "../include/config.php";

header('Content-Type: application/json');

try {
    if (!isset($_GET['asset'])) {
        throw new Exception('Asset name not provided');
    }

    $assetName = $_GET['asset'];
    
    $sql = "SELECT CAST(quantity AS SIGNED) as quantity FROM asset_table WHERE asset_name = :asset";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':asset', $assetName, PDO::PARAM_STR);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode(['quantity' => intval($result['quantity'])]);
    } else {
        echo json_encode(['quantity' => 0]);
    }
} catch (Exception $e) {
    error_log("Error in check_quantity.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
