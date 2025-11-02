<?php namespace U2Code\OrderMessenger\Admin;

use Exception;
use U2Code\OrderMessenger\Core\ServiceContainerTrait;

/**
 * Class ModalOrderMessenger
 *
 * @package U2Code\OrderMessenger\Admin
 */
class ModalOrderMessenger {

	const MODAL_LOAD_ACTION = 'load_messenger_for_order';

	use ServiceContainerTrait;

	public function __construct() {

		add_action( 'admin_post_' . self::MODAL_LOAD_ACTION, array( $this, 'loadMessenger' ) );

		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'renderMessageColumn' ) );
		// HPOS
		add_action( 'woocommerce_shop_order_list_table_custom_column', array( $this, 'renderMessageColumn' ), 10, 2 );

		add_filter( 'manage_edit-shop_order_columns', array( $this, 'addMessageColumn' ), 20 );
		// HPOS
		add_filter( 'woocommerce_shop_order_list_table_columns', array( $this, 'addMessageColumn' ), 20 );
	}

	public function addMessageColumn( $columns ) {

		$new_columns = array();

		foreach ( $columns as $column_name => $column_info ) {
			if ( 'order_total' === $column_name ) {
				$new_columns['order_messages'] = __( 'Messenger', 'order-messenger-for-woocommerce:' );
			}

			$new_columns[ $column_name ] = $column_info;
		}

		return $new_columns;
	}

	public function renderMessageColumn( $column, $order = null ) {

		global $post;

		$order = $order ? $order : wc_get_order( $post->ID );

		if ( ! $order ) {
			return;
		}

		if ( 'order_messages' === $column ) {

			try {
				$unreadCount = $this->getContainer()->getMessageRepository()->getUnreadCountForOrder( $order->get_id(),
					'admin' );
			} catch ( Exception $e ) {
				$unreadCount = 0;
			}

			?>
			<a data-order-id="<?php echo esc_attr( $order->get_id() ); ?>"
			   href='
			   <?php
			   echo esc_attr( add_query_arg( array(
				   'action'   => self::MODAL_LOAD_ACTION,
				   'order_id' => $order->get_id(),
			   ), admin_url( 'admin-post.php' ) ) );
				?>
			   '
			   data-om-open-messenger class="om-open-messenger-button button button-small">
				<?php esc_attr_e( 'View', 'order-messenger-for-woocommerce' ); ?>
				<?php if ( $unreadCount > 0 ) : ?>
					<span class="om-open-messenger-button__new-messages-count"
						  data-notifications-count="<?php echo esc_attr( $unreadCount ); ?>">+ <?php echo esc_html( $unreadCount ); ?></span>
				<?php endif; ?>
			</a>
			<?php
		}
	}

	public function loadMessenger() {
		$orderId    = isset( $_GET['order_id'] ) ? (int) $_GET['order_id'] : 0;
		$permission = apply_filters( 'order_messenger/admin/permissions/userViewMessenger',
			current_user_can( 'edit_others_shop_orders' ), $orderId );

		if ( ! $permission ) {
			return false;
		}

		// todo: check nonce

		$order = wc_get_order( $orderId );

		if ( $order ) {

			try {
				$messages      = $this->getContainer()->getMessageRepository()->getForOrder( $order->get_id(), null,
					null, 'admin-view' );
				$totalMessages = $this->getContainer()->getMessageRepository()->getTotalForOrder( $order->get_id(),
					'admin-view' );
			} catch ( Exception $e ) {
				$messages      = array();
				$totalMessages = 0;
			}

			$this->getContainer()->getMessageRepository()->makeMessagesAsReadForOrder( $orderId, 'admin' );

			?>
			<div class="om-order-modal-messenger" data-order-messenger>
				<?php
				$this->getContainer()->getFileManager()->includeTemplate( 'admin/order/messenger-metabox.php', array(
					'orderId'       => $order->get_id(),
					'messages'      => $messages,
					'totalMessages' => $totalMessages,
				) );
				?>
				<button title="Close (Esc)" type="button" class="mfp-close">×</button>
			</div>
			<?php
		}

		die;
	}
}
