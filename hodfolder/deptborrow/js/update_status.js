function updateStatus(type, id, status, button) {
    // Show loading state
    $(button).prop('disabled', true);
    $(button).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

    const data = {
        hod_approve_id: id,
        hod_status: status
    };

    $.ajax({
        url: '/asset_managment/hodfolder/deptborrow/update_status.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            console.log('Response:', response);
            if (response.success) {
                const btnGroup = $(button).closest('.btn-group');
                const returnCell = $(button).closest('tr').find('td').eq(7);

                if (status === 1) {
                    btnGroup.html("<button class='btn btn-success' disabled>Approved</button>");
                    returnCell.html("<button onclick='returnAsset(" + id + ", this)' class='btn btn-info'>Item Not Returned</button>");
                    alert('Request approved successfully');
                } else if (status === 2) {
                    btnGroup.html("<button class='btn btn-danger' disabled>Rejected</button>");
                    returnCell.html("<span class='badge badge-danger'>Request Rejected</span>");
                    alert('Request rejected');
                }

                // Refresh the page to show updated status
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                alert('Failed to update status: ' + (response.error || 'Please try again'));
                // Restore button state
                $(button).prop('disabled', false);
                $(button).html(status === 1 ? 'Pending' : 'Reject');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            console.error('Response:', xhr.responseText);
            alert('Error updating status. Please try again.');
            // Restore button state
            $(button).prop('disabled', false);
            $(button).html(status === 1 ? 'Pending' : 'Reject');
        }
    });
}
