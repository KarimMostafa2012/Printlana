<?php if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * Activation plugin message
 *
 * @var string $link
 */
?>

<div id="message" class="updated notice is-dismissible">
	<p>
		<strong>
			<?php esc_attr_e( 'Thanks for installing Order Messenger for WooCommerce! You can customize it ', 'order-messenger-for-woocommerce' ); ?>
			<a href="<?php echo esc_url( $link ); ?>"><?php esc_attr_e( 'here', 'order-messenger-for-woocommerce' ); ?></a>
		</strong>
	</p>
</div>
