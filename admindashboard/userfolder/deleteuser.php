<?php
require "../include/config.php";

// Assuming $conn is a PDO instance from config.php
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    if (!isset($_GET['confirm'])) {
        // Show confirmation dialog only if 'confirm' is not set
        echo "
        <script>
            var confirmDelete = confirm('Are you sure you want to delete this asset?');
            if (confirmDelete) {
                window.location.href = 'deleteuser.php?id=$id&confirm=yes';
            } else {
                window.location.href = '../newuser.php';
            }
        </script>
        ";
        exit;
    } elseif ($_GET['confirm'] === 'yes') {
        // Use PDO prepared statement for security
        $stmt = $conn->prepare("DELETE FROM user_table WHERE id = :id");
        if ($stmt->execute([':id' => $id])) {
            echo "<script>alert('Record deleted successfully');window.location.href='../newuser.php';</script>";
        } else {
            echo "<script>alert('Error deleting record');window.location.href='../newuser.php';</script>";
        }
        exit;
    }
} else {
    // Invalid or missing id, redirect
    header("Location: ../newuser.php");
    exit;
}
?>

