jQuery(document).ready(function ($) {

    $('.color-option').on('click', function (e) {
        var radio = $(e.target).closest('.color-option').find('input[type=radio]');
        radio.prop('checked', true);
        radio.trigger('change');

    });

    $('[name=order_messenger_color_theme]').on('change', function (e) {
        $('.color-option').removeClass('selected');

        $(e.target).closest('.color-option').addClass('selected');
    });

    $('[data-om-extra-settings-controll-checkbox]').on('change', function () {

        var extraSettings = $(this).closest('tr').find('[data-om-extra-settings]');

        if ($(this).is(':checked')) {
            extraSettings.show();
        } else {
            extraSettings.hide();
        }
    }).trigger('change');
});