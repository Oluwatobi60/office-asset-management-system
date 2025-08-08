<?php 
// Include database configuration file
require "../admindashboard/include/config.php"; 
?>
<!-- Bootstrap row container for the asset request list -->
<div class="row mt-5">
    <div class="col-md-12 col-lg-12 col-xlg-3">
        <!-- Responsive table with Bootstrap styling -->
        <table class="table shadow-lg table-striped table-bordered table-hover">
            <thead class="thead-dark">
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Reg No.</th>
                    <th scope="col">Asset Name</th>
                    <th scope="col">Request By</th>
                    <th scope="col">Request Date</th>
                    <th scope="col">HOD Status</th>
                    <th scope="col">Procurement Status</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <?php
                // Initialize counter for row numbering
                $s = 1;
                // Get current logged-in username from session
                $username = $_SESSION['username'];                // SQL query to fetch request details with user role
                // Join request_table with user_table to get user role information
                $sql = "SELECT r.*, u.role FROM request_table r 
                        LEFT JOIN user_table u ON r.requested_by = u.username 
                        WHERE r.requested_by = :username";
                // Prepare the SQL statement to prevent SQL injection
                $stmt = $conn->prepare($sql);
                // Bind the username parameter to the query
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                // Execute the prepared statement
                $stmt->execute();
                
                // Check if any requests exist
                if ($stmt->rowCount() > 0) {
                    // Loop through each request
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        // Safely access $row['id'] with null coalescing operator to avoid undefined key warning
                        $id = isset($row['id']) ? $row['id'] : '';
                        echo "<tr>";
                        // Display row number and increment counter
                        echo "<th scope='row'>".$s++."</th>";
                        // Display request registration number
                        echo "<td>".$row['reg_no']."</td>";
                        // Display asset name
                        echo "<td>".$row['asset_name']."</td>";
                        // Display name of employee who made the request
                        echo "<td>".$row['assigned_employee']."</td>";
                        // Display date when request was made
                        echo "<td>".$row['request_date']."</td>";
                        
                        // HOD Status button - always disabled for regular users
                        echo "<td>";
                        // Set button color based on HOD approval status
                        $hodBtnClass = $row['hod_approved'] == 1 ? 'btn-success' : 'btn-warning';
                        // Set button text based on HOD approval status
                        $hodStatus = $row['hod_approved'] == 1 ? 'Approved' : 'Not Approved';
                        // Display disabled HOD status button
                        echo "<button class='btn {$hodBtnClass}' disabled>{$hodStatus}</button>";
                        echo "</td>";
                        
                        // Procurement Status button - always disabled for regular users
                        echo "<td>";
                        // Set button color based on procurement approval status
                        $proBtnClass = $row['pro_approved'] == 1 ? 'btn-success' : 'btn-warning';
                        // Set button text based on procurement approval status
                        $proStatus = $row['pro_approved'] == 1 ? 'Approved' : 'Not Approved';
                        // Display disabled procurement status button
                        echo "<button class='btn {$proBtnClass}' disabled>{$proStatus}</button>";
                        echo "</td>";

                        // Action column with view button
                        echo "<td>
                        <a href='requestfolder/viewrequest.php?id=".$row['staff_id']."'><i class='fa fa-eye'></i></a>
                        </td>";
                        echo "</tr>";
                    }
                }
            ?>
        </table>
    </div>
</div><!-- End of row for asset list -->