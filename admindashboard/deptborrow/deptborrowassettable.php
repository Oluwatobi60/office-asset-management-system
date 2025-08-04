<?php
require "../admindashboard/include/config.php";

$items_per_page = 7;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

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
                $query = "SELECT * FROM department_borrow_table ORDER BY borrow_date DESC LIMIT :offset, :limit";
                $stmt = $conn->prepare($query);
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
                        // Status logic
                        if ($row['hod_status'] == 0) {
                            echo "<span class='badge badge-secondary'>Pending</span>";
                        } elseif ($row['hod_status'] == 1) {
                            echo "<span class='badge badge-success'>Approved</span>";
                        }
                        if ($row['returned'] == 0) {
                            echo "<span class='badge badge-info'>Not yet returned</span>";
                        } else {
                            echo "<span class='badge badge-primary'>Returned</span>";
                        }
                        echo "</td>";

                        echo "<td>";
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
                    $returned_query = "SELECT * FROM department_borrow_table WHERE returned = 1 ORDER BY return_date DESC";
                    $returned_stmt = $conn->prepare($returned_query);
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

