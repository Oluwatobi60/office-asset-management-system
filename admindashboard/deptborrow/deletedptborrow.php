<?php
 require "../../admindashboard/include/config.php";
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
                window.location.href = 'deletedptborrow.php?id=" . $id . "&confirm=yes';            } else {
                window.location.href = '../borrowdept.php';
            }
        </script>
        ";
    } elseif ($_GET['confirm'] === 'yes') {
        // Begin transaction
        $conn->beginTransaction();

        try {
            // First, get the request details including returned status
            $selectSql = "SELECT asset_name, quantity, returned FROM department_borrow_table WHERE id = :id";
            $selectStmt = $conn->prepare($selectSql);
            $selectStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $selectStmt->execute();
            $request = $selectStmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                throw new Exception('Request not found');
            }

            if ($request['returned'] == 1) {
                // If returned, just delete without updating request_table
                $deleteSql = "DELETE FROM department_borrow_table WHERE id = :id";
                $deleteStmt = $conn->prepare($deleteSql);
                $deleteStmt->bindParam(':id', $id, PDO::PARAM_INT);
                if (!$deleteStmt->execute() || $deleteStmt->rowCount() === 0) {
                    throw new Exception('Failed to delete the record');
                }
                $conn->commit();
                echo "<script>alert('Request deleted successfully');</script>";
                echo "<script>window.location.href = '../borrowdept.php';</script>";
            } else {
                // If not returned, update request_table then delete
                $updateAssetSql = "UPDATE request_table SET quantity = quantity + :quantity 
                                 WHERE asset_name = :asset_name";
                $updateAssetStmt = $conn->prepare($updateAssetSql);
                $updateAssetStmt->bindParam(':quantity', $request['quantity'], PDO::PARAM_INT);
                $updateAssetStmt->bindParam(':asset_name', $request['asset_name'], PDO::PARAM_STR);
                if (!$updateAssetStmt->execute()) {
                    throw new Exception('Failed to update asset quantity');
                }
                $deleteSql = "DELETE FROM department_borrow_table WHERE id = :id";
                $deleteStmt = $conn->prepare($deleteSql);
                $deleteStmt->bindParam(':id', $id, PDO::PARAM_INT);
                if (!$deleteStmt->execute() || $deleteStmt->rowCount() === 0) {
                    throw new Exception('Failed to delete the record');
                }
                $conn->commit();
                echo "<script>alert('Request deleted successfully and asset quantity updated');</script>";
                echo "<script>window.location.href = '../borrowdept.php';</script>";
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollBack();
            logError("Error in delete request: " . $e->getMessage());
            echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
            echo "<script>window.location.href = '../borrowdept.php';</script>";
        }
    }
} catch (Exception $e) {
    logError("Error in delete request: " . $e->getMessage());
    echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    echo "<script>window.location.href = '../borrowdept.php';</script>";
}

ob_end_flush();
?>

