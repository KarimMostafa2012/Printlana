<?php namespace U2Code\OrderMessenger\Settings;

use WC_Admin_Settings;

class ColorThemeOption {

	const FIELD_TYPE = 'oc_color_theme';

	public function __construct() {
		add_action( 'woocommerce_admin_field_' . self::FIELD_TYPE, array( $this, 'render' ) );
	}

	public function render( $value ) {

		$optionValue = $value['value'];

		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?><?php echo wc_help_tip( $value['desc'] ); ?></label>
			</th>
			<td class="forminp">

				<fieldset id="color-picker" class="scheme-list">
					<legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ); ?></span></legend>
					<?php foreach ( $this->getAvailableThemes() as $themeName => $theme ) : ?>
						<div class="color-option <?php echo $optionValue === $themeName ? 'selected' : ''; ?>">
							<input name="<?php echo esc_attr( $value['id'] ); ?>"
								   id="oc_theme_<?php echo esc_attr( $themeName ); ?>"
								   type="radio" value="<?php echo esc_attr( $themeName ); ?>"
								   class="tog"
								<?php checked( $optionValue, $themeName ); ?>>
							<label for="oc_theme_<?php echo esc_attr( $themeName ); ?>"><?php echo esc_html( $themeName ); ?></label>
							<table class="color-palette">
								<tbody>
								<tr>
									<?php foreach ( array_unique( $theme ) as $color ) : ?>
										<td style="background-color: <?php echo esc_html( $color ); ?>">&nbsp;</td>
									<?php endforeach; ?>
								</tr>
								</tbody>
							</table>
						</div>
					<?php endforeach; ?>
				</fieldset>
			</td>
		</tr>
		<?php
	}

	public function getAvailableThemes() {

		$availableThemes = array(
			'Default' => array(
				'--om-primary-text-color'     => '#000000',
				'--om-secondary-text-color'   => '#333333',
				'--om-secondary-color-darker' => '#676565',
				'--om-primary-color-darker'   => '#0d6b00',
				'--om-primary-color'          => '#c6e1c6',
				'--om-secondary-color'        => '#f5f5f5',
			),
			'Sea'     => array(
				'--om-secondary-text-color'   => '#276678',
				'--om-primary-color-darker'   => '#276678',
				'--om-primary-color'          => '#1687a7',
				'--om-secondary-color-darker' => '#9ca6ad',
				'--om-secondary-color'        => '#d3e0ea',
				'--om-primary-text-color'     => '#ffffff',
			),
			'Grackle' => array(
				'--om-primary-color-darker'   => '#000000',
				'--om-secondary-text-color'   => '#222831',
				'--om-primary-color'          => '#393e46',
				'--om-secondary-color-darker' => '#04767b',
				'--om-secondary-color'        => '#00adb5',
				'--om-primary-text-color'     => '#eeeeee',
			),
			'Rose'    => array(
				'--om-primary-color'          => '#311d3f',
				'--om-secondary-color-darker' => '#770113',
				'--om-primary-color-darker'   => '#984682',
				'--om-secondary-color'        => '#e23e57',
				'--om-primary-text-color'     => '#ffffff',
				'--om-secondary-text-color'   => '#ffffff',
			),
			'Sunset'  => array(
				'--om-primary-color-darker'   => '#bb5d33',
				'--om-primary-color'          => '#f08a5d',
				'--om-secondary-color-darker' => '#b9ab0e',
				'--om-secondary-color'        => '#f9ed69',
				'--om-primary-text-color'     => '#000000',
				'--om-secondary-text-color'   => '#000000',
			),
		);

		return apply_filters( 'order_messenger/settings/available_themes', $availableThemes );
	}
}
