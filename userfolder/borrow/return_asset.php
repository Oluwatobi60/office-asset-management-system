<?php
require "../../include/config.php";

header('Content-Type: application/json');

if (isset($_POST['return_id'])) {
    $id = $_POST['return_id'];
    $return_date = date('Y-m-d');

    try {
        // Begin transaction
        $conn->beginTransaction();

        // Get borrow details first
        $borrowSql = "SELECT asset_name, quantity FROM borrow_table WHERE id = :id";
        $borrowStmt = $conn->prepare($borrowSql);
        $borrowStmt->bindValue(':id', $id, PDO::PARAM_INT);
        $borrowStmt->execute();
        $borrowDetails = $borrowStmt->fetch(PDO::FETCH_ASSOC);

        if (!$borrowDetails) {
            throw new Exception('Borrow record not found');
        }

        // Update borrow_table to mark as returned
        $updateSql = "UPDATE borrow_table SET returned = 1, returned_date = :return_date WHERE id = :id";
        $stmt = $conn->prepare($updateSql);
        $stmt->bindValue(':return_date', $return_date, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update borrow record');
        }

        // Update asset_table to return the quantity
        $updateAssetSql = "UPDATE asset_table SET quantity = quantity + :qty WHERE asset_name = :asset_name";
        $stmt = $conn->prepare($updateAssetSql);
        $stmt->bindValue(':qty', $borrowDetails['quantity'], PDO::PARAM_INT);
        $stmt->bindValue(':asset_name', $borrowDetails['asset_name'], PDO::PARAM_STR);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update asset quantity');
        }

        // Commit transaction
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Asset returned successfully']);
        
    } catch (Exception $e) {
        // Rollback on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No return ID provided']);
}
?>