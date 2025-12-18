<?php
namespace WpAssetCleanUp;

use WpAssetCleanUp\OptimiseAssets\OptimizeCommon;
use WpAssetCleanUp\OptimiseAssets\OptimizeCss;
use WpAssetCleanUp\OptimiseAssets\OptimizeJs;

/**
 * Class MainFront
 *
 * This class has functions that are only for the front-end view
 * for both the admin and guest visits (nothing within the /wp-admin/ area)
 *
 * @package WpAssetCleanUp
 */
class MainFront
{
	/**
	 * Populated in the Parser constructor
	 *
	 * @var array
	 */
	public $skipAssets = array( 'styles' => array(), 'scripts' => array() );

	/**
	 * @var MainFront|null
	 */
	private static $singleton;

	/**
	 * @return null|MainFront
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
        if ( is_admin() ) {
            return;
        }

        Main::instance()->loadAllSettings();
        add_action('init', array($this, 'triggersAfterInitFrontendView'));

		if (Main::instance()->isGetAssetsCall) {
			$currentTheme = strtolower(wp_get_theme());
			$noRocketInit = true;

			if (strpos($currentTheme, 'uncode') !== false) {
				$noRocketInit = false; // make exception for the "Uncode" Theme as it doesn't check if the get_rocket_option() function exists
			}

			if ($noRocketInit) {
				add_filter('rocket_cache_reject_uri', function($urls) {
					$urls[] = '/?wpassetcleanup_load=1';
					return $urls;
				});
				}

			// Do not output Query Monitor's information as it's irrelevant in this context
			if ( class_exists( '\QueryMonitor' ) && class_exists( '\QM_Plugin' ) ) {
				add_filter( 'user_has_cap', static function( $userCaps ) {
					$userCaps['view_query_monitor'] = false;
					return $userCaps;
				} );
			}

			add_filter( 'style_loader_tag', static function( $styleTag, $tagHandle ) {
				// This is used to determine if the LINK is enqueued later on
				// If the handle name is not showing up, then the LINK stylesheet has been hardcoded (not enqueued the WordPress way)
				return str_replace( '<link ', '<link data-wpacu-style-handle=\'' . $tagHandle . '\' ', $styleTag );
			}, PHP_INT_MAX, 2 ); // Trigger it later in case plugins such as "Ronneby Core" plugin alters it

			add_filter( 'script_loader_tag', static function( $scriptTag, $tagHandle ) {
				// This is used to determine if the SCRIPT is enqueued later on
				// If the handle name is not showing up, then the SCRIPT has been hardcoded (not enqueued the WordPress way)
				$reps = array( '<script ' => '<script data-wpacu-script-handle=\'' . $tagHandle . '\' ' );

				return str_replace( array_keys( $reps ), array_values( $reps ), $scriptTag );
			}, PHP_INT_MAX, 2 );

			add_filter( 'show_admin_bar', '__return_false' );
		}

	    // Early Triggers
	    $wpacuAction = Misc::isElementorMaintenanceModeOn() ? 'template_redirect' : 'wp';

	    if ($wpacuAction === 'wp') {
		    add_action( 'wp', array( $this, 'setVarsBeforeUpdate' ), 8 );
		    add_action( 'wp', array( $this, 'setVarsAfterAnyUpdate' ) );
	    } else {
		    add_action( 'template_redirect', array( $this, 'setVarsBeforeUpdate' ), 12 ); // over 11 which is set in Elementor's maintenance-mode.php
		    add_action( 'template_redirect', array( $this, 'setVarsAfterAnyUpdate' ), 13 );
	    }

	    // Fetch Assets AJAX Call? Make sure the output is as clean as possible (no plugins interfering with it)
	    // It can also be used for debugging purposes (via /?wpacu_clean_load) when you want to view all the CSS/JS
	    // that are loaded in the HTML source code before they are unloaded or altered in any way
	    if ( Main::instance()->isGetAssetsCall || array_key_exists('wpacu_clean_load', $_GET) ) {
		    $wpacuCleanUp = new CleanUp();
		    $wpacuCleanUp->cleanUpHtmlOutputForAssetsCall();
	    }

	    // "Direct" AJAX call or "WP Remote POST" method used?
	    // Do not trigger the admin bar as it's not relevant
	    if ( Main::instance()->isAjaxCall ) {
		    add_filter( 'show_admin_bar', '__return_false' );
	    }

	    // Front-end View - Unload the assets
	    // If there are reasons to prevent the unloading in case 'test mode' is enabled,
	    // then the prevention will trigger within filterStyles() and filterScripts()

	    /*
		 * [START] /?wpassetcleanup_load=1 is called
		 */
	    if ( Main::instance()->isGetAssetsCall ) {
		    // These actions are also called when the page is loaded without query string (regular load)
		    // This time, the CSS/JS will not be unloaded, but the CSS/JS marked for unload will be collected
		    // and passed to the AJAX call for the option "Group by loaded or unloaded status"
		    if ( get_option( 'siteground_optimizer_combine_css' ) ) {
			    add_action( 'wp_print_styles',     array( $this, 'filterStyles' ), 9 ); // priority should be below 10
		    }
		    add_action( 'wp_print_styles',         array( $this, 'filterStyles' ), 100000 );
		    add_action( 'wp_print_scripts',        array( $this, 'filterScripts' ), 100000 );
		    add_action( 'wp_print_footer_scripts', array( $this, 'onPrintFooterScriptsStyles' ), 1 );
	    }
	    /*
		 * [END] /?wpassetcleanup_load=1 is called
		 */

	    /*
	     * [START] Front-end page visited (e.g. by the admin or a guest visitor)
	     */
	    if ( ! Main::instance()->isGetAssetsCall ) {
		    // [START] Unload CSS/JS on page request (for debugging)
		    add_filter( 'wpacu_ignore_child_parent_list', array( $this, 'filterIgnoreChildParentList' ) );
		    // [END] Unload CSS/JS on page request (for debugging)

		    // SG Optimizer Compatibility: Unload Styles - HEAD (Before pre_combine_header_styles() from Combinator)
		    if ( get_option( 'siteground_optimizer_combine_css' ) ) {
			    add_action( 'wp_print_styles',     array( $this, 'filterStyles' ), 9 ); // priority should be below 10
		    }

		    self::filterStylesSpecialCases(); // e.g. CSS enqueued in a different way via Oxygen Builder

		    add_action( 'wp_print_styles',         array( $this, 'filterStyles' ), 100000 ); // Unload Styles  - HEAD
		    add_action( 'wp_print_scripts',        array( $this, 'filterScripts' ), 100000 ); // Unload Scripts - HEAD

		    add_action( 'wp_print_styles',         array($this, 'printAnySpecialCss'), PHP_INT_MAX );

		    // Unload Styles & Scripts - FOOTER
		    // Needs to be triggered very soon as some old plugins/themes use wp_footer() to enqueue scripts
		    // Sometimes styles are loaded in the BODY section of the page
		    add_action( 'wp_print_footer_scripts', array( $this, 'onPrintFooterScriptsStyles' ), 1 );

            if ( ! is_admin() && ! Plugin::preventAnyFrontendOptimization() ) {
                add_filter( 'style_loader_tag', function( $tag, $handle ) {
                    if ( OptimizeCss::isWorthCheckingForOptimization() ) {
                        ObjectCache::wpacu_cache_set( 'wpacu_style_loader_tag_' . $handle, $tag );
                    }
                    return $tag;
                }, 10, 2 );

                add_filter( 'script_loader_tag', function( $tag, $handle ) {
                    if ( OptimizeJs::isWorthCheckingForOptimization() ) {
                        ObjectCache::wpacu_cache_set( 'wpacu_script_loader_tag_' . $handle, $tag );
                    }
                    return $tag;
                }, 10, 2 );
            }

		    // Preloads
		    add_action( 'wp_head', static function() {
			    if ( (defined('WPACU_ALLOW_ONLY_UNLOAD_RULES') && WPACU_ALLOW_ONLY_UNLOAD_RULES)
			         || Plugin::preventAnyFrontendOptimization()
			         || Main::isTestModeActiveAndVisitorNonAdmin() ) {
				    return;
			    }

			    // Only place the market IF there's at least one preload OR combine JS is activated
			    $preloadsClass = new Preloads();

			    if ( isset( $preloadsClass->preloads[ 'styles' ] ) && ! empty( $preloadsClass->preloads[ 'styles' ] ) ) {
				    echo Preloads::DEL_STYLES_PRELOADS;
			    }

			    if ( (isset( $preloadsClass->preloads[ 'scripts' ] ) && ! empty( $preloadsClass->preloads[ 'scripts' ] )) || OptimizeJs::proceedWithJsCombine() ) {
				    echo Preloads::DEL_SCRIPTS_PRELOADS;
			    }
		    }, 1 );

		    add_filter( 'style_loader_tag', static function( $styleTag, $tagHandle ) {
			    /* [wpacu_timing] */ $wpacuTimingName = 'style_loader_tag'; Misc::scriptExecTimer( $wpacuTimingName ); /* [/wpacu_timing] */

			    if ( Plugin::preventAnyFrontendOptimization() ) {
				    return $styleTag;
			    }

			    // Preload the plugin's CSS for assets management layout (for faster content paint if the user is logged-in and manages the assets in the front-end)
			    // For a better admin experience
			    if ( $tagHandle === WPACU_PLUGIN_ID . '-style' ) {
				    $styleTag = str_ireplace(
					    array( '<link ', 'rel=\'stylesheet\'', 'rel="stylesheet"', 'id=\'', 'id="' ),
					    array(
						    '<link rel=\'preload\' as=\'style\' data-wpacu-preload-it-async=\'1\' ',
						    'onload="this.onload=null;this.rel=\'stylesheet\'"',
						    'onload="this.rel=\'stylesheet\'"',
						    'id=\'wpacu-preload-',
						    'id="wpacu-preload-'
					    ),
					    $styleTag
				    );
			    }

			    // Irrelevant for Critical CSS as the top admin bar is for logged-in users
			    // and if it's not included in the critical CSS it would cause a flash of unstyled content which is not pleasant for the admin
			    if ( $tagHandle === 'admin-bar' ) {
				    $styleTag = str_replace( '<link ', '<link data-wpacu-skip-preload=\'1\' ', $styleTag );
			    }

			    if ( Plugin::preventAnyFrontendOptimization() || Main::isTestModeActiveAndVisitorNonAdmin() ) {
				    /* [wpacu_timing] */ Misc::scriptExecTimer( $wpacuTimingName, 'end' ); /* [/wpacu_timing] */
				    return $styleTag;
			    }

			    // Alter for debugging purposes; triggers before anything else
			    // e.g. you're working on a website and there is no Dashboard access, and you want to determine the handle name
			    // if the handle name is not showing up, then the LINK stylesheet has been hardcoded (not enqueued the WordPress way)
			    if ( isset($_GET['wpacu_show_handle_names']) ) {
				    $styleTag = str_replace( '<link ', '<link data-wpacu-debug-style-handle=\'' . $tagHandle . '\' ', $styleTag );
			    }

			    if ( strpos( $styleTag, 'data-wpacu-style-handle' ) === false ) {
				    $styleTag = str_replace( '<link ', '<link data-wpacu-style-handle=\'' . $tagHandle . '\' ', $styleTag );
			    }

			    /* [wpacu_timing] */ Misc::scriptExecTimer( $wpacuTimingName, 'end' ); /* [/wpacu_timing] */
			    return $styleTag;
		    }, PHP_INT_MAX, 2 ); // Trigger it later in case plugins such as "Ronneby Core" plugin alters it

		    add_filter( 'script_loader_tag', static function( $scriptTag, $tagHandle ) {
			    /* [wpacu_timing] */ $wpacuTimingName = 'script_loader_tag'; Misc::scriptExecTimer( $wpacuTimingName ); /* [/wpacu_timing] */

			    if ( Plugin::preventAnyFrontendOptimization() ) {
				    /* [wpacu_timing] */ Misc::scriptExecTimer( $wpacuTimingName, 'end' ); /* [/wpacu_timing] */
				    return $scriptTag;
			    }

			    // Alter for debugging purposes; triggers before anything else
			    // e.g. you're working on a website and there is no Dashboard access, and you want to determine the handle name
			    // if the handle name is not showing up, then the SCRIPT has been hardcoded (not enqueued the WordPress way)
			    if ( isset($_GET['wpacu_show_handle_names']) ) {
				    $scriptTag = str_replace( '<script ', '<script data-wpacu-debug-script-handle=\'' . $tagHandle . '\' ', $scriptTag );
			    }

			    if ( strpos( $scriptTag, 'data-wpacu-script-handle' ) === false && Main::instance()->isFrontendEditView ) {
				    $scriptTag = str_replace( '<script ', '<script data-wpacu-script-handle=\'' . $tagHandle . '\' ', $scriptTag );
			    }

			    if ( Plugin::preventAnyFrontendOptimization() || Main::isTestModeActiveAndVisitorNonAdmin() ) {
				    /* [wpacu_timing] */ Misc::scriptExecTimer( $wpacuTimingName, 'end' ); /* [/wpacu_timing] */
				    return $scriptTag;
			    }

			    if ( strpos( $scriptTag, 'data-wpacu-script-handle' ) === false ) {
				    $scriptTag = str_replace( '<script ', '<script data-wpacu-script-handle=\'' . $tagHandle . '\' ', $scriptTag );
			    }

			    if ( $tagHandle === 'jquery-core' ) {
				    $scriptTag = str_replace( '<script ', '<script data-wpacu-jquery-core-handle=1 ', $scriptTag );
			    }

			    if ( $tagHandle === 'jquery-migrate' ) {
				    $scriptTag = str_replace( '<script ', '<script data-wpacu-jquery-migrate-handle=1 ', $scriptTag );
			    }

			    /* [wpacu_timing] */ Misc::scriptExecTimer( $wpacuTimingName, 'end' ); /* [/wpacu_timing] */
			    return $scriptTag;
		    }, PHP_INT_MAX, 2 );

		    Preloads::instance()->init();
	    }
	    /*
	     * [END] Front-end page visited (e.g. by the admin or a guest visitor)
	     */

        $this->alterWpStylesScriptsObj();
    }

	/**
	 * @return void
	 */
	public function triggersAfterInitFrontendView()
    {
	    // Fetch the page in the background to see what scripts/styles are already loading
	    // This applies only for front-end loading
	    if ( Main::instance()->isGetAssetsCall || (Menu::userCanManageAssets() && AssetsManager::instance()->frontendShow()) ) {
		    if ( Main::instance()->isGetAssetsCall ) {
			    add_filter( 'show_admin_bar', '__return_false' );
		    }

		    // Save CSS handles list that is printed in the <HEAD>
		    // No room for errors, some developers might enqueue (although not ideal) assets via "wp_head" or "wp_print_styles"/"wp_print_scripts"
		    add_action( 'wp_enqueue_scripts', array( $this, 'saveHeadAssets' ), PHP_INT_MAX - 1 );

		    // Save CSS/JS list that is printed in the <BODY>
		    add_action( 'wp_print_footer_scripts', array( $this, 'saveFooterAssets' ), 100000000 );
		    add_action( 'wp_footer', array( $this, 'printScriptsStyles' ), ( PHP_INT_MAX - 1 ) );
	    }

        /*
           DO NOT disable the features below if the following apply:
           - The option is not enabled
           - Test Mode Enabled & Admin Logged in
           - The user is in the Dashboard (any changes are applied in the front-end view)
        */
	    if ( ! Main::instance()->preventAssetsSettings() ) {
		    if ( Main::instance()->settings['disable_emojis'] == 1 ) {
			    $wpacuCleanUp = new CleanUp();
			    $wpacuCleanUp->doDisableEmojis();
		    }

		    if ( Main::instance()->settings['disable_oembed'] == 1 ) {
			    $wpacuCleanUp = new CleanUp();
			    $wpacuCleanUp->doDisableOembed();
		    }
	    }
    }

	/**
	 * Priority: 8 (earliest)
	 */
	public function setVarsBeforeUpdate()
	{
		// Conditions
		// 1) User has rights to manage the assets and the option is enabled in plugin's Settings
		// 2) Not an AJAX call from the Dashboard
		// 3) Not inside the Dashboard
		Main::instance()->isFrontendEditView = ( Menu::userCanManageAssets() && AssetsManager::instance()->frontendShow() // 1
		                              && ! Main::instance()->isGetAssetsCall // 2
		                              && ! is_admin() ); // 3

		if ( Main::instance()->isFrontendEditView ) {
			$wpacuCleanUp = new CleanUp();
			$wpacuCleanUp->cleanUpHtmlOutputForAssetsCall();
		}

		Main::instance()->getCurrentPostId();

		define( 'WPACU_CURRENT_PAGE_ID', Main::instance()->getCurrentPostId() );
	}

	/**
	 * Priority: 10 (latest)
	 */
	public function setVarsAfterAnyUpdate()
	{
		if ( ! is_admin() ) {
			Main::instance()->globalUnloaded = Main::instance()->getGlobalUnload();

			$getCurrentPost = Main::instance()->getCurrentPost();

			$post = $type = false;

			if (empty($getCurrentPost) && Misc::isHomePage()) {
				$type = 'front_page';
			} elseif ( ! empty($getCurrentPost) )  {
				$type = 'post';
				$post = $getCurrentPost;
				Main::instance()->postTypesUnloaded = (isset($post->post_type) && $post->post_type)
					? Main::instance()->getBulkUnload('post_type', $post->post_type)
					: array();
				}

			// [wpacu_pro]
			if (! $type) {
				// Main::instance()->currentPostId should be 0 in this case
				$type = 'for_pro';
			}
			// [/wpacu_pro]

			Main::$vars['for_type'] = $type;
			Main::$vars['current_post_id'] = Main::instance()->currentPostId;

			if ($post && $type === 'post' && isset($post->post_type) && $post->post_type) {
				Main::$vars['current_post_type'] = $post->post_type;
			}

			}
	}

	/**
	 * In case there were assets enqueued within "wp_footer" action hook, instead of the standard "wp_enqueue_scripts"
	 */
	public function onPrintFooterScriptsStyles()
	{
		self::instance()->filterStyles();
		self::instance()->filterScripts();
	}

	/**
	 * This is useful to change via hooks the "src", "ver" or other values of the loaded handle
	 * Example: You have your theme's main style.css that is needed on every page
	 * On some pages, you only need 20% of it to load, and you can manually trim the other 80% (if you're sure you know which CSS is not used)
	 * You can use a filter hook such as 'wpacu_{main_theme_handle_name_here}_css_handle_obj' to filter the "src" of the object and load an alternative purified CSS file
	 */
	public function alterWpStylesScriptsObj()
	{
        if ( isset($_GET['wpacu_clean_load']) || isset($_GET['wpacu_load_original']) ) {
            return; // this is for debugging purposes, load the original sources
        }

		add_action('wp_print_styles', function() {
			global $wp_styles;

            $assetsToLoop = array();

            if ( ! empty($wp_styles->queue) ) {
                $assetsToLoop = $wp_styles->queue;
            } elseif ( ! empty($wp_styles->registered) ) {
                $assetsToLoop = array_keys($wp_styles->registered);
            }

			if ( ! empty($assetsToLoop) ) {
				foreach ($assetsToLoop as $assetHandle) {
                    if ( ! isset($wp_styles->registered[$assetHandle]) ) {
                        // They were in the queue, but not registered yet; Do not continue
                        continue;
                    }

					$wp_styles->registered[$assetHandle] = $this->maybeFilterAssetObject($wp_styles->registered[$assetHandle], 'css');
				}
			}
		}, 1);

		foreach (array('wp_print_scripts', 'wp_print_footer_scripts') as $actionToAdd) {
			add_action( $actionToAdd, function() {
				global $wp_scripts;

                $assetsToLoop = array();

                if ( ! empty($wp_scripts->queue) ) {
                    $assetsToLoop = $wp_scripts->queue;
                } elseif ( ! empty($wp_scripts->registered) ) {
                    $assetsToLoop = array_keys($wp_scripts->registered);
                }

				if ( ! empty($assetsToLoop) ) {
                    foreach ($assetsToLoop as $assetHandle) {
                        if ( ! isset($wp_scripts->registered[$assetHandle]) ) {
                            // It was in the queue, but not registered yet; Do not continue
                            continue;
                        }

                        $wp_scripts->registered[$assetHandle] = $this->maybeFilterAssetObject($wp_scripts->registered[$assetHandle], 'js');
                    }
				}
			}, 1);
		}
	}

	/**
	 * @param $object | as returned from $wp_styles or $wp_scripts
	 * @param $fileType | "css" or "js"
	 *
	 * @return mixed
	 */
	public function maybeFilterAssetObject($object, $fileType)
	{
		if ( ! isset($object->handle, $object->src) ) {
			return $object;
		}

        $object->handleRef = $object->handle;

        $refString = 'gt_widget_script_';

        // Special case (GTranslate plugin | 'gt_widget_script_' + random unique number added to it)
        if (strpos($object->handle, $refString) === 0) {
            $maybeRandNum = str_replace($refString, '', $object->handle);

            if (is_numeric($maybeRandNum)) {
                $object->handleRef = $refString . 'gtranslate';
            }
        }

		$filterTagName = 'wpacu_'.$object->handleRef.'_' . $fileType . '_handle_data';

        if ( has_filter($filterTagName) ) {
			$originData = (array)$object;
			$newData = apply_filters( $filterTagName, $originData );

			if ( isset($originData['src'], $newData['src']) && $newData['src'] !== $originData['src'] ) {
				$object->src = $newData['src'];
				$object->src_origin = $originData['src'];

				$object->ver = $newData['ver'] ?: null;
				$object->ver_origin = isset($originData['ver']) ? $originData['ver'] : null;
			}
		}

		return $object;
	}

	/**
	 * @param $ignoreChildParentList
	 *
	 * @return array
	 */
	public function filterIgnoreChildParentList($ignoreChildParentList)
	{
		if (isset(Main::instance()->ignoreChildrenHandlesOnTheFly['styles']) && ! empty(Main::instance()->ignoreChildrenHandlesOnTheFly['styles'])) {
			foreach (Main::instance()->ignoreChildrenHandlesOnTheFly['styles'] as $cssHandle) {
				$ignoreChildParentList['styles'][$cssHandle] = 1;
			}
		}

		if (isset(Main::instance()->ignoreChildrenHandlesOnTheFly['scripts']) && ! empty(Main::instance()->ignoreChildrenHandlesOnTheFly['scripts'])) {
			foreach (Main::instance()->ignoreChildrenHandlesOnTheFly['scripts'] as $jsHandle) {
				$ignoreChildParentList['scripts'][$jsHandle] = 1;
			}
		}

		return $ignoreChildParentList;
	}

    /**
     * @return mixed|null
     */
    public static function buildUnloadList($assetType)
    {
        /*
         * [All unloaded styles]
         */
        if ($assetType === 'styles') {
            $globalUnload = Main::instance()->globalUnloaded;

            // Post, Page, Front-page and more
            $toRemove = Main::instance()->getAssetsUnloadedPageLevel();

            $jsonList = @json_decode($toRemove);

            $list = array();

            if (isset($jsonList->styles)) {
                $list = (array)$jsonList->styles;
            }

            // Any global unloaded styles? Append them
            if ( ! empty($globalUnload['styles'])) {
                foreach ($globalUnload['styles'] as $handleStyle) {
                    $list[] = $handleStyle;
                }
            }

            if (Main::isSingularPage()) {
                // Any bulk unloaded styles (e.g. for all pages belonging to a post type)? Append them
                if (empty(Main::instance()->postTypesUnloaded)) {
                    $post                               = Main::instance()->getCurrentPost();
                    Main::instance()->postTypesUnloaded = (isset($post->post_type) && $post->post_type)
                        ? Main::instance()->getBulkUnload('post_type', $post->post_type)
                        : array();
                }

                if (isset(Main::instance()->postTypesUnloaded['styles']) && ! empty(Main::instance()->postTypesUnloaded['styles'])) {
                    foreach (Main::instance()->postTypesUnloaded['styles'] as $handleStyle) {
                        $list[] = $handleStyle;
                    }
                }
            }

            // Site-Wide Unload for "Dashicons" if user is not logged-in
            if (Main::instance()->settings['disable_dashicons_for_guests'] && ! is_user_logged_in()) {
                $list[] = 'dashicons';
            }

            // Any bulk unloaded styles for 'category', 'post_tag' and more?
            // If the Pro version is enabled, any of the unloaded CSS will be added to the list
            $list = apply_filters('wpacu_filter_styles_list_unload', array_unique($list));
        }
        /*
         * [/All unloaded styles]
         */

        /*
         * [All unloaded scripts]
         */
        if ($assetType === 'scripts') {
            $globalUnload = Main::instance()->globalUnloaded;

            // Post, Page or Front-page?
            $toRemove = Main::instance()->getAssetsUnloadedPageLevel();

            $jsonList = @json_decode( $toRemove );

            $list = array();

            if ( isset( $jsonList->scripts ) ) {
                $list = (array) $jsonList->scripts;
            }

            // Any global unloaded styles? Append them
            if ( ! empty( $globalUnload['scripts'] ) ) {
                foreach ( $globalUnload['scripts'] as $handleScript ) {
                    $list[] = $handleScript;
                }
            }

            if ( Main::isSingularPage() ) {
                // Any bulk unloaded styles (e.g. for all pages belonging to a post type)? Append them
                if ( empty( Main::instance()->postTypesUnloaded ) ) {
                    $post = Main::instance()->getCurrentPost();

                    // Make sure the post_type is set; it's not in specific pages (e.g. BuddyPress ones)
                    Main::instance()->postTypesUnloaded = ( isset( $post->post_type ) && $post->post_type )
                        ? Main::instance()->getBulkUnload( 'post_type', $post->post_type )
                        : array();
                }

                if ( isset( Main::instance()->postTypesUnloaded['scripts'] ) && ! empty( Main::instance()->postTypesUnloaded['scripts'] ) ) {
                    foreach ( Main::instance()->postTypesUnloaded['scripts'] as $handleStyle ) {
                        $list[] = $handleStyle;
                    }
                }
            }

            // Any bulk unloaded styles for 'category', 'post_tag' and more?
            // These are PRO rules or rules added via custom coding
            $list = apply_filters( 'wpacu_filter_scripts_list_unload', array_unique( $list ) );

            global $wp_scripts;

            $allScripts = $wp_scripts;

            if ( $allScripts !== null && ! empty( $allScripts->registered ) ) {
                foreach ( $allScripts->registered as $handle => $value ) {
                    // This could be triggered several times, check if the script already exists
                    if ( ! isset( Main::instance()->wpAllScripts['registered'][ $handle ] ) ) {
                        Main::instance()->wpAllScripts['registered'][ $handle ] = $value;
                        if ( in_array( $handle, $allScripts->queue ) ) {
                            Main::instance()->wpAllScripts['queue'][] = $handle;
                        }
                    }

                    // [wpacu_pro]
                    $initialPos = ( isset( $wp_scripts->registered[ $handle ]->extra['group'] ) && $wp_scripts->registered[ $handle ]->extra['group'] === 1 ) ? 'body' : 'head';
                    ObjectCache::wpacu_cache_add ( $handle, $initialPos, 'wpacu_scripts_initial_positions' );
                    // [/wpacu_pro]
                }

                if ( isset( Main::instance()->wpAllScripts['queue'] ) && ! empty( Main::instance()->wpAllScripts['queue'] ) ) {
                    Main::instance()->wpAllScripts['queue'] = array_unique( Main::instance()->wpAllScripts['queue'] );
                }
            }
        }
        /*
         * [/All unloaded scripts]
         */

        return $list;
    }

    /**
     * @param $assetType
     *
     * @return mixed|null
     */
    public static function buildLoadExceptionList($list, $assetType)
    {
        /*
         * [All load exception styles]
         */
        if ($assetType === 'styles') {
            // Load exception rules ALWAYS have priority over the unloading ones
            // Thus, if an exception is found, the handle will be removed from the unloading list
            // Let's see if there are load exceptions for this page or site-wide (e.g. for logged-in users)
            // Only check for any load exceptions if the unloading list has at least one item
            // Otherwise the action is irrelevant since the assets are loaded anyway by default

            // These are common rules triggered in both LITE & PRO plugins
            $list = ( ! empty($list) ) ? Main::instance()->filterAssetsUnloadList($list, 'styles','load_exception') : $list;

            // These are pro rules OR rules added via custom coding
            $list = ( ! empty($list) ) ? apply_filters('wpacu_filter_styles_list_load_exception', $list) : $list;
        }
        /*
         * [/All load exception styles]
         */

        /*
         * [All load exception scripts]
         */
        if ($assetType === 'scripts') {
            // Load exception rules ALWAYS have priority over the unloading ones
            // Thus, if an exception is found, the handle will be removed from the unloading list
            // Let's see if there are load exceptions for this page or site-wide (e.g. for logged-in users)

            // These are common rules triggered in both LITE & PRO plugins
            $list = ( ! empty($list) ) ? Main::instance()->filterAssetsUnloadList($list, 'scripts', 'load_exception') : $list;

            // These are pro rules OR rules added via custom coding
            // Only check for any load exceptions if the unloading list has at least one item
            // Otherwise the action is irrelevant since the assets are loaded anyway by default
            $list = ( ! empty($list) ) ? apply_filters('wpacu_filter_scripts_list_load_exception', $list) : $list;
        }
        /*
         * [/All load exception scripts]
         */

        return $list;
    }

	/* [START] Styles Dequeue */
	/**
	 * See if there is any list with styles to be removed in JSON format
	 * Only the handles (the ID of the styles) is stored
	 */
	public function filterStyles()
	{
		/* [wpacu_timing] */ Misc::scriptExecTimer( 'filter_dequeue_styles' );/* [/wpacu_timing] */

		if (is_admin()) {
			return;
		}

		global $wp_styles;

		if (current_action() === 'wp_print_styles') {
			ObjectCache::wpacu_cache_set('wpacu_styles_object_after_wp_print_styles', $wp_styles);
		}

		$list = array();

		if (current_action() === 'wp_print_footer_scripts') {
			$cachedWpStyles = ObjectCache::wpacu_cache_get('wpacu_styles_object_after_wp_print_styles');
			if (isset($cachedWpStyles->registered) && count($cachedWpStyles->registered) === count($wp_styles->registered)) {
				// The list was already generated in "wp_print_styles" and the number of registered assets are the same
				// Save resources and do not re-generate it
				$list = ObjectCache::wpacu_cache_get('wpacu_styles_handles_marked_for_unload');
			}
		}

		if ( empty($list) || ! is_array($list) ) {
			/*
			* [START] Build unload list
			*/
			$list = self::buildUnloadList('styles');
			/*
			* [END] Build unload list
			*/

			// Add handles such as the Oxygen Builder CSS ones that are missing and added differently to the queue
			$allStyles = $this->wpStylesFilter( $wp_styles, 'registered', $list );

			if ( $allStyles !== null && ! empty( $allStyles->registered ) ) {
				// Going through all the registered styles
				foreach ( $allStyles->registered as $handle => $value ) {
					// This could be triggered several times, check if the style already exists
					if ( ! isset( Main::instance()->wpAllStyles['registered'][ $handle ] ) ) {
						Main::instance()->wpAllStyles['registered'][ $handle ] = $value;
						if ( in_array( $handle, $allStyles->queue ) ) {
							Main::instance()->wpAllStyles['queue'][] = $handle;
						}
					}
				}

				if ( isset( Main::instance()->wpAllStyles['queue'] ) && ! empty( Main::instance()->wpAllStyles['queue'] ) ) {
					Main::instance()->wpAllStyles['queue'] = array_unique( Main::instance()->wpAllStyles['queue'] );
				}
			}

			if ( isset( Main::instance()->wpAllStyles['registered'] ) && ! empty( Main::instance()->wpAllStyles['registered'] ) ) {
				ObjectCache::wpacu_cache_set( 'wpacu_all_styles_handles', array_keys( Main::instance()->wpAllStyles['registered'] ) );
			}

			// e.g. for test/debug mode or AJAX calls (where all assets have to load)
			if ( isset($_REQUEST['wpacu_no_css_unload']) ) {
				// [wpacu_pro]
				// Don't forget (before preventing the unloading) to mark the ones that are set to be moved to BODY or HEAD
				// Make sure it is triggered even if the unload list is empty as the user might just want to move assets on this page
				do_action( 'wpacu_pro_mark_enqueued_styles_to_load_in_new_position', $list );
				// [wpacu_pro]

				/* [wpacu_timing] */Misc::scriptExecTimer( 'filter_dequeue_styles', 'end' ); /* [/wpacu_timing] */
				return;
			}

			if ( Main::instance()->preventAssetsSettings(array('assets_call')) ) {
				/* [wpacu_timing] */Misc::scriptExecTimer( 'filter_dequeue_styles', 'end' ); /* [/wpacu_timing] */
				return;
			}

			/*
			* [START] Load Exception Check
			* */
            $list = self::buildLoadExceptionList($list, 'styles');
			/*
			 * [END] Load Exception Check
			 * */

			// [wpacu_pro]
			if ( ! Main::instance()->isGetAssetsCall ) {
				// Only relevant if the regular page is viewed (not when the assets are fetched from the Dashboard)
				// Make sure it is triggered even if the unload list is empty as the user might just want to move assets on this page
				do_action( 'wpacu_pro_mark_enqueued_styles_to_load_in_new_position', $list );
			}
			// [/wpacu_pro]

			// Is $list still empty? Nothing to unload? Stop here
			if (empty($list)) {
				/* [wpacu_timing] */ Misc::scriptExecTimer( 'filter_dequeue_styles', 'end' ); /* [/wpacu_timing] */
				return;
			}
		}

		$ignoreChildParentList = apply_filters('wpacu_ignore_child_parent_list', Main::instance()->getIgnoreChildren());

		foreach ($list as $handle) {
			if (isset($ignoreChildParentList['styles'], Main::instance()->wpAllStyles['registered'][$handle]->src)
			    && is_array($ignoreChildParentList['styles']) && array_key_exists($handle, $ignoreChildParentList['styles'])) {
				// Do not dequeue it as it's "children" will also be dequeued (ignore rule is applied)
				// It will be stripped by cleaning its LINK tag from the HTML Source
				Main::instance()->ignoreChildren['styles'][$handle] = Main::instance()->wpAllStyles['registered'][$handle]->src;
				Main::instance()->ignoreChildren['styles'][$handle.'_has_unload_rule'] = 1;
				Main::instance()->allUnloadedAssets['styles'][] = $handle;
				continue;
			}

			$handle = trim($handle);

			// Ignore auto generated handles for the hardcoded CSS as they were added for reference purposes
			// They will get stripped later on via OptimizeCommon.php
			if (strpos($handle, 'wpacu_hardcoded_link_') === 0) {
				// [wpacu_pro]
				$saveMarkedHandles   = ObjectCache::wpacu_cache_get('wpacu_hardcoded_links') ?: array();
				$saveMarkedHandles[] = $handle;
				Main::instance()->allUnloadedAssets['styles'][] = $handle; // for "wpacu_no_load" on hardcoded list
				ObjectCache::wpacu_cache_set( 'wpacu_hardcoded_links', $saveMarkedHandles );
				// [/wpacu_pro]
				continue; // the handle is used just for reference for later stripping via altering the DOM
			}

			if (strpos($handle, 'wpacu_hardcoded_style_') === 0) {
				// [wpacu_pro]
				$saveMarkedHandles   = ObjectCache::wpacu_cache_get('wpacu_hardcoded_styles') ?: array();
				$saveMarkedHandles[] = $handle;
				Main::instance()->allUnloadedAssets['styles'][] = $handle; // for "wpacu_no_load" on hardcoded list
				ObjectCache::wpacu_cache_set( 'wpacu_hardcoded_styles', $saveMarkedHandles );
				// [/wpacu_pro]
				continue; // the handle is used just for reference for later stripping via altering the DOM
			}

			// Do not unload "dashicons" if the top WordPress admin bar is showing up
			if ($handle === 'dashicons' && is_admin_bar_showing()) {
				continue;
			}

			Main::instance()->allUnloadedAssets['styles'][] = $handle;

			// Only trigger the unloading on regular page load, not when the assets list is collected
			if ( ! Main::instance()->isGetAssetsCall ) {
				wp_deregister_style( $handle );
				wp_dequeue_style( $handle );
			}
		}

		if (current_action() === 'wp_print_styles') {
			ObjectCache::wpacu_cache_set( 'wpacu_styles_handles_marked_for_unload', $list );
		}

		/* [wpacu_timing] */ Misc::scriptExecTimer( 'filter_dequeue_styles', 'end' ); /* [/wpacu_timing] */
	}

	/**
	 * @param $wpStylesFilter
	 * @param string $listType
	 * @param array $unloadedList
	 *
	 * @return mixed
	 */
	public function wpStylesFilter($wpStylesFilter, $listType, $unloadedList = array())
	{
		global $wp_styles, $oxygen_vsb_css_styles;

		if ( ( $listType === 'registered' ) && is_object( $oxygen_vsb_css_styles ) && ! empty( $oxygen_vsb_css_styles->registered ) ) {
			$stylesSpecialCases = array();

			foreach ($oxygen_vsb_css_styles->registered as $oxygenHandle => $oxygenValue) {
				if (! array_key_exists($oxygenHandle, $wp_styles->registered)) {
					$wpStylesFilter->registered[$oxygenHandle] = $oxygenValue;
					$stylesSpecialCases[$oxygenHandle] = $oxygenValue->src;
				}
			}

			$unloadedSpecialCases = array();

			foreach ($unloadedList as $unloadedHandle) {
				if (array_key_exists($unloadedHandle, $stylesSpecialCases)) {
					$unloadedSpecialCases[$unloadedHandle] = $stylesSpecialCases[$unloadedHandle];
				}
			}

			if (! empty($unloadedSpecialCases)) {
				// This will be later used in 'wp_loaded' below to extract the special styles
				echo Main::$wpStylesSpecialDelimiters['start'] . wp_json_encode($unloadedSpecialCases) . Main::$wpStylesSpecialDelimiters['end'];
			}
		}

		if ( ( $listType === 'done' ) && isset( $oxygen_vsb_css_styles->done ) && is_object( $oxygen_vsb_css_styles ) ) {
			foreach ($oxygen_vsb_css_styles->done as $oxygenHandle) {
				if (! in_array($oxygenHandle, $wp_styles->done)) {
					$wpStylesFilter[] = $oxygenHandle;
				}
			}
		}

		if ( ( $listType === 'queue' ) && isset( $oxygen_vsb_css_styles->queue ) && is_object( $oxygen_vsb_css_styles ) ) {
			foreach ($oxygen_vsb_css_styles->queue as $oxygenHandle) {
				if (! in_array($oxygenHandle, $wp_styles->queue)) {
					$wpStylesFilter[] = $oxygenHandle;
				}
			}
		}

		return $wpStylesFilter;
	}

	/**
	 *
	 */
	public static function filterStylesSpecialCases()
	{
		if ( isset($_REQUEST['wpacu_no_css_unload']) ) {
			return;
		}

		add_action('wp_loaded', static function() {
			ob_start(static function($htmlSource) {
				if (strpos($htmlSource, Main::$wpStylesSpecialDelimiters['start']) === false && strpos($htmlSource, Main::$wpStylesSpecialDelimiters['end']) === false) {
					return $htmlSource;
				}

				$jsonStylesSpecialCases = Misc::extractBetween($htmlSource, Main::$wpStylesSpecialDelimiters['start'], Main::$wpStylesSpecialDelimiters['end']);

				$stylesSpecialCases = json_decode($jsonStylesSpecialCases, ARRAY_A);

				if (! empty($stylesSpecialCases) && Misc::jsonLastError() === JSON_ERROR_NONE) {
					foreach ($stylesSpecialCases as $styleSrc) {
						$styleLocalSrc = Misc::getLocalSrcIfExist($styleSrc);
						$styleRelSrc = isset($styleLocalSrc['rel_src']) ? $styleLocalSrc['rel_src'] : $styleSrc;
						$htmlSource = CleanUp::cleanLinkTagFromHtmlSource($styleRelSrc, $htmlSource);
					}

					// Strip the info HTML comment
					$htmlSource = str_replace(
						Main::$wpStylesSpecialDelimiters['start'] . $jsonStylesSpecialCases . Main::$wpStylesSpecialDelimiters['end'],
						'',
						$htmlSource
					);
				}

				return $htmlSource;
			});
		}, 1);
	}

	/**
	 *
	 */
	public function printAnySpecialCss()
	{
		if (isset(Main::instance()->allUnloadedAssets['styles']) &&
		    ! empty(Main::instance()->allUnloadedAssets['styles']) &&
		    in_array('photoswipe', Main::instance()->allUnloadedAssets['styles'])) {
			?>
			<?php if (current_user_can('administrator')) { ?><!-- Asset CleanUp: "photoswipe" unloaded (avoid printing useless HTML) --><?php } ?>
			<style <?php echo Misc::getStyleTypeAttribute(); ?>>.pswp { display: none; }</style>
			<?php
		}
	}
	/* [END] Styles Dequeue */

	/* [START] Scripts Dequeue */
	/**
	 * See if there is any list with scripts to be removed in JSON format
	 * Only the handles (the ID of the scripts) are saved
	 */
	public function filterScripts()
	{
		/* [wpacu_timing] */ Misc::scriptExecTimer( 'filter_dequeue_scripts' );/* [/wpacu_timing] */

		if (is_admin()) {
			return;
		}

		global $wp_scripts;

		if (current_action() === 'wp_print_scripts') {
			ObjectCache::wpacu_cache_set('wpacu_scripts_object_after_wp_print_scripts', $wp_scripts);
		}

		$list = array();

		if (current_action() === 'wp_print_footer_scripts') {
			$cachedWpScripts = ObjectCache::wpacu_cache_get('wpacu_scripts_object_after_wp_print_scripts');
			if (isset($cachedWpScripts->registered) && count($cachedWpScripts->registered) === count($wp_scripts->registered)) {
				// The list was already generated in "wp_print_scripts" and the number of registered assets are the same
				// Save resources and do not re-generate it
				$list = ObjectCache::wpacu_cache_get('wpacu_scripts_handles_marked_for_unload');
			}
		}

		if ( empty($list) ) {
			/*
			* [START] Build unload list
			*/
            $list = self::buildUnloadList('scripts');
			/*
			* [END] Build unload list
			*/

			/*
			* [START] Load Exception Check
			* */
            $list = self::buildLoadExceptionList($list, 'scripts');
			/*
			 * [END] Load Exception Check
			 * */

			// [wpacu_pro]
			if ( ! Main::instance()->isGetAssetsCall ) {
				// Only relevant if the regular page is viewed (not when the assets are fetched from the Dashboard)
				// Make sure it is triggered even if the unload list is empty as the user might just want to move assets on this page
				// Are there any scripts that have their location changed from HEAD to BODY or the other way around?
				do_action( 'wpacu_pro_mark_enqueued_scripts_to_load_in_new_position' );
			}
			// [/wpacu_pro]

			// Nothing to unload
			if ( empty( $list ) ) {
				/* [wpacu_timing] */Misc::scriptExecTimer( 'filter_dequeue_scripts', 'end' ); /* [/wpacu_timing] */
				return;
			}

			// e.g. for test/debug mode or AJAX calls (where all assets have to load)
			if ( isset($_REQUEST['wpacu_no_js_unload']) || Main::instance()->preventAssetsSettings(array('assets_call')) ) {
				/* [wpacu_timing] */Misc::scriptExecTimer( 'filter_dequeue_scripts', 'end' ); /* [/wpacu_timing] */
				return;
			}
		}

		$ignoreChildParentList = apply_filters('wpacu_ignore_child_parent_list', Main::instance()->getIgnoreChildren());

		foreach ($list as $handle) {
			$handle = trim($handle);

			// Ignore auto generated handles for the hardcoded CSS as they were added for reference purposes
			// They will get stripped later on via OptimizeCommon.php
			// The handle is used just for reference for later stripping via altering the DOM
			if (strpos($handle, 'wpacu_hardcoded_script_inline_') !== false || strpos($handle, 'wpacu_hardcoded_noscript_inline_') !== false) {
				// [wpacu_pro]
				$saveMarkedHandles = ObjectCache::wpacu_cache_get('wpacu_hardcoded_scripts_noscripts_inline') ?: array();
				$saveMarkedHandles[] = $handle;
				Main::instance()->allUnloadedAssets['scripts'][] = $handle; // for "wpacu_no_load" on hardcoded list
				ObjectCache::wpacu_cache_set( 'wpacu_hardcoded_scripts_noscripts_inline', $saveMarkedHandles );
				// [/wpacu_pro]
				continue;
			}

			if (strpos($handle, 'wpacu_hardcoded_script_src_') !== false) {
				// [wpacu_pro]
				$saveMarkedHandles = ObjectCache::wpacu_cache_get('wpacu_hardcoded_scripts_src') ?: array();
				$saveMarkedHandles[] = $handle;
				Main::instance()->allUnloadedAssets['scripts'][] = $handle; // for "wpacu_no_load" on hardcoded list
				ObjectCache::wpacu_cache_set( 'wpacu_hardcoded_scripts_src', $saveMarkedHandles );
				// [/wpacu_pro]
				continue;
			}

			// Special Action for 'jquery-migrate' handler as it's tied to 'jquery'
			if ($handle === 'jquery-migrate' && isset(Main::instance()->wpAllScripts['registered']['jquery'])) {
				$jQueryRegScript = Main::instance()->wpAllScripts['registered']['jquery'];

				if (isset($jQueryRegScript->deps)) {
					$jQueryRegScript->deps = array_diff($jQueryRegScript->deps, array('jquery-migrate'));
				}

				if (Misc::isPluginActive('jquery-updater/jquery-updater.php')) {
					wp_dequeue_script($handle);
				}

				// [wpacu_pro]
                if (! defined('WPACU_JQUERY_MIGRATE_UNLOADED')) { define('WPACU_JQUERY_MIGRATE_UNLOADED', true); }
                // [/wpacu_pro]
				continue;
			}

			// [wpacu_pro]
            if (in_array($handle, array('jquery', 'jquery-core')) && ! defined('WPACU_JQUERY_UNLOADED')) { define('WPACU_JQUERY_UNLOADED', true); }
            // [/wpacu_pro]

			if (isset($ignoreChildParentList['scripts'], Main::instance()->wpAllScripts['registered'][$handle]->src) && is_array($ignoreChildParentList['scripts']) && array_key_exists($handle, $ignoreChildParentList['scripts'])) {
				// Do not dequeue it as it's "children" will also be dequeued (ignore rule is applied)
				// It will be stripped by cleaning its SCRIPT tag from the HTML Source
				Main::instance()->ignoreChildren['scripts'][$handle] = Main::instance()->wpAllScripts['registered'][$handle]->src;
				Main::instance()->ignoreChildren['scripts'][$handle.'_has_unload_rule'] = 1;
				Main::instance()->allUnloadedAssets['scripts'][] = $handle;
				continue;
			}

			Main::instance()->allUnloadedAssets['scripts'][] = $handle;

			// Only trigger the unloading on regular page load, not when the assets list is collected
			if ( ! Main::instance()->isGetAssetsCall ) {
                $handle = Main::maybeGetOriginalNonUniqueHandleName($handle, 'scripts');

				wp_deregister_script( $handle );
				wp_dequeue_script( $handle );
			}
		}

		if (current_action() === 'wp_print_scripts') {
			ObjectCache::wpacu_cache_set( 'wpacu_scripts_handles_marked_for_unload', $list );
		}

		/* [wpacu_timing] */ Misc::scriptExecTimer( 'filter_dequeue_scripts', 'end' ); /* [/wpacu_timing] */
	}
	/* [END] Scripts Dequeue */

	/**
	 *
	 */
	public function saveHeadAssets()
	{
		global $wp_styles, $wp_scripts;

		if (isset(Main::instance()->wpAllStyles['queue']) && ! empty(Main::instance()->wpAllStyles['queue'])) {
			Main::instance()->stylesInHead = Main::instance()->wpAllStyles['queue'];
		}

		if (! empty($wp_styles->queue)) {
			foreach ($wp_styles->queue as $styleHandle) {
				Main::instance()->stylesInHead[] = $styleHandle;
			}
		}

		Main::instance()->stylesInHead = array_unique(Main::instance()->stylesInHead);

		if (isset(Main::instance()->wpAllScripts['queue']) && ! empty(Main::instance()->wpAllScripts['queue'])) {
			Main::instance()->scriptsInHead = Main::instance()->wpAllScripts['queue'];
		}

		if (! empty($wp_scripts->queue)) {
			foreach ($wp_scripts->queue as $scriptHandle) {
				Main::instance()->scriptsInHead[] = $scriptHandle;
			}
		}

		Main::instance()->scriptsInHead = array_unique(Main::instance()->scriptsInHead);

		}

	/**
	 *
	 */
	public function saveFooterAssets()
	{
		global $wp_scripts, $wp_styles;

		// [Styles Collection]
		$footerStyles = array();

		if (isset(Main::instance()->wpAllStyles['queue']) && ! empty(Main::instance()->wpAllStyles['queue'])) {
			foreach ( Main::instance()->wpAllStyles['queue'] as $handle ) {
				if ( ! in_array( $handle, Main::instance()->stylesInHead ) ) {
					$footerStyles[] = $handle;
				}
			}
		}

		if (! empty($wp_styles->queue)) {
			foreach ( $wp_styles->queue as $handle ) {
				if ( ! in_array( $handle, Main::instance()->stylesInHead ) ) {
					$footerStyles[] = $handle;
				}
			}
		}

		Main::instance()->assetsInFooter['styles'] = array_unique($footerStyles);
		// [/Styles Collection]

		// [Scripts Collection]
		Main::instance()->assetsInFooter['scripts'] = ! empty($wp_scripts->in_footer) ? $wp_scripts->in_footer : array();

		if (isset(Main::instance()->wpAllScripts['queue']) && ! empty(Main::instance()->wpAllScripts['queue'])) {
			foreach ( Main::instance()->wpAllScripts['queue'] as $handle ) {
				if ( ! in_array( $handle, Main::instance()->scriptsInHead ) ) {
					Main::instance()->assetsInFooter['scripts'][] = $handle;
				}
			}
		}

		if (! empty($wp_scripts->queue)) {
			foreach ( $wp_scripts->queue as $handle ) {
				if ( ! in_array( $handle, Main::instance()->scriptsInHead ) ) {
					Main::instance()->assetsInFooter['scripts'][] = $handle;
				}
			}
		}

		Main::instance()->assetsInFooter['scripts'] = array_unique(Main::instance()->assetsInFooter['scripts']);
		// [/Scripts Collection]

		}

	/**
	 * This output will be extracted and the JSON will be processed
	 * in the WP Dashboard when editing a post
	 *
	 * It will also print the asset list in the front-end
	 * if the option was enabled in the Settings
	 */
	public function printScriptsStyles()
	{
		// Not for WordPress AJAX calls
		if (Main::$domGetType === 'direct' && defined('DOING_AJAX') && DOING_AJAX) {
			return;
		}

		$isFrontEndEditView = Main::instance()->isFrontendEditView;
		$isDashboardEditView = (! $isFrontEndEditView && Main::instance()->isGetAssetsCall);

		if (! $isFrontEndEditView && ! $isDashboardEditView) {
			return;
		}

		if ($isFrontEndEditView && isset($_GET['elementor-preview']) && $_GET['elementor-preview']) {
			return;
		}

		/* [wpacu_timing] */ $wpacuTimingName = 'output_css_js_manager'; Misc::scriptExecTimer($wpacuTimingName); /* [/wpacu_timing] */

		// Prevent plugins from altering the DOM
		add_filter('w3tc_minify_enable', '__return_false');

		Misc::w3TotalCacheFlushObjectCache();

		// This is the list of the scripts and styles that were eventually loaded
		// We have also the list of the ones that were unloaded
		// located in Main::instance()->wpScripts and Main::instance()->wpStyles
		// We will add it to the list as they will be marked

		$stylesBeforeUnload = Main::instance()->wpAllStyles;
		$scriptsBeforeUnload = Main::instance()->wpAllScripts;

		global $wp_scripts, $wp_styles;

		$list = array();

		// e.g. for "Loaded" and "Unloaded" statuses
		$currentUnloadedAll = isset(Main::instance()->allUnloadedAssets)
            ? Main::instance()->allUnloadedAssets
            : array('styles' => array(), 'scripts' => array());

		foreach (array('styles', 'scripts') as $assetType) {
			if ( isset( $currentUnloadedAll[$assetType] ) ) {
				$currentUnloadedAll[$assetType] = array_unique( $currentUnloadedAll[$assetType] );
			}
		}

		$manageStylesCore = isset($wp_styles->done) && is_array($wp_styles->done) ? $wp_styles->done : array();
		$manageStyles     = self::instance()->wpStylesFilter($manageStylesCore, 'done');

		$manageScripts    = isset($wp_scripts->done) && is_array($wp_scripts->done) ? $wp_scripts->done : array();

		if ($isFrontEndEditView) {
			if (! empty(Main::instance()->wpAllStyles) && isset(Main::instance()->wpAllStyles['queue'])) {
				$manageStyles = self::instance()->wpStylesFilter(Main::instance()->wpAllStyles['queue'], 'queue');
			}

			if (! empty(Main::instance()->wpAllScripts) && isset(Main::instance()->wpAllScripts['queue'])) {
				$manageScripts = Main::instance()->wpAllScripts['queue'];
			}

			if (! empty($currentUnloadedAll['styles'])) {
				foreach ( $currentUnloadedAll['styles'] as $currentUnloadedStyleHandle ) {
					if ( ! in_array( $currentUnloadedStyleHandle, $manageStyles ) ) {
						$manageStyles[] = $currentUnloadedStyleHandle;
					}
				}
			}

			if (! empty($manageStylesCore)) {
				foreach ($manageStylesCore as $wpDoneStyle) {
					if ( ! in_array( $wpDoneStyle, $manageStyles ) ) {
						$manageStyles[] = $wpDoneStyle;
					}
				}
			}

			$manageStyles = array_unique($manageStyles);

			if (! empty($currentUnloadedAll['scripts'])) {
				foreach ( $currentUnloadedAll['scripts'] as $currentUnloadedScriptHandle ) {
					if ( ! in_array( $currentUnloadedScriptHandle, $manageScripts ) ) {
						$manageScripts[] = $currentUnloadedScriptHandle;
					}
				}
			}

			if (! empty($wp_scripts->done)) {
				foreach ($wp_scripts->done as $wpDoneScript) {
					if ( ! in_array( $wpDoneScript, $manageScripts ) ) {
						$manageScripts[] = $wpDoneScript;
					}
				}
			}

			$manageScripts = array_unique($manageScripts);
		}

		/*
		 * Style List
		 */
		if ($isFrontEndEditView) { // "Manage in the Front-end"
			$stylesList = $stylesBeforeUnload['registered'];
		} else { // "Manage in the Dashboard"
			$stylesListFilterAll = self::instance()->wpStylesFilter($wp_styles, 'registered');
			$stylesList = $stylesListFilterAll->registered;
		}

		if (! empty($stylesList)) {
			foreach ($manageStyles as $handle) {
				if (! isset($stylesList[$handle]) || in_array($handle, self::instance()->getSkipAssets('styles'))) {
					continue;
				}

				$list['styles'][] = $stylesList[$handle];
			}

			// Append unloaded ones (if any)
			if (! empty($stylesBeforeUnload) && ! empty($currentUnloadedAll['styles'])) {
				foreach ($currentUnloadedAll['styles'] as $sbuHandle) {
					if (! in_array($sbuHandle, $manageStyles)) {
						// Could be an old style that is not loaded anymore
						// We have to check that
						if (! isset($stylesBeforeUnload['registered'][$sbuHandle])) {
							continue;
						}

						$sbuValue = $stylesBeforeUnload['registered'][$sbuHandle];
						$list['styles'][] = $sbuValue;
					}
				}
			}

			ksort($list['styles']);
		}

		/*
		* Scripts List
		*/
		$scriptsList = $wp_scripts->registered;

		if ($isFrontEndEditView) {
			$scriptsList = $scriptsBeforeUnload['registered'];
		}

		if (! empty($scriptsList)) {
			/* These scripts below are used by this plugin (except admin-bar) and they should not show in the list
			   as they are loaded only when you (or other admin) manage the assets, never for your website visitors */
			foreach ($manageScripts as $handle) {
				if (! isset($scriptsList[$handle]) || in_array($handle, self::instance()->getSkipAssets('scripts'))) {
					continue;
				}

				$list['scripts'][] = $scriptsList[$handle];
			}

			// Append unloaded ones (if any)
			if (! empty($scriptsBeforeUnload) && ! empty($currentUnloadedAll['scripts'])) {
				foreach ($currentUnloadedAll['scripts'] as $sbuHandle) {
					if (! in_array($sbuHandle, $manageScripts)) {
						// Could be an old script that is not loaded anymore
						// We have to check that
						if (! isset($scriptsBeforeUnload['registered'][$sbuHandle])) {
							continue;
						}

						$sbuValue = $scriptsBeforeUnload['registered'][$sbuHandle];

						$list['scripts'][] = $sbuValue;
					}
				}
			}

			ksort($list['scripts']);

			}

		if (! empty($list)) {
			Update::updateHandlesInfo( $list );
		}

		// Front-end View while admin is logged in
		if ($isFrontEndEditView) {
			$wpacuSettings = new Settings();

			$data = array(
				'is_frontend_view'            => true,
				'post_type'                   => '',
				'bulk_unloaded'               => array( 'post_type' => array() ),
				'plugin_settings'             => $wpacuSettings->getAll(),
				'current_unloaded_all'        => $currentUnloadedAll,
				'current_unloaded_page_level' => Main::instance()->getAssetsUnloadedPageLevel( Main::instance()->getCurrentPostId(), true )
			);

			$data['wpacu_page_just_updated'] = false;

			if (isset($_GET['wpacu_time'], $_GET['nocache']) && get_transient('wpacu_page_just_updated')) {
				$data['wpacu_page_just_updated'] = true;
				delete_transient('wpacu_page_just_updated');
			}

			if ($currentDebug = ObjectCache::wpacu_cache_get('wpacu_assets_unloaded_list_page_request')) {
				foreach ( array( 'styles', 'scripts' ) as $assetType ) {
					if ( ! empty( $data['current_unloaded_all'][ $assetType ] ) ) {
						foreach ( $data['current_unloaded_all'][ $assetType ] as $handleKey => $handle ) {
							if ( isset( $currentDebug[ $assetType ] ) && in_array( $handle, $currentDebug[ $assetType ] ) ) {
								unset( $data['current_unloaded_all'][ $assetType ][ $handleKey ] );
							}
						}
					}
				}
			}

			// e.g. /?wpacu_unload_(css|js)=
			$data['current_debug'] = ObjectCache::wpacu_cache_get('wpacu_assets_unloaded_list_page_request');

			$data['all']['scripts'] = $list['scripts'];
			$data['all']['styles']  = $list['styles'];

			if ($data['plugin_settings']['assets_list_layout'] === 'by-location') {
				$data['all'] = Sorting::appendLocation($data['all']);
			} else {
				$data['all'] = Sorting::sortListByAlpha($data['all']);
			}

			Main::instance()->fetchUrl = Misc::getPageUrl(Main::instance()->getCurrentPostId());

			$data['fetch_url']      = Main::instance()->fetchUrl;

			$data['nonce_action']   = Update::NONCE_ACTION_NAME;
			$data['nonce_name']     = Update::NONCE_FIELD_NAME;

			$data = Main::instance()->alterAssetObj($data);
			$data['global_unload']   = Main::instance()->globalUnloaded;

			$type = false;

			if (Misc::isHomePage() && Main::instance()->getCurrentPostId() < 1 && get_option('show_on_front') === 'posts') {
				$type = 'front_page';
			} elseif (Main::instance()->getCurrentPostId() > 0) {
				$type = 'post';
			}

			// [wpacu_pro]
			if (! $type) {
				// Main::instance()->getCurrentPostId() would be 0
				$type = 'for_pro';
			}
			// [/wpacu_pro]

			$data['wpacu_type'] = $type;

			$data['load_exceptions_per_page'] = Main::instance()->getLoadExceptionsPageLevel($type, Main::instance()->getCurrentPostId());

			// Avoid the /?wpacu_load_(css|js) to interfere with the form inputs
			if ($loadExceptionsDebug = ObjectCache::wpacu_cache_get( 'wpacu_exceptions_list_page_request' )) {
				foreach ( array( 'styles', 'scripts' ) as $assetType ) {
					if ( isset( $loadExceptionsDebug[ $assetType ] ) && ! empty( $data['load_exceptions_per_page'][ $assetType ] ) ) {
						foreach ( $data['load_exceptions_per_page'][ $assetType ] as $handleKey => $handle ) {
							if ( in_array( $handle, $loadExceptionsDebug[ $assetType ] ) ) {
								unset( $data['load_exceptions_per_page'][ $assetType ][ $handleKey ] );
							}
						}
					}
				}

				// e.g. /?wpacu_load_(css|js)=
				$data['load_exceptions_debug'] = $loadExceptionsDebug;
			}

			// WooCommerce Shop Page?
			$data['is_woo_shop_page'] = Main::$vars['is_woo_shop_page'];

			$data['is_bulk_unloadable'] = $data['bulk_unloaded_type'] = false;

			$data['bulk_unloaded']['post_type'] = array('styles' => array(), 'scripts' => array());

			$data['load_exceptions_post_type'] = array();

			if (Main::isSingularPage()) {
				$post = Main::instance()->getCurrentPost();

				$data['post_id'] = $post->ID;

				// Current Post Type
				$data['post_type'] = $post->post_type;

				$data['load_exceptions_post_type'] = Main::instance()->getLoadExceptionsPostType($data['post_type']);

				// Are there any assets unloaded for this specific post type?
				// (e.g. page, post, product (from WooCommerce) or other custom post type)
				$data['bulk_unloaded']['post_type'] = Main::instance()->getBulkUnload('post_type', $data['post_type']);

				$data['bulk_unloaded_type'] = 'post_type';

				$data['is_bulk_unloadable'] = true;

				$data['post_type_has_tax_assoc'] = Main::getAllSetTaxonomies($data['post_type']);

				$data = Main::instance()->setPageTemplate($data);
			}

			// [wpacu_pro]
			/*elseif (is_tax()) {
				$data['bulk_unloaded_type'] = 'taxonomy'; // category, tag, product category (WooCommerce), etc.
			}*/
			// [/wpacu_pro]

			$data['total_styles']  = ! empty($data['all']['styles'])  ? count($data['all']['styles'])  : false;
			$data['total_scripts'] = ! empty($data['all']['scripts']) ? count($data['all']['scripts']) : false;

			// [wpacu_pro]
			// is_archive() includes: Category, Tag, Author, Date, Custom Post Type or Custom Taxonomy based pages.
			// is_singular() includes: Post, Page, Custom Post Type
			$data['is_wp_recognizable'] = (is_archive() || is_singular() || is_404() || is_search() || is_front_page() || is_home());
			// [/wpacu_pro]

			$data['all_deps'] = Main::instance()->getAllDeps($data['all']);

			$data['preloads'] = Preloads::instance()->getPreloads();

			// Load exception: If the user is logged in (applies globally)
			$data['handle_load_logged_in'] = Main::instance()->getHandleLoadLoggedIn();

			$data['handle_notes'] = AssetsManager::getHandleNotes();
			$data['handle_rows_contracted'] = AssetsManager::getHandleRowStatus();

			$data['ignore_child'] = Main::instance()->getIgnoreChildren();

			// [wpacu_pro]
			// Any extra Pro rules to pass to the template?
			$data = apply_filters('wpacu_data_var_template', $data);
			// [/wpacu_pro]

			switch (assetCleanUpHasNoLoadMatches($data['fetch_url'])) {
				case 'is_set_in_settings':
					// The rules from "Settings" -> "Plugin Usage Preferences" -> "Do not load the plugin on certain pages" will be checked
					$data['status'] = 5;
					break;

				case 'is_set_in_page':
					// The following option from "Page Options" (within the CSS/JS manager of the targeted page) is set: "Do not load Asset CleanUp Pro on this page (this will disable any functionality of the plugin)"
					$data['status'] = 6;
					break;

				default:
					$data['status'] = 1;
			}

			$data['page_options'] = array();
			$data['show_page_options'] = false;

			if (in_array($type, array('post', 'front_page'))) {
				$data['show_page_options'] = true;
				$data['page_options'] = MetaBoxes::getPageOptions(Main::instance()->getCurrentPostId(), $type);
			}

			$data['post_id'] = ($type === 'front_page') ? 0 : Main::instance()->getCurrentPostId();
			ObjectCache::wpacu_cache_set('wpacu_settings_frontend_data', $data);
			Main::instance()->parseTemplate('settings-frontend', $data, true);
		} elseif ($isDashboardEditView && ! isset($_GET['wpacu_just_hardcoded'])) {
			// AJAX call (not the classic WP one) from the WP Dashboard
			// Send the altered value that has the initial position too

			// Taken front the front-end view
			$data = array();
			$data['all']['scripts'] = $list['scripts'];
			$data['all']['styles'] = $list['styles'];

			$data = Main::instance()->alterAssetObj($data);

			$list['styles']  = $data['all']['styles'];
			$list['scripts'] = $data['all']['scripts'];

			// [wpacu_pro]
            $list = apply_filters('wpacu_filter_list_on_dashboard_ajax_call', $list);
            // [/wpacu_pro]

            // e.g. for "Loaded" and "Unloaded" statuses
			$list['current_unloaded_all'] = isset(Main::instance()->allUnloadedAssets)
                ? Main::instance()->allUnloadedAssets
                : array('styles' => array(), 'scripts' => array());

			if ( isset($_GET['wpacu_print']) ) {
                echo '<!-- Enqueued List: '."\n".print_r($list, true)."\n".' -->';
                echo '<!-- Hardcoded List: '."\n".'{wpacu_hardcoded_assets_printed}'."\n".' -->';
            }

			echo Main::START_DEL_ENQUEUED  . base64_encode(wp_json_encode($list)) . Main::END_DEL_ENQUEUED; // Loaded via wp_enqueue_scripts()
			echo Main::START_DEL_HARDCODED . '{wpacu_hardcoded_assets}' . Main::END_DEL_HARDCODED; // Make the user aware of any hardcoded CSS/JS (if any)

			add_action('shutdown', static function() {
				// Do not allow further processes as cache plugins such as W3 Total Cache could alter the source code,
				// and we need the non-minified version of the DOM (e.g. to determine the position of the elements)
				exit();
			});
		} elseif ($isDashboardEditView && isset($_GET['wpacu_just_hardcoded'])) {
            if ( isset($_GET['wpacu_print']) ) {
                echo '<!-- Hardcoded list: '."\n".'{wpacu_hardcoded_assets_printed}'."\n".' -->';
            }

			// AJAX call just for the hardcoded assets
			echo Main::START_DEL_HARDCODED . '{wpacu_hardcoded_assets}' . Main::END_DEL_HARDCODED; // Make the user aware of any hardcoded CSS/JS (if any)

			add_action('shutdown', static function() {
				// Do not allow further processes as cache plugins such as W3 Total Cache could alter the source code,
				// and we need the non-minified version of the DOM (e.g. to determine the position of the elements)
				exit();
			});
		}

		/* [wpacu_timing] */ Misc::scriptExecTimer($wpacuTimingName, 'end'); /* [/wpacu_timing] */
	}


	/**
	 * @param $getForAssetsType ("styles", "scripts")
	 *
	 * @return array|array[]
	 */
	public function getSkipAssets($getForAssetsType)
	{
		if ( ! empty($this->skipAssets[$getForAssetsType]) ) {
			return $this->skipAssets[$getForAssetsType];
		}

		$ownScriptsIfAdminIsLoggedIn = current_user_can( 'administrator' ) && AssetsManager::instance()->frontendShow()
			? OwnAssets::getOwnAssetsHandles( $getForAssetsType )
			: array();

		if ($getForAssetsType === 'styles') {
			$this->skipAssets[$getForAssetsType] = array_merge(
				array(
					'admin-bar',
					// The top admin bar
					'yoast-seo-adminbar',
					// Yoast "WordPress SEO" plugin
					'autoptimize-toolbar',
					'query-monitor',
					'wp-fastest-cache-toolbar',
					// WP Fastest Cache plugin toolbar CSS
					'litespeed-cache',
					// LiteSpeed toolbar
					'siteground-optimizer-combined-styles-header'
					// Combine CSS in SG Optimiser (irrelevant as it made from the combined handles)
				),
				// Own Scripts (for admin use only)
				$ownScriptsIfAdminIsLoggedIn
			);
		}

		if ($getForAssetsType === 'scripts') {
			$this->skipAssets[$getForAssetsType] = array_merge(
				array(
					'admin-bar',            // The top admin bar
					'autoptimize-toolbar',
					'query-monitor',
					'wpfc-toolbar'          // WP Fastest Cache plugin toolbar JS
				),
				// Own Scripts (for admin use only)
				$ownScriptsIfAdminIsLoggedIn
			);
		}

		return $this->skipAssets[$getForAssetsType];
	}
}
