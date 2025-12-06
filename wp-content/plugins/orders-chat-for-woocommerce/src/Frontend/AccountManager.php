<?php namespace U2Code\OrderMessenger\Frontend;

use Exception;
use U2Code\OrderMessenger\Config\Config;
use U2Code\OrderMessenger\Core\ServiceContainerTrait;
use U2Code\OrderMessenger\Entity\MessageType;
use WC_Order;

/**
 * Class AccountManager
 *
 * @package U2Code\OrderMessenger\Frontend
 */
class AccountManager {

	use ServiceContainerTrait;

	/**
	 * Frontend constructor.
	 *
	 * @throws Exception
	 */
	public function __construct() {

		add_filter( 'woocommerce_account_menu_items', function ( $items ) {

			$items['messages'] = __( 'Messages', 'order-messenger-for-woocommerce' );

			return $items;
		} );

		add_filter( 'woocommerce_my_account_my_orders_actions', function ( array $actions, WC_Order $order ) {

			if ( Config::isShowMessengerLinkInOrderActions() ) {
				$actions['messenger'] = array(
					'url'  => wc_get_endpoint_url( 'messenger', $order->get_id(), wc_get_page_permalink( 'myaccount' ) ),
					'name' => __( 'Messenger', 'order-messenger-for-woocommerce' ),
				);
			}

			return $actions;
		}, 2, 10 );

		add_action( 'woocommerce_account_messenger_endpoint', function ( $orderId = null ) {
			do_shortcode( '[order_chatroom order_id="' . $orderId . '"]' );
		} );

		add_action( 'woocommerce_account_messages_endpoint', function ( $currentPage = 1 ) {

			$currentPage = $currentPage ? intval( $currentPage ) : 1;
			$limit       = 10;

			do_shortcode( '[order_chatrooms limit="' . $limit . '" current_page="' . $currentPage . '" ]' );
		} );

		add_action( 'wp_head', function () {
			if ( is_account_page() && is_user_logged_in() ) {
				$userId = get_current_user_id();

				if ( $this->isMessengerPage() ) {
					global $wp;

					$parts = array_values( $wp->query_vars );

					reset( $parts );
					$orderId = intval( end( $parts ) );
					$order   = wc_get_order( $orderId );

					if ( $order && $order->get_customer_id() === get_current_user_id() ) {
						$this->getContainer()->getMessageRepository()->makeMessagesAsReadForOrder( $orderId, 'customer' );
					}
				}

				try {
					$unreadMessagesCount = (int) $this->getContainer()->getMessageRepository()->getUnreadMessagesCountForUser( $userId );
				} catch ( Exception $e ) {
					$unreadMessagesCount = 0;
				}
				if ( $unreadMessagesCount > 0 ) {
					?>
					<script>
						jQuery(document).ready(function ($) {
							var menuItem = $('.woocommerce-MyAccount-navigation-link--messages a');
							var unreadMessagesCount = <?php echo esc_attr( $unreadMessagesCount ); ?>;
							menuItem.append('<span class="om-global-new-messages-count"> + ' + unreadMessagesCount + '</span>');
						});
					</script>
					<style>
						.woocommerce-MyAccount-navigation-link--messages a:before {
							content: '' !important;
						}
					</style>
					<?php
				}
			}
		} );


		add_filter( 'woocommerce_endpoint_messenger_title', '__return_empty_string' );

		add_filter( 'woocommerce_endpoint_messages_title', function () {
			return __( 'Messages', 'order-messenger-for-woocommerce' );
		} );

	}

	public function isMessengerPage() {
		global $wp;

		$page_id = wc_get_page_id( 'myaccount' );

		return ( $page_id && is_page( $page_id ) && isset( $wp->query_vars['messenger'] ) );
	}
}
