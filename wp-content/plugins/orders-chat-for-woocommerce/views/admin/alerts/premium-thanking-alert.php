<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Available variables
 *
 * @var string $accountUrl
 * @var string $contactUsUrl
 */
?>
<div class="om-alert">

	<div class="om-alert__text">
		<div class="om-alert__inner">
			<?php esc_html_e( 'You are running premium version of the plugin!', 'tier-pricing-table' ); ?>
		</div>
	</div>

	<div class="om-alert__buttons">
		<div class="om-alert__inner">
			<a class="om-button om-button--accent" href="<?php echo esc_attr( $accountUrl ); ?>">
				<?php esc_html_e( 'My Account', 'tier-pricing-table' ); ?>
			</a>
			<a class="om-button om-button--default" href="<?php echo esc_attr( $contactUsUrl ); ?>">
				<?php esc_html_e( 'Contact us', 'tier-pricing-table' ); ?>
			</a>
		</div>
	</div>
</div>