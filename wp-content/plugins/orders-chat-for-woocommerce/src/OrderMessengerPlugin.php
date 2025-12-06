<?php namespace U2Code\OrderMessenger;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use U2Code\OrderMessenger\Addons\ScheduleMessages;
use U2Code\OrderMessenger\Admin\Admin;
use U2Code\OrderMessenger\Config\Config;
use U2Code\OrderMessenger\Core\FileManager;
use U2Code\OrderMessenger\API\REST\MessagesREST;
use U2Code\OrderMessenger\Core\ServiceContainerTrait;
use U2Code\OrderMessenger\Database\Database;
use U2Code\OrderMessenger\Email\AdminMessagesNotificationEmail;
use U2Code\OrderMessenger\Email\CustomerMessagesNotification;
use U2Code\OrderMessenger\Entity\Message;
use U2Code\OrderMessenger\Entity\MessageType;
use U2Code\OrderMessenger\Frontend\Frontend;
use U2Code\OrderMessenger\Database\OrderMessagesTable;
use U2Code\OrderMessenger\Frontend\Shortcodes\MessengerShortcode;
use U2Code\OrderMessenger\Frontend\Shortcodes\OrderMessengerShortcode;
use U2Code\OrderMessenger\Frontend\Shortcodes\UnreadMessagesCountShortcode;
use U2Code\OrderMessenger\Repository\MessageRepository;
use U2Code\OrderMessenger\Settings\Settings;
use WC_Order;

/**
 * Class OrderMessengerPlugin
 *
 * @package U2Code\OrderMessenger
 */
class OrderMessengerPlugin {

	const VERSION = '1.2.0';

	use ServiceContainerTrait;

	/**
	 * OrderMessengerPlugin constructor.
	 *
	 * @param  string  $mainFile
	 */
	public function __construct( $mainFile ) {

		FileManager::init( $mainFile );

		add_action( 'plugins_loaded', array( $this, 'loadTextDomain' ) );

		add_filter( 'woocommerce_get_query_vars', function ( $vars ) {
			$vars[] = 'messages';
			$vars[] = 'messenger';

			return $vars;
		}, 0 );

		add_filter( 'woocommerce_email_classes', function ( $emails ) {

			$emails['OM_Admin_Notification_Email']    = new AdminMessagesNotificationEmail();
			$emails['OM_Customer_Notification_Email'] = new CustomerMessagesNotification();

			return $emails;

		} );

		add_action( 'init', function () {
			if ( get_option( 'om_need_flash_rewrite_rules' ) === 'yes' ) {

				flush_rewrite_rules();

				update_option( 'om_need_flash_rewrite_rules', 'no' );
			}
		} );

		add_action( 'before_woocommerce_init', function () use ( $mainFile ) {
			if ( class_exists( FeaturesUtil::class ) ) {
				FeaturesUtil::declare_compatibility( 'custom_order_tables', $mainFile, true );
			}
		} );
	}

	/**
	 * Run plugin part
	 */
	public function run() {

		$this->initContainer();

		if ( is_admin() ) {
			new Admin();
		} else {
			new Frontend();
		}

		new MessagesREST();

		if ( Config::isServiceNotesMessagesEnabled() ) {
			add_filter( 'woocommerce_new_order_note_data', function ( array $note, array $data ) {
				if ( $data['is_customer_note'] ) {

					$order = wc_get_order( $data['order_id'] );

					$message = new Message( $note['comment_content'], $data['order_id'], $order->get_customer_id(), 1,
						MessageType::service() );

					try {
						$message->save();
					} catch ( \Exception $e ) {
						wc_get_logger()->add( 'order_messenger__errors', $e->getMessage() );
					}
				}

				return $note;
			}, 2, 10 );

			if ( Config::isDisableWooCommerceOrderNotesEmailsEnabled() ) {
				add_filter( 'woocommerce_email_enabled_customer_note', '__return_false' );
			}
		}

		if ( Config::isOrderStatusChangingMessagesEnabled() ) {
			add_action( 'woocommerce_order_status_changed', function ( $orderId, $from, $to, WC_Order $order ) {

				$message = Message::createOrderStatusMessage( $orderId, $order->get_customer_id(), $from, $to );

				try {
					$message->save();
				} catch ( \Exception $e ) {
					wc_get_logger()->add( 'order_messenger__errors', $e->getMessage() );
				}
			}, 10, 4 );
		}

		add_filter( 'plugin_action_links_' . plugin_basename( $this->getContainer()->getFileManager()->getMainFile() ),
			function ( $actions ) {
				$actions[] = '<a href="' . $this->getContainer()->getSettings()->getLink() . '">' . __( 'Settings',
						'order-messenger-for-woocommerce' ) . '</a>';

				if ( ! omfw_fs()->is_anonymous() && omfw_fs()->is_installed_on_site() ) {
					$actions[] = '<a href="' . self::getAccountPageURL() . '"><b style="color: green">' . __( 'Account',
							'tier-pricing-table' ) . '</b></a>';
				}


				$actions[] = '<a href="' . self::getContactUsURL() . '"><b style="color: green">' . __( 'Contact Us',
						'tier-pricing-table' ) . '</b></a>';

				if ( ! omfw_fs()->is_premium() ) {
					$actions[] = '<a href="' . omfw_fs_activation_url() . '"><b style="color: red">' . __( 'Go Premium',
							'tier-pricing-table' ) . '</b></a>';
				}

				return $actions;
			}, 10, 4 );
	}

	public function initContainer() {

		$this->getContainer()->add( 'database', Database::getInstance() );
		$this->getContainer()->add( 'fileManager', FileManager::getInstance() );
		$this->getContainer()->add( 'messageRepository', new MessageRepository() );
		$this->getContainer()->add( 'settings', new Settings() );
		$this->getContainer()->add( 'productPageManager', new ProductManager() );
		$this->getContainer()->add( 'notificationManager', new NotificationManager() );

		$this->getContainer()->add( 'shortcode.unreadMessagesCount', new UnreadMessagesCountShortcode() );
		$this->getContainer()->add( 'shortcode.orderMessenger', new OrderMessengerShortcode() );
		$this->getContainer()->add( 'shortcode.chatrooms', new MessengerShortcode() );

		$this->getContainer()->add( 'addons.scheduleMessages', new ScheduleMessages() );

		do_action( 'order_messenger/container/main_services_init' );
	}

	/**
	 * Load plugin translations
	 */
	public function loadTextDomain() {
		$name = $this->getContainer()->getFileManager()->getPluginName();
		load_plugin_textdomain( 'order-messenger-for-woocommerce', false, $name . '/languages/' );
	}

	/**
	 * Fired when the plugin is activated
	 */
	public function activate() {
		update_option( 'om_need_flash_rewrite_rules', 'yes', true );
		set_transient( 'order_messenger_activated', true, 100 );

		OrderMessagesTable::create();
	}

	public static function getAccountPageURL() {
		return omfw_fs()->get_account_url();
		return admin_url( 'admin.php?page=order-messenger-for-woocommerce-account' );
	}

	public static function getContactUsURL() {
		return omfw_fs()->contact_url();
		return admin_url( 'admin.php?page=order-messenger-for-woocommerce-contact-us' );
	}

	/**
	 * Fired when the plugin is deactivated
	 */
	public function deactivate() {}

	/**
	 * Fired during plugin uninstall
	 */
	public static function uninstall() {
		OrderMessagesTable::delete();
	}
}
