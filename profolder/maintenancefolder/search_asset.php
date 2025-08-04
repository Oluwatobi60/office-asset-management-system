<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../../error_log.txt');

error_log("Search asset script starting...");

require_once "../../include/config.php";

// Set headers for JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Validate search query
    if (!isset($_GET['q'])) {
        throw new Exception('Search query parameter is required');
    }

    $query = trim($_GET['q']);
    
    if (empty($query)) {
        echo json_encode([]);
        exit;
    }
    
    // SQL query to search assets with priority ordering
    $sql = "SELECT 
            asset_name,
            reg_no,
            category,
            description
        FROM asset_table
        WHERE (
            LOWER(asset_name) LIKE LOWER(:search)
            OR LOWER(reg_no) LIKE LOWER(:search)
            OR LOWER(category) LIKE LOWER(:search)
        )
        AND asset_name IS NOT NULL
        ORDER BY 
            CASE 
                WHEN LOWER(asset_name) = LOWER(:exact) THEN 1
                WHEN LOWER(asset_name) LIKE LOWER(:startsWith) THEN 2
                ELSE 3 
            END,
            asset_name 
        LIMIT 10";

    error_log("Preparing SQL query: " . $sql);
    $stmt = $conn->prepare($sql);
    
    $searchPattern = "%{$query}%";
    $startsWithPattern = "{$query}%";
    
    $stmt->bindParam(':search', $searchPattern, PDO::PARAM_STR);
    $stmt->bindParam(':exact', $query, PDO::PARAM_STR);
    $stmt->bindParam(':startsWith', $startsWithPattern, PDO::PARAM_STR);
    
    error_log("Executing query with search term: " . $query);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Found " . count($results) . " results");
    
    error_log("Search results for query '{$query}': " . json_encode($results));
    echo json_encode($results);

} catch (PDOException $e) {
    error_log("Database error in search_asset.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    error_log("SQL State: " . $e->getCode());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error in search_asset.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(400);
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>
