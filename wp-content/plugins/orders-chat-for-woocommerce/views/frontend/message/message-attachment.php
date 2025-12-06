<?php defined( 'ABSPATH' ) || die;

use U2Code\OrderMessenger\Entity\Message;

/**
 * View variables
 *
 * @var Message $message
 */
?>

<?php if ( $message->getAttachment() ) : ?>
	<div class="om-message__attachment">

		<?php if ( $message->getAttachment()->isValid() ) : ?>

			<div class="om-message-attachment om-message-attachment--<?php echo esc_attr( $message->getAttachment()->isImage() ? 'image' : 'file' ); ?>">
				<?php if ( $message->getAttachment()->isImage() ) : ?>
					<a  class="no-lightbox" href="<?php echo esc_attr( $message->getAttachment()->getImageSrc( 'full' ) ); ?>">
						<img src="<?php echo esc_attr( $message->getAttachment()->getImageSrc() ); ?>"
								alt="<?php echo esc_attr( $message->getAttachment()->getName() ); ?>"
								title="<?php echo esc_attr( $message->getMessage() ); ?>"
								>
					</a>
				<?php else : ?>
					<a href="<?php echo esc_attr( $message->getAttachment()->getURL() ); ?>"
					   target="_blank"><?php echo esc_attr( $message->getAttachment()->getName() ); ?></a>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'File is unavailable', 'order-messenger-for-woocommerce' ); ?></p>
		<?php endif; ?>
	</div>
<?php endif; ?>
