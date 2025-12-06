<?php defined( 'ABSPATH' ) || die;

use U2Code\OrderMessenger\Entity\Message;
use U2Code\OrderMessenger\Services\TextPreprocessor;

/**
 * View variables
 *
 * @var Message $message
 * @var bool $is_last
 */

?>

<div class="om-message om-message--<?php echo esc_attr( $message->getMessageType()->getName() ); ?>">
	<div class="om-message__wrapper">
		<div class="om-message__body">
			<div class="om-message-text">
				<?php
				/* translators: $1: Service message */
				printf( esc_html__( ' — %s —', 'order-messenger-for-woocommerce' ), esc_html( TextPreprocessor::process( $message->getMessage() ) ) );
				?>
			</div>
			<span>
				<span class="om-message-date"
					  title="<?php echo esc_attr( $message->getDateSent()->format( 'Y-m-d H:i:s' ) ); ?>">
					<?php

					/* translators: $1: time ago */
					printf( esc_html__( '%s ago', 'order-messenger-for-woocommerce' ), esc_html( human_time_diff( time(), $message->getDateSent()->getTimestamp() ) ) );
					?>
				</span>
			</span>
		</div>
	</div>
</div>
