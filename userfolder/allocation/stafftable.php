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

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo "Unauthorized access. Please log in.";
    exit;
}

// Get the user's full name from the database
try {
    $username = $_SESSION['username'];
    $userStmt = $conn->prepare("SELECT CONCAT(firstname, ' ', lastname) AS full_name, department FROM user_table WHERE username = :username");
    $userStmt->bindParam(':username', $username, PDO::PARAM_STR);
    $userStmt->execute();
    $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userRow) {
        http_response_code(404);
        echo "User details not found.";
        exit;
    }
    
    $user_fullname = $userRow['full_name'];
    $user_department = $userRow['department'];
    
    // ===== Filter Configuration =====
    // Initialize WHERE clause and params array for SQL query
    $where_clause = "WHERE s.assigned_employee = :assigned_employee AND s.department = :department";
    $params = [
        ':assigned_employee' => $user_fullname,
        ':department' => $user_department
    ];

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
        $sql = "SELECT s.*, DATE_FORMAT(s.request_date, '%Y-%m-%d %H:%i') as formatted_date 
                FROM staff_table s 
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
} catch (PDOException $e) {
    http_response_code(500);
    echo "Internal server error. Please try again later.";
    exit;
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
        .btn-repair {
            transition: all 0.3s ease;
        }
        .btn-repair:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }
    </style>
</head>
<body>
    <!-- Main content container -->
    <div class="row mt-5">
        <div class="col-md-12 col-lg-12 col-xlg-3">
            <!-- Filter Section -->
            
            <!-- Responsive table wrapper -->
            <div class="table-responsive">
                <table class="table shadow table-striped table-bordered table-hover">                
                    <thead class="thead-dark">
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
                            <td><?php echo $request_date; ?></td>                            <td>
                                <a href="viewallocation.php?id=<?php echo $id; ?>" class="btn btn-info btn-sm">
                                    <i class="fa fa-eye"></i>
                                </a>                                
                                <?php if (!isset($row['status']) || $row['status'] !== 'Under Repair'): ?>
                                    <button id="repair-btn-<?php echo $id; ?>" onclick="handleRepair(this)" class="btn btn-warning btn-sm btn-repair" 
                                        data-asset-id="<?php echo $id; ?>"
                                        data-asset="<?php echo htmlspecialchars(json_encode([
                                            'reg_no' => $reg_no,
                                            'asset_name' => $asset_name,
                                            'department' => $department,
                                            'assigned_employee' => $assigned_employee,
                                            'quantity' => $quantity
                                        ]), ENT_QUOTES); ?>">
                                        <i class="fa fa-wrench"></i> Need Repair
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm" disabled>
                                        <i class="fa fa-wrench"></i> Under Repair
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr><?php
                    }
                    
                    // Display message if no records found
                    if (!$hasRecords): ?>
                        <tr>
                            <td colspan="8" class="text-center">
                                <div class="alert alert-info" role="alert">                                    <?php 
                                    echo "No allocations found for you at this time.";
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
                            <span aria-hidden="true">«</span>
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
                            <span aria-hidden="true">»</span>
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
    function handleRepair(button) {
        const row = button.closest('tr');
        const assetId = button.getAttribute('data-asset-id');
        
        let assetInfo;
        try {
            assetInfo = JSON.parse(button.getAttribute('data-asset'));
            assetInfo.description = 'Marked for repair';
            assetInfo.category = 'General';
            console.log('Parsed asset info:', assetInfo);
        } catch (error) {
            console.error('Error parsing asset info:', error);
            alert('There was an error processing the asset information.');
            return;
        }

        if (!assetId || !assetInfo) {
            console.error('Missing required data:', { assetId, assetInfo });
            alert('Missing required asset information.');
            return;
        }
        
        button.disabled = true;
        button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
        
        const resetButton = () => {
            button.disabled = false;
            button.innerHTML = '<i class="fa fa-wrench"></i> Need Repair';
        };

        (async () => {
            try {
                // CORRECTED URL: Use a simple relative path.
                const requestUrl = '/asset_management/userfolder/allocation/submit_repair.php'; 

                console.log('Sending request to:', requestUrl);
                console.log('Request payload:', {
                    asset_id: assetId,
                    asset_info: assetInfo
                });
                          const response = await fetch(requestUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        asset_id: assetId,
                        asset_info: assetInfo
                    })
                });
                
                const textResponse = await response.text();
                console.log('Raw server response:', textResponse);
                console.log('Response status:', response.status);
                console.log('Response headers:', Object.fromEntries(response.headers.entries()));

                // Try to extract JSON from the response if there's other content
                let jsonText = textResponse;
                const jsonStart = textResponse.indexOf('{');
                const jsonEnd = textResponse.lastIndexOf('}');
                
                if (jsonStart >= 0 && jsonEnd >= 0) {
                    jsonText = textResponse.substring(jsonStart, jsonEnd + 1);
                }

                if (!response.ok) {
                    throw new Error(`Server responded with an error (${response.status}): ${textResponse.substring(0, 300)}`);
                }

                if (!jsonText.trim()) {
                    throw new Error('Server returned an empty response.');
                }
                
                let data;
                try {
                    data = JSON.parse(jsonText);
                    if (!data || typeof data !== 'object') {
                        throw new Error('Invalid JSON structure');
                    }
                } catch (e) {
                    console.error("JSON Parsing Error:", e);
                    console.error("Response text:", textResponse);
                    throw new Error("Failed to parse server response. The server might have returned invalid JSON or additional content.");
                }

                if (data.success) {
                    row.style.backgroundColor = '#fff3cd';
                    
                    const disabledButton = document.createElement('button');
                    disabledButton.className = 'btn btn-secondary btn-sm';
                    disabledButton.disabled = true;
                    disabledButton.innerHTML = '<i class="fa fa-wrench"></i> Under Repair';
                    button.parentNode.replaceChild(disabledButton, button);
                    
                    row.querySelector('a.btn-info').style.pointerEvents = 'none';
                    row.querySelector('a.btn-info').style.opacity = '0.5';
                    
                    alert(data.message || 'Asset has been successfully marked for repair.');
                } else {
                    resetButton();
                    alert(data.message || 'The server indicated a failure to mark the asset for repair.');
                }
            } catch (error) {
                console.error('Repair request failed:', error);
                resetButton();
                alert('An error occurred: ' + error.message);
            }
        })();
    }
</script>
</body>
</html>
