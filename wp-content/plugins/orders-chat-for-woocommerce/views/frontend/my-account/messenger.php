<?php defined( 'ABSPATH' ) || die;

use U2Code\OrderMessenger\API\REST\MessagesREST;
use U2Code\OrderMessenger\Config\Config;
use U2Code\OrderMessenger\Core\ServiceContainer;
use U2Code\OrderMessenger\Entity\Message;

/**
 * View variables
 *
 * @var Message[] $messages
 * @var int $orderId
 * @var int $totalMessages
 */
?>

<div class="om-messenger" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>"
	 data-url="<?php echo esc_attr( rest_url( MessagesREST::API_NAMESPACE ) ); ?>"
	 data-order-id="<?php echo esc_attr( $orderId ); ?>"
	 data-limit="<?php echo esc_attr( Config::getPreloadMessagesCount() ); ?>"
	 data-total="<?php echo esc_attr( $totalMessages ); ?>"
	 data-max-file-size="<?php echo esc_attr( Config::getMaxFileSize( true ) ); ?>"
	 data-max-file-size-string="<?php echo esc_attr( size_format( Config::getMaxFileSize( true ) ) ); ?>"
>
	<div class="om-messenger__main">
		<div class="om-messages-wrapper">

			<?php if ( $totalMessages < 1 ) : ?>
				<p class="om-messenger__no-messages" data-messenger-no-messages>
					<?php esc_html_e('There are no messages yet. Your message would be the first one.', 'order-messenger-for-woocommerce' ); ?>
				</p>
			<?php else : ?>
				<?php foreach ( array_reverse( $messages ) as $key => $message ) : ?>
					<?php 
					ServiceContainer::getInstance()->getFileManager()->includeTemplate( $message->getViewPath(), array(
						'message' => $message,
					), true ); 
					?>
				<?php endforeach; ?>
			<?php endif; ?>

		</div>
	</div>
	<div class="om-messenger__footer">
		<form data-message-sending-form class="om-messenger-sending-form">

			<p>
				<textarea maxlength="<?php echo esc_attr( Message::MAX_LENGTH ); ?>" required data-message-textarea
						  cols="30" rows="3"></textarea>
			</p>

			<div class="om-messenger-sending-form__buttons">

				<?php if ( Config::isFilesEnabled() ) : ?>

					<div data-messenger-attach-file>
						<input data-messenger-fileinput type="file" hidden="hidden"
							   accept="<?php echo esc_attr( implode( ', ', array_column( Config::getEnabledFileFormats(), 'mime' ) ) ); ?>"
							   id="message-attachment-<?php echo esc_attr( $orderId ); ?>">
						<label for="message-attachment-<?php echo esc_attr( $orderId ); ?>"
							   class="om-messenger__attach-file">
							<?php esc_attr_e( 'Attach file', 'order-messenger-for-woocommerce' ); ?>
						</label>
					</div>

					<div data-messenger-attached-file style="display: none">
						<span data-messenger-attached-file-remove class="om-messenger__attached-remove"></span>
						<span data-messenger-attached-filename class="om-messenger__attached-filename"></span>
					</div>
				<?php endif; ?>

				<div>
					<button class="button" id="message-send-<?php echo esc_attr( $orderId ); ?>">
						<?php esc_attr_e( 'Send', 'order-messenger-for-woocommerce' ); ?>
					</button>
				</div>

			</div>

			<div class="om-messenger-sending-form__messages">
				<div class="om-messenger-sending-form-error" style="display:none;" data-messenger-error></div>
			</div>
		</form>
	</div>
</div>
