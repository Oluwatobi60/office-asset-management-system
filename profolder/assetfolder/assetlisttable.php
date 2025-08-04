<?php require "../admindashboard/include/config.php"; ?> <!-- Include the database configuration file -->
<div class="row mt-5"><!-- Begin of row for asset list -->
    <div class="col-md-12 col-lg-12 col-xlg-3">
        <table class="table shadow table-striped table-bordered table-hover"><!-- Table for displaying asset list -->
            <thead class="thead-dark">
                <tr>
                    <th scope="col">#</th> <!-- Serial number column -->
                    <th scope="col">Reg No.</th> <!-- Asset registration number column -->
                    <th scope="col">Asset Name</th> <!-- Asset name column -->
                    <th scope="col">Description</th> <!-- Asset description column -->
                    <th scope="col">Category</th> <!-- Asset category column -->
                    <th scope="col">Purchase Date</th> <!-- Asset purchase date column -->
                    <th scope="col">Qty</th> <!-- Asset quantity column -->
                    <th scope="col">Action</th> <!-- Action column for edit/delete -->
                </tr>
            </thead>
            <tbody>
                <?php                $s = 1; // Initialize serial number
                try {
                    // SQL query to fetch asset details
                    $sql = "SELECT * FROM asset_table ORDER BY id DESC"; 
                    $stmt = $conn->prepare($sql); // Prepare the query
                    $stmt->execute(); // Execute the query
                    
                    // Start looping through results
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { // Loop through each row
                        echo "<tr>";
                        echo "<th scope='row'>" . $s++ . "</th>"; // Display serial number
                        echo "<td>" . $row['reg_no'] . "</td>"; // Display registration number
                        echo "<td>" . $row['asset_name'] . "</td>"; // Display asset name
                        echo "<td>" . $row['description'] . "</td>"; // Display description
                        echo "<td>" . $row['category'] . "</td>"; // Display category
                        echo "<td>" . $row['dateofpurchase'] . "</td>"; // Display purchase date
                        // Display quantity with a green or red badge
                        echo "<td>";
                        echo ($row['quantity'] == 0) 
                            ? "<span class='badge badge-danger'>Not Available</span>" // Red badge if not available
                            : "<span class='badge badge-success'>" . $row['quantity'] . "</span>"; // Green badge for available
                        echo "</td>";
                        // Display action buttons for edit and delete
                        echo "<td>
                            <a href='assetfolder/editasset.php?id=" . $row['id'] . "'><i class='fa fa-edit'></i></a>
                            <a href='assetfolder/deleteasset.php?id=" . $row['id'] . "'><i class='fa fa-trash'></i></a>
                        </td>";                        echo "</tr>";
                    }
                } catch (PDOException $e) {
                    // Log error and display a user-friendly message
                    error_log("Error in assetlisttable.php: " . $e->getMessage());
                    echo "<tr><td colspan='8' class='text-center'>No assets found or error loading assets.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div><!-- End of row for asset list -->