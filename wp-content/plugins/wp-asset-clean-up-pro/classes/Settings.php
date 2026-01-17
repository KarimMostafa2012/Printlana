<?php
namespace WpAssetCleanUp;

use WpAssetCleanUp\OptimiseAssets\OptimizeCommon;
use WpAssetCleanUp\OptimiseAssets\OptimizeCss;
use WpAssetCleanUp\OptimiseAssets\OptimizeJs;

/**
 * Class Settings
 * @package WpAssetCleanUp
 */
class Settings
{
	/**
	 * @var array
	 */
	public $settingsKeys = array(
		// Stored in 'wpassetcleanup_settings'
		'wiki_read',

        // Dashboard Assets Management
        'dashboard_show',
        'dom_get_type',

		'show_assets_meta_box', // Asset CleanUp (Pro): CSS & JavaScript Manager & Options

        'hide_meta_boxes_for_post_types', // Hide all metaboxes for the chosen post types

		// Front-end View Assets Management
        'frontend_show',
        'frontend_show_exceptions',

		// Allow managing assets to:
		'allow_manage_assets_to',
		'allow_manage_assets_to_list',

		// Hide plugin's menus to make the top admin bar / left sidebar within the Dashboard cleaner (if the plugin is not used much)
		'hide_from_admin_bar',
		'hide_from_side_bar', // Since v1.1.7.1 (Pro)

		// The way the CSS/JS list is showing (various ways depending on the preference)
		'assets_list_layout',
		'assets_list_layout_areas_status',
		'assets_list_inline_code_status',

        'assets_list_layout_plugin_area_status',

		// Fetch automatically, Fetch on click
        'assets_list_show_status',

		'input_style',

		'hide_core_files',
		'test_mode',

		// Combine loaded CSS (remaining ones after unloading the useless ones) into fewer files
		'combine_loaded_css',
		'combine_loaded_css_exceptions',

		// Added since v1.1.7.3 (Pro) & v1.3.6.4 (Lite)
		'combine_loaded_css_for',
		'combine_loaded_js_for',

		// [wpacu_pro]
        'defer_css_loaded_body',

        // [CRITICAL CSS]
        'critical_css_status',
        // [/CRITICAL CSS]

        // [/wpacu_pro]

        'cache_dynamic_loaded_css',
		'cache_dynamic_loaded_js',

        'inline_js_files',
		'inline_js_files_below_size', // Enable?
		'inline_js_files_below_size_input', // Actual size
        'inline_js_files_list',

        'move_inline_jquery_after_src_tag',

		// [wpacu_pro]
        'move_scripts_to_body',
        'move_scripts_to_body_exceptions',
        // [/wpacu_pro]

        'inline_css_files',
        'inline_css_files_below_size', // Enable?
        'inline_css_files_below_size_input', // Actual size
        'inline_css_files_list',

        // Combine loaded JS (remaining ones after unloading the useless ones) into fewer files
        'combine_loaded_js',
		'combine_loaded_js_exceptions',
        'combine_loaded_js_for_admin_only',
        'combine_loaded_js_defer_body', // Applies 'defer="defer"' to the combined file(s) within BODY tag
        'combine_loaded_js_try_catch', // try {} catch (e) {} for each individual file within a combined file

		// Minify each loaded CSS (remaining ones after unloading the useless ones)
		'minify_loaded_css',
		'minify_loaded_css_for',
		'minify_loaded_css_inline', // deprecated ("minify_loaded_css_for" is used instead)
		'minify_loaded_css_exceptions',

		// Minify each loaded JS (remaining ones after unloading the useless ones)
		'minify_loaded_js',
		'minify_loaded_js_for',
		'minify_loaded_js_inline', // deprecated ("minify_loaded_js_for" is used instead)
		'minify_loaded_js_exceptions',

        'cdn_rewrite_enable',
		'cdn_rewrite_url_css',
		'cdn_rewrite_url_js',

        'disable_emojis',

		// v1.2.1.2+ (Pro), v1.3.8.6 (Lite)
		'disable_rss_feed',
        'disable_rss_feed_message',

		'disable_oembed',

		// Stored in 'wpassetcleanup_global_unload' option
		'disable_dashicons_for_guests', // CSS
		'disable_wp_block_library', // CSS
        'disable_jquery_migrate', // JS
        'disable_comment_reply', // JS

		// <head> CleanUp
		'remove_rsd_link',
		'remove_wlw_link',
		'remove_rest_api_link',
		'remove_shortlink',
		'remove_posts_rel_links',
		'remove_wp_version',

		// all "generator" meta-tags including the WordPress version
		'remove_generator_tag',

		// RSS Feed Links
		'remove_main_feed_link',
		'remove_comment_feed_link',

		// Remove HTML comments
		'remove_html_comments',
		'remove_html_comments_exceptions',

		'disable_xmlrpc',

        // Allow Usage Tracking
        'allow_usage_tracking',

        // Serve cached CSS/JS details from: Database or Disk
        'fetch_cached_files_details_from',

        // Clear CSS/JS files cache after (x) days
        'clear_cached_files_after',

		// Do not load Asset CleanUp (Pro) if the URI is matched by the specified patterns
		'do_not_load_plugin_patterns',

        // Do not load specific Asset CleanUp (Pro) features if the URI is matched by the specified patterns
        // The previous feature prevents the plugin from loading while this one limits the optimizations on the targeted pages
        // Pro: v1.2.4.2+
        'do_not_load_plugin_features',

		// [wpacu_pro]
        // Local Fonts: "font-display" CSS property (Pro feature)
        'local_fonts_display',
        'local_fonts_display_overwrite',
		// [/wpacu_pro]

        // Local Fonts: Preload Files
        'local_fonts_preload_files',

        // Google Fonts: Combine Into One Request
        'google_fonts_combine',
        'google_fonts_combine_type',

        // Google Fonts: "font-display" CSS property: LINK & STYLE tags, @import in CSS files
        'google_fonts_display',

        // Google Fonts: preconnect hint
        'google_fonts_preconnect',

        // Google Fonts: Preload Files
        'google_fonts_preload_files',

        // Google Fonts: Remove all traces
        'google_fonts_remove',

        'plugins_manager_front_disable',
        'plugins_manager_dash_disable',

        // [wpacu_lite]
        // Do not trigger Feedback Popup on Deactivation
        'disable_freemius'
		// [/wpacu_lite]
    );

    /**
     * @var array
     */
    public $currentSettings = array();

	/**
	 * @var array
	 */
	public $defaultSettings = array();

	/**
	 * Settings constructor.
	 */
	public function __construct()
    {
        $this->defaultSettings = array(
	        // Show the assets' list within the Dashboard, while they are hidden in the front-end view
	        'dashboard_show' => '1',

	        // Direct AJAX call by default (not via WP Remote POST)
	        'dom_get_type'   => 'direct',

	        'show_assets_meta_box' => 1,

	        'hide_meta_boxes_for_post_types' => array(),

	        // Very good especially for page builders: Divi Visual Builder, Oxygen Builder, WPBakery, Beaver Builder etc.
	        // It is also hidden in preview mode (if query strings such as 'preview_nonce' are used)
	        'frontend_show_exceptions' =>  'et_fb=1'."\n"
	                                       .'ct_builder=true'."\n"
	                                       .'vc_editable=true'."\n"
	                                       .'preview_nonce='."\n",

	        'allow_manage_assets_to' => 'any_admin',
	        'allow_manage_assets_to_list' => array(),

	        // Since v1.2.9.3 (Lite) and version 1.1.0.8 (Pro), the default value is "by-location" (All Styles & All Scripts - By Location (Theme, Plugins, Custom & External))
	        // Prior to that it's "two-lists" (All Styles & All Scripts - 2 separate lists)
	        'assets_list_layout'              => 'by-location',
	        'assets_list_layout_areas_status' => 'expanded',

	        'assets_list_layout_plugin_area_status' => 'expanded',

	        // "contracted" since 1.1.0.8 (Pro)
	        'assets_list_inline_code_status' => 'contracted', // takes less space overall

	        'minify_loaded_css_for' => 'href',
            'minify_loaded_js_for'  => 'src',

	        'minify_loaded_css_exceptions' => '(.*?)\.min.css'. "\n". '/wd-instagram-feed/(.*?).css',
	        'minify_loaded_js_exceptions'  => '(.*?)\.min.js' . "\n". '/wd-instagram-feed/(.*?).js',

	        'inline_css_files_below_size' => '1', // Enabled by default
	        'inline_css_files_below_size_input' => '3', // Size in KB

            // [wpacu_pro]
	        'inline_js_files_below_size_input' => '3', // Size in KB

            // Specific AMP scripts should always be in 'HEAD'
            'move_scripts_to_body_exceptions' => '//cdn.ampproject.org/',
            // [/wpacu_pro]

	        // Since v1.1.7.3 (Pro) & v1.3.6.4 (Lite)
            'combine_loaded_css_for' => 'guests',
	        'combine_loaded_js_for'  => 'guests',

	        'combine_loaded_css_exceptions' => '/wd-instagram-feed/(.*?).css',
	        'combine_loaded_js_exceptions'  => '/wd-instagram-feed/(.*?).js',

	        // [wpacu_pro]
            'defer_css_loaded_body' => 'moved',

	        // [CRITICAL CSS]
	        'critical_css_status' => 'on',
	        // [/CRITICAL CSS]

	        // [/wpacu_pro]

	        'input_style' => 'enhanced',

	        // Starting from v1.2.8.6 (lite), WordPress core files are hidden in the assets' list as a default setting
	        'hide_core_files' => '1',

            'fetch_cached_files_details_from' => 'disk', // Do not add more rows to the database by default (options table can become quite large)

            'clear_cached_files_after' => '14', // 2 weeks

            // Starting from v1.3.6.9 (Lite) & v1.1.7.9 (Pro), /cart/ & /checkout/ pages are added to the exclusion list by default
            'do_not_load_plugin_patterns' => '/cart/'. "\n". '/checkout/',

	        'disable_rss_feed_message' => __('There is no RSS feed available.', 'wp-asset-clean-up'),

            // [wpacu_pro]
            // [from v1.2.4.1]
            'plugins_manager_front_disable' => 0,
            'plugins_manager_dash_disable'  => 0,
            // [/from v1.2.4.1]
            // [/wpacu_pro]

	        // [Hidden Settings]
            // They are prefixed with underscore _
	        '_combine_loaded_css_append_handle_extra' => '1',
	        '_combine_loaded_js_append_handle_extra'  => '1'
	        // [/Hidden Settings]
        );

        // In case it's udpated within the CSS/JS manager, make sure it's updated in the settings no matter how early they will be triggered (before the actual update takes place, after the "Update" button is clicked)
        add_filter('wpacu_settings', function($settings) {
            if ( ! empty($_POST['wpacu_assets_list_layout']) ) {
                $settings['assets_list_layout'] = sanitize_text_field($_POST['wpacu_assets_list_layout']);
            }

            if (! empty($_POST['wpacu_dom_get_type'])) {
                $settings['dom_get_type'] = sanitize_text_field($_POST['wpacu_dom_get_type']);
            }

            return $settings;
        });
    }

	/**
     *
     */
    public function adminInit()
    {
        // This is triggered BEFORE "triggerAfterInit" from 'Main' class
        add_action('admin_init', array($this, 'saveSettings'), 9);

        if (Misc::getVar('get', 'page') === WPACU_PLUGIN_ID . '_settings') {
	        add_action('wpacu_admin_notices', array($this, 'notices'));

	        if (function_exists('curl_init')) {
		        // Check if the website supports HTTP/2 protocol and based on that advise the admin that combining CSS/JS is likely unnecessary
		        add_action( 'admin_footer', array($this, 'adminFooterSettings') );
	        }
        }

	    add_action( 'wp_ajax_' . WPACU_PLUGIN_ID . '_do_verifications',  array( $this, 'ajaxDoVerifications' ) );

        // e.g. when "Contract All Groups" is used, the state is kept (the setting is updated in the background)
        add_action( 'wp_ajax_' . WPACU_PLUGIN_ID . '_update_settings', array($this, 'ajaxUpdateSpecificSettings') );
	    add_action( 'wp_ajax_nopriv_' . WPACU_PLUGIN_ID . '_update_settings', array($this, 'ajaxUpdateSpecificSettings') );

        // "Settings" -- "Plugin Usage Preferences" -- "Prevent features of Asset CleanUp Pro from triggering on specific pages" -- "Add New Rule"
        add_action( 'wp_ajax_' . WPACU_PLUGIN_ID . '_add_new_no_features_load_row', array($this, 'ajaxAddNewNoFeaturesLoadRow') );
    }

	/**
	 *
	 */
	public function notices()
    {
    	$settings = $this->getAll();

    	// When no retrieval method for fetching the assets is enabled
    	if ($settings['dashboard_show'] != 1 && $settings['frontend_show'] != 1) {
		    ?>
		    <div class="notice notice-warning">
				<p><span style="color: #ffb900;" class="dashicons dashicons-info"></span>&nbsp;<?php _e('It looks like you have both "Manage in the Dashboard?" and "Manage in the Front-end?" inactive. The plugin still works fine and any assets you have selected for unload are not loaded. However, if you want to manage the assets in any page, you need to have at least one of the view options enabled.', 'wp-asset-clean-up'); ?></p>
		    </div>
		    <?php
	    }

	    // After "Save changes" is clicked
        if (Misc::getVar('get', 'wpacu_selected_tab_area') && get_transient('wpacu_settings_updated')) {
            delete_transient('wpacu_settings_updated');
            ?>
            <div class="notice notice-success is-dismissible">
                <p><span class="dashicons dashicons-yes"></span> <?php _e('The settings were successfully updated.', 'wp-asset-clean-up'); ?></p>
            </div>
            <?php
        }
    }

    /**
     *
     */
    public function saveSettings()
    {
	    if (! Misc::getVar('post', 'wpacu_settings_nonce')) {
		    return;
	    }

	    check_admin_referer('wpacu_settings_update', 'wpacu_settings_nonce');

        $savedSettings = Misc::getVar('post', WPACU_PLUGIN_ID . '_settings', array());
        $savedSettings = stripslashes_deep($savedSettings);

        // Hooks can be attached here,
        // e.g. from PluginTracking.php (check if "Allow Usage Tracking" has been enabled)
        do_action('wpacu_before_save_settings', $savedSettings);

        $this->update($savedSettings);
    }

    /**
     *
     */
    public function settingsPage()
    {
        $data = $this->getAll();

        foreach ($this->settingsKeys as $settingKey) {
            // Special check for plugin versions < 1.2.4.4
            if ($settingKey === 'frontend_show') {
                $data['frontend_show'] = $this->showOnFrontEndLegacy();
            }
        }

        $globalUnloadList = Main::instance()->getGlobalUnload();

        // [CSS]
	    if (in_array('dashicons', $globalUnloadList['styles'])) {
		    $data['disable_dashicons_for_guests'] = 1;
	    }

        if (in_array('wp-block-library', $globalUnloadList['styles'])) {
            $data['disable_wp_block_library'] = 1;
        }
	    // [/CSS]

        // [JS]
        if (in_array('jquery-migrate', $globalUnloadList['scripts'])) {
            $data['disable_jquery_migrate'] = 1;
        }

	    if (in_array('comment-reply', $globalUnloadList['scripts'])) {
		    $data['disable_comment_reply'] = 1;
	    }
	    // [/JS]

	    $data['is_optimize_css_enabled_by_other_party'] = OptimizeCss::isOptimizeCssEnabledByOtherParty();
	    $data['is_optimize_js_enabled_by_other_party']  = OptimizeJs::isOptimizeJsEnabledByOtherParty();

	    Main::instance()->parseTemplate('admin-page-settings-plugin', $data, true);
    }

    /**
     * @return bool
     */
    public function showOnFrontEndLegacy()
    {
        $settings = $this->getAll();
        return ($settings['frontend_show'] == 1);
    }

	/**
	 * @param false $forceRefetch
	 *
	 * @return array|mixed
	 */
	public function getAll($forceRefetch = false)
    {
        if ($forceRefetch) {
	        $GLOBALS['wp_object_cache']->delete(WPACU_PLUGIN_ID . '_settings', 'options');
        } elseif ( ! empty( $this->currentSettings ) ) { // default check
            return apply_filters('wpacu_settings', $this->currentSettings);
        }

        $settingsOption = get_option(WPACU_PLUGIN_ID . '_settings');

	    $applyDefaultToNeverSaved = array(
		    'frontend_show_exceptions',
		    'minify_loaded_css_exceptions',
		    'inline_css_files_below_size_input',
		    'minify_loaded_js_exceptions',
		    'inline_js_files_below_size_input',
		    'clear_cached_files_after',
		    'hide_meta_boxes_for_post_types',
            'disable_rss_feed_message',

            // [wpacu_pro]
            // [from v1.2.4.1]
            'plugins_manager_front_disable',
            'plugins_manager_dash_disable'
            // [/from v1.2.4.1]
            // [/wpacu_pro]
	    );

        // If there's already a record in the database
        if ($settingsOption !== '' && is_string($settingsOption)) {
            $settings = json_decode($settingsOption, ARRAY_A);

            if (Misc::jsonLastError() === JSON_ERROR_NONE) {
                // Make sure all the keys are there even if no value is attached to them
                // To avoid writing extra checks in other parts of the code and prevent PHP notice errors
                foreach ($this->settingsKeys as $settingsKey) {
                    if ( ! array_key_exists($settingsKey, $settings) ) {
                        $settings[$settingsKey] = '';

                        // If it doesn't exist, it was never saved (Exception: "show_assets_meta_box")
                        // Make sure the default value is added
	                    if ( in_array($settingsKey, $applyDefaultToNeverSaved) ) {
	                        $settings[ $settingsKey ] = isset( $this->defaultSettings[ $settingsKey ] ) ? $this->defaultSettings[ $settingsKey ] : '';
                        }
                    }
                }

                $this->currentSettings = $this->filterSettings($settings);

                return apply_filters('wpacu_settings', $this->currentSettings);
            }
        }

	    // No record in the database? Set the default values
	    // That could be because no changes were done on the "Settings" page
	    // OR a full reset of the plugin (via "Tools") was performed
        $finalDefaultSettings = $this->defaultSettings;

        foreach ($this->settingsKeys as $settingsKey) {
	        if (! array_key_exists($settingsKey, $finalDefaultSettings)) {
		        // Keep the keys with empty values to avoid notice errors
		        $finalDefaultSettings[$settingsKey] = '';
	        }
        }

	    return apply_filters('wpacu_settings', $this->filterSettings($finalDefaultSettings));
    }

	/**
	 * @param $settingsKey
	 *
	 * @return mixed
	 */
	public function getOption($settingsKey)
    {
        $settings = $this->getAll();
        return $settings[$settingsKey];
    }

	/**
	 * @param $key
	 * @param $value
     *
     * @return bool|void
	 */
	public function updateOption($key, $value)
    {
	    $settings = $this->getAll(true);

	    if ( ! is_array($key) ) { // not an array (e.g. a string)
		    $settings[ $key ] = $value;

            if ( ! in_array($key, $this->settingsKeys) ) {
                // The setting does not exist; Stop here!
                return;
            }
	    } else {
	        foreach ($key as $keyIndex => $keyValue) { // Array, loop through it

                if ( ! in_array($key, $this->settingsKeys) ) {
                    // The setting does not exist; Skip this one from the array
                    continue;
                }

		        $settings[ $keyValue ] = $value[$keyIndex];
	        }
	    }

        return $this->update($settings, false);
    }

	/**
	 * @param $key
	 */
	public function deleteOption($key)
	{
		$settings = $this->getAll(true);
		$settings[$key] = '';
		$this->update($settings, false);
	}

	/**
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function filterSettings($settings)
	{
        // "only to the following administrator(s):" can not be empty; if that's the case, reset it to "any administrator"
        if ($settings['allow_manage_assets_to'] === 'chosen' && empty($settings['allow_manage_assets_to_list'])) {
            $settings['allow_manage_assets_to'] = 'any_admin';
        }

		// Renamed "hide_assets_meta_box" to "show_assets_meta_box" (legacy)
		if ( isset($settings['show_assets_meta_box'], $settings['hide_assets_meta_box']) && $settings['hide_assets_meta_box'] == 1 ) { // legacy
			$settings['show_assets_meta_box'] = '0';
		}

		// "show_assets_meta_box" is either 0 or 1
		// if it doesn't exist, it was never saved (the user didn't update the settings after updating to 1.2.0.1)
		// Thus it will be activated by default: 1
		if ( ! isset($settings['show_assets_meta_box']) || $settings['show_assets_meta_box'] === '' ) {
			$settings['show_assets_meta_box'] = 1;
		}

		// Oxygen Builder is triggered, and some users might want to trigger unload rules there to make the editor faster, especially plugin unload rules
        // We will prevent minify/combine and other functions that will require caching any files to avoid any errors
		if (defined('WPACU_ALLOW_ONLY_UNLOAD_RULES') && WPACU_ALLOW_ONLY_UNLOAD_RULES) {
		    $settings['minify_loaded_css']
                = $settings['minify_loaded_js']
                = $settings['combine_loaded_css']
                = $settings['combine_loaded_js']
                = $settings['inline_css_files']
                = $settings['inline_js_files']
                = $settings['google_fonts_combine']
                = $settings['google_fonts_remove']
                = false;
		}

		if ($settings['minify_loaded_css_inline']) {
			$settings['minify_loaded_css_for'] = 'all';
		}

		if ($settings['minify_loaded_js_inline']) {
		    $settings['minify_loaded_js_for'] = 'all';
		}

		// Google Fonts Removal is enabled; make sure other related settings are nullified
		if ($settings['google_fonts_remove']) {
            $settings['google_fonts_combine']
                = $settings['google_fonts_combine_type']
                = $settings['google_fonts_display']
                = $settings['google_fonts_preconnect']
                = $settings['google_fonts_preload_files']
                = '';
		}

        if ((int)$settings['clear_cached_files_after'] === 0) {
	        $settings['clear_cached_files_after'] = 1; // Starting from v1.2.3.6 (Pro)
        }

        if ( function_exists('get_rocket_option') && Misc::isPluginActive('wp-rocket/wp-rocket.php') ) {
            // [wpacu_pro]
            if ( ! defined( 'WPACU_WP_ROCKET_REMOVE_UNUSED_CSS_ENABLED' ) && get_rocket_option('remove_unused_css') ) {
	            // When "File Optimization" -- "Optimize CSS Delivery" -- "Reduce Unused CSS" is enabled in WP Rocket
	            // Set "Settings" -- "Optimize CSS" -- "Defer CSS Loaded in the <BODY> (Footer)" to "No, leave the stylesheet LINK tags from the BODY as they are without any alteration"
	            $settings['defer_css_loaded_body'] = 'no';
                define( 'WPACU_WP_ROCKET_REMOVE_UNUSED_CSS_ENABLED', true );
            }
	        // [/wpacu_pro]

	        if ( ! defined( 'WPACU_WP_ROCKET_DELAY_JS_ENABLED' ) && get_rocket_option('delay_js') ) {
		        // When "File Optimization" -- "Delay JavaScript execution" is enabled in WP Rocket
                // Set "Settings" -- "Optimize JavaScript" -- "Combine loaded JS (JavaScript) into fewer files" to false
                $settings['combine_loaded_js'] = '';
                define( 'WPACU_WP_ROCKET_DELAY_JS_ENABLED', true );
	        }
        }

		// [START] Overwrite specific settings via query string
        // Ideally, either use /?wpacu_settings[...] OR /?wpacu_skip_test_mode (never both because they could interfere)
		if ( ! empty($_GET['wpacu_settings']) && is_array($_GET['wpacu_settings']) ) {
            foreach ($_GET['wpacu_settings'] as $settingKey => $settingValue) {
                if ($settingValue === 'true') {
	                $settingValue = true;
                }
	            if ($settingValue === 'false') {
		            $settingValue = false;
	            }
                $settings[$settingKey] = $settingValue;
            }
		}

		// /?wpacu_test_mode (will load the page with "Test Mode" enabled disregarding the value from the plugin's "Settings")
		// For debugging purposes (e.g. to make sure the HTML source is the same when a guest user accesses it as the one that is generated when the plugin is deactivated)
		if ( isset($_GET['wpacu_test_mode']) ) {
			$settings['test_mode'] = true;
		}

		if ( isset($_GET['wpacu_skip_test_mode']) ) {
			$settings['test_mode'] = false;
		}

		// /?wpacu_skip_inline_css
		if (isset($_GET['wpacu_skip_inline_css_files'])) {
			$settings['inline_css_files'] = false;
		}

		// /?wpacu_skip_inline_js
		if (isset($_GET['wpacu_skip_inline_js_files'])) {
			$settings['inline_js_files'] = false;
		}

		// /?wpacu_manage_front -> "Manage in the Front-end" via query string request
		// Useful when working for a client, and you prefer him to view the pages (while logged-in) without the CSS/JS list at the bottom
		if (isset($_GET['wpacu_manage_front'])) {
			$settings['frontend_show'] = true;
		}

		// /?wpacu_manage_dash -> "Manage in the Dashboard" via query string request
		// For debugging purposes
		if (is_admin() && (isset($_REQUEST['wpacu_manage_dash']) || isset($_REQUEST['force_manage_dash']))) {
			$settings['dashboard_show'] = true;
		}
		// [END] Overwrite specific settings via query string

        // "Settings"
        // -- "Plugin Usage Preferences"
        // -- "Do not load on specific pages"
        // -- "Prevent features of Asset CleanUp Pro from triggering on specific pages"
        if ( ! empty($settings['do_not_load_plugin_features']) ) {
            $requestUriAsItIs = rawurldecode($_SERVER['REQUEST_URI']);

            foreach ( $settings['do_not_load_plugin_features'] as $setValues ) {
                if (trim($setValues['pattern']) === '{homepage}') {
                    $condToUse = wpacuIsHomePageUrl(rawurldecode($_SERVER['REQUEST_URI']));
                } else {
                    $condToUse = strpos($requestUriAsItIs, $setValues['pattern']) !== false;
                }

                $isToCheck = ( ! empty($setValues['pattern']) && ! empty($setValues['list']) ) &&
                           ($condToUse || @preg_match($setValues['pattern'], $requestUriAsItIs) );

                if ( ! $isToCheck ) {
                    continue;
                }

                foreach ($setValues['list'] as $featureToAvoid) {
                    // [CSS]
                    if ($featureToAvoid === 'minify_css') {
                        $settings['minify_loaded_css'] = false;
                    }

                    if ($featureToAvoid === 'inline_css') {
                        $settings['inline_css_files'] = false;
                    }

                    if ($featureToAvoid === 'combine_css') {
                        $settings['combine_loaded_css'] = false;
                    }

                    if ($featureToAvoid === 'defer_css_body') {
                        $settings['defer_css_loaded_body'] = 'no';
                    }

                    if ($featureToAvoid === 'critical_css') {
                        $settings['critical_css_status'] = 'off';
                    }

                    if ($featureToAvoid === 'cache_dynamic_loaded_css') {
                        $settings['cache_dynamic_loaded_css'] = false;
                    }
                    // [/CSS]

                    // [JavaScript]
                    if ($featureToAvoid === 'minify_js') {
                        $settings['minify_loaded_js'] = false;
                    }

                    if ($featureToAvoid === 'inline_js') {
                        $settings['inline_js_files'] = false;
                    }

                    if ($featureToAvoid === 'combine_js') {
                        $settings['combine_loaded_js'] = false;
                    }

                    if ($featureToAvoid === 'move_inline_jquery_after_src_tag') {
                        $settings['move_inline_jquery_after_src_tag'] = false;
                    }

                    if ($featureToAvoid === 'move_scripts_to_body') {
                        $settings['move_scripts_to_body'] = false;
                    }

                    if ($featureToAvoid === 'cache_dynamic_loaded_js') {
                        $settings['cache_dynamic_loaded_js'] = false;
                    }
                    // [/JavaScript]

                    // [Local Fonts]
                    if ($featureToAvoid === 'local_fonts_display') {
                        $settings['local_fonts_display'] = '';
                    }

                    if ($featureToAvoid === 'local_fonts_preload') {
                        $settings['local_fonts_preload_files'] = '';
                    }
                    // [/Local Fonts]

                    // [Google Fonts]
                    if ($featureToAvoid === 'google_fonts_combine') {
                        $settings['google_fonts_combine'] = '';
                    }

                    if ($featureToAvoid === 'google_fonts_display') {
                        $settings['google_fonts_display'] = '';
                    }

                    if ($featureToAvoid === 'google_fonts_preconnect') {
                        $settings['google_fonts_preconnect'] = '';
                    }

                    if ($featureToAvoid === 'google_fonts_preload') {
                        $settings['google_fonts_preload_files'] = '';
                    }
                    // [/Google Fonts]
                }
            }
        }

        return $settings;
	}

	/**
	 * @param $settings
	 * @param bool $redirectAfterUpdate
     *
     * @return bool|void
	 */
	public function update($settings, $redirectAfterUpdate = true)
    {
	    $settingsNotNull = array();

	    foreach ($settings as $settingKey => $settingValue) {
	        if ($settingValue !== '') {
	            // Some validation
	            if ($settingKey === 'clear_cached_files_after') {
	                $settingValue = (int)$settingValue;
                }

		        $settingsNotNull[$settingKey] = $settingValue;
            }
        }

	    if (wp_json_encode($this->defaultSettings) === wp_json_encode($settingsNotNull)) {
	        // Do not keep a record in the database (no point of having an extra entry)
            // if the submitted values are the same as the default ones
	        delete_option(WPACU_PLUGIN_ID . '_settings');

	        if ($redirectAfterUpdate) {
		        $this->redirectAfterUpdate(); // script ends here
	        }
        }

	    // The following are only triggered IF the user submitted the form from "Settings" area
        if (Misc::getVar('post', 'wpacu_settings_nonce')) {
	        // By default, these hidden settings are enabled; In case they do not exist (older database), add them
	        // Only keep them enabled if WordPress version is >= 5.5
	        $appendInlineCodeToCombinedAssets = ( Misc::isWpVersionAtLeast('5.5') ) ? 1 : '';

	        if ( $appendInlineCodeToCombinedAssets === '' ) {
		        // WordPress version < 5.5 (do not enable it)
		        $settings['_combine_loaded_css_append_handle_extra'] = $settings['_combine_loaded_js_append_handle_extra'] = '';
	        } else {
		        // WordPress version >= 5.5 (make it enabled by default if not set)
		        if ( ! isset( $settings['_combine_loaded_css_append_handle_extra'] ) ) {
			        $settings['_combine_loaded_css_append_handle_extra'] = 1; // default
		        }
		        if ( ! isset( $settings['_combine_loaded_js_append_handle_extra'] ) ) {
			        $settings['_combine_loaded_js_append_handle_extra'] = 1; // default
		        }
	        }

	        // "Site-Wide Common Unloads" tab
	        $disableGutenbergCssBlockLibrary = isset( $_POST[ WPACU_PLUGIN_ID . '_global_unloads' ]['disable_wp_block_library'] );
	        $disableJQueryMigrate            = isset( $_POST[ WPACU_PLUGIN_ID . '_global_unloads' ]['disable_jquery_migrate'] );
	        $disableCommentReply             = isset( $_POST[ WPACU_PLUGIN_ID . '_global_unloads' ]['disable_comment_reply'] );
	        $disableDashiconsForGuests       = isset( $_POST[ WPACU_PLUGIN_ID . '_global_unloads' ]['disable_dashicons_for_guests'] );

	        $this->updateSiteWideRuleForCommonAssets(array(
                'wp_block_library' => $disableGutenbergCssBlockLibrary,
		        'dashicons'        => $disableDashiconsForGuests,
		        'jquery_migrate'   => $disableJQueryMigrate,
		        'comment_reply'    => $disableCommentReply
	        ));

	        // Some validation
	        $stripTagsForList = array(
                'frontend_show_exceptions',
                'do_not_load_plugin_patterns',
                'minify_loaded_css_exceptions',
                'combine_loaded_css_exceptions',
                'inline_css_files_list',
                'minify_loaded_js_exceptions',
                'combine_loaded_js_exceptions',
                'inline_js_files_list',
                'move_scripts_to_body_exceptions',
                'cdn_rewrite_url_css',
                'cdn_rewrite_url_js',
                'remove_html_comments_exceptions',
                'local_fonts_preload_files',
                'google_fonts_preload_files'
            );

            foreach ($stripTagsForList as $stripTagsFor) {
	            $settings[$stripTagsFor] = strip_tags($settings[$stripTagsFor]);
            }

	        // Apply 'Ignore dependency rule and keep the "children" loaded' for "dashicons" handle if Ninja Forms is active
	        // because "nf-display" handle depends on the Dashicons, and it could break the forms' styling
	        if ($disableDashiconsForGuests && Misc::isPluginActive('ninja-forms/ninja-forms.php')) {
		        $mainVarToUse = array();
		        $mainVarToUse['wpacu_ignore_child']['styles']['dashicons'] = 1;
				Update::updateIgnoreChild($mainVarToUse);
	        }

	        if ($appendInlineCodeToCombinedAssets) {
		        $settings = self::toggleAppendInlineAssocCodeHiddenSettings($settings);
	        }

            // Pro: v1.2.4.2
            if ( ! empty($settings['do_not_load_plugin_features']) ) {
                $settings['do_not_load_plugin_features'] = Misc::filterList($settings['do_not_load_plugin_features']);
                if ( ! empty($settings['do_not_load_plugin_features']) ) {
                    foreach ($settings['do_not_load_plugin_features'] as $rowKey => $setValues) {
                        if (empty($setValues['pattern']) || empty($setValues['list'])) {
                            unset($settings['do_not_load_plugin_features'][$rowKey]);
                        }
                    }
                }

                }
        }

	    $addUpdateStatus = Misc::addUpdateOption(WPACU_PLUGIN_ID . '_settings', wp_json_encode(Misc::filterList($settings)));

        Misc::w3TotalCacheFlushObjectCache();

        // New Plugin Update (since 6 April 2020): the cache is cleared after page load via AJAX
	    // This is done in case the cache directory is large and more time is required to clear it
	    // This offers the admin a better user experience (no one likes to wait too much until a page is reloaded, which sometimes could cause confusion)
	    if ($redirectAfterUpdate) {
		    $this->redirectAfterUpdate();
	    }

        return $addUpdateStatus;
    }

	/**
	 * @param $settings
	 * @param false $doSettingUpdate (e.g. 'true' if called from a WP Cron)
	 * @param false $isDebug (e.g. 'true' if requested via a query string such as 'wpacu_toggle_inline_code_to_combine_js' for debugging purposes)
     *
	 * @return mixed
	 */
	public static function toggleAppendInlineAssocCodeHiddenSettings($settings, $doSettingUpdate = false, $isDebug = false)
    {
	    // Are there too many files in WP_CONTENT_DIR . WpAssetCleanUp\OptimiseAssets\OptimizeCommon::getRelPathPluginCacheDir() . '(css|js)/' directory?
	    // Deactivate the appending of the inline CSS/JS code (extra, before or after)
	    $mbLimitForFiles = array(
		    'css' => 700,
		    'js'  => 700 // This is the one that usually has non-unique inline JS code
	    );

	    if ($doSettingUpdate) {
	        $settingsClass = new Settings();
	    }

	    foreach ( $mbLimitForFiles as $assetType => $mbLimit ) { // Go through both .css and .js
	        $combineSettingsKey = 'combine_loaded_'.$assetType;
	        $isCombineAssetsEnabled = isset($settings[$combineSettingsKey]) && $settings[$combineSettingsKey];

		    if ( ! $isCombineAssetsEnabled ) {
		        if ($isDebug) {
			        echo 'Combine '.strtoupper($assetType).' is not enabled.<br />';
		        }
			    continue; // Only do the checking if combine CSS/JS is enabled
		    }

		    $wpacuPathToCombineDirSize = Misc::getSizeOfDirectoryRootFiles(
			    array(
				    WP_CONTENT_DIR . OptimizeCommon::getRelPathPluginCacheDir() . $assetType . '/',
				    WP_CONTENT_DIR . OptimizeCommon::getRelPathPluginCacheDir() . $assetType . '/logged-in/' // just in case "Apply it for all visitors (not recommended)" has been enabled
			    ),
			    '.' . $assetType
		    );

		    $preventAddingInlineCodeToCombinedAssets = isset( $wpacuPathToCombineDirSize['total_size_mb'] ) && $wpacuPathToCombineDirSize['total_size_mb'] > $mbLimit;

		    if ( $preventAddingInlineCodeToCombinedAssets ) {
			    $settings['_combine_loaded_'.$assetType.'_append_handle_extra'] = '';
		    } else {
			    $settings['_combine_loaded_'.$assetType.'_append_handle_extra'] = 1;
		    }

		    if ($isDebug) {
		        if ($preventAddingInlineCodeToCombinedAssets) {
		            echo 'Adding inline code to combined '.strtoupper($assetType).' has been deactivated as the total size of combined assets is '.$wpacuPathToCombineDirSize['total_size_mb'].' MB.<br />';
		        } else {
			        echo 'Adding inline code to combined '.strtoupper($assetType).' has been (re)activated as the total size of combined assets is '.$wpacuPathToCombineDirSize['total_size_mb'].' MB.<br />';
		        }
		    }

		    if ($doSettingUpdate) {
			    $settingsClass->updateOption(
                    '_combine_loaded_'.$assetType.'_append_handle_extra',
                    $settings['_combine_loaded_'.$assetType.'_append_handle_extra']
                );
		    }
	    }

	    return $settings;
    }

	/**
	 * @param $unloadsList
	 */
	public function updateSiteWideRuleForCommonAssets($unloadsList)
    {
	    $wpacuUpdate = new Update;

	    $disableGutenbergCssBlockLibrary = $unloadsList['wp_block_library'];
	    $disableJQueryMigrate            = $unloadsList['jquery_migrate'];
	    $disableCommentReply             = $unloadsList['comment_reply'];
	    $disableDashiconsForGuests       = $unloadsList['dashicons'];

	    /*
	     * Add element(s) to the global unload rules
	     */
	    if ($disableGutenbergCssBlockLibrary || $disableDashiconsForGuests) {
		    $unloadList = array();

		    if ($disableGutenbergCssBlockLibrary) {
			    $unloadList[] = 'wp-block-library';
		    }

		    if ($disableDashiconsForGuests) {
			    $unloadList[] = 'dashicons';
		    }

		    $wpacuUpdate->saveToEverywhereUnloads($unloadList);
        }

	    if ($disableJQueryMigrate || $disableCommentReply) {
		    $unloadList = array();

		    // Add jQuery Migrate to the global unload rules
		    if ($disableJQueryMigrate) {
			    $unloadList[] = 'jquery-migrate';
		    }

		    // Add Comment Reply to the global unload rules
		    if ($disableCommentReply) {
			    $unloadList[] = 'comment-reply';
		    }

		    $wpacuUpdate->saveToEverywhereUnloads(array(), $unloadList);
	    }

	    /*
		 * Remove element(s) from the global unload rules
		 */

	    // For Stylesheets (.CSS)
	    if (! $disableGutenbergCssBlockLibrary || ! $disableDashiconsForGuests) {
		    $removeFromUnloadList = array();

		    if (! $disableGutenbergCssBlockLibrary) {
			    $removeFromUnloadList['wp-block-library'] = 'remove';
            }

		    if (! $disableDashiconsForGuests) {
			    $removeFromUnloadList['dashicons'] = 'remove';
		    }

		    $wpacuUpdate->removeEverywhereUnloads($removeFromUnloadList);
	    }

	    // For JavaScript (.JS)
	    if (! $disableJQueryMigrate || ! $disableCommentReply) {
		    $removeFromUnloadList = array();

		    // Remove jQuery Migrate from global unload rules
		    if (! $disableJQueryMigrate) {
			    $removeFromUnloadList['jquery-migrate'] = 'remove';
		    }

		    // Remove Comment Reply from global unload rules
		    if (! $disableCommentReply) {
			    $removeFromUnloadList['comment-reply'] = 'remove';
		    }

		    $wpacuUpdate->removeEverywhereUnloads(array(), $removeFromUnloadList);
	    }
    }

	/**
	 *
	 */
	public function redirectAfterUpdate()
    {
	    $tabArea    = Misc::getVar('post', 'wpacu_selected_tab_area', 'wpacu-setting-plugin-usage-settings');
	    $subTabArea = Misc::getVar('post', 'wpacu_selected_sub_tab_area', '');

	    set_transient('wpacu_settings_updated', 1, 30);

	    $wpacuQueryString = array(
            'page' => 'wpassetcleanup_settings',
            'wpacu_selected_tab_area' => $tabArea,
            'wpacu_time' => time()
        );

	    if ($subTabArea) {
		    $wpacuQueryString['wpacu_selected_sub_tab_area'] = $subTabArea;
        }

	    wp_redirect(add_query_arg($wpacuQueryString, esc_url(admin_url('admin.php'))));
	    exit();
    }

	/**
	 *
	 */
	public function ajaxDoVerifications()
    {
	    if ( ! isset($_POST['action']) || ! Menu::userCanManageAssets() ) {
		    return;
	    }

	    if ( ! isset($_POST['wpacu_nonce']) ) {
		    echo 'Error: The security nonce was not sent for verification. Location: '.__METHOD__;
		    return;
	    }

	    if ( ! wp_verify_nonce($_POST['wpacu_nonce'], 'wpacu_do_verifications') ) {
		    echo 'Error: The security check has failed. Location: '.__METHOD__;
		    return;
	    }

	    $result = array();

	    $ch = curl_init();

	    $curlParams = array(
		    CURLOPT_URL            => get_site_url(),
		    CURLOPT_HEADER         => true,
		    CURLOPT_NOBODY         => true,
		    CURLOPT_RETURNTRANSFER => true
	    );

	    if (defined('CURLOPT_HTTP_VERSION') && defined('CURL_HTTP_VERSION_2_0')) {
		    // cURL will attempt to make an HTTP/2.0 request (can downgrade to HTTP/1.1)))
		    $curlParams[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_2_0;
	    }

	    curl_setopt_array($ch, $curlParams);

	    $response = curl_exec($ch);

	    if (! $response) {
		    echo curl_error($ch); // something else happened causing the request to fail
	    }

	    if (strpos($response, 'HTTP/2') === 0) {
		    $result['has_http2'] = '1'; // Has HTTP/2 Support
	    }

	    if ((strpos($response, 'cf-cache-status:') !== false) &&
            (strpos($response, 'cf-request-id:') !== false) &&
            (strpos($response, 'cf-ray:') !== false)) {
		    $result['uses_cloudflare'] = '1'; // Uses Cloudflare
	    }

	    curl_close($ch);

	    echo wp_json_encode($result);
	    exit();
    }

	/**
	 *
	 */
	public function ajaxUpdateSpecificSettings()
    {
        // Option: "On Assets List Layout Load, keep the groups:"
        if (isset($_POST['wpacu_update_keep_the_groups'])) {
	        if ( ! isset( $_POST['action'], $_POST['wpacu_keep_the_groups_state'] ) || ! Menu::userCanManageAssets() ) {
		        return;
	        }

	        if ( $_POST['wpacu_update_keep_the_groups'] !== 'yes' ) {
		        return;
	        }

	        if ( ! isset($_POST['wpacu_nonce']) ) {
		        echo 'Error: The security nonce was not sent for verification. Location: '.__METHOD__;
		        return;
	        }

	        if ( ! wp_verify_nonce($_POST['wpacu_nonce'], 'wpacu_update_specific_settings_nonce') ) {
		        echo 'Error: The nonce security check has failed. Location: '.__METHOD__;
		        return;
	        }

	        $newKeepTheGroupsState = $_POST['wpacu_keep_the_groups_state'];

	        $this->updateOption( 'assets_list_layout_areas_status', $newKeepTheGroupsState );

	        echo 'done';
        }

	    exit();
    }

    /**
     * @return void
     */
    public function ajaxAddNewNoFeaturesLoadRow()
    {
        if ( ! isset( $_REQUEST['action'] ) ||
             ($_REQUEST['action'] !== WPACU_PLUGIN_ID . '_add_new_no_features_load_row') ||
             ! Menu::userCanManageAssets() ) {
            exit();
        }

        $settingsClass = new Settings();
        $settingsData = $settingsClass->getAll();

        echo self::generateNewRuleNoFeatureAreaRow($settingsData);
        exit();
    }

	/**
	 *
	 */
	public function adminFooterSettings()
    {
	    if ( ! (defined('CURLOPT_HTTP_VERSION') && defined('CURL_HTTP_VERSION_2_0')) ) {
	        ?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $('.wpacu_verify_http2_protocol').removeClass('wpacu_hide');
                });
            </script>
            <?php
		    return; // Stop here! "CURL_HTTP_VERSION_2_0" constant has to be defined
	    }
	    ?>
	    <script type="text/javascript">
		    jQuery(document).ready(function($) {
                $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                    'action': '<?php echo WPACU_PLUGIN_ID; ?>_do_verifications',
                    'wpacu_nonce': '<?php echo wp_create_nonce('wpacu_do_verifications'); ?>'
                }, function (obj) {
                    let result = jQuery.parseJSON(obj);
                    console.log(result);

                    if (result.has_http2 === '1') {
                        $('.wpacu-combine-notice-http-2-detected').removeClass('wpacu_hide');
                    } else {
                        $('.wpacu-combine-notice-default').removeClass('wpacu_hide');
                    }

                    if (result.uses_cloudflare === '1') {
                        $('#wpacu-site-uses-cloudflare').show();
                    }
                });
		    });
	    </script>
	    <?php
    }

	/**
	 * @param $value
     * @param $name
	 *
	 * @return false|string
	 */
	public static function generateAssetsListLayoutDropDown($value, $name)
    {
        ob_start();
        ?>
        <select id="wpacu_assets_list_layout" style="max-width: inherit;" name="<?php echo esc_attr($name); ?>">
            <option <?php if ($value === 'by-location') { echo 'selected="selected"'; } ?> value="by-location"><?php esc_html_e('Grouped by location (themes, plugins, core &amp; external)', 'wp-asset-clean-up'); ?></option>
            <option <?php if ($value === 'by-position') { echo 'selected="selected"'; } ?> value="by-position"><?php esc_html_e('Grouped by tag position: &lt;head&gt; &amp; &lt;body&gt;', 'wp-asset-clean-up'); ?></option>
            <option <?php if ($value === 'by-preload') { echo 'selected="selected"'; } ?> value="by-preload"><?php esc_html_e('Grouped by preloaded or not-preloaded status', 'wp-asset-clean-up'); ?></option>
            <option <?php if ($value === 'by-parents') { echo 'selected="selected"'; } ?> value="by-parents"><?php esc_html_e('Grouped by dependencies: Parents, Children, Independent', 'wp-asset-clean-up'); ?></option>
            <option <?php if ($value === 'by-loaded-unloaded') { echo 'selected="selected"'; } ?> value="by-loaded-unloaded"><?php esc_html_e('Grouped by loaded or unloaded status', 'wp-asset-clean-up'); ?></option>
            <option <?php if ($value === 'by-size') { echo 'selected="selected"'; } ?> value="by-size"><?php esc_html_e('Grouped by their size (sorted in descending order)', 'wp-asset-clean-up'); ?></option>
            <option <?php if ($value === 'by-rules') { echo 'selected="selected"'; } ?> value="by-rules"><?php esc_html_e('Grouped by having at least one rule &amp; no rules', 'wp-asset-clean-up'); ?></option>
            <option <?php if (in_array($value, array('two-lists', 'default'))) { echo 'selected="selected"'; } ?> value="two-lists"><?php esc_html_e('All enqueued CSS, followed by all enqueued JavaScript', 'wp-asset-clean-up'); ?></option>
            <option <?php if ($value === 'all') { echo 'selected="selected"'; } ?> value="all"> <?php esc_html_e('All enqueues in one list', 'wp-asset-clean-up'); ?></option>
        </select>
        <?php
        return ob_get_clean();
    }

    /**
     * @param $data
     * @param $setValues
     *
     * @return false|string
     */
    public static function generateNewRuleNoFeatureAreaRow($data, $setValues = array('pattern' => '', 'list' => array()))
    {
        ob_start();

        $uniqueId = uniqid('', true);

        $allFeaturesSelectOptionsGroups = array(
            __('CSS', 'wp-asset-clean-up') => array(
                'minify_css'                       => __('Minify CSS', 'wp-asset-clean-up'),
                'inline_css'                       => __('Inline CSS', 'wp-asset-clean-up'),
                'combine_css'                      => __('Combine CSS', 'wp-asset-clean-up'),
                'defer_css_body'                   => __('Defer CSS Loaded in the &lt;BODY&gt; (Footer)', 'wp-asset-clean-up'),
                'critical_css'                     => __('Critical CSS', 'wp-asset-clean-up'),
                'cache_dynamic_loaded_css'         => __('Cache Dynamic Loaded CSS', 'wp-asset-clean-up')
            ),

            __('JavaScript', 'wp-asset-clean-up') => array(
                'minify_js'                        => __('Minify JavaScript', 'wp-asset-clean-up'),
                'inline_js'                        => __('Inline JavaScript', 'wp-asset-clean-up'),
                'combine_js'                       => __('Combine JavaScript', 'wp-asset-clean-up'),
                'move_inline_jquery_after_src_tag' => __('Move jQuery Inline Code After jQuery library', 'wp-asset-clean-up'),
                'move_scripts_to_body'             => __('Move All &lt;SCRIPT&gt; tags from HEAD to BODY', 'wp-asset-clean-up'),
                'cache_dynamic_loaded_js'          => __('Cache Dynamic Loaded JavaScript', 'wp-asset-clean-up')
            ),

            __('Local Fonts', 'wp-asset-clean-up') => array(
                'local_fonts_display' => __('Local Fonts: "font-display" update', 'wp-asset-clean-up'),
                'local_fonts_preload' => __('Local Fonts: Preload', 'wp-asset-clean-up')
            ),

            __('Google Fonts', 'wp-asset-clean-up') => array(
                'google_fonts_combine'    => __('Google Fonts: Combine', 'wp-asset-clean-up'),
                'google_fonts_display'    => __('Google Fonts: "font-display" update', 'wp-asset-clean-up'),
                'google_fonts_preconnect' => __('Google Fonts: Preconnect', 'wp-asset-clean-up'),
                'google_fonts_preload'    => __('Google Fonts: Preload', 'wp-asset-clean-up'),
                'google_fonts_remove'     => __('Google Fonts: Remove', 'wp-asset-clean-up')
            ),
        );
        ?>
        <div class="wpacu-prevent-feature-rule-area">
            &nbsp;<strong>When URI contains/matches:</strong>&nbsp;

            <input type="text"
                   class="wpacu-input-pattern-element wpacu-input-element"
                   name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[do_not_load_plugin_features][<?php echo $uniqueId; ?>][pattern]"
                   placeholder="<?php esc_attr_e('String or RegEx', 'wp-asset-clean-up'); ?>"
                   value="<?php if ($setValues['pattern']) { echo esc_attr($setValues['pattern']); } ?>" />

            &nbsp;&#x2192;&nbsp;&nbsp;<strong>DO NOT load these features:</strong>&nbsp;

            <select multiple="multiple"
                    name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[do_not_load_plugin_features][<?php echo $uniqueId; ?>][list][]"
                    class="wpacu-input-element <?php if ($data['input_style'] !== 'standard') { ?> wpacu_chosen_can_be_later_enabled <?php } ?>"
                <?php if ($data['input_style'] !== 'standard') { ?>
                    data-placeholder="<?php esc_attr_e('Choose the features to prevent from loading', 'wp-asset-clean-up'); ?>..."
                <?php } ?>
                    style="min-width: 300px;">
                <?php
                foreach ($allFeaturesSelectOptionsGroups as $optionsGroup => $allFeaturesSelectOptions) { ?>
                    <optgroup label="<?php echo esc_attr($optionsGroup); ?>">
                    <?php
                    foreach ($allFeaturesSelectOptions as $selectOptionValue => $selectOptionText) {
                    ?>
                        <option <?php if (in_array($selectOptionValue, $setValues['list'])) { echo 'selected="selected"'; } ?> value="<?php echo esc_attr($selectOptionValue); ?>"><?php echo $selectOptionText; ?></option>
                    <?php
                    }
                    ?>
                    </optgroup>
                    <?php
                }
                ?>
            </select> <a class="wpacu-delete-no-features-rule-row" href="#">&nbsp;<span class="dashicons dashicons-minus"></span> Clear Rule&nbsp;</a>&nbsp;&nbsp;<a class="wpacu-add-new-no-features-rule-row" href="#"><span class="dashicons dashicons-plus-alt"></span> Add New Rule</a> <span class="wpacu-add-new-no-features-rule-row-loader"><img src="<?php echo esc_url(admin_url('images/spinner.gif')); ?>" width="20" height="20" alt="" /></span>
        </div>
        <?php
        return ob_get_clean();
    }
}
