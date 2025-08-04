<?php 
require_once dirname(__FILE__) . "/../include/config.php";
require_once dirname(__FILE__) . "/../../include/utils.php";
?>
<div class="row mt-5"><!--Begin of row for asset list-->
                    <div class="col-md-12 col-lg-12 col-xlg-3">
                    <table class="table shadow table-striped table-bordered table-hover" id="categorytable">
                        <thead class="thead-dark">
                            <tr>
                            <th scope="col">#</th>
                            <th scope="col">Categories</th>
                            <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        try {
                            $s = 1;
                            $sql = "SELECT * FROM category ORDER BY category ASC"; 
                            $stmt = $conn->prepare($sql);
                            $stmt->execute();
                            
                            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                // Sanitize data
                                $id = htmlspecialchars($row['id']);
                                $category = htmlspecialchars($row['category']);                                ?>
                                <tr>
                                    <th scope="row"><?php echo $s++; ?></th>
                                    <td><?php echo $category; ?></td>
                                    <td>
                                        <a href='categoryfolder/editcategory.php?id=<?php echo $id; ?>'><i class='fa fa-edit'></i></a>
                                        <a href='categoryfolder/deletecategory.php?id=<?php echo $id; ?>'><i class='fa fa-trash'></i></a>
                                    </td>
                                </tr>
                                <?php
                            }
                        } catch (PDOException $e) {
                            logError("Database error in categorytable.php: " . $e->getMessage());
                            echo "<tr><td colspan='3' class='text-center text-danger'>Error loading categories</td></tr>";
                        }
                        ?>
                        </tbody>
                        </table>
                    </div>
                </div><!-- End of row for asset list-->