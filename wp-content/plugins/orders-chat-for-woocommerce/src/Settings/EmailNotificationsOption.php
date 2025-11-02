<?php namespace U2Code\OrderMessenger\Settings;

use U2Code\OrderMessenger\Core\ServiceContainer;
use WC_Admin_Settings;

class EmailNotificationsOption {

	const FIELD_TYPE = 'oc_email_notification';
	const FIELD_ID = 'email_notification';

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
		$option_value = $value['value'];

		$option_value['enabled'] = isset( $option_value['enabled'] ) ? $option_value['enabled'] : 'yes';
		$option_value['type']    = isset( $option_value['type'] ) ? $option_value['type'] : 'group';
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
							<span><?php esc_attr_e( 'Notifications type', 'order-messenger-for-woocommerce' ); ?>:</span>
						</legend>

						<?php foreach ( $this->getMessageSendTriggers() as $trigger => $name ) : ?>
							<div>

								<input <?php echo checked( $option_value['type'], $trigger ); ?>
										type="radio"
										id="<?php echo esc_attr( $value['id'] . '-' . $trigger ); ?>"
										value="<?php echo esc_attr( $trigger ); ?>"
										name="<?php echo esc_attr( $value['id'] . '[type]' ); ?>"
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
			'group'  => __( 'Group messages in one email notification and send with a delay', 'order-messenger-for-woocommerce' ),
			'single' => __( 'Send email notification immediately for each message', 'order-messenger-for-woocommerce' )
		);
	}

	public function getWooCommerceArrayFormat() {
		return array(
			'title'    => __( 'Email notifications', 'order-messenger-for-woocommerce' ),
			'id'       => Settings::SETTINGS_PREFIX . self::FIELD_ID,
			'type'     => self::FIELD_TYPE,
			'default'  => self::getDefaults(),
			'desc'     => __( 'Email notifications for new messages', 'order-messenger-for-woocommerce' ),
			'desc_tip' => true,
		);
	}

	public static function getDefaults() {

		return array(
			'enabled' => 'yes',
			'type'    => 'group',
		);
	}

	public function sanitize( $value ) {

		$value['enabled'] = isset( $value['enabled'] ) ? 'yes' : 'no';
		$value['type']    = isset( $value['type'] ) && array_key_exists( $value['type'], $this->getMessageSendTriggers() ) ? $value['type'] : 'group';

		return $value;
	}

	public static function getValue() {
		return get_option( Settings::SETTINGS_PREFIX . 'email_notification', self::getDefaults() );
	}

	public static function isNotificationsEnabled() {
		return 'yes' === self::getValue()['enabled'];
	}

	public static function getNotificationsType() {
		return self::getValue()['type'];
	}
}
