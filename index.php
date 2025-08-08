<?php
// Start the PHP session to enable session variables
session_start();

// Start output buffering to prevent header modification issues
ob_start();

// Include database configuration file
include 'include/config.php';

// Define a function to log errors with timestamps
function logError($message) {
    // Set the path to the error log file
    $logFile = 'error_log.txt';
    // Get current timestamp for the log entry
    $timestamp = date("Y-m-d H:i:s");
    // Append the error message with timestamp to the log file
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

// Check if database connection is successful
if (!$conn) {
    // Log and display database connection failure
    logError("Database connection failed");
    die("Database connection failed");
}

// Initialize array to store department names
$departments = [];

// SQL query to fetch unique department names in lowercase
$deptQuery = "SELECT DISTINCT LOWER(department) AS department FROM user_table WHERE department IS NOT NULL";

try {
    // Execute the department query
    $deptResult = $conn->query($deptQuery);
    
    // Fetch each department and add to departments array
    while ($row = $deptResult->fetch(PDO::FETCH_ASSOC)) {
        $departments[] = $row['department'];
    }
} catch (PDOException $e) {
    // Log any database errors that occur while fetching departments
    logError("Failed to fetch departments: " . $e->getMessage());
}

// Check if the form was submitted using POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Remove whitespace from username and password inputs
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validate that both username and password are provided
    if (!empty($username) && !empty($password)) {
        // SQL query to find user by email or username
        $query = "SELECT * FROM user_table WHERE email = ? OR username = ?";
        
        try {
            // Prepare and execute the query with parameters
            $stmt = $conn->prepare($query);
            $stmt->execute([$username, $username]);
            
            // Fetch the user data
            $user = $stmt->fetch(PDO::FETCH_ASSOC);            // Check if user exists
            if ($user !== false) {
                // Verify password match without logging sensitive data
                if ($password === $user['password']) {
                    // Log successful login attempt without password info
                    logError("Login successful for user '$username'");

                    // Convert role to lowercase for consistent comparison
                    $role = strtolower($user['role']);
                    // Get department and ensure it exists
                    $department = isset($user['department']) ? strtolower($user['department']) : '';

                    // Validate user role and department
                    if (in_array($role, ['hod', 'user', 'admin', 'procurement']) &&
                        in_array($department, $departments)) {
                        
                        // Set session variables for the authenticated user
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['department'] = $user['department'];

                        // Display success message
                        echo "<div class='alert alert-success text-center'>Login successful! Redirecting...</div>";

                        // Redirect user based on their role
                        switch ($role) {
                            case 'hod':
                                header("Location: hodfolder/hoddashboard.php");
                                break;
                            case 'user':
                                header("Location: userfolder/dashboard.php");
                                break;
                            case 'procurement':
                                header("Location: profolder/prodashboard.php");
                                break;
                            case 'admin':
                                header("Location: admindashboard/index.php");
                                break;
                        }
                        // Stop execution after redirect
                        exit();
                    } else {
                        // Set error for invalid role or department
                        $error = "Invalid role or category.";
                        logError("Login failed: Invalid role or category for user '$username'.");
                    }
                } else {
                    // Set error for password mismatch
                    $error = "Invalid username or password.";
                    logError("Login failed: Password mismatch for user '$username'.");
                }
            } else {
                // Set error for non-existent user
                $error = "Invalid username or password.";
                logError("Login failed: No matching user found for '$username'.");
            }
        } catch (PDOException $e) {
            // Set error for database query failure
            $error = "Failed to prepare the SQL statement.";
            logError("SQL error: " . $e->getMessage());
        }
    } else {
        // Set error for empty fields
        $error = "Please fill in all fields.";
        logError("Login failed: Missing username or password.");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Set character encoding for the document -->
    <meta charset="UTF-8">
    <!-- Configure viewport for responsive design -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Set favicon for the website -->
    <link rel="icon" type="image/png" sizes="16x16" href="admindashboard/assets/images/logo.png">
    <!-- Set page title -->
    <title>Login | Asset Management</title>
    <!-- Include Bootstrap CSS framework -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<!-- Set body background and styling -->
<body class="bg-light" style="background-image: url('background.jpg'); background-size: cover; background-position: center;">
    <!-- Main container with vertical and horizontal centering -->
    <div class="container d-flex justify-content-center align-items-center vh-100 shadow-sm">
        <!-- Responsive row for layout -->
        <div class="row w-100 align-items-center">
            <!-- Left column for logo -->
            <div class="col-md-5 text-center mb-4 mb-md-0">
                <img src="admindashboard/assets/images/logo.png" alt="Office" class="img-fluid rounded shadow-lg" style="max-height: 300px;">
            </div>
            <!-- Right column for login form -->
            <div class="col-md-5">
                <!-- Login card with shadow effect -->
                <div class="card shadow-lg p-4">
                    <!-- Card title -->
                    <h3 class="text-center mb-4">Asset Management</h3>
                    <!-- Display error message if exists -->
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <!-- Login form -->
                    <form method="POST" action="">
                        <!-- Username/Email input field -->
                        <div class="mb-3">
                            <label for="username" class="form-label">Email/Username</label>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username">
                        </div>
                        <!-- Password input field -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password">
                        </div>
                        <!-- Submit button -->
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    <!-- Forgot password link -->
                    <div class="text-center mt-3">
                        <a href="#" class="text-decoration-none">Forgot Password?</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Include Bootstrap JS bundle for functionality -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>