<?php namespace U2Code\OrderMessenger\Settings;

use WC_Admin_Settings;

class AllowedUserRolesToManageMessengerOption {

	const FIELD_TYPE = 'oc_allowed_user_roles';
	const FIELD_ID = 'allowed_user_roles_to_manage_messenger';

	public function __construct() {
		add_action( 'woocommerce_admin_field_' . self::FIELD_TYPE, array( $this, 'render' ) );
		add_action( 'woocommerce_admin_settings_sanitize_option_' . Settings::SETTINGS_PREFIX . self::FIELD_ID, array(
			$this,
			'sanitize'
		), 3, 10 );
	}

	public function render( $value ) {

		$option_value      = $value['value'];
		$visibility_class  = array();
		$option_value      = ! empty( $option_value ) ? (array) $option_value : $this->getDefaults();

		?>
		<tr valign="top" class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
			<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset style="display: flex; flex-wrap: wrap; gap: 10px">

					<?php foreach ( $this->getUserRoles() as $slug => $name ) : ?>
						<div style="padding: 5px 10px; border: 1px solid #ccc;">
							<label for="<?php echo esc_attr( $value['id'] . '-' . $slug ); ?>">
								<?php echo esc_html( $name ); ?>
							</label>
							<input <?php echo checked( in_array( $slug, $option_value ), true ); ?>
									type="checkbox"
									id="<?php echo esc_attr( $value['id'] . '-' . $slug ); ?>"
									value="1"
									name="<?php echo esc_attr( $value['id'] . '[' . $slug . ']' ); ?>"
							>
						</div>
					<?php endforeach; ?>
				</fieldset>
			</td>
		</tr>
		<?php
	}

	public function getUserRoles() {
		$roles = array();

		foreach ( wp_roles()->roles as $roleSlug => $role ) {
			$roles[ $roleSlug ] = translate_user_role( wp_roles()->role_names[ $roleSlug ] );
		}

		return apply_filters( 'order_messenger/settings/allowed_roles_to_manage_admin_messenger/available_roles', $roles );
	}

	public function getDefaults() {
		return apply_filters( 'order_messenger/settings/allowed_roles_to_manage_admin_messenger/default', array(
			'administrator',
			'shop_manager'
		) );
	}

	public function getWooCommerceArrayFormat() {
		return array(
			'title'    => __( 'Roles can send admin messages', 'order-messenger-for-woocommerce' ),
			'id'       => Settings::SETTINGS_PREFIX . self::FIELD_ID,
			'type'     => self::FIELD_TYPE,
			'default'  => $this->getDefaults(),
			'desc_tip' => false,
		);
	}

	public function sanitize( $value ) {
		$roles = array();

		foreach ( $this->getUserRoles() as $role => $roleName ) {
			if ( ! empty( $value[ $role ] ) ) {
				$roles[] = $role;
			}
		}

		return $roles;
	}
}
