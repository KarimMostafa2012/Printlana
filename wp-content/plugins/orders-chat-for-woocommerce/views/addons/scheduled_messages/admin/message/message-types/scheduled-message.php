<?php defined( 'ABSPATH' ) || die;

use U2Code\OrderMessenger\Core\ServiceContainer;
use U2Code\OrderMessenger\Entity\Message;
use U2Code\OrderMessenger\Services\TextPreprocessor;

/**
 * View variables
 *
 * @var Message $message
 */

$data = $message->getData();

$scheduled_date = isset( $data['scheduled_date'] ) ? $data['scheduled_date'] : false;
$scheduled_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $scheduled_date );


if ( ! $scheduled_date ) {
	return;
}

?>

<div class="order-message order-message--<?php echo esc_attr( $message->getMessageType()->getName() ); ?>"
	 data-message-id="<?php echo esc_attr( $message->getId() ); ?>">
	<div class="order-message__content">

		<?php
		TextPreprocessor::process( $message->getMessage(), true );

		ServiceContainer::getInstance()->getFileManager()->includeTemplate( 'admin/message/message-attachment.php', array( 'message' => $message ) );
		?>
		<br>
		<br>
		<b><?php esc_html_e( 'Scheduled', 'order-messenger-for-woocommerce' ); ?></b>
		<br>
		<abbr class="exact-date" style="text-decoration: none"
			  title="<?php echo esc_attr( $message->getDateSent()->format( 'y-m-d H:i:s' ) ); ?>">
			<?php

			printf(
			/* translators: $1: Date created, $2 Time created */
				esc_html__( '%1$s at %2$s ', 'order-messenger-for-woocommerce' ),
				esc_html( date_i18n( wc_date_format(), strtotime( $scheduled_date->format( 'y-m-d H:i:s' ) ) ) ),
				esc_html( date_i18n( wc_time_format(), strtotime( $scheduled_date->format( 'y-m-d H:i:s' ) ) ) )
			);
			?>
		</abbr>
	</div>
	<p class="order-message-meta">
		<?php if ( $message->getDateRead() ) : ?>
			<span title="
			<?php
			/* translators: $1: Date of message read */
			printf( esc_attr__( 'The message was read at %s' ), esc_html( $message->getDateRead()->format( 'Y-m-d H:i:s' ) ) );
			?>
			">✓</span>
		<?php endif; ?>
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

		<span>
		<?php
		/* translators: $1: Send by */
		echo esc_html( sprintf( __( 'by %s', 'order-messenger-for-woocommerce' ), $message->getSenderName() ) );
		?>
			</span>
		<a href="#" role="button" class="order-message-meta__delete"
		   data-delete-message="<?php echo esc_attr( $message->getId() ); ?>">
			<?php esc_attr_e( 'Delete message', 'order-messenger-for-woocommerce' ); ?>
		</a>
	</p>
</div>
