function updateStatus(type, id, status, button) {
    let data = {};
    if (type === 'hod') {
        data.hod_approve_id = id;
        data.hod_status = status;
    }

    // Disable all buttons in the group
    $(button).closest('.btn-group').find('button').prop('disabled', true);
    
    // Show loading state on clicked button
    $(button).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

    $.ajax({
        url: '/asset_managment/hodfolder/deptborrow/update_status.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            console.log('Server response:', response);
            if (response.success) {
                let $btnGroup = $(button).closest('.btn-group');
                let $row = $(button).closest('tr');
                let $statusCell = $row.find('td:eq(5)'); // Status cell
                let $returnCell = $row.find('td:eq(7)'); // Return cell

                if (status === 1) {
                    $statusCell.html("<button class='btn btn-success' disabled>Approved</button>");
                    $returnCell.html("<button onclick='returnAsset(" + id + ", this)' class='btn btn-info'>Item Not Returned</button>");
                    alert('Request approved successfully!');
                } else if (status === 2) {
                    $statusCell.html("<button class='btn btn-danger' disabled>Rejected</button>");
                    $returnCell.html("<span class='badge badge-danger'>Request Rejected</span>");
                    alert('Request rejected successfully!');
                }
            } else {
                // Restore buttons on failure
                $(button).closest('.btn-group').find('button').prop('disabled', false);
                $(button).html(status === 1 ? 'Approve' : 'Reject');
                alert('Failed to update status: ' + (response.message || response.error || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            // Restore buttons on error
            $(button).closest('.btn-group').find('button').prop('disabled', false);
            $(button).html(status === 1 ? 'Approve' : 'Reject');
            console.error('AJAX Error:', xhr.responseText);
            alert('An error occurred while updating status. Please try again.');
        }
    });
}

function returnAsset(id, button) {
    if (!confirm('Are you sure you want to return this asset?')) {
        return;
    }

    $(button).prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

    $.ajax({
        url: '/asset_managment/hodfolder/deptborrow/return_asset.php',
        type: 'POST',
        data: { return_id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $(button).replaceWith("<span class='badge badge-success'>Returned</span>");
                alert('Asset returned successfully!');
                // Reload the page to update both tables
                location.reload();
            } else {
                $(button).prop('disabled', false).html('Return Asset');
                alert('Error returning asset: ' + (response.error || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            $(button).prop('disabled', false).html('Return Asset');
            console.error('AJAX Error:', xhr.responseText);
            alert('Failed to process return. Please try again.');
        }
    });
}
