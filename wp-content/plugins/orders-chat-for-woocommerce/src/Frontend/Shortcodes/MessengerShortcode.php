<?php namespace U2Code\OrderMessenger\Frontend\Shortcodes;

use Exception;
use U2Code\OrderMessenger\Core\ServiceContainerTrait;

class MessengerShortcode {

	use ServiceContainerTrait;

	const TAG = 'order_chatrooms';

	public function __construct() {
		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	public function render( $args ) {

		$args = wp_parse_args( $args, array(
			'limit'        => 10,
			'current_page' => 1,
		) );

		$limit       = $args['limit'];
		$currentPage = $args['current_page'];

		try {
			$totalMessages = $this->getContainer()->getMessageRepository()->getUserOrderDialogsCount( get_current_user_id() );

			if ( ! $totalMessages ) {
				?>
				<div class="woocommerce-info">
					<?php esc_html__( 'You don\'t have any messages yet.', 'order-messenger-for-woocommerce' ); ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="wc-forward">
						<?php esc_html_e( 'My account', 'woocommerce' ); ?>
					</a>
				</div>
				<?php
			} else {
				$messages = $this->getContainer()->getMessageRepository()->getUserOrderMessages( get_current_user_id(), $limit * ( $currentPage - 1 ), $limit );

				$this->getContainer()->getFileManager()->includeTemplate( 'frontend/my-account/dialogs.php', array(
					'messages'    => $messages,
					'totalPages'  => ceil( $totalMessages / $limit ),
					'currentPage' => $currentPage
				) );
			}

		} catch ( Exception $e ) {
			wc_get_logger()->add( 'order_messenger__errors', $e->getMessage() );
		}
	}
}
