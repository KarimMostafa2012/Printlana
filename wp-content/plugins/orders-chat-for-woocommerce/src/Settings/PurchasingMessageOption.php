<?php namespace U2Code\OrderMessenger\Settings;

use WC_Admin_Settings;

class PurchasingMessageOption {

	const FIELD_TYPE = 'oc_purchasing_message';
	const FIELD_ID = 'purchasing_message';

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
		$tooltip_html = $field_description['tooltip_html'];
		$option_value = $value['value'];

		$option_value['enabled'] = isset( $option_value['enabled'] ) ? $option_value['enabled'] : 'yes';
		$option_value['send_on'] = isset( $option_value['send_on'] ) ? $option_value['send_on'] : 'order_placed';
		$visibility_class        = array();

		?>
		<tr valign="top" class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
			<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ); ?></span></legend>
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
							<span><?php esc_attr_e( 'Send message when', 'order-messenger-for-woocommerce' ); ?>:</span>
						</legend>

						<?php foreach ( $this->getMessageSendTriggers() as $trigger => $name ) : ?>
							<div>

								<input <?php echo checked( $option_value['send_on'], $trigger ); ?>
										type="radio"
										id="<?php echo esc_attr( $value['id'] . '-' . $trigger ); ?>"
										value="<?php echo esc_attr( $trigger ); ?>"
										name="<?php echo esc_attr( $value['id'] . '[send_on]' ); ?>"
								>
								<label for="<?php echo esc_attr( $value['id'] . '-' . $trigger ); ?>">
									<?php echo esc_html( $name ); ?>
								</label>
							</div>
						<?php endforeach; ?>
					</fieldset>
				</div>
			</td>
		</tr>
		<?php
	}

	public function getMessageSendTriggers() {
		return array(
			'order_placed'    => __( 'Order placed', 'order-messenger-for-woocommerce' ),
			'payment_success' => __( 'Successful payment', 'order-messenger-for-woocommerce' ),
		);
	}

	public function getWooCommerceArrayFormat() {
		return array(
			'title'    => __( 'Purchasing message', 'order-messenger-for-woocommerce' ),
			'id'       => Settings::SETTINGS_PREFIX . self::FIELD_ID,
			'type'     => self::FIELD_TYPE,
			'default'  => $this->getDefaults(),
			'desc'     => __( 'Enable sending purchase message functionality. You can set a message per product at the "Order Messenger" tab.',
				'order-messenger-for-woocommerce' ),
			'desc_tip' => true,
		);
	}

	public function getDefaults() {

		return array(
			'enabled' => 'yes',
			'send_on' => 'order_placed',
		);
	}

	public function sanitize( $value ) {

		$value['enabled'] = isset( $value['enabled'] ) ? 'yes' : 'no';
		$value['send_on'] = isset( $value['send_on'] ) && array_key_exists( $value['send_on'], $this->getMessageSendTriggers() ) ? $value['send_on'] : 'order_placed';

		return $value;
	}
}
