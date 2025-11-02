jQuery(document).ready(function ($) {

    // Add to Favorites / Remove from Favorites on the 'Inspiration' archive or single post page
    $('.inspiration-fav-btn').on('click', function () {
        var btn = $(this);
        var postID = btn.data('post-id');

        // Get heart icon and spinner elements inside the button
        var icon = btn.find('.icon-heart');
        var spinner = btn.find('.spinner');

        // Hide icon and show spinner while request is processing
        icon.hide();
        spinner.show();

        // Send AJAX request to toggle wishlist status
        $.post(InspirationWishlist.ajaxurl, {
            action: 'toggle_inspiration_wishlist',
            post_id: postID,
            nonce: InspirationWishlist.nonce
        }, function (response) {
            if (response.success) {
                // Update button appearance based on response
                if (response.data.status === 'added') {
                    btn.addClass('active');
                    icon.html('<i class="fas fa-heart"></i>');
                } else {
                    btn.removeClass('active');
                    icon.html('<i class="far fa-heart"></i>');
                }
            }
        }).always(function () {
            // Restore icon and hide spinner regardless of result
            spinner.hide();
            icon.show();
        });
    });

    // Remove item from wishlist on the wishlist page
    $('.wishlist-remove-btn').on('click', function () {
        var btn = $(this);
        var postID = btn.data('post-id');
        var card = btn.closest('.wishlist-card');

        // Get trash icon and spinner elements
        var icon = btn.find('.icon-trash');
        var spinner = btn.find('.spinner');

        // Hide trash icon and show spinner while removing
        icon.hide();
        spinner.show();

        // Send AJAX request to remove the item
        $.post(InspirationWishlist.ajaxurl, {
            action: 'toggle_inspiration_wishlist',
            post_id: postID,
            nonce: InspirationWishlist.nonce
        }, function (response) {
            if (response.success && response.data.status === 'removed') {
                // Slide up and remove the wishlist card
                card.slideUp(300, function () {
                    card.remove();

                    // If no cards left, show a message
                    if ($('.wishlist-card').length === 0) {
                        $('.inspiration-wishlist-grid').html('<p>No inspiration favorites yet.</p>');
                    }
                });
            } else {
                // If failed, restore the icon
                spinner.hide();
                icon.show();
            }
        });
    });

});
