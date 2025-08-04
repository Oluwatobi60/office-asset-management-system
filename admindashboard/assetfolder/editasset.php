<?php
require_once dirname(__FILE__) . "/../include/config.php";
require_once dirname(__FILE__) . "/../../include/utils.php";

try {
    // Validate and sanitize input
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception("Invalid asset ID");
    }
    
    $id = (int)$_GET['id'];

    if (isset($_POST['submit'])) {
        // Sanitize and validate inputs
        $asset_name = trim($_POST['asset_name']);
        $desc = trim($_POST['desc']);
        $qty = (int)$_POST['qty'];
        $asset_cat = isset($_POST['asset_cat']) ? trim($_POST['asset_cat']) : '';
        $date = trim($_POST['dates']);

        // Validate required fields
        if (empty($asset_name) || empty($desc) || empty($asset_cat) || empty($date)) {
            throw new Exception("All fields are required");
        }

        if ($qty < 0) {
            throw new Exception("Quantity cannot be negative");
        }

        // Update the asset using prepared statement
        $update_sql = "UPDATE asset_table 
                      SET asset_name = :asset_name, 
                          description = :description, 
                          quantity = :quantity, 
                          category = :category, 
                          dateofpurchase = :date 
                      WHERE id = :id";
                      
        $stmt = $conn->prepare($update_sql);
        $stmt->bindParam(':asset_name', $asset_name);
        $stmt->bindParam(':description', $desc);
        $stmt->bindParam(':quantity', $qty, PDO::PARAM_INT);
        $stmt->bindParam(':category', $asset_cat);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            logError("Asset updated successfully (ID: $id)");
            echo "<script>alert('Asset updated successfully'); window.location.href='../assets.php';</script>";
            exit();
        } else {
            throw new PDOException("Failed to update asset");
        }
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
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/logo.png">
    <title>Admin||Dashboard</title>
    <!-- Custom CSS -->
    <link href="../assets/libs/flot/css/float-chart.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../dist/css/style.min.css" rel="stylesheet">
 
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
                    <!-- This is for the sidebar toggle which is visible on mobile only -->
                    <a class="nav-toggler waves-effect waves-light d-block d-md-none" href="javascript:void(0)"><i class="ti-menu ti-close"></i></a>
                    <!-- ============================================================== -->
                    <!-- Logo -->
                    <!-- ============================================================== -->
                    <a class="navbar-brand" href="../index.php">
                        <!-- Logo icon -->
                        <b class="logo-icon p-l-10">
                            <!--You can put here icon as well // <i class="wi wi-sunset"></i> //-->
                            <!-- Dark Logo icon -->
                            <img src="../assets/images/logo.png" alt="homepage" class="light-logo" width="100px"/>
                           
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
                            <a class="nav-link dropdown-toggle text-muted waves-effect waves-dark pro-pic" href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><img src="assets/images/users/1.jpg" alt="user" class="rounded-circle" width="31"></a>
                            <div class="dropdown-menu dropdown-menu-right user-dd animated">
                                <a class="dropdown-item" href="javascript:void(0)"><i class="ti-user m-r-5 m-l-5"></i> My Profile</a>
                                <a class="dropdown-item" href="javascript:void(0)"><i class="ti-wallet m-r-5 m-l-5"></i> My Balance</a>
                                <a class="dropdown-item" href="javascript:void(0)"><i class="ti-email m-r-5 m-l-5"></i> Inbox</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="javascript:void(0)"><i class="ti-settings m-r-5 m-l-5"></i> Account Setting</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="javascript:void(0)"><i class="fa fa-power-off m-r-5 m-l-5"></i> Logout</a>
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
                        <h4 class="page-title">Update Asset</h4>
                        <div class="ml-auto text-right">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Library</li>
                                </ol>
                            </nav>
                        </div>
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
                <!-- ============================================================== -->
                <!-- Sales Cards  -->
                
                <!-- ============================================================== -->

                <!-- MODAL SECTION-->
                <?php 
                   /*  require "modal.php"; */
                ?>
                   <!--END OF MODAL SECTION-->                <!-- ============================================================== -->                
                <?php
                    // Fetch current asset data
                    $sql = "SELECT * FROM asset_table WHERE id = :id";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    if (!($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
                        logError("Asset not found: ID $id");
                        echo "<script>alert('Asset not found'); window.location.href='../assets.php';</script>";
                        exit();
                    }
                ?>

                <!-- START OF UPDATING -->
                 <form action="" method="POST" class="shadow p-4 mt-5 bg-white rounded">
                  
                 <div class="row bg-light rounded"><!-- start row-->
                    <div class="col-md-8 m-auto">
                        <div class="form-group shadow-sm">
                            <label for="" class="form-label">Registration No:</label>
                                <input type="text" id="" class="form-control" name="asset_model" value="<?php echo $row['reg_no']; ?>"  disabled>
                        </div>
                    </div>

                    <div class="col-md-8 m-auto">
                        <div class="form-group shadow-sm">
                            <label for="" class="form-label">Asset Name:</label>
                                <input type="text" id="" class="form-control" name="asset_name" value="<?php echo $row['asset_name']; ?>">
                        </div>
                    </div>

                    <div class="col-md-8 m-auto">
                        <div class="form-group shadow-sm">
                            <label for="" class="form-label">Asset Name:</label>
                                <textarea name="desc" id="" rows="3" class="form-control" ><?php echo $row['description']; ?></textarea>
                        </div>
                    </div>

                    <div class="col-md-8 m-auto">
                        <div class="form-group shadow-sm">
                            <label for="" class="form-label">Quantity:</label>
                                <input type="number" id="" class="form-control" name="qty" value="<?php echo $row['quantity']; ?>">
                        </div>
                    </div>

                    <div class="col-md-8 m-auto">
                        <div class="form-group shadow-sm">
                            <label for="category" class="col-form-label">Category:</label>
                                <select id="category" class="form-control" name="asset_cat">
                                    <option selected disabled><?php echo $row['category']; ?></option>                                        <?php
                                        try {
                                            $cat_sql = "SELECT * FROM category ORDER BY category ASC";
                                            $cat_stmt = $conn->prepare($cat_sql);
                                            $cat_stmt->execute();
                                            
                                            while($cat_row = $cat_stmt->fetch(PDO::FETCH_ASSOC)) {
                                                $category = htmlspecialchars($cat_row['category']);
                                                echo "<option value='" . $category . "'>" . $category . "</option>";
                                            }
                                        } catch (PDOException $e) {
                                            logError("Error fetching categories in editasset.php: " . $e->getMessage());
                                        }
                                        ?>
                                </select>
                                                               
                        </div>
                    </div>

                    <div class="col-md-8 m-auto">
                        <div class="form-group shadow-sm">
                            <label for="" class="form-label">Date:</label>
                                <input type="date" id="" class="form-control" name="dates" value="<?php echo $row['dateofpurchase']; ?>">
                        </div>
                    </div>                    <div class="col-md-8 m-auto">
                        <button type="submit" name="submit" class="btn btn-primary shadow">Update</button>
                        <a href="../assets.php" class="btn btn-secondary shadow">Cancel</a>
                    </div>
                 </div><!-- End row-->                 </form>                
                <?php
                } catch (Exception $e) {
                    logError("Error in editasset.php: " . $e->getMessage());
                    echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.location.href='../assets.php';</script>";
                    exit();
                }
                ?>
                <!-- END OF UPDATING -->
                 
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
            <?php require "../include/footer.php" ?>
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
    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap tether Core JavaScript -->
    <script src="../assets/libs/popper.js/dist/umd/popper.min.js"></script>
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../assets/extra-libs/sparkline/sparkline.js"></script>
    <!--Wave Effects -->
    <script src="../dist/js/waves.js"></script>
    <!--Menu sidebar -->
    <script src="../dist/js/sidebarmenu.js"></script>
    <!--Custom JavaScript -->
    <script src="../dist/js/custom.min.js"></script>
    <!--This page JavaScript -->
    <!-- <script src="dist/js/pages/dashboards/dashboard1.js"></script> -->
    <!-- Charts js Files -->
    <script src="../assets/libs/flot/excanvas.js"></script>
    <script src="../assets/libs/flot/jquery.flot.js"></script>
    <script src="../assets/libs/flot/jquery.flot.pie.js"></script>
    <script src="../assets/libs/flot/jquery.flot.time.js"></script>
    <script src="../assets/libs/flot/jquery.flot.stack.js"></script>
    <script src="../assets/libs/flot/jquery.flot.crosshair.js"></script>
    <script src="../assets/libs/flot.tooltip/js/jquery.flot.tooltip.min.js"></script>
    <script src="../dist/js/pages/chart/chart-page-init.js"></script>

</body>

</html>

