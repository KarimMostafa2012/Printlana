jQuery(document).ready(function ($) {
    // 1. Highlighted Elements Preview & Modal
    var $modal = $('#homeland-modal');
    var currentElementIndex = null;

    // Disable all links in admin preview
    $('.homeland-admin-preview').on('click', 'a', function (e) {
        e.preventDefault();
        e.stopPropagation();
    });

    $('.homeland-admin-preview').on('click', '.h-card, .h-small-card', function (e) {
        e.preventDefault();

        var allCards = $('.homeland-admin-preview').find('.h-card, .h-small-card');
        currentElementIndex = allCards.index(this) + 1;

        $('#homeland-element-index').text(currentElementIndex);

        // Load data from hidden fields
        var img = $('#h_img_' + currentElementIndex).val();
        var text = $('#h_text_' + currentElementIndex).val();
        var link = $('#h_link_' + currentElementIndex).val();

        $('#modal-field-text').val(text);
        $('#modal-field-link').val(link);
        if (img) {
            $('#modal-preview-img').attr('src', img).show();
        } else {
            $('#modal-preview-img').hide();
        }

        $modal.fadeIn();
    });

    $('.homeland-modal-close').click(function () {
        $modal.fadeOut();
    });

    $(window).click(function (e) {
        if ($(e.target).is($modal)) $modal.fadeOut();
    });

    // Reset Elements
    $('.homeland-reset-btn').click(function (e) {
        e.preventDefault();
        if (confirm('Are you sure you want to reset all highlighted elements to defaults?')) {
            $.post(homeland_admin.ajax_url, {
                action: 'homeland_reset_highlights',
                nonce: homeland_admin.nonce
            }, function (response) {
                if (response.success) {
                    location.reload();
                }
            });
        }
    });

    // Modal Image Upload
    $('.homeland-modal-upload-btn').click(function (e) {
        e.preventDefault();
        var custom_uploader = wp.media({
            title: 'Select Image',
            button: { text: 'Use Image' },
            multiple: false
        }).on('select', function () {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            $('#modal-preview-img').attr('src', attachment.url).show();
            $('#h_img_' + currentElementIndex).val(attachment.url);
        }).open();
    });

    // Update hidden fields when modal fields change
    $('#modal-field-text').on('input', function () {
        $('#h_text_' + currentElementIndex).val($(this).val());
    });
    $('#modal-field-link').on('input', function () {
        $('#h_link_' + currentElementIndex).val($(this).val());
    });

    // 2. Carousel Bulk Add
    $('.homeland-bulk-add').click(function (e) {
        e.preventDefault();
        var bulk_uploader = wp.media({
            title: 'Select Images to Add as Slides',
            button: { text: 'Add to Carousel' },
            multiple: true
        }).on('select', function () {
            var selection = bulk_uploader.state().get('selection');
            var ids = selection.map(function (attachment) {
                return attachment.id;
            });

            if (ids.length > 0) {
                var $btn = $('.homeland-bulk-add');
                $btn.prop('disabled', true).text('Adding...');
                $.post(homeland_admin.ajax_url, {
                    action: 'homeland_bulk_add',
                    nonce: homeland_admin.nonce,
                    image_ids: ids
                }, function (response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                        $btn.prop('disabled', false).text('Bulk Add Slides');
                    }
                });
            }
        }).open();
    });

    // 3. Copy Shortcode Utility
    $(document).on('click', '.homeland-copy-btn', function (e) {
        e.preventDefault();
        var code = $(this).data('code');
        var $temp = $('<input style="position: absolute; left: -9999px;">');
        $('body').append($temp);
        $temp.val(code).select();
        document.execCommand('copy');
        $temp.remove();

        var $btn = $(this);
        var originalText = $btn.text();
        $btn.text('Copied!');
        setTimeout(function () {
            $btn.text(originalText);
        }, 2000);
    });
});
