<aside class="left-sidebar" data-sidebarbg="skin5">
    <div class="scroll-sidebar">
        <nav class="sidebar-nav">
            <div class="sidebar-header p-3 border-bottom">
                <h3 class="text-center mb-0" style="color: #2255a4; font-weight: 600;">Asset Management</h3>
            </div>
            <ul id="sidebarnav" class="pt-4">
                <li class="sidebar-item">
                    <a class="sidebar-link waves-effect" href="../index.php">
                        <i class="mdi mdi-view-dashboard text-info"></i>
                        <span class="hide-menu">Dashboard</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a class="sidebar-link waves-effect" href="../assets.php">
                        <i class="mdi mdi-database text-success"></i>
                        <span class="hide-menu">Asset</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a class="sidebar-link waves-effect" href="../categories.php">
                        <i class="mdi mdi-format-list-bulleted text-warning"></i>
                        <span class="hide-menu">Categories</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow waves-effect" href="javascript:void(0)">
                        <i class="mdi mdi-file-document text-danger"></i>
                        <span class="hide-menu">Request Module</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a href="../requestasset.php" class="sidebar-link">
                                <i class="mdi mdi-arrow-right-bold"></i>
                                <span class="hide-menu">Asset Request</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="../borrowasset.php" class="sidebar-link">
                                <i class="mdi mdi-arrow-right-bold"></i>
                                <span>Borrow Asset</span>
                            </a>
                        </li>


                        
                         <li class="sidebar-item">
                            <a href="../borrowdept.php" class="sidebar-link">
                                <i class="mdi mdi-arrow-right-bold"></i>
                                <span>Borrow || Dept Store</span>
                            </a>
                         </li>
                    </ul>
                </li>


                   <li class="sidebar-item">
                            <a href="../assethistory.php" class="sidebar-link">
                                <i class="mdi mdi-history"></i>
                                <span>Report</span>
                            </a>
                </li>

                <li class="sidebar-item">
                    <a class="sidebar-link waves-effect" href="maintenance.php">
                        <i class="mdi mdi-settings text-primary"></i>
                        <span class="hide-menu">Schedule Maintenance</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a class="sidebar-link waves-effect" href="../qrcode.php">
                        <i class="mdi mdi-qrcode text-dark"></i>
                        <span class="hide-menu">QR Code Generator</span>
                    </a>
                </li>

                 <li class="sidebar-item">
                    <a class="sidebar-link waves-effect" href="../qrcode.php">
                        <i class="mdi mdi-qrcode text-dark"></i>
                        <span class="hide-menu">Damaged Assets</span>
                    </a>
                </li>


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
                        <span class="hide-menu">Administrator</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a href="../newuser.php" class="sidebar-link">
                                <i class="mdi mdi-account-plus"></i>
                                <span class="hide-menu">Register</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="../department.php" class="sidebar-link">
                                <i class="mdi mdi-domain"></i>
                                <span class="hide-menu">Add Department</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="authentication-logout.php" class="sidebar-link">
                                <i class="mdi mdi-logout"></i>
                                <span class="hide-menu">Logout</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
    </div>
</aside>

<style>
.left-sidebar {
    background: #ffffff;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
}

.sidebar-nav ul {
    margin: 0;
    padding: 0;
}

.sidebar-nav .sidebar-item {
    margin-bottom: 5px;
}

.sidebar-nav .sidebar-link {
    padding: 12px 15px;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
    color: #67757c;
    border-radius: 4px;
    margin: 0 10px;
    font-size: 15px;
}

.sidebar-nav .sidebar-link:hover {
    background: #f8f9fa;
    color: #2255a4;
    text-decoration: none;
}

.sidebar-nav .sidebar-link i {
    font-size: 18px;
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

.sidebar-nav .has-arrow::after {
    content: '';
    border-right: 2px solid #67757c;
    border-bottom: 2px solid #67757c;
    width: 7px;
    height: 7px;
    transform: rotate(45deg);
    position: absolute;
    right: 15px;
    top: 17px;
    transition: all 0.3s ease;
}

.sidebar-nav .first-level {
    padding-left: 0;
    background: #f8f9fa;
    margin: 5px 10px;
    border-radius: 4px;
}

.sidebar-nav .first-level .sidebar-link {
    padding-left: 30px;
    font-size: 14px;
}

.sidebar-nav .first-level .sidebar-link i {
    font-size: 14px;
}

.sidebar-header {
    background: linear-gradient(to right, #ffffff, #f8f9fa);
}

/* Active state styling */
.sidebar-nav .sidebar-item.active .sidebar-link {
    color: #2255a4;
    background: #e8f0fe;
    font-weight: 500;
}
</style>