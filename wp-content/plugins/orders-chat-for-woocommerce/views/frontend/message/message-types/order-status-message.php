<?php defined( 'ABSPATH' ) || die;

use U2Code\OrderMessenger\Entity\Message;

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

				$data           = $message->getData();
				$orderChangedTo = ! empty( $data['to'] ) ? (string) $data['to'] : 'undefined';

				/* translators: $1: Order status */
				printf( esc_html__( ' — Order status was changed to %s —', 'order-messenger-for-woocommerce' ), esc_html($orderChangedTo) )
				?>

			</div>
			<span>
				<span class="om-message-date"
					  title="<?php echo esc_attr( $message->getDateSent()->format( 'Y-m-d H:i:s' ) ); ?>">
					<?php

					/* translators: $1: time ago */
					printf( esc_html__( '%s ago', 'order-messenger-for-woocommerce' ), esc_html(human_time_diff( time(), $message->getDateSent()->getTimestamp() ) ) );
					?>
				</span>
			</span>
		</div>
	</div>
</div>
