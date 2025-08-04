<?php
// Include database configuration file
require "../include/config.php";

// Set response header to JSON
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => ''
];

try {
    // Start transaction
    $conn->beginTransaction();

    // HOD approval handling
    if (isset($_POST['hod_approve_id']) && isset($_POST['hod_status'])) {
        $id = $_POST['hod_approve_id'];
        $status = $_POST['hod_status'];
        
        // Validate input
        if (!in_array($status, [0, 1, 2])) {
            throw new Exception("Invalid HOD status value");
        }
        
        // Update HOD status
        $sql = "UPDATE borrow_table SET hod_status = :status WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':status', $status, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update HOD status");
        }

        // If HOD rejects, automatically set procurement status to N/A (represented by -1)
        if ($status == 2) {
            $updatePro = "UPDATE borrow_table SET pro_status = :pro_status WHERE id = :id";
            $stmtPro = $conn->prepare($updatePro);
            $stmtPro->bindValue(':pro_status', -1, PDO::PARAM_INT);
            $stmtPro->bindParam(':id', $id, PDO::PARAM_INT);
            
            if (!$stmtPro->execute()) {
                throw new Exception("Failed to update Procurement status after HOD rejection");
            }
        }
        
        $response['message'] = "HOD status updated successfully";
    }

    // Procurement approval handling
    if (isset($_POST['pro_approve_id']) && isset($_POST['pro_status'])) {
        $id = $_POST['pro_approve_id'];
        $status = $_POST['pro_status'];
        
        // Validate input
        if (!in_array($status, [0, 1, 2])) {
            throw new Exception("Invalid Procurement status value");
        }
        
        // Check if HOD has approved
        $checkHod = "SELECT hod_status FROM borrow_table WHERE id = :id";
        $stmtHod = $conn->prepare($checkHod);
        $stmtHod->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtHod->execute();
        $hodStatus = $stmtHod->fetchColumn();
        
        if ($hodStatus != 1) {
            throw new Exception("HOD approval is required before Procurement can take action");
        }
        
        // Update Procurement status
        $sql = "UPDATE borrow_table SET pro_status = :status WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':status', $status, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update Procurement status");
        }
        
        $response['message'] = "Procurement status updated successfully";
    }

    // If we got here, everything worked
    $conn->commit();
    $response['success'] = true;

} catch (Exception $e) {
    // Rollback the transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Log the error
    error_log("Error in update_status.php: " . $e->getMessage());
    
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

// Send the response
echo json_encode($response);
?>