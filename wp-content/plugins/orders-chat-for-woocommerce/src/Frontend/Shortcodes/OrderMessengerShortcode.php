<?php namespace U2Code\OrderMessenger\Frontend\Shortcodes;

use Exception;
use U2Code\OrderMessenger\Core\ServiceContainerTrait;

class OrderMessengerShortcode {

	use ServiceContainerTrait;

	const TAG = 'order_chatroom';

	public function __construct() {
		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	public function render( $args ) {

		global $wp;

		$args = wp_parse_args( $args, array(
			'user_id'  => get_current_user_id(),
			'order_id' => false,
		) );

		$user    = new \WP_User( $args['user_id'] );
		$orderId = isset( $args['order_id'] ) ? intval( $args['order_id'] ) : false;

		if ( ! $orderId && $wp ) {
			$orderId = $wp->query_vars['view-order'];
		}

		if ( $user->ID ) {
			$order = wc_get_order( $orderId );

			if ( $order ) {

				$userCanViewMessenger = apply_filters( 'order_messenger/permissions/userCanViewMessenger', $order->get_customer_id() === get_current_user_id(), $order );

				if ( ! $userCanViewMessenger ) {
					?>
					<div class="woocommerce-error"><?php esc_html_e( 'You have no enough access', 'order-messenger-for-woocommerce' ); ?>
						<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"
						   class="wc-forward"> <?php esc_html_e( 'My account', 'woocommerce' ); ?>
						</a>
					</div>
					<?php
				} else {

					wp_enqueue_script( 'om-messenger-script' );
					wp_enqueue_style( 'om-messenger-style' );

					try {
						$messages      = $this->getContainer()->getMessageRepository()->getForOrder( $orderId );
						$totalMessages = $this->getContainer()->getMessageRepository()->getTotalForOrder( $orderId );
					} catch ( Exception $e ) {
						$messages      = array();
						$totalMessages = 0;
					}

					$this->getContainer()->getFileManager()->includeTemplate( 'frontend/my-account/messenger.php', array(
						'messages'      => $messages,
						'orderId'       => $orderId,
						'fileManager'   => $this->getContainer()->getFileManager(),
						'totalMessages' => $totalMessages,
					) );
				}
			} else {
				?>
				<div class="woocommerce-error"><?php esc_html_e( 'Invalid order.', 'woocommerce' ); ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="wc-forward">
						<?php esc_html_e( 'My account', 'woocommerce' ); ?>
					</a>
				</div>
				<?php
			}
		}

		return '';
	}
}
