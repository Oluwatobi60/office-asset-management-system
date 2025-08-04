<?php
require_once dirname(__FILE__) . "/../../include/config.php";
require_once dirname(__FILE__) . "/../../include/utils.php";

// Handle AJAX request for asset suggestions and details
if (isset($_GET['q'])) {
    header('Content-Type: application/json');
    $searchTerm = $_GET['q'];
    error_log("Search Term Received: $searchTerm");
    
    $sql = "SELECT asset_name, reg_no, category, description, CAST(quantity AS SIGNED) as quantity 
            FROM asset_table 
            WHERE LOWER(asset_name) LIKE LOWER(:search)
               OR LOWER(reg_no) LIKE LOWER(:search)
               OR LOWER(category) LIKE LOWER(:search)
            ORDER BY 
                CASE WHEN LOWER(asset_name) = LOWER(:exact) THEN 1
                     WHEN LOWER(reg_no) = LOWER(:exact) THEN 2
                     ELSE 3 
                END,
                asset_name 
            LIMIT 10";
    
    try {
        $stmt = $conn->prepare($sql);
        $searchPattern = "%" . $searchTerm . "%";
        $stmt->bindParam(':search', $searchPattern, PDO::PARAM_STR);
        $stmt->bindParam(':exact', $searchTerm, PDO::PARAM_STR);
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
        echo json_encode(array('error' => 'Database error'));
    }
    exit;
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit-request'])) {
    $assetNames = isset($_POST['asset-name']) ? $_POST['asset-name'] : [];
    $regNos = isset($_POST['reg-no']) ? $_POST['reg-no'] : [];
    $descriptions = isset($_POST['description']) ? $_POST['description'] : [];
    $qtys = isset($_POST['qty']) ? $_POST['qty'] : [];
    $categories = isset($_POST['category']) ? $_POST['category'] : [];
    $department = isset($_POST['department']) ? $_POST['department'] : '';
    $assignedEmployee = isset($_POST['employee']) ? $_POST['employee'] : '';
    $requestedBy = isset($_SESSION['username']) ? $_SESSION['username'] : '';
    $date = isset($_POST['dates']) ? $_POST['dates'] : '';

    // Basic validation
    if (empty($department) || empty($assignedEmployee) || empty($date) || empty($assetNames)) {
        echo "<script>alert('Please fill in all required fields and add at least one asset.');</script>";
        exit;
    }

    try {
        // Begin transaction
        $conn->beginTransaction();

        // Process each asset
        for ($i = 0; $i < count($assetNames); $i++) {
            $assetName = $assetNames[$i];
            $regNo = $regNos[$i];
            $description = $descriptions[$i];
            $qty = intval($qtys[$i]);
            $category = $categories[$i];

            if (empty($assetName) || empty($qty)) {
                throw new Exception("Missing required fields for asset #" . ($i + 1));
            }

            // Check available quantity with FOR UPDATE lock
            $sql = "SELECT quantity FROM asset_table WHERE asset_name = :asset FOR UPDATE";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':asset', $assetName, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                throw new Exception("Asset '$assetName' not found");
            }

            $availableQty = $result['quantity'];
            if ($availableQty < $qty) {
                throw new Exception("Insufficient quantity for '$assetName'. Available: $availableQty, Requested: $qty");
            }            // Insert request with timestamp
            $sql = "INSERT INTO staff_table (reg_no, asset_name, description, quantity, category, department, assigned_employee, requested_by, request_date) 
                    VALUES (:reg_no, :asset_name, :description, :quantity, :category, :department, :assigned_employee, :requested_by, :date)";
            $stmt = $conn->prepare($sql);
            
            // Convert date to datetime format
            $datetime = date('Y-m-d H:i:s', strtotime($date));
            
            $stmt->bindParam(':reg_no', $regNo, PDO::PARAM_STR);
            $stmt->bindParam(':asset_name', $assetName, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':quantity', $qty, PDO::PARAM_INT);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
            $stmt->bindParam(':department', $department, PDO::PARAM_STR);
            $stmt->bindParam(':assigned_employee', $assignedEmployee, PDO::PARAM_STR);
            $stmt->bindParam(':requested_by', $requestedBy, PDO::PARAM_STR);
            $stmt->bindParam(':date', $datetime, PDO::PARAM_STR);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert request for $assetName");
            }

            // Update asset quantity
            $sql = "UPDATE asset_table SET quantity = quantity - :qty WHERE asset_name = :asset_name";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':qty', $qty, PDO::PARAM_INT);
            $stmt->bindParam(':asset_name', $assetName, PDO::PARAM_STR);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update quantity for $assetName");
            }
        }

        // Commit transaction
        $conn->commit();
        echo "<script>alert('All assets allocated successfully!');</script>";

    } catch (Exception $e) {
        // Roll back the transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}
?>

<!-- Add styles for suggestions -->
<style>
.asset-suggestions {
    max-height: 200px;
    overflow-y: auto;
    width: 100%;
    border: 1px solid rgba(0,0,0,.125);
    border-radius: 4px;
    background: white;
}
.asset-suggestions .list-group-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
}
.asset-suggestions .list-group-item.disabled {
    background-color: #f8f9fa;
    cursor: not-allowed;
}
.asset-quantity {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 500;
    margin-left: 8px;
}
.quantity-available {
    background-color: #d4edda;
    color: #155724;
}
.quantity-empty {
    background-color: #f8d7da;
    color: #721c24;
}
</style>

<div class="row"><!-- Begin of row for modal -->
    <!-- Column -->
    <div class="col-md-12 col-lg-6 col-xlg-6"> 
        <div class=" m-4">
            <h3 class="text-primary">Staff Asset Allocation</h3> <!-- Title for the modal -->
            <p class="text-muted">Allocate assets to staff members</p> <!-- Subtitle for the modal -->
        <button type="button" class="btn btn-primary " data-toggle="modal" data-target="#exampleModal" data-whatever="@mdo">Staff Asset</button> <!-- Button to open the modal -->
        </div>
        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"> 
            <!-- Modal structure -->
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
                            <div id="assets-container">
                                <div class="asset-entry mb-4">
                                    <div class="row"><!--first row-->
                                         <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="asset-name-0" class="col-form-label">Asset Name:</label>
                                                        <input type="text" id="asset-name-0" class="form-control asset-name" name="asset-name[]" placeholder="Type to search assets" autocomplete="off">
                                                        <ul class="asset-suggestions list-group" style="position: absolute; z-index: 1000; display: none;"></ul>
                                                    </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="description" class="col-form-label">Description:</label> <!-- Label for description -->
                                                <textarea class="form-control" id="description-0" name="description[]" readonly></textarea> <!-- Textarea for description -->
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">                                                <label for="unit-0" class="col-form-label">Asset Quantity:</label> <!-- Label for quantity -->
                                                <input type="number" class="form-control quantity" id="unit-0" name="qty[]" min="1" placeholder="Enter quantity" disabled> <!-- Input for quantity -->
                                            </div>
                                        </div>
                                       
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="category-0" class="col-form-label">Category:</label> <!-- Label for category -->
                                                <input type="text" class="form-control categor" id="category-0" name="category[]" readonly> <!-- Input for category (readonly) -->
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="reg-no-0" class="col-form-label">Registration Number:</label>
                                                        <input type="text" class="form-control reg-no" id="reg-no-0" name="reg-no[]" readonly>
                                                    </div>
                                        </div>
                                       <!--   <div class="col-12">
                                                    <button type="button" class="btn btn-danger btn-sm remove-asset" style="display: none;">Remove Asset</button>
                                        </div> -->
                                    </div><!-- end of first row-->
                                </div><!-- End of first asset entry -->
                            </div><!-- End of assets container -->

                                     <div class="row mt-3">
                                       <!--  <div class="col-md-12 mb-3">
                                            <button type="button" class="btn btn-info" id="add-asset-btn">Add Another Asset</button>
                                        </div> -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="department-select" class="col-form-label">Department:</label> <!-- Label for department -->
                                                <select id="department-select" class="form-control" name="department"> <!-- Dropdown for department -->
                                                    <option selected disabled>Select Department</option> 
                                                    <?php                                     $sql = "SELECT department FROM department_table ORDER BY department";
                                                    $stmt = $conn->prepare($sql);
                                                    $stmt->execute();
                                                    while($row = $stmt->fetch()){
                                                        $dept = trim($row['department']);
                                                        echo "<option value='".htmlspecialchars($dept)."'>".htmlspecialchars($dept)."</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="employee-select" class="col-form-label">Assigned Employee:</label> <!-- Label for assigned employee -->
                                                <select id="employee-select" class="form-control" name="employee"> <!-- Dropdown for assigned employee -->
                                                    <option selected disabled>Select Employee</option>                                
                                                <?php                                       $sql_user = "SELECT * FROM user_table";
                                                    $stmt_user = $conn->prepare($sql_user);
                                                    $stmt_user->execute();
                                                    while($row = $stmt_user->fetch()){
                                                        echo "<option value='".htmlspecialchars($row['firstname']." ".$row['lastname'])."'>".htmlspecialchars($row['firstname']." ".$row['lastname'])."</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                       

                                         <div class="col-md-6">
                                            <div class="form-group">                                  <label for="dates" class="col-form-label">Request Date and Time:</label>
                                                <input type="datetime-local" class="form-control" id="dates" name="dates">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <button type="submit" class="btn btn-primary" name="submit-request">Submit Request</button>
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
        // Set current date and time by default
    document.addEventListener('DOMContentLoaded', function() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        
        const formattedDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
        document.getElementById('dates').value = formattedDateTime;
    });

    const assetNameInputs = document.querySelectorAll('.asset-name');
    const suggestionsLists = document.querySelectorAll('.asset-suggestions');
    const quantityInputs = document.querySelectorAll('.quantity');
    const submitButton = document.querySelector('button[name="submit-request"]');
    let availableQuantities = [];

    // Disable quantity inputs and submit button initially
    quantityInputs.forEach(input => input.disabled = true);
    submitButton.disabled = true;

    // Function to fetch asset suggestions
    async function fetchAssetSuggestions(searchTerm) {
        try {
            console.log('Fetching suggestions for:', searchTerm);
            const response = await fetch(`/asset_managment/admindashboard/staffallocation/search_asset.php?q=${encodeURIComponent(searchTerm)}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const text = await response.text();
            console.log('Raw response:', text);
            
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                console.log('Response that failed to parse:', text);
                return [];
            }
        } catch (error) {
            console.error('Error:', error);
            return [];
        }
    }

    // Function to check asset quantity
    async function checkAssetQuantity(assetName) {
        try {
            const response = await fetch(`/asset_managment/admindashboard/staffallocation/check_quantity.php?asset=${encodeURIComponent(assetName)}`);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const data = await response.json();
            return data.quantity || 0;
        } catch (error) {
            console.error('Error checking quantity:', error);
            return 0;
        }
    }

    // Function to update form based on quantity
    async function updateFormBasedOnQuantity(assetName, index) {
        const quantity = await checkAssetQuantity(assetName);
        availableQuantities[index] = parseInt(quantity);

        // Enable/disable quantity input based on availability
        quantityInputs[index].disabled = availableQuantities[index] <= 0;

        if (availableQuantities[index] <= 0) {
            quantityInputs[index].value = '';
            quantityInputs[index].placeholder = 'Asset out of stock';
            alert('This asset is currently out of stock!');
        } else {
            quantityInputs[index].max = availableQuantities[index];
            quantityInputs[index].placeholder = `Max available: ${availableQuantities[index]}`;
        }

        return availableQuantities[index];
    }

    // Function to handle asset suggestions for both initial and new forms
    function handleAssetSuggestion(assetNameInput, suggestionsList, quantityInput, assetIndex) {
        let debounceTimer;
        
        assetNameInput.addEventListener('input', async function() {
            const searchTerm = this.value.trim();
            // Reset quantity field when asset name changes
            quantityInput.value = '';
            quantityInput.disabled = true;
            
            // Clear previous timer
            clearTimeout(debounceTimer);
            
            if (searchTerm.length > 0) {
                // Debounce the search to avoid too many requests
                debounceTimer = setTimeout(async () => {
                    try {
                        const data = await fetchAssetSuggestions(searchTerm);
                        suggestionsList.innerHTML = '';
                        suggestionsList.style.display = 'block';
                        
                        if (Array.isArray(data) && data.length > 0) {
                            data.forEach(asset => {
                                const li = document.createElement('li');
                                const assetQuantity = parseInt(asset.quantity) || 0;
                                
                                li.innerHTML = `
                                    <div class="d-flex justify-content-between align-items-center w-100">
                                        <span>${asset.asset_name}</span>
                                        <span class="badge ${assetQuantity > 0 ? 'quantity-available' : 'quantity-empty'}">
                                            Available: ${assetQuantity}
                                        </span>
                                    </div>
                                `;
                                
                                if (assetQuantity > 0) {
                                    li.className = 'list-group-item list-group-item-action';
                                } else {
                                    li.className = 'list-group-item list-group-item-action disabled';
                                }
                                
                                if (assetQuantity > 0) {
                                    li.addEventListener('click', function() {
                                        assetNameInput.value = asset.asset_name;
                                        document.querySelector(`#reg-no-${assetIndex}`).value = asset.reg_no;
                                        document.querySelector(`#category-${assetIndex}`).value = asset.category;
                                        document.querySelector(`#description-${assetIndex}`).value = asset.description;
                                        
                                        quantityInput.disabled = false;
                                        quantityInput.min = 1;
                                        quantityInput.max = assetQuantity;
                                        quantityInput.value = '';
                                        quantityInput.placeholder = `Max available: ${assetQuantity}`;
                                        availableQuantities[assetIndex] = assetQuantity;
                                        
                                        updateSubmitButtonState();
                                        suggestionsList.style.display = 'none';
                                    });
                                }
                                
                                suggestionsList.appendChild(li);
                            });
                        } else {
                            const li = document.createElement('li');
                            li.textContent = 'No assets found';
                            li.className = 'list-group-item';
                            suggestionsList.appendChild(li);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        suggestionsList.innerHTML = '';
                        const li = document.createElement('li');
                        li.textContent = 'Error fetching assets. Please try again.';
                        li.className = 'list-group-item text-danger';
                        suggestionsList.appendChild(li);
                    }
                }, 300);
            } else {
                suggestionsList.style.display = 'none';
            }
        });
    }

    // Initial form setup
    assetNameInputs.forEach((input, index) => {
        handleAssetSuggestion(input, suggestionsLists[index], quantityInputs[index], index);
    });

    // Add quantity input validation
    quantityInputs.forEach((input, index) => {
        input.addEventListener('input', function() {
            const value = parseInt(this.value) || 0;
            
            if (value <= 0) {
                this.setCustomValidity('Quantity must be greater than 0');
                submitButton.disabled = true;
            } else if (value > availableQuantities[index]) {
                this.setCustomValidity(`Maximum available quantity is ${availableQuantities[index]}`);
                submitButton.disabled = true;
            } else {
                this.setCustomValidity('');
                submitButton.disabled = false;
            }
            this.reportValidity();
        });
    });

    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
        assetNameInputs.forEach((input, index) => {
            if (!input.contains(e.target) && !suggestionsLists[index].contains(e.target)) {
                suggestionsLists[index].style.display = 'none';
            }
        });
    });

    // Handle department selection and employee population
    document.getElementById('department-select').addEventListener('change', function() {
        const selectedDepartment = this.value;
        const employeeSelect = document.getElementById('employee-select');
        
        console.log('Selected department:', selectedDepartment);
        
        // Clear current options
        employeeSelect.innerHTML = '<option value="" selected disabled>Select Employee</option>';
        
        if (selectedDepartment) {
            // Show loading state
            employeeSelect.innerHTML = '<option value="" selected disabled>Loading employees...</option>';            // Fetch employees for the selected department
            fetch('/asset_managment/admindashboard/staffallocation/get_employees.php?department-select=' + encodeURIComponent(selectedDepartment))
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Received employees:', data);
                    
                    // Reset the select element
                    employeeSelect.innerHTML = '<option value="" selected disabled>Select Employee</option>';
                    
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    // Add employee options
                    data.forEach(employee => {
                        const option = document.createElement('option');
                        option.value = employee.name;
                        option.textContent = employee.name;
                        employeeSelect.appendChild(option);
                    });
                    
                    // Enable the employee select
                    employeeSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error fetching employees:', error);
                    employeeSelect.innerHTML = '<option value="" selected disabled>Error loading employees</option>';
                });
        } else {
            employeeSelect.disabled = true;
        }
    });

    // Add asset button functionality
    let assetIndex = 1; // Start index for assets
    document.getElementById('add-asset-btn').addEventListener('click', function() {
        const newAssetEntry = document.createElement('div');
        newAssetEntry.className = 'asset-entry mb-4';
        newAssetEntry.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="asset-name-${assetIndex}" class="col-form-label">Asset Name:</label>
                        <input type="text" id="asset-name-${assetIndex}" class="form-control asset-name" name="asset-name[]" placeholder="Type to search assets" autocomplete="off">
                        <ul class="asset-suggestions list-group" style="position: absolute; z-index: 1000; display: none;"></ul>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="description-${assetIndex}" class="col-form-label">Description:</label>
                        <textarea class="form-control description" id="description-${assetIndex}" name="description[]" readonly></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="unit-${assetIndex}" class="col-form-label">Asset Quantity:</label>
                        <input type="number" class="form-control quantity" id="unit-${assetIndex}" name="qty[]" min="1" placeholder="Enter quantity" disabled>
                    </div>
                </div>
               
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="category-${assetIndex}" class="col-form-label">Category:</label>
                        <input type="text" class="form-control category" id="category-${assetIndex}" name="category[]" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="reg-no-${assetIndex}" class="col-form-label">Registration Number:</label>
                        <input type="text" class="form-control reg-no" id="reg-no-${assetIndex}" name="reg-no[]" readonly>
                    </div>
                </div>
                <div class="col-12">
                    <button type="button" class="btn btn-danger btn-sm remove-asset">Remove Asset</button>
                </div>
            </div>
        `;

        // Add the new asset entry to the container
        document.getElementById('assets-container').appendChild(newAssetEntry);

        // Attach event listeners using the new function
        const newAssetNameInput = newAssetEntry.querySelector(`#asset-name-${assetIndex}`);
        const newSuggestionsList = newAssetEntry.querySelector('.asset-suggestions');
        const newQuantityInput = newAssetEntry.querySelector(`#unit-${assetIndex}`);

        handleAssetSuggestion(newAssetNameInput, newSuggestionsList, newQuantityInput, assetIndex);

        assetIndex++;
    });

    // Remove asset functionality
    document.getElementById('assets-container').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-asset')) {
            e.target.closest('.asset-entry').remove();
        }
    });    // Function to update submit button state
    function updateSubmitButtonState() {
        const allAssetEntries = document.querySelectorAll('.asset-entry');
        const departmentSelect = document.getElementById('department-select');
        const employeeSelect = document.getElementById('employee-select');
        const dateInput = document.getElementById('dates');
        let isValid = true;

        // Check if at least one asset entry exists and is valid
        if (allAssetEntries.length === 0) {
            isValid = false;
        } else {
            allAssetEntries.forEach((entry) => {
                const assetNameInput = entry.querySelector('.asset-name');
                const quantityInput = entry.querySelector('.quantity');
                const value = parseInt(quantityInput.value) || 0;
                
                if (!assetNameInput.value || !quantityInput.value || value <= 0 || quantityInput.validity.customError) {
                    isValid = false;
                }
            });
        }

        // Check other required fields
        isValid = isValid && departmentSelect.value && employeeSelect.value && dateInput.value;
        submitButton.disabled = !isValid;
    }

    // Add event listeners for form validation
    document.getElementById('assets-container').addEventListener('input', function(e) {
        if (e.target.classList.contains('asset-name') || 
            e.target.classList.contains('quantity')) {
            updateSubmitButtonState();
        }
    });

    // Monitor changes to select fields and date
    document.getElementById('department-select').addEventListener('change', updateSubmitButtonState);
    document.getElementById('employee-select').addEventListener('change', updateSubmitButtonState);
    document.getElementById('dates').addEventListener('change', updateSubmitButtonState);
    document.getElementById('dates').addEventListener('input', updateSubmitButtonState);

    // Add validation check when asset suggestions are selected
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('list-group-item-action')) {
            setTimeout(updateSubmitButtonState, 100); // Small delay to ensure values are updated
        }
    });
</script>

