<?php require "include/config.php";?>
<div class="row mt-5"><!--Begin of row for asset list-->
    <div class="col-md-12 col-lg-12 col-xlg-3">
        <div class="table-responsive"><!-- Added table-responsive class -->
            <table class="table shadow-lg table-striped table-bordered table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Reg No.</th>
                        <th scope="col">Asset Name</th>
                        <th scope="col">Description</th>
                        <th scope="col">Last Service Date</th>
                        <th scope="col">Next Service Date</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                try {
                    $s = 1;
                    $limit = 7; // Number of items per page
                    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                    $offset = ($page - 1) * $limit;

                // Prepare and execute the query with sorting based on next service date
                    $sql = "SELECT *, DATEDIFF(next_service, CURRENT_DATE()) as days_until_service 
                           FROM maintenance_table 
                           ORDER BY CASE 
                               WHEN DATEDIFF(next_service, CURRENT_DATE()) < 0 THEN 1 
                               ELSE 0 
                           END, ABS(DATEDIFF(next_service, CURRENT_DATE())) ASC 
                           LIMIT :limit OFFSET :offset";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                    $stmt->execute();

                    // Fetch and display the results
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr>";
                        echo "<th scope='row'>" . htmlspecialchars($s++) . "</th>";
                        echo "<td>" . htmlspecialchars($row['reg_no']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['asset_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['last_service']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['next_service']) . "</td>";
                        echo "<td>
                            <a href='maintenancefolder/deletemain.php?id=" . htmlspecialchars($row['id']) . "'><i class='fa fa-trash' style='color:red'></i></a>
                            </td>";
                        echo "</tr>";
                    }

                    // Get total count for pagination
                    $sql_total = "SELECT COUNT(*) AS total FROM maintenance_table";
                    $stmt_total = $conn->query($sql_total);
                    $total_items = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
                    $total_pages = ceil($total_items / $limit);
                } catch (PDOException $e) {
                    // Log the error and show a user-friendly message
                    error_log("Database error: " . $e->getMessage());
                    echo '<tr><td colspan="7" class="text-center text-danger">An error occurred while fetching the data. Please try again later.</td></tr>';
                }
                ?>
                </tbody>
            </table>
        </div><!-- End of table-responsive -->

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if (isset($total_pages) && $total_pages > 0): ?>
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div><!-- End of row for asset list -->