<?php
require "../admindashboard/include/config.php"; // Include the database configuration file

// Handle AJAX request for asset suggestions and details
if (isset($_POST['action']) && $_POST['action'] === 'fetch_assets') {
    $searchTerm = $_POST['term'] ?? '';
    error_log("Search Term Received: $searchTerm"); // Debugging: Log the search term
    $sql = "SELECT asset_name, reg_no, category, description FROM asset_table WHERE asset_name LIKE ? LIMIT 10"; // Fetch matching assets
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

// Handle search request
if (isset($_POST['search-asset'])) { // Check if the search form is submitted
    $searchTerm = $_POST['search-term']; // Get the search term from the form
    $sql = "SELECT * FROM asset_table WHERE asset_name LIKE ?"; // SQL query to search assets
    $stmt = $conn->prepare($sql); // Prepare the SQL statement
    $searchTerm = "%$searchTerm%"; // Add wildcard characters for partial matching
    $stmt->bind_param("s", $searchTerm); // Bind the search term to the query
    $stmt->execute(); // Execute the query
    $result = $stmt->get_result(); // Get the result set
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit-request'])) { // Check if the request form is submitted
    $assetName = isset($_POST['asset-name']) ? $_POST['asset-name'] : ''; // Safely get the asset name
    $regNo = isset($_POST['reg-no']) ? $_POST['reg-no'] : ''; // Safely get the registration number
    $description = isset($_POST['description']) ? $_POST['description'] : ''; // Safely get the description
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
            } else {
                // SQL query to insert the request into the database
                $insertSql = "INSERT INTO request_table (reg_no, asset_name, description, quantity, category, department, assigned_employee, requested_by, request_date) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"; // Query to insert the request
                $stmt = $conn->prepare($insertSql); // Prepare the SQL statement
                $stmt->bind_param("sssisssss", $regNo, $assetName, $description, $qty, $category, $department, $assignedEmployee, $requestedBy, $date); // Bind the parameters

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
                                                <ul id="asset-suggestions" class="list-group" style="position: absolute; z-index: 1000; display: none;"></ul> <!-- Suggestions dropdown -->
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
    const assetNameInput = document.getElementById('asset-name');
    const suggestionsList = document.getElementById('asset-suggestions');

    assetNameInput.addEventListener('input', function () {
        const searchTerm = this.value.trim();
        console.log('Input Event Triggered. Search Term:', searchTerm); // Debugging: Log the search term
        if (searchTerm.length > 0) {
            fetch('requestasset.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'fetch_assets', term: searchTerm })
            })
            .then(response => {
                // Check if the response status is not in the 200-299 range
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}, StatusText: ${response.statusText}`);
                }
                // Attempt to parse the response as JSON
                return response.json().catch(() => {
                    throw new Error('Response is not valid JSON');
                });
            })
            .then(data => {
                console.log('Assets fetched:', data); // Debugging: Log fetched data
                suggestionsList.innerHTML = '';
                suggestionsList.style.display = 'block';
                if (data.length === 0) {
                    const li = document.createElement('li');
                    li.textContent = 'No assets found';
                    li.className = 'list-group-item';
                    suggestionsList.appendChild(li);
                } else {
                    data.forEach(asset => {
                        const li = document.createElement('li');
                        li.textContent = asset.asset_name;
                        li.className = 'list-group-item list-group-item-action';
                        li.addEventListener('click', function () {
                            assetNameInput.value = asset.asset_name;
                            document.getElementById('reg-no').value = asset.reg_no;
                            document.getElementById('category').value = asset.category;
                            document.getElementById('description').value = asset.description;
                            suggestionsList.style.display = 'none';
                        });
                        suggestionsList.appendChild(li);
                    });
                }
            })
            .catch(error => {
                console.error('Error fetching assets:', error); // Log the error
                // Optionally, display an error message to the user
                alert('Failed to fetch assets. Please try again later.');
            });
        } else {
            suggestionsList.style.display = 'none';
        }
    });

    document.addEventListener('click', function (e) {
        if (!assetNameInput.contains(e.target) && !suggestionsList.contains(e.target)) {
            suggestionsList.style.display = 'none';
        }
    });
</script>