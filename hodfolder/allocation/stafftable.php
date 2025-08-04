<?php
require_once dirname(__FILE__) . "/../../include/utils.php";
require_once dirname(__FILE__) . "/../../include/config.php";

// ===== Pagination Configuration =====
// Set the number of items to display per page
$items_per_page = 7; // Minimum items per page

// Get the current page number from URL, default to page 1 if not set
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Calculate the offset for SQL LIMIT clause
$offset = ($page - 1) * $items_per_page;

// Start a session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== Filter Configuration =====
// Initialize WHERE clause and params array for SQL query
$where_clause = "WHERE 1=1";
$params = [];

// Get HOD's department from session
if (!isset($_SESSION['department'])) {
    die("Error: Department information not found. Please log in again.");
}
$hod_department = $_SESSION['department'];
$where_clause .= " AND s.department = :department";
$params[':department'] = $hod_department;

// Add employee filter if provided
if (isset($_GET['employee']) && !empty($_GET['employee'])) {
    $where_clause .= " AND s.assigned_employee LIKE :employee";
    $params[':employee'] = "%" . $_GET['employee'] . "%";
}

// Params array is already initialized above with the employee filter

try {
    // Get total count
    $total_sql = "SELECT COUNT(*) AS total FROM staff_table s $where_clause";
    $total_stmt = $conn->prepare($total_sql);
    
    // Bind date parameters if they exist
    foreach ($params as $key => $value) {
        $total_stmt->bindValue($key, $value);
    }
    
    $total_stmt->execute();
    $total_row = $total_stmt->fetch(PDO::FETCH_ASSOC);
    $total_items = $total_row['total'];
    $total_pages = ceil($total_items / $items_per_page);

    // Fetch staff allocation data
    $sql = "SELECT s.*, DATE_FORMAT(s.request_date, '%Y-%m-%d %H:%i') as formatted_date,
            CASE WHEN r.status = 'Under Repair' THEN 1 ELSE 0 END as is_under_repair
            FROM staff_table s 
            LEFT JOIN repair_asset r ON s.id = r.asset_id AND r.status = 'Under Repair'
            $where_clause 
            ORDER BY s.request_date DESC 
            LIMIT :offset, :limit";
    
    $stmt = $conn->prepare($sql);
    
    // Bind the date parameters first
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    // Then bind the pagination parameters
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    
    $stmt->execute();
    
} catch (PDOException $e) {
    logError("Database error in stafftable.php: " . $e->getMessage() . 
            "\nSQL Query: " . (isset($sql) ? $sql : $total_sql) . 
            "\nParameters: offset=" . $offset . ", limit=" . $items_per_page);
    $total_items = 0;
    $total_pages = 1;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Allocation</title>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <style>
        .ui-autocomplete {
            max-height: 200px;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 1000;
        }
        .employee-filter {
            position: relative;
            margin-bottom: 1rem;
        }
        .employee-filter input {
            width: 100%;
            padding: 0.375rem 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
        }
        .loading {
            background-image: url('data:image/gif;base64,R0lGODlhEAAQAPIAAP///wAAAMLCwkJCQgAAAGJiYoKCgpKSkiH/C05FVFNDQVBFMi4wAwEAAAAh/hpDcmVhdGVkIHdpdGggYWpheGxvYWQuaW5mbwAh+QQJCgAAACwAAAAAEAAQAAADMwi63P4wyklrE2MIOggZnAdOmGYJRbExwroUmcG2LmDEwnHQLVsYOd2mBzkYDAdKa+dIAAAh+QQJCgAAACwAAAAAEAAQAAADNAi63P5OjCEgG4QMu7DmikRxQlFUYDEZIGBMRVsaqHwctXXf7WEYB4Ag1xjihkMZsiUkKhIAIfkECQoAAAAsAAAAABAAEAAAAzYIujIjK8pByJDMlFYvBoVjHA70GU7xSUJhmKtwHPAKzLO9HMaoKwJZ7Rf8AYPDDzKpZBqfvwQAIfkECQoAAAAsAAAAABAAEAAAAzMIumIlK8oyhpHsnFZfhYumCYUhDAQxRIdhHBGqRoKw0R8DYlJd8z0fMDgsGo/IpHI5TAAAIfkECQoAAAAsAAAAABAAEAAAAzIIunInK0rnZBTwGPNMgQwmdsNgXGJUlIWEuR5oWUIpz8pAEAMe6TwfwyYsGo/IpFKSAAAh+QQJCgAAACwAAAAAEAAQAAADMwi6IMKQORfjdOe82p4wGccc4CEuQradylesojEMBgsUc2G7sDX3lQGBMLAJibufbSlKAAAh+QQJCgAAACwAAAAAEAAQAAADMgi63P7wCRHZnFVdmgHu2nFwlWCI3WGc3TSWhUFGxTAUkGCbtgENBMJAEJsxgMLWzpEAACH5BAkKAAAALAAAAAAQABAAAAMyCLrc/jDKSatlQtScKdceCAjDII7HcQ4EMTCpyrCuUBjCYRgHVtqlAiB1YhiCnlsRkAAAOwAAAAAAAAAAAA==');
            background-position: right center;
            background-repeat: no-repeat;
            padding-right: 25px;
        }
        .ui-autocomplete-loading {
            background-position: right center;
            background-repeat: no-repeat;
        }
    </style>
</head>
<body>
    <!-- Main content container -->
    <div class="row mt-5">
        <div class="col-md-12 col-lg-12 col-xlg-3">
            <!-- Filter Section -->
            <div class="card mb-3">
                <div class="card-body">
                    <form id="filterForm" method="GET" class="row align-items-center">
                        <div class="col-md-4">
                            <div class="employee-filter">
                                <label for="employee" class="form-label">Filter by Employee:</label>
                                <input type="text" class="form-control" id="employee" name="employee" 
                                       value="<?php echo isset($_GET['employee']) ? htmlspecialchars($_GET['employee']) : ''; ?>" 
                                       placeholder="Type employee name...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block">Apply Filter</button>
                        </div>
                        <?php if (isset($_GET['employee'])): ?>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <a href="?<?php echo isset($_GET['page']) ? 'page=' . $_GET['page'] : ''; ?>" 
                                   class="btn btn-secondary d-block">Clear Filter</a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <!-- Responsive table wrapper -->
            <div class="table-responsive">
                <table class="table shadow table-striped table-bordered table-hover">                <thead class="thead-dark">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Reg No.</th>
                        <th scope="col">Asset Name</th>
                        <th scope="col">Department</th>
                        <th scope="col">Assigned To</th>
                      <!--   <th scope="col">Requested By</th> -->
                        <th scope="col">Quantity</th>
                        <th scope="col">Allocation Date</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>                <tbody>
                    <?php
                    $s = $offset + 1; // Initialize serial number for the current page
                    $hasRecords = false; // Flag to check if any records exist
                    
                    // Fetch and display all results using PDO
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $hasRecords = true; // Set flag when at least one record is found
                        // Sanitize data to prevent XSS attacks
                        $id = htmlspecialchars($row['id']);
                        $reg_no = htmlspecialchars($row['reg_no']);
                        $asset_name = htmlspecialchars($row['asset_name']);
                        $department = htmlspecialchars($row['department']);
                        $assigned_employee = htmlspecialchars($row['assigned_employee']);
                      /*   $requested_by = htmlspecialchars($row['requested_by']); */
                        $quantity = (int)$row['quantity'];
                        $request_date = htmlspecialchars($row['formatted_date']);
                        $status = isset($row['status']) ? htmlspecialchars($row['status']) : 'Pending';
                        ?>
                        <tr>
                            <th scope="row"><?php echo $s++; ?></th>
                            <td><?php echo $reg_no; ?></td>
                            <td><?php echo $asset_name; ?></td>
                            <td><?php echo $department; ?></td>
                            <td><?php echo $assigned_employee; ?></td>
                           <!--  <td><?php //echo $requested_by; ?></td>  -->
                            <td><span class="badge badge-info"><?php echo $quantity; ?></span></td>
                            <td><?php echo $request_date; ?></td>
                            <td>
                                <a href="allocation/viewallocation.php?id=<?php echo $id; ?>" class="btn btn-info btn-sm">
                                    <i class="fa fa-eye"></i>
                                </a>
                             
                            <?php if ($row['is_under_repair']): ?>
                                    <button class="btn btn-secondary btn-sm" disabled>
                                        <i class="fa fa-wrench"></i> Under Repair
                                    </button>
                                <?php else: ?>
                                    <button onclick="markForRepair(<?php echo $id; ?>, <?php 
                                        echo htmlspecialchars(json_encode([
                                            'reg_no' => $reg_no,
                                            'asset_name' => $asset_name,
                                            'department' => $department,
                                            'category' => 'General'
                                        ]), ENT_QUOTES); 
                                    ?>)" class="btn btn-warning btn-sm">
                                        <i class="fa fa-wrench"></i> Need Repair
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php
                    }
                    
                    // Display message if no records found
                    if (!$hasRecords): ?>
                        <tr>
                            <td colspan="8" class="text-center">
                                <div class="alert alert-info" role="alert">
                                    <?php 
                                    if (!empty($_GET['employee'])) {
                                        echo "No allocations found for employee \"" . htmlspecialchars($_GET['employee']) . "\" in " . htmlspecialchars($hod_department) . " department.";
                                    } else {
                                        echo "No staff allocations found for " . htmlspecialchars($hod_department) . " department.";
                                    }
                                    ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div><!-- End of table-responsive -->        <!-- Pagination controls -->
        <?php if ($hasRecords): // Only show pagination if there are records ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php
                // Initialize filter parameters string
                $filter_params = '';
                // Add employee filter parameter if it exists
                if (!empty($_GET['employee'])) {
                    $filter_params .= '&employee=' . htmlspecialchars($_GET['employee']);
                }
                ?>
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo ($page - 1) . $filter_params; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i . $filter_params; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo ($page + 1) . $filter_params; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; // End of if ($hasRecords) condition ?>
    </div>
</div><!-- End of row for asset list -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
    $(document).ready(function() {
        $("#employee").autocomplete({
            source: "get_employee_suggestions.php",
            minLength: 2,
            select: function(event, ui) {
                if (ui.item) {
                    $("#employee").val(ui.item.value);
                    $("#filterForm").submit();
                }
            },
            response: function(event, ui) {
                if (!ui.content.length) {
                    var noResult = { label: "No matches found", value: "" };
                    ui.content.push(noResult);
                }
            }
        }).data("ui-autocomplete")._renderItem = function(ul, item) {
            return $("<li>")
                .append("<div>" + (item.label || item.value) + "</div>")
                .appendTo(ul);
        };
        
        // Make the autocomplete dropdown width match the input field
        $.ui.autocomplete.prototype._resizeMenu = function() {
            const ul = this.menu.element;
            ul.outerWidth(this.element.outerWidth());
        };

        // Add loading indicator
        $(document).ajaxStart(function() {
            $("#employee").addClass("loading");
        }).ajaxStop(function() {
            $("#employee").removeClass("loading");
        });
    });
</script>
</body>
</html>