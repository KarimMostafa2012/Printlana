<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Available variables
 *
 * @var string $upgradeUrl
 * @var string $contactUsUrl
 */
?>
<div class="om-alert">

	<div class="om-alert__text">
		<div class="om-alert__inner">
			<?php
			esc_html_e( 'Upgrade your plan to unlock the great premium features.', 'tier-pricing-table' );
			?>
		</div>
	</div>

	<div class="om-alert__buttons">
		<div class="om-alert__inner">
			<a class="om-button om-button--accent om-button--bounce" target="_blank"
			   href="<?php echo esc_attr($upgradeUrl); ?>">
				<?php esc_html_e( 'Upgrade to Premium!', 'tier-pricing-table' ); ?>
			</a>
			<span style="font-weight: bold; color: #646970"> - or -</span>
			<a target="_blank" class="om-button om-button--secondary" href="<?php echo esc_attr( omfw_fs()->get_trial_url() ); ?>">
				<?php esc_html_e( 'Try trial', 'tier-pricing-table' ); ?>
			</a>
			<a target="_blank" class="om-button om-button--default" href="<?php echo esc_attr( $contactUsUrl); ?>">
				<?php esc_html_e( 'Contact Us!', 'tier-pricing-table' ); ?>
			</a>
		</div>
	</div>
</div>