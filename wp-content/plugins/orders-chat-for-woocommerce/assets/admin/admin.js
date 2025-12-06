jQuery(document).ready(function ($) {
    $('[data-om-open-messenger]').magnificPopup({
        type: 'ajax',
        closeOnContentClick: false,
        enableEscapeKey: true,
        callbacks: {
            ajaxContentAdded: function () {
                $.each($('.order-messenger'), function (i, el) {
                    var orderMessenger = new OCAdminMessenger($(el));
                    orderMessenger.init();
                });

                $('.woocommerce-help-tip').tipTip({
                    'attribute': 'data-tip',
                    'fadeIn': 50,
                    'fadeOut': 50,
                    'delay': 200
                });

                var target = $.magnificPopup.instance.st.el;
                var notification = target.find('.om-open-messenger-button__new-messages-count');

                if (target && notification.length) {
                    var notificationsCount = parseInt(notification.data('notifications-count'));

                    if (notificationsCount > 0) {
                        notification.hide();

                        var globalNotifications = $('.om-unread-messages-count');
                        var globalNotificationsCount = parseInt(globalNotifications.text());

                        if (globalNotifications.length && globalNotificationsCount > 0) {

                            globalNotificationsCount -= notificationsCount;

                            if (globalNotificationsCount > 0) {
                                globalNotifications.text(globalNotificationsCount);
                            } else {
                                globalNotifications.hide();
                            }
                        }
                    }
                }
            }
        }
    });
});