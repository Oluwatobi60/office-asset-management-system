<?php
require_once dirname(__FILE__) . "/../include/config.php";
require_once dirname(__FILE__) . "/../../include/utils.php";

try {
    // Validate and sanitize input
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception("Invalid category ID");
    }
    
    $id = (int)$_GET['id'];
    
    // If not yet confirmed, show confirmation dialog
    if (!isset($_GET['confirm'])) {
        echo "
        <script>
            var confirmDelete = confirm('Are you sure you want to delete this category?');
            if (confirmDelete) {
                window.location.href = 'deletecategory.php?id=$id&confirm=yes';
            } else {
                window.location.href = '../categories.php';
            }
        </script>
        ";
    } 
    // If confirmed, proceed with deletion
    elseif ($_GET['confirm'] == 'yes') {
        // First check if category exists and is not in use
        $check_sql = "SELECT COUNT(*) FROM asset_table WHERE category = (SELECT category FROM category WHERE id = :id)";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $check_stmt->execute();
        
        if ($check_stmt->fetchColumn() > 0) {
            throw new Exception("Cannot delete category as it is being used by existing assets");
        }

        // Proceed with deletion if category is not in use
        $sql = "DELETE FROM category WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            logError("Category deleted successfully (ID: $id)");
            echo "<script>alert('Category deleted successfully');</script>";
            header("Location: ../categories.php");
            exit();
        } else {
            throw new PDOException("Failed to delete category");
        }
    }
} catch (Exception $e) {
    logError("Error in deletecategory.php: " . $e->getMessage());
    echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.location.href = '../categories.php';</script>";
    exit();
}
?>

