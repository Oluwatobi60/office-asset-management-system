<?php
require "../include/config.php"; // Include the database configuration file

if (isset($_GET['asset_name'])) {
    $assetName = $_GET['asset_name'];

    // Query to fetch the category based on the asset name
    $sql = "SELECT category FROM asset_table WHERE asset_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $assetName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(['category' => $row['category']]);
    } else {
        echo json_encode(['category' => null]); // Return null if no category is found
    }
} else {
    echo json_encode(['error' => 'Asset name not provided']);
}
?>
