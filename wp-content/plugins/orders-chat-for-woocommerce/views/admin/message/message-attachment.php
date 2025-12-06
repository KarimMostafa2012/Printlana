<?php defined( 'ABSPATH' ) || die;

use U2Code\OrderMessenger\Entity\Message;

/**
 * View variables
 *
 * @var Message $message
 * @var bool $is_last
 */

?>

<?php if ( $message->getAttachment() ) : ?>
	<div class="order-message-attachment">
		<?php if ( $message->getAttachment()->isValid() ) : ?>
			<a target="_blank"
			   href="<?php echo esc_attr( $message->getAttachment()->getURL() ); ?>"><?php echo esc_html( $message->getAttachment()->getName() ); ?></a>
		<?php else : ?>
			<p><?php esc_html_e( 'File is unavailable', 'order-messenger-for-woocommerce' ); ?></p>
		<?php endif; ?>
	</div>
<?php endif; ?>
