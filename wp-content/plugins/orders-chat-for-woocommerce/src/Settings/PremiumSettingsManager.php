<?php namespace U2Code\OrderMessenger\Settings;

use U2Code\OrderMessenger\Core\ServiceContainerTrait;

/**
 * Class PremiumSettingsManager
 *
 */
class PremiumSettingsManager {

	use ServiceContainerTrait;

	public $premiumSubsections = array(

	);

	public function __construct() {
		add_action( 'woocommerce_settings_' . Settings::SETTINGS_PAGE, array( $this, 'scripts' ) );
	}

	protected function getConfig() {
		return array(
			'premiumSubsections' => $this->premiumSubsections,
		);
	}

	public function scripts() {


		?>
		<script>
			jQuery(document).ready(function ($) {

				const config = JSON.parse('<?php echo json_encode( $this->getConfig() ); ?>');

				$.each($('[data-order-messenger-premium-option]'), function (i, el) {

					const row = $(el).closest('tr');

					if (!row) {
						return;
					}
					const $premiumLabel = jQuery('<span>');
					$premiumLabel.addClass('om_premium_option_label');
					$premiumLabel.text('Only for premium version');

					row.find('th').append($premiumLabel);
					row.find('td').addClass('om_premium_option');

				});

				config.premiumSubsections.forEach(function (subsectionId) {

					const $subsection = $('#' + subsectionId);
					const $title = $subsection.prev('h2');

					const $premiumLabel = jQuery('<span>');
					$premiumLabel.addClass('om_premium_subsection_label');
					$premiumLabel.text('Only for premium version');

					$title.append($premiumLabel);

					$subsection.next('table').addClass('om_premium_subsection');
				});

			});
		</script>
		<?php
	}
}