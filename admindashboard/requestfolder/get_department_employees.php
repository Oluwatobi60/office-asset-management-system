<?php
require_once dirname(__FILE__) . "/../include/config.php";
require_once dirname(__FILE__) . "/../../include/utils.php";

header('Content-Type: application/json');

try {
    if (!isset($_GET['department'])) {
        throw new Exception('Department parameter is required');
    }

    $department = $_GET['department'];
    
    // Query to get employees from the selected department
    $sql = "SELECT CONCAT(firstname, ' ', lastname) as fullname 
            FROM user_table 
            WHERE department = :department 
            ORDER BY firstname, lastname";
            
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':department', $department, PDO::PARAM_STR);
    $stmt->execute();
    
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($employees);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    logError("Error in get_department_employees.php: " . $e->getMessage());
}
?>
