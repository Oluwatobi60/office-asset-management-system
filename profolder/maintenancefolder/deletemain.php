<?php
require "../../admindashboard/include/config.php";

// Start or resume session to store messages
session_start();

try {
    // Validate and sanitize the ID parameter
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id) {
        throw new Exception('Invalid ID provided');
    }

    if (!isset($_GET['confirm'])) {
        // First visit - show confirmation dialog
        echo "
        <script>
            var confirmDelete = confirm('Are you sure you want to delete this maintenance record?');
            if (confirmDelete) {
                window.location.href = 'deletemain.php?id=" . $id . "&confirm=yes';
            } else {
                window.location.href = '../maintenance.php';
            }
        </script>
        ";
        exit();
    }

    if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
        // User confirmed - proceed with deletion
        $sql = "DELETE FROM maintenance_table WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Maintenance record deleted successfully";
        } else {
            throw new Exception('Failed to delete the record');
        }
        
        // Redirect back with success message
        header("Location: ../maintenance.php");
        exit();
    }

} catch (PDOException $e) {
    // Log database errors
    error_log("Database Error in deletemain.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Database error occurred while deleting the record";
    header("Location: ../maintenance.php");
    exit();
} catch (Exception $e) {
    // Log other errors
    error_log("Error in deletemain.php: " . $e->getMessage());
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: ../maintenance.php");
    exit();
}
?>

