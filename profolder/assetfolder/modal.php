<?php
require "../admindashboard/include/config.php";

if(isset($_POST['submit'])) {
    try {
        // Sanitize and validate input
        $reg_no = trim($_POST['reg_no']);
        $asset_name = trim($_POST['asset_name']);
        $asset_desc = trim($_POST['asset_desc']);
        $asset_qty = (int)$_POST['asset_qty'];
        $asset_cat = isset($_POST['asset_cat']) ? trim($_POST['asset_cat']) : '';
        $date_of_purchase = trim($_POST['date_of_purchase']);

        // Check if any field is empty
        if (empty($reg_no) || empty($asset_name) || empty($asset_desc) || empty($asset_qty) || empty($asset_cat) || empty($date_of_purchase)) {
            echo "<script>alert('All fields are required. Please fill out the form completely.'); window.history.back();</script>";
            exit;
        }

        // Check if registration number already exists
        $check_sql = "SELECT COUNT(*) FROM asset_table WHERE reg_no = :reg_no";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bindParam(':reg_no', $reg_no, PDO::PARAM_STR);
        $check_stmt->execute();
        
        if($check_stmt->fetchColumn() > 0) {
            echo "<script>alert('Registration Number already exists'); window.location.href='assets.php';</script>";
            exit;
        }

        // Insert new asset
        $sql = "INSERT INTO asset_table (reg_no, asset_name, description, quantity, category, dateofpurchase) 
                VALUES (:reg_no, :asset_name, :description, :quantity, :category, :dateofpurchase)";
        
        $stmt = $conn->prepare($sql);
        
        // Bind parameters
        $stmt->bindParam(':reg_no', $reg_no, PDO::PARAM_STR);
        $stmt->bindParam(':asset_name', $asset_name, PDO::PARAM_STR);
        $stmt->bindParam(':description', $asset_desc, PDO::PARAM_STR);
        $stmt->bindParam(':quantity', $asset_qty, PDO::PARAM_INT);
        $stmt->bindParam(':category', $asset_cat, PDO::PARAM_STR);
        $stmt->bindParam(':dateofpurchase', $date_of_purchase, PDO::PARAM_STR);

        if($stmt->execute()) {
            // Log successful asset addition
            error_log("New asset added: " . $reg_no . " - " . $asset_name);
            echo "<script>alert('New Asset Added successfully'); window.location.href='assets.php';</script>";
        } else {
            throw new PDOException("Failed to add new asset");
        }

    } catch (PDOException $e) {
        // Log the error
        error_log("Error in modal.php: " . $e->getMessage());
        echo "<script>alert('Error adding asset. Please try again.');</script>";
    }
}
?>

<div class="row"><!-- Begin of row for for modal-->
                    <!-- Column -->
                    <div class="col-md-12 col-lg-6 col-xlg-6"> 
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal" data-whatever="@mdo">Add Assets</button>
                        

                    
                        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="exampleModalLabel">Asset Information</h5>
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
                                        <!-- Basic info tab-->
                                        <div class="tab-content" id="myTabContent">
                                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="basic-info-tab">
                                                <form action="" method="POST">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="asset-model" class="col-form-label">Registration No.:</label>
                                                                <div class="input-group">
                                                                    <div class="input-group-prepend">
                                                                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                                                    </div>
                                                                    <input type="text" class="form-control" name="reg_no" id="reg_no" readonly>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="asset-name" class="col-form-label">Asset Name:</label>
                                                                <div class="input-group">
                                                                    <div class="input-group-prepend">
                                                                        <span class="input-group-text"><i class="fas fa-box"></i></span>
                                                                    </div>
                                                                    <input type="text" class="form-control" placeholder="Enter Asset Name" name="asset_name" id="asset-name">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="description" class="col-form-label">Description:</label>
                                                                <div class="input-group">
                                                                    <div class="input-group-prepend">
                                                                        <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                                                    </div>
                                                                    <textarea class="form-control" placeholder="Enter Asset Description" name="asset_desc" id="description"></textarea>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="unit" class="col-form-label">Asset Quantity:</label>
                                                                <div class="input-group">
                                                                    <div class="input-group-prepend">
                                                                        <span class="input-group-text"><i class="fas fa-sort-numeric-up"></i></span>
                                                                    </div>
                                                                    <input type="number" class="form-control" placeholder="Enter Asset Quantity" name="asset_qty" id="unit">
                                                                </div>
                                                            </div>
                                                        </div>
                                            
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="category" class="col-form-label">Category:</label>
                                                                <div class="input-group">
                                                                    <div class="input-group-prepend">
                                                                        <span class="input-group-text"><i class="fas fa-list"></i></span>
                                                                    </div>
                                                                    <select id="category" class="form-control" name="asset_cat">
                                                                    <option selected disabled>Select Category</option>                                                                    <?php
                                                                    try {
                                                                        $sql = "SELECT * FROM category";
                                                                        $stmt = $conn->prepare($sql);
                                                                        $stmt->execute();
                                                                        
                                                                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                                            echo "<option value='" . htmlspecialchars($row['category']) . "'>" . 
                                                                                 htmlspecialchars($row['category']) . "</option>";
                                                                        }
                                                                    } catch (PDOException $e) {
                                                                        error_log("Error loading categories: " . $e->getMessage());
                                                                        echo "<option value=''>Error loading categories</option>";
                                                                    }
                                                                    ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="category" class="col-form-label">Date of Purchase:</label>
                                                                <div class="input-group">
                                                                    <div class="input-group-prepend">
                                                                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                                                    </div>
                                                                    <input type="date" class="form-control" id="category" name="date_of_purchase">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary" name="submit">Submit</button>
                                    </div>
                                                    </div>
                                                </form>
                                            </div>
                                            <!-- ============= End Asset Assigned tab=========-->
                                        </div>
                                    </div>
                                   
                                </div>
                            </div>
                        </div>
                    </div><!-- End of column -->
                </div><!-- end of Row for modal-->

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Attach an event listener to the modal when it is about to be shown
        $('#exampleModal').on('show.bs.modal', function() {
            // Generate a random registration number
            const randomRegNo = Math.random().toString(36).substring(2, 4).toUpperCase() + 
                                Math.floor(1000 + Math.random() * 9000).toString();
            // Set the generated registration number to the input field with ID 'reg_no'
            document.getElementById('reg_no').value = randomRegNo; // Corrected ID from 'asset-model' to 'reg_no'
        });
    });
</script>