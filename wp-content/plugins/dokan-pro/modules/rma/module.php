<?php

namespace WeDevs\DokanPro\Modules\RMA;

use WeDevs\DokanPro\Modules\RMA\Api\CouponController;
use WeDevs\DokanPro\Modules\RMA\Api\RefundController;
use WeDevs\DokanPro\Modules\RMA\Api\WarrantyRequestController;
use WeDevs\DokanPro\Modules\RMA\Api\WarrantyConversationController;
use WeDevs\DokanPro\Modules\RMA\Emails\ConversationNotification;
use WeDevs\DokanPro\Modules\RMA\Emails\SendCouponEmail;
use WeDevs\DokanPro\Modules\RMA\Emails\SendWarrantyRequest;

class Module {

    /**
     * Load automatically when class initiate
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->define();
        $this->includes();
        $this->initiate();
        $this->hooks();

        add_action( 'dokan_activated_module_rma', [ $this, 'activate' ] );
        add_filter( 'dokan_rest_api_class_map', [ $this, 'rest_api_class_map' ] );
    }

    /**
     * Defines constant
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function define() {
        define( 'DOKAN_RMA_DIR', __DIR__ );
        define( 'DOKAN_RMA_INC_DIR', DOKAN_RMA_DIR . '/includes' );
        define( 'DOKAN_RMA_ASSETS_DIR', plugins_url( 'assets', __FILE__ ) );
    }

    /**
     * Get plugin path
     *
     * @since 1.5.1
     *
     * @return string
     **/
    public function plugin_path() {
        return untrailingslashit( plugin_dir_path( __FILE__ ) );
    }

    /**
     * Includes all necessary class and files
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function includes() {
        if ( is_admin() ) {
            // File included only for backward compatibility.
            require_once DOKAN_RMA_DIR . '/classes/class-admin.php';
        }

        // Files included only for backward compatibility.
        require_once DOKAN_RMA_DIR . '/classes/class-trait-rma.php';
        require_once DOKAN_RMA_DIR . '/classes/class-ajax.php';
        require_once DOKAN_RMA_DIR . '/classes/class-vendor.php';
        require_once DOKAN_RMA_DIR . '/classes/class-product.php';
        require_once DOKAN_RMA_DIR . '/classes/class-order.php';
        require_once DOKAN_RMA_DIR . '/classes/class-frontend.php';
        require_once DOKAN_RMA_DIR . '/classes/class-warranty-request.php';
        require_once DOKAN_RMA_DIR . '/classes/class-warranty-item.php';
        require_once DOKAN_RMA_DIR . '/classes/class-warranty-request-conversation.php';

        // Load all helper functions
        require_once DOKAN_RMA_INC_DIR . '/functions.php';
    }

    /**
     * Initiate all classes
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function initiate() {
        if ( is_admin() ) {
            new Admin();
        }

        new RmaCache();
        new BlockData();
        new Ajax();
        new Vendor();
        new Frontend();
        new Product();
        new Order();
    }

    /**
     * Init all hooks
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function hooks() {
        add_action( 'init', [ $this, 'register_scripts' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'load_scripts' ] );

        add_action( 'dokan_loaded', [ $this, 'load_emails' ], 20 );
        add_filter( 'dokan_email_list', [ $this, 'set_email_template_directory' ] );
        add_action( 'woocommerce_flush_rewrite_rules', [ $this, 'flush_rewrite_rules' ] );
    }

    /**
     * Register REST API controllers
     *
     * @since 3.5.0
     *
     * @param array $class_map
     *
     * @return array
     */
    public function rest_api_class_map( array $class_map ): array {
        $class_map[ DOKAN_RMA_INC_DIR . '/Api/WarrantyRequestController.php' ] = WarrantyRequestController::class;
        $class_map[ DOKAN_RMA_INC_DIR . '/Api/CouponController.php' ] = CouponController::class;
        $class_map[ DOKAN_RMA_INC_DIR . '/Api/RefundController.php' ] = RefundController::class;
        $class_map[ DOKAN_RMA_INC_DIR . '/Api/WarrantyConversationController.php' ] = WarrantyConversationController::class;

        return $class_map;
    }

    /**
     * Load emails
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function load_emails() {
        add_filter( 'dokan_email_classes', [ $this, 'load_rma_email_classes' ], 99 );
        add_filter( 'dokan_email_actions', [ $this, 'register_rma_email_actions' ] );
    }

    /**
     * Load all email class related with RMA
     *
     * @since 1.0.0
     *
     * @param array $wc_emails WooCommerce email classes
     *
     * @return array
     */
    public function load_rma_email_classes( array $wc_emails ): array {
        // Files included only for backward compatibility.
        include_once DOKAN_RMA_DIR . '/classes/emails/class-dokan-rma-send-coupin-email.php';
        include_once DOKAN_RMA_DIR . '/classes/emails/class-dokan-rma-send-warranty-request.php';

        $wc_emails['Dokan_Send_Coupon_Email']             = new SendCouponEmail();
        $wc_emails['Dokan_Rma_Send_Warranty_Request']     = new SendWarrantyRequest();
        $wc_emails['Dokan_RMA_Conversation_Notification'] = new ConversationNotification();

        return $wc_emails;
    }

    /**
     * Register all email actions
     *
     * @since 1.0.0
     *
     * @param array $actions Email actions
     *
     * @return array
     */
    public function register_rma_email_actions( array $actions ): array {
        $actions[] = 'dokan_send_coupon_to_customer';
        $actions[] = 'dokan_rma_send_warranty_request';
        $actions[] = 'dokan_pro_rma_conversion_created';

        return $actions;
    }

    /**
     * Register Scripts
     *
     * @since 3.7.4
     */
    public function register_scripts() {
        list( $suffix, $version ) = dokan_get_script_suffix_and_version();

        wp_register_script( 'dokan-rma-script', DOKAN_RMA_ASSETS_DIR . '/js/scripts.js', array( 'jquery' ), $version, true );
        wp_register_style( 'dokan-rma-style', DOKAN_RMA_ASSETS_DIR . '/js/style-style.css', array(), $version );

        $script_assets_path = DOKAN_RMA_DIR . '/assets/js/vendor-dashboard.asset.php';
        if ( file_exists( $script_assets_path ) ) {
            $vendor_asset = require $script_assets_path;
            $dependencies = $vendor_asset['dependencies'] ?? [];
            $version      = $vendor_asset['version'] ?? '';

            wp_register_style(
                'dokan-rma-vendor-dashboard',
                DOKAN_RMA_ASSETS_DIR . '/js/vendor-dashboard.css',
                [ 'dokan-react-frontend','dokan-react-components' ],
                $version
            );

            wp_register_script(
                'dokan-rma-vendor-dashboard',
                DOKAN_RMA_ASSETS_DIR . '/js/vendor-dashboard.js',
                array_merge( $dependencies, [ 'dokan-react-components', 'dokan-utilities', 'moment' ] ),
                $version,
                true
            );

            $localize_data = [
                'orderUrl'         => wp_nonce_url( add_query_arg( [ 'order_id' => '{{ORDER_ID}}' ], dokan_get_navigation_url( 'orders' ) ), 'dokan_view_order' ),
                'currentUserId'    => get_current_user_id(),
                'is_coupon_enable' => 'on' === dokan_get_option( 'rma_enable_coupon_request', 'dokan_rma', 'off' ),
                'is_refund_enable' => 'on' === dokan_get_option( 'rma_enable_refund_request', 'dokan_rma', 'off' ),
            ];

            wp_localize_script( 'dokan-rma-vendor-dashboard', 'DokanRMAPanel', $localize_data );
            wp_set_script_translations( 'dokan-rma-vendor-dashboard', 'dokan', plugin_dir_path( DOKAN_FILE ) . 'languages' );
        }
    }

    /**
     * Load scripts
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function load_scripts() {
        global $wp;

        if ( ( isset( $wp->query_vars['settings'] ) && 'rma' === $wp->query_vars['settings'] ) ||
            ( isset( $_GET['action'] ) && 'edit' === $_GET['action'] && isset( $_GET['product_id'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            wp_enqueue_script( 'dokan-rma-script' );
            wp_enqueue_style( 'dokan-rma-style' );
        }

        if ( is_account_page() && ( isset( $wp->query_vars['request-warranty'] ) || isset( $wp->query_vars['view-rma-requests'] ) ) ) {
            wp_enqueue_style( 'dokan-rma-style' );
        }

        if ( isset( $wp->query_vars['return-request'] ) ) {
            wp_enqueue_style( 'dokan-rma-style' );
            wp_enqueue_script( 'dokan-rma-script' );

            wp_localize_script(
                'dokan-rma-script', 'DokanRMA', [
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'dokan_rma_nonce' ),
				]
            );
        }

        if ( dokan_is_seller_dashboard() ) {
            wp_enqueue_script( 'dokan-rma-vendor-dashboard' );
            wp_enqueue_style( 'dokan-rma-vendor-dashboard' );
        }
    }

    /**
     * Create Mapping table for product and vendor
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function activate() {
        global $wp_roles;

        if ( class_exists( 'WP_Roles' ) && ! isset( $wp_roles ) ) {
            $wp_roles = new \WP_Roles(); //phpcs:ignore
        }

        $wp_roles->add_cap( 'seller', 'dokan_view_store_rma_menu' );
        $wp_roles->add_cap( 'administrator', 'dokan_view_store_rma_menu' );
        $wp_roles->add_cap( 'shop_manager', 'dokan_view_store_rma_menu' );

        $wp_roles->add_cap( 'seller', 'dokan_view_store_rma_settings_menu' );
        $wp_roles->add_cap( 'administrator', 'dokan_view_store_rma_settings_menu' );
        $wp_roles->add_cap( 'shop_manager', 'dokan_view_store_rma_settings_menu' );

        $this->create_tables();
        $this->flush_rewrite_rules();
    }

    /**
     * Flush rewrite rules
     *
     * @since 3.3.1
     *
     * @return void
     */
    public function flush_rewrite_rules() {
        dokan()->rewrite->register_rule();
        flush_rewrite_rules();
    }

    /**
     * Set Proper template directory.
     *
     * @param array $template_array Email template array
     *
     * @return array
     */
    public function set_email_template_directory( array $template_array ): array {
        $template_array[] = 'send-coupon.php';
        $template_array[] = 'send-warranty-request.php';
        $template_array[] = 'send-conversation-notification.php';
        return $template_array;
    }

    /**
     * Create all tables related with RMA
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function create_tables() {
        global $wpdb;

        include_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $request_table = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}dokan_rma_request` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `order_id` int(11) NOT NULL,
          `vendor_id` int(11) NOT NULL,
          `customer_id` int(11) NOT NULL,
          `type` varchar(25) NOT NULL DEFAULT '',
          `status` varchar(25) NOT NULL DEFAULT '',
          `reasons` text NOT NULL,
          `details` longtext,
          `note` longtext,
          `created_at` datetime NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";

        $request_product_map = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}dokan_rma_request_product` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `request_id` int(11) NOT NULL,
          `product_id` int(11) NOT NULL,
          `quantity` int(11) NOT NULL,
          `item_id` int(11) NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";

        $conversation_table = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}dokan_rma_conversations` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `request_id` int(11) NOT NULL,
          `from` int(11) NOT NULL,
          `to` int(11) NOT NULL,
          `message` longtext,
          `created_at` datetime NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";

        dbDelta( $request_table );
        dbDelta( $request_product_map );
        dbDelta( $conversation_table );
    }
}
