<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require "../include/config.php";

header('Content-Type: application/json');

try {
    $query = isset($_GET['q']) ? $_GET['q'] : '';
    
    if (empty($query)) {
        echo json_encode([]);
        exit;
    }

    // Normalize the query for consistent matching
    $normalizedQuery = preg_replace('/\s+/', ' ', trim($query));
    
    // First try exact matches
    $sql = "SELECT asset_name, reg_no, category, description, CAST(quantity AS SIGNED) as quantity 
            FROM asset_table 
            WHERE (LOWER(asset_name) = LOWER(:exact)
                   OR LOWER(reg_no) = LOWER(:exact))
                  AND asset_name IS NOT NULL
                  AND reg_no IS NOT NULL
            ORDER BY 
                CASE WHEN LOWER(asset_name) = LOWER(:exact) THEN 1
                     WHEN LOWER(reg_no) = LOWER(:exact) THEN 2
                     ELSE 3 
                END,
                asset_name 
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':exact', $normalizedQuery, PDO::PARAM_STR);
    $stmt->execute();
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no exact matches, try partial matches with improved relevance
    if (empty($assets)) {
        $sql = "SELECT asset_name, reg_no, category, description, CAST(quantity AS SIGNED) as quantity 
                FROM asset_table 
                WHERE (LOWER(asset_name) LIKE LOWER(:search)
                       OR LOWER(reg_no) LIKE LOWER(:search)
                       OR LOWER(category) LIKE LOWER(:search))
                  AND asset_name IS NOT NULL
                  AND reg_no IS NOT NULL
                ORDER BY 
                    CASE WHEN LOWER(asset_name) LIKE LOWER(:exact) THEN 1
                         WHEN LOWER(reg_no) LIKE LOWER(:exact) THEN 2
                         ELSE 3 
                    END,
                    asset_name 
                LIMIT 10";
        
        $stmt = $conn->prepare($sql);
        $searchPattern = "%" . $normalizedQuery . "%";
        $stmt->bindParam(':search', $searchPattern, PDO::PARAM_STR);
        $stmt->bindParam(':exact', $normalizedQuery, PDO::PARAM_STR);
        $stmt->execute();
        $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // If still no assets found, broaden the search criteria
    if (empty($assets)) {
        $sql = "SELECT asset_name, reg_no, category, description, CAST(quantity AS SIGNED) as quantity 
                FROM asset_table 
                WHERE (LOWER(asset_name) LIKE LOWER(:broadSearch)
                       OR LOWER(reg_no) LIKE LOWER(:broadSearch)
                       OR LOWER(category) LIKE LOWER(:broadSearch)
                       OR LOWER(description) LIKE LOWER(:broadSearch))
                  AND asset_name IS NOT NULL
                  AND reg_no IS NOT NULL
                ORDER BY 
                    CASE WHEN LOWER(asset_name) LIKE LOWER(:exact) THEN 1
                         WHEN LOWER(reg_no) LIKE LOWER(:exact) THEN 2
                         ELSE 3 
                    END,
                    asset_name 
                LIMIT 10";
        
        $stmt = $conn->prepare($sql);
        $broadSearchPattern = "%" . $normalizedQuery . "%";
        $stmt->bindParam(':broadSearch', $broadSearchPattern, PDO::PARAM_STR);
        $stmt->bindParam(':exact', $normalizedQuery, PDO::PARAM_STR);
        $stmt->execute();
        $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Ensure quantity is treated as a number
    foreach ($assets as &$row) {
        $row['quantity'] = intval($row['quantity']);
    }
    
    error_log("Search query '$query' returned: " . json_encode($assets)); // Debug log
    echo json_encode($assets);

} catch (PDOException $e) {
    error_log("Database error in search_asset.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
