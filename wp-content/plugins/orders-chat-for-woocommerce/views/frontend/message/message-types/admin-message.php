<?php defined( 'ABSPATH' ) || die;

use U2Code\OrderMessenger\Core\ServiceContainer;
use U2Code\OrderMessenger\Entity\Message;
use U2Code\OrderMessenger\Services\TextPreprocessor;

/**
 * View variables
 *
 * @var Message $message
 */
?>
<div class="om-message om-message--<?php echo esc_attr( $message->getMessageType()->getName() ); ?>">
	<div class="om-message__wrapper">
		<div class="om-message__body">
			<div class="om-message-text">
				<?php TextPreprocessor::process( $message->getMessage(), true ); ?>
			</div>
			<?php ServiceContainer::getInstance()->getFileManager()->includeTemplate( 'frontend/message/message-attachment.php', array( 'message' => $message ) ); ?>
		</div>
		<div class="om-message__footer">
			<small>
				<abbr class="exact-date" style="border: none"
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

				<b>
					<?php
					/* translators: $1: Send by */
						echo esc_html( sprintf( __( 'by %s', 'order-messenger-for-woocommerce' ), $message->getSenderName() ) );
					?>
					</b>
			</small>
		</div>
	</div>
</div>
