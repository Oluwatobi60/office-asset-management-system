<?php
require "include/config.php"; // Include the database configuration file

try {
    // Pagination logic
    $items_per_page = 7; // Number of items to display per page
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $items_per_page;

    // Initial WHERE clause
    $where_clause = "WHERE (hod_approved = 1 OR pro_approved = 1)";
    $params = array();

    // Add date filters if provided
    if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
        $where_clause .= " AND DATE(r.request_date) >= :start_date";
        $params[':start_date'] = $_GET['start_date'];
    }

    if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
        $where_clause .= " AND DATE(r.request_date) <= :end_date";
        $params[':end_date'] = $_GET['end_date'];
    }

    // Count total records
    $count_sql = "SELECT COUNT(*) as total 
                  FROM request_table r 
                  LEFT JOIN user_table u ON r.requested_by = u.username " . $where_clause;
    
    $count_stmt = $conn->prepare($count_sql);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $items_per_page);

    // Main query with proper joins and pagination
    $sql = "SELECT r.*, u.firstname, u.lastname,
            CONCAT(COALESCE(u.firstname, ''), ' ', COALESCE(u.lastname, '')) as full_name  
            FROM request_table r 
            LEFT JOIN user_table u ON r.requested_by = u.username 
            {$where_clause}
            ORDER BY r.id DESC 
            LIMIT :limit OFFSET :offset";

    $stmt = $conn->prepare($sql);
    
    // Bind all parameters including pagination
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Start table output
?>

<!-- Table structure -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead class="thead-light">
                            <tr>
                                <th>ID</th>
                                <th>Asset Name</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Department</th>
                                <th>Request By</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Loop through the results using PDO fetch
                            if ($result) {
                                foreach ($result as $row) {
                                    $status = '';
                                    if ($row['hod_approved'] == 1 && $row['pro_approved'] == 1) {
                                        $status = '<span class="badge badge-success">Approved</span>';
                                    } elseif ($row['hod_approved'] == 2 || $row['pro_approved'] == 2) {
                                        $status = '<span class="badge badge-danger">Rejected</span>';
                                    } else {
                                        $status = '<span class="badge badge-warning">Pending</span>';
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['asset_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                                        <td><?php echo htmlspecialchars($row['category']); ?></td>                                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                                        <td><?php echo htmlspecialchars(trim($row['full_name']) ?: 'Unknown User'); ?></td>
                                        <td><?php echo htmlspecialchars($row['request_date']); ?></td>
                                        <td><?php echo $status; ?></td>
                                        <td>
                                            <a href="viewhistory.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php }
                            } ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-center mt-4">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page-1); ?>&start_date=<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>&end_date=<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&start_date=<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>&end_date=<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page+1); ?>&start_date=<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>&end_date=<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
} catch (PDOException $e) {
    // Log the error and display a user-friendly message
    error_log("Database error: " . $e->getMessage());
    echo '<div class="alert alert-danger">An error occurred while fetching the data. Please try again later.</div>';
}
?>