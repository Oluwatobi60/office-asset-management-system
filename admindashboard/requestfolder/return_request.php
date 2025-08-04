<?php
require "../include/config.php";

header('Content-Type: application/json');

if (isset($_POST['return_id'])) {
    $id = $_POST['return_id'];
    $return_date = date('Y-m-d');

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Update request_table to mark as returned
        $updateSql = "UPDATE request_table SET returned = 1, returned_date = ? WHERE id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("si", $return_date, $id);
        $stmt->execute();

        // Get the asset details to update quantity
        $assetSql = "SELECT asset_name, quantity FROM request_table WHERE id = ?";
        $stmt = $conn->prepare($assetSql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        // Update asset_table to return the quantity
        $updateAssetSql = "UPDATE asset_table SET quantity = quantity + ? WHERE asset_name = ?";
        $stmt = $conn->prepare($updateAssetSql);
        $stmt->bind_param("is", $row['quantity'], $row['asset_name']);
        $stmt->execute();

        // Commit transaction
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No return ID provided']);
}
?>