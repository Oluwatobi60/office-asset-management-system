<?php
require "../include/config.php";

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'error' => ''];

if (isset($_POST['return_id'])) {
    $id = $_POST['return_id'];
    $return_date = date('Y-m-d');

    try {
        // Begin transaction
        $conn->beginTransaction();

        // First get the borrow details before updating
        $borrowSql = "SELECT asset_name, quantity FROM borrow_table WHERE id = :id AND returned = 0";
        $stmt = $conn->prepare($borrowSql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Asset not found or already returned');
        }
        
        $borrowDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        // Update borrow_table to mark as returned
        $updateSql = "UPDATE borrow_table SET returned = 1, returned_date = :return_date WHERE id = :id";
        $stmt = $conn->prepare($updateSql);
        $stmt->bindParam(':return_date', $return_date, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update borrow record');
        }

        // Update asset_table to return the quantity
        $updateAssetSql = "UPDATE asset_table SET quantity = quantity + :quantity WHERE asset_name = :asset_name";
        $stmt = $conn->prepare($updateAssetSql);
        $stmt->bindParam(':quantity', $borrowDetails['quantity'], PDO::PARAM_INT);
        $stmt->bindParam(':asset_name', $borrowDetails['asset_name'], PDO::PARAM_STR);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update asset quantity');
        }

        // If we got here, everything worked
        $conn->commit();
        $response['success'] = true;
        $response['message'] = 'Asset returned successfully';
        
    } catch (Exception $e) {
        // Rollback on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        // Log the error
        error_log("Error in return_asset.php: " . $e->getMessage());
        
        $response['success'] = false;
        $response['message'] = 'Failed to process return';
        $response['error'] = $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request: return_id not provided';
    $response['error'] = 'Missing required parameter';
}

echo json_encode($response);
?>