<?php
require "include/config.php";
require_once "../include/utils.php";

// Check if 'asset_name' is provided in the POST request
if (isset($_POST['asset_name'])) {
    try {
        // Prepare the query with a parameter placeholder and proper date formatting
        $sql = "SELECT reg_no, description, DATE_FORMAT(dateofpurchase, '%Y-%m-%d') as dateofpurchase 
                FROM asset_table 
                WHERE asset_name = :asset_name";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':asset_name', $_POST['asset_name']);
        $stmt->execute();
        
        if ($assetDetails = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Fetch maintenance details from the maintenance_table with proper date formatting
            $sqlMaintenance = "SELECT DATE_FORMAT(last_service, '%Y-%m-%d') as last_service, 
                                    DATE_FORMAT(next_service, '%Y-%m-%d') as next_service 
                             FROM maintenance_table 
                             WHERE asset_name = :asset_name";
            
            $stmtMaintenance = $conn->prepare($sqlMaintenance);
            $stmtMaintenance->bindParam(':asset_name', $_POST['asset_name']);
            $stmtMaintenance->execute();
            
            if ($maintenanceDetails = $stmtMaintenance->fetch(PDO::FETCH_ASSOC)) {
                // Add maintenance details to the asset details
                $assetDetails['last_service'] = $maintenanceDetails['last_service'];
                $assetDetails['next_service'] = $maintenanceDetails['next_service'];
            } else {
                // If no maintenance details are found, set default empty values
                $assetDetails['last_service'] = '';
                $assetDetails['next_service'] = '';
            }
            
            // Return the asset details as a JSON response
            echo json_encode($assetDetails);
        } else {
            // Return null if no asset details are found
            echo json_encode(null);
        }
    } catch (PDOException $e) {
        logError("Database error in fetch_asset_details.php: " . $e->getMessage());
        echo json_encode(['error' => 'Failed to fetch asset details']);
    }
} else {
    // Return an error if 'asset_name' is not provided
    echo json_encode(['error' => 'Asset name not provided']);
}
?>
