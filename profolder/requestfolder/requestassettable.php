<?php
require "../admindashboard/include/config.php"; // Include the database configuration file

// Handle approval button click
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_id'])) {
    try {
        $approve_id = (int)$_POST['approve_id'];
        $update_sql = "UPDATE request_table SET pro_approved = 1 WHERE id = :id";
        $stmt = $conn->prepare($update_sql);
        $stmt->bindParam(':id', $approve_id, PDO::PARAM_INT);
        $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error updating approval status: " . $e->getMessage());
    }
}

// Pagination logic
$items_per_page = 7; // Minimum of 7 items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Get current page or default to 1
$offset = ($page - 1) * $items_per_page; // Calculate offset for SQL query

try {
    // Get total number of rows
    $total_sql = "SELECT COUNT(*) AS total FROM request_table";
    $stmt = $conn->query($total_sql);
    $total_rows = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_rows / $items_per_page); // Calculate total pages

    // Fetch paginated results
    $sql = "SELECT * FROM request_table 
            ORDER BY 
                CASE 
                    WHEN request_date = CURDATE() THEN 0 
                    ELSE 1 
                END, 
                request_date ASC, 
                pro_approved DESC, 
                id ASC 
            LIMIT :offset, :limit";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
?>
<div class="row mt-5"><!-- Begin of row for asset list -->
    <div class="col-md-12 col-lg-12 col-xlg-3">
        <table class="table shadow-lg table-striped table-bordered table-hover">
            <thead class="thead-dark">
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Reg No.</th>
                    <th scope="col">Asset Name</th>
                    <th scope="col">Request By</th>
                    <th scope="col">Request Date</th>
                    <th scope="col">Procurement Status</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
                try {
                    $s = $offset + 1; // Initialize serial number based on offset
                    $hasRows = false;
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $hasRows = true;
                        echo "<tr>";
                        echo "<th scope='row'>" . htmlspecialchars($s++) . "</th>";
                        echo "<td>" . htmlspecialchars($row['reg_no']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['asset_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['assigned_employee']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['request_date']) . "</td>";
                        echo "<td>";
                        // Only allow procurement approval if HOD has approved
                        if ($row['pro_approved'] == 0) {
                            if ($row['hod_approved'] == 1) {
                                echo "<form method='POST' style='display:inline;'>";
                                echo "<input type='hidden' name='approve_id' value='" . htmlspecialchars($row['id']) . "'>";
                                echo "<button type='submit' class='btn btn-warning'>Pending...</button>";
                                echo "</form>";
                            } else {
                                echo "<button class='btn btn-secondary' disabled>Waiting for HOD Approval</button>";
                            }
                        } else {
                            echo "<button class='btn btn-success' disabled>Approved</button>";
                        }
                        echo "</td>";
                        echo "<td>";
                        echo "<a href='requestfolder/viewrequest.php?id=" . htmlspecialchars($row['id']) . "'><i class='fa fa-eye'></i></a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                    if (!$hasRows) {
                        echo "<tr><td colspan='7' class='text-center'>No request record</td></tr>";
                    }
                } catch (PDOException $e) {
                    error_log("Error fetching request details: " . $e->getMessage());
                    echo "<tr><td colspan='7' class='text-center'>Error loading requests</td></tr>";
                }
            ?>
            </tbody>
        </table>
        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a></li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div><!-- End of row for asset list -->
<?php
} catch (PDOException $e) {
    error_log("Error in pagination: " . $e->getMessage());
    echo "<div class='alert alert-danger'>Error loading table. Please try again later.</div>";
}
?>