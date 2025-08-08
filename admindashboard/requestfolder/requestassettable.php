<!-- /**
 * Asset Request Table Display
 * This file handles the display and management of asset requests including pagination
 * and status update functionality for HOD and Procurement approvals
 */ -->

<?php 
require_once dirname(__FILE__) . "/../include/config.php";
require_once dirname(__FILE__) . "/../../include/utils.php";

try {
    // Pagination setup
    $items_per_page = 7;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $items_per_page;

    // Calculate total number of records and pages for pagination
    $total_stmt = $conn->query("SELECT COUNT(*) FROM request_table");
    $total_items = $total_stmt->fetchColumn();
    $total_pages = ceil($total_items / $items_per_page);

    // Fetch paginated records ordered by procurement status first, then HOD status
    $sql = "SELECT * FROM request_table 
            ORDER BY pro_approved ASC, hod_approved ASC, request_date DESC 
            LIMIT :offset, :limit";
            
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);    $stmt->execute();
} catch (PDOException $e) {
    // Log the error and display a user-friendly message
    error_log("Database error: " . $e->getMessage());
    echo '<div class="alert alert-danger">An error occurred while fetching the data. Please try again later.</div>';
    exit;
}
?>
<!-- Include jQuery for AJAX functionality -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Table Section -->
<div class="row mt-5">
    <!-- Asset Request List Table Container -->
    <div class="col-md-12 col-lg-12 col-xlg-3">
        <!-- Responsive table wrapper for horizontal scrolling on small screens -->
        <div class="table-responsive">
            <!-- Main asset requests table with styling -->
            <table class="table shadow-lg table-striped table-bordered table-hover">
                <!-- Table header section -->
                <thead class="thead-dark">
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Reg No.</th>
                        <th scope="col">Asset Name</th>
                        <th scope="col">Request By</th>
                        <th scope="col">Request Date</th>
                        <th scope="col">HOD Status</th>
                        <th scope="col">Procurement Status</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <?php
                // Initialize counter for row numbering
                $s = $offset + 1;
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($rows && count($rows) > 0) {
                    foreach ($rows as $row) {
                        // Generate table rows with request details
                        // Each row shows ID, registration number, asset details, and approval status
                        echo "<tr>";
                        echo "<th scope='row'>" . $s++ . "</th>";
                        echo "<td>" . htmlspecialchars($row['reg_no']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['asset_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['assigned_employee']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['request_date']) . "</td>";

                        // HOD Status
                        echo "<td>";
                        if ($row['hod_approved'] == 0) {
                            echo "<button onclick='updateStatus(\"hod\", " . $row['id'] . ", this)' class='btn btn-warning'>Pending....</button>";
                        } else {
                            echo "<button class='btn btn-success' disabled>Approved</button>";
                        }
                        echo "</td>";

                        // Procurement Status
                        echo "<td>";
                        if ($row['pro_approved'] == 0) {
                            echo "<button onclick='updateStatus(\"pro\", " . $row['id'] . ", this)' class='btn btn-warning'>Pending....</button>";
                        } else {
                            echo "<button class='btn btn-success' disabled>Approved</button>";
                        }
                        echo "</td>";

                        echo "<td>
                                <a href='requestfolder/deleterequest.php?id=" . $row['id'] . "'><i class='fa fa-trash' style='color:red'></i></a>
                                <a href='requestfolder/viewrequest.php?id=" . $row['id'] . "'><i class='fa fa-eye'></i></a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    // Show message if no records
                    echo "<tr><td colspan='8' class='text-center'>No request record</td></tr>";
                }
                ?>
            </table>
        </div>

        <!-- Pagination Navigation -->
        <!-- Shows Previous/Next buttons and page numbers -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>

<script>
/**
 * Updates the approval status of an asset request
 * @param {string} type - The type of approval ('hod' or 'pro')
 * @param {number} id - The ID of the request to update
 * @param {HTMLElement} button - The button element that triggered the update
 */
function updateStatus(type, id, button) {
    // Check if button is already disabled or processing
    const $button = $(button);
    if ($button.prop('disabled') || $button.hasClass('disabled')) {
        return;
    }

    // Check if procurement is trying to approve before HOD
    if (type === 'pro') {
        const hodButton = $(button).closest('tr').find('td:nth-child(6) button');
        const hodStatus = hodButton.hasClass('btn-success') || hodButton.text() === 'Approved';
        if (!hodStatus) {
            showNotification('error', 'HOD must approve first before Procurement can take action');
            return;
        }
    }

    // Show loading state
    const originalText = $button.text();
    $button.prop('disabled', true).text('Processing...');

    let data = {};
    if (type === 'hod') {
        data.hod_approve_id = id;
    } else if (type === 'pro') {
        data.pro_approve_id = id;
    }    // Send AJAX request to update the status
    $.ajax({
        url: 'requestfolder/update_status.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Update the button appearance on successful status change
                $button.removeClass('btn-warning').addClass('btn-success')
                       .text('Approved')
                       .prop('disabled', true);

                // If HOD approved, enable procurement button if it exists
                if (type === 'hod') {
                    const $proButton = $button.closest('tr').find('td:nth-child(7) button');
                    if ($proButton.length && $proButton.hasClass('btn-warning')) {
                        $proButton.prop('disabled', false);
                    }
                }

                showNotification('success', response.message || 'Status updated successfully');
            } else {
                $button.prop('disabled', false).text(originalText);
                showNotification('error', response.message || 'Failed to update status');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', {xhr, status, error});
            $button.prop('disabled', false).text(originalText);
            
            let errorMessage = 'An error occurred while updating the status.';
            if (!navigator.onLine) {
                errorMessage = 'Please check your internet connection.';
            } else if (xhr.status === 404) {
                errorMessage = 'Update service not found.';
            } else if (xhr.status === 500) {
                errorMessage = 'Server error occurred. Please try again.';
            }
            
            showNotification('error', errorMessage);
        }
    });
}

/**
 * Shows a notification message to the user
 * @param {string} type - The type of notification ('success' or 'error')
 * @param {string} message - The message to display
 */
function showNotification(type, message) {
    // Create notification element
    const notification = $('<div>')
        .addClass('alert')
        .addClass(type === 'success' ? 'alert-success' : 'alert-danger')
        .text(message)
        .css({
            'position': 'fixed',
            'top': '20px',
            'right': '20px',
            'z-index': '9999',
            'display': 'none'
        });

    // Add to body, fade in, wait, then fade out and remove
    $('body').append(notification);
    notification.fadeIn(300).delay(3000).fadeOut(300, function() {
        $(this).remove();
    });
}
</script>