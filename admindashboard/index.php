<?php  
session_start(); // Start the session to manage user sessions

// Set session timeout to 20 minutes
$timeout_duration = 1200; // 20 minutes in seconds

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    // If the session has been inactive for too long, destroy it
    session_unset();
    session_destroy();
    header("Location: ../userfolder/index.php"); // Redirect to login page
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time(); // Update last activity timestamp

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../userfolder/index.php"); // Redirect to login page if not logged in
    exit();
}

// Database connection
require_once "include/config.php";

// Section: Fetch Admin User Details
try {
    // Get the username from the session variable
    $admin_username = $_SESSION['username'];
    
    // SQL query to get the admin's first and last name
    $admin_query = "SELECT firstname, lastname FROM user_table WHERE username = ?";
    
    // Prepare the SQL statement to prevent SQL injection
    $stmt = $conn->prepare($admin_query);
    
    // Execute the query with the username parameter
    $stmt->execute([$admin_username]);
    
    // Fetch the result as an associative array
    $admin_row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Extract the first name and last name from the result
    $admin_first_name = $admin_row['firstname'];
    $admin_last_name = $admin_row['lastname'];
} catch (PDOException $e) {
    // Log any database errors that occur
    logError("Failed to fetch admin details: " . $e->getMessage());
    // Set default values if query fails
    $admin_first_name = "Admin";
    $admin_last_name = "User";
}

// Section: Fetch Asset Quantities
try {
    // Prepare query to get total quantity for each asset category
    $query = "SELECT SUM(quantity) AS total_quantity FROM asset_table WHERE category = ?";
    // Create a prepared statement for reuse with different categories
    $stmt = $conn->prepare($query);

    // Get total quantity of printers
    // Execute the query specifically for 'Printers' category
    $stmt->execute(['Printers']);
    // Fetch the result row
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    // Store the quantity, use 0 if NULL using null coalescing operator
    $total_printer_quantity = $row['total_quantity'] ?? 0;

    // Get furniture quantity
    $stmt->execute(['Furniture']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_furniture_quantity = $row['total_quantity'] ?? 0;

    // Get laptops quantity
    $stmt->execute(['Laptops']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_laptop_quantity = $row['total_quantity'] ?? 0;

    // Get accessories quantity
    $stmt->execute(['Accessories']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_accessories_quantity = $row['total_quantity'] ?? 0;

    // Get AC quantity
    $stmt->execute(['AC']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_ac_quantity = $row['total_quantity'] ?? 0;
} catch (PDOException $e) {
    logError("Failed to fetch asset quantities: " . $e->getMessage());
    // Set default values if query fails
    $total_printer_quantity = 0;
    $total_furniture_quantity = 0;
    $total_laptop_quantity = 0;
    $total_accessories_quantity = 0;
    $total_ac_quantity = 0;
}

// Fetch total "Today Request"
/* $query = "SELECT COUNT(*) AS total_today_request FROM request_table WHERE DATE(request_date) = CURDATE()";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$total_today_request = $row['total_today_request']; */

// Section: Fetch Request Statistics
try {
    // Query to get total requests for today
    // Uses DATE() function to compare only the date part
    $query = "SELECT SUM(quantity) AS total_today_request_quantity 
              FROM request_table 
              WHERE DATE(request_date) = CURDATE()";
    $stmt = $conn->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    // Store today's request quantity, default to 0 if NULL
    $total_today_request_quantity = $row['total_today_request_quantity'] ?? 0;

    // Query to get total requests for current month
    // Uses MONTH() and YEAR() to match current month and year
    $query = "SELECT SUM(quantity) AS total_this_month_request_quantity 
              FROM request_table 
              WHERE MONTH(request_date) = MONTH(CURDATE()) 
              AND YEAR(request_date) = YEAR(CURDATE())";
    $stmt = $conn->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    // Store monthly request quantity, default to 0 if NULL
    $total_this_month_request_quantity = $row['total_this_month_request_quantity'] ?? 0;

    // Fetch total number of users
    $query = "SELECT COUNT(*) AS total_users FROM user_table";
    $stmt = $conn->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_users = $row['total_users'] ?? 0;
} catch (PDOException $e) {
    logError("Failed to fetch request statistics: " . $e->getMessage());
    $total_today_request_quantity = 0;
    $total_this_month_request_quantity = 0;
    $total_users = 0;
}
?>

<!DOCTYPE html>
<html dir="ltr" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/logo.png">
    <title>Admin||Dashboard</title>
    <!-- Custom CSS -->
    <link href="assets/libs/flot/css/float-chart.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="dist/css/style.min.css" rel="stylesheet">

</head>

<body>
    <!-- ============================================================== -->
    <!-- Preloader - style you can find in spinners.css -->
    <!-- ============================================================== -->
   <!--  <div class="preloader">
        <div class="lds-ripple">
            <div class="lds-pos"></div>
            <div class="lds-pos"></div>
        </div>
    </div> -->
    <!-- ============================================================== -->
    <!-- Main wrapper - style you can find in pages.scss -->
    <!-- ============================================================== -->
    <div id="main-wrapper">
        <!-- ============================================================== -->
        <!-- Topbar header - style you can find in pages.scss -->
        <!-- ============================================================== -->
        <header class="topbar" data-navbarbg="skin5">
            <nav class="navbar top-navbar navbar-expand-md navbar-dark">
                <div class="navbar-header" data-logobg="skin5">
                    <!-- This is for the sidebar toggle which is visible on mobile only -->
                    <a class="nav-toggler waves-effect waves-light d-block d-md-none" href="javascript:void(0)"><i class="ti-menu ti-close"></i></a>
                    <!-- ============================================================== -->
                    <!-- Logo -->
                    <!-- ============================================================== -->
                    <a class="navbar-brand" href="index.php">
                        <!-- Logo icon -->
                        <b class="logo-icon p-l-10">
                            <!--You can put here icon as well // <i class="wi wi-sunset"></i> //-->
                            <!-- Dark Logo icon -->
                            <img src="assets/images/logo.png" alt="homepage" class="light-logo" width="100px"/>
                           
                        </b>
                        <!--End Logo icon -->
                         <!-- Logo text -->
                        <span class="logo-text">
                        
                            
                     
                    <a class="topbartoggler d-block d-md-none waves-effect waves-light" href="javascript:void(0)" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><i class="ti-more"></i></a>
                </div>
                <!-- ============================================================== -->
                <!-- End Logo -->
                <!-- ============================================================== -->
                <div class="navbar-collapse collapse" id="navbarSupportedContent" data-navbarbg="skin5">
                    <!-- ============================================================== -->
                    <!-- toggle and nav items -->
                    <!-- ============================================================== -->
                    <ul class="navbar-nav float-left mr-auto">
                        <li class="nav-item d-none d-md-block"><a class="nav-link sidebartoggler waves-effect waves-light" href="javascript:void(0)" data-sidebartype="mini-sidebar"><i class="mdi mdi-menu font-24"></i></a></li>
                        <!-- ============================================================== -->
                    
                        <!-- Search -->
                        <!-- ============================================================== -->
                        <li class="nav-item search-box"> <a class="nav-link waves-effect waves-dark" href="javascript:void(0)"><i class="ti-search"></i></a>
                            <form class="app-search position-absolute">
                                <input type="text" class="form-control" placeholder="Search &amp; enter"> <a class="srh-btn"><i class="ti-close"></i></a>
                            </form>
                        </li>
                    </ul>
                    <!-- ============================================================== -->
                    <!-- Right side toggle and nav items -->
                    <!-- ============================================================== -->
                    <ul class="navbar-nav float-right">
                        <!-- ============================================================== -->
                        <!-- Comment -->
                        <!-- ============================================================== -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle waves-effect waves-dark" href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="mdi mdi-bell font-24"></i>
                            </a>
                             <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="#">Action</a>
                                <a class="dropdown-item" href="#">Another action</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#">Something else here</a>
                            </div>
                        </li>
                     
                        <!-- ============================================================== -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle waves-effect waves-dark" href="" id="2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="font-24 mdi mdi-comment-processing"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right mailbox animated bounceInDown" aria-labelledby="2">
                                <ul class="list-style-none">
                                    <li>
                                        <div class="">
                                             <!-- Message -->
                                            <a href="javascript:void(0)" class="link border-top">
                                                <div class="d-flex no-block align-items-center p-10">
                                                    <span class="btn btn-success btn-circle"><i class="ti-calendar"></i></span>
                                                    <div class="m-l-10">
                                                        <h5 class="m-b-0">Event today</h5> 
                                                        <span class="mail-desc">Just a reminder that event</span> 
                                                    </div>
                                                </div>
                                            </a>
                                            <!-- Message -->
                                            <a href="javascript:void(0)" class="link border-top">
                                                <div class="d-flex no-block align-items-center p-10">
                                                    <span class="btn btn-info btn-circle"><i class="ti-settings"></i></span>
                                                    <div class="m-l-10">
                                                        <h5 class="m-b-0">Settings</h5> 
                                                        <span class="mail-desc">You can customize this template</span> 
                                                    </div>
                                                </div>
                                            </a>
                                           
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </li>
                   

                        <!-- User profile and search -->
                        <!-- ============================================================== -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-muted waves-effect waves-dark pro-pic" href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <img src="assets/images/users/1.jpg" alt="user" class="rounded-circle" width="31">
                                <span class="online-indicator" style="color: green; font-size: 12px;">●</span>
                                <span class="username" style="margin-left: 5px;"><?php echo htmlspecialchars($admin_first_name . ' ' . $admin_last_name); ?></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right user-dd animated">
                                <a class="dropdown-item" href="javascript:void(0)"><i class="ti-user m-r-5 m-l-5"></i> My Profile</a>
                                <a class="dropdown-item" href="javascript:void(0)"><i class="ti-wallet m-r-5 m-l-5"></i> My Balance</a>
                                <a class="dropdown-item" href="javascript:void(0)"><i class="ti-email m-r-5 m-l-5"></i> Inbox</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="javascript:void(0)"><i class="ti-settings m-r-5 m-l-5"></i> Account Setting</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="logout.php"><i class="fa fa-power-off m-r-5 m-l-5"></i> Logout</a>
                                <div class="dropdown-divider"></div>
                                <div class="p-l-30 p-10"><a href="javascript:void(0)" class="btn btn-sm btn-success btn-rounded">View Profile</a></div>
                            </div>
                        </li>
                        <!-- ============================================================== -->
                        <!-- User profile and search -->
                        <!-- ============================================================== -->
                    </ul>
                </div>
            </nav>
        </header>
        <!-- ============================================================== -->
        <!-- End Topbar header -->
      

        <!-- Left Sidebar - style you can find in sidebar.scss  -->
        <!-- ============================================================== -->
        <?php require "asidebar.php";  ?>
        <!-- ============================================================== -->
        <!-- End Left Sidebar - style you can find in sidebar.scss  -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- Page wrapper  -->
        <!-- ============================================================== -->
        <div class="page-wrapper">
            <!-- ============================================================== -->
            <!-- Bread crumb and right sidebar toggle -->
            <!-- ============================================================== -->
             <div class="page-breadcrumb">
                <div class="row">
                    <div class="col-12 d-flex no-block align-items-center">
                        <h4 class="page-title">Admin Dashboard</h4>
                    </div>
                </div>
            </div>
            <!-- ============================================================== -->
            <!-- End Bread crumb and right sidebar toggle -->
            <!-- ============================================================== -->
            <!-- ============================================================== -->
            <!-- Container fluid  -->
            <!-- ============================================================== -->
            <div class="container-fluid">
                <!-- Main Stats Overview -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-gradient-primary text-white shadow-lg rounded-lg">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h2 class="mb-3">Welcome back, <?php echo htmlspecialchars($admin_first_name . ' ' . $admin_last_name); ?>!</h2>
                                        <p class="mb-0">Your asset management dashboard overview</p>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        <i class="mdi mdi-view-dashboard" style="font-size: 4rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Asset Categories Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h4 class="text-muted mb-4">Asset Categories</h4>
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <div class="card shadow-lg rounded-lg h-100 border-left-primary">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Printers</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_printer_quantity; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="mdi mdi-printer fa-2x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 col-lg-2">
                        <div class="card shadow-lg rounded-lg h-100 border-left-success">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Furniture</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_furniture_quantity; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="mdi mdi-sofa fa-2x text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 col-lg-2">
                        <div class="card shadow-lg rounded-lg h-100 border-left-warning">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Laptops</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_laptop_quantity; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="mdi mdi-laptop fa-2x text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 col-lg-2">
                        <div class="card shadow-lg rounded-lg h-100 border-left-danger">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Accessories</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_accessories_quantity; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="mdi mdi-headphones fa-2x text-danger"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 col-lg-2">
                        <div class="card shadow-lg rounded-lg h-100 border-left-info">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">AC</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_ac_quantity; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="mdi mdi-air-conditioner fa-2x text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h4 class="text-muted mb-4">Statistics</h4>
                    </div>
                    <div class="col-xl-3 col-md-4 mb-4">
                        <div class="card shadow-lg rounded-lg h-100 bg-gradient-primary text-white">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">Total Users</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_users; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="mdi mdi-account-multiple fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-xl-3 col-md-4 mb-4">
                        <div class="card shadow-lg rounded-lg h-100 bg-gradient-warning text-white">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">Today's Requests</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_today_request_quantity; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="mdi mdi-calendar-today fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-4 mb-4">
                        <div class="card shadow-lg rounded-lg h-100 bg-gradient-danger text-white">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">Monthly Requests</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_this_month_request_quantity; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="mdi mdi-calendar fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="row">
                    <div class="col-xl-8 col-lg-7">
                        <div class="card shadow-lg rounded-lg mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-light">
                                <h6 class="m-0 font-weight-bold text-primary">Asset Overview</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-area">
                                    <canvas id="assetBarChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-lg-5">
                        <div class="card shadow-lg rounded-lg mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-light">
                                <h6 class="m-0 font-weight-bold text-primary">Request Distribution</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-pie pt-4 pb-2">
                                    <canvas id="requestDistributionChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- footer -->
            <!-- ============================================================== -->
           <?php require "include/footer.php" ?>
            <!-- ============================================================== -->
            <!-- End footer -->
            <!-- ============================================================== -->
        </div>
        <!-- ============================================================== -->
        <!-- End Page wrapper  -->
        <!-- ============================================================== -->
    </div>
    <!-- ============================================================== -->
    <!-- End Wrapper -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- All Jquery -->
    <!-- ============================================================== -->
    <script src="assets/libs/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap tether Core JavaScript -->
    <script src="assets/libs/popper.js/dist/umd/popper.min.js"></script>
    <script src="assets/libs/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="assets/extra-libs/sparkline/sparkline.js"></script>
    <!--Wave Effects -->
    <script src="dist/js/waves.js"></script>
    <!--Menu sidebar -->
    <script src="dist/js/sidebarmenu.js"></script>
    <!--Custom JavaScript -->
    <script src="dist/js/custom.min.js"></script>
    <!--This page JavaScript -->
    <!-- <script src="dist/js/pages/dashboards/dashboard1.js"></script> -->
    <!-- Charts js Files -->
    <script src="assets/libs/flot/excanvas.js"></script>
    <script src="assets/libs/flot/jquery.flot.js"></script>
    <script src="assets/libs/flot/jquery.flot.pie.js"></script>
    <script src="assets/libs/flot/jquery.flot.time.js"></script>
    <script src="assets/libs/flot/jquery.flot.stack.js"></script>
    <script src="assets/libs/flot/jquery.flot.crosshair.js"></script>
    <script src="assets/libs/flot.tooltip/js/jquery.flot.tooltip.min.js"></script>
    <script src="dist/js/pages/chart/chart-page-init.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Get the canvas context for the asset bar chart
    var ctx = document.getElementById('assetBarChart').getContext('2d');
    
    // Initialize the bar chart with configuration
    var assetBarChart = new Chart(ctx, {
        // Set chart type to bar
        type: 'bar',
        // Define the data structure
        data: {
            // Define labels for each asset category
            labels: ['Printers', 'Furniture', 'Laptops', 'Accessories', 'AC'],
            datasets: [{
                label: 'Asset Quantities',
                data: [
                    <?php echo $total_printer_quantity; ?>, 
                    <?php echo $total_furniture_quantity; ?>, 
                    <?php echo $total_laptop_quantity; ?>, 
                    <?php echo $total_accessories_quantity; ?>, 
                    <?php echo $total_ac_quantity; ?>
                ],
                backgroundColor: [
                    'rgba(78, 115, 223, 0.8)',
                    'rgba(28, 200, 138, 0.8)',
                    'rgba(246, 194, 62, 0.8)',
                    'rgba(231, 74, 59, 0.8)',
                    'rgba(54, 185, 204, 0.8)'
                ],
                borderColor: [
                    'rgb(78, 115, 223)',
                    'rgb(28, 200, 138)',
                    'rgb(246, 194, 62)',
                    'rgb(231, 74, 59)',
                    'rgb(54, 185, 204)'
                ],
                borderWidth: 2,
                borderRadius: 5
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false,
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    var reqCtx = document.getElementById('requestDistributionChart').getContext('2d');
    var requestDistributionChart = new Chart(reqCtx, {
        type: 'doughnut',
        data: {
            labels: ['Today\'s Requests', 'Monthly Requests'],
            datasets: [{
                data: [
                    <?php echo $total_today_request_quantity; ?>,
                    <?php echo $total_this_month_request_quantity; ?>
                ],
                backgroundColor: [
                    'rgba(246, 194, 62, 0.8)',
                    'rgba(231, 74, 59, 0.8)'
                ],
                borderColor: [
                    'rgb(246, 194, 62)',
                    'rgb(231, 74, 59)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    </script>

<style>
.bg-gradient-primary {
    background: linear-gradient(45deg, #4e73df 10%, #224abe 100%);
}

.bg-gradient-success {
    background: linear-gradient(45deg, #1cc88a 10%, #13855c 100%);
}

.bg-gradient-warning {
    background: linear-gradient(45deg, #f6c23e 10%, #dda20a 100%);
}

.bg-gradient-danger {
    background: linear-gradient(45deg, #e74a3b 10%, #be2617 100%);
}

.bg-gradient-secondary {
    background: linear-gradient(45deg, #858796 10%, #60616f 100%);
}

.border-left-primary {
    border-left: 4px solid #4e73df;
}

.border-left-success {
    border-left: 4px solid #1cc88a;
}

.border-left-warning {
    border-left: 4px solid #f6c23e;
}

.border-left-danger {
    border-left: 4px solid #e74a3b;
}

.border-left-info {
    border-left: 4px solid #36b9cc;
}

.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-5px);
}

.chart-area {
    position: relative;
    height: 350px;
    width: 100%;
}

.chart-pie {
    position: relative;
    height: 350px;
    width: 100%;
}
</style>
</body>

</html>