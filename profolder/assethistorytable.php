<?php
require "../admindashboard/include/config.php"; // Include the database configuration file

try {
    // Pagination logic
    $items_per_page = 7; // Minimum of 7 items per page
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Get current page or default to 1
    $offset = ($page - 1) * $items_per_page; // Calculate offset for SQL query

    // Get total number of rows using PDO
    $total_query = "SELECT COUNT(*) AS total FROM request_table WHERE hod_approved = 1 OR pro_approved = 1";
    $stmt = $conn->query($total_query);
    $total_rows = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_rows / $items_per_page); // Calculate total pages

    // Fetch paginated results using prepared statement
    $query = "SELECT * FROM request_table WHERE hod_approved = 1 OR pro_approved = 1 LIMIT :offset, :limit";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();

    $s = $offset + 1; // Initialize serial number based on offset
    ?>
    <div class="row mt-5"><!--Begin of row for asset list-->
        <div class="col-md-12 col-lg-12 col-xlg-3">
            <table class="table shadow table-striped table-bordered table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Reg No.</th>
                        <th scope="col">Req by</th>
                        <th scope="col">Department</th>
                        <th scope="col">Req Date</th>
                        <th scope="col">Procurement Status</th>
                        <th scope="col">HOD Status</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($stmt->rowCount() > 0) {
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr>";
                            echo "<th scope='row'>" . htmlspecialchars($s++) . "</th>";
                            echo "<td>" . htmlspecialchars($row['reg_no']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['assigned_employee']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['department']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['request_date']) . "</td>";
                            echo "<td>";
                            echo "<span class='" . ($row['pro_approved'] == 1 ? 'text-success' : 'text-danger') . "'>";
                            echo $row['pro_approved'] == 1 ? 'Approved' : 'Not Approved';
                            echo "</span>";
                            echo "</td>";
                            echo "<td>";
                            echo "<span class='" . ($row['hod_approved'] == 1 ? 'text-success' : 'text-danger') . "'>";
                            echo $row['hod_approved'] == 1 ? 'Approved' : 'Not Approved';
                            echo "</span>";
                            echo "</td>";
                            echo "<td>";
                            echo "<a href='viewhistory.php?id=" . htmlspecialchars($row['id']) . "'>";
                            echo "<i class='fas fa-eye'></i>";
                            echo "</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' class='text-center'>No records found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            <!-- Pagination -->
            <nav>
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo htmlspecialchars($i); ?>"><?php echo htmlspecialchars($i); ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div><!-- End of row for asset list-->
    <?php
} catch (PDOException $e) {
    error_log("Error in assethistorytable.php: " . $e->getMessage());
    echo '<div class="alert alert-danger">An error occurred while fetching the records</div>';
}
?>