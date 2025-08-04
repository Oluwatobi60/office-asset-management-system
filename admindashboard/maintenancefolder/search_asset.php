<?php
require_once "../include/config.php";

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Validate the search parameter
    if (!isset($_GET['q'])) {
        throw new Exception('Search term is required');
    }

    $searchTerm = trim($_GET['q']);
    if (empty($searchTerm)) {
        throw new Exception('Search term cannot be empty');
    }    // Prepare and execute the query
    $sql = "SELECT asset_name, reg_no, category, description 
            FROM asset_table 
            WHERE LOWER(asset_name) LIKE LOWER(:search) 
               OR LOWER(reg_no) LIKE LOWER(:search)
            ORDER BY asset_name 
            LIMIT 10";
    
    error_log("Search query executed with term: " . $searchTerm); // Debug log
            
    $stmt = $conn->prepare($sql);
    $searchPattern = "%" . $searchTerm . "%";
    $stmt->bindParam(':search', $searchPattern, PDO::PARAM_STR);
    $stmt->execute();
    
    // Fetch all matches
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($assets);

} catch (Exception $e) {
    logError("Asset search error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
