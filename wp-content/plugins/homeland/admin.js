jQuery(document).ready(function ($) {
    // Media Uploader
    $('.homeland-upload-btn').click(function (e) {
        e.preventDefault();
        var button = $(this);
        var targetId = button.data('target');
        var previewId = 'preview_' + targetId;

        var custom_uploader = wp.media({
            title: 'Select Image',
            button: {
                text: 'Use Image'
            },
            multiple: false
        }).on('select', function () {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            $('#' + targetId).val(attachment.url);
            $('#' + previewId).html('<img src="' + attachment.url + '" style="max-width:100px;display:block;margin-top:10px;">');
        }).open();
    });

    // Copy Shortcode
    $('.homeland-copy-btn').click(function () {
        var code = $(this).data('code');
        var tempInput = $('<input>');
        $('body').append(tempInput);
        tempInput.val(code).select();
        document.execCommand('copy');
        tempInput.remove();

        var originalText = $(this).text();
        $(this).text('Copied!');
        setTimeout(() => {
            $(this).text(originalText);
        }, 2000);
    });
});
