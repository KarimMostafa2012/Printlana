jQuery(document).ready(function () {
    if (typeof count_notifs !== 'undefined') {
        jQuery('.wps-notifs .pending-count').text(count_notifs.notifs);
    }

    if(count_notifs.notifs === '0' || count_notifs.notifs === '') {
        jQuery('.wps-notifs').hide();
    }

    var red_notice      = jQuery('#wps-dashboard-notifications .main').children('div.wps-border-red').length;
    var yellow_notice   = jQuery('#wps-dashboard-notifications .main').children('div.wps-border-yellow').length;
    number = red_notice + yellow_notice;

    if (number) {

        jQuery('.wps-notifs .pending-count').text(number);

        data = {
            'action': 'count_notif',
            'number': number,
            '_ajax_nonce': count_notifs.nonce
        };

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(ajaxurl, data);
    } else if (typeof count_notifs !== 'undefined') {
        jQuery('.wps-notifs .pending-count').text(count_notifs.notifs);
    }

    if(jQuery('#wps-dashboard-notifications .main').length && ! jQuery('#wps-dashboard-notifications .main').children('div').length ) {
        jQuery('.wps-notifs').hide();
        jQuery('#wps-dashboard-notifications .main').html('<p class="wps-sleep"><i class="fal fa-coffee" data-fa-transform="grow-6"></i> <span>' + count_notifs.text + '</span></p>')

        data = {
            'action': 'count_notif',
            'number': 0,
            '_ajax_nonce': count_notifs.nonce
        };

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(ajaxurl, data);
    }
});