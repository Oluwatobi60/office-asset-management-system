<?php require "include/config.php";?>
<div class="row mt-5"><!--Begin of row for asset list-->
                    <div class="col-md-12 col-lg-12 col-xlg-3">
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
                                $s=1;
                                $sql = "SELECT * FROM request_table"; 
                                $result = $conn->query($sql);
                                if($result->num_rows > 0){
                                    while($row = $result->fetch_assoc()){
                                        echo "<tr>";
                                        echo "<th scope='row'>".$s++."</th>";
                                        echo "<td>".$row['reg_no']."</td>";
                                        echo "<td>".$row['asset_name']."</td>";
                                        echo "<td>".$row['assigned_employee']."</td>";
                                        echo "<td>".$row['request_date']."</td>";
                                        echo "<td><button class='btn btn-warning'>Approved</button></td>";
                                        echo "<td><button class='btn btn-warning'>Yet to Despense</button></td>";
                                        echo "<td>
                                        <a href='requestfolder/deleterequest.php?id=".$row['id']."'><i class='fa fa-trash' style='color:red'></i></a>
                                        <a href='requestfolder/viewrequest.php?id=".$row['id']."'><i class='fa fa-eye'></i></a>
                                        </td>";
                                        echo "</tr>";
                                    }
                                }
                                ?>
                        </table>
                    </div>
                </div><!-- End of row for asset list-->