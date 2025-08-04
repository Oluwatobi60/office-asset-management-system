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

// Fetch total "HOD Not Approved" requests
$query = "SELECT COUNT(*) AS total_hod_not_approved FROM request_table WHERE hod_approved = 0";
$stmt = $conn->prepare($query);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_hod_not_approved = $row['total_hod_not_approved'] ?? 0; // Default to 0 if no record

// Fetch total "HOD Not Approved" borrow
$query = "SELECT COUNT(*) AS total_hod_not_borrow FROM borrow_table WHERE hod_status = 0";
$stmt = $conn->prepare($query);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_hod_not_borrow = $row['total_hod_not_borrow'] ?? 0; // Default to 0 if no record

// Fetch total rejected requests by HOD
$query = "SELECT COUNT(*) AS total_hod_rejected FROM request_table WHERE hod_approved = 2";
$stmt = $conn->prepare($query);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_hod_rejected = $row['total_hod_rejected'] ?? 0; // Default to 0 if no record

// Get the department and name of the logged-in HOD
$hod_username = $_SESSION['username'];
$dept_query = "SELECT department, firstname, lastname FROM user_table WHERE username = :username";
$stmt = $conn->prepare($dept_query);
$stmt->bindParam(':username', $hod_username, PDO::PARAM_STR);
$stmt->execute();
$dept_row = $stmt->fetch(PDO::FETCH_ASSOC);
$hod_department = $dept_row['department'];
$hod_first_name = $dept_row['firstname'];
$hod_last_name = $dept_row['lastname'];

// Fetch total registered users from HOD's department
$query = "SELECT COUNT(*) AS total_registered_users FROM user_table WHERE department = :department";
$stmt = $conn->prepare($query);
$stmt->bindParam(':department', $hod_department, PDO::PARAM_STR);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_registered_users = $row['total_registered_users'] ?? 0; // Default to 0 if no record

// Fetch monthly HOD approved requests
$current_month = date('m');
$current_year = date('Y');
$monthly_query = "SELECT COUNT(*) as monthly_approved FROM request_table 
                 WHERE hod_approved = 1 
                 AND MONTH(approval_date) = :month 
                 AND YEAR(approval_date) = :year";
$stmt = $conn->prepare($monthly_query);
$stmt->bindParam(':month', $current_month, PDO::PARAM_STR);
$stmt->bindParam(':year', $current_year, PDO::PARAM_STR);
$stmt->execute();
$monthly_row = $stmt->fetch(PDO::FETCH_ASSOC);
$monthly_approved = $monthly_row['monthly_approved'] ?? 0;

// Fetch yearly HOD approved requests
$yearly_query = "SELECT COUNT(*) as yearly_approved FROM request_table 
                WHERE hod_approved = 1 
                AND YEAR(approval_date) = :year";
$stmt = $conn->prepare($yearly_query);
$stmt->bindParam(':year', $current_year, PDO::PARAM_STR);
$stmt->execute();
$yearly_row = $stmt->fetch(PDO::FETCH_ASSOC);
$yearly_approved = $yearly_row['yearly_approved'] ?? 0;

// Fetch combined request and borrow data by month
$combined_query = "SELECT 
    MONTH(COALESCE(r.request_date, b.borrow_date)) as month,
    COUNT(DISTINCT r.id) as request_count,
    COUNT(DISTINCT b.id) as borrow_count
FROM 
    (SELECT DISTINCT department FROM user_table WHERE username = :username) as dept
LEFT JOIN request_table r ON r.department = dept.department
LEFT JOIN borrow_table b ON b.department = dept.department
WHERE 
    (r.request_date IS NOT NULL AND YEAR(r.request_date) = YEAR(CURRENT_DATE))
    OR (b.borrow_date IS NOT NULL AND YEAR(b.borrow_date) = YEAR(CURRENT_DATE))
GROUP BY 
    MONTH(COALESCE(r.request_date, b.borrow_date))
ORDER BY 
    month";

$stmt = $conn->prepare($combined_query);
$stmt->bindParam(':username', $hod_username, PDO::PARAM_STR);
$stmt->execute();
$combined_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

$months = [];
$request_counts = [];
$borrow_counts = [];

// Initialize arrays with 0 for all months
for ($i = 1; $i <= 12; $i++) {
    $months[] = date("F", mktime(0, 0, 0, $i, 1));
    $request_counts[$i-1] = 0;
    $borrow_counts[$i-1] = 0;
}

foreach ($combined_result as $row) {
    $month_index = (int)$row['month'] - 1;
    $request_counts[$month_index] = (int)$row['request_count'];
    $borrow_counts[$month_index] = (int)$row['borrow_count'];
}

// Fetch asset counts by asset name for HOD's department
$asset_query = "SELECT asset_name, quantity 
                FROM request_table 
                WHERE department = :department 
                ORDER BY quantity DESC";
$stmt = $conn->prepare($asset_query);
$stmt->bindParam(':department', $hod_department, PDO::PARAM_STR);
$stmt->execute();
$asset_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

$asset_names = [];
$asset_quantities = [];
foreach ($asset_result as $row) {
    $asset_names[] = $row['asset_name'];
    $asset_quantities[] = $row['quantity'];
}

// Convert PHP arrays to JSON for JavaScript use
$months_json = json_encode($months);
$request_counts_json = json_encode(array_values($request_counts));
$borrow_counts_json = json_encode(array_values($borrow_counts));
$asset_names_json = json_encode($asset_names);
$asset_quantities_json = json_encode($asset_quantities);

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
    <title>HOD||Dashboard</title>
    <!-- Custom CSS -->
    <link href="../admindashboard/assets/libs/flot/css/float-chart.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../admindashboard/dist/css/style.min.css" rel="stylesheet">

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
    <!-- ============================================================== -->
    <!-- Preloader - style you can find in spinners.css -->
    <!-- ============================================================== -->
  <!--   <div class="preloader">
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
                                <span class="username" style="margin-left: 5px;"><?php echo htmlspecialchars($hod_first_name . ' ' . $hod_last_name); ?></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right user-dd animated">
                                <a class="dropdown-item" href="logout.php"><i class="fa fa-power-off m-r-5 m-l-5"></i> Logout</a>
                            </div>
                        </li>
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
           
            <!-- Container fluid  -->
            <!-- ============================================================== -->
            <div class="container-fluid">
                <div class="dashboard-header">
                    <h1 class="page-title">HOD Dashboard</h1>
                    <div class="welcome-message" style="color: #666; font-size: 1.1em; margin-top: -15px; margin-bottom: 25px;">
                        Welcome, <span style="color: #4e73df; font-weight: 600;"><?php echo htmlspecialchars($hod_first_name . ' ' . $hod_last_name); ?></span>!
                    </div>
                </div>

                <div class="row"><!-- Begin of row for navbar-->
                    <!-- Column -->
                    
                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card">
                            <div class="stat-content bg-warning">
                                <div class="stat-icon bg-warning-light">
                                    <i class="fas fa-exclamation-circle text-white"></i>
                                </div>
                                <div class="stat-details">
                                    <div class="stat-value text-white"><?php echo $total_hod_not_approved; ?></div>
                                    <div class="stat-label">Pending Request</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Column -->
                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card">
                            <div class="stat-content bg-info">
                                <div class="stat-icon bg-warning-light">
                                    <i class="fas fa-exclamation-circle text-white"></i>
                                </div>
                                <div class="stat-details">
                                    <div class="stat-value text-white"><?php echo $total_hod_not_borrow; ?></div>
                                    <div class="stat-label">Pending Borrow</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Column -->
                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card">
                            <div class="stat-content bg-danger">
                                <div class="stat-icon bg-danger-light">
                                    <i class="fas fa-times-circle text-white"></i>
                                </div>
                                <div class="stat-details">
                                    <div class="stat-value text-white"><?php echo $total_hod_rejected; ?></div>
                                    <div class="stat-label">Rejected Requests</div>
                                </div>
                            </div>
                        </div>
                    </div>

                      <!-- Column -->
                      <div class="col-md-6 col-lg-4">
                        <div class="stat-card">
                            <div class="stat-content bg-success">
                                <div class="stat-icon bg-warning-light">
                                    <i class="fas fa-exclamation-circle text-white"></i>
                                </div>
                                <div class="stat-details">
                                    <div class="stat-value text-white"><?php echo $total_registered_users; ?></div>
                                    <div class="stat-label">Registered Users in Department</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Column -->
                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card">
                            <div class="stat-content bg-primary">
                                <div class="stat-icon bg-success-light">
                                    <i class="fas fa-check-circle text-white"></i>
                                </div>
                                <div class="stat-details">
                                    <div class="stat-value text-white"><?php echo $monthly_approved; ?></div>
                                    <div class="stat-label">Monthly Approved Requests</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Column --> 
                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card">
                            <div class="stat-content bg-success">
                                <div class="stat-icon bg-success-light">
                                    <i class="fas fa-check-circle text-white"></i>
                                </div>
                                <div class="stat-details">
                                    <div class="stat-value text-white"><?php echo $yearly_approved; ?></div>
                                    <div class="stat-label">Yearly Approved Requests</div>
                                </div>
                            </div>
                        </div>
                    </div>
                
                </div><!-- end of Row for navbar-->
                <!-- ============================================================== -->

                <!-- ============================================================== -->
                <!-- Bar Chart Section -->
                <!-- ============================================================== -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Asset Bar Chart</h4>
                                <div class="chart-container shadow" style="position: relative; height:40vh; width:80vw">
                                    <canvas id="assetBarChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ============================================================== -->

                  <!-- Combined Request and Borrow Chart -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Combined Request and Borrow Chart</h4>
                                <div class="chart-container shadow" style="position: relative; height:40vh; width:80vw">
                                    <canvas id="combinedChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ============================================================== -->

                <!-- Sales chart -->
                <!-- ============================================================== -->
              
                <!-- ============================================================== -->
                <!-- Sales chart -->
                <!-- ============================================================== -->
                <!-- ============================================================== -->
                <!-- Recent comment and chats -->
                <!-- ============================================================== -->
              
                <!-- ============================================================== -->
                <!-- Recent comment and chats -->
                <!-- ============================================================== -->
            </div>
            <!-- ============================================================== -->
            <!-- End Container fluid  -->
            <!-- ============================================================== -->
            <!-- ============================================================== -->
            <!-- footer -->
            <!-- ============================================================== -->
           <?php require "../admindashboard/include/footer.php" ?>
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
    <script src="../admindashboard/assets/libs/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap tether Core JavaScript -->
    <script src="../admindashboard/assets/libs/popper.js/dist/umd/popper.min.js"></script>
    <script src="../admindashboard/assets/libs/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="../admindashboard/assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../admindashboard/assets/extra-libs/sparkline/sparkline.js"></script>
    <!--Wave Effects -->
    <script src="../admindashboard/dist/js/waves.js"></script>
    <!--Menu sidebar -->
    <script src="../admindashboard/dist/js/sidebarmenu.js"></script>
    <!--Custom JavaScript -->
    <script src="../admindashboard/dist/js/custom.min.js"></script>
    <!--This page JavaScript -->
    <!-- <script src="dist/js/pages/dashboards/dashboard1.js"></script> -->
    <!-- Charts js Files -->
    <script src="../admindashboard/assets/libs/flot/excanvas.js"></script>
    <script src="../admindashboard/assets/libs/flot/jquery.flot.js"></script>
    <script src="../admindashboard/assets/libs/flot/jquery.flot.pie.js"></script>
    <script src="../admindashboard/assets/libs/flot/jquery.flot.time.js"></script>
    <script src="../admindashboard/assets/libs/flot/jquery.flot.stack.js"></script>
    <script src="../admindashboard/assets/libs/flot/jquery.flot.crosshair.js"></script>
    <script src="../admindashboard/assets/libs/flot.tooltip/js/jquery.flot.tooltip.min.js"></script>
    <script src="../admindashboard/dist/js/pages/chart/chart-page-init.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        var ctx = document.getElementById('assetBarChart').getContext('2d');
        var assetBarChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo $asset_names_json; ?>,
                datasets: [{
                    label: 'Department Assets',
                    data: <?php echo $asset_quantities_json; ?>,
                    backgroundColor: [
                        'rgba(78, 115, 223, 0.7)',
                        'rgba(54, 185, 204, 0.7)',
                        'rgba(246, 194, 62, 0.7)',
                        'rgba(28, 200, 138, 0.7)',
                        'rgba(231, 74, 59, 0.7)'
                    ],
                    borderColor: [
                        'rgba(78, 115, 223, 1)',
                        'rgba(54, 185, 204, 1)',
                        'rgba(246, 194, 62, 1)',
                        'rgba(28, 200, 138, 1)',
                        'rgba(231, 74, 59, 1)'
                    ],
                    borderWidth: 2,
                    borderRadius: 8,
                    barThickness: 50,
                    maxBarThickness: 80
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                size: 14,
                                family: "'Arial', sans-serif"
                            },
                            padding: 20
                        }
                    },
                    title: {
                        display: true,
                        text: 'Department Asset Distribution',
                        font: {
                            size: 20,
                            family: "'Arial', sans-serif",
                            weight: 'bold'
                        },
                        padding: {
                            top: 10,
                            bottom: 30
                        }
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        },
                        padding: 12,
                        cornerRadius: 6,
                        displayColors: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            font: {
                                size: 12,
                                family: "'Arial', sans-serif"
                            },
                            padding: 10
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12,
                                family: "'Arial', sans-serif"
                            },
                            padding: 10
                        }
                    }
                },
                animation: {
                    duration: 1500,
                    easing: 'easeInOutQuart'
                },
                layout: {
                    padding: {
                        left: 20,
                        right: 20,
                        top: 0,
                        bottom: 0
                    }
                }
            }
        });

        var combinedCtx = document.getElementById('combinedChart').getContext('2d');
        var combinedChart = new Chart(combinedCtx, {
            type: 'line',
            data: {
                labels: <?php echo $months_json; ?>,
                datasets: [
                    {
                        label: 'Requests',
                        data: <?php echo $request_counts_json; ?>,
                        borderColor: 'rgba(78, 115, 223, 1)',
                        backgroundColor: 'rgba(78, 115, 223, 0.2)',
                        borderWidth: 2,
                        tension: 0.4
                    },
                    {
                        label: 'Borrows',
                        data: <?php echo $borrow_counts_json; ?>,
                        borderColor: 'rgba(28, 200, 138, 1)',
                        backgroundColor: 'rgba(28, 200, 138, 0.2)',
                        borderWidth: 2,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                size: 14,
                                family: "'Arial', sans-serif"
                            },
                            padding: 20
                        }
                    },
                    title: {
                        display: true,
                        text: 'Monthly Requests and Borrows',
                        font: {
                            size: 20,
                            family: "'Arial', sans-serif",
                            weight: 'bold'
                        },
                        padding: {
                            top: 10,
                            bottom: 30
                        }
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        },
                        padding: 12,
                        cornerRadius: 6,
                        displayColors: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            font: {
                                size: 12,
                                family: "'Arial', sans-serif"
                            },
                            padding: 10
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12,
                                family: "'Arial', sans-serif"
                            },
                            padding: 10
                        }
                    }
                },
                animation: {
                    duration: 1500,
                    easing: 'easeInOutQuart'
                },
                layout: {
                    padding: {
                        left: 20,
                        right: 20,
                        top: 0,
                        bottom: 0
                    }
                }
            }
        });
    </script>
</body>

</html>