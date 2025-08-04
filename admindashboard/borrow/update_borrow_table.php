<?php
require "../include/config.php";

// Check if returned column exists
$checkColumn = "SHOW COLUMNS FROM borrow_table LIKE 'returned'";
$result = $conn->query($checkColumn);

if ($result->num_rows == 0) {
    // Add returned column
    $alterSql = "ALTER TABLE borrow_table 
                 ADD COLUMN returned TINYINT(1) DEFAULT 0,
                 ADD COLUMN returned_date DATE NULL";
    
    if ($conn->query($alterSql) === TRUE) {
        echo "Table updated successfully";
    } else {
        echo "Error updating table: " . $conn->error;
    }
} else {
    echo "Columns already exist";
}
?>