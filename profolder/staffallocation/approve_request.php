<?php
require_once dirname(__FILE__) . "/../../include/utils.php";
require_once dirname(__FILE__) . "/../include/config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $id = (int)$_POST['id'];
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Update request status
    $update_sql = "UPDATE staff_table SET status = 'Approved' WHERE id = :id";
    $stmt = $conn->prepare($update_sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update request status");
    }
    
    $conn->commit();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error in approve_request.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
