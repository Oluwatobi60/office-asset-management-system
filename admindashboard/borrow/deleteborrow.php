<?php
require_once "../include/config.php";
require_once "../../include/utils.php";

// Start output buffering to prevent header issues
ob_start();

try {
    // Check if ID is provided and is numeric
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid borrow record ID');
    }

    $id = intval($_GET['id']); // Sanitize the input

    // If confirmation is not yet received, show confirmation dialog
    if (!isset($_GET['confirm'])) {
        echo "
        <script>
            var confirmDelete = confirm('Are you sure you want to delete this borrow record?');
            if (confirmDelete) {
                window.location.href = 'deleteborrow.php?id=" . $id . "&confirm=yes';
            } else {
                window.location.href = '../borrowasset.php';
            }
        </script>
        ";
    } elseif ($_GET['confirm'] === 'yes') {
        // Begin transaction
        $conn->beginTransaction();

        try {
            // First, get the borrow details to update asset quantity
            $selectSql = "SELECT asset_name, quantity FROM borrow_table WHERE id = :id";
            $selectStmt = $conn->prepare($selectSql);
            $selectStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $selectStmt->execute();
            
            $borrow = $selectStmt->fetch(PDO::FETCH_ASSOC);
            // Check if the borrow record exists
            if (!$borrow) {
                throw new Exception('Borrow record not found');
            }

            // Update asset quantity - add back the borrowed quantity
            $updateAssetSql = "UPDATE asset_table SET quantity = quantity + :quantity 
                             WHERE asset_name = :asset_name";
            $updateAssetStmt = $conn->prepare($updateAssetSql);
            $updateAssetStmt->bindParam(':quantity', $borrow['quantity'], PDO::PARAM_INT);
            $updateAssetStmt->bindParam(':asset_name', $borrow['asset_name'], PDO::PARAM_STR);
            
            if (!$updateAssetStmt->execute()) {
                throw new Exception('Failed to update asset quantity');
            }

            // Delete the borrow record
            $deleteSql = "DELETE FROM borrow_table WHERE id = :id";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if (!$deleteStmt->execute()) {
                throw new Exception('Failed to delete borrow record');
            }

            // Commit the transaction
            $conn->commit();

            echo "<script>
                alert('Borrow record deleted successfully and asset quantity updated');
                window.location.href = '../borrowasset.php';
            </script>";

        } catch (Exception $e) {
            // Rollback transaction on error
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log("Error in delete borrow record: " . $e->getMessage());
            echo "<script>
                alert('Error: " . addslashes($e->getMessage()) . "');
                window.location.href = '../borrowasset.php';
            </script>";
        }
    }
} catch (Exception $e) {
    error_log("Error in delete borrow record: " . $e->getMessage());
    echo "<script>
        alert('Error: " . addslashes($e->getMessage()) . "');
        window.location.href = '../borrowasset.php';
    </script>";
}

ob_end_flush();
?>

