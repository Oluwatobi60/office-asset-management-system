<?php
require_once dirname(__FILE__) . "/../../include/utils.php";
require_once dirname(__FILE__) . "/../../include/config.php";

try {
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit-request'])) {
        // Safely get form values
        $assetName = filter_input(INPUT_POST, 'asset-name', FILTER_SANITIZE_STRING) ?? '';
        $regNo = filter_input(INPUT_POST, 'reg-no', FILTER_SANITIZE_STRING) ?? '';
        $purpose = filter_input(INPUT_POST, 'purpose', FILTER_SANITIZE_STRING) ?? '';
        $qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT) ?? 0;
        $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING) ?? '';
        $department = filter_input(INPUT_POST, 'department', FILTER_SANITIZE_STRING) ?? '';
        $assignedEmployee = filter_input(INPUT_POST, 'employee', FILTER_SANITIZE_STRING) ?? '';
        $date = filter_input(INPUT_POST, 'dates', FILTER_SANITIZE_STRING) ?? '';
        $requestedBy = $_SESSION['username'] ?? '';

        // Validate required fields
        if (empty($assetName) || empty($department) || empty($qty) || empty($date) || empty($purpose)) {
            throw new Exception('Please fill in all required fields.');
        }
        if (empty($assignedEmployee)) {
            throw new Exception('Please select an assigned employee.');
        }
        if (empty($requestedBy)) {
            throw new Exception('Borrow user is not set in the session.');
        }

        // Begin transaction
        $conn->beginTransaction();

        try {
            // Check asset availability
            $checkStmt = $conn->prepare("SELECT quantity FROM asset_table WHERE asset_name = :assetName FOR UPDATE");
            $checkStmt->bindParam(':assetName', $assetName, PDO::PARAM_STR);
            $checkStmt->execute();
            
            $row = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                throw new Exception('Asset not found.');
            }

            $availableQuantity = (int)$row['quantity'];
            if ($availableQuantity <= 0) {
                throw new Exception('Asset is not available.');
            }
            if ($qty > $availableQuantity) {
                throw new Exception("Asset quantity limit reached. Available quantity: $availableQuantity");
            }

            // Insert borrow request
            $adminBorrowFor = '';
            // If admin is borrowing for another staff, save the username of the selected employee
            if (isset($_POST['employee']) && !empty($_POST['employee']) && $requestedBy !== $_POST['employee']) {
                $adminBorrowFor = $_POST['employee'];
            }
            $insertSql = "INSERT INTO borrow_table (reg_no, asset_name, purpose, quantity, category, 
                         employee_name, department, borrow_date, borrow_by, admin_borrow_for) 
                         VALUES (:regNo, :assetName, :purpose, :qty, :category, 
                         :employee, :department, :date, :requestedBy, :adminBorrowFor)";
            
            $stmt = $conn->prepare($insertSql);
            $stmt->bindParam(':regNo', $regNo, PDO::PARAM_STR);
            $stmt->bindParam(':assetName', $assetName, PDO::PARAM_STR);
            $stmt->bindParam(':purpose', $purpose, PDO::PARAM_STR);
            $stmt->bindParam(':qty', $qty, PDO::PARAM_INT);
            $stmt->bindParam(':category', $category, PDO::PARAM_STR);
            $stmt->bindParam(':employee', $assignedEmployee, PDO::PARAM_STR);
            $stmt->bindParam(':department', $department, PDO::PARAM_STR);
            $stmt->bindParam(':date', $date, PDO::PARAM_STR);
            $stmt->bindParam(':requestedBy', $requestedBy, PDO::PARAM_STR);
            $stmt->bindParam(':adminBorrowFor', $adminBorrowFor, PDO::PARAM_STR);

            if (!$stmt->execute()) {
                throw new Exception('Failed to submit borrow request.');
            }

            // Update asset quantity
            $updateStmt = $conn->prepare("UPDATE asset_table SET quantity = quantity - :qty 
                                        WHERE asset_name = :assetName AND quantity >= :qty");
            $updateStmt->bindParam(':qty', $qty, PDO::PARAM_INT);
            $updateStmt->bindParam(':assetName', $assetName, PDO::PARAM_STR);
            
            if (!$updateStmt->execute() || $updateStmt->rowCount() === 0) {
                throw new Exception('Failed to update asset quantity.');
            }

            // Commit transaction
            $conn->commit();
            echo "<script>alert('Borrow submitted successfully and asset quantity updated!');</script>";

        } catch (Exception $e) {
            $conn->rollBack();
            throw new Exception('Transaction failed: ' . $e->getMessage());
        }
    }
} catch (Exception $e) {
    echo "<script>alert('" . htmlspecialchars($e->getMessage()) . "');</script>";
}
?>

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
                                            <div class="form-group">
                                                <label for="asset-name" class="col-form-label">Asset Name:</label> <!-- Label for asset name -->
                                                <input type="text" id="asset-name" class="form-control" name="asset-name" placeholder="Type to search assets" autocomplete="off"> <!-- Input for asset name -->
                                                <ul id="asset-suggestions" class="list-group" style="position: absolute; z-index: 1000; display: none;"></ul> <!-- Suggestions dropdown -->
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="description" class="col-form-label">Purpose:</label> <!-- Label for description -->
                                                <textarea class="form-control" id="description" name="purpose"></textarea> <!-- Textarea for description -->
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
                                                    <option selected disabled>Select Department</option>
                                                    <?php
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
                                                <label for="employee-select" class="col-form-label">Borrow by:</label>
                                                <select id="employee-select" class="form-control" name="employee" required>
                                                    <option value="" selected disabled>Select Employee</option>
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



<style>
.selected-row {
    background-color: #e3f2fd !important;
}
</style>

