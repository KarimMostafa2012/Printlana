<?php namespace U2Code\OrderMessenger\Settings;

use U2Code\OrderMessenger\Core\ServiceContainerTrait;
use U2Code\OrderMessenger\OrderMessengerPlugin;

/**
 * Class Settings
 *
 * @package Settings
 */
class Settings {

	use ServiceContainerTrait;

	const SETTINGS_PREFIX = 'order_messenger_';

	const SETTINGS_PAGE = 'order_messenger_settings';

	/**
	 * Array with the settings
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Settings constructor.
	 */
	public function __construct() {

		$this->initCustomOptions();
		$this->hooks();

		add_action( 'init', function () {
			$this->getAllSettings();
		} );
	}

	public function initCustomOptions() {
		$this->getContainer()->add( 'settings.colorThemeOption', new ColorThemeOption() );
		$this->getContainer()->add( 'settings.allowSendingFilesOption', new AllowSendingFilesOption() );
		$this->getContainer()->add( 'settings.purchasingMessageOption', new PurchasingMessageOption() );
		$this->getContainer()->add( 'settings.showSystemOrderNotesOption', new ShowSystemOrderNotesOption() );
		$this->getContainer()->add( 'settings.AllowedUserRolesToManageMessengerOption',
			new AllowedUserRolesToManageMessengerOption() );
		$this->getContainer()->add( 'settings.emailNotificationsOption', new EmailNotificationsOption() );
	}

	/**
	 * Handle updating settings
	 */
	public function updateSettings() {
		woocommerce_update_options( $this->settings );
	}

	/**
	 * Init all settings
	 */
	public function initSettings() {
		$user = new \WP_User( get_current_user_id() );

		if ( ! $user ) {
			return;
		}

		$settings = array(
			'settings'                                        => array(
				'title' => __( 'Order Messenger settings', 'order-messenger-for-woocommerce' ),
				'desc'  => __( 'This controls look and feel of Order Messenger at your store.',
					'order-messenger-for-woocommerce' ),
				'id'    => self::SETTINGS_PREFIX . 'main_settings',
				'type'  => 'title',
			),
			'color_theme'                                     => array(
				'title'    => __( 'Messenger color theme', 'order-messenger-for-woocommerce' ),
				'id'       => self::SETTINGS_PREFIX . 'color_theme',
				'type'     => ColorThemeOption::FIELD_TYPE,
				'default'  => 'Default',
				'desc'     => __( 'Colors will be applied at user\'s interface.', 'order-messenger-for-woocommerce' ),
				'desc_tip' => true,
			),
			'show_order_status_changing'                      => array(
				'title'    => __( 'Order status messages', 'order-messenger-for-woocommerce' ),
				'id'       => self::SETTINGS_PREFIX . 'show_order_status_changing',
				'type'     => 'checkbox',
				'default'  => 'yes',
				'desc'     => __( 'Show messages about changing orders status.', 'order-messenger-for-woocommerce' ),
				'desc_tip' => true,
			),
			'store_signature'                                 => array(
				'title'       => __( 'Custom "From" label for admin messages', 'order-messenger-for-woocommerce' ),
				'id'          => self::SETTINGS_PREFIX . 'store_signature',
				'type'        => 'text',
				'default'     => '',
				'placeholder' => $user->display_name,
				'desc'        => __( 'By default users see your name as message signature. You can set it to any string. For example "The best flower shop", etc.',
					'order-messenger-for-woocommerce' ),
				'desc_tip'    => false,
			),
			ShowSystemOrderNotesOption::FIELD_ID              => $this->getContainer()->get( 'settings.showSystemOrderNotesOption' )->getWooCommerceArrayFormat(),
			AllowSendingFilesOption::FIELD_ID                 => $this->getContainer()->get( 'settings.allowSendingFilesOption' )->getWooCommerceArrayFormat(),
			PurchasingMessageOption::FIELD_ID                 => $this->getContainer()->get( 'settings.purchasingMessageOption' )->getWooCommerceArrayFormat(),
			EmailNotificationsOption::FIELD_ID                => $this->getContainer()->get( 'settings.emailNotificationsOption' )->getWooCommerceArrayFormat(),
			'preloaded_messages_count'                        => array(
				'title'             => __( 'Preloaded messages count', 'order-messenger-for-woocommerce' ),
				'id'                => self::SETTINGS_PREFIX . 'preloaded_messages_count',
				'type'              => 'number',
				'custom_attributes' => array(
					'min'  => 10,
					'step' => 1,
				),
				'default'           => 20,
				'desc'              => __( 'Set how many messages should be preloaded.',
					'order-messenger-for-woocommerce' ),
				'desc_tip'          => false,
			),
			'show_messenger_link_as_order_action'             => array(
				'title'   => __( 'Order messenger link in order actions', 'order-messenger-for-woocommerce' ),
				'id'      => self::SETTINGS_PREFIX . 'show_messenger_link_as_order_action',
				'type'    => 'checkbox',
				'default' => 'yes',
				'desc'    => __( 'Show link to the messenger in "order actions" column at my-account.',
					'order-messenger-for-woocommerce' ),
			),
			'show_read_message_for_customers'                 => array(
				'title'    => __( 'Show read message check mark', 'order-messenger-for-woocommerce' ),
				'id'       => self::SETTINGS_PREFIX . 'show_read_message_for_customers',
				'type'     => 'checkbox',
				'default'  => 'yes',
				'desc'     => __( 'Show a check mark at messenger that message was read by an admin.',
					'order-messenger-for-woocommerce' ),
				'desc_tip' => false,
			),
			AllowedUserRolesToManageMessengerOption::FIELD_ID => $this->getContainer()->get( 'settings.AllowedUserRolesToManageMessengerOption' )->getWooCommerceArrayFormat(),
			'section_end'                                     => array(
				'type' => 'sectionend',
				'id'   => self::SETTINGS_PREFIX . 'settings_end',
			),

			array(
				'title' => __( 'Advanced settings', 'order-messenger-for-woocommerce' ),
				'id'    => self::SETTINGS_PREFIX . 'advanced_settings',
				'type'  => 'title',
			),

			array(
				'title'    => __( 'Scheduled messages', 'order-messenger-for-woocommerce' ),
				'id'       => self::SETTINGS_PREFIX . 'scheduled_messages_enabled',
				'type'     => 'checkbox',
				'default'  => 'yes',
				'desc'     => __( 'Enable scheduled messages functionality', 'order-messenger-for-woocommerce' ),
				'desc_tip' => true,
			),

			array(
				'type' => 'sectionend',
			),

		);

		$this->settings = apply_filters( 'order_messenger/settings/settings', $settings );
	}

	/**
	 * Register hooks
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'initSettings' ) );

		add_filter( 'woocommerce_settings_tabs_' . self::SETTINGS_PAGE, array( $this, 'registerSettings' ) );
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'addSettingsTab' ), 50 );
		add_action( 'woocommerce_update_options_' . self::SETTINGS_PAGE, array( $this, 'updateSettings' ) );

		if ( ! omfw_fs()->is_premium() ) {
			new PremiumSettingsManager();
		}
	}

	/**
	 * Add own settings tab
	 *
	 * @param  array  $settings_tabs
	 *
	 * @return mixed
	 */
	public function addSettingsTab( $settings_tabs ) {

		$settings_tabs[ self::SETTINGS_PAGE ] = __( 'Order Messenger', '' );

		return $settings_tabs;
	}

	/**
	 * Add settings to WooCommerce
	 */
	public function registerSettings() {

		wp_enqueue_script( 'om-admin-settings',
			$this->getContainer()->getFileManager()->locateAsset( 'admin/settings.js' ), [],
			OrderMessengerPlugin::VERSION );

		woocommerce_admin_fields( $this->settings );
	}

	/**
	 * Get setting by name
	 *
	 * @param  string  $option_name
	 * @param  mixed  $default
	 *
	 * @return mixed
	 */
	public function get( $option_name, $default = null ) {
		return get_option( self::SETTINGS_PREFIX . $option_name, $default );
	}


	public function getAllSettings() {

		$settings = array_filter( $this->settings, function ( $setting ) {
			return ! in_array( $setting['type'], array( 'section', 'sectionend', 'title' ) );
		} );

		return array_map( function ( $key, $value ) {
			return $this->get( $key, $value['default'] );
		}, array_keys( $settings ), $settings );

	}

	/**
	 * Get url to settings page
	 *
	 * @return string
	 */
	public function getLink() {
		return admin_url( 'admin.php?page=wc-settings&tab=' . self::SETTINGS_PAGE );
	}
}
