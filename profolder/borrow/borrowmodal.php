<?php
require "../admindashboard/include/config.php"; // Include the database configuration file

// Handle AJAX request for asset suggestions and details
if (isset($_GET['q'])) {
    $searchTerm = $_GET['q'];
    error_log("Search Term Received: $searchTerm"); // Debugging: Log the search term
    $sql = "SELECT asset_name, reg_no, category, description, quantity FROM asset_table WHERE asset_name LIKE ? LIMIT 10"; // Fetch matching assets including quantity
    $stmt = $conn->prepare($sql);
    $searchTerm = "%$searchTerm%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $assets = [];
    while ($row = $result->fetch_assoc()) {
        $assets[] = $row; // Add asset details to the array
    }
    error_log("Assets Found: " . json_encode($assets)); // Debugging: Log the assets found
    echo json_encode($assets); // Return results as JSON
    exit;
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit-request'])) { // Check if the request form is submitted
    $assetName = isset($_POST['asset-name']) ? $_POST['asset-name'] : ''; // Safely get the asset name
    $regNo = isset($_POST['reg-no']) ? $_POST['reg-no'] : ''; // Safely get the registration number
    $purpose = isset($_POST['purpose']) ? $_POST['purpose'] : ''; // Safely get the purpose
    $qty = isset($_POST['qty']) ? $_POST['qty'] : ''; // Safely get the quantity
    $category = isset($_POST['category']) ? $_POST['category'] : ''; // Safely get the category from the form submission
    $requestedBy = isset($_SESSION['username']) ? $_SESSION['username'] : ''; // Get the requesting user from the session
    $date = isset($_POST['dates']) ? $_POST['dates'] : ''; // Safely get the request date from the form submission

    // Fetch department and assigned employee based on the logged-in user
    $department = ''; // Initialize the department variable
    $assignedEmployee = ''; // Initialize the assigned employee variable
    if (!empty($requestedBy)) { // Check if the requesting user is set
        $userSql = "SELECT department, CONCAT(firstname, ' ', lastname) AS full_name FROM user_table WHERE username = ?"; // Query to fetch department and full name of the user
        $userStmt = $conn->prepare($userSql); // Prepare the SQL statement
        $userStmt->bind_param("s", $requestedBy); // Bind the username to the query
        $userStmt->execute(); // Execute the query
        $userResult = $userStmt->get_result(); // Get the result set
        if ($userResult->num_rows > 0) { // Check if any result is returned
            $userRow = $userResult->fetch_assoc(); // Fetch the result as an associative array
            $department = $userRow['department']; // Assign the department
            $assignedEmployee = $userRow['full_name']; // Assign the full name of the user
        }
    }

    // Check if required fields are empty
    if (empty($assetName) || empty($qty) || empty($date)) { // Validate required fields
        echo "<script>alert('Please fill in all required fields.');</script>"; // Alert if required fields are missing
    } elseif (empty($requestedBy)) { // Check if the requesting user is set
        echo "<script>alert('Requesting user is not set in the session.');</script>"; // Alert if the requesting user is not set
    } else {
        // Check if the asset quantity is 0 or less
        $checkQuantitySql = "SELECT quantity FROM asset_table WHERE asset_name = ?"; // Query to check the available quantity of the asset
        $checkStmt = $conn->prepare($checkQuantitySql); // Prepare the SQL statement
        $checkStmt->bind_param("s", $assetName); // Bind the asset name to the query
        $checkStmt->execute(); // Execute the query
        $checkResult = $checkStmt->get_result(); // Get the result set
        if ($checkResult->num_rows > 0) { // Check if the asset exists
            $row = $checkResult->fetch_assoc(); // Fetch the result as an associative array
            $availableQuantity = $row['quantity']; // Get the available quantity of the asset
            if ($availableQuantity <= 0) { // Check if the asset is unavailable
                echo "<script>alert('Asset is not available.');</script>"; // Alert if the asset is unavailable
            } elseif ($qty > $availableQuantity) { // Check if the requested quantity exceeds the available quantity
                echo "<script>alert('Asset quantity limit reached. Available quantity: $availableQuantity');</script>"; // Alert if the quantity limit is exceeded
            } else {                // SQL query to insert the request into the database
                $insertSql = "INSERT INTO borrow_table (reg_no, asset_name, purpose, quantity, category, employee_name, department, borrow_date, borrow_by) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"; // Query to insert the request
                $stmt = $conn->prepare($insertSql); // Prepare the SQL statement
                $stmt->bind_param("sssisssss", $regNo, $assetName, $purpose, $qty, $category, $assignedEmployee, $department, $date, $requestedBy); // Bind the parameters

                if ($stmt->execute()) { // Execute the query
                    // Deduct the requested quantity from the asset_table
                    $updateAssetSql = "UPDATE asset_table SET quantity = quantity - ? WHERE asset_name = ?"; // Query to update the asset quantity
                    $updateStmt = $conn->prepare($updateAssetSql); // Prepare the SQL statement
                    $updateStmt->bind_param("is", $qty, $assetName); // Bind the parameters
                    if ($updateStmt->execute()) { // Execute the query
                        echo "<script>alert('Request submitted successfully and asset quantity updated!');</script>"; // Alert if the request is successful
                    } else {
                        echo "<script>alert('Request submitted, but failed to update asset quantity: " . $updateStmt->error . "');</script>"; // Alert if the quantity update fails
                    }
                } else {
                    echo "<script>alert('Error: " . $stmt->error . "');</script>"; // Alert if the request submission fails
                }
            }
        } else {
            echo "<script>alert('Asset not found.');</script>"; // Alert if the asset is not found
        }
    }
}
?>

<style>
    #asset-suggestions {
        position: absolute;
        width: 100%;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1050;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    #asset-suggestions .list-group-item {
        cursor: pointer;
    }
    
    #asset-suggestions .list-group-item:hover {
        background-color: #f8f9fa;
    }
    
    .form-group {
        position: relative;
    }
</style>

<div class="row"><!-- Begin of row for modal -->
    <!-- Column -->
    <div class="col-md-12 col-lg-6 col-xlg-6"> 
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal" data-whatever="@mdo">Borrow Asset</button> <!-- Button to open the modal -->
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
                                            <div class="form-group">                                                <label for="asset-name" class="col-form-label">Asset Name:</label> <!-- Label for asset name -->
                                                <input type="text" id="asset-name" class="form-control" name="asset-name" placeholder="Type to search assets" autocomplete="off" required> <!-- Input for asset name -->
                                                <ul id="asset-suggestions" class="list-group" style="display: none;"></ul> <!-- Suggestions dropdown -->
                                            </div>
                                        </div>
                                        
                                         <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="description" class="col-form-label">Purpose:</label> <!-- Label for description -->
                                                <textarea class="form-control" id="description" name="purpose"></textarea> <!-- Textarea for description -->
                                            </div>
                                        </div>
                                        <div class="col-md-6">                                            <div class="form-group">
                                                <label for="unit" class="col-form-label">Asset Quantity:</label> <!-- Label for quantity -->
                                                <input type="number" class="form-control" id="unit" name="qty" min="1" required disabled placeholder="Select an asset first"> <!-- Input for quantity -->
                                            </div>
                                        </div>
                                       
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="category" class="col-form-label">Category:</label> <!-- Label for category -->
                                                <input type="text" class="form-control" id="category" name="category" readonly> <!-- Input for category (readonly) -->
                                            </div>
                                        </div>
                                        <div class="col-md-6" style="display: none;"> <!-- Hide the department field -->
                                            <div class="form-group">
                                                <label for="department" class="col-form-label">Department:</label>
                                                <select id="department" class="form-control" name="department">
                                                    <option selected disabled>Select Department</option>
                                                    <?php
                                                    $sql = "SELECT * FROM department_table"; // Query to fetch departments
                                                    $result = $conn->query($sql); // Execute the query
                                                    if($result->num_rows > 0){ // Check if there are results
                                                        while($row = $result->fetch_assoc()){ // Loop through the results
                                                            echo "<option value='".$row['department']."'>".$row['department']."</option>"; // Populate the dropdown
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6" style="display: none;"> <!-- Hide the assigned employee field -->
                                            <div class="form-group">
                                                <label for="employee" class="col-form-label">Assigned Employee:</label>
                                                <select id="employee" class="form-control" name="employee">
                                                    <option selected disabled>Select Employee</option>
                                                    <?php
                                                    $sql_user = "SELECT * FROM user_table"; // Query to fetch users
                                                    $result_user = $conn->query($sql_user); // Execute the query
                                                    if($result_user->num_rows > 0){ // Check if there are results
                                                        while($row = $result_user->fetch_assoc()){ // Loop through the results
                                                            echo "<option value='".$row['firstname']." ".$row['lastname']."'>".$row['firstname']." ".$row['lastname']."</option>"; // Populate the dropdown
                                                        }
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
                                                <label for="dates" class="col-form-label">Borrow Date:</label> <!-- Label for date -->
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
        //document.getElementById('description').value = asset.description || '';
        
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
                 fetch(`/asset_management/admindashboard/borrow/search_asset.php?q=${encodeURIComponent(searchTerm)}`)
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
    });    // Event listener for department selection
    document.getElementById('department-select').addEventListener('change', function() {
        const selectedDepartment = this.value;
        const employeeSelect = document.getElementById('employee-select');
        
        console.log('Selected department:', selectedDepartment); // Debug log
        
        if (!selectedDepartment) {
            employeeSelect.innerHTML = '<option value="" selected disabled>Select Department First</option>';
            employeeSelect.disabled = true;
            return;
        }
        
        // Show loading state
        employeeSelect.disabled = true;        employeeSelect.innerHTML = '<option>Loading employees...</option>';
        
        // Fetch employees for selected department
        fetch(`/asset_management/admindashboard/borrow/get_employees.php?department-select=${encodeURIComponent(selectedDepartment)}`)
            .then(response => {
                console.log('Response status:', response.status); // Debug log
                console.log('Response URL:', response.url); // Log the full URL
                return response.text().then(text => {
                    try {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        const data = JSON.parse(text);
                        console.log('Raw response:', text); // Log the raw response
                        return data;
                    } catch (e) {
                        console.error('Error parsing response:', text);
                        throw new Error('Failed to parse server response');
                    }
                });
            })
            .then(data => {
                console.log('Received employee data:', data); // Debug log
                
                // Check for error in response
                if (data.error) {
                    throw new Error(data.error);
                }

                // Reset dropdown
                employeeSelect.innerHTML = '<option selected disabled>Select Employee</option>';
                
                // Handle the employees data
                if (Array.isArray(data) && data.length > 0) {
                    data.forEach(employee => {
                        const option = document.createElement('option');
                        option.value = employee.name;
                        option.textContent = employee.name;
                        employeeSelect.appendChild(option);
                    });
                    employeeSelect.disabled = false;
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

