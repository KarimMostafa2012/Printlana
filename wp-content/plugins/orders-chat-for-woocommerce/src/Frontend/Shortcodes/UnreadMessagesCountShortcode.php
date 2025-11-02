<?php namespace U2Code\OrderMessenger\Frontend\Shortcodes;

use Exception;
use U2Code\OrderMessenger\Core\ServiceContainerTrait;
use U2Code\OrderMessenger\Entity\MessageType;

class UnreadMessagesCountShortcode {

	use ServiceContainerTrait;

	const TAG = 'order_chatroom_unread_messages_count';

	public function __construct() {
		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	public function render( $args ) {
		$args = wp_parse_args( $args, array(
			'user_id'  => get_current_user_id(),
			'order_id' => 0,
		) );

		$user = new \WP_User( $args['user_id'] );

		if ( $user->ID ) {
			try {

				if ( $args['order_id'] > 0 ) {
					$messagesCount = $this->getContainer()->getMessageRepository()->getUnreadCountForOrder( $args['order_id'], MessageType::ADMIN );
				} else {
					$messagesCount = $this->getContainer()->getMessageRepository()->getUnreadMessagesCountForUser( $user->ID );
				}

				return $messagesCount;

			} catch ( Exception $e ) {
				return '';
			}
		}

		return '';
	}
}
