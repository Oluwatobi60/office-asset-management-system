<?php
require_once "../include/config.php";

header('Content-Type: application/json');

if (!isset($_GET['department-select'])) {
    echo json_encode(['error' => 'Department not specified']);
    exit;
}

try {
    $department = $_GET['department-select'];
      // Log the incoming request
    error_log("Fetching employees for department: " . $department);

    // Prepare SQL to get employee details
    $sql = "SELECT firstname, lastname, department FROM user_table WHERE department = :department";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':department', $department, PDO::PARAM_STR);
    $stmt->execute();

    $employees = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $employees[] = [
            'name' => $row['firstname'] . ' ' . $row['lastname'],
            'department' => $row['department']
        ];
    }
    
    // Log the results
    error_log("Found " . count($employees) . " employees for department: " . $department);
    
    echo json_encode($employees);
} catch (PDOException $e) {
    error_log("Error in get_employees.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch employees: ' . $e->getMessage()]);
}
?>
