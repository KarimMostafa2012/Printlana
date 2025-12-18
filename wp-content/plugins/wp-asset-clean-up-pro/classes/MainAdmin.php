<?php
namespace WpAssetCleanUp;

/**
 * Class MainAdmin
 *
 * This class has functions that are only for the admin's concern
 *
 * @package WpAssetCleanUp
 */
class MainAdmin
{
	/**
	 * @var MainAdmin|null
	 */
	private static $singleton;

	/**
	 * @return null|MainAdmin
	 */
	public static function instance()
    {
		if ( self::$singleton === null ) {
			self::$singleton = new self();
		}

		return self::$singleton;
	}

	/**
	 * Parser constructor.
	 */
	public function __construct()
    {
	    add_action( 'admin_footer', array( $this, 'adminFooter' ) );
	    add_action( 'wp_ajax_' . WPACU_PLUGIN_ID . '_fetch_active_plugins_icons', array( $this, 'ajaxFetchActivePluginsIconsFromWordPressOrg' ) );

	    // This is triggered AFTER "saveSettings" from 'Settings' class
	    // In case the settings were just updated, the script will get the latest values
	    add_action( 'init', array( $this, 'triggersAfterInit' ));

	    $this->wpacuHtmlNoticeForAdmin();
    }

	/**
	 * @return void
	 */
	public function ajaxFetchActivePluginsIconsFromWordPressOrg()
	{
		if ( ! isset($_POST['wpacu_nonce']) ) {
			echo 'Error: The security nonce was not sent for verification. Location: '.__METHOD__;
			return;
		}

		if ( ! wp_verify_nonce($_POST['wpacu_nonce'], 'wpacu_fetch_active_plugins_icons') ) {
			echo 'Error: The security check has failed. Location: '.__METHOD__;
			return;
		}

		if (! isset($_POST['action']) || ! Menu::userCanManageAssets()) {
			return;
		}

		echo 'POST DATA: '.print_r($_POST, true)."\n\n";

		echo '- Downloading from WordPress.org'."\n\n";

		$activePluginsIcons = Misc::fetchActiveFreePluginsIconsFromWordPressOrg();

		if (is_array($activePluginsIcons) && ! empty($activePluginsIcons)) {
			echo print_r($activePluginsIcons, true)."\n";
			exit;
		}
	}

    /**
     * @return void
     */
    public function adminFooter()
    {
        // Only trigger it within the Dashboard when an Asset CleanUp (Pro) page is accessed and the transient is non-existent or expired
        $this->ajaxFetchActivePluginsJsFooterCode();
    }

	/**
	 *
	 */
	public function ajaxFetchActivePluginsJsFooterCode()
	{
		if (! Menu::isPluginPage() || ! Menu::userCanManageAssets()) {
			return;
		}

		$forcePluginIconsDownload = isset($_GET['wpacu_force_plugin_icons_fetch']);

		$triggerPluginIconsDownload = $forcePluginIconsDownload || ! get_transient('wpacu_active_plugins_icons');

		if (! $triggerPluginIconsDownload) {
			return;
		}
		?>
		<script type="text/javascript" >
            jQuery(document).ready(function($) {
                let wpacuDataToSend = {
                    'action': '<?php echo WPACU_PLUGIN_ID.'_fetch_active_plugins_icons'; ?>',
                    'wpacu_nonce': '<?php echo wp_create_nonce('wpacu_fetch_active_plugins_icons'); ?>'
                };

                $.post(ajaxurl, wpacuDataToSend, function(response) {
                    console.log(response);
                });
            });
		</script>
		<?php
	}

	/**
	 *
	 */
	public function triggersAfterInit()
	{
        Main::instance()->loadAllSettings();

        $metaboxes = new MetaBoxes;

        // Do not load the meta box nor do any AJAX calls
        // if the asset management is not enabled for the Dashboard
        if ( Main::instance()->settings['dashboard_show'] == 1 ) {
            // Send an AJAX request to get the list of loaded scripts and styles and print it nicely
            add_action( 'wp_ajax_' . WPACU_PLUGIN_ID . '_get_loaded_assets', array( $this, 'ajaxGetJsonListCallback' ) );

            // This is valid when the Gutenberg editor (not via "Classic Editor" plugin) is used and the user used the following option:
            // "Do not load Asset CleanUp Pro on this page (this will disable any functionality of the plugin)"
            add_action( 'wp_ajax_' . WPACU_PLUGIN_ID . '_load_page_restricted_area', array( $this, 'ajaxLoadRestrictedPageAreaCallback' ) );
        }

        // If assets management within the Dashboard is not enabled, an explanation message will be shown within the box unless the meta box is hidden completely
        if ( Main::instance()->settings['show_assets_meta_box'] ) {
            $metaboxes->initMetaBox( 'manage_page_assets' );
        }

        }

	/**
	 *
	 */
	public function ajaxGetJsonListCallback()
	{
		if ( ! isset($_POST['wpacu_nonce']) ) {
			echo 'Error: The security nonce was not sent for verification. Location: '.__METHOD__;
			return;
		}

		if ( ! wp_verify_nonce($_POST['wpacu_nonce'], 'wpacu_ajax_get_loaded_assets_nonce') ) {
			echo 'Error: The nonce security check has failed. Location: '.__METHOD__;
			return;
		}

		$postId  = (int)Misc::getVar('post', 'post_id'); // if any (could be home page for instance)
		$pageUrl = Misc::getVar('post', 'page_url'); // post, page, custom post type, home page etc.

		$postStatus = $postId > 0 ? get_post_status($postId) : false;

		// Not homepage, but a post/page? Check if it's published in case AJAX call
		// wasn't stopped due to JS errors or other reasons
		if ($postId > 0 && ! in_array($postStatus, array('publish', 'private'))) {
			exit(__('The CSS/JS files will be available to manage once the post/page is published.', 'wp-asset-clean-up'));
		}

		if ($postId > 0) {
			$type = 'post';
		}

		// [wpacu_pro]
        elseif (Misc::getVar('post', 'tag_id')) {
			$type = 'for_pro';
		}
		// [/wpacu_pro]

        elseif ($postId == 0) {
			$type = 'front_page';
		}

		$wpacuListE = $wpacuListH = '';

		$settings = new Settings();

		// If the post status is 'private' only direct method can be used to fetch the assets
		// as the remote post one will return a 404 error since the page is accessed as a guest visitor
		if (Main::$domGetType === 'direct' || $postStatus === 'private') {
			$wpacuListE = Misc::getVar('post', 'wpacu_list_e');
			$wpacuListH = Misc::getVar('post', 'wpacu_list_h');
		} elseif (Main::$domGetType === 'wp_remote_post') {
			$wpRemotePost = wp_remote_post($pageUrl, array(
				'body' => array(
					WPACU_LOAD_ASSETS_REQ_KEY => 1
				)
				));

			$contents = (is_array($wpRemotePost) && isset($wpRemotePost['body']) && (! is_wp_error($wpRemotePost))) ? $wpRemotePost['body'] : '';

			// Enqueued List
			if ($contents
			    && ( strpos($contents, Main::START_DEL_ENQUEUED) !== false)
			    && ( strpos($contents, Main::END_DEL_ENQUEUED) !== false)) {
				// Enqueued CSS/JS (most of them or all)
				$wpacuListE = Misc::extractBetween(
					$contents,
					Main::START_DEL_ENQUEUED,
					Main::END_DEL_ENQUEUED
				);
			}

			// Hardcoded List
			if ($contents
			    && ( strpos($contents, Main::START_DEL_HARDCODED) !== false)
			    && ( strpos($contents, Main::END_DEL_HARDCODED) !== false)) {
				// Hardcoded (if any)
				$wpacuListH = Misc::extractBetween(
					$contents,
					Main::START_DEL_HARDCODED,
					Main::END_DEL_HARDCODED
				);
			}

			// The list of assets COULD NOT be retrieved via "WP Remote POST" for this server
			// EITHER the enqueued or hardcoded list of assets HAS TO BE RETRIEVED
			// Print out the 'error' response to make the user aware about it
			if ( ! ($wpacuListE || $wpacuListH) ) {
				// 'body' is set, and it's not an array
				if ( is_wp_error($wpRemotePost) ) {
					$wpRemotePost['response']['message'] = $wpRemotePost->get_error_message();
				} elseif ( isset( $wpRemotePost['body']) ) {
					if ( trim( $wpRemotePost['body'] ) === '' ) {
						$wpRemotePost['body'] = '<strong>Error (blank page):</strong> It looks the targeted page is loading, but it has no content. The page seems to be blank. Please load it in incognito mode (when you are not logged-in) via your browser.';
					} elseif ( ! is_array( $wpRemotePost['body'] ) ) {
						$wpRemotePost['body'] = strip_tags( $wpRemotePost['body'], '<p><a><strong><b><em><i>' );
					}
				}

				$data = array(
					'is_dashboard_view' => true,
					'plugin_settings'   => $settings->getAll(),
					'wp_remote_post'    => $wpRemotePost
				);

				if (isset($type) && $type) {
					$data['page_options'] = MetaBoxes::getPageOptions( $postId, $type );
				}

				Main::instance()->parseTemplate('meta-box-loaded', $data, true);
				exit();
			}
		}

		$data = array(
			'is_dashboard_view' => true,
			'post_id'           => $postId,
			'plugin_settings'   => $settings->getAll()
		);

		// [START] Enqueued CSS/JS (most of them or all)
		$jsonE = base64_decode($wpacuListE);
		$data['all'] = (array) json_decode($jsonE);

        // Make sure if there are no STYLES enqueued, the list will be empty to avoid any notice errors
		if ( ! isset($data['all']['styles']) ) {
			$data['all']['styles'] = array();
		}

		// Make sure if there are no SCRIPTS enqueued, the list will be empty to avoid any notice errors
		if ( ! isset($data['all']['scripts']) ) {
			$data['all']['scripts'] = array();
		}
		// [END] Enqueued CSS/JS (most of them or all)

		// [START] Hardcoded (if any)
		if ($wpacuListH) {
			// Only set the following variables if there is at least one hardcoded LINK/STYLE/SCRIPT
			$jsonH                    = base64_decode( $wpacuListH );
			$data['all']['hardcoded'] = (array) json_decode( $jsonH, ARRAY_A );

			if ( ! empty($data['all']['hardcoded']['within_conditional_comments']) ) {
				ObjectCache::wpacu_cache_set( 'wpacu_hardcoded_content_within_conditional_comments', $data['all']['hardcoded']['within_conditional_comments'] );
			}
		}
		// [END] Hardcoded (if any)

		$data['current_unloaded_page_level'] = Main::instance()->getAssetsUnloadedPageLevel( $postId, true );

		// e.g. for "Loaded" and "Unloaded" statuses
		$data['current_unloaded_all'] = isset($data['all']['current_unloaded_all']) ? (array)$data['all']['current_unloaded_all'] : array('styles' => array(), 'scripts' => array());

        if ($data['plugin_settings']['assets_list_layout'] === 'by-location') {
			$data['all'] = Sorting::appendLocation($data['all']);
		} else {
			$data['all'] = Sorting::sortListByAlpha($data['all']);
		}

        $data['fetch_url'] = $pageUrl;
		$data['global_unload'] = Main::instance()->getGlobalUnload();

		// [wpacu_pro]
		//ObjectCache::wpacu_cache_set('wpacu_data_global_unload', $data['global_unload']);
		// [/wpacu_pro]

		$data['is_bulk_unloadable'] = $data['bulk_unloaded_type'] = false;

		$data['bulk_unloaded']['post_type'] = array('styles' => array(), 'scripts' => array());

		// Post Information
		if ($postId > 0) {
			$postData = get_post($postId);

			if (isset($postData->post_type) && $postData->post_type) {
				// Current Post Type
				$data['post_type']                  = $postData->post_type;

				// Are there any assets unloaded for this specific post type?
				// (e.g. page, post, product (from WooCommerce) or another custom post type)
				$data['bulk_unloaded']['post_type'] = Main::instance()->getBulkUnload('post_type', $data['post_type']);
				$data['bulk_unloaded_type']         = 'post_type';
				$data['is_bulk_unloadable']         = true;
				$data['post_type_has_tax_assoc']    = Main::getAllSetTaxonomies($data['post_type']);
				}
		}

		// DO NOT alter any position as it's already verified and set
		// This AJAX call is for printing the assets that were already fetched
		$data = Main::instance()->alterAssetObj($data, false);

		$data['wpacu_type'] = $type;

		// e.g. LITE rules: Load it on this page & on all pages of a specific post type
		$data['load_exceptions_per_page']  = Main::instance()->getLoadExceptionsPageLevel($type, $postId);
		$data['load_exceptions_post_type'] = ($type === 'post' && $data['post_type']) ? Main::instance()->getLoadExceptionsPostType($data['post_type']) : array();

		// [wpacu_pro]
		// Any Pro information to add to the template?
		$data = apply_filters('wpacu_data_var_template', $data);
		// [/wpacu_pro]

		$data['handle_rows_contracted'] = AssetsManager::getHandleRowStatus();

		$data['total_styles']  = ! empty($data['all']['styles'])  ? count($data['all']['styles'])  : 0;
		$data['total_scripts'] = ! empty($data['all']['scripts']) ? count($data['all']['scripts']) : 0;

		$data['all_deps'] = Main::instance()->getAllDeps($data['all']);

		$data['preloads'] = Preloads::instance()->getPreloads();

		$data['handle_load_logged_in'] = Main::instance()->getHandleLoadLoggedIn();

		$data['handle_notes'] = AssetsManager::getHandleNotes();

		$data['ignore_child'] = Main::instance()->getIgnoreChildren();

		$data['is_for_singular'] = (Misc::getVar('post', 'is_for_singular') === 'true');

		$data['page_options'] = array();
		$data['show_page_options'] = false;

		if (in_array($type, array('post', 'front_page'))) {
			$data['show_page_options'] = true;
			$data['page_options'] = MetaBoxes::getPageOptions($postId, $type);
		}

		Main::instance()->parseTemplate('meta-box-loaded', $data, true);
		exit();
	}

	/**
	 *
	 */
	public function ajaxLoadRestrictedPageAreaCallback()
	{
		if ( ! isset( $_POST['wpacu_nonce'] ) || ! wp_verify_nonce( $_POST['wpacu_nonce'], 'wpacu_ajax_load_page_restricted_area_nonce' ) ) {
			echo 'Error: The security nonce is not valid.';
			exit();
		}

		$postId = (int)Misc::getVar('post', 'post_id'); // if any (could be home page for instance)

		$data = array();

		$data['post_id']   = Main::instance()->currentPostId = $postId;
		$data['fetch_url'] = Misc::getPageUrl($postId);

		$data['show_page_options'] = true;
		$data['page_options']      = MetaBoxes::getPageOptions($postId);

		$post = get_post($postId);

		// Current Post Type
		$data['post_type']          = $post->post_type;
		$data['bulk_unloaded_type'] = 'post_type';
		$data['is_bulk_unloadable'] = true;

		$data = Main::instance()->setPageTemplate($data);

		switch (assetCleanUpHasNoLoadMatches($data['fetch_url'])) {
			case 'is_set_in_settings':
				// The rules from "Settings" -> "Plugin Usage Preferences" -> "Do not load the plugin on certain pages" will be checked
				$data['status']  = 5;
				break;

			case 'is_set_in_page':
				// The following option from "Page Options" (within the CSS/JS manager of the targeted page) is set: "Do not load Asset CleanUp Pro on this page (this will disable any functionality of the plugin)"
				$data['status']  = 6;
				break;

			default:
				$data['status'] = 1;
		}

		Main::instance()->parseTemplate('meta-box-restricted-page-load', $data, true);
		exit();
	}

	/**
	 * Make administrator more aware if "TEST MODE" is enabled or not
	 */
	public function wpacuHtmlNoticeForAdmin()
	{
		add_action('wp_footer', static function() {
			if ((WPACU_GET_LOADED_ASSETS_ACTION === true) || (! apply_filters('wpacu_show_admin_console_notice', true)) || Plugin::preventAnyFrontendOptimization()) {
				return;
			}

			if ( ! (Menu::userCanManageAssets() && ! is_admin()) ) {
				return;
			}

			if (Main::instance()->settings['test_mode']) {
				$consoleMessage = sprintf(esc_html__('%s: "TEST MODE" ENABLED (any settings or unloads will be visible ONLY to you, the logged-in administrator)', 'wp-asset-clean-up'), WPACU_PLUGIN_TITLE);
				$testModeNotice = esc_html__('"Test Mode" is ENABLED. Any settings or unloads will be visible ONLY to you, the logged-in administrator.', 'wp-asset-clean-up');
			} else {
				$consoleMessage = sprintf(esc_html__('%s: "LIVE MODE" (test mode is not enabled, thus, all the plugin changes are visible for everyone: you, the logged-in administrator and the regular visitors)', 'wp-asset-clean-up'), WPACU_PLUGIN_TITLE);
				$testModeNotice = esc_html__('The website is in LIVE MODE as "Test Mode" is not enabled. All the plugin changes are visible for everyone: logged-in administrators and regular visitors.', 'wp-asset-clean-up');
			}
			?>
            <!--
            <?php echo sprintf(esc_html__('NOTE: These "%s: Page Speed Booster" messages are only shown to you, the HTML comment is not visible for the regular visitor.', 'wp-asset-clean-up'), WPACU_PLUGIN_TITLE); ?>

            <?php echo esc_html__($testModeNotice); ?>
            -->
            <script <?php echo Misc::getScriptTypeAttribute(); ?> data-wpacu-own-inline-script="true">
                console.log('<?php echo esc_js($consoleMessage); ?>');
            </script>
			<?php
		});
	}
}
