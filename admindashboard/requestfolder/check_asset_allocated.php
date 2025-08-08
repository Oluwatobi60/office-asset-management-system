<?php
require_once dirname(__DIR__, 2) . "/admindashboard/include/config.php";
header('Content-Type: application/json');

$reg_no = isset($_GET['reg_no']) ? $_GET['reg_no'] : '';
$username = isset($_GET['username']) ? $_GET['username'] : '';

if ($reg_no && $username) {
    // Check if the user has already been allocated this asset in staff_table
    $sql = "SELECT COUNT(*) FROM staff_table WHERE reg_no = ? AND username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$reg_no, $username]);
    $allocated = $stmt->fetchColumn() > 0;
    echo json_encode(['allocated' => $allocated]);
} else {
    echo json_encode(['allocated' => false]);
}
?>
