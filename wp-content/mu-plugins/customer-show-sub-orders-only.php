<?php
/**
 * Plugin Name: Customer Show Sub Orders Only
 * Description: Filters customer "My Account" orders page to show only sub-orders and hide parent orders (reverses Dokan's default behavior)
 * Version: 2.0
 * Author: Printlana
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Enable debugging mode - set to false to disable logs
define( 'PRINTLANA_CUSTOMER_SUB_ORDERS_DEBUG', true );

class Printlana_Customer_Show_Suborders_Only {

    public function __construct() {
        // Remove Dokan's filter that shows only parent orders and add our own
        add_action( 'init', array( $this, 'override_dokan_filter' ), 999 );

        // Log orders being returned (for debugging)
        add_action( 'woocommerce_before_account_orders', array( $this, 'log_customer_orders' ) );
    }

    /**
     * Override Dokan's filter to reverse the behavior
     */
    public function override_dokan_filter() {
        $this->log( 'Plugin initializing - setting up filters' );

        // Remove Dokan's filter that shows only parent orders (post_parent = 0)
        $removed = remove_filter( 'woocommerce_my_account_my_orders_query', 'dokan_get_customer_main_order' );
        $this->log( 'Dokan filter removed?', $removed ? 'Success' : 'Failed or did not exist' );

        // Add our custom filter to show only sub-orders (reverse of Dokan's behavior)
        add_filter( 'woocommerce_my_account_my_orders_query', array( $this, 'show_sub_orders_only' ), 10 );
        $this->log( 'Custom filter added successfully' );
    }

    /**
     * Filter to show only sub-orders (exclude parent orders)
     * This is the opposite of Dokan's dokan_get_customer_main_order function
     *
     * @param array $customer_orders Query args
     * @return array Modified query args
     */
    public function show_sub_orders_only( $customer_orders ) {
        $this->log( 'Filter triggered - original query args', $customer_orders );

        // Dokan uses: $customer_orders['post_parent'] = 0; (only parent orders)
        // We use parent_exclude to exclude orders with post_parent = 0 (exclude parent orders)

        if ( empty( $customer_orders['parent_exclude'] ) || ! is_array( $customer_orders['parent_exclude'] ) ) {
            $customer_orders['parent_exclude'] = array( 0 );
        } else {
            // Merge with existing parent_exclude if any
            $customer_orders['parent_exclude'][] = 0;
            $customer_orders['parent_exclude'] = array_values( array_unique( array_map( 'intval', $customer_orders['parent_exclude'] ) ) );
        }

        $this->log( 'Filter applied - modified query args (excluding parent orders)', $customer_orders );

        return $customer_orders;
    }

    /**
     * Log the customer's orders for debugging
     */
    public function log_customer_orders( $current_page ) {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $customer_id = get_current_user_id();

        // Get orders using the same method WooCommerce uses
        $customer_orders = wc_get_orders( apply_filters(
            'woocommerce_my_account_my_orders_query',
            array(
                'customer' => $customer_id,
                'page'     => $current_page,
                'paginate' => true,
            )
        ) );

        $this->log( 'Customer orders query executed', array(
            'customer_id' => $customer_id,
            'total_orders' => isset( $customer_orders->total ) ? $customer_orders->total : 0,
            'current_page' => $current_page,
        ) );

        // Log each order's details
        if ( isset( $customer_orders->orders ) && ! empty( $customer_orders->orders ) ) {
            $orders_info = array();
            foreach ( $customer_orders->orders as $order ) {
                $orders_info[] = array(
                    'order_id' => $order->get_id(),
                    'parent_id' => $order->get_parent_id(),
                    'status' => $order->get_status(),
                    'total' => $order->get_total(),
                    'is_sub_order' => $order->get_parent_id() > 0 ? 'YES (SUB-ORDER)' : 'NO (PARENT ORDER)',
                    'date' => $order->get_date_created()->date( 'Y-m-d H:i:s' ),
                );
            }
            $this->log( 'Orders being displayed (' . count( $orders_info ) . ' total)', $orders_info );
        } else {
            $this->log( 'No orders found for this customer' );
        }
    }

    /**
     * Debug logger
     */
    private function log( $message, $data = null ) {
        if ( ! defined( 'PRINTLANA_CUSTOMER_SUB_ORDERS_DEBUG' ) || ! PRINTLANA_CUSTOMER_SUB_ORDERS_DEBUG ) {
            return;
        }

        $log_message = '[PRINTLANA CUSTOMER SUB ORDERS] ' . $message;

        if ( $data !== null ) {
            $log_message .= ' | Data: ' . print_r( $data, true );
        }

        error_log( $log_message );
    }
}

// Initialize the plugin
new Printlana_Customer_Show_Suborders_Only();

/**
 * Add admin notice to show debugging is active
 */
function printlana_customer_sub_orders_debug_notice() {
    if ( ! defined( 'PRINTLANA_CUSTOMER_SUB_ORDERS_DEBUG' ) || ! PRINTLANA_CUSTOMER_SUB_ORDERS_DEBUG ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    echo '<div class="notice notice-warning is-dismissible">';
    echo '<p><strong>Printlana Customer Sub-Orders Debug Mode Active:</strong> Check your debug.log file for detailed logging. Logs are prefixed with [PRINTLANA CUSTOMER SUB ORDERS]. Remember to disable debugging in production!</p>';
    echo '</div>';
}
add_action( 'admin_notices', 'printlana_customer_sub_orders_debug_notice' );
