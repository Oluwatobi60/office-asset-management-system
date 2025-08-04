<?php
require_once dirname(__FILE__) . "/../include/config.php";
require_once dirname(__FILE__) . "/../../include/utils.php";

if(isset($_POST['submit'])) {
    try {
        // Sanitize and validate input
        $asset_cat = trim($_POST['categories']);
        
        if (empty($asset_cat)) {
            throw new Exception("Category name cannot be empty");
        }
        
        // Check if category already exists using prepared statement
        $check_sql = "SELECT COUNT(*) FROM category WHERE category = :category";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bindParam(':category', $asset_cat);
        $check_stmt->execute();
        
        if ($check_stmt->fetchColumn() > 0) {
            echo "<script>alert('Category already exists');</script>";
        } else {
            // Insert new category using prepared statement
            $sql = "INSERT INTO category (category) VALUES (:category)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':category', $asset_cat);
            
            if($stmt->execute()){
                logError("New category added successfully: " . $asset_cat);
                echo "<script>alert('New Category Added successfully'); window.location.reload();</script>";
            } else {
                throw new PDOException("Failed to add category");
            }
        }
    } catch (Exception $e) {
        logError("Error in catmodal.php: " . $e->getMessage());
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}
?>

<div class="row"><!-- Begin of row for for modal-->
    <!-- Column -->
    <div class="col-md-12 col-lg-6 col-xlg-6"> 
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal" data-whatever="@mdo">Add Category</button>
        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Asset Category</h5>
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
                                                <label for="category" class="col-form-label">Category:</label>
                                                <input type="text" class="form-control" name="categories" id="category" placeholder="Enter Category" required>
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