<?php
require "../admindashboard/include/config.php";

// Handle Needs Approval button (change hod_status from 0 to 1)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_id'])) {
    $approve_id = $_POST['approve_id'];
    $update_sql = "UPDATE department_borrow_table SET hod_status = 1 WHERE id = :id";
    $stmt = $conn->prepare($update_sql);
    $stmt->bindParam(':id', $approve_id, PDO::PARAM_INT);
    $stmt->execute();
}

// Handle Return button (owner side)
// Handle Return button (owner or borrower side)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_id'])) {
    $return_id = $_POST['return_id'];
    $update_sql = "UPDATE department_borrow_table SET status = 'returned', returned = 1, return_date = NOW() WHERE id = :id";
    $stmt = $conn->prepare($update_sql);
    $stmt->bindParam(':id', $return_id, PDO::PARAM_INT);
    $stmt->execute();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrower_return_id'])) {
    $borrower_return_id = $_POST['borrower_return_id'];
    // Get asset name and quantity from department_borrow_table
    $get_sql = "SELECT asset_name, quantity FROM department_borrow_table WHERE id = :id";
    $get_stmt = $conn->prepare($get_sql);
    $get_stmt->bindParam(':id', $borrower_return_id, PDO::PARAM_INT);
    $get_stmt->execute();
    $asset = $get_stmt->fetch(PDO::FETCH_ASSOC);
    if ($asset) {
        // Update asset quantity in request_table
        $update_asset_sql = "UPDATE request_table SET quantity = quantity + :qty WHERE asset_name = :asset_name";
        $update_asset_stmt = $conn->prepare($update_asset_sql);
        $update_asset_stmt->bindParam(':qty', $asset['quantity'], PDO::PARAM_INT);
        $update_asset_stmt->bindParam(':asset_name', $asset['asset_name'], PDO::PARAM_STR);
        $update_asset_stmt->execute();
    }
    // Update returned status in department_borrow_table
    $update_sql = "UPDATE department_borrow_table SET returned = 1, return_date = NOW() WHERE id = :id";
    $stmt = $conn->prepare($update_sql);
    $stmt->bindParam(':id', $borrower_return_id, PDO::PARAM_INT);
    $stmt->execute();
}

$items_per_page = 7;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Count total records
$count_sql = "SELECT COUNT(*) AS total FROM department_borrow_table";
$stmt = $conn->prepare($count_sql);
$stmt->execute();
$total_items = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_items / $items_per_page);

?>
<div class="row mt-5">
    <div class="col-md-12">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>ID</th>
                        <th>Reg No.</th>
                        <th>Asset Name</th>
                        <th>Borrowing Dept</th>
                        <th>From Dept</th>
                        <th>Borrow Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                 $s = $offset + 1; // Initialize serial number
                $userDept = isset($_SESSION['department']) ? $_SESSION['department'] : '';
                // Show requests where the logged-in department is either the owner or the borrower
                $query = "SELECT * FROM department_borrow_table WHERE department = :department OR borrow_by_dept = :department ORDER BY borrow_date DESC LIMIT :offset, :limit";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':department', $userDept, PDO::PARAM_STR);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr>";
                        echo "<td>".$s++."</td>";
                        echo "<td>" . htmlspecialchars($row['reg_no']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['asset_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['borrow_by_dept']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['department']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['borrow_date']) . "</td>";
                        echo "<td>";
                        // Status logic for both owner and borrower sides
                        // Owner side
                        if ($row['department'] === $userDept) {
                            if ($row['hod_status'] == 0) {
                                echo "<form method='POST' style='display:inline;'>
                                        <input type='hidden' name='approve_id' value='" . $row['id'] . "'>
                                        <button type='submit' class='btn btn-warning btn-sm'>Needs approval</button>
                                      </form>";
                            } elseif ($row['hod_status'] == 1) {
                                echo "<span class='badge badge-success'>Approved</span>";
                                if ($row['status'] === 'approved') {
                                    echo "<form method='POST' style='display:inline;'>
                                            <input type='hidden' name='return_id' value='" . $row['id'] . "'>
                                            <button type='submit' class='btn btn-warning btn-sm'>Mark Returned</button>
                                          </form>";
                                }
                            }
                            if ($row['returned'] == 0) {
                                echo "<span class='badge badge-info'>Not yet returned</span>";
                            }
                        }
                        // Borrower side
                        if ($row['borrow_by_dept'] === $userDept) {
                            if ($row['hod_status'] == 0) {
                                echo "<span class='badge badge-secondary'>Pending</span>";
                            } elseif ($row['hod_status'] == 1) {
                                echo "<span class='badge badge-success'>Approved</span>";
                                // If not yet returned, show clickable button
                                if ($row['returned'] == 0) {
                                    echo "<form method='POST' style='display:inline;'>
                                            <input type='hidden' name='borrower_return_id' value='" . $row['id'] . "'>
                                            <button type='submit' class='btn btn-info btn-sm'>Not yet returned</button>
                                          </form>";
                                }
                            }
                        }
                        echo "</td>";


                          echo "<td>";
                        // Only show delete button if logged-in department is the borrower and status is pending
                        if ($row['borrow_by_dept'] === $userDept && $row['hod_status'] == 0) {
                            echo "<a href='deptborrow/deletedptborrow.php?id=" . $row['id'] . "'><i class='fa fa-trash' style='color:red'></i></a> ";
                        }
                        // Always show view button
                        echo "<a href='deptborrow/viewborrowdept.php?id=" . $row['id'] . "'><i class='fa fa-eye'></i></a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='9' class='text-center'>No asset requests found.</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
        <!-- Returned Table -->
        <div class="mt-4">
            <h5>Returned Assets</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>ID</th>
                            <th>Reg No.</th>
                            <th>Asset Name</th>
                            <th>Borrowing Dept</th>
                            <th>From Dept</th>
                            <th>Borrow Date</th>
                            <th>Return Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                     $s = $offset + 1; // Initialize serial number
                    $returned_query = "SELECT * FROM department_borrow_table WHERE (department = :department OR borrow_by_dept = :department) AND returned = 1 ORDER BY return_date DESC";
                    $returned_stmt = $conn->prepare($returned_query);
                    $returned_stmt->bindParam(':department', $userDept, PDO::PARAM_STR);
                    $returned_stmt->execute();
                    if ($returned_stmt->rowCount() > 0) {
                        while ($row = $returned_stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr>";
                            echo "<td>".$s++."</td>";
                            echo "<td>" . htmlspecialchars($row['reg_no']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['asset_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['borrow_by_dept']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['department']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['borrow_date']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['return_date']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' class='text-center'>No returned assets found.</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a></li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>

