<?php

namespace U2Code\OrderMessenger\Admin;

use Exception;
use U2Code\OrderMessenger\Core\ServiceContainerTrait;
use U2Code\OrderMessenger\OrderMessengerPlugin;
use U2Code\OrderMessenger\Settings\Settings;
use WC_Order;
/**
 * Class Admin
 *
 * @package U2Code\OrderMessenger\Admin
 */
class Admin {
    use ServiceContainerTrait;
    /**
     * Admin constructor.
     *
     * @throws Exception
     */
    public function __construct() {
        $this->getContainer()->add( 'orderMessengerMetabox', new OrderMessengerMetabox() );
        $this->getContainer()->add( 'modalOrderMessenger', new ModalOrderMessenger() );
        add_action(
            'admin_enqueue_scripts',
            array($this, 'enqueueScripts'),
            10,
            2
        );
        add_action( 'admin_head', array($this, 'addNewMessagesIndicator'), 99 );
        add_action( 'admin_notices', array($this, 'showActivationMessage') );
        add_action( 'restrict_manage_posts', array($this, 'registerCustomMessagesFilter') );
        add_filter( 'parse_query', array($this, 'filterOrdersByUnread') );
        add_action(
            'before_delete_post',
            array($this, 'handleDeleteOrder'),
            10,
            2
        );
        add_action(
            'wp_trash_post',
            array($this, 'handleTrashOrder'),
            10,
            1
        );
        // HPOS
        add_action(
            'woocommerce_before_delete_order',
            array($this, 'handleDeleteOrder'),
            10,
            2
        );
        add_action(
            'woocommerce_before_trash_order',
            array($this, 'handleTrashOrder'),
            10,
            1
        );
        if ( !omfw_fs()->is_premium() ) {
            add_action( 'woocommerce_settings_' . Settings::SETTINGS_PAGE, function () {
                $this->getContainer()->getFileManager()->includeTemplate( 'admin/alerts/upgrade-alert.php', [
                    'upgradeUrl'   => omfw_fs_activation_url(),
                    'contactUsUrl' => OrderMessengerPlugin::getContactUsURL(),
                ] );
            } );
        }
    }

    public function handleTrashOrder( $orderId ) {
        $order = wc_get_order( $orderId );
        if ( $order ) {
            $this->getContainer()->getMessageRepository()->makeMessagesAsReadForOrder( $order->get_id(), 'admin' );
            $this->getContainer()->getMessageRepository()->makeMessagesAsReadForOrder( $order->get_id(), 'customer' );
        }
    }

    public function handleDeleteOrder( $orderId, $order ) {
        $order = ( $order instanceof WC_Order ? $order : wc_get_order( $orderId ) );
        if ( $order ) {
            $this->getContainer()->getMessageRepository()->deleteMessagesForOrder( $orderId );
        }
    }

    public function filterOrdersByUnread( $query ) {
        global $pagenow;
        $type = 'shop_order';
        if ( isset( $_GET['post_type'] ) ) {
            $type = sanitize_text_field( $_GET['post_type'] );
        }
        if ( 'shop_order' == $type && is_admin() && 'edit.php' == $pagenow && isset( $_GET['unread_orders'] ) && 'yes' === $_GET['unread_orders'] ) {
            try {
                $unreadOrdersIds = $this->getContainer()->getMessageRepository()->getUnreadOrdersIds();
            } catch ( Exception $e ) {
                return $query;
            }
            if ( !empty( $unreadOrdersIds ) ) {
                $query->query_vars['post__in'] = $unreadOrdersIds;
            } else {
                $query->query_vars['post__in'] = array(0);
            }
        }
        return $query;
    }

    public function registerCustomMessagesFilter() {
        if ( isset( $_GET['post_type'] ) && 'shop_order' === $_GET['post_type'] ) {
            $isSelected = isset( $_GET['unread_orders'] ) && 'yes' === $_GET['unread_orders'];
            ?>
			<select name="unread_orders">
				<option value=""><?php 
            esc_html_e( 'Show all orders', 'order-messenger-for-woocommerce' );
            ?></option>
				<option <?php 
            selected( $isSelected, true );
            ?>
					value="yes">
					<?php 
            esc_html_e( 'Show orders with unread messages', 'order-messenger-for-woocommerce' );
            ?>
				</option>
			</select>
			<?php 
        }
    }

    /**
     * Show message about activation plugin and advise next steps
     */
    public function showActivationMessage() {
        if ( get_transient( 'order_messenger_activated' ) ) {
            $link = $this->getContainer()->getSettings()->getLink();
            $this->getContainer()->getFileManager()->includeTemplate( 'admin/alerts/activation-alert.php', array(
                'link' => $link,
            ) );
            delete_transient( 'order_messenger_activated' );
        }
    }

    public function addNewMessagesIndicator() {
        global $submenu;
        $_submenu =& $submenu;
        if ( isset( $submenu['woocommerce'] ) ) {
            // Remove 'WooCommerce' sub menu item.
            unset($submenu['woocommerce'][0]);
            // Add count if user has access.
            if ( apply_filters( 'to', true ) && current_user_can( 'edit_others_shop_orders' ) ) {
                try {
                    $order_count = $this->getContainer()->getMessageRepository()->getUnreadMessagesCount();
                } catch ( Exception $e ) {
                    $order_count = 0;
                }
                if ( $order_count ) {
                    foreach ( $submenu['woocommerce'] as $key => $menu_item ) {
                        if ( 0 === strpos( $menu_item[0], _x( 'Orders', 'Admin menu name', 'woocommerce' ) ) ) {
                            $_submenu['woocommerce'][$key][0] .= ' <span data-notifications-count="' . esc_attr( $order_count ) . '" class="om-unread-messages-count count-' . esc_attr( $order_count ) . '"><span>' . esc_html( $order_count ) . '</span></span>';
                            // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                            break;
                        }
                    }
                }
            }
        }
    }

    public function enqueueScripts() {
        global $current_screen;
        wp_register_script(
            'om-admin-messenger-script',
            $this->getContainer()->getFileManager()->locateAsset( 'admin/messenger.js' ),
            array('jquery'),
            OrderMessengerPlugin::VERSION
        );
        wp_register_style(
            'om-admin-messenger-style',
            $this->getContainer()->getFileManager()->locateAsset( 'admin/messenger.css' ),
            array(),
            OrderMessengerPlugin::VERSION
        );
        wp_register_script(
            'magnific-popup',
            $this->getContainer()->getFileManager()->locateAsset( 'libraries/magnific-popup.min.js' ),
            array(),
            OrderMessengerPlugin::VERSION
        );
        wp_register_style(
            'magnific-popup',
            $this->getContainer()->getFileManager()->locateAsset( 'libraries/magnific-popup.css' ),
            array(),
            OrderMessengerPlugin::VERSION
        );
        wp_enqueue_script(
            'om-admin',
            $this->getContainer()->getFileManager()->locateAsset( 'admin/admin.js' ),
            array('jquery', 'magnific-popup'),
            OrderMessengerPlugin::VERSION
        );
        wp_enqueue_style(
            'om-admin',
            $this->getContainer()->getFileManager()->locateAsset( 'admin/admin.css' ),
            array(),
            OrderMessengerPlugin::VERSION
        );
        if ( 'shop_order' === $current_screen->post_type || 'woocommerce_page_wc-orders' === $current_screen->id || 'product' === $current_screen->post_type ) {
            wp_enqueue_media();
            wp_enqueue_script( 'magnific-popup' );
            wp_enqueue_style( 'magnific-popup' );
            wp_enqueue_script( 'om-admin-messenger-script' );
            wp_enqueue_style( 'om-admin-messenger-style' );
        }
    }

}
