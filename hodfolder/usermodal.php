<?php
require "../admindashboard/include/config.php"; // Include the database configuration file

// Check if the form is submitted via POST and the 'submit' button is set
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    // Sanitize and trim input values to prevent XSS and whitespace issues
    $firstname = htmlspecialchars(trim($_POST['firstname']));
    $lastname = htmlspecialchars(trim($_POST['lastname']));
    $username = htmlspecialchars(trim($_POST['username']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = trim($_POST['password']); // Store password as plain text
    $cpass = trim($_POST['cpass']); // Confirm password
    $role = htmlspecialchars(trim($_POST['role']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $department = htmlspecialchars(trim($_POST['department']));    // Check if email, phone, or username already exists in the database
    $checkSql = "SELECT * FROM user_table WHERE email = :email OR phone = :phone OR username = :username";
    $checkStmt = $conn->prepare($checkSql);
    if ($checkStmt) {
        // Bind the parameters to the prepared statement
        $checkStmt->bindParam(':email', $email, PDO::PARAM_STR);
        $checkStmt->bindParam(':phone', $phone, PDO::PARAM_STR);
        $checkStmt->bindParam(':username', $username, PDO::PARAM_STR);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            // Alert the user if any of the fields already exist in the database
            echo "<script>alert('Error: Email, phone, or username already exists.');</script>";
            return;
        }
    } else {
        // Alert the user if the validation statement could not be prepared
        echo "<script>alert('Error: Could not prepare validation statement.');</script>";
        return;
    }

    // Verify if the confirm password matches the plain text password
    if ($cpass === $password) {        // Prepare the SQL query to insert user data into the database
        $sql = "INSERT INTO user_table (firstname, lastname, username, email, password, role, phone, department) 
                VALUES (:firstname, :lastname, :username, :email, :password, :role, :phone, :department)";
        $stmt = $conn->prepare($sql); // Prepare the SQL statement
        if ($stmt) {
            // Bind the parameters to the prepared statement
            $stmt->bindParam(':firstname', $firstname, PDO::PARAM_STR);
            $stmt->bindParam(':lastname', $lastname, PDO::PARAM_STR);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->bindParam(':role', $role, PDO::PARAM_STR);
            $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
            $stmt->bindParam(':department', $department, PDO::PARAM_STR);

            // Execute the statement and check if the query was successful
            if ($stmt->execute()) {
                // Alert the user of successful registration
                echo "<script>alert('User registered successfully!');</script>";
            } else {
                // Alert the user if the registration failed
                echo "<script>alert('Error: Could not register user.');</script>";
            }
        } else {
            // Alert the user if the statement preparation failed
            echo "<script>alert('Error: Could not prepare statement.');</script>";
        }
    } else {
        // Alert the user if the passwords do not match
        echo "<script>alert('Passwords do not match.');</script>";
    }
}
?>
<div class="row"><!-- Begin of row for modal -->
    <!-- Column -->
    <div class="col-md-12 col-lg-6 col-xlg-6"> 
        <!-- Button to trigger the modal -->
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal" data-whatever="@mdo">Add User</button>

        <!-- Modal structure -->
        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">User Information</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Navigation tabs for modal -->
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="basic-info-tab" data-toggle="tab" href="#basic-info" role="tab" aria-controls="basic-info" aria-selected="true">User Information</a>
                            </li>
                        </ul>
                        <!-- Tab content -->
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="basic-info-tab">
                                <!-- Form for user registration -->
                                <form action="" method="POST">
                                    <div class="row">
                                        <!-- Input fields for user details -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="firstname" class="col-form-label">First Name:</label>
                                                <input type="text" class="form-control" id="firstname" name="firstname">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="lastname" class="col-form-label">Last Name:</label>
                                                <input type="text" class="form-control" id="lastname" name="lastname">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="username" class="col-form-label">Username:</label>
                                                <input type="text" class="form-control" id="username" name="username">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="email" class="col-form-label">Email:</label>
                                                <input type="email" class="form-control" id="email" name="email">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="password" class="col-form-label">Password:</label>
                                                <input type="password" class="form-control" id="password" name="password">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="cpass" class="col-form-label">Confirm Password:</label>
                                                <input type="password" class="form-control" id="cpass" name="cpass">
                                            </div>
                                        </div>
                                  <!--       <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="role" class="col-form-label">Role:</label>
                                                <select id="role" class="form-control" name="role">
                                                    <option selected disabled>Select Role</option>                                                    <?php
                                                    // Fetch roles from the database and populate the dropdown
                                                    $sql = "SELECT * FROM role";
                                                    $stmt = $conn->prepare($sql);
                                                    if ($stmt->execute()) {
                                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                            echo "<option value='".htmlspecialchars($row['user_role'], ENT_QUOTES)."'>".
                                                                 htmlspecialchars($row['user_role'], ENT_QUOTES)."</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div> -->



                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="phone" class="col-form-label">Role:</label>
                                                <select name="role" id="role" class="form-control">
                                                    <option selected disabled>Select Role</option>
                                                    <option value="user">user</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="phone" class="col-form-label">Phone Number:</label>
                                                <input type="text" class="form-control" id="phone" name="phone">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="department" class="col-form-label">Department:</label>
                                                <select id="department" class="form-control" name="department">
                                                    <option selected disabled>Select Department</option>                                                    <?php
                                                    // Fetch departments from the database and populate the dropdown
                                                    $sql = "SELECT * FROM department_table";
                                                    $stmt = $conn->prepare($sql);
                                                    if ($stmt->execute()) {
                                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                            echo "<option value='".htmlspecialchars($row['department'], ENT_QUOTES)."'>".
                                                                 htmlspecialchars($row['department'], ENT_QUOTES)."</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <!-- Modal footer with buttons -->
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-primary" name="submit">Register</button>
                                        </div>
                                    </div>
                                </form>
                            </div><!-- End of basic info tab -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- End of column -->
</div><!-- End of row for modal -->