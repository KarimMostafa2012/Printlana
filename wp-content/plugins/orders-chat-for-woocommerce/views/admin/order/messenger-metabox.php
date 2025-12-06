<?php

defined( 'ABSPATH' ) || die;
use U2Code\OrderMessenger\API\REST\MessagesREST;
use U2Code\OrderMessenger\Config\Config;
use U2Code\OrderMessenger\Core\ServiceContainer;
use U2Code\OrderMessenger\Entity\Message;
use U2Code\OrderMessenger\Settings\EmailNotificationsOption;
/**
 * View variables
 *
 * @var int $orderId
 * @var int $totalMessages
 * @var Message[] $messages
 */
?>

<div class="order-messenger"
	 data-order-id="<?php 
echo esc_attr( $orderId );
?>"
	 data-nonce="<?php 
echo esc_attr( wp_create_nonce( 'wp_rest' ) );
?>"
	 data-url="<?php 
echo esc_attr( rest_url( MessagesREST::API_NAMESPACE ) );
?>"
	 data-limit="<?php 
echo esc_attr( Config::getPreloadMessagesCount() );
?>"
	 data-total="<?php 
echo esc_attr( $totalMessages );
?>">

	<div class="order-messenger__messages" data-order-messenger-messages-container>

		<?php 
if ( $totalMessages < 1 ) {
    ?>
			<p class="order-messenger__no-messages" data-messenger-no-messages>
				<?php 
    esc_html_e( 'There are no messages yet. Your message would be the first one.', 'order-messenger-for-woocommerce' );
    ?>
			</p>
		<?php 
} else {
    ?>

			<?php 
    foreach ( array_reverse( $messages ) as $message ) {
        ?>
				<?php 
        ServiceContainer::getInstance()->getFileManager()->includeTemplate( $message->getViewPath( 'admin' ), array(
            'message' => $message,
        ), true );
        ?>
			<?php 
    }
    ?>

		<?php 
}
?>
	</div>

	<div class="order-messenger__form">
		<hr>
		<div class="order-messenger__form-input">
			<label for="order-message-textarea">
				<?php 
esc_html_e( 'New message', 'order-messenger-for-woocommerce' );
?>
				<?php 
echo wc_help_tip( __( 'Send a new message to customer of this order (the user will be notified).', 'order-messenger-for-woocommerce' ) );
?>
			</label>
			<textarea minlength="2" maxlength="<?php 
echo esc_attr( Message::MAX_LENGTH );
?>" type="text"
					  class="input-text" cols="20"
					  rows="2" name="order-message"
					  data-order-message-textarea
					  id="order-message-textarea"></textarea>
		</div>

		<div class="order-messenger__form-buttons">
			<label class="screen-reader-text">
			<?php 
esc_html_e( 'Order message', 'order-messenger-for-woocommerce' );
?>
					</label>
			<div class="order-messenger__footer">

				<div class="order-messenger-attachment order-messenger-attachment--image"
					 data-order-messenger-attachment style="display: none">

					<div class="order-messenger-attachment-image">
						<img src="" data-order-messenger-attachment-image alt="">
						<div class="order-messenger-attachment-image__actions">
							<a href="#" class="order-messenger-attachment-remove"
							   title="<?php 
esc_attr_e( 'Delete image', 'order-messenger-for-woocommerce' );
?>"
							   data-order-messenger-attachment-remove-image>
							   <?php 
esc_attr_e( 'Delete', 'order-messenger-for-woocommerce' );
?>
									</a>
						</div>
					</div>

				</div>

				<div class="order-messenger-attachment order-messenger-attachment--file"
					 data-order-messenger-attachment style="display: none">
					<a href="#" class="order-messenger-attachment-remove"
					   data-order-messenger-attachment-remove-file></a>
					<a href="#" data-order-messenger-attachment-filename></a>
				</div>
				<?php 
?>
				<div>
					<input type="button" class="button" data-order-message-send
						   value="<?php 
esc_attr_e( 'Send', 'order-messenger-for-woocommerce' );
?>">
				</div>
			</div>
			<style>
				.order-messenger-additional_options {
					margin-top: 10px;
				}

				.order-messenger-additional_options__header {
					display: flex;
					justify-content: space-between;
					margin-top: 7px;
					cursor: pointer;
					font-weight: bold;
					font-size: .85em;
					background: #f8f8f8;
					padding: 7px 10px;
				}

				.order-messenger-additional_options__header:hover {
					background: #f5f5f5;
				}

				.order-messenger-additional_options__content {
					margin-top: 15px;
					display: none;
				}

				.order-messenger-additional_options--open .order-messenger-additional_options__content {
					display: block;
				}

				.order-messenger-additional_options--open .order-messenger-additional_options__header {
					background: #f5f5f5;
				}

				.order-messenger-additional_options--open .order-messenger-additional_options__header-arrow {
					transform: rotate(180deg);
				}
			</style>

			<script>
				jQuery(document).ready(function ($) {
					jQuery('.order-messenger-additional_options__header').click(function () {
						jQuery(this).parent('.order-messenger-additional_options').toggleClass('order-messenger-additional_options--open');
					});
				});
			</script>

			<div class="order-messenger-additional_options">
				<div class="order-messenger-additional_options__header">
					<div class="order-messenger-additional_options__header-title">
					<?php 
esc_html_e( 'Options', 'order-messenger-for-woocommerce' );
?>
							</div>
					<div class="order-messenger-additional_options__header-arrow">▼</div>
				</div>
				<div class="order-messenger-additional_options__content">

					<?php 
if ( EmailNotificationsOption::isNotificationsEnabled() ) {
    ?>
						<div>
							<input type="checkbox" name="order-messenger-custom-data[send_notification]"
								   id="order-messenger-send-notifications-checkbox"
								   data-order-message-send-notification checked>
							<label
								for="order-messenger-send-notifications-checkbox">
								<?php 
    esc_html_e( 'Send email notification', 'order-messenger-for-woocommerce' );
    ?>
									</label>
						</div>
					<?php 
}
?>

					<?php 
/**
 * Additional options
 *
 * @since 2.0.0
 */
do_action( 'order_messenger/admin/messenger_metabox/additional_options', $orderId, $messages );
?>
				</div>

			</div>
		</div>

		<div class="order-messenger__error">
			<div class="order-messenger-error" data-order-messenger-error>
				<p></p>
			</div>
		</div>

	</div>
</div>
