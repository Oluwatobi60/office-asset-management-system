<?php require "include/config.php";?>
<div class="row mt-5"><!--Begin of row for asset list-->
                    <div class="col-md-12 col-lg-12 col-xlg-3">
                    <table class="table  shadow-lg table-bordered table-hover table-striped table-responsive-sm">
                        <thead class="thead-dark">
                            <tr>
                            <th scope="col">#</th>
                            <th scope="col">First Name</th>
                            <th scope="col">Last Name</th>
                            <th scope="col">Username</th>
                            <th scope="col">Email</th>
                            <th scope="col">Phone</th>
                            <th scope="col">Role</th>
                            <th scope="col">Department</th>
                            <th scope="col">Action</th>

                            </tr>
                        </thead>
                        <tbody>                                <?php
                                $s=1;
                                $sql = "SELECT * FROM user_table"; 
                                $stmt = $conn->prepare($sql);
                                $stmt->execute();
                                $hasRows = $stmt->rowCount() > 0;
                                if($hasRows){
                                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                                        echo "<tr>";
                                        echo "<th scope='row'>".$s++."</th>";
                                        echo "<td>".$row['firstname']."</td>";
                                        echo "<td>".$row['lastname']."</td>";
                                        echo "<td>".$row['username']."</td>";
                                        echo "<td>".$row['email']."</td>";
                                        echo "<td>".$row['phone']."</td>";
                                        echo "<td>".$row['role']."</td>";
                                        echo "<td>".$row['department']."</td>";
                                        echo "<td>
                                        <a href='userfolder/edituser.php?id=".$row['id']."'><i class='fa fa-edit'></i></a>
                                        <a href='userfolder/deleteuser.php?id=".$row['id']."'><i class='fa fa-trash'></i></a>
                                        </td>";
                                        echo "</tr>";
                                    }
                                }
                                ?>
                        </tbody>
                        </table>
                    </div>
                </div><!-- End of row for asset list-->