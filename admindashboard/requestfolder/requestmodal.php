<?php
require_once dirname(__FILE__) . "/../include/config.php";
require_once dirname(__FILE__) . "/../../include/utils.php";

// Handle AJAX request for asset suggestions and details
if (isset($_GET['q'])) {
    header('Content-Type: application/json');
    $searchTerm = $_GET['q'];
    error_log("Search Term Received: $searchTerm");    $sql = "SELECT asset_name, reg_no, category, description, CAST(quantity AS SIGNED) as quantity 
            FROM asset_table 
            WHERE (
                LOWER(asset_name) LIKE LOWER(:search)
                OR LOWER(reg_no) LIKE LOWER(:search)
                OR LOWER(category) LIKE LOWER(:search)
            )
            AND quantity > 0
            ORDER BY 
                CASE 
                    WHEN LOWER(asset_name) = LOWER(:exact) THEN 1
                    WHEN LOWER(asset_name) LIKE LOWER(:startsWith) THEN 2
                    ELSE 3
                END,
                asset_name 
            LIMIT 10";
    
    try {        $stmt = $conn->prepare($sql);
        $searchPattern = "%" . $searchTerm . "%";
        $startsWithPattern = $searchTerm . "%";
        $stmt->bindParam(':search', $searchPattern, PDO::PARAM_STR);
        $stmt->bindParam(':exact', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':startsWith', $startsWithPattern, PDO::PARAM_STR);
        $stmt->execute();
        $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ensure quantity is treated as a number
        foreach ($assets as &$asset) {
            $asset['quantity'] = intval($asset['quantity']);
        }
        
        error_log("Found assets: " . json_encode($assets));
        echo json_encode($assets);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(array('error' => 'Database error'));    }
    exit;
}

try {
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit-request'])) { // Check if the request form is submitted
        $assetName = isset($_POST['asset-name']) ? $_POST['asset-name'] : ''; // Safely get the asset name
    $regNo = isset($_POST['reg-no']) ? $_POST['reg-no'] : ''; // Safely get the registration number
    $description = isset($_POST['description']) ? $_POST['description'] : ''; // Safely get the description
    $qty = isset($_POST['qty']) ? $_POST['qty'] : ''; // Safely get the quantity
    $category = isset($_POST['category']) ? $_POST['category'] : ''; // Safely get the category
    $department = isset($_POST['department']) ? $_POST['department'] : ''; // Safely get the department
    $assignedEmployee = isset($_POST['employee']) ? $_POST['employee'] : ''; // Get the assigned employee from the form
    $requestedBy = isset($_SESSION['username']) ? $_SESSION['username'] : ''; // Get the requesting user from the session
    $date = isset($_POST['dates']) ? $_POST['dates'] : ''; // Safely get the request date

    // Check if required fields are empty
    if (empty($assetName) || empty($department) || empty($qty) || empty($date)) {
        echo "<script>alert('Please fill in all required fields.');</script>";
    } elseif (empty($assignedEmployee)) {
        echo "<script>alert('Please select an assigned employee.');</script>";
    } elseif (empty($requestedBy)) {
        echo "<script>alert('Requesting user is not set in the session.');</script>";
    } else {
          // Check if the asset quantity is 0 or less
         try {
             $checkQuantitySql = "SELECT quantity FROM asset_table WHERE asset_name = :asset_name";
             $checkStmt = $conn->prepare($checkQuantitySql);
             $checkStmt->bindParam(':asset_name', $assetName, PDO::PARAM_STR);
             $checkStmt->execute();
             
             if ($row = $checkStmt->fetch(PDO::FETCH_ASSOC)) {
                $availableQuantity = $row['quantity'];
                if ($availableQuantity <= 0) {
                    echo "<script>alert('Asset is not available.');</script>";
                } elseif ($qty > $availableQuantity) {
                    echo "<script>alert('Asset quantity limit reached. Available quantity: $availableQuantity');</script>";
                } else {
                    try {
                        // Start transaction
                        $conn->beginTransaction();

                        // Insert request
                        $insertSql = "INSERT INTO request_table (reg_no, asset_name, description, quantity, category, department, assigned_employee, requested_by, request_date) 
                                    VALUES (:reg_no, :asset_name, :description, :quantity, :category, :department, :assigned_employee, :requested_by, :request_date)";
                        $stmt = $conn->prepare($insertSql);
                        
                        $stmt->bindParam(':reg_no', $regNo, PDO::PARAM_STR);
                        $stmt->bindParam(':asset_name', $assetName, PDO::PARAM_STR);
                        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
                        $stmt->bindParam(':quantity', $qty, PDO::PARAM_INT);
                        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
                        $stmt->bindParam(':department', $department, PDO::PARAM_STR);
                        $stmt->bindParam(':assigned_employee', $assignedEmployee, PDO::PARAM_STR);
                        $stmt->bindParam(':requested_by', $requestedBy, PDO::PARAM_STR);
                        $stmt->bindParam(':request_date', $date, PDO::PARAM_STR);

                        if ($stmt->execute()) {
                            // Update asset quantity
                            $updateAssetSql = "UPDATE asset_table SET quantity = quantity - :qty WHERE asset_name = :asset_name";
                            $updateStmt = $conn->prepare($updateAssetSql);
                            $updateStmt->bindParam(':qty', $qty, PDO::PARAM_INT);
                            $updateStmt->bindParam(':asset_name', $assetName, PDO::PARAM_STR);
                            
                            if ($updateStmt->execute()) {
                                $conn->commit();
                                echo "<script>alert('Request submitted successfully and asset quantity updated!');</script>";
                            } else {
                                throw new Exception("Failed to update asset quantity");
                            }
                        } else {
                            throw new Exception("Failed to submit request");
                        }
                    } catch (Exception $e) {
                        if ($conn->inTransaction()) {
                            $conn->rollBack();
                        }
                        logError("Error in request submission: " . $e->getMessage());
                        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
                    }
                }
            } else {
                echo "<script>alert('Asset not found.');</script>";
            }
        } catch (Exception $e) {
            logError("Error checking asset quantity: " . $e->getMessage());
            echo "<script>alert('Error checking asset availability: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
} // End of POST request handling

} catch (Exception $e) {
    logError("Critical error in requestmodal.php: " . $e->getMessage());
    echo "<script>alert('A system error occurred. Please try again later.');</script>";
}
?>

<div class="row"><!-- Begin of row for modal -->
    <!-- Column -->
    <div class="col-md-12 col-lg-6 col-xlg-6"> 
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal" data-whatever="@mdo">Request for Asset</button> <!-- Button to open the modal -->
        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"> <!-- Modal structure -->
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Asset Information</h5> <!-- Modal title -->
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <!-- Close button -->
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs" id="myTab" role="tablist"> <!-- Tab navigation -->
                            <li class="nav-item">
                                <a class="nav-link active" id="basic-info-tab" data-toggle="tab" href="#basic-info" role="tab" aria-controls="basic-info" aria-selected="true">Basic Info</a> <!-- Tab for basic info -->
                            </li>
                        </ul>
                        <div class="tab-content" id="myTabContent"> <!-- Tab content -->
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="basic-info-tab">
                                <form action="" method="POST"> <!-- Form for submitting asset request -->
                                    <div class="row">
                                    <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="asset-name" class="col-form-label">Asset Name:</label> <!-- Label for asset name -->
                                                <input type="text" id="asset-name" class="form-control" name="asset-name" placeholder="Type to search assets" autocomplete="off"> <!-- Input for asset name -->
                                                <ul id="asset-suggestions" class="list-group shadow-sm" style="position: absolute; z-index: 1000; display: none; width: 100%; max-height: 300px; overflow-y: auto;"></ul> <!-- Suggestions dropdown with improved styling -->
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="description" class="col-form-label">Description:</label> <!-- Label for description -->
                                                <textarea class="form-control" id="description" name="description" readonly></textarea> <!-- Textarea for description -->
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="unit" class="col-form-label">Asset Quantity:</label> <!-- Label for quantity -->
                                                <input type="number" class="form-control" id="unit" name="qty"> <!-- Input for quantity -->
                                            </div>
                                        </div>
                                       
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="category" class="col-form-label">Category:</label> <!-- Label for category -->
                                                <input type="text" class="form-control" id="category" name="category" readonly> <!-- Input for category (readonly) -->
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="department-select" class="col-form-label">Department:</label> <!-- Label for department -->
                                                <select id="department-select" class="form-control" name="department"> <!-- Dropdown for department -->
                                                    <option selected disabled>Select Department</option>                                <?php
                                                    try {
                                                        $sql = "SELECT department FROM department_table ORDER BY department";
                                                        $stmt = $conn->prepare($sql);
                                                        $stmt->execute();
                                                        
                                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                            $dept = trim($row['department']);
                                                            echo "<option value='" . htmlspecialchars($dept, ENT_QUOTES) . "'>" . 
                                                                 htmlspecialchars($dept, ENT_QUOTES) . "</option>";
                                                        }
                                                    } catch (Exception $e) {
                                                        logError("Error fetching departments: " . $e->getMessage());
                                                        echo "<option value=''>Error loading departments</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="employee-select" class="col-form-label">Assigned Employee:</label> <!-- Label for assigned employee -->
                                                <select id="employee-select" class="form-control" name="employee"> <!-- Dropdown for assigned employee -->
                                                    <option selected disabled>Select Employee</option>                                                    <?php
                                                    try {
                                                        $sql_user = "SELECT firstname, lastname FROM user_table ORDER BY firstname, lastname";
                                                        $stmt_user = $conn->prepare($sql_user);
                                                        $stmt_user->execute();
                                                        
                                                        while ($row = $stmt_user->fetch(PDO::FETCH_ASSOC)) {
                                                            $fullName = htmlspecialchars($row['firstname'] . " " . $row['lastname'], ENT_QUOTES);
                                                            echo "<option value='" . $fullName . "'>" . $fullName . "</option>";
                                                        }
                                                    } catch (Exception $e) {
                                                        logError("Error fetching users: " . $e->getMessage());
                                                        echo "<option value=''>Error loading users</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="reg-no" class="col-form-label">Registration Number:</label> <!-- Label for registration number -->
                                                <input type="text" class="form-control" id="reg-no" name="reg-no" readonly> <!-- Input for registration number (readonly) -->
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="dates" class="col-form-label">Request Date:</label> <!-- Label for date -->
                                                <input type="date" class="form-control" id="dates" name="dates"> <!-- Input for date -->
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <button type="submit" class="btn btn-primary" name="submit-request">Submit Request</button> <!-- Submit button -->
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button> 
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                   
                </div>
            </div>
        </div>
    </div><!-- End of column -->
</div><!-- End of row for modal -->

<script>
    // Get references to DOM elements that we'll be working with
    const assetNameInput = document.getElementById('asset-name');        // Input field for asset name search
    const suggestionsList = document.getElementById('asset-suggestions'); // Dropdown list for search suggestions
    const quantityInput = document.getElementById('unit');               // Input field for quantity
    const submitButton = document.querySelector('button[name="submit-request"]'); // Submit button for the form
    let availableQuantity = 0;  // Track the currently selected asset's available quantity    // Initially disable the quantity input and submit button until an asset is selected
    quantityInput.disabled = true;     // Prevent quantity input until asset is selected
    submitButton.disabled = true;      // Prevent form submission until valid quantity is entered

    /**
     * Reset all form fields to their default state
     * This is called when the asset search input changes or when clearing the form
     */
    function resetFormFields() {
        document.getElementById('reg-no').value = '';          // Clear registration number field
        document.getElementById('category').value = '';        // Clear category field
        document.getElementById('description').value = '';     // Clear description field
        quantityInput.value = '';                             // Clear quantity input
        quantityInput.disabled = true;                        // Disable quantity input
        submitButton.disabled = true;                         // Disable submit button
        quantityInput.placeholder = 'Select an asset first';  // Set helpful placeholder text
    }

    // Function to update form fields with asset data
    function updateFormFields(asset) {
        document.getElementById('reg-no').value = asset.reg_no || '';
        document.getElementById('category').value = asset.category || '';
        document.getElementById('description').value = asset.description || '';
        
        const assetQuantity = parseInt(asset.quantity) || 0;
        availableQuantity = assetQuantity;
        
        // Update quantity input
        quantityInput.value = '';
        quantityInput.disabled = assetQuantity <= 0;
        quantityInput.min = 1;
        quantityInput.max = assetQuantity;
        
        if (assetQuantity <= 0) {
            quantityInput.placeholder = 'Asset out of stock';
            submitButton.disabled = true;
            alert('This asset is currently out of stock!');
        } else {
            quantityInput.placeholder = `Enter quantity (max: ${assetQuantity})`;
            quantityInput.disabled = false;
            submitButton.disabled = false;
        }
    }

    // Asset name input handler with debouncing
    assetNameInput.addEventListener('input', function() {
        const searchTerm = this.value.trim();
        resetFormFields();
        
        if (searchTerm.length > 0) {
            // Clear previous timer
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }
            
            // Show loading state
            suggestionsList.innerHTML = '<li class="list-group-item">Loading...</li>';
            suggestionsList.style.display = 'block';
            
            // Add debouncing to prevent too many requests
            this.debounceTimer = setTimeout(() => {               
                 fetch(`/asset_management/admindashboard/requestfolder/search_asset.php?q=${encodeURIComponent(searchTerm)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Received data:', data);
                        suggestionsList.innerHTML = '';
                        
                        if (Array.isArray(data) && data.length > 0) {
                            data.forEach(asset => {
                                const li = document.createElement('li');
                                const quantity = parseInt(asset.quantity) || 0;
                                const status = quantity > 0 ? 'text-success' : 'text-danger';
                                
                                li.innerHTML = `
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span><strong>${asset.asset_name}</strong></span>
                                        <span class="${status}">${quantity} available</span>
                                    </div>
                                    <small class="text-muted">${asset.category || 'No category'}</small>
                                `;
                                
                                li.className = 'list-group-item list-group-item-action';
                                li.style.cursor = quantity > 0 ? 'pointer' : 'not-allowed';
                                
                                if (quantity > 0) {
                                    li.addEventListener('click', function() {
                                        assetNameInput.value = asset.asset_name;
                                        updateFormFields(asset);
                                        suggestionsList.style.display = 'none';
                                    });
                                }
                                
                                suggestionsList.appendChild(li);
                            });
                            suggestionsList.style.display = 'block';
                        } else {
                            suggestionsList.innerHTML = '<li class="list-group-item text-muted">No assets found</li>';
                            suggestionsList.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        suggestionsList.innerHTML = '<li class="list-group-item text-danger">Error fetching assets. Please try again.</li>';
                        suggestionsList.style.display = 'block';
                    });
            }, 300); // 300ms debounce delay
        } else {
            suggestionsList.style.display = 'none';
        }
    });

    // Add quantity input validation
    quantityInput.addEventListener('input', function() {
        const value = parseInt(this.value) || 0;
        
        if (value <= 0) {
            this.setCustomValidity('Quantity must be greater than 0');
            submitButton.disabled = true;
        } else if (value > availableQuantity) {
            this.setCustomValidity(`Maximum available quantity is ${availableQuantity}`);
            submitButton.disabled = true;
        } else {
            this.setCustomValidity('');
            submitButton.disabled = false;
        }
        this.reportValidity();
    });

    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!assetNameInput.contains(e.target) && !suggestionsList.contains(e.target)) {
            suggestionsList.style.display = 'none';
        }
    });

    // Event listener for department selection
    document.getElementById('department-select').addEventListener('change', function() {
        const selectedDepartment = this.value;
        const employeeSelect = document.getElementById('employee-select');
        
        // Show loading state
        employeeSelect.disabled = true;
        employeeSelect.innerHTML = '<option>Loading employees...</option>';
        
        // Fetch employees for selected department
        fetch(`/asset_management/admindashboard/requestfolder/get_department_employees.php?department=${encodeURIComponent(selectedDepartment)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(employees => {
                // Reset and populate employee dropdown
                employeeSelect.innerHTML = '<option selected disabled>Select Employee</option>';
                
                if (Array.isArray(employees) && employees.length > 0) {
                    employees.forEach(employee => {
                        const option = document.createElement('option');
                        option.value = employee.fullname;
                        option.textContent = employee.fullname;
                        employeeSelect.appendChild(option);
                    });
                } else {
                    employeeSelect.innerHTML = '<option disabled selected>No employees found in this department</option>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                employeeSelect.innerHTML = '<option disabled selected>Error loading employees</option>';
            })
            .finally(() => {
                employeeSelect.disabled = false;
            });
    });
</script>

