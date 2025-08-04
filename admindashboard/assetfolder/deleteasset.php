<?php
require_once dirname(__FILE__) . "/../include/config.php";
require_once dirname(__FILE__) . "/../../include/utils.php";

try {
    // Validate input
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception("Invalid asset ID");
    }
    
    $id = (int)$_GET['id'];

    if (!isset($_GET['confirm'])) {
        // Show confirmation dialog
        echo "
        <script>
            var confirmDelete = confirm('Are you sure you want to delete this asset?');
            if (confirmDelete) {
                window.location.href = 'deleteasset.php?id=$id&confirm=yes';
            } else {
                window.location.href = '../assets.php';
            }
        </script>
        ";
    } elseif ($_GET['confirm'] === 'yes') {
        // Delete the asset using prepared statement
        $sql = "DELETE FROM asset_table WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            logError("Asset deleted successfully (ID: $id)");
            echo "<script>alert('Record deleted successfully'); window.location.href='../assets.php';</script>";
            exit();
        } else {
            throw new PDOException("Failed to delete asset");
        }
    } else {
        header("Location: ../assets.php");
        exit();
    }
} catch (Exception $e) {
    logError("Error in deleteasset.php: " . $e->getMessage());
    echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.location.href='../assets.php';</script>";
    exit();
}
?>

