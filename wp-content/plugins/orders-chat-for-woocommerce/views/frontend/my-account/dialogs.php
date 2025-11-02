<?php defined( 'ABSPATH' ) || die;

use U2Code\OrderMessenger\Core\ServiceContainer;
use U2Code\OrderMessenger\Entity\Message;
use U2Code\OrderMessenger\Entity\MessageType;
use U2Code\OrderMessenger\Services\TextPreprocessor;

/**
 * View variables
 *
 * @var Message[] $messages
 * @var int $totalPages
 * @var int $currentPage
 */
?>

<table class="om-dialogs-table shop_table shop_table_responsive my_account_orders">
	<thead>
	<tr>
		<th class="om-dialogs-table__header om-dialogs-table__header-order-number">
			<span class="nobr"><?php esc_html_e( 'Order', 'order-messenger-for-woocommerce' ); ?></span>
		</th>

		<th class="om-dialogs-table__header om-dialogs-table__header-dialogs-last-message">
			<span class="nobr"><?php esc_html_e( 'Last message', 'order-messenger-for-woocommerce' ); ?></span>
		</th>
		<th class="om-dialogs-table__header om-dialogs-table__header-dialogs-date">
			<span class="nobr"><?php esc_html_e( 'Date', 'order-messenger-for-woocommerce' ); ?></span>
		</th>
		<th class="om-dialogs-table__header om-dialogs-table__header-dialogs-actions">
			<span class="nobr"></span>
		</th>
	</tr>
	</thead>

	<tbody>
	<?php foreach ( $messages as $message ) : ?>
		<tr class="om-dialogs-table__row om-dialogs-table__row--status-on-hold order">
			<td class="om-dialogs-table__cell om-dialogs-table__cell-order-number" data-title="Order">
				<a href="<?php echo esc_attr( wc_get_account_endpoint_url( 'view-order' ) . $message->getOrderId() ); ?>">
					#<?php echo esc_attr( $message->getOrderId() ); ?> </a>
			</td>
			<td class="om-dialogs-table__cell om-dialogs-table__cell-last-message" data-title="Last message">
				<?php if ( $message->getMessageType()->isCustomer() ) : ?>
					<b><?php esc_html_e( 'You', 'order-messenger-for-woocommerce' ); ?>:</b>
					<?php TextPreprocessor::process( wp_trim_words( substr( $message->getMessage(), 0, 100 ), 10, '...' ), true ); ?>
				<?php elseif ( $message->getMessageType()->isOrderStatus() ) : ?>
					<b><i><?php esc_html_e( 'Order status changed', 'order-messenger-for-woocommerce' ); ?></i></b>
				<?php else : ?>
					<b><?php echo esc_html( $message->getSenderName() ); ?>:</b>
					<?php echo esc_html( $message->getMessage() ); ?>
				<?php endif; ?>
			</td>
			<td class="om-dialogs-table__cell om-dialogs-table__cell-message-date" data-title="Date">
				<time datetime="<?php echo esc_attr( $message->getDateSent()->format( 'Y-m-d H:i:s' ) ); ?>"><?php echo esc_html( $message->getDateSent()->format( wc_date_format() ) ); ?></time>
			</td>
			<td class="om-dialogs-table__cell om-dialogs-table__cell-actions" data-title="Actions">
				<?php
				try {
					$unreadCount = ServiceContainer::getInstance()->getMessageRepository()->getUnreadCountForOrder( $message->getOrderId(), MessageType::ADMIN );
				} catch ( Exception $e ) {
					$unreadCount = 0;
				}
				?>

				<a href="<?php echo esc_attr(wc_get_endpoint_url( 'messenger', $message->getOrderId(), wc_get_page_permalink( 'myaccount' ) ) ); ?>"
				   class="woocommerce-button button view">
					<?php esc_html_e( 'View', 'order-messenger-for-woocommerce' ); ?>
					<?php if ( $unreadCount > 0 ) : ?>
						<span class="om-new-messages-count">+ <?php echo esc_attr( $unreadCount ); ?></span>
					<?php endif; ?>
				</a>

			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>

<?php if ( 1 < $totalPages ) : ?>
	<div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
		<?php if ( 1 !== $currentPage ) : ?>
			<a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button"
			   href="<?php echo esc_url( wc_get_endpoint_url( 'messages', $currentPage - 1 ) ); ?>"><?php esc_html_e( 'Previous', 'woocommerce' ); ?></a>
		<?php endif; ?>

		<?php if ( intval( $totalPages ) !== $currentPage ) : ?>
			<a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button"
			   href="<?php echo esc_url( wc_get_endpoint_url( 'messages', $currentPage + 1 ) ); ?>"><?php esc_html_e( 'Next', 'woocommerce' ); ?></a>
		<?php endif; ?>
	</div>
<?php endif; ?>
