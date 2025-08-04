<?php require "../admindashboard/include/config.php";?>
<div class="row mt-5"><!--Begin of row for asset list-->
                    <div class="col-md-12 col-lg-12 col-xlg-3">
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
                        </thead>                        <?php
                                try {
                                    $sql = "SELECT * FROM maintenance_table"; 
                                    $stmt = $conn->prepare($sql);
                                    $stmt->execute();
                                    
                                    $s = 1;
                                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        // Get the current date for comparison
                                        $currentDate = new DateTime();
                                        $nextServiceDate = new DateTime($row['next_service']);
                                        
                                        // Add warning class if service is due within 7 days
                                        $serviceClass = '';
                                        $interval = $currentDate->diff($nextServiceDate);
                                        if ($interval->days <= 7 && $nextServiceDate > $currentDate) {
                                            $serviceClass = 'class="table-warning"';
                                        } elseif ($nextServiceDate < $currentDate) {
                                            $serviceClass = 'class="table-danger"';
                                        }
                                        
                                        echo "<tr " . $serviceClass . ">";
                                        echo "<th scope='row'>" . htmlspecialchars($s++) . "</th>";
                                        echo "<td>" . htmlspecialchars($row['reg_no']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['asset_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                        echo "<td style='color:red;'>" . htmlspecialchars($row['last_service']) . "</td>";
                                        echo "<td style='color:green;'>" . htmlspecialchars($row['next_service']) . "</td>";
                                        echo "<td>
                                            <a href='maintenancefolder/deletemain.php?id=" . htmlspecialchars($row['id']) . "' 
                                               onclick='return confirm(\"Are you sure you want to delete this record?\");'>
                                               <i class='fa fa-trash' style='color:red'></i>
                                            </a>
                                        </td>";
                                        echo "</tr>";
                                    }
                                    
                                    if ($stmt->rowCount() === 0) {
                                        echo "<tr><td colspan='7' class='text-center'>No maintenance records found</td></tr>";
                                    }
                                } catch (PDOException $e) {
                                    error_log("Error in maintable.php: " . $e->getMessage());
                                    echo "<tr><td colspan='7' class='text-center text-danger'>An error occurred while fetching the records</td></tr>";
                                }
                                ?>
                        </table>
                    </div>
                </div><!-- End of row for asset list-->