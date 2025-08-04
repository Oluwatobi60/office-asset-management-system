<?php
require "include/config.php";

if(isset($_POST['submit'])){
    $department = trim($_POST['department']);
    
    try {
        // Check if department already exists
        $check_sql = "SELECT COUNT(*) FROM department_table WHERE department = :department";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bindParam(':department', $department, PDO::PARAM_STR);
        $check_stmt->execute();
        
        if ($check_stmt->fetchColumn() > 0) {
            echo "<script>alert('Department already exists')</script>";
        } else {
            // Insert new department
            $sql = "INSERT INTO department_table (department) VALUES (:department)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':department', $department, PDO::PARAM_STR);
            
            if($stmt->execute()){
                echo "<script>alert('New Department Added successfully')</script>";
            } else {
                echo "<script>alert('Error: Could not add department')</script>";
            }
        }
    } catch(PDOException $e) {
        echo "<script>alert('Error: " . htmlspecialchars($e->getMessage()) . "')</script>";
    }
}
?>

<div class="row"><!-- Begin of row for for modal-->
    <!-- Column -->
    <div class="col-md-12 col-lg-6 col-xlg-6"> 
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal" data-whatever="@mdo">Add Department</button>
        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Department Info</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="basic-info-tab" data-toggle="tab" href="#basic-info" role="tab" aria-controls="basic-info" aria-selected="true">Basic Info</a>
                            </li>
                        </ul>
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="basic-info-tab">
                                <form action="" method="POST">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="department" class="col-form-label">Department:</label>
                                                <input type="text" class="form-control" name="department" id="department" placeholder="Enter Department" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary" name="submit">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- End of column -->
</div><!-- end of Row for modal-->