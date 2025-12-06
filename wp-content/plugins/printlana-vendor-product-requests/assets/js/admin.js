jQuery(document).ready(function($) {
    // Approve request
    $('.pl-approve-btn').on('click', function() {
        const btn = $(this);
        const requestId = btn.data('request-id');

        if (!confirm('Are you sure you want to approve this request?')) {
            return;
        }

        btn.prop('disabled', true).text('Approving...');

        $.ajax({
            url: plProductRequests.ajaxurl,
            type: 'POST',
            data: {
                action: 'pl_approve_product_request',
                nonce: plProductRequests.nonce,
                request_id: requestId
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || 'Error approving request');
                    btn.prop('disabled', false).text('Approve');
                }
            },
            error: function() {
                alert('Error approving request');
                btn.prop('disabled', false).text('Approve');
            }
        });
    });

    // Reject request
    $('.pl-reject-btn').on('click', function() {
        const btn = $(this);
        const requestId = btn.data('request-id');

        if (!confirm('Are you sure you want to reject this request?')) {
            return;
        }

        btn.prop('disabled', true).text('Rejecting...');

        $.ajax({
            url: plProductRequests.ajaxurl,
            type: 'POST',
            data: {
                action: 'pl_reject_product_request',
                nonce: plProductRequests.nonce,
                request_id: requestId
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || 'Error rejecting request');
                    btn.prop('disabled', false).text('Reject');
                }
            },
            error: function() {
                alert('Error rejecting request');
                btn.prop('disabled', false).text('Reject');
            }
        });
    });
});
