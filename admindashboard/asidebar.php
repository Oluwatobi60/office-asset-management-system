<!-- Modern Left Sidebar -->
<aside class="left-sidebar" data-sidebarbg="skin5">
    <!-- Sidebar scroll-->
    <div class="scroll-sidebar">
        <!-- Brand Logo and Name -->
        <div class="brand-container p-3 mb-4 text-center">
            <h4 class="text-white mb-0">Asset Management</h4>
        </div>
        
        <!-- Sidebar navigation-->
        <nav class="sidebar-nav">
            <ul id="sidebarnav" class="sidebar-menu">
                <li class="sidebar-item"> 
                    <a class="sidebar-link waves-effect" href="index.php">
                        <i class="mdi mdi-view-dashboard text-info"></i>
                        <span class="menu-title">Dashboard</span>
                    </a>
                </li>
                
                <li class="sidebar-item"> 
                    <a class="sidebar-link waves-effect" href="assets.php">
                        <i class="mdi mdi-database text-success"></i>
                        <span class="menu-title">Asset</span>
                    </a>
                </li>
                
                <li class="sidebar-item"> 
                    <a class="sidebar-link waves-effect" href="categories.php">
                        <i class="mdi mdi-format-list-bulleted text-warning"></i>
                        <span class="menu-title">Categories</span>
                    </a>
                </li>

                <li class="sidebar-item"> 
                    <a class="sidebar-link has-arrow waves-effect" href="javascript:void(0)">
                        <i class="mdi mdi-file-document text-danger"></i>
                        <span class="menu-title">Request Module</span>
                    </a>
                    <ul class="collapse submenu">
                        <li class="sidebar-item">
                            <a href="requestasset.php" class="sidebar-link">
                                <i class="mdi mdi-arrow-right-bold"></i>
                                <span>Asset Request</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="borrowasset.php" class="sidebar-link">
                                <i class="mdi mdi-arrow-right-bold"></i>
                                <span>Borrow Asset</span>
                            </a>
                        </li>

                         <li class="sidebar-item">
                            <a href="borrowdept.php" class="sidebar-link">
                                <i class="mdi mdi-arrow-right-bold"></i>
                                <span>Borrow || Dept Store</span>
                            </a>
                         </li>
                    </ul>
                </li>

                   <li class="sidebar-item">
                            <a href="assethistory.php" class="sidebar-link">
                                <i class="mdi mdi-history"></i>
                                <span>Report</span>
                            </a>
                    </li>

                <li class="sidebar-item"> 
                    <a class="sidebar-link waves-effect" href="maintenance.php">
                        <i class="mdi mdi-settings text-primary"></i>
                        <span class="menu-title">Schedule Maintenance</span>
                    </a>
                </li>

                <li class="sidebar-item"> 
                    <a class="sidebar-link waves-effect" href="qrcode.php">
                        <i class="mdi mdi-qrcode text-primary"></i>
                        <span class="menu-title">QR Code Generator</span>
                    </a>
                </li>

                <!--  <li class="sidebar-item"> 
                    <a class="sidebar-link waves-effect" href="damaged_assets.php">
                        <i class="mdi mdi-alert-circle text-danger"></i>
                        <span class="menu-title">Damaged Assets</span>
                    </a>
                </li> -->

                  <li class="sidebar-item"> 
                    <a class="sidebar-link has-arrow waves-effect" href="javascript:void(0)">
                        <i class="mdi mdi-account-key text-info"></i>
                        <span class="menu-title">Staffs Module</span>
                    </a>
                    <ul class="collapse submenu">
                        <li class="sidebar-item">
                            <a href="staffallocation.php" class="sidebar-link">
                                <i class="mdi mdi-database text-success"></i>
                                <span>Allocated Assets</span>
                            </a>
                        </li>
                      
                     
                    </ul>
                </li>

                <li class="sidebar-item"> 
                    <a class="sidebar-link has-arrow waves-effect" href="javascript:void(0)">
                        <i class="mdi mdi-account-key text-info"></i>
                        <span class="menu-title">Administrator</span>
                    </a>
                    <ul class="collapse submenu">
                        <li class="sidebar-item">
                            <a href="newuser.php" class="sidebar-link">
                                <i class="mdi mdi-account-plus"></i>
                                <span>Register</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="department.php" class="sidebar-link">
                                <i class="mdi mdi-domain"></i>
                                <span>Add Department</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="logout.php" class="sidebar-link">
                                <i class="mdi mdi-logout"></i>
                                <span>Logout</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
        <!-- End Sidebar navigation -->
    </div>
    <!-- End Sidebar scroll-->
</aside>

<style>
/* Modern Sidebar Styling */
.left-sidebar {
    background: #2c3e50;
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.brand-container {
    background: rgba(255,255,255,0.1);
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-menu {
    padding: 15px 0;
}

.sidebar-item {
    margin: 5px 15px;
    border-radius: 8px;
    overflow: hidden;
}

.sidebar-link {
    padding: 12px 15px;
    color: #ecf0f1 !important;
    transition: all 0.3s ease;
    border-radius: 8px;
    display: flex;
    align-items: center;
    text-decoration: none;
}

.sidebar-link:hover {
    background: rgba(255,255,255,0.1);
    color: #3498db !important;
}

.menu-icon {
    margin-right: 10px;
    font-size: 18px;
}

.menu-title {
    font-size: 14px;
    font-weight: 500;
}

.submenu {
    padding-left: 30px;
    background: rgba(0,0,0,0.1);
    border-left: 3px solid #3498db;
    margin: 5px 0;
}

.submenu .sidebar-link {
    padding: 8px 15px;
    font-size: 13px;
}

.has-arrow::after {
    content: '\f142';
    font-family: 'Material Design Icons';
    font-size: 18px;
    position: absolute;
    right: 15px;
    transition: transform 0.3s ease;
}

.collapse.show + .has-arrow::after {
    transform: rotate(90deg);
}

/* Active state styling */
.sidebar-item.active .sidebar-link {
    background: #3498db;
    color: white !important;
}

/* Hover effects */
.sidebar-link:hover .menu-icon,
.sidebar-link:hover .menu-title {
    transform: translateX(5px);
    transition: transform 0.3s ease;
}
</style>