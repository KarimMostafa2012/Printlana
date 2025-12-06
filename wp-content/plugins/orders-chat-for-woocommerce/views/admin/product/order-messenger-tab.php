<?php defined( 'ABSPATH' ) || die;

use U2Code\OrderMessenger\Entity\MessageAttachment;
use U2Code\OrderMessenger\Services\TextPreprocessor;

/**
 * View variables
 *
 * @var int $productId
 * @var string $message
 * @var int $attachmentId
 * @var MessageAttachment $attachment
 */

?>
<div id="order-messenger-data" class="panel woocommerce_options_panel">
	<?php do_action( 'order_messenger/admin/product_page/messenger_tab_begin', $productId ); ?>
	<fieldset class="form-field _om_purchase_message_field show_if_simple show_if_variable" data-om-purchase-message>
		<label style="margin: 9px 0 0 -150px;" for="_om_purchase_message">
			<?php esc_html_e( 'Purchasing message', 'order-messenger-for-woocommerce' ); ?>
		</label>

		<p>
			<textarea data-om-stater-message-textarea class="short" name="_om_purchase_message" style="height: 100px"
					  id="_om_purchase_message"
					  rows="3"
					  cols="20"><?php echo esc_html( TextPreprocessor::parseBreaksTextarea( $message ) ); ?></textarea>
			<input data-order-message-attach-file-id type="hidden" name="_om_purchase_attachment_id"
				   value="<?php echo esc_attr( $attachmentId ); ?>">
		</p>

		<p style="<?php echo $attachment ? 'display:none' : ''; ?>">
			<a href="#"
			   data-order-message-attach-file><?php esc_html_e( 'Attach file', 'order-messenger-for-woocommerce' ); ?></a>
		</p>

		<p class="order-messenger-attachment order-messenger-attachment--file"
		   data-order-messenger-attachment
		   style="<?php echo $attachment && ! $attachment->isImage() ? '' : 'display:none'; ?>">
			<a href="#" class="order-messenger-attachment-remove"
			   data-order-messenger-attachment-remove-file></a>
			<a target="_blank" href="<?php echo esc_attr( $attachment ? $attachment->getURL() : '#' ); ?>"
			   data-order-messenger-attachment-filename>
				<?php if ( $attachment && ! $attachment->isImage() ) : ?>
					<?php echo esc_html( $attachment->getName() ); ?>
				<?php endif; ?>
			</a>
		</p>

		<p class="order-messenger-attachment order-messenger-attachment--image"
		   data-order-messenger-attachment
		   style="<?php echo esc_attr( $attachment && ( $attachment->isImage() ) ? '' : 'display:none' ); ?>">

			<span style="display: block;" class="order-messenger-attachment-image">
				<img src="<?php echo esc_attr( $attachment && $attachment->isImage() ? $attachment->getImageSrc() : '#' ); ?>"
					 data-order-messenger-attachment-image>
				<span style="display: block;" class="order-messenger-attachment-image__actions">
					<a href="#" class="order-messenger-attachment-remove"
					   title="<?php esc_attr_e( 'Delete image', 'order-messenger-for-woocommerce' ); ?>"
					   data-order-messenger-attachment-remove-image><?php esc_attr_e( 'Delete', 'order-messenger-for-woocommerce' ); ?></a>
				</span>
			</span>

		</p>

		<p class="description"><?php esc_html_e( 'Message will be automatically sent once user purchase the product. Might be useful for instructions/guides/virtual products.', 'order-messenger-for-woocommerce' ); ?></p>
	</fieldset>

	<?php do_action( 'order_messenger/admin/product_page/messenger_tab_end', $productId ); ?>
</div>
<script>
	jQuery(document).ready(function ($) {
		var ProductPurchaseMessage = function (wrapper) {

			this.wrapper = wrapper;
			this.imageFrame = null;

			this.init = function () {
				this.wrapper.find('[data-order-message-attach-file]').on('click', this.selectFile.bind(this));
				this.wrapper.find('[data-order-messenger-attachment-remove-image]').on('click', this.removeAttachedImage.bind(this));
				this.wrapper.find('[data-order-messenger-attachment-remove-file]').on('click', this.removeAttachedFile.bind(this));
			}

			this.removeAttachedFile = function (e) {
				e.preventDefault();

				this.setAttachmentId(0);
				this.hideAttachedFile();
				this.showFileSelection();
			}

			this.removeAttachedImage = function (e) {
				e.preventDefault();

				this.setAttachmentId(0);
				this.hideAttachedImage();
				this.showFileSelection();
			}

			this.setAttachmentId = function (id) {
				this.wrapper.find('[data-order-message-attach-file-id]').val(id);
			}

			this.hideFileSelection = function () {
				this.wrapper.find('[data-order-message-attach-file]').parent().hide();
			}

			this.showFileSelection = function () {
				this.wrapper.find('[data-order-message-attach-file]').parent().show();
			}

			this.hideAttachedFile = function () {
				var $attachment = this.wrapper.find('[data-order-messenger-attachment]').filter('.order-messenger-attachment--file');
				$attachment.find('[data-order-messenger-attachment-filename]').text('');
				$attachment.find('[data-order-messenger-attachment-filename]').attr('src', '#');
				$attachment.hide();
			}

			this.showAttachedFile = function (fileName, fileSrc) {
				var $attachment = this.wrapper.find('[data-order-messenger-attachment]').filter('.order-messenger-attachment--file');
				$attachment.find('[data-order-messenger-attachment-filename]').text(fileName);
				$attachment.find('[data-order-messenger-attachment-filename]').attr('href', fileSrc);
				$attachment.show();
			}

			this.showAttachedImage = function (imageSrc) {
				var $attachment = this.wrapper.find('[data-order-messenger-attachment]').filter('.order-messenger-attachment--image');
				$attachment.find('[data-order-messenger-attachment-image]').attr('src', imageSrc);
				$attachment.show();
			}

			this.hideAttachedImage = function () {
				var $attachment = this.wrapper.find('[data-order-messenger-attachment]').filter('.order-messenger-attachment--image');
				$attachment.find('[data-order-messenger-attachment-image]').attr('src', '#');
				$attachment.hide();
			}

			this.onCloseMedia = function () {
				var selection = this.imageFrame.state().get('selection');

				selection.each((function (attachment) {
					if (attachment) {
						this.hideFileSelection();
						this.setAttachmentId(attachment.attributes.id);

						if (attachment.attributes.type === 'image') {
							this.showAttachedImage(attachment.attributes.url);
						} else {
							this.showAttachedFile(attachment.attributes.filename, attachment.attributes.url);
						}
					}
				}).bind(this));
			}

			this.selectFile = function (e) {
				e.preventDefault();

				if (this.imageFrame) {
					this.imageFrame.open();
				} else {

					this.imageFrame = wp.media({
						title: 'Select Media',
						multiple: false,
					});

					this.imageFrame.on('close', this.onCloseMedia.bind(this));
					this.imageFrame.open();
				}

			}
		}

		var productTab = new ProductPurchaseMessage($('[data-om-purchase-message]'));

		productTab.init();
	});
</script>
