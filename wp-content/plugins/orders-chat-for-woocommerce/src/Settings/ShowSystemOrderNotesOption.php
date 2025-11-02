<?php namespace U2Code\OrderMessenger\Settings;

use U2Code\OrderMessenger\Core\ServiceContainerTrait;
use WC_Admin_Settings;

class ShowSystemOrderNotesOption {

	use ServiceContainerTrait;

	const FIELD_TYPE = 'oc_show_system_order_notes';
	const FIELD_ID = 'show_system_order_notes';

	public function __construct() {
		add_action( 'woocommerce_admin_field_' . self::FIELD_TYPE, array( $this, 'render' ) );

		add_action( 'woocommerce_admin_settings_sanitize_option_' . Settings::SETTINGS_PREFIX . self::FIELD_ID, array(
			$this,
			'sanitize'
		), 3, 10 );
	}

	public function render( $value ) {

		$field_description = WC_Admin_Settings::get_field_description( $value );

		$description  = $field_description['description'];
		$option_value = (array) $value['value'];

		$option_value['enabled']                     = ! empty( $option_value['enabled'] ) ? $option_value['enabled'] : 'yes';
		$option_value['disable_system_notes_emails'] = ! empty( $option_value['disable_system_notes_emails'] ) ? $option_value['disable_system_notes_emails'] : 'yes';

		$visibility_class = array();
		?>
		<tr valign="top" class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
			<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>

					<label for="<?php echo esc_attr( $value['id'] ); ?>">
						<input
								name="<?php echo esc_attr( $value['id'] ); ?>[enabled]"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								type="checkbox"
								data-om-extra-settings-controll-checkbox
								class="<?php echo esc_attr( isset( $value['class'] ) ? $value['class'] : '' ); ?>"
								value="1"
							<?php checked( $option_value['enabled'], 'yes' ); ?>
						/> <?php echo esc_attr( $description ); ?>
					</label> <?php echo wc_help_tip( $value['desc'] ); ?>
				</fieldset>

				<div data-om-extra-settings>
					<fieldset>
						<legend style="margin: 10px 0">

						</legend>
						<?php foreach ( $this->getOptions() as $key => $option ) : ?>
							<div>
								<input <?php echo checked( $option_value[ $key ], 'yes' ); ?>
										type="checkbox"
										id="<?php echo esc_attr( $value['id'] . '-' . $key ); ?>"
										value="1"
										name="<?php echo esc_attr( $value['id'] . '[' . $key . ']' ); ?>"
								>
								<label for="<?php echo esc_attr( $value['id'] . '-' . $key ); ?>">
									<?php echo esc_attr( $option['title'] ); ?>
								</label>
							</div>
							<?php if ( $option['description'] ) : ?>
								<p class="description">
									<?php echo esc_html( $option['description'] ); ?>
								</p>
							<?php endif; ?>
						<?php endforeach; ?>
					</fieldset>
				</div>

			</td>
		</tr>
		<?php
	}

	public function getOptions() {
		return array(
			'disable_system_notes_emails' => array(
				'title'       => __( 'Disable WooCommerce customer system notes emails', 'order-messenger-for-woocommerce' ),
				'default'     => 'yes',
				'description' => __( 'By default, WooCommerce sends emails with system notes to customers. Check “Disable WooCommerce customer system notes emails” if you want to keep only message notifications.', 'order-messenger-for-woocommerce' ),
			)
		);
	}

	public function getWooCommerceArrayFormat() {
		return array(
			'title'    => __( 'System order notes as service messages', 'order-messenger-for-woocommerce' ),
			'id'       => Settings::SETTINGS_PREFIX . self::FIELD_ID,
			'type'     => self::FIELD_TYPE,
			'default'  => $this->getDefaults(),
			'desc'     => __( 'Your clients will receive service messages in the chatroom right after some trigger event happens. Might be useful with external plugins that push information such as shipping info, payment status, etc., to order notes.',
				'order-messenger-for-woocommerce' ),
			'desc_tip' => true,
		);
	}

	public function getDefaults() {

		return array(
			'enabled'                     => 'yes',
			'disable_system_notes_emails' => 'yes',
		);
	}

	public function sanitize( $value ) {

		$value['enabled']                     = isset( $value['enabled'] ) ? 'yes' : 'no';
		$value['disable_system_notes_emails'] = isset( $value['disable_system_notes_emails'] ) ? 'yes' : 'no';

		return $value;
	}
}
