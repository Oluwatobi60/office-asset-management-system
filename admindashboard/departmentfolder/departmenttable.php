<?php require "include/config.php";?>
<div class="row mt-5"><!--Begin of row for asset list-->
                    <div class="col-md-12 col-lg-12 col-xlg-3">
                    <table class="table shadow-lg table-bordered table-hover table-striped table-responsive-sm">
                        <thead class="thead-dark">
                            <tr>
                            <th scope="col">#</th>
                            <th scope="col">Departments</th>
                            <th scope="col">Action</th>

                            </tr>
                        </thead>
                        <tbody>                        <?php
                        $s=1;
                        $sql = "SELECT * FROM department_table"; 
                        $stmt = $conn->prepare($sql);
                        $stmt->execute();
                        
                        if($stmt->rowCount() > 0){
                            while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                                echo "<tr>";
                                echo "<th scope='row'>".$s++."</th>";
                                echo "<td>".htmlspecialchars($row['department'])."</td>";
                                echo "<td>
                                <a href='departmentfolder/editdepartment.php?id=".htmlspecialchars($row['id'])."'><i class='fa fa-edit'></i></a>
                                <a href='departmentfolder/deletedepartment.php?id=".htmlspecialchars($row['id'])."'><i class='fa fa-trash'></i></a>
                                </td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                        </tbody>
                        </table>
                    </div>
                </div><!-- End of row for asset list-->