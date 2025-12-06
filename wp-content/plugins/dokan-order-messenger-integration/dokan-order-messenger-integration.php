<?php
/**
 * Plugin Name: Dokan Order Messenger Integration
 * Description: Integrates Order Messenger for WooCommerce with Dokan Vendor Dashboard
 * Version: 1.9.1
 * Author: andrew
 * Text Domain: dokan-order-messenger
 * Requires Plugins: orders-chat-for-woocommerce, dokan-lite
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure required plugins are active
if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

if (!is_plugin_active('orders-chat-for-woocommerce/order-chats-for-woocommerce.php') || !is_plugin_active('dokan-lite/dokan.php')) {
    return;
}

// Add Chat action to Orders page
add_filter('dokan_orders_table_row_actions', 'dokan_add_chat_action', 10, 2);
function dokan_add_chat_action($actions, $order) {
    $order_id = $order->get_id();
    $vendor_id = dokan_get_seller_id_by_order($order_id);

    if (dokan_get_current_user_id() == $vendor_id) {
        try {
            $unread_count = \U2Code\OrderMessenger\Core\ServiceContainer::getInstance()
                ->getMessageRepository()
                ->getUnreadCountForOrder($order_id, 'admin');
        } catch (Exception $e) {
            $unread_count = 0;
        }

        $action_label = __('Chat', 'dokan-order-messenger');
        if ($unread_count > 0) {
            $action_label .= ' <span class="om-unread-messages-count">+' . esc_html($unread_count) . '</span>';
        }

        $actions['chat'] = array(
            'title' => __('Chat', 'dokan-order-messenger'),
            'action' => 'chat',
            'url' => dokan_get_navigation_url('orders') . '?order_id=' . $order_id . '&_wpnonce=' . wp_create_nonce('dokan_view_order'),
            'icon' => '<i class="fa fa-comments"></i>',
            'name' => $action_label
        );
    }

    return $actions;
}

// Add chat to order details page after downloadable
add_action('dokan_order_detail_after_downloadable', 'dokan_add_order_messenger_chat', 10, 1);
function dokan_add_order_messenger_chat($order) {
    $order_id = $order->get_id();
    $vendor_id = dokan_get_seller_id_by_order($order_id);

    if (dokan_get_current_user_id() == $vendor_id) {
        ?>
        <div class="dokan-order-messenger">
            <h3><?php _e('Order Messages', 'dokan-order-messenger'); ?></h3>
            <div class="order-messenger"
                 data-url="<?php echo esc_url(rest_url('order-messenger/v1')); ?>"
                 data-nonce="<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>"
                 data-orderId="<?php echo esc_attr($order_id); ?>"
                 data-limit="10"
                 data-total="<?php
                    try {
                        echo esc_attr(\U2Code\OrderMessenger\Core\ServiceContainer::getInstance()
                            ->getMessageRepository()
                            ->getTotalForOrder($order_id, 'admin'));
                    } catch (Exception $e) {
                        echo '0';
                    }
                 ?>">
                <?php
                try {
                    $messages = \U2Code\OrderMessenger\Core\ServiceContainer::getInstance()
                        ->getMessageRepository()
                        ->getForOrder($order_id, 0, null, 'admin');
                    $total_messages = \U2Code\OrderMessenger\Core\ServiceContainer::getInstance()
                        ->getMessageRepository()
                        ->getTotalForOrder($order_id, 'admin');

                    \U2Code\OrderMessenger\Core\ServiceContainer::getInstance()->getFileManager()->includeTemplate(
                        'admin/order/messenger-metabox.php',
                        array(
                            'orderId' => $order_id,
                            'messages' => $messages,
                            'totalMessages' => $total_messages,
                        )
                    );
                } catch (Exception $e) {
                    echo '<div class="dokan-alert dokan-alert-danger">' . esc_html__('Error loading messages.', 'dokan-order-messenger') . '</div>';
                }
                ?>
            </div>
        </div>
        <?php
        try {
            \U2Code\OrderMessenger\Core\ServiceContainer::getInstance()
                ->getMessageRepository()
                ->makeMessagesAsReadForOrder($order_id, 'admin');
        } catch (Exception $e) {
            // Silent fail - messages will remain unread
        }
    }
}

// Override send message permission for vendors
add_filter('order_messenger/permissions/userCanSendAdminMessage', 'dokan_allow_vendor_send_message', 10, 4);
function dokan_allow_vendor_send_message($can_send, $user_id, $order, $message) {
    $vendor_id = dokan_get_seller_id_by_order($order->get_id());
    $user = get_user_by('id', $user_id);
    $is_vendor = in_array('seller', (array) $user->roles);
    return $can_send || ($is_vendor && $user_id == $vendor_id);
}

// Fallback capability override for REST API
add_filter('user_has_cap', 'dokan_grant_vendor_send_access', 10, 4);
function dokan_grant_vendor_send_access($allcaps, $cap, $args, $user) {
    if (isset($_GET['order_id']) && in_array('seller', (array) $user->roles)) {
        $order_id = absint($_GET['order_id']);
        $vendor_id = dokan_get_seller_id_by_order($order_id);
        if ($user->ID == $vendor_id) {
            $allcaps['edit_others_shop_orders'] = true;
            $allcaps['send_order_messages'] = true;
        }
    }
    return $allcaps;
}

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'dokan_order_messenger_enqueue_scripts');
function dokan_order_messenger_enqueue_scripts() {
    if (dokan_is_seller_dashboard() && isset($_GET['order_id'])) {
        // Enqueue Order Messenger admin scripts and styles
        wp_enqueue_script('jquery');
        wp_enqueue_media();
        wp_enqueue_script('jquery-blockui', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.blockUI/2.70/jquery.blockUI.min.js', array('jquery'), '2.70', true);
        wp_enqueue_script('magnific-popup', plugins_url('orders-chat-for-woocommerce/assets/libraries/magnific-popup.min.js'), array('jquery'), '1.0', true);
        wp_enqueue_style('magnific-popup', plugins_url('orders-chat-for-woocommerce/assets/libraries/magnific-popup.css'), array(), '1.0');
        wp_enqueue_script('om-admin-messenger-script', plugins_url('orders-chat-for-woocommerce/assets/admin/messenger.js'), array('jquery', 'magnific-popup', 'jquery-blockui'), '1.0', true);
        wp_enqueue_style('om-admin-messenger-style', plugins_url('orders-chat-for-woocommerce/assets/admin/messenger.css'), array('magnific-popup'), '1.0');

        // Enqueue custom styles
        wp_enqueue_style('dokan-order-messenger', plugins_url('assets/css/dokan-order-messenger.css', __FILE__), array(), '1.0');

        // Localize script with REST API details
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        wp_localize_script('om-admin-messenger-script', 'dokanOrderMessenger', array(
            'rest_url' => rest_url('order-messenger/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'order_id' => $order_id,
            'is_admin' => true
        ));
    }
}
?>