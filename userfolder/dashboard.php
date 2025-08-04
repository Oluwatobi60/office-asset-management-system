<?php  
session_start(); // Start the session to manage user sessions

// Set session timeout to 20 minutes
$timeout_duration = 1200; // 20 minutes in seconds

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    // If the session has been inactive for too long, destroy it
    session_unset();
    session_destroy();
    header("Location: index.php"); // Redirect to login page
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time(); // Update last activity timestamp

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php"); // Redirect to login page if not logged in
    exit();
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
    <link rel="icon" type="image/png" sizes="16x16" href="../admindashboard/assets/images/logo.png">
    <title>User||Dashboard</title>
    <!-- Custom CSS -->
    <link href="../admindashboard/assets/libs/flot/css/float-chart.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../admindashboard/dist/css/style.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
 
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

    <div id="main-wrapper">
        <!-- ============================================================== -->
        <!-- Topbar header - style you can find in pages.scss -->
        <!-- ============================================================== -->
        <?php require "header.php"; ?>
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
                    <div class="col-12 d-flex no-block align-items-center mt-3">
                        <h4 class="page-title">User Dashboard</h4>
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
                <div class="dashboard-header">
                    <h1 class="page-title">User Dashboard</h1>
                    <div class="welcome-message" style="color: #666; font-size: 1.1em; margin-top: -15px; margin-bottom: 25px;">
                        Welcome, <span style="color: #4e73df; font-weight: 600;"><?php echo htmlspecialchars($user_first_name . ' ' . $user_last_name); ?></span>!
                    </div>
                </div>

                <div class="row"><!-- Begin of row for navbar-->
                   
                    <!-- Column -->
                    <div class="col-md-6 col-lg-6 col-xlg-3">
                        <div class="card card-hover shadow">
                            <div class="box bg-success text-center">
                                <?php
                                require "../admindashboard/include/config.php";
                                $username = $_SESSION['username'];



                           


                                // Count pending requests - using hod_approved and pro_approved columns
                                $sql = "SELECT COUNT(*) as pending_count FROM request_table WHERE assigned_employee = :username AND (hod_approved = 0 OR pro_approved = 0)";
                                $stmt = $conn->prepare($sql);
                                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                                $stmt->execute();
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                $pending_count = $row['pending_count'];

                                // Count pending borrows - using hod_status and pro_status columns
                                $sql_borrow = "SELECT COUNT(*) as pending_borrow FROM borrow_table WHERE employee_name = :username AND (hod_status = 0 OR pro_status = 0)";
                                $stmt_borrow = $conn->prepare($sql_borrow);
                                $stmt_borrow->bindParam(':username', $username, PDO::PARAM_STR);
                                $stmt_borrow->execute();
                                $row_borrow = $stmt_borrow->fetch(PDO::FETCH_ASSOC);
                                $pending_borrow = $row_borrow['pending_borrow'];
                                ?>
                                <h1 class="font-light text-white">
                                <i class="fas fa-exclamation-circle text-white"></i>
                                </h1>
                                <h6 class="text-white">Pending Requests (<?php echo $pending_count; ?>)</h6>
                            </div>
                        </div>
                    </div>
                  
                   
                    <!-- Column -->
                    <div class="col-md-6 col-lg-6 col-xlg-3">
                        <div class="card card-hover shadow">
                            <div class="box bg-info text-center">
                                <h1 class="font-light text-white"><i class="fas fa-exclamation-circle text-white"></i></h1>
                                <h6 class="text-white">Pending Borrows (<?php echo $pending_borrow; ?>)</h6>
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
                        <div class="card shadow-lg">
                            <div class="card-body">
                                <h4 class="card-title mb-4">My Asset Requests and Borrows Overview</h4>
                                <?php
                                // Fetch counts for both requested and borrowed assets by category for the logged-in user
                                $sql_combined = "SELECT 
                                    c.category,
                                    COUNT(DISTINCT CASE WHEN r.assigned_employee = :username1 THEN r.id END) as request_count,
                                    COUNT(DISTINCT CASE WHEN b.employee_name = :username2 THEN b.id END) as borrow_count
                                FROM category c
                                LEFT JOIN request_table r ON r.category = c.category
                                LEFT JOIN borrow_table b ON b.category = c.category
                                WHERE r.assigned_employee = :username3 OR b.employee_name = :username4
                                GROUP BY c.category
                                ORDER BY c.category";
                                
                                $stmt_combined = $conn->prepare($sql_combined);
                                $username = $_SESSION['username'];
                                $stmt_combined->bindParam(':username1', $username, PDO::PARAM_STR);
                                $stmt_combined->bindParam(':username2', $username, PDO::PARAM_STR);
                                $stmt_combined->bindParam(':username3', $username, PDO::PARAM_STR);
                                $stmt_combined->bindParam(':username4', $username, PDO::PARAM_STR);
                                $stmt_combined->execute();
                                $result_combined = $stmt_combined;
                                
                                $categories = [];
                                $request_counts = [];
                                $borrow_counts = [];
                                
                                while($row = $stmt_combined->fetch(PDO::FETCH_ASSOC)) {
                                    $categories[] = $row['category'];
                                    $request_counts[] = (int)$row['request_count'];
                                    $borrow_counts[] = (int)$row['borrow_count'];
                                }
                                
                                // Convert to JSON for JavaScript
                                $categories_json = json_encode($categories);
                                $request_counts_json = json_encode($request_counts);
                                $borrow_counts_json = json_encode($borrow_counts);
                                ?>
                                <div class="chart-container shadow-sm" style="position: relative; height:50vh; width:100%; padding: 20px;">
                                    <canvas id="assetBarChart"></canvas>
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
        var ctx = document.getElementById('assetBarChart').getContext('2d');
        var categories = <?php echo $categories_json; ?>;
        var requestCounts = <?php echo $request_counts_json; ?>;
        var borrowCounts = <?php echo $borrow_counts_json; ?>;
        
        // Create gradient for Requested Assets
        var requestGradient = ctx.createLinearGradient(0, 0, 0, 400);
        requestGradient.addColorStop(0, 'rgba(54, 162, 235, 0.8)');
        requestGradient.addColorStop(1, 'rgba(54, 162, 235, 0.2)');
        
        // Create gradient for Borrowed Assets
        var borrowGradient = ctx.createLinearGradient(0, 0, 0, 400);
        borrowGradient.addColorStop(0, 'rgba(255, 99, 132, 0.8)');
        borrowGradient.addColorStop(1, 'rgba(255, 99, 132, 0.2)');
        
        var assetBarChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: categories,
                datasets: [
                    {
                        label: 'Requested Assets',
                        data: requestCounts,
                        backgroundColor: requestGradient,
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        borderRadius: 5,
                        barPercentage: 0.8,
                        categoryPercentage: 0.9
                    },
                    {
                        label: 'Borrowed Assets',
                        data: borrowCounts,
                        backgroundColor: borrowGradient,
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 2,
                        borderRadius: 5,
                        barPercentage: 0.8,
                        categoryPercentage: 0.9
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false,
                            color: 'rgba(200, 200, 200, 0.2)'
                        },
                        ticks: {
                            font: {
                                size: 12,
                                weight: 'bold'
                            },
                            padding: 10
                        },
                        title: {
                            display: true,
                            text: 'Number of Assets',
                            font: {
                                size: 14,
                                weight: 'bold'
                            },
                            padding: 20
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12,
                                weight: 'bold'
                            },
                            padding: 10
                        },
                        title: {
                            display: true,
                            text: 'Asset Categories',
                            font: {
                                size: 14,
                                weight: 'bold'
                            },
                            padding: 20
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 13,
                                weight: 'bold'
                            },
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'rectRounded'
                        }
                    },
                    title: {
                        display: true,
                        text: 'Asset Requests and Borrows Distribution',
                        font: {
                            size: 18,
                            weight: 'bold'
                        },
                        padding: {
                            top: 10,
                            bottom: 30
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#333',
                        bodyColor: '#666',
                        bodyFont: {
                            size: 13
                        },
                        borderColor: '#ddd',
                        borderWidth: 1,
                        padding: 15,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y;
                                return label;
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