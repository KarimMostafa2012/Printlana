<?php namespace U2Code\OrderMessenger\Admin;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Exception;
use U2Code\OrderMessenger\Core\ServiceContainerTrait;
use WC_Order;
use WP_Post;

/**
 * Class OrderMessengerMetabox
 *
 * @package U2Code\OrderMessenger\Admin
 */
class OrderMessengerMetabox {

	use ServiceContainerTrait;

	/**
	 * OrderMessengerMetabox constructor.
	 */
	public function __construct() {

		add_action( 'add_meta_boxes', function () {

			$screen           = 'shop_order';
			$controllerExists = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' );
			$hposEnabled      = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled();

			// HPOS
			if ( $controllerExists && $hposEnabled ) {
				$screen = wc_get_page_screen_id( 'shop-order' );
			}

			add_meta_box( 'order_messages', 'Messages', array( $this, 'render' ), $screen, 'side', 'low' );
		} );
	}


	/**
	 * Render metabox
	 *
	 * @param  WP_Post  $order
	 */
	public function render( $order ) {

		$order = $order instanceof WC_Order ? $order : wc_get_order( $order->ID );

		wp_enqueue_media();

		wp_enqueue_script( 'om-admin-messenger-script' );
		wp_enqueue_style( 'om-admin-messenger-style' );

		try {
			$messages      = $this->getContainer()->getMessageRepository()->getForOrder( $order->get_id(), null, null,
				'admin-view' );
			$totalMessages = $this->getContainer()->getMessageRepository()->getTotalForOrder( $order->get_id(),
				'admin-view' );
		} catch ( Exception $e ) {
			$messages      = array();
			$totalMessages = 0;
		}

		$this->getContainer()->getFileManager()->includeTemplate( 'admin/order/messenger-metabox.php', array(
			'orderId'       => $order->get_id(),
			'messages'      => $messages,
			'totalMessages' => $totalMessages,
		) );

		add_action( 'shutdown', function () use ( $order ) {
			// Read customer messages for this order
			$this->getContainer()->getMessageRepository()->makeMessagesAsReadForOrder( $order->get_id() );
		} );
	}
}
