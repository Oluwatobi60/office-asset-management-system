<?php
require_once dirname(__FILE__) . "/../../include/utils.php";
require_once dirname(__FILE__) . "/../include/config.php";

// ===== Pagination Configuration =====
// Set the number of items to display per page
$items_per_page = 7; // Minimum items per page

// Get the current page number from URL, default to page 1 if not set
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Calculate the offset for SQL LIMIT clause
$offset = ($page - 1) * $items_per_page;

// ===== Filter Configuration =====
// Initialize base WHERE clause for SQL query
$where_clause = "WHERE 1=1";

// Add date range filters if provided in URL parameters
try {
    if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
        $start_date = $_GET['start_date'];
        if (!strtotime($start_date)) {
            throw new Exception("Invalid start date format");
        }
        $where_clause .= " AND DATE(dateofpurchase) >= :start_date";
    }

    if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
        $end_date = $_GET['end_date'];
        if (!strtotime($end_date)) {
            throw new Exception("Invalid end date format");
        }
        $where_clause .= " AND DATE(dateofpurchase) <= :end_date";
    }
} catch (Exception $e) {
    logError("Date filter error in assetlisttable.php: " . $e->getMessage() . 
            "\nStart Date: " . (isset($_GET['start_date']) ? $_GET['start_date'] : 'not set') . 
            "\nEnd Date: " . (isset($_GET['end_date']) ? $_GET['end_date'] : 'not set'));
    // Reset where clause if date validation fails
    $where_clause = "WHERE 1=1";
}

// Initialize params array for binding
$params = [];
if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $params[':start_date'] = $_GET['start_date'];
}
if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $params[':end_date'] = $_GET['end_date'];
}

// ===== Calculate Total Pages =====
try {
    // Get total number of assets matching the filter criteria
    $total_sql = "SELECT COUNT(*) AS total FROM asset_table $where_clause";
    $total_stmt = $conn->prepare($total_sql);
    if (!$total_stmt) {
        throw new PDOException("Failed to prepare count query");
    }
    
    // Bind date parameters if they exist
    foreach ($params as $key => $value) {
        $total_stmt->bindValue($key, $value);
    }
    
    $total_stmt->execute();
    $total_row = $total_stmt->fetch(PDO::FETCH_ASSOC);
    $total_items = $total_row['total'];
    $total_pages = ceil($total_items / $items_per_page); // Round up to get total pages needed    // ===== Fetch Asset Data =====
    $sql = "SELECT *, DATE_FORMAT(dateofpurchase, '%Y-%m-%d') as dateofpurchase 
            FROM asset_table $where_clause 
            ORDER BY dateofpurchase DESC 
            LIMIT :offset, :limit";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new PDOException("Failed to prepare asset query");
    }

    // Bind the date parameters first
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    // Then bind the pagination parameters
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    
    $stmt->execute();
} catch (PDOException $e) {
    // Log error with details and set default values
    logError("Database error in assetlisttable.php: " . $e->getMessage() . 
            "\nSQL Query: " . (isset($sql) ? $sql : $total_sql) . 
            "\nParameters: offset=" . $offset . ", limit=" . $items_per_page);
    $total_items = 0;
    $total_pages = 1;
}
?>

<!-- Main content container -->
<div class="row mt-5">
    <div class="col-md-12 col-lg-12 col-xlg-3">
        <!-- Responsive table wrapper -->
        <div class="table-responsive">
            <table class="table shadow table-striped table-bordered table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th scope="col">#</th> <!-- Serial number column -->
                        <th scope="col">Reg No.</th> <!-- Asset registration number column -->
                        <th scope="col">Asset Name</th> <!-- Asset name column -->
                        <th scope="col">Description</th> <!-- Asset description column -->
                        <th scope="col">Category</th> <!-- Asset category column -->
                        <th scope="col">Purchase Date</th> <!-- Asset purchase date column -->
                        <th scope="col">Qty</th> <!-- Asset quantity column -->
                        <th scope="col">Action</th> <!-- Action column for edit/delete -->
                    </tr>
                </thead>                <tbody>
                    <?php
                    $s = $offset + 1; // Initialize serial number for the current page
                    // Fetch and display all results using PDO
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { // Loop through each row
                        // Sanitize data to prevent XSS attacks
                        $id = htmlspecialchars($row['id']);
                        $reg_no = htmlspecialchars($row['reg_no']);
                        $asset_name = htmlspecialchars($row['asset_name']);
                        $description = htmlspecialchars($row['description']);
                        $category = htmlspecialchars($row['category']);
                        $dateofpurchase = htmlspecialchars($row['dateofpurchase']);
                        $quantity = (int)$row['quantity'];
                        ?>
                        <tr>
                            <th scope="row"><?php echo $s++; ?></th>
                            <td><?php echo $reg_no; ?></td>
                            <td><?php echo $asset_name; ?></td>
                            <td><?php echo $description; ?></td>
                            <td><?php echo $category; ?></td>
                            <td><?php echo $dateofpurchase; ?></td>
                            <td>
                                <?php if ($quantity == 0): ?>
                                    <span class="badge badge-danger">Not Available</span>
                                <?php else: ?>
                                    <span class="badge badge-success"><?php echo $quantity; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="assetfolder/editasset.php?id=<?php echo $id; ?>"><i class="fa fa-edit"></i></a>
                                <a href="assetfolder/deleteasset.php?id=<?php echo $id; ?>"><i class="fa fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div><!-- End of table-responsive -->

        <!-- Pagination controls -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">                <?php
                // Preserve date filter parameters in pagination URLs
                $date_params = '';
                if (!empty($_GET['start_date'])) {
                    $date_params .= '&start_date=' . htmlspecialchars($_GET['start_date']);
                }
                if (!empty($_GET['end_date'])) {
                    $date_params .= '&end_date=' . htmlspecialchars($_GET['end_date']);
                }
                ?>
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo ($page - 1) . $date_params; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i . $date_params; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo ($page + 1) . $date_params; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div><!-- End of row for asset list -->