<?php
require "include/config.php";

$query = $_GET['q'];
$sql = "SELECT * FROM asset_table WHERE asset_name LIKE '%$query%'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<option value='" . $row['asset_name'] . "'>" . $row['asset_name'] . "</option>";
    }
} else {
    echo "<option disabled>No results found</option>";
}
?>
