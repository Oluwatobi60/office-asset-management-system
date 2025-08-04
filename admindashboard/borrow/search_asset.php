<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../include/config.php";

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
            description, 
            CAST(quantity AS SIGNED) as quantity
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

    $stmt = $conn->prepare($sql);
    
    $searchPattern = "%{$query}%";
    $startsWithPattern = "{$query}%";
    
    $stmt->bindParam(':search', $searchPattern, PDO::PARAM_STR);
    $stmt->bindParam(':exact', $query, PDO::PARAM_STR);
    $stmt->bindParam(':startsWith', $startsWithPattern, PDO::PARAM_STR);
    
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ensure quantity is an integer
    foreach ($results as &$result) {
        $result['quantity'] = intval($result['quantity']);
    }
    
    error_log("Search results for query '{$query}': " . json_encode($results));
    echo json_encode($results);

} catch (PDOException $e) {
    error_log("Database error in search_asset.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred', 'details' => $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error in search_asset.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
