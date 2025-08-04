<?php
require "../admindashboard/include/config.php"; // Include the database configuration file

// Handle approval button click
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_id'])) {
    $approve_id = $_POST['approve_id'];
    $update_sql = "UPDATE request_table SET hod_approved = 1 WHERE id = :id";
    $stmt = $conn->prepare($update_sql);
    $stmt->bindParam(':id', $approve_id, PDO::PARAM_INT);
    $stmt->execute();
}

// Pagination logic
$items_per_page = 7; // Minimum 7 items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Count total records
$count_sql = "SELECT COUNT(*) AS total FROM request_table r 
              INNER JOIN user_table u 
              ON r.department = u.department AND u.role = 'HOD' 
              WHERE u.username = :username";
$stmt = $conn->prepare($count_sql);
$stmt->bindParam(':username', $_SESSION['username'], PDO::PARAM_STR);
$stmt->execute();
$total_items = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_items / $items_per_page);
?>
<div class="row mt-5"><!-- Begin of row for asset list -->
    <div class="col-md-12 col-lg-12 col-xlg-3">
        <div class="table-responsive"><!-- Add table-responsive class -->
            <table class="table shadow-lg table-striped table-bordered table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th scope="col">ID</th> <!-- Column for serial number -->
                        <th scope="col">Reg No.</th> <!-- Column for registration number -->
                        <th scope="col">Asset Name</th> <!-- Column for asset name -->
                        <th scope="col">Request By</th> <!-- Column for the requester -->
                        <th scope="col">Request Date</th> <!-- Column for the request date -->
                        <th scope="col">HOD Status</th> <!-- Column for HOD approval status -->
                        <th scope="col">Status</th> <!-- Column for action buttons -->
                        <th scope="col">Action</th> <!-- Column for action buttons -->
                    </tr>
                </thead>
                <?php
                    $s = $offset + 1; // Initialize serial number
                    $username = $_SESSION['username']; // Retrieve the username from the session
                    // SQL query to fetch paginated requests related to the HOD's department
                    $sql = "SELECT r.* 
                            FROM request_table r 
                            INNER JOIN user_table u 
                            ON r.department = u.department AND u.role = 'HOD' 
                            WHERE u.username = :username
                            LIMIT :limit OFFSET :offset";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                    $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
                    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { // Loop through each result row
                        echo "<tr>";
                        echo "<th scope='row'>".$s++."</th>"; // Display serial number
                        echo "<td>".$row['reg_no']."</td>"; // Display registration number
                        echo "<td>".$row['asset_name']."</td>"; // Display asset name
                        echo "<td>".$row['assigned_employee']."</td>"; // Display the assigned employee
                        echo "<td>".$row['request_date']."</td>"; // Display the request date
                        // Display HOD status as a button
                        echo "<td>";
                        if ($row['hod_approved'] == 0) {
                            echo "<form method='POST' style='display:inline;'>
                                    <input type='hidden' name='approve_id' value='".$row['id']."'>
                                    <button type='submit' class='btn btn-warning'>Not Approve</button>
                                  </form>";
                        } else {
                            echo "<button class='btn btn-success' disabled>Approved</button>";
                        }                        echo "</td>";
                        // Display status based on quantity
                        echo "<td>";
                        if ($row['quantity'] == 0) {
                            echo "<span class='badge badge-danger'>Asset Borrowed Out</span>";
                        } else {
                            echo "<span class='badge badge-success'>Available</span>";
                        }
                        echo "</td>";
                        // Display action button to view request details
                        echo "<td>
                        <a href='requestfolder/viewrequest.php?id=".$row['id']."'><i class='fa fa-eye'></i></a>
                        </td>"; 
                        echo "</tr>";
                    }
                ?>
            </table>
        </div><!-- End of table-responsive -->
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
</div><!-- End of row for asset list -->