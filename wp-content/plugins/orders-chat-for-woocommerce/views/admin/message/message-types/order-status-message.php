<?php defined( 'ABSPATH' ) || die;

use U2Code\OrderMessenger\Entity\Message;

/**
 * View variables
 *
 * @var Message $message
 */
?>

<div class="order-message order-message--<?php echo esc_attr( $message->getMessageType()->getName() ); ?>"
	 data-message-id="<?php echo esc_attr( $message->getId() ); ?>">
	<div class="order-message__content">
		<?php
		$data           = $message->getData();
		$orderChangedTo = ! empty( $data['to'] ) ? (string) $data['to'] : 'undefined';
		/* translators: $1: order status */
		echo esc_html( sprintf( __( ' — Order status was changed to %s —', 'order-messenger-for-woocommerce' ), $orderChangedTo ) );
		?>
	</div>
	<p class="order-message-meta">
		<abbr class="exact-date"
			  title="<?php echo esc_attr( $message->getDateSent()->format( 'y-m-d H:i:s' ) ); ?>">
			<?php
			printf(
			/* translators: $1: Date created, $2 Time created */
				esc_html__( 'Sent on %1$s at %2$s ', 'order-messenger-for-woocommerce' ),
				esc_html( date_i18n( wc_date_format(), strtotime( $message->getDateSent()->format( 'y-m-d H:i:s' ) ) ) ),
				esc_html( date_i18n( wc_time_format(), strtotime( $message->getDateSent()->format( 'y-m-d H:i:s' ) ) ) )
			);
			?>
		</abbr>
	</p>
</div>
