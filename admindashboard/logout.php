<?php
session_start(); // Start the session
session_unset(); // Unset all session variables
session_destroy(); // Destroy the session
header("Location: ../index.php"); // Redirect to index.php in userfolder
exit(); // Ensure no further code is executed
?>
