jQuery(function ($) {
    var tiptip_args = {
        'attribute': 'data-tip',
        'maxWidth': '280px',
        'fadeIn': 50,
        'fadeOut': 50,
        'delay': 200
    };

    $('#tiptip_holder').removeAttr('style');
    $('#tiptip_arrow').removeAttr('style');
    $('.wps-help-tip').tipTip(tiptip_args);

    $('.wps-updates.is-dismissible').each(function () {
        var $el = $(this),
            $button = $('<button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button>');

        // Ensure plain text
        $button.find('.screen-reader-text').text( 'Dismiss this notice.' );
        $button.on('click.wp-dismiss-notice', function (event) {
            event.preventDefault();
            $el.fadeTo(100, 0, function () {
                $el.slideUp(100, function () {
                    $el.remove();
                });
            });
        });

        $el.append($button);
    });

    $('.wps-counter-notifications').append($('#wps-dashboard-notifications .main').children('div').length);

    $('div[data-dismissible] button.notice-dismiss').click(function (event) {
        event.preventDefault();
        var $this = $(this);

        var attr_value, option_name, dismissible_length, data;

        attr_value = $this.parent().attr('data-dismissible').split('-');

        // remove the dismissible length from the attribute value and rejoin the array.
        dismissible_length = attr_value.pop();

        option_name = attr_value.join('-');

        if ( $this.parent().hasClass('wps-border-red') || $this.parent().hasClass('wps-border-yellow') ) {
            var red_notice = jQuery('#wps-dashboard-notifications .main').children('div.wps-border-red').length;
            var yellow_notice = jQuery('#wps-dashboard-notifications .main').children('div.wps-border-yellow').length;
            number = red_notice + yellow_notice;
            jQuery('.wps-notifs .pending-count').text(number - 1);
        } else {
            number = false;
        }

        data = {
            'action': 'dismiss_admin_notice',
            'option_name': option_name,
            'dismissible_length': dismissible_length,
            '_ajax_nonce': dismissible_notice.nonce,
            'number': number
        };

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        $.post(ajaxurl, data);
    });

    $('#nav-add-mass').click(function () {
        $('#add-mass').attr('class', 'wps-view');
        $('#add-simple').attr('class', 'wps-hide');
        $('#nav-add-mass').attr('class', 'current');
        $('#nav-add-simple').attr('class', 'not-current');
    });
    $('#nav-add-simple').click(function () {
        $('#add-simple').attr('class', 'wps-view');
        $('#add-mass').attr('class', 'wps-hide');
        $('#nav-add-mass').attr('class', 'not-current');
        $('#nav-add-simple').attr('class', 'current');
    });

    // multiple select with AJAX search
    $('#select2_wps_users').select2({
        ajax: {
            url: ajaxurl, // AJAX URL is predefined in WordPress admin
            dataType: 'json',
            delay: 250, // delay in ms while typing when to perform a AJAX search
            data: function (params) {
                return {
                    q: params.term, // search query
                    action: 'wps_get_users' // AJAX action for admin-ajax.php
                };
            },
            processResults: function (data) {
                var options = [];
                if (data) {

                    // data is the array of arrays, and each of them contains ID and the Label of the option
                    $.each(data, function (index, text) { // do not forget that "index" is just auto incremented value
                        options.push({id: text['id'], text: text['text']});
                    });

                }
                return {
                    results: options
                };
            },
            cache: true
        },
        language: 'fr',
        minimumInputLength: 3 // the minimum of symbols to input before perform a search
    });

    $('#select2_wps_posts').select2({
        ajax: {
            url: ajaxurl, // AJAX URL is predefined in WordPress admin
            dataType: 'json',
            delay: 250, // delay in ms while typing when to perform a AJAX search
            data: function (params) {
                return {
                    q: params.term, // search query
                    action: 'wps_get_posts',
                    _ajax_nonce: $(this).data('nonce')
                };
            },
            processResults: function (data) {
                var options = [];
                if (data) {

                    // data is the array of arrays, and each of them contains ID and the Label of the option
                    $.each(data, function (index, text) { // do not forget that "index" is just auto incremented value
                        options.push({id: text['id'], text: text['text'] + ' ( ' + text['cpt'] + ' )'});
                    });

                }
                return {
                    results: options
                };
            },
            cache: true
        },
        language: 'fr',
        minimumInputLength: 3 // the minimum of symbols to input before perform a search
    });

    $('.toplevel_page_wps-bidouille h2.hndle').click(function () {
        var elem = $(this).parent().find('.main');
        var h2 = $(this).parent().find('h2');
        var option_name = $(this).parent().attr('id');
        if (elem.hasClass('wps-hide')) {
            elem.removeClass('wps-hide').addClass('wps-view');
            h2.removeClass('block-hide').addClass('block-view');
            data = {
                'action': 'delete_option_wps_display',
                'option_name': option_name
            };

            // We can also pass the url value separately from ajaxurl for front end AJAX implementations
            $.post(ajaxurl, data);
        } else {
            elem.removeClass('wps-view').addClass('wps-hide');
            h2.removeClass('block-view').addClass('block-hide');
            data = {
                'action': 'add_option_wps_display',
                'option_name': option_name
            };

            // We can also pass the url value separately from ajaxurl for front end AJAX implementations
            $.post(ajaxurl, data);
        }
    });

    $('a.wps-repair-db').click(function (event) {
        event.preventDefault();

        url = $(this).attr('href');

        data = {
            'action': 'add_allow_repair_wp_config',
            '_ajax_nonce': $(this).data('nonce')
        };

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        $.post(ajaxurl, data).done(function (data) {
            window.open(url, '_blank');
        });
    });

    $('#wps_save_settings').bind('change', function () {
        var value = $(this).val();

        checked = $(this).is(":checked");

        data = {
            'action': 'save_settings_wps',
            'checked': checked,
            '_ajax_nonce': $(this).data('nonce')
        };

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        $.post(ajaxurl, data);
    });

    $('a.wps_bidouill_install_premiums').click(function (event) {
        event.preventDefault();

        var url     = $(this).attr('data-href');
        var nonce   = $(this).attr('data-nonce');
        var id      = $(this).attr('id');

        data = {
            'action': 'download_plugins_premium',
            'url': url,
            '_ajax_nonce': nonce
        };

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        var t = $.post(ajaxurl, data);

        t.done(function () {
            $('#' + id).text(_wpUpdatesSettings.l10n.installing);
            setTimeout(function(){
                $('#' + id).parent().hide();
                $('#' + id + '-activate').show();
                $('#' + id + '-activate').addClass('button-primary');
            }, 2000);
        });
    });

    $('a.wps_bidouille_update_plugin_premiums').click(function (event) {
        event.preventDefault();

        var url     = $(this).attr('data-href');
        var nonce   = $(this).attr('data-nonce');
        var id      = $(this).attr('id');
        var wps     = $(this).attr('data-wps');

        data = {
            'action': 'update_plugin_premium',
            'url': url,
            '_ajax_nonce': nonce
        };

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        var t = $.post(ajaxurl, data);

        t.done(function () {
            $('#' + id).text(_wpUpdatesSettings.l10n.updating);
            setTimeout(function(){
                $('#' + id).parent().hide();
                $('#' + id + '-' + wps).show();
            }, 2000);
        });
    });

    $('a.wps_bidouille_install_theme_premiums').click(function (event) {
        event.preventDefault();

        var url     = $(this).attr('data-href');
        var nonce   = $(this).attr('data-nonce');
        var id      = $(this).attr('id');

        data = {
            'action': 'download_themes_premium',
            'url': url,
            '_ajax_nonce': nonce
        };

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        var t = $.post(ajaxurl, data);

        t.done(function () {
            $('#' + id).text(_wpUpdatesSettings.l10n.installing);
            setTimeout(function(){
                $('#' + id).parent().hide();
                $('#' + id + '-activate').show();
                $('#' + id + '-activate').addClass('button-primary');
            }, 2000);
        });
    });

    $('a.wps_bidouille_update_theme_premiums').click(function (event) {
        event.preventDefault();

        var url     = $(this).attr('data-href');
        var nonce   = $(this).attr('data-nonce');
        var id      = $(this).attr('id');
        var wps     = $(this).attr('data-wps');

        data = {
            'action': 'update_theme_premium',
            'url': url,
            '_ajax_nonce': nonce
        };

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        var t = $.post(ajaxurl, data);

        t.done(function () {
            $('#' + id).text(_wpUpdatesSettings.l10n.updating);
            setTimeout(function(){
                $('#' + id).parent().hide();
                $('#' + id + '-' + wps).show();
            }, 2000);
        });
    });

    $('.wps-bidouille_page_wps-bidouille-suggest-plugins-premium a.activate-now').click(function (event) {
        $(this).text('Activation ...');
    });

    $('a.wps-install-theme').click(function () {
        $(this).text(_wpUpdatesSettings.l10n.installing);
    });

    $('a.wps-update-theme').click(function () {
        $(this).text(_wpUpdatesSettings.l10n.updating);
    });

    $('.wps-bidouille_page_wps-bidouille-suggest-themes a.activate-now').click(function (event) {
        $(this).text('Activation ...');
    });

    $('a.wps-delete-transient-premiums').click(function (event) {
        event.preventDefault();

        var nonce   = $(this).attr('data-nonce');

        data = {
            'action': 'delete_transient_premium',
            'nonce': nonce
        };

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        var t = $.post(ajaxurl, data);

        t.done(function () {
            location.reload(true);
        });
    });
});