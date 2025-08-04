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
require_once "../admindashboard/include/config.php";

// Get the first name and last name of the logged-in admin
try {
    $pro_username = $_SESSION['username'];
    $pro_query = "SELECT firstname, lastname FROM user_table WHERE username = :username";
    $stmt = $conn->prepare($pro_query);
    $stmt->bindParam(':username', $pro_username, PDO::PARAM_STR);
    $stmt->execute();
    $pro_row = $stmt->fetch(PDO::FETCH_ASSOC);
    $pro_first_name = $pro_row['firstname'] ?? '';
    $pro_last_name = $pro_row['lastname'] ?? '';

    // Fetch total "Today Request" based on quantity
    $query = "SELECT SUM(quantity) AS total_today_request_quantity FROM request_table WHERE DATE(request_date) = CURDATE()";
    $stmt = $conn->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_today_request_quantity = $row['total_today_request_quantity'] ?? 0; // Default to 0 if no record

    // Fetch total "This Month Request" based on quantity
    $query = "SELECT SUM(quantity) AS total_this_month_request_quantity FROM request_table WHERE MONTH(request_date) = MONTH(CURDATE()) AND YEAR(request_date) = YEAR(CURDATE())";
    $stmt = $conn->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_this_month_request_quantity = $row['total_this_month_request_quantity'] ?? 0; // Default to 0 if no record

    // Fetch total "HOD Approved" requests
    $query = "SELECT COUNT(*) AS total_hod_approved FROM request_table WHERE hod_approved = 1";
    $stmt = $conn->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_hod_approved = $row['total_hod_approved'] ?? 0; // Default to 0 if no record

    // Fetch total "HOD Not Approved" requests
    $query = "SELECT COUNT(*) AS total_hod_not_approved FROM request_table WHERE hod_approved = 0";
    $stmt = $conn->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_hod_not_approved = $row['total_hod_not_approved'] ?? 0; // Default to 0 if no record

    // Fetch total "All Previous Days Not Approved by HOD or Procurement" requests
    $query = "SELECT COUNT(*) AS total_previous_days_not_approved FROM request_table WHERE (hod_approved = 0 OR pro_approved = 0) AND DATE(request_date) < CURDATE()";
    $stmt = $conn->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_previous_days_not_approved = $row['total_previous_days_not_approved'] ?? 0; // Default to 0 if no record    // Fetch total number of all assets
    $query = "SELECT COUNT(*) AS total_assets FROM asset_table";
    $stmt = $conn->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_assets = $row['total_assets'] ?? 0; // Default to 0 if no record

} catch (PDOException $e) {
    // Log error and set default values
    error_log("Database error in prodashboard.php: " . $e->getMessage());
    $total_hod_not_approved = 0;
    $total_previous_days_not_approved = 0;
    $total_assets = 0;
    $total_today_request_quantity = 0;
    $total_this_month_request_quantity = 0;
    $total_hod_approved = 0;
    $pro_first_name = 'User';
    $pro_last_name = '';
}
?>

<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" type="image/png" sizes="16x16" href="../admindashboard/assets/images/logo.png">
    <title>Procurement||Dashboard</title>
    <link href="../admindashboard/assets/libs/flot/css/float-chart.css" rel="stylesheet">
    <link href="../admindashboard/dist/css/style.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .stat-card {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-content {
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .stat-icon {
            font-size: 40px;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 15px;
        }
        
        .stat-details {
            flex-grow: 1;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: rgba(255,255,255,0.8);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-top: 30px;
            height: 400px; /* Added fixed height */
            position: relative; /* Added for proper chart sizing */
        }

        .chart-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }

        .page-title {
            font-size: 28px;
            font-weight: 600;
            color: #333;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 3px solid #4e73df;
            display: inline-block;
        }

        .dashboard-header {
            margin-bottom: 40px;
        }
    </style>
</head>

<body>
    <div id="main-wrapper">
        <header class="topbar" data-navbarbg="skin5">
            <nav class="navbar top-navbar navbar-expand-md navbar-dark">
                <div class="navbar-header" data-logobg="skin5">
                    <a class="nav-toggler waves-effect waves-light d-block d-md-none" href="javascript:void(0)"><i class="ti-menu ti-close"></i></a>
                    <a class="navbar-brand" href="index.php">
                        <b class="logo-icon p-l-10">
                            <img src="../admindashboard/assets/images/logo.png" alt="homepage" class="light-logo" width="100px"/>
                        </b>
                    </a>
                    <a class="topbartoggler d-block d-md-none waves-effect waves-light" href="javascript:void(0)" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><i class="ti-more"></i></a>
                </div>
                <div class="navbar-collapse collapse" id="navbarSupportedContent" data-navbarbg="skin5">
                    <ul class="navbar-nav float-left mr-auto">
                        <li class="nav-item d-none d-md-block"><a class="nav-link sidebartoggler waves-effect waves-light" href="javascript:void(0)" data-sidebartype="mini-sidebar"><i class="mdi mdi-menu font-24"></i></a></li>
                        <li class="nav-item search-box"> <a class="nav-link waves-effect waves-dark" href="javascript:void(0)"><i class="ti-search"></i></a>
                            <form class="app-search position-absolute">
                                <input type="text" class="form-control" placeholder="Search &amp; enter"> <a class="srh-btn"><i class="ti-close"></i></a>
                            </form>
                        </li>
                    </ul>
                    <ul class="navbar-nav float-right">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-muted waves-effect waves-dark pro-pic" href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <img src="../admindashboard/assets/images/users/1.jpg" alt="user" class="rounded-circle" width="31">
                                <span class="online-indicator" style="color: green; font-size: 12px;">●</span>
                                <span class="username" style="margin-left: 5px;"><?php echo htmlspecialchars($pro_first_name . ' ' . $pro_last_name); ?></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right user-dd animated">
                                <a class="dropdown-item" href="logout.php"><i class="fa fa-power-off m-r-5 m-l-5"></i> Logout</a>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>
        </header>

        <?php require "asidebar.php"; ?>

        <div class="page-wrapper">
            <div class="container-fluid">
                <div class="dashboard-header">
                    <h1 class="page-title">Procurement Dashboard</h1>
                    <div class="welcome-message" style="color: #666; font-size: 1.1em; margin-top: -15px; margin-bottom: 25px;">
                        Welcome, <span style="color: #4e73df; font-weight: 600;"><?php echo htmlspecialchars($pro_first_name . ' ' . $pro_last_name); ?></span>!
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card">
                            <div class="stat-content bg-danger">
                                <div class="stat-icon bg-danger-light">
                                    <i class="fas fa-clock text-white"></i>
                                </div>
                                <div class="stat-details">
                                    <div class="stat-value text-white"><?php echo $total_previous_days_not_approved; ?></div>
                                    <div class="stat-label">Previous Days Not Approved</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card">
                            <div class="stat-content bg-info">
                                <div class="stat-icon bg-info-light">
                                    <i class="fas fa-calendar-day text-white"></i>
                                </div>
                                <div class="stat-details">
                                    <div class="stat-value text-white"><?php echo $total_today_request_quantity; ?></div>
                                    <div class="stat-label">Today's Requests</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card">
                            <div class="stat-content bg-success">
                                <div class="stat-icon bg-success-light">
                                    <i class="fas fa-calendar-alt text-white"></i>
                                </div>
                                <div class="stat-details">
                                    <div class="stat-value text-white"><?php echo $total_this_month_request_quantity; ?></div>
                                    <div class="stat-label">This Month's Requests</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card">
                            <div class="stat-content bg-primary">
                                <div class="stat-icon bg-primary-light">
                                    <i class="fas fa-check-circle text-white"></i>
                                </div>
                                <div class="stat-details">
                                    <div class="stat-value text-white"><?php echo $total_hod_approved; ?></div>
                                    <div class="stat-label">HOD Approved</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card">
                            <div class="stat-content bg-warning">
                                <div class="stat-icon bg-warning-light">
                                    <i class="fas fa-exclamation-circle text-white"></i>
                                </div>
                                <div class="stat-details">
                                    <div class="stat-value text-white"><?php echo $total_hod_not_approved; ?></div>
                                    <div class="stat-label">HOD Not Approved</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card">
                            <div class="stat-content bg-purple" style="background-color: #6f42c1;">
                                <div class="stat-icon bg-purple-light">
                                    <i class="fas fa-boxes text-white"></i>
                                </div>
                                <div class="stat-details">
                                    <div class="stat-value text-white"><?php echo $total_assets; ?></div>
                                    <div class="stat-label">Total Assets</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="chart-container">
                            <h2 class="chart-title">Asset Requests Overview</h2>
                            <canvas id="assetBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <?php require "../admindashboard/include/footer.php" ?>
        </div>
    </div>

    <script src="../admindashboard/assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../admindashboard/assets/libs/popper.js/dist/umd/popper.min.js"></script>
    <script src="../admindashboard/assets/libs/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="../admindashboard/assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../admindashboard/dist/js/waves.js"></script>
    <script src="../admindashboard/dist/js/sidebarmenu.js"></script>
    <script src="../admindashboard/dist/js/custom.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        var ctx = document.getElementById('assetBarChart').getContext('2d');
        var assetBarChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Today Request', 'This Month Request', 'HOD Approved', 'HOD Not Approved', 'Previous Days Not Approved'],
                datasets: [{
                    label: 'Requests',
                    data: [
                        <?php echo $total_today_request_quantity; ?>,
                        <?php echo $total_this_month_request_quantity; ?>,
                        <?php echo $total_hod_approved; ?>,
                        <?php echo $total_hod_not_approved; ?>,
                        <?php echo $total_previous_days_not_approved; ?>
                    ],
                    backgroundColor: [
                        '#36b9cc',  // Info color
                        '#1cc88a',  // Success color
                        '#4e73df',  // Primary color
                        '#f6c23e',  // Warning color
                        '#e74a3b'   // Danger color
                    ],
                    borderColor: [
                        '#2c9faf',
                        '#169b6b',
                        '#2e59d9',
                        '#dda20a',
                        '#be2617'
                    ],
                    borderWidth: 1,
                    borderRadius: 8,
                    maxBarThickness: 50
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        top: 20,
                        right: 20,
                        bottom: 20,
                        left: 20
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                return context.raw + ' Requests';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12,
                                weight: '500'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            borderDash: [2, 4],
                            color: '#e0e0e0'
                        },
                        ticks: {
                            beginAtZero: true,
                            precision: 0,
                            stepSize: Math.ceil(Math.max(
                                <?php echo $total_today_request_quantity; ?>,
                                <?php echo $total_this_month_request_quantity; ?>,
                                <?php echo $total_hod_approved; ?>,
                                <?php echo $total_hod_not_approved; ?>,
                                <?php echo $total_previous_days_not_approved; ?>
                            ) / 5),
                            font: {
                                size: 12,
                                weight: '500'
                            }
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    </script>
</body>
</html>