<?php defined( 'ABSPATH' ) || die;

use U2Code\OrderMessenger\Config\Config;
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
				<?php if ( $message->getDateRead() && Config::isShowReadMessageCheckMark() ) : ?>
					<span title="
					<?php
					/* translators: $1: Date of message read */
					printf( esc_attr__( 'the message was read at %s' ), esc_html( $message->getDateRead()->format( 'Y-m-d H:i:s' ) ) );
					?>
					">✓</span>
				<?php endif; ?>
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
					esc_attr_e( 'by you', 'order-messenger-for-woocommerce' );
					?>
				</b>
			</small>
		</div>
	</div>
</div>
