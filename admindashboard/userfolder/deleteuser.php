<?php
 require "../include/config.php";
 
$id = $_GET['id']; // Retrieve the id from the URL parameters

if (isset($id)) {
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
    
    if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
        // SQL query to delete the asset record with the given id
        $sql = "DELETE FROM user_table WHERE id = $id";
        if ($conn->query($sql) === TRUE) {
            echo "<script>alert('Record deleted successfully');</script>";
            header("Location: ../newuser.php"); // Redirect back to the asset list page
        } else {
            echo "<script>alert('Error deleting record');</script>";
        }
    }
}
?>

