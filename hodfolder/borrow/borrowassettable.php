<!-- /**
 * Asset Request Table Display
 * This file handles the display and management of asset requests including pagination
 * and status update functionality for Procurement approvals
 */ -->

<?php require "../include/config.php";

// Pagination setup for borrow table
$items_per_page = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Calculate total number of records and pages for pagination
$total_sql = "SELECT COUNT(*) as total FROM borrow_table WHERE department = '{$_SESSION['department']}'";
$total_stmt = $conn->prepare($total_sql);
$total_stmt->execute();
$total_row = $total_stmt->fetch(PDO::FETCH_ASSOC);
$total_items = $total_row['total'];
$total_pages = ceil($total_items / $items_per_page);

// Fetch paginated records
$sql = "SELECT * FROM borrow_table 
        WHERE department = '{$_SESSION['department']}'
        ORDER BY pro_status ASC, borrow_date DESC 
        LIMIT :offset, :limit";
$stmt = $conn->prepare($sql);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->execute();

// Pagination setup for returned assets
$items_per_pages = 6;
$pages = isset($_GET['returned_page']) ? (int)$_GET['returned_page'] : 1;
$offsets = ($pages - 1) * $items_per_pages;

// Calculate total number of returned assets
$total_sqls = "SELECT COUNT(*) as total FROM borrow_table WHERE department = '{$_SESSION['department']}' AND returned = 1";
$total_stmts = $conn->prepare($total_sqls);
$total_stmts->execute();
$total_rows = $total_stmts->fetch(PDO::FETCH_ASSOC);
$total_itemss = $total_rows['total'];
$total_pagess = ceil($total_itemss / $items_per_pages);

// Fetch paginated records for returned assets
$returnedSql = "SELECT * FROM borrow_table 
                WHERE department = '{$_SESSION['department']}' AND returned = 1 
                ORDER BY returned_date DESC 
                LIMIT :offset, :limit";
$returnedStmt = $conn->prepare($returnedSql);
$returnedStmt->bindValue(':offset', $offsets, PDO::PARAM_INT);
$returnedStmt->bindValue(':limit', $items_per_pages, PDO::PARAM_INT);
$returnedStmt->execute();
?>
<!-- Include jQuery for AJAX functionality -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Table Section -->
<div class="row mt-5">
    <!-- Asset Request List Table Container -->
    <div class="col-md-12 col-lg-12 col-xlg-3">
        <!-- Responsive table wrapper for horizontal scrolling on small screens -->
        <div class="table-responsive">
            <!-- Main asset requests table with styling -->
            <table class="table shadow-lg table-striped table-bordered table-hover">
                <!-- Table header section -->
                <thead class="thead-dark">
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Reg No.</th>
                        <th scope="col">Asset Name</th>
                        <th scope="col">Borrow By</th>
                        <th scope="col">Borrow Date</th> <!-- Changed from Request Date to Borrow Date -->
                        <th scope="col">HOD Status</th>
                        <th scope="col">Return</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <?php
                // Initialize counter for row numbering
                $s = $offset + 1;
                
                // Display table rows if there are records
                if ($stmt->rowCount() > 0) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        // Generate table rows with request details
                        echo "<tr data-id='" . $row['id'] . "'>";
                        echo "<th scope='row'>" . $s++ . "</th>";
                        echo "<td>" . $row['reg_no'] . "</td>";
                        echo "<td>" . $row['asset_name'] . "</td>";
                        echo "<td>" . $row['employee_name'] . "</td>";
                        echo "<td>" . $row['borrow_date'] . "</td>";

                        // Procurement Status
                        echo "<td>";
                        if ($row['hod_status'] == 0) {
                            echo "<div class='btn-group'>";
                            echo "<button onclick='updateStatus(\"hod\", " . $row['id'] . ", 1, this)' class='btn btn-warning'>Pending</button>";
                            echo "<button onclick='updateStatus(\"hod\", " . $row['id'] . ", 2, this)' class='btn btn-danger'>Reject</button>";
                            echo "</div>";
                        } else if ($row['hod_status'] == 1) {
                            echo "<button class='btn btn-success' disabled>Approved</button>";
                        } else if ($row['hod_status'] == 2) {
                            echo "<button class='btn btn-danger' disabled>Rejected</button>";
                        }
                        echo "</td>";

                        // Return button cell
                        echo "<td>";
                        if ($row['hod_status'] == 0 && $row['pro_status'] == 1) {
                            echo "<span class='badge badge-warning'>Waiting for HOD Approval</span>";
                        } else if ($row['hod_status'] == 1 && $row['pro_status'] == 1) {
                            if (!isset($row['returned']) || $row['returned'] == 0) {
                                echo "<button onclick='returnAsset(" . $row['id'] . ", this)' class='btn btn-info'>Item Not Returned</button>";
                            } else {
                                echo "<span class='badge badge-success'>Returned</span>";
                            }
                        } else if ($row['hod_status'] == 2) {
                            echo "<span class='badge badge-danger'>Request Rejected</span>";
                        } else {
                            echo "<span class='badge badge-warning'>Waiting For Approval</span>";
                        }
                        echo "</td>";

                        // Action buttons
                        echo "<td>";
                        echo "<a href='requestfolder/deleterequest.php?id=" . $row['id'] . "'><i class='fa fa-trash' style='color:red'></i></a> ";
                        echo "<a href='borrow/viewborrow.php?id=" . $row['id'] . "'><i class='fa fa-eye'></i></a>";
                        echo "</td>";

                        echo "</tr>";
                    }
                }
                ?>
            </table>
        </div>

        <!-- Pagination Navigation -->
        <!-- Shows Previous/Next buttons and page numbers -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
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
</div>

<!-- Returned Assets Table -->
<div class="row mt-5">
    <div class="col-md-12 col-lg-12 col-xlg-3">
        <h3 class="mb-4">Returned Assets</h3>
        <div class="table-responsive returned-assets-table">
            <table class="table shadow-lg table-striped table-bordered table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Reg No.</th>
                        <th scope="col">Asset Name</th>
                        <th scope="col">Returned By</th>
                        <th scope="col">Borrow Date</th>
                        <th scope="col">Return Date</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <?php
                if ($returnedStmt->rowCount() > 0) {
                    while ($row = $returnedStmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr>";
                        echo "<th scope='row'>" . $row['id'] . "</th>";
                        echo "<td>" . $row['reg_no'] . "</td>";
                        echo "<td>" . $row['asset_name'] . "</td>";
                        echo "<td>" . $row['employee_name'] . "</td>";
                        echo "<td>" . $row['borrow_date'] . "</td>";
                        echo "<td>" . $row['returned_date'] . "</td>";
                        echo "<td><span class='badge badge-success'>Returned</span></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' class='text-center'>No returned assets found</td></tr>";
                }
                ?>
            </table>
        </div>

        <!-- Pagination Navigation -->
        <!-- Shows Previous/Next buttons and page numbers -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($pages > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?returned_page=<?php echo $pages - 1; ?>">Previous</a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pagess; $i++): ?>
                    <li class="page-item <?php echo $i == $pages ? 'active' : ''; ?>">
                        <a class="page-link" href="?returned_page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($pages < $total_pagess): ?>
                    <li class="page-item">
                        <a class="page-link" href="?returned_page=<?php echo $pages + 1; ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>

<script>
function updateStatus(type, id, status, button) {
    let data = {};
    if (type === 'hod') {
        data.hod_approve_id = id;
        data.hod_status = status;
    }

    // Send AJAX request to update the status
    $.ajax({
        url: '/asset_management/hodfolder/borrow/update_status.php', // Corrected path
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Update the button group appearance
                let btnGroup = $(button).closest('.btn-group');
                if (status === 1) {
                    btnGroup.html("<button class='btn btn-success' disabled>Approved</button>");
                    // Update return cell
                    let returnCell = $(button).closest('tr').find('td:nth-child(7)');
                    returnCell.html("<button onclick='returnAsset(" + id + ", this)' class='btn btn-info'>Item Not Returned</button>");
                } else if (status === 2) {
                    btnGroup.html("<button class='btn btn-danger' disabled>Rejected</button>");
                    // Update return cell
                    let returnCell = $(button).closest('tr').find('td:nth-child(7)');
                    returnCell.html("<span class='badge badge-danger'>Request Rejected</span>");
                }
            } else {
                alert('Failed to update status: ' + (response.error || 'Please try again.'));
                console.error('Update failed:', response);
            }
        },
        error: function(xhr, status, error) {
            alert('An error occurred: ' + error);
            console.error('AJAX Error:', xhr.responseText);
        }
    });
}

function returnAsset(id, button) {
    if (confirm('Are you sure you want to return this asset?')) {
        $.ajax({
            url: '/asset_managment/profolder/borrow/return_asset.php',
            type: 'POST',
            data: { return_id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Replace return button with returned badge
                    $(button).replaceWith("<span class='badge badge-success'>Returned</span>");
                    
                    // Refresh the returned assets table
                    $.get(window.location.href, function(data) {
                        let newReturnedTable = $(data).find('.returned-assets-table').html();
                        $('.returned-assets-table').html(newReturnedTable);
                    });
                } else {
                    alert('Error returning asset: ' + (response.error || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                alert('Failed to process return. Please try again.');
                console.error('AJAX Error:', xhr.responseText);
            }
        });
    }
}

// Add click handler to make rows selectable
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('table tbody tr');
    rows.forEach(row => {
        row.addEventListener('click', function() {
            // Remove selection from other rows
            rows.forEach(r => r.classList.remove('selected-row'));
            // Add selection to clicked row
            this.classList.add('selected-row');
        });
    });
});
</script>

<style>
.selected-row {
    background-color: #e3f2fd !important;
}
</style>