<?php
require "include/config.php"; // Include the database configuration file
//
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
    $category = isset($_POST['category']) ? $_POST['category'] : ''; // Safely get the category
    $department = isset($_POST['department']) ? $_POST['department'] : ''; // Safely get the department
    $last_dates = isset($_POST['last_dates']) ? $_POST['last_dates'] : ''; // Safely get the last service date

    // Predict next service date based on category
    $next_date = '';
    if (!empty($last_dates) && !empty($category)) {
        $lastServiceDate = new DateTime($last_dates);
        if ($category === 'Printers') {
            $lastServiceDate->modify('+2 months'); // Add 2 months for printers
        } elseif ($category === 'AC') {
            $lastServiceDate->modify('+3 months'); // Add 3 months for ACs
        }
        $next_date = $lastServiceDate->format('Y-m-d'); // Format the predicted date
    }

    // Check if required fields are empty
    if (empty($assetName) || empty($department) || empty($last_dates) || empty($next_date)) {
        echo "<script>alert('Please fill in all required fields.');</script>";
    } else {
        // Ensure next_service is not null
        if (empty($next_date)) {
            $next_date = date('Y-m-d'); // Set a default value, e.g., today's date
        }

        // SQL query to insert the request into the database
        $insertSql = "INSERT INTO maintenance_table (reg_no, asset_name, description, category, department, last_service, next_service) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertSql); // Prepare the SQL statement
        $stmt->bind_param("sssssss", $regNo, $assetName, $description, $category, $department, $last_dates, $next_date); // Bind the form data to the query

        if ($stmt->execute()) { // Execute the query and check if it was successful
            echo "<script>alert('Request submitted successfully!');</script>"; // Show success message
        } else {
            echo "<script>alert('Error: " . $stmt->error . "');</script>"; // Show error message
        }
    }
}
?>

<div class="row"><!-- Begin of row for modal -->
    <!-- Column -->
    <div class="col-md-12 col-lg-6 col-xlg-6"> 
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal" data-whatever="@mdo">Schedule Maintenance</button> <!-- Button to open the modal -->
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
                                                <textarea class="form-control" id="description" name="description"></textarea> <!-- Textarea for description -->
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
                                                <label for="department" class="col-form-label">Department:</label> <!-- Label for department -->
                                                <select id="department" class="form-control" name="department"> <!-- Dropdown for department -->
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
                                     

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="reg-no" class="col-form-label">Registration Number:</label> <!-- Label for registration number -->
                                                <input type="text" class="form-control" id="reg-no" name="reg-no" readonly> <!-- Input for registration number (readonly) -->
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="last_dates" class="col-form-label">Last Service:</label> <!-- Label for date -->
                                                <input type="date" class="form-control" id="last_dates" name="last_dates"> <!-- Input for date -->
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="next_dates" class="col-form-label">Next Servicing:</label> <!-- Label for date -->
                                                <input type="date" class="form-control" id="next_dates" name="next_dates"> <!-- Input for date -->
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
    // Add an event listener to the asset name dropdown
/*     document.getElementById('asset-name').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex]; // Get the selected option
        const regNo = selectedOption.getAttribute('data-regno'); // Get the registration number from the selected option
        const category = selectedOption.getAttribute('data-category'); // Get the category from the selected option

        document.getElementById('reg-no').value = regNo; // Set the registration number in the input field
        document.getElementById('category').value = category; // Set the category in the input field
    }); */



    const assetNameInput = document.getElementById('asset-name');
    const suggestionsList = document.getElementById('asset-suggestions');

    assetNameInput.addEventListener('input', function() {
        const searchTerm = this.value.trim();
        console.log('Searching for:', searchTerm); // Debug log
        if (searchTerm.length > 0) {
            // Using absolute path from web root
            fetch('/asset_managment/profolder/requestfolder/search_asset.php?q=' + encodeURIComponent(searchTerm))
                .then(response => {
                    console.log('Response status:', response.status); // Debug log
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text().then(text => {
                        console.log('Raw response:', text); // Debug log
                        return JSON.parse(text);
                    });
                })
                .then(data => {
                    console.log('Parsed data:', data); // Debug log
                    suggestionsList.innerHTML = '';
                    suggestionsList.style.display = 'block';
                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(asset => {
                            const li = document.createElement('li');
                            li.textContent = asset.asset_name;
                            li.className = 'list-group-item list-group-item-action';
                            li.addEventListener('click', function() {
                                assetNameInput.value = asset.asset_name;
                                document.getElementById('reg-no').value = asset.reg_no;
                                document.getElementById('category').value = asset.category;
                                document.getElementById('description').value = asset.description;
                                suggestionsList.style.display = 'none';
                            });
                            suggestionsList.appendChild(li);
                        });
                    } else {
                        const li = document.createElement('li');
                        li.textContent = 'No assets found';
                        li.className = 'list-group-item';
                        suggestionsList.appendChild(li);
                    }
                })
                .catch(error => {
                    console.error('Error:', error); // Debug log
                    suggestionsList.innerHTML = '';
                    const li = document.createElement('li');
                    li.textContent = 'Error fetching assets. Please check console for details.';
                    li.className = 'list-group-item text-danger';
                    suggestionsList.appendChild(li);
                });
        } else {
            suggestionsList.style.display = 'none';
        }
    });

    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!assetNameInput.contains(e.target) && !suggestionsList.contains(e.target)) {
            suggestionsList.style.display = 'none';
        }
    });





    // Add an event listener to the last service date input field
    document.getElementById('last_dates').addEventListener('change', function() {
        const lastServiceDate = new Date(this.value); // Parse the selected last service date
        const category = document.getElementById('category').value; // Get the selected category
        const nextServiceInput = document.getElementById('next_dates'); // Get the next servicing date input field

        if (!isNaN(lastServiceDate.getTime())) { // Check if the date is valid
            switch (category) { // Determine the next service date based on the category
                case 'Printers':
                    lastServiceDate.setMonth(lastServiceDate.getMonth() + 2); // Add 2 months for printers
                    break;
                case 'AC':
                    lastServiceDate.setMonth(lastServiceDate.getMonth() + 3); // Add 3 months for ACs
                    break;
                default:
                    nextServiceInput.value = ''; // Clear the next servicing date if the category is unrecognized
                    alert('Unrecognized category. Please select a valid category.'); // Show an alert for unrecognized category
                    return;
            }
            nextServiceInput.value = lastServiceDate.toISOString().split('T')[0]; // Set the next servicing date in the input field
        } else {
            nextServiceInput.value = ''; // Clear the next servicing date if the input is invalid
            alert('Invalid last service date. Please select a valid date.'); // Show an alert for invalid date
        }
    });

    // Ensure the next servicing date is updated when the form is reloaded
    window.addEventListener('load', function() {
        const lastServiceDateInput = document.getElementById('last_dates'); // Get the last service date input field
        const categoryInput = document.getElementById('category'); // Get the category input field
        const nextServiceInput = document.getElementById('next_dates'); // Get the next servicing date input field

        if (lastServiceDateInput.value && categoryInput.value) { // Check if both last service date and category are set
            const lastServiceDate = new Date(lastServiceDateInput.value); // Parse the last service date
            if (!isNaN(lastServiceDate.getTime())) { // Check if the date is valid
                switch (categoryInput.value) { // Determine the next service date based on the category
                    case 'Printers':
                        lastServiceDate.setMonth(lastServiceDate.getMonth() + 2); // Add 2 months for printers
                        break;
                    case 'AC':
                        lastServiceDate.setMonth(lastServiceDate.getMonth() + 3); // Add 3 months for ACs
                        break;
                    default:
                        nextServiceInput.value = ''; // Clear the next servicing date if the category is unrecognized
                        return;
                }
                nextServiceInput.value = lastServiceDate.toISOString().split('T')[0]; // Set the next servicing date in the input field
            }
        }
    });
</script>

