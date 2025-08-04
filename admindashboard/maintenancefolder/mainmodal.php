<?php
require_once dirname(__FILE__) . "/../include/config.php";

// Function to calculate predicted maintenance interval based on historical data
function calculateMaintenanceInterval($conn, $assetName, $category) {
    try {
        // Get historical maintenance data
        $sql = "SELECT 
                    m.last_service,
                    m.next_service,
                    COUNT(r.id) as usage_count,
                    AVG(DATEDIFF(m2.last_service, m.last_service)) as avg_interval
                FROM maintenance_table m
                LEFT JOIN request_table r ON r.asset_name = m.asset_name 
                    AND r.request_date BETWEEN m.last_service AND IFNULL(m.next_service, CURDATE())
                LEFT JOIN maintenance_table m2 ON m2.asset_name = m.asset_name 
                    AND m2.last_service > m.last_service
                WHERE m.asset_name = :asset_name AND m.category = :category
                GROUP BY m.id
                ORDER BY m.last_service DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':asset_name', $assetName, PDO::PARAM_STR);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->execute();
        
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($stats && $stats['avg_interval'] > 0) {
            $baseInterval = $stats['avg_interval'];
            $usageCount = $stats['usage_count'];
            
            // Adjust interval based on usage frequency
            if ($category === 'Printers') {
                if ($usageCount > 30) {
                    return round($baseInterval * 0.7); // Heavy usage - reduce by 30%
                } elseif ($usageCount > 10) {
                    return round($baseInterval * 0.85); // Medium usage - reduce by 15%
                }
                return round($baseInterval); // Light usage - keep standard interval
            }
            return round($baseInterval); // For non-printer assets
        }
        
        // Default intervals if no history exists
        $defaultIntervals = [
            'Printers' => 60,    // 2 months default
            'AC' => 90,          // 3 months
            'Computers' => 120,   // 4 months
            'Network Equipment' => 180 // 6 months
        ];
        
        // For printers, adjust default interval based on recent usage
        if ($category === 'Printers') {
            // Check recent usage (last 2 months)
            $usageSql = "SELECT COUNT(*) as recent_usage 
                        FROM request_table 
                        WHERE asset_name = :asset_name 
                        AND request_date >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH)";
            $usageStmt = $conn->prepare($usageSql);
            $usageStmt->bindParam(':asset_name', $assetName, PDO::PARAM_STR);
            $usageStmt->execute();
            $usage = $usageStmt->fetch(PDO::FETCH_ASSOC)['recent_usage'];
            
            if ($usage > 30) {
                return 45; // 1.5 months for heavy usage
            } elseif ($usage > 10) {
                return 52; // ~1.75 months for medium usage
            }
        }
        
        return $defaultIntervals[$category] ?? 90; // Default to 90 days if category not found
    } catch (PDOException $e) {
        error_log("Error in calculateMaintenanceInterval: " . $e->getMessage());
        return 90; // Default to 90 days on error
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit-request'])) {
    try {
        $assetName = $_POST['asset-name'] ?? '';
        $regNo = $_POST['reg-no'] ?? '';
        $description = $_POST['description'] ?? '';
        $category = $_POST['category'] ?? '';
        $department = $_POST['department'] ?? '';
        $last_dates = $_POST['last_dates'] ?? '';

        // Predict next service date
        $next_date = '';
        if (!empty($last_dates) && !empty($category)) {
            $lastServiceDate = new DateTime($last_dates);
            $interval = calculateMaintenanceInterval($conn, $assetName, $category);
            $lastServiceDate->add(new DateInterval("P{$interval}D"));
            $next_date = $lastServiceDate->format('Y-m-d');
        }

        // Validate required fields
        if (empty($assetName) || empty($department) || empty($last_dates) || empty($next_date)) {
            throw new Exception('Please fill in all required fields.');
        }

        // Set default next_service if empty
        if (empty($next_date)) {
            $next_date = date('Y-m-d');
        }

        // Insert into maintenance_table
        $insertSql = "INSERT INTO maintenance_table (reg_no, asset_name, description, category, department, last_service, next_service) 
                      VALUES (:reg_no, :asset_name, :description, :category, :department, :last_service, :next_service)";
        $stmt = $conn->prepare($insertSql);
        
        $stmt->bindParam(':reg_no', $regNo, PDO::PARAM_STR);
        $stmt->bindParam(':asset_name', $assetName, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->bindParam(':department', $department, PDO::PARAM_STR);
        $stmt->bindParam(':last_service', $last_dates, PDO::PARAM_STR);
        $stmt->bindParam(':next_service', $next_date, PDO::PARAM_STR);

        if ($stmt->execute()) {
            echo "<script>alert('Maintenance schedule created successfully!');</script>";
        } else {
            throw new Exception('Failed to create maintenance schedule.');
        }
    } catch (Exception $e) {
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
        error_log("Error in maintenance form submission: " . $e->getMessage());
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
                                                    try {
                                                        $sql = "SELECT department FROM department_table ORDER BY department";
                                                        $stmt = $conn->query($sql);
                                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                            $dept = htmlspecialchars(trim($row['department']));
                                                            echo "<option value='{$dept}'>{$dept}</option>";
                                                        }
                                                    } catch (PDOException $e) {
                                                        error_log("Error fetching departments: " . $e->getMessage());
                                                        echo "<option value=''>Error loading departments</option>";
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
                                                <label for="last_dates" class="col-form-label">Last Service Date:</label>
                                                <input type="date" class="form-control" id="last_dates" name="last_dates" required>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="next_dates" class="col-form-label">Next Service Date:</label>
                                                <input type="date" class="form-control" id="next_dates" name="next_date" readonly>
                                            </div>
                                        </div>

                                        <div class="col-md-12 text-center">
                                            <button type="submit" name="submit-request" class="btn btn-primary">Schedule Maintenance</button> <!-- Submit button -->
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
    
    assetNameInput.addEventListener('input', async function() {
        const searchTerm = this.value.trim();
        suggestionsList.innerHTML = '';
        
        if (searchTerm.length > 0) {
            try {                // Using absolute path to search_asset.php
                const response = await fetch(`maintenancefolder/search_asset.php?q=${encodeURIComponent(searchTerm)}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Response data:', data); // Debug log
                
                if (!data || (Array.isArray(data) && data.length === 0)) {
                    const li = document.createElement('li');
                    li.textContent = 'No assets found';
                    li.className = 'list-group-item';
                    suggestionsList.appendChild(li);
                    suggestionsList.style.display = 'block';
                    return;
                }
                
                if (data.status === 'error') {
                    throw new Error(data.message || 'An error occurred');
                }
                
                suggestionsList.style.display = 'block';
                
                data.forEach(asset => {
                    const li = document.createElement('li');
                    li.textContent = `${asset.asset_name} (${asset.reg_no})`;
                    li.className = 'list-group-item list-group-item-action';
                    
                    li.addEventListener('click', function() {
                        // Set values in the form
                        assetNameInput.value = asset.asset_name || '';
                        document.getElementById('reg-no').value = asset.reg_no || '';
                        document.getElementById('category').value = asset.category || '';
                        document.getElementById('description').value = asset.description || '';
                        
                        // Hide suggestions
                        suggestionsList.style.display = 'none';
                    });
                    
                    suggestionsList.appendChild(li);
                });
                
            } catch (error) {
                console.error('Error fetching assets:', error);
                suggestionsList.innerHTML = '';
                const li = document.createElement('li');
                li.textContent = 'Error loading suggestions';
                li.className = 'list-group-item text-danger';
                suggestionsList.appendChild(li);
                suggestionsList.style.display = 'block';
            }
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
                case 'Laptops':
                    lastServiceDate.setMonth(lastServiceDate.getMonth() + 4); // Add 4 months for laptops
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

    document.addEventListener('DOMContentLoaded', function() {
        const assetNameInput = document.getElementById('asset_names');
        const suggestionsList = document.getElementById('suggestions');
        const categoryInput = document.getElementById('category');
        const regNoInput = document.getElementById('reg_no');
        const lastDatesInput = document.getElementById('last_dates');
        const nextDatesInput = document.getElementById('next_dates');
        const descriptionInput = document.getElementById('description');

        assetNameInput.addEventListener('input', async function() {
            const searchTerm = this.value.trim();
            if (searchTerm.length < 2) {
                suggestionsList.innerHTML = '';
                return;
            }

            try {
                const response = await fetch('search_asset.php?search=' + encodeURIComponent(searchTerm));
                const data = await response.json();
                
                suggestionsList.innerHTML = '';
                data.forEach(asset => {
                    const li = document.createElement('li');
                    li.textContent = `${asset.asset_name} (${asset.reg_no})`;
                    li.classList.add('suggestion-item');
                    li.addEventListener('click', () => {
                        assetNameInput.value = asset.asset_name;
                        regNoInput.value = asset.reg_no;
                        categoryInput.value = asset.category;
                        descriptionInput.value = asset.description || '';
                        suggestionsList.innerHTML = '';
                    });
                    suggestionsList.appendChild(li);
                });
            } catch (error) {
                console.error('Error fetching suggestions:', error);
            }
        });

        // Calculate next service date based on last service date and category
        lastDatesInput.addEventListener('change', function() {
            const lastDate = new Date(this.value);
            const category = categoryInput.value.toLowerCase();
            let months = 3; // Default interval

            // Set maintenance interval based on asset category
            switch(category) {
                case 'machinery':
                case 'heavy equipment':
                    months = 6;
                    break;
                case 'vehicles':
                    months = 3;
                    break;
                case 'electronics':
                case 'computers':
                    months = 12;
                    break;
                case 'office equipment':
                    months = 9;
                    break;
            }

            // Calculate next service date
            const nextDate = new Date(lastDate);
            nextDate.setMonth(nextDate.getMonth() + months);
            
            // Format date as YYYY-MM-DD for input field
            const formattedDate = nextDate.toISOString().split('T')[0];
            nextDatesInput.value = formattedDate;
        });

        // Click outside suggestions to close
        document.addEventListener('click', function(e) {
            if (e.target !== assetNameInput) {
                suggestionsList.innerHTML = '';
            }
        });
    });
</script>

