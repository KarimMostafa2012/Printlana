<?php namespace U2Code\OrderMessenger\Frontend;

use Exception;
use U2Code\OrderMessenger\Config\Config;
use U2Code\OrderMessenger\Core\ServiceContainerTrait;
use U2Code\OrderMessenger\OrderMessengerPlugin;

/**
 * Class Frontend
 *
 * @package U2Code\OrderMessenger\Frontend
 */
class Frontend {

	use ServiceContainerTrait;

	/**
	 * Frontend constructor.
	 *
	 * @throws Exception
	 */
	public function __construct() {

		$this->getContainer()->add( 'accountManager', new AccountManager() );

		add_action( 'wp_enqueue_scripts', function () {
			wp_register_script( 'simplelightbox-js', $this->getContainer()->getFileManager()->locateAsset( 'frontend/simplelightbox.min.js' ), array( 'jquery' ), OrderMessengerPlugin::VERSION );
			wp_register_style( 'simplelightbox-css', $this->getContainer()->getFileManager()->locateAsset( 'frontend/simplelightbox.min.css' ), array(), OrderMessengerPlugin::VERSION );

			wp_register_script( 'om-messenger-script', $this->getContainer()->getFileManager()->locateAsset( 'frontend/messenger.js' ), array(
				'jquery',
				'simplelightbox-js'
			), OrderMessengerPlugin::VERSION );
			wp_register_style( 'om-messenger-style', $this->getContainer()->getFileManager()->locateAsset( 'frontend/messenger.css' ), array( 'simplelightbox-css' ), OrderMessengerPlugin::VERSION );

			wp_enqueue_style( 'om-frontend', $this->getContainer()->getFileManager()->locateAsset( 'frontend/frontend.css' ), array(), OrderMessengerPlugin::VERSION );
		} );


		add_action( 'wp_head', function () {
			?>
			<style>
				:root {
				<?php
				foreach ( Config::getCurrentMessengerThemeColors() as $var => $color) {
					echo esc_html($var . ': ' . $color . ";\n");
				}
				?>
				}
			</style>
			<?php
		} );
	}

}
