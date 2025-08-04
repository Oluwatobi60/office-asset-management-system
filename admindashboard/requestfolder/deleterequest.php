<?php
require_once "../include/config.php";
require_once "../../include/utils.php";

// Start output buffering to prevent header issues
ob_start();

try {
    // Check if ID is provided and is numeric
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid request ID');
    }

    $id = intval($_GET['id']); // Sanitize the input

    // If confirmation is not yet received, show confirmation dialog
    if (!isset($_GET['confirm'])) {
        echo "
        <script>
            var confirmDelete = confirm('Are you sure you want to delete this request?');
            if (confirmDelete) {
                window.location.href = 'deleterequest.php?id=" . $id . "&confirm=yes';
            } else {
                window.location.href = '../requestasset.php';
            }
        </script>
        ";
    } elseif ($_GET['confirm'] === 'yes') {
        // Begin transaction
        $conn->beginTransaction();

        try {
            // First, get the request details to update asset quantity
            $selectSql = "SELECT asset_name, quantity FROM request_table WHERE id = :id";
            $selectStmt = $conn->prepare($selectSql);
            $selectStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $selectStmt->execute();
            
            $request = $selectStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$request) {
                throw new Exception('Request not found');
            }

            // Update asset quantity - add back the requested quantity
            $updateAssetSql = "UPDATE asset_table SET quantity = quantity + :quantity 
                             WHERE asset_name = :asset_name";
            $updateAssetStmt = $conn->prepare($updateAssetSql);
            $updateAssetStmt->bindParam(':quantity', $request['quantity'], PDO::PARAM_INT);
            $updateAssetStmt->bindParam(':asset_name', $request['asset_name'], PDO::PARAM_STR);
            $updateAssetStmt->execute();

            // Now delete the request
            $deleteSql = "DELETE FROM request_table WHERE id = :id";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $deleteStmt->execute();

            // Commit the transaction
            $conn->commit();
            
            echo "<script>alert('Request deleted successfully and asset quantity updated');</script>";
            echo "<script>window.location.href = '../requestasset.php';</script>";
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollBack();
            logError("Error in delete request: " . $e->getMessage());
            echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
            echo "<script>window.location.href = '../requestasset.php';</script>";
        }
    }
} catch (Exception $e) {
    logError("Error in delete request: " . $e->getMessage());
    echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    echo "<script>window.location.href = '../requestasset.php';</script>";
}

ob_end_flush();
?>

