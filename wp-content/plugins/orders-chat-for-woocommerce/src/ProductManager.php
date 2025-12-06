<?php namespace U2Code\OrderMessenger;

use U2Code\OrderMessenger\Config\Config;
use U2Code\OrderMessenger\Core\ServiceContainerTrait;
use U2Code\OrderMessenger\Entity\Message;
use U2Code\OrderMessenger\Entity\MessageAttachment;
use U2Code\OrderMessenger\Entity\MessageType;
use WC_Order;
use WC_Order_Item_Product;

/**
 * Class ProductManager
 *
 * @package U2Code\OrderMessenger\Admin
 */
class ProductManager {

	use ServiceContainerTrait;

	/**
	 * Admin constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'register' ), 99, 1 );
		add_action( 'woocommerce_product_data_panels', array( $this, 'render' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save' ) );

		if ( Config::ifPurchaseMessageEnabled() ) {

			switch ( Config::getPurchaseMessageSendTrigger() ) {
				case 'order_placed':
					$hook = 'woocommerce_thankyou';
					break;
				case 'payment_success':
					$hook = 'woocommerce_payment_complete';
					break;
				default:
					$hook = 'woocommerce_thankyou';
			}

			add_action( $hook, array( $this, 'handleProductPurchaseMessage' ) );

			add_action( 'woocommerce_order_actions', function ( $actions ) {

				$actions['om_send_purchasing_message'] = __( 'Send purchasing message', 'order-messenger-for-woocommerce' );

				return $actions;
			} );

			add_action( 'woocommerce_order_action_om_send_purchasing_message', array(
				$this,
				'handleSendPurchasingMessageAction'
			) );
		}
	}

	protected function sendPurchaseMessage( WC_Order $order, $force = false ) {
		foreach ( $order->get_items() as $item ) {

			if ( $item instanceof WC_Order_Item_Product ) {

				$productId = $item->get_product_id();
				$message   = get_post_meta( $productId, '_om_purchase_message', true );

				if ( $message ) {

					if ( $item->get_meta( 'is_purchase_message_sent' ) === 'yes' && ! $force ) {
						continue;
					}

					$attachmentId = (int) get_post_meta( $productId, '_om_purchase_attachment_id', true );
					$attachment   = new MessageAttachment( $attachmentId );
					$message      = new Message( $message, $order->get_id(), $order->get_customer_id(), 1, MessageType::admin(), $attachment );

					try {
						$message->save();

						$item->add_meta_data( 'is_purchase_message_sent', 'yes' );

					} catch ( \Exception $e ) {
						wc_get_logger()->add( 'order_messenger__errors', $e->getMessage() );
					}
				}
			}
		}
	}

	/**
	 * Handle purchase process
	 *
	 * @param int $orderId
	 */
	public function handleProductPurchaseMessage( $orderId ) {

		$order = wc_get_order( $orderId );

		if ( $order ) {
			$this->sendPurchaseMessage( $order );
		}
	}

	public function handleSendPurchasingMessageAction( $orderId ) {
		$order = wc_get_order( $orderId );

		if ( $order ) {
			$this->sendPurchaseMessage( $order, true );
		}
	}

	/**
	 * Add a new tab to woocommerce product tabs
	 *
	 * @param array $productTabs
	 *
	 * @return array
	 */
	public function register( $productTabs ) {

		$productTabs['order-messenger'] = array(
			'label'  => __( 'Order Messenger', 'order-messenger-for-woocommerce' ),
			'target' => 'order-messenger-data',
			'class'  => array( 'show_if_simple', 'show_if_variable' )
		);

		return $productTabs;
	}

	/**
	 * Render tab
	 */
	public function render() {

		global $post;

		$attachmentId = (int) get_post_meta( $post->ID, '_om_purchase_attachment_id', true );
		$message      = get_post_meta( $post->ID, '_om_purchase_message', true );
		$attachment   = null;

		if ( $attachmentId ) {
			$attachment = new MessageAttachment( $attachmentId );
		}

		$this->getContainer()->getFileManager()->includeTemplate( 'admin/product/order-messenger-tab.php', array(
			'productId'    => $post->ID,
			'message'      => $message,
			'attachmentId' => $attachmentId,
			'attachment'   => $attachment,
		) );
	}

	/**
	 * Save data
	 *
	 * @param int $productId
	 */
	public function save( $productId ) {
		if ( wp_verify_nonce( wp_create_nonce( 'validnonce' ), 'validnonce' ) ) {
			if ( isset( $_POST['_om_purchase_message'] ) ) {
				$purchaseMessage = sanitize_text_field(wp_strip_all_tags( $_POST['_om_purchase_message'] ));
				update_post_meta( $productId, '_om_purchase_message', $purchaseMessage );
			}
			if ( isset( $_POST['_om_purchase_attachment_id'] ) ) {
				$purchaseMessageAttachmentId = intval( $_POST['_om_purchase_attachment_id'] );
				update_post_meta( $productId, '_om_purchase_attachment_id', $purchaseMessageAttachmentId );
			}
		}
	}
}
