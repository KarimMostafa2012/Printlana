<?php
namespace WpAssetCleanUpPro;

use WpAssetCleanUp\Debug;
use WpAssetCleanUp\HardcodedAssets;
use WpAssetCleanUp\Main;
use WpAssetCleanUp\Menu;
use WpAssetCleanUp\Misc;
use WpAssetCleanUp\ObjectCache;
use WpAssetCleanUp\OptimiseAssets\OptimizeCommon;
use WpAssetCleanUp\Plugin;
use WpAssetCleanUp\Preloads;

/**
 * Class MainPro
 * @package WpAssetCleanUpPro
 */
class MainPro
{
	/**
     * These are special unloads that once applied, it could take effect on more than one page
     *
	 * @var array[]
	 */
	public static $unloads = array(
        'regex' => array(
            // Values saved
            'styles'              => array(),
            'scripts'             => array(),
            '_set'                => false,

            // Any matches for the current URL? It will contain the list of handles
            'current_url_matches' => array( 'styles' => array(), 'scripts' => array() )
        ),

        'post_type_via_tax' => array(
	        // Values saved
	        'styles'               => array(),
	        'scripts'              => array(),
            '_set'                 => false,

	        // Any matches for the current post? It will contain the list of handles
	        'current_post_matches' => array( 'styles' => array(), 'scripts' => array() )
        )
	);

	/**
     * These are special load exceptions that once applied, it could take effect
     * on more than one page by cancelling any unload rule set for that handle
     *
	 * @var array[]
	 */
	public static $loadExceptions = array(
        'regex' => array(
	        // Values saved
	        'styles'              => array(),
	        'scripts'             => array(),
            '_set'                => false,

	        // Any matches for the current URL? It will contain the list of handles
	        'current_url_matches' => array( 'styles' => array(), 'scripts' => array() )
        ),

        'post_type_via_tax' => array(
	        // Values saved
	        'styles'               => array(),
	        'scripts'              => array(),
            // '_set' is not relevant here as the function is called several times and returns different results
            //'_set'                 => false,

	        // Any matches for the current post? It will contain the list of handles
	        'current_post_matches' => array( 'styles' => array(), 'scripts' => array() )
        )
    );

	/**
	 * @var bool
	 */
	public $isTaxonomyEditPage = false;

	/**
	 * @var array
	 */
	public $asyncScripts = array();

	/**
	 * @var array
	 */
	public $deferScripts = array();

	/**
	 * @var array
	 */
	public $globalScriptsAttributes = array();

	/**
	 * @var bool
	 */
	public $scriptsAttributesChecked = false;

	/**
	 * @var array
	 */
	public $scriptsAttrsThisPage = array('async' => array(), 'defer' => array());

	/**
     * "not here (exception)" option
	 * @var array
	 */
	public $scriptsAttrsNoLoad = array('async' => array(), 'defer' => array());

	/**
	 * @var array
	 */
	public $settings = array();

	/**
	 *
	 */
	public function init()
	{
		$this->fallbacks();

		$assetsPositions = self::getAssetsPositions();
        if ( ! empty($assetsPositions['styles']) || ! empty($assetsPositions['scripts']) ) {
            if ( ! Main::instance()->isGetAssetsCall && ! is_admin() ) {
                add_action('init', static function () {
                    PositionsPro::setSignatures();
                }, 20);
            }

            $positionsClass = new PositionsPro();
            $positionsClass->init();
        }

		// "Per Page" Unloaded Assets
		add_filter('wpacu_pro_get_assets_unloaded', array($this, 'getAssetsUnloadedPageLevel'));
		add_filter('wpacu_pro_get_bulk_unloads',    array($this, 'getBulkUnloads'));

		// This filter appends to the existing "all unloaded" list, assets belonging to the is_tax(), is_author() etc. group
		// This way, they will PRINT to the list of unloaded assets for management
		// "async", "defer" attribute changes to show up in the management list
		add_filter('wpacu_pro_get_scripts_attributes_for_each_asset', array($this, 'getScriptsAttributesToPrintInList'));

		add_filter('wpacu_filter_styles_list_unload',          array($this, 'filterAssets'));
		add_filter('wpacu_filter_styles_list_load_exception',  array($this, 'filterAssets'));

		add_filter('wpacu_filter_scripts_list_unload',         array($this, 'filterAssets'));
		add_filter('wpacu_filter_scripts_list_load_exception', array($this, 'filterAssets'));

		add_filter('wpacu_object_data',                        array($this, 'wpacuObjectData'));

		add_filter('wpacu_data_var_template',                  array($this, 'filterDataVarTemplate'));
		add_filter('wpacu_filter_list_on_dashboard_ajax_call', array($this, 'filterListOnDashboardAjaxCall'));

		add_action('current_screen',                           array($this, 'currentScreen'));

		if (defined('WPACU_ALLOW_ONLY_UNLOAD_RULES') && WPACU_ALLOW_ONLY_UNLOAD_RULES) {
		    return; // stop here, do not do any alteration to the LINK/SCRIPT tags as only the unloading rules are allowed
        }

		// Only valid for front-end pages
		if (! is_admin()) {
			add_filter('style_loader_tag', array($this, 'styleLoaderTag'), 10, 2);

			// Add async, defer (if selected) for the loaded scripts
			add_filter('script_loader_tag', array($this, 'scriptLoaderTag'), 10, 2);
		}

		// Load via an AJAX call the list of all the taxonomies set for a post type
        // They will show only if at least one value is set (e.g. a tag, category) for a post
        // This is to save resources and have a smaller drop-down
        // The admin needs to set the tag/category/any taxonomy first, then use the drop-down
		add_action('wp_ajax_' . WPACU_PLUGIN_ID . '_load_all_set_terms_for_post_type', array($this, 'ajaxLoadAllSetTermsForPostType'), 10, 2);
	}

	/**
	 * @param $assetsRemoved
	 *
	 * @return mixed|string
	 */
	public function getAssetsUnloadedPageLevel($assetsRemoved)
	{
	    $bulkType = false;

	    /*
		 * NOTE: This list only includes assets that are unloaded on a page level
		 * A 404 page will have the same unloaded assets, as it returns a 404 response (no matter which URL is requested)
		*/

		/*
		 * [START] DASHBOARD VIEW ONLY
		 */
            if (isset($_REQUEST['tag_id']) && is_admin() && Main::instance()->settings['dashboard_show']) {
                // The "tag_id" value is sent to the AJAX call (it's not the same as 'tag_ID' from the URL of the page)
                $termId = (int)$_REQUEST['tag_id'];
                return get_term_meta($termId, '_' . WPACU_PLUGIN_ID . '_no_load', true);
            }
		/*
		 * [END] DASHBOARD VIEW ONLY
		 */

		/*
		 * [START] FRONT-END VIEW ONLY
		 */
		/*
		  *
		  * Possible pages:
		  *
		  * 404 Page: Not Found (applies to any non-existent request)
		  * Default WordPress Search Page: Applies to any search request
		  * Date Archive Page: Applies to any date
		 *
		*/
		if ( is_404() || Main::isWpDefaultSearchPage() || is_date() || self::isCustomPostTypeArchivePage() ) {
			$bulkUnloadJson = get_option( WPACU_PLUGIN_ID . '_bulk_unload' );

			@json_decode( $bulkUnloadJson );

			if ( empty( $bulkUnloadJson ) || ! ( Misc::jsonLastError() === JSON_ERROR_NONE ) ) {
				return $assetsRemoved;
			}

			$bulkUnload = json_decode( $bulkUnloadJson, true );

			if (is_404()) {
				$bulkType = '404';     // 404 (Not Found) WordPress page (located in 404.php)
			} elseif (Main::isWpDefaultSearchPage()) {
				$bulkType = 'search';  // Default WordPress Search Page
			} elseif (is_date()) {
				$bulkType = 'date';    // Show posts by date page
			} elseif ($customPostTypeObj = self::isCustomPostTypeArchivePage()) {
			    $bulkType = 'custom_post_type_archive_' . $customPostTypeObj->name;
            }

			if (! $bulkType) {
				// Shouldn't reach this; it's added just in case there's any conditional missing above
				return $assetsRemoved;
			}

			return wp_json_encode( array(
				'styles'  => isset($bulkUnload['styles'][$bulkType])  ? $bulkUnload['styles'][$bulkType]  : array(),
				'scripts' => isset($bulkUnload['scripts'][$bulkType]) ? $bulkUnload['scripts'][$bulkType] : array()
			) );
		}

		// Taxonomy and Author pages check (Front-end View)
		$isTaxonomyView = is_category() || is_tag() || is_tax(); // Category, Tag & Any Custom Taxonomy

		if ( $isTaxonomyView || is_author() ) {
			global $wp_query;
			$object = $wp_query->get_queried_object();

            /*
             * Taxonomy page: Could be 'category' (Default WordPress taxonomy), 'product_cat', 'post_tag' (for the tag page) etc.
            */
			if ( isset( $object->taxonomy, $object->term_id ) || $isTaxonomyView ) {
				return get_term_meta($object->term_id, '_' . WPACU_PLUGIN_ID . '_no_load', true);
			}

            /*
             * Author page (individual, not for all authors)
             */
			if ( is_author() ) {
				$authorId = self::getAuthorIdOnAuthorArchivePage(__FILE__, __LINE__);

                if ($authorId !== null) {
	                return get_user_meta( $authorId, '_' . WPACU_PLUGIN_ID . '_no_load', true );
                }
			}
        }
		/*
		 * [END] FRONT-END VIEW ONLY
		 */

		return $assetsRemoved;
	}

	/**
	 * @param $fromFile
	 * @param $fromLine
	 *
	 * @return string|null
	 */
	public static function getAuthorIdOnAuthorArchivePage($fromFile, $fromLine)
    {
	    $authorId = null;

	    if ( is_author() ) {
		    global $wp_query;
		    $object = $wp_query->get_queried_object();

		    if (isset($object->data->ID) && $object->data->ID) {
			    $authorId = $object->data->ID;
		    } elseif (function_exists('get_the_author_meta')) {
			    $authorId = get_the_author_meta('ID');
		    }

		    if ($authorId === null) {
			    error_log(WPACU_PLUGIN_TITLE . ': Error detecting the author ID when visiting an author archive page (you can raise a ticket about this to the support team) / File: '.$fromFile.' / Line: '.$fromLine);
		    }
        }

        return $authorId;
    }

	/**
     * Get bulk unloads for taxonomy and author pages
     *
	 * @param array $data (possible values: "post_type_via_tax" or "tax_and_author")
	 *
	 * @return array
	 */
	public function getBulkUnloads($data = array())
	{
		if ( ! isset($data['fetch']) ) {
            $data['fetch'] = 'tax_and_author'; // default
        }

	    if ( $data['fetch'] === 'tax_and_author' ) {
		    global $wp_query;

		    $object = $wp_query->get_queried_object();

		    if ( isset( $object->taxonomy ) && ( ! is_admin() ) ) {
			    // Front-end View
			    $data['is_bulk_unloadable']        = true;
			    $data['bulk_unloaded']['taxonomy'] = Main::instance()->getBulkUnload( 'taxonomy', $object->taxonomy );
			    $data['bulk_unloaded_type']        = 'taxonomy';
		    } elseif ( isset( $_REQUEST['wpacu_taxonomy'] ) && Main::instance()->settings['dashboard_show'] && is_admin() ) {
			    // Dashboard View
			    $data['is_bulk_unloadable']        = true;
			    $data['bulk_unloaded']['taxonomy'] = Main::instance()->getBulkUnload( 'taxonomy', $_REQUEST['wpacu_taxonomy'] );
			    $data['bulk_unloaded_type']        = 'taxonomy';
		    } elseif ( is_author() ) {
			    // Only in front-end view
			    $data['is_bulk_unloadable']      = true;
			    $data['bulk_unloaded']['author'] = Main::instance()->getBulkUnload( 'author' );
			    $data['bulk_unloaded_type']      = 'author';
		    }
	    } elseif ( $data['fetch'] === 'post_type_via_tax' ) {
		    $data['is_bulk_unloadable']                 = true;
		    $data['bulk_unloaded']['post_type_via_tax'] = Main::instance()->getBulkUnload( 'post_type_via_tax', $data['post_type'] );
		    $data['bulk_unloaded_type']                 = 'post_type_via_tax';
        }

		return $data;
	}

	/**
	 * Case 1: UNLOAD style/script (based on the handle) for URLs matching a specified RegExp
	 * Case 2: LOAD (make an exception) style/script (based on the handle) for URLs matching a specified RegExp
	 *
	 * @param $for ("unloads" or "load_exceptions")
	 *
	 * @return array
	 */
	public static function getRegExRules($for)
	{
        if ($for === 'unloads' && self::$unloads['regex']['_set']) {
            return self::$unloads['regex'];
        }

        if ($for === 'load_exceptions' && self::$loadExceptions['regex']['_set']) {
			return self::$loadExceptions['regex'];
		}

		$regExes = array('styles' => array(), 'scripts' => array());

		$regExDbListJson = get_option(WPACU_PLUGIN_ID . '_global_data');

		// DB Key (how it's saved in the database)
		if ($for === 'load_exceptions') {
			$globalKey = 'load_regex';
		} else {
			$globalKey = 'unload_regex';
		}

		if ($regExDbListJson) {
			$regExDbList = @json_decode($regExDbListJson, true);

			// Issues with decoding the JSON file? Return an empty list
			if (Misc::jsonLastError() !== JSON_ERROR_NONE) {
                if ($for === 'unloads') {
	                self::$unloads['regex']         = $regExes;
	                self::$unloads['regex']['_set'] = true;
	                $toReturn = self::$unloads['regex'];
                } else {
	                self::$loadExceptions['regex']         = $regExes;
	                self::$loadExceptions['regex']['_set'] = true;
	                $toReturn = self::$loadExceptions['regex'];
                }

                return $toReturn;
			}

			// Are there any load exceptions / unload RegExes?
			foreach (array('styles', 'scripts') as $assetKey) {
				if ( ! empty( $regExDbList[$assetKey][$globalKey] ) ) {
					$regExes[$assetKey] = $regExDbList[$assetKey][$globalKey];
				}
			}
		}

		if ($for === 'unloads') {
			self::$unloads['regex']         = $regExes;
			self::$unloads['regex']['_set'] = true;
			$toReturn = self::$unloads['regex'];
		} else {
			self::$loadExceptions['regex']         = $regExes;
			self::$loadExceptions['regex']['_set'] = true;
			$toReturn = self::$loadExceptions['regex'];
		}

        return $toReturn;
	}

	/**
	 * @param $varName
	 * @param $data
	 *
	 * @return array|array[]
	 */
	public function filterThisVar($varName, $data)
	{
		if ($varName === 'unloadsRegEx') {
			// For the management of the assets in the Dashboard
			self::$unloads['regex'] = self::getRegExRules('unloads');

			// Any RegEx unload matches?
			if ( ! empty( self::$unloads['regex'] ) ) {
				foreach ( self::$unloads['regex'] as $assetType => $wpacuUlValues ) {
                    if ($assetType === '_set') {
                        continue; // irrelevant here
                    }

					if ( ! empty( $wpacuUlValues ) ) {
						foreach ( $wpacuUlValues as $wpacuHandle => $wpacuUlValue ) {
							if ( isset( $wpacuUlValue['enable'], $wpacuUlValue['value'] ) && $wpacuUlValue['enable'] &&
                                 self::isRegExMatch( $wpacuUlValue['value'], $data['fetch_url']) ) {
								self::$unloads['regex']['current_url_matches'][$assetType][] = $wpacuHandle;
							}
						}
					}
				}
			}

			return self::$unloads['regex'];
		}

		if ($varName === 'loadExceptionsRegEx') {
			self::$loadExceptions['regex'] = self::getRegExRules('load_exceptions');

			// Any load exceptions matches?
			if (! empty(self::$loadExceptions['regex'])) {
				foreach (self::$loadExceptions['regex'] as $assetType => $wpacuLeValues) {
					if ($assetType === '_set') {
						continue; // irrelevant here
					}

					if (! empty($wpacuLeValues)) {
						foreach ($wpacuLeValues as $wpacuHandle => $wpacuLeData) {
							// Needs to be marked as enabled with a value
							if ( isset( $wpacuLeData['enable'], $wpacuLeData['value'] ) && $wpacuLeData['enable']
                                 && self::isRegExMatch( $wpacuLeData['value'], $data['fetch_url'] ) ) {
								self::$loadExceptions['regex']['current_url_matches'][$assetType][] = $wpacuHandle;
							}
						}
					}
				}
			}

			return self::$loadExceptions['regex'];
		}

		if ($varName === 'unloadsPostTypeViaTax' && isset($data['post_type'], $data['post_id']) && $data['post_type'] && $data['post_id']) {
			self::$unloads['post_type_via_tax'] = self::getTaxonomyValuesAssocToPostType($data['post_type']);

			$currentPostTerms = self::getTaxonomyTermIdsAssocToPost( $data['post_id'] );

			if ( ! empty($currentPostTerms) ) {
				foreach ( self::$unloads['post_type_via_tax'] as $assetType => $wpacuUValues ) {
					if ($assetType === '_set') {
						continue; // irrelevant here
					}

					foreach ($wpacuUValues as $assetHandle => $assetData) {
						if ( isset( $assetData['enable'] ) && $assetData['enable'] && ! empty( $assetData['values'] ) ) {
							// Go through the terms set and check if the current post ID is having the taxonomy value associated with it
							foreach ( $assetData['values'] as $termId ) {
								if ( in_array( $termId, $currentPostTerms ) ) {
									self::$unloads['post_type_via_tax']['current_post_matches'][ $assetType ][] = $assetHandle;
									break;
								}
							}
						}
					}
				}
			}

			return self::$unloads['post_type_via_tax'];
        }

		if ($varName === 'loadExceptionsPostTypeViaTax' && isset($data['post_id'], $data['post_type']) && $data['post_id'] && $data['post_type']) {
			self::$loadExceptions['post_type_via_tax'] = self::getTaxonomyValuesAssocToPostTypeLoadExceptions($data['post_type']);

			$currentPostTerms = self::getTaxonomyTermIdsAssocToPost( $data['post_id'] );

            if ( ! empty($currentPostTerms) ) {
	            foreach ( self::$loadExceptions['post_type_via_tax'] as $assetType => $wpacuLeValues ) {
		            if ($assetType === '_set') {
			            continue; // irrelevant here
		            }

		            foreach ($wpacuLeValues as $assetHandle => $assetData) {
			            if ( isset( $assetData['enable'] ) && $assetData['enable'] && ! empty( $assetData['values'] ) ) {
				            // Go through the terms set and check if the current post ID is having the taxonomy value associated with it
				            foreach ( $assetData['values'] as $termId ) {
					            if ( in_array( $termId, $currentPostTerms ) ) {
						            self::$loadExceptions['post_type_via_tax']['current_post_matches'][ $assetType ][] = $assetHandle;
						            break;
					            }
				            }
			            }
		            }
	            }
            }

		    return self::$loadExceptions['post_type_via_tax'];
        }

		return array();
	}

	/**
	 * @param $list
	 *
	 * @return array
	 */
	public function filterAssets($list)
	{
        $keyToCheck = 'pro_'.current_filter();

        if (isset($GLOBALS[$keyToCheck])) {
            return $GLOBALS[$keyToCheck];
        }

	    // [unload list]
	    if (current_filter() === 'wpacu_filter_styles_list_unload') {
		    $list = $this->filterAssetsUnloadList($list, 'styles', 'unload');
        }
		elseif (current_filter() === 'wpacu_filter_scripts_list_unload') {
			$list = $this->filterAssetsUnloadList($list, 'scripts', 'unload');
		}
		// [/unload list]

		// [load exception list]
        elseif (current_filter() === 'wpacu_filter_styles_list_load_exception') {
	        $list = $this->filterAssetsUnloadList($list, 'styles', 'load_exception');
		}
		elseif (current_filter() === 'wpacu_filter_scripts_list_load_exception') {
			$list = $this->filterAssetsUnloadList($list, 'scripts', 'load_exception');
		}
		// [/load exception list]

		$GLOBALS[$keyToCheck] = $list;
		return $list;
	}

	/**
	 * @return false|object
	 */
	public static function isCustomPostTypeArchivePage()
	{
		// There are exceptions here, when the archive page is connected to a page ID such as the WooCommerce Shop page
		if (Main::$vars['is_woo_shop_page']) {
			return false;
		}

		$wpacuQueriedObj = get_queried_object();

		$wpacuIsCustomPostTypeArchivePage = is_archive()
            && isset($wpacuQueriedObj->label, $wpacuQueriedObj->query_var, $wpacuQueriedObj->capability_type, $wpacuQueriedObj->name)
            && $wpacuQueriedObj->name && $wpacuQueriedObj->query_var
            && ( in_array($wpacuQueriedObj->capability_type, array('post', 'product'))
                 || (isset($wpacuQueriedObj->_edit_link) && $wpacuQueriedObj->_edit_link === 'post.php?post=%d') );

		if ($wpacuIsCustomPostTypeArchivePage) {
			return $wpacuQueriedObj;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function isTaxonomyEditPage()
	{
		if (! $this->isTaxonomyEditPage) {
			$current_screen = \get_current_screen();

			if ( $current_screen->taxonomy !== null
			     && $current_screen->taxonomy
			     && ( strpos( $current_screen->id, 'edit' ) !== false ) ) {
				$this->isTaxonomyEditPage = true;
			}
		}

		return $this->isTaxonomyEditPage;
	}

	/**
	 * @param $pattern
	 * @param $subject
	 *
	 * @return bool
	 */
	public static function isRegExMatch($pattern, $subject)
	{
		$regExMatches = false;

		$pattern = trim($pattern);
		$subject = trim($subject);

		if (! $pattern || ! $subject) {
		    return false;
        }

		if (PHP_VERSION_ID >= 70100) {
		    $hasTregxPhpSevenOnePlus = class_exists( '\TRegx\CleanRegex\PcrePattern' )
               && method_exists( '\TRegx\CleanRegex\Pattern', 'delimited' );

			if ( $hasTregxPhpSevenOnePlus ) {
				try {
					// One line (there aren't several lines in the textarea)
					if ( strpos( $pattern, "\n" ) === false ) {
						$cleanRegexPattern = \TRegx\CleanRegex\PcrePattern::of($pattern)->delimited();

						if ( \TRegx\CleanRegex\PcrePattern::of($cleanRegexPattern)->test($subject) ) {
							$regExMatches = true;
						} elseif ( @preg_match( $pattern, $subject ) ) { // fallback
							$regExMatches = true;
						}
					} else {
						// Multiple lines
						foreach ( explode( "\n", $pattern ) as $patternRow ) {
							$patternRow = trim( $patternRow );
							$cleanRegexPattern = \TRegx\CleanRegex\PcrePattern::of($patternRow)->delimited();

							if ( \TRegx\CleanRegex\PcrePattern::of($cleanRegexPattern)->test($subject) ) {
								$regExMatches = true;
								break;
							}

							if ( @preg_match( $patternRow, $subject ) ) { // fallback
								$regExMatches = true;
								break;
							}
						}
					}
				} catch ( \Exception $e ) {}
			}
        } else {
			$hasTregxPhpFiveSixPlus = class_exists( '\CleanRegex\Pattern' )
              && class_exists( '\SafeRegex\preg' )
              && method_exists( '\CleanRegex\Pattern', 'delimitered' )
              && method_exists( '\SafeRegex\preg', 'match' );

			if ( $hasTregxPhpFiveSixPlus ) {
                try {
                    // One line (there aren't several lines in the textarea)
                    if ( strpos( $pattern, "\n" ) === false ) {
                        $cleanRegexPattern = new \CleanRegex\Pattern( $pattern );
                        if ( \SafeRegex\preg::match( $cleanRegexPattern->delimitered(), $subject ) ) {
                            $regExMatches = true;
                        } elseif ( @preg_match( $pattern, $subject ) ) { // fallback
                            $regExMatches = true;
                        }
                    } else {
                        // Multiple lines
                        foreach ( explode( "\n", $pattern ) as $patternRow ) {
                            $patternRow = trim( $patternRow );

                            $cleanRegexPattern = new \CleanRegex\Pattern( $patternRow );
                            if ( \SafeRegex\preg::match( $cleanRegexPattern->delimitered(), $subject ) ) {
                                $regExMatches = true;
                                break;
                            }

                            if ( @preg_match( $patternRow, $subject ) ) { // fallback
                                $regExMatches = true;
                                break;
                            }
                        }
                    }
                } catch ( \Exception $e ) {}
			}
		}

		return $regExMatches;
	}

	/**
	 *
	 */
	public function currentScreen()
    {
        // Do not show it if 'Hide "Asset CleanUp Pro: CSS & JavaScript Manager" meta box' is checked in 'Settings' -> 'Plugin Usage Preferences'
        // Or if the user has no right to view this (e.g. an editor that does not have admin rights, thus no business with any of the plugin's settings)
        if ( ! Main::instance()->settings['show_assets_meta_box'] || ! Menu::userCanManageAssets() ) {
            return;
        }

	    $current_screen = \get_current_screen();

	    if ($current_screen->base === 'term' && isset($current_screen->taxonomy) && $current_screen->taxonomy !== '') {
		    add_action('admin_head', static function() {
		        // Make the CSS/JS List larger
		        ?>
                <style data-wpacu-admin-inline-css="1" <?php echo Misc::getStyleTypeAttribute(); ?>>
                    #edittag {
                        max-width: 96%;
                    }
                    tr.form-field[class*="term-"] > th {
                        width: 200px;
                    }
                    tr.form-field[class*="term-"] > td > * {
                        max-width: 550px;
                    }
                </style>
                <?php
            }, PHP_INT_MAX);

		    add_action ($current_screen->taxonomy . '_edit_form_fields', static function ($tag) {
		        if (! Main::instance()->settings['dashboard_show']) {
                    ?>
                    <tr class="form-field">
                        <th scope="row" valign="top"><label for="wpassetcleanup_list"><?php echo WPACU_PLUGIN_TITLE; ?>: CSS &amp; JavaScript Manager</label></th>
                        <td><?php echo sprintf(__('"Manage in the Dashboard?" is not enabled in the plugin\'s "%sSettings%s", thus, the list is not available.', 'wp-asset-clean-up'), '<a href="'.esc_url(admin_url('admin.php?page=wpassetcleanup_settings')).'">', '</a>'); ?></td>
                    </tr>
                    <?php
                    return;
                }
			    $domGetType = Main::instance()->settings['dom_get_type'];
                $fetchAssetsOnClick = Main::instance()->settings['assets_list_show_status'] === 'fetch_on_click';
			    ?>
                <tr class="form-field">
                    <th scope="row" valign="top"><label for="wpassetcleanup_list"><?php echo WPACU_PLUGIN_TITLE; ?>: CSS &amp; JavaScript Manager</label></th>
                    <td data-wpacu-taxonomy="<?php echo esc_attr($tag->taxonomy); ?>">
                        <?php
                        $targetUrl = get_term_link($tag, $tag->taxonomy);

                        if (assetCleanUpHasNoLoadMatches($targetUrl)) {
	                        $parseUrl = parse_url($targetUrl);
	                        $rootUrl = $parseUrl['scheme'].'://'.$parseUrl['host'];
	                        $targetUri = str_replace( $rootUrl, '', $targetUrl );
                            ?>
                            <p class="wpacu_verified">
                                <strong>Target URL:</strong> <a target="_blank" href="<?php echo esc_url($targetUrl); ?>"><span><?php echo esc_url($targetUrl); ?></span></a>
                            </p>
	                        <?php
	                        $msg = sprintf(__('This taxonomy\'s URI <em>%s</em> is matched by one of the RegEx rules you have in <strong>"Settings"</strong> -&gt; <strong>"Plugin Usage Preferences"</strong> -&gt; <strong>"Do not load the plugin on certain pages"</strong>, thus %s is not loaded on that page and no CSS/JS are to be managed. If you wish to view the CSS/JS manager, please remove the matching RegEx rule and reload this page.', 'wp-asset-clean-up-pro'), $targetUri, WPACU_PLUGIN_TITLE);
	                        ?>
                            <p class="wpacu-warning" style="margin: 15px 0 0; padding: 10px; font-size: inherit; width: 99%;">
                                <span style="color: red;" class="dashicons dashicons-info"></span> <?php echo wp_kses($msg, array('em' => array(), 'strong' => array())); ?>
                            </p>
                            <?php
                        } else {
                        ?>
                            <input type="hidden"
                                   id="wpacu_ajax_fetch_assets_list_dashboard_view"
                                   name="wpacu_ajax_fetch_assets_list_dashboard_view"
                                   value="1" />
                            <?php
                            if ($fetchAssetsOnClick) {
                            ?>
                                <a style="margin: 10px 0; height: 34px; padding: 2px 16px 1px;" href="#" class="button button-secondary" id="wpacu_ajax_fetch_on_click_btn"><span style="font-size: 22px; vertical-align: middle;" class="dashicons dashicons-download"></span>&nbsp;Fetch CSS &amp; JavaScript Management List</a>
                                <?php
                            }
                            ?>
                            <div id="wpacu_fetching_assets_list_wrap" <?php if ($fetchAssetsOnClick) { echo 'style="display: none;"'; } ?>>
                                <div id="wpacu_meta_box_content">
                                    <?php
                                    if ($domGetType === 'direct') {
                                        $wpacuDefaultFetchListStepDefaultStatus   = '<img src="'.esc_url(admin_url('images/spinner.gif')).'" align="top" width="20" height="20" alt="" />&nbsp; Please wait...';
                                        $wpacuDefaultFetchListStepCompletedStatus = '<span style="color: green;" class="dashicons dashicons-yes-alt"></span> Completed';
                                        ?>
                                        <div id="wpacu-list-step-default-status" style="display: none;"><?php echo wp_kses($wpacuDefaultFetchListStepDefaultStatus, array('img' => array('src' => array(), 'align' => array(), 'width' => array(), 'height' => array(), 'alt' => array()))); ?></div>
                                        <div id="wpacu-list-step-completed-status" style="display: none;"><?php echo wp_kses($wpacuDefaultFetchListStepCompletedStatus, array('span' => array('style' => array(), 'class' => array()))); ?></div>
                                        <div>
                                            <ul class="wpacu_meta_box_content_fetch_steps">
                                                <li id="wpacu-fetch-list-step-1-wrap"><strong>Step 1</strong>: <?php echo sprintf(__('Fetch the assets from <strong>%s</strong>', 'wp-asset-clean-up'), $targetUrl); ?>... <span id="wpacu-fetch-list-step-1-status"><?php echo wp_kses($wpacuDefaultFetchListStepDefaultStatus, array('img' => array('src' => array(), 'align' => array(), 'width' => array(), 'height' => array(), 'alt' => array()))); ?></span></li>
                                                <li id="wpacu-fetch-list-step-2-wrap"><strong>Step 2</strong>: Build the list of the fetched assets and print it... <span id="wpacu-fetch-list-step-2-status"></span></li>
                                            </ul>
                                        </div>
                                    <?php } else { ?>
                                        <div style="margin: 18px 0;">
                                            <img src="<?php echo esc_url(admin_url('images/spinner.gif')); ?>" align="top" width="20" height="20" alt="" />&nbsp;
                                            <?php echo sprintf(__('Fetching the loaded scripts and styles for <strong>%s</strong>... Please wait...', 'wp-asset-clean-up'), $targetUrl); ?>
                                        </div>
                                    <?php } ?>

                                    <hr>
                                    <div style="margin-top: 20px;">
                                        <strong>Is the fetching taking too long? Please do the following:</strong>
                                        <ul style="margin-top: 8px; margin-left: 20px; padding: 0; list-style: disc;">
                                            <li>Check your internet connection and the actual page that is being fetched to see if it loads completely.</li>
                                            <li>If the targeted page loads fine and your internet connection is working fine, please try managing the assets in the front-end view by going to <em>"Settings" -&gt; "Plugin Usage Preferences" -&gt; "Manage in the Front-end"</em></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php
                        }
                        ?>
                    </td>
                </tr>
			    <?php
		    });
	    }
    }

	/**
	 * @param $wpacuObjectData
	 *
	 * @return mixed
	 */
	public function wpacuObjectData($wpacuObjectData)
    {
	    if (is_admin() && $this->isTaxonomyEditPage() && Misc::getVar('get', 'tag_ID') && Misc::getVar('get', 'taxonomy')) {
		    $wpacuObjectData['tag_id']         = (int)Misc::getVar('get', 'tag_ID');
		    $wpacuObjectData['wpacu_taxonomy'] = Misc::getVar('get', 'taxonomy');
	    }

	    if ( isset($wpacuObjectData['page_url']) && is_admin() && Misc::isHttpsSecure()) {
	        $wpacuObjectData['page_url'] = str_replace('http://', 'https://', $wpacuObjectData['page_url']);
	    }

	    $currentPostId = 0;

	    if (is_admin()) {
		    /*
		     * Dashboard management
		     */
		    // Location one: /wp-admin/admin.php?page=wpassetcleanup_assets_manager&wpacu_for=posts&wpacu_post_id=[post_id_here]
		    if ( isset( $_GET['wpacu_post_id'] ) ) {
			    $currentPostId = (int) $_GET['wpacu_post_id'];
		    }

		    // Location two: /wp-admin/post.php?post=[post_id_here]&action=edit
		    if ( isset( $_GET['post'], $_GET['action'] ) && ( $_GET['action'] === 'edit' ) ) {
			    $currentPostId = (int) $_GET['post'];
		    }

		    // Location three: /wp-admin/admin.php?page=wpassetcleanup_assets_manager(&wpacu_for=homepage)
		    if ( defined( 'WPACU_FRONT_PAGE_DISPLAYS_STATIC_PAGE_ID' ) ) {
			    $currentPostId = (int) WPACU_FRONT_PAGE_DISPLAYS_STATIC_PAGE_ID;
		    }
	    } elseif (Main::isSingularPage()) {
	        /*
	         * Front-end management
	         */
	        $currentPostId = Main::instance()->getCurrentPostId();
	    }

	    if ($currentPostId > 0) {
            $wpacuObjectData['current_post_type']                    = get_post_type($currentPostId);
            $wpacuObjectData['wpacu_ajax_get_post_type_terms_nonce'] = wp_create_nonce('wpacu_ajax_get_post_type_terms_nonce');
	    }

	    return $wpacuObjectData;
    }

    /**
	 * @param array $data
	 *
	 * @return array
	 */
	public function getScriptAttributesToApplyOnCurrentPage($data = array())
    {
        if ($this->scriptsAttributesChecked || Plugin::preventAnyFrontendOptimization() || Main::instance()->preventAssetsSettings()) {
            return array('async' => $this->asyncScripts, 'defer' => $this->deferScripts);
        }

	    // Could be front-end view or Dashboard view
        // Various conditionals are set below as this method would be trigger on Front-end view (no AJAX call)
        // and from AJAX calls when a post / page / taxonomy or home page are managed within the Dashboard
	    if (isset($data['post_id'])) {
		    // AJAX Call (within the Dashboard)
		    $postId = $data['post_id'];
	    } else {
	        // Regular view (either front-end edit mode or visitor accessing the page)
            // Either page, the ID is fetched in the same way
	        $postId = Main::instance()->getCurrentPostId();
        }

        // Any global loaded attributes?
        $scriptGlobalAttributes = $this->getScriptGlobalAttributes();

	    $this->asyncScripts = $scriptGlobalAttributes['async'];
	    $this->deferScripts = $scriptGlobalAttributes['defer'];

	    $taxID = false;

	    global $wp_query;
	    $object = $wp_query->get_queried_object();

	    if (isset($object->taxonomy)) {
		    $taxID = $object->term_id;
	    } elseif (Main::instance()->settings['dashboard_show'] && is_admin() && isset($_REQUEST['tag_id'])) {
		    $taxID = $_REQUEST['tag_id'];
        }

        $isForSingularPage = (Main::instance()->settings['dashboard_show'] && $postId > 1) || Main::isSingularPage();
	    $isForFrontPage = (isset($data['wpacu_type']) && $data['wpacu_type'] === 'front_page') || Misc::isHomePage();

        if ($isForSingularPage) {
	        // Post, Page, Custom Post Type, Home page (static page selected as front page)
	        $list = get_post_meta($postId, '_' . WPACU_PLUGIN_ID . '_data', true);
        } elseif ($isForFrontPage) {
            // Home page (latest posts)
	        $list = get_option( WPACU_PLUGIN_ID . '_front_page_data');
        } elseif (is_404() || Main::isWpDefaultSearchPage() || is_date() || self::isCustomPostTypeArchivePage()) {
            // 404 Not Found, Search Results, Date archive page, Custom Post Type archive page
	        $list = get_option( WPACU_PLUGIN_ID . '_global_data');
        } elseif ($taxID) {
            // Taxonomy page (e.g. category, tag pages)
            $list = get_term_meta($taxID, '_' . WPACU_PLUGIN_ID . '_data', true);
        } elseif (is_author()) {
	        $authorId = self::getAuthorIdOnAuthorArchivePage(__FILE__, __LINE__);

	        if ($authorId !== null) {
		        // Author pages (e.g /author/author-name-here/)
		        $list = get_user_meta( $authorId, '_' . WPACU_PLUGIN_ID . '_data', true );
	        }
        }

        if (! (isset($list) && $list)) {
	        return array('async' => $this->asyncScripts, 'defer' => $this->deferScripts);
        }

        $targetKeyNoLoads = 'scripts_attributes_no_load';

	    $list = json_decode($list, ARRAY_A);

	    if (Misc::jsonLastError() === JSON_ERROR_NONE) {
	        if ($isForSingularPage || $isForFrontPage || $taxID || is_author()) {
		        $targetLocation        = isset($list['scripts']) ? $list['scripts'] : array();
		        $targetLocationNoLoads = isset($list[$targetKeyNoLoads]) ? $list[$targetKeyNoLoads] : array();
            } elseif (is_404()) {
	            $targetLocation        = isset($list['scripts']['404']) ? $list['scripts']['404'] : array();
		        $targetLocationNoLoads = isset($list[$targetKeyNoLoads]['404']) ? $list[$targetKeyNoLoads]['404'] : array();
	        } elseif (Main::isWpDefaultSearchPage()) {
	            $targetLocation        = isset($list['scripts']['search']) ? $list['scripts']['search'] : array();
		        $targetLocationNoLoads = isset($list[$targetKeyNoLoads]['search']) ? $list[$targetKeyNoLoads]['search'] : array();
	        } elseif (is_date()) {
		        $targetLocation        = isset($list['scripts']['date']) ? $list['scripts']['date'] : array();
		        $targetLocationNoLoads = isset($list[$targetKeyNoLoads]['date']) ? $list[$targetKeyNoLoads]['date'] : array();
	        } elseif ($customPostTypeObj = self::isCustomPostTypeArchivePage()) {
	            $targetKey             = 'custom_post_type_archive_' . $customPostTypeObj->name;
                $targetLocation        = isset($list['scripts'][$targetKey]) ? $list['scripts'][$targetKey] : array();
		        $targetLocationNoLoads = isset($list[$targetKeyNoLoads][$targetKey]) ? $list[$targetKeyNoLoads][$targetKey] : array();
	        }

	        if ( ! empty($targetLocation) ) {
			    foreach ( $targetLocation as $asset => $values ) {
				    if ( ! empty( $values['attributes'] ) ) {
					    if ( in_array( 'async', $values['attributes'] ) ) {
						    $this->asyncScripts[] = $this->scriptsAttrsThisPage['async'][] = $asset;
					    }

					    if ( in_array( 'defer', $values['attributes'] ) ) {
						    $this->deferScripts[] = $this->scriptsAttrsThisPage['defer'][] = $asset;
					    }
				    }
			    }
		    }

		    // Any load exceptions? "not here (exception)" option
		    if ( ! empty($targetLocationNoLoads) ) {
			    foreach ($targetLocationNoLoads as $handle => $values) {
				    if (in_array('async', $values)) {
					    $this->scriptsAttrsNoLoad['async'][] = $handle;
				    }

				    if (in_array('defer', $values)) {
					    $this->scriptsAttrsNoLoad['defer'][] = $handle;
				    }
			    }
		    }
	    }

	    $this->scriptsAttributesChecked = true;

	    if ($wpacuLoadJsAsyncHandles = Misc::getVar('get', 'wpacu_js_async')) {
		    if (strpos($wpacuLoadJsAsyncHandles, ',') !== false) {
			    foreach (explode(',', $wpacuLoadJsAsyncHandles) as $wpacuLoadJsAsyncHandle) {
				    if (trim($wpacuLoadJsAsyncHandle)) {
					    $this->asyncScripts[] = $wpacuLoadJsAsyncHandle;
				    }
			    }
		    } else {
			    $this->asyncScripts[] = $wpacuLoadJsAsyncHandles;
		    }
	    }

	    if ($wpacuLoadJsDeferHandles = Misc::getVar('get', 'wpacu_js_defer')) {
	        if (strpos($wpacuLoadJsDeferHandles, ',') !== false) {
	            foreach (explode(',', $wpacuLoadJsDeferHandles) as $wpacuLoadJsDeferHandle) {
	                if (trim($wpacuLoadJsDeferHandle)) {
		                $this->deferScripts[] = $wpacuLoadJsDeferHandle;
	                }
                }
            } else {
		        $this->deferScripts[] = $wpacuLoadJsDeferHandles;
	        }
	    }

	    return array('async' => $this->asyncScripts, 'defer' => $this->deferScripts);
    }

	/**
     * This fetches the list of applied attributes (defer, async) that will be used
     * on the scripts management list
     *
	 * @param $data
	 *
	 * @return mixed
	 */
	public function getScriptsAttributesToPrintInList($data)
    {
	    // Dashboard view? Fetch the attributes as it's on AJAX mode view (via Dashboard)
        if (! Main::instance()->isFrontendEditView) {
	        $this->scriptsAttrsThisPage = $this->getScriptAttributesToApplyOnCurrentPage($data);
        }

	    // If on front-end view getScriptAttributesToApplyOnCurrentPage() was already called
	    // and $this->scriptsAttrsThisPage populated within method getScriptAttributesToApplyOnCurrentPage()

        $data['scripts_attributes'] = array(
	        'everywhere'       => $this->getScriptGlobalAttributes(),
            'on_this_page'     => $this->scriptsAttrsThisPage,
            'not_on_this_page' => $this->scriptsAttrsNoLoad
        );

        return $data;
    }

	/**
	 * @return array
	 */
	public function getScriptGlobalAttributes()
    {
        if (! empty($this->globalScriptsAttributes)) {
            return $this->globalScriptsAttributes;
        }

	    $list = get_option( WPACU_PLUGIN_ID . '_global_data');

	    $asyncGlobalScripts = $deferGlobalScripts = array();

	    // Empty list, no attributes to apply
	    if (! $list) {
		    $this->globalScriptsAttributes = array('async' => $asyncGlobalScripts, 'defer' => $deferGlobalScripts);
		    return $this->globalScriptsAttributes;
        }

        $list = json_decode($list, ARRAY_A);

	    // Is it in a valid JSON format and global attributes (applied everywhere) are stored there?
        if ( ! empty($list['scripts']['everywhere']) ) {
            foreach ($list['scripts']['everywhere'] as $asset => $values) {
                if ( ! empty($values['attributes']) ) {
                    if (in_array('async', $values['attributes'])) {
                        $asyncGlobalScripts[] = $asset;
                    }

                    if (in_array('defer', $values['attributes'])) {
                        $deferGlobalScripts[] = $asset;
                    }
                }
            }
        }

	    $this->globalScriptsAttributes = array('async' => $asyncGlobalScripts, 'defer' => $deferGlobalScripts);

	    return $this->globalScriptsAttributes;
    }

	/**
	 * @return array
	 */
	public static function getMediaQueriesLoad()
	{
	    if ($handleData = ObjectCache::wpacu_cache_get('wpacu_media_queries_load')) {
	        return $handleData;
	    }

		$handleData = array('styles' => array(), 'scripts' => array());

		$globalKey = 'media_queries_load';

		$handleDataListJson = get_option(WPACU_PLUGIN_ID . '_global_data');

		if ($handleDataListJson) {
			$handleDataList = @json_decode($handleDataListJson, true);

			// Issues with decoding the JSON file? Return an empty list
			if (Misc::jsonLastError() !== JSON_ERROR_NONE) {
				ObjectCache::wpacu_cache_add('wpacu_media_queries_load', $handleData);
				return $handleData;
			}

			// Are new positions set for styles and scripts?
			foreach (array('styles', 'scripts') as $assetKey) {
				if ( ! empty( $handleDataList[$assetKey][$globalKey] ) ) {
					$handleData[$assetKey] = $handleDataList[$assetKey][$globalKey];
				}
			}
		}

		ObjectCache::wpacu_cache_add('wpacu_media_queries_load', $handleData);

		return $handleData;
	}

    /**
     * This function is used first, and in case there are entries, call /pro/classes/MatchMediaLoadPro.php
     * This helps reduce resources as calling too many classes/methods/functions adds up to the total load time of the plugin
     *
     * @param $htmlSource
     * @param $assetType
     *
     * @return array|\string[][]
     */
    public static function anyMediaQueryLoadAssetsFor($htmlSource, $assetType)
    {
        if ($assetType === 'styles') {
            if ( isset($_GET['wpacu_no_media_query_load_for_css']) ) {
                return array();
            }

            preg_match_all('#<link[^>]*(data-wpacu-apply-media-query)[^>]*(>)#Umi', $htmlSource, $matchesSourcesFromTags, PREG_SET_ORDER);

            return $matchesSourcesFromTags;
        }

        if ($assetType === 'scripts') {
            if ( isset($_GET['wpacu_no_media_query_load_for_js']) ) {
                return array();
            }

            preg_match_all(
                '#(<script[^>]*(data-wpacu-apply-media-query)(|\s+)=(|\s+)[^>]*>)|(<link[^>]*(as(\s+|)=(\s+|)(|"|\')script(|"|\'))(.*)data-wpacu-apply-media-query(.*)[^>]*>|<link[^>]*(.*)data-wpacu-apply-media-query(.*)(as(\s+|)=(\s+|)(|"|\')script(|"|\'))[^>]*>)#Umi',
                $htmlSource,
                $matchesSourcesFromTags,
                PREG_SET_ORDER
            );

            return $matchesSourcesFromTags;
        }

        return array();
    }

	/**
	 * @param $tag
	 * @param $handle
	 * @return mixed
	 */
	public function styleLoaderTag($tag, $handle)
	{
		/* [wpacu_timing] */ $wpacuTimingName = 'style_loader_tag_pro_changes'; Misc::scriptExecTimer( $wpacuTimingName ); /* [/wpacu_timing] */

		$mediaQueriesLoad = self::getMediaQueriesLoad();

        $enableStatus = isset($mediaQueriesLoad['styles'][$handle]['enable']) ? (int)$mediaQueriesLoad['styles'][$handle]['enable'] : 0;
        $mediaQueryCustomValue = isset($mediaQueriesLoad['styles'][$handle]['value']) ? $mediaQueriesLoad['styles'][$handle]['value'] : '';

        $reps = array();

        // Case 1: Make the browser download the file only if this media query is matched: $mediaQueryCustomValue
        if ($enableStatus === 1 && $mediaQueryCustomValue !== '') {
            $reps = array( '<link ' => '<link data-wpacu-apply-media-query=\'' . esc_attr($mediaQueriesLoad['styles'][$handle]['value']) . '\' ' );
        }

        // Case 2: Make the browser download the file only if its current media query is matched
        // The LINK tag already has a "media" attribute different from "all"
        if ($enableStatus === 2) {
            $mediaAttrValue = Misc::getValueFromTag($tag, 'media');

            if ($mediaAttrValue !== 'all') {
                $reps = array( '<link ' => '<link data-wpacu-apply-media-query=\'' . esc_attr($mediaAttrValue) . '\' ' );
            }
        }

        if ( ! empty($reps) ) {
            // Perform the replacement
            $tag = str_replace( array_keys( $reps ), array_values( $reps ), $tag );

            if (strpos($tag, 'data-wpacu-style-handle') === false) {
                // This is for a hardcoded LINK with "href"
                $reps = array( '<link ' => '<link data-wpacu-style-handle=\'' . $handle . '\' ' );
                $tag = str_replace( array_keys( $reps ), array_values( $reps ), $tag );
            }

            ObjectCache::wpacu_cache_add_to_array('wpacu_css_media_queries_load_current_page', $handle);
        }

		/* [wpacu_timing] */ Misc::scriptExecTimer( $wpacuTimingName, 'end' ); /* [/wpacu_timing] */
		return $tag;
	}

	/**
	 * @param $tag
	 * @param $handle
     *
	 * @return mixed
	 */
	public function scriptLoaderTag($tag, $handle)
	{
		/* [wpacu_timing] */ $wpacuTimingName = 'script_loader_tag_pro_changes'; Misc::scriptExecTimer( $wpacuTimingName ); /* [/wpacu_timing] */

		$applyAsyncOrDeferFromSetRules = true;

		// Prevent adding both 'async' and 'defer' attributes for debugging purposes
		if ( ! empty($_REQUEST) && array_key_exists('wpacu_no_async', $_REQUEST) && array_key_exists('wpacu_no_defer', $_REQUEST) ) {
			$applyAsyncOrDeferFromSetRules = false;
		}

        if ($applyAsyncOrDeferFromSetRules) {
	        $attrs = $this->getScriptAttributesToApplyOnCurrentPage();

	        foreach ( array( 'async', 'defer' ) as $attrType ) {
		        if ( ! empty( $_REQUEST ) && array_key_exists( 'wpacu_no_' . $attrType, $_REQUEST ) ) {
			        continue; // prevent adding any async/defer attributes for debugging purposes
		        }

		        if ( in_array( $handle, $attrs[ $attrType ] ) && ( ! in_array( $handle, $this->scriptsAttrsNoLoad[ $attrType ] ) ) ) {
			        $tag = str_replace( ' src=', ' ' . $attrType . '=\'' . $attrType . '\' src=', $tag );
			        ObjectCache::wpacu_cache_add_to_array( 'wpacu_js_media_queries_load_current_page', $handle );
		        }
	        }
        }

		$mediaQueriesLoad = self::getMediaQueriesLoad();

		if (isset($mediaQueriesLoad['scripts'][$handle]['enable'], $mediaQueriesLoad['scripts'][$handle]['value']) &&
		    $mediaQueriesLoad['scripts'][$handle]['enable'] && $mediaQueriesLoad['scripts'][$handle]['value']
		) {
			$reps = array( '<script ' => '<script data-wpacu-apply-media-query=\'' . esc_attr($mediaQueriesLoad['scripts'][$handle]['value']) . '\' ' );
            $tag = str_replace( array_keys( $reps ), array_values( $reps ), $tag );

            if (strpos($tag, 'data-wpacu-script-handle') === false) {
                // This is for a hardcoded script with "SRC"
                $reps = array( '<script ' => '<script data-wpacu-script-handle=\'' . $handle . '\' ' );
                $tag = str_replace( array_keys( $reps ), array_values( $reps ), $tag );
            }
		}

		/* [wpacu_timing] */ Misc::scriptExecTimer( $wpacuTimingName, 'end' ); /* [/wpacu_timing] */
		return $tag;
	}

	/**
     * Get the list of any position changes for the assets
     * If there are any values returned, then trigger the /classes/pro/PositionsPro.php class and its methods
     *
     * @param $filtered bool
     * If set to false, it will return the settings from the database as they are
     * The filtered version might have styles removed in case "Optimize CSS Delivery" from WP Rocket is enabled
     *
     * @return array
     */
    public static function getAssetsPositions($filtered = true)
    {
        $cacheKeyToCheck = $filtered ? '_filtered' : '_non_filtered';

        if ( $newPositionsAssets = ObjectCache::wpacu_cache_get('wpacu_assets_positions' . $cacheKeyToCheck) ) {
            return $newPositionsAssets;
        }

        $newPositionsAssets = array('styles' => array(), 'scripts' => array());

        $newPositionsListJson = get_option(WPACU_PLUGIN_ID . '_global_data');

        if ($newPositionsListJson) {
            $newPositionsList = @json_decode($newPositionsListJson, true);

            // Issues with decoding the JSON file? Return an empty list
            if (Misc::jsonLastError() !== JSON_ERROR_NONE) {
                ObjectCache::wpacu_cache_set('wpacu_assets_positions' . $cacheKeyToCheck, $newPositionsAssets);
                return $newPositionsAssets;
            }

            if ($filtered) {
                $newPositionsList = apply_filters('wpacu_pro_new_positions_assets', $newPositionsList);
            }

            // Are new positions set for styles and scripts?
            foreach (array('styles', 'scripts') as $assetKey) {
                if ( ! empty( $newPositionsList[$assetKey]['positions'] ) ) {
                    $newPositionsAssets[$assetKey] = $newPositionsList[$assetKey]['positions'];
                }
            }
        }

        /*
         * On page request, for testing purposes CSS/JS can be moved from HEAD to BODY and vice-versa
           e.g. /?wpacu_css_move_to_body=handle-here,another-handle | /?wpacu_js_move_to_body=handle-here,another-handle
                /?wpacu_css_move_to_head=handle-here,another-handle | /?wpacu_js_move_to_head=handle-here,another-handle
            * Note: A single handle can be used; Multiple handle names are separated by comma
        */
        foreach (array('head', 'body') as $wpacuChosenPosition) {
            foreach (array('css', 'js') as $assetExt) {
                if ($wpacuCssMoveToNewPositionHandles = Misc::getVar('get', 'wpacu_'.$assetExt.'_move_to_' . $wpacuChosenPosition)) {
                    $assetType = ($assetExt === 'css') ? 'styles' : 'scripts';

                    if (strpos($wpacuCssMoveToNewPositionHandles, ',') !== false) {
                        foreach (explode(',', $wpacuCssMoveToNewPositionHandles) as $wpacuCssMoveToBodyHandle) {
                            if (trim($wpacuCssMoveToBodyHandle)) {
                                $newPositionsAssets[$assetType][$wpacuCssMoveToBodyHandle] = $wpacuChosenPosition;
                            }
                        }
                    } else {
                        $newPositionsAssets[$assetType][$wpacuCssMoveToNewPositionHandles] = $wpacuChosenPosition;
                    }
                }
            }
        }

        ObjectCache::wpacu_cache_set('wpacu_assets_positions' . $cacheKeyToCheck, $newPositionsAssets);

        return $newPositionsAssets;
    }

    // [START HARDCODED RELATED METHODS]
    // Thess methods are called first to determine if it's worth loading the following classes:
    // -- /classes/HardcodedAssets.php
    // -- /pro/classes/HardcodedAssetsPro.php
    /**
     * @return bool
     */
    public static function triggerDifferentHtmlAlterationForFrontendView()
    {
        if ( is_admin() ) {
            return false;
        }

        // If "Smart Slider 3" plugin is not enabled, then this triggering method won't take effect
        if ( ! defined('SMARTSLIDER3_LIBRARY_PATH') ) {
            return false;
        }

        // Do not do continue if "Test Mode" is Enabled and the user is a guest
        if ( ! Menu::userCanManageAssets() && Main::instance()->settings['test_mode'] ) {
            return false;
        }

        return true;
    }

    /**
     * The '_has_hardcoded_rule' key is set to true when at least one hardcoded rule is in the list
     *
     * @return array[]
     */
    public static function getHardcodedRules()
    {
        $rules = array(
            'unload' => self::getHardcodedUnloadList(),
        );

        if ( ! empty($rules['unload']) ) {
            foreach ( $rules['unload'] as $generatedHandles ) {
                if ( ! empty($generatedHandles) ) {
                    foreach ( $generatedHandles as $generatedHandle ) {
                        if ( strpos($generatedHandle, 'wpacu_hardcoded_') !== false ) {
                            $rules['_has_hardcoded_rule'] = true;
                            break;
                        }
                    }
                }
            }
        }

        // [Positions]
        $assetsPositions = self::getAssetsPositions();

        foreach ( $assetsPositions as $assetType => $list ) {
            if ( ! empty($list) ) {
                foreach ( $list as $dbHandle => $position ) {
                    if ( strpos($dbHandle, 'wpacu_hardcoded_') === false ) {
                        unset($assetsPositions[$assetType][$dbHandle]);
                    } else {
                        $rules['_has_hardcoded_rule'] = true;
                    }
                }

                if (empty($assetsPositions[$assetType])) {
                    unset($assetsPositions[$assetType]);
                }
            } else {
                unset($assetsPositions[$assetType]);
            }
        }

        $rules['positions'] = $assetsPositions;
        // [/Positions]

        // [Preloads]
        $preloads = Preloads::instance()->getPreloads();

        foreach ( $preloads as $assetType => $preloadList ) {
            if ( ! empty($preloadList) ) {
                foreach ( array_keys($preloadList) as $dbHandle ) {
                    if ( strpos($dbHandle, 'wpacu_hardcoded_') === false ) {
                        unset($preloads[$assetType][$dbHandle]);
                    } else {
                        $rules['_has_hardcoded_rule'] = true;
                    }
                }
            }

            if (empty($preloads[$assetType])) {
                unset($preloads[$assetType]);
            }
        }

        $rules['preloads'] = $preloads;
        // [/Preloads]

        // [Media Queries Load]
        $mediaQueriesLoad = self::getMediaQueriesLoad();

        foreach ($mediaQueriesLoad as $assetType => $loadList) {
            if ( ! empty($loadList) ) {
                foreach ( $loadList as $dbHandle => $loadValuesPerHandle ) {
                    if ( (empty($loadValuesPerHandle['enable']) || empty($loadValuesPerHandle['value']))
                         || strpos($dbHandle, 'wpacu_hardcoded_') === false ) {
                        unset ($mediaQueriesLoad[$assetType][$dbHandle]);
                    } else {
                        $rules['_has_hardcoded_rule'] = true;
                    }
                }
            }

            if (empty($mediaQueriesLoad[$assetType])) {
                unset($mediaQueriesLoad[$assetType]);
            }
        }

        $rules['media_queries_load'] = $mediaQueriesLoad;
        // [/Media Queries Load]

        $applyAsyncOrDeferFromSetRules = true;

        // Prevent adding both 'async' and 'defer' attributes for debugging purposes
        if ( ! empty($_REQUEST) && array_key_exists('wpacu_no_async', $_REQUEST) && array_key_exists('wpacu_no_defer', $_REQUEST) ) {
            $applyAsyncOrDeferFromSetRules = false;
        }

        if ($applyAsyncOrDeferFromSetRules) {
            global $wpacuMainPro;

            if ( ! (is_object($wpacuMainPro) && method_exists($wpacuMainPro, 'getScriptAttributesToApplyOnCurrentPage')) ) {
                $wpacuMainPro = new self();
            }

            $scriptAttrs = $wpacuMainPro->getScriptAttributesToApplyOnCurrentPage();

            foreach ($scriptAttrs as $attrType => $attrsList) {
                if ( ! empty($attrsList) ) {
                    foreach ( $attrsList as $dbHandleKey => $dbHandle ) {
                        if (strpos($dbHandle, 'wpacu_hardcoded_') === false) {
                            unset($scriptAttrs[$attrType][$dbHandleKey]);
                        } else {
                            $rules['_has_hardcoded_rule'] = true;
                        }
                    }
                }

                if (empty($scriptAttrs[$attrType])) {
                    unset($scriptAttrs[$attrType]);
                }
            }

            $rules['script_attrs'] = $scriptAttrs;
        }

        return $rules;
    }

    /**
     * @return array
     */
    public static function getHardcodedUnloadList()
    {
        $hardcodedUnloadList['wpacu_hardcoded_links']                    = ObjectCache::wpacu_cache_get('wpacu_hardcoded_links')  ?: array();
        $hardcodedUnloadList['wpacu_hardcoded_styles']                   = ObjectCache::wpacu_cache_get('wpacu_hardcoded_styles') ?: array();
        $hardcodedUnloadList['wpacu_hardcoded_scripts_src']              = ObjectCache::wpacu_cache_get('wpacu_hardcoded_scripts_src') ?: array();
        $hardcodedUnloadList['wpacu_hardcoded_scripts_noscripts_inline'] = ObjectCache::wpacu_cache_get('wpacu_hardcoded_scripts_noscripts_inline') ?: array();

        return Misc::filterList($hardcodedUnloadList);
    }
    // [END HARDCODED RELATED METHODS]

	/**
	 * @param $postId
	 *
	 * @return array
	 */
	public static function getTaxonomyTermIdsAssocToPost($postId)
    {
        $postTaxonomies = get_post_taxonomies($postId);

        if (in_array('post_format', $postTaxonomies)) {
            $unsetKey = array_search('post_format', $postTaxonomies);
            unset($postTaxonomies[$unsetKey]);
        }

        // All terms associated to all taxonomies
        $allTermsIds = array();

        foreach ($postTaxonomies as $postTaxonomy) {
	        $allPostTerms = get_the_terms($postId, $postTaxonomy);

	        if (empty($allPostTerms)) {
	            continue;
	        }

	        foreach ($allPostTerms as $postTermData) {
	            $allTermsIds[] = $postTermData->term_id;
	        }
        }

        return $allTermsIds;
    }

	/**
	 * @param $postType
	 * @param $assetType
	 * @param $handle
	 *
	 * @return array|mixed
	 */
	public static function getTaxonomyValuesAssocToPostType($postType, $assetType = '', $handle = '')
    {
	    $existingListAllJson = get_option( WPACU_PLUGIN_ID . '_bulk_unload' );

	    if ( ! $existingListAllJson || ! $postType ) {
		    return array();
	    }

	    $existingListAll = json_decode( $existingListAllJson, true );

	    if ( Misc::jsonLastError() !== JSON_ERROR_NONE ) {
		    return array();
	    }

	    if ( ! empty( $existingListAll[ $assetType ]['post_type_via_tax'][ $postType ] [ $handle ] ['values'] ) ) {
            /*
             * Fetch for a certain handle (either a CSS or a JS)
             */
            return $existingListAll[ $assetType ]['post_type_via_tax'][ $postType ] [ $handle ] ['values'];
        }

	    $finalList = array(); // default

	    if ( $assetType === '' && $handle === '' ) {
            /*
             * Fetch all CSS/JS that have rules for this post type
             */
            foreach ( array('styles', 'scripts') as $assetTypeTwo ) {
                if ( ! empty($existingListAll[ $assetTypeTwo ]['post_type_via_tax'][ $postType ]) ) {
                    $finalList[$assetTypeTwo] = $existingListAll[ $assetTypeTwo ]['post_type_via_tax'][ $postType ];
                }
            }

		    return $finalList;
        }

	    return array();
    }

	/**
     * Case 1: If $postType is not mentioned, it will get all post types
     * Case 2: If $postType is set and $assetType & $handle are not set, it will get all rules for $postType
     * Case 3: If all parameters are set, it will get any terms set for the CSS/JS handle loaded within $postType pages
     *
	 * @param string $postType
	 * @param string $assetType
	 * @param string $handle
	 *
	 * @return array|\array[][]|mixed
	 */
	public static function getTaxonomyValuesAssocToPostTypeLoadExceptions($postType = '', $assetType = '', $handle = '')
	{
		$exceptionsListDefault = array();

	    if ($postType) {
	        if ($assetType === '' && $handle === '') {
	            // Default for all results for this $postType
		        $exceptionsListDefault = array( $postType => array( 'styles' => array(), 'scripts' => array() ) );
	        } else {
	            // Default for the terms list for the specific $handle of $assetType ("styles" or "scripts")
                $exceptionsListDefault = array();
	        }
	    }

		$exceptionsListJson = get_option(WPACU_PLUGIN_ID . '_post_type_via_tax_load_exceptions');
		$exceptionsList = @json_decode($exceptionsListJson, true);

		// Issues with decoding the JSON file? Return an empty list
		if (Misc::jsonLastError() !== JSON_ERROR_NONE) {
			return $exceptionsListDefault;
		}

		// Return any handles added as load exceptions for the requested $postType
		if ($postType !== '' && isset($exceptionsList[$postType])) {
			/*
			 * Fetch load exceptions for a certain handle (either a CSS or a JS)
			 */
		    if ( ! empty($exceptionsList[$postType][$assetType][$handle]['values']) ) {
			    return $exceptionsList[ $postType ] [$assetType] [ $handle ] ['values'];
		    }

			if ( $assetType === '' && $handle === '' ) {
			    /*
				 * Fetch all load exceptions (CSS & JS)
				 */
			    return $exceptionsList[$postType];
		    }
		} elseif (is_array($exceptionsList) && ! empty($exceptionsList)) {
		    return $exceptionsList;
		}

		return $exceptionsListDefault;
	}

	/**
	 * @param $postType
	 * @param $assetType
	 * @param $handle
	 * @param array $alreadySetTerms
	 * @param string $for
	 *
	 * @return string
	 */
	public static function loadDDOptionsForAllSetTermsForPostType($postType, $assetType, $handle, $alreadySetTerms = array(), $for = 'unload')
    {
	    $allSetTermsPostType = Main::getAllSetTaxonomies($postType);

	    if (empty($alreadySetTerms)) {
		    $alreadySetTerms = ( $for === 'unload' )
			    ? self::getTaxonomyValuesAssocToPostType( $postType, $assetType, $handle )
			    : self::getTaxonomyValuesAssocToPostTypeLoadExceptions( $postType, $assetType, $handle );
	    }

	    $output = '';

	    foreach (array_keys($allSetTermsPostType) as $taxLabel) {
		    $output .= '<optgroup label="'.esc_attr($taxLabel.' ('.$allSetTermsPostType[$taxLabel][0]['taxonomy'].')').'">'."\n";

		    $taxDropDown = wp_dropdown_categories(array(
			    'taxonomy'     => $allSetTermsPostType[$taxLabel][0]['taxonomy'],
			    'echo'         => 0,
			    'hierarchical' => 1,
			    'show_count'   => 1,
			    'order_by'     => 'name'
		    ));

		    $taxDropDown = preg_replace('@<select[^>]*?>@si', '', $taxDropDown);
		    $taxDropDown = str_ireplace('</select>', '', $taxDropDown);

		    if ( ! empty($alreadySetTerms) ) {
			    foreach ($alreadySetTerms as $termId) {
				    $taxDropDown = str_replace('value="'.$termId.'"', 'selected="selected" value="'.(int)$termId.'"', $taxDropDown);
			    }
		    }

		    $output .= $taxDropDown;

		    $output .= '</optgroup>'."\n";
	    }

	    return $output;
    }

	/**
	 *
	 */
	public function ajaxLoadAllSetTermsForPostType()
    {
	    // Check nonce
	    if ( ! isset( $_POST['wpacu_nonce'] ) || ! wp_verify_nonce( $_POST['wpacu_nonce'], 'wpacu_ajax_get_post_type_terms_nonce' ) ) {
		    echo 'Error: The security nonce is not valid.';
		    exit();
	    }

	    // Check privileges
	    if (! Menu::userCanManageAssets()) {
		    echo 'Error: Not enough privileges to perform this action.';
		    exit();
	    }

	    // Current Post Type (depending on the admin's location)
	    $postType  = isset($_POST['wpacu_post_type'])  ? sanitize_text_field($_POST['wpacu_post_type']) : '';
	    $handle    = isset($_POST['wpacu_handle'])     ? esc_html($_POST['wpacu_handle'])               : '';
	    $assetType = isset($_POST['wpacu_asset_type']) ? esc_html($_POST['wpacu_asset_type'])           : '';
	    $for       = isset($_POST['wpacu_for'])        ? esc_html($_POST['wpacu_for'])                  : '';

	    if ( ! $postType ) {
		    echo 'Error: The post type is missing.';
		    exit();
	    }

        echo self::loadDDOptionsForAllSetTermsForPostType($postType, $assetType, $handle, array(), $for);
        exit();
    }

	/**
	 * @param $list
	 * @param $assetType
	 * @param $filterType
	 *
	 * @return mixed
	 */
	public function filterAssetsUnloadList($list, $assetType, $filterType)
    {
	    $currentPost = Main::instance()->getCurrentPost();

        if ($filterType === 'unload') {
	        self::$unloads['regex'] = self::getRegExRules('unloads');

            // Page type: Any URL that mighty have its URI match any of the rules
	        if ( ! empty( self::$unloads['regex'][ $assetType ] ) ) {
		        foreach ( self::$unloads['regex'][ $assetType ] as $handle => $handleValues ) {
			        if ( isset( $handleValues['enable'], $handleValues['value'] ) && $handleValues['enable'] ) {
				        // We want to make sure the RegEx rules will be working fine if certain characters (e.g. Thai ones) are used
				        $requestUriAsItIs = rawurldecode( $_SERVER['REQUEST_URI'] );

				        if ( self::isRegExMatch( $handleValues['value'], $requestUriAsItIs ) ) {
					        $list[] = $handle;
					        self::$unloads['regex']['current_url_matches'][ $assetType ][] = $handle;
				        }
			        }
		        }
	        }

	        // Page type: a post that might have taxonomies (e.g. category) associated to it
            if (isset($currentPost->post_type) && $currentPost->post_type) {
	            self::$unloads['post_type_via_tax']        = self::getTaxonomyValuesAssocToPostType( $currentPost->post_type );

	            if ( ! empty( self::$unloads['post_type_via_tax'][ $assetType ] ) && isset( $currentPost->ID ) && $currentPost->ID ) {
		            $currentPostTerms = self::getTaxonomyTermIdsAssocToPost( $currentPost->ID );

		            foreach ( self::$unloads['post_type_via_tax'][ $assetType ] as $assetHandle => $assetData ) {
			            if ( isset( $assetData['enable'] ) && $assetData['enable'] && ! empty( $assetData['values'] ) ) {
				            // Go through the terms set and check if the current post ID is having the taxonomy value associated with it
				            foreach ( $assetData['values'] as $termId ) {
					            if ( in_array( $termId, $currentPostTerms ) ) {
						            // At least one match found; Stop here and add the asset to the unloading list
						            $list[] = $assetHandle;
						            self::$unloads['post_type_via_tax']['current_post_matches'][$assetType ][] = $assetHandle;
						            break;
					            }
				            }
			            }
		            }
	            }
            }

	        // Page type: A taxonomy page (e.g. category page), author page
            // e.g. "Unload on All Pages of category taxonomy type * bulk unload"
	        $bulkUnloads = apply_filters('wpacu_pro_get_bulk_unloads', array());

            foreach (array('taxonomy', 'author') as $bulkType) {
                if ( ! empty( $bulkUnloads['bulk_unloaded'][$bulkType][ $assetType ] ) ) {
                    foreach ( $bulkUnloads['bulk_unloaded'][$bulkType][ $assetType ] as $assetHandle ) {
                        $list[] = $assetHandle;
                    }
                }
            }

	        }

        if ($filterType === 'load_exception') {
	        self::$loadExceptions['regex'] = self::getRegExRules('load_exceptions');

	        if ( ! empty( self::$loadExceptions['regex'][ $assetType ] ) ) {
		        foreach ( $list as $handleKey => $handle ) {
			        if ( isset( self::$loadExceptions['regex'][ $assetType ][ $handle ]['enable'], self::$loadExceptions['regex'][ $assetType ][ $handle ]['value'] )
			             && self::$loadExceptions['regex'][ $assetType ][ $handle ]['enable'] ) { // Needs to be marked as enabled
				        // We want to make sure the RegEx rules will be working fine if certain characters (e.g. Thai ones) are used
				        $requestUriAsItIs = rawurldecode( $_SERVER['REQUEST_URI'] );

				        if ( self::isRegExMatch( self::$loadExceptions['regex'][ $assetType ][ $handle ]['value'], $requestUriAsItIs ) ) {
					        unset( $list[ $handleKey ] );
					        self::$loadExceptions['regex']['current_url_matches'][ $assetType ][] = $handle;

					        // Are there any unload rules via RegEx? Clean them up as the load exception takes priority
					        if ( isset( self::$unloads['regex'][ $assetType ][ $handle ] ) ) {
						        unset( self::$unloads['regex'][ $assetType ][ $handle ] );
					        }

					        if ( isset( self::$unloads['regex']['current_url_matches'][ $assetType ] )
					             && is_array( self::$unloads['regex']['current_url_matches'][ $assetType ] )
					             && in_array( $handle, self::$unloads['regex']['current_url_matches'][ $assetType ] ) ) {
						        $targetKey = array_search( $handle, self::$unloads['regex']['current_url_matches'][ $assetType ] );
						        unset( self::$unloads['regex']['current_url_matches'][ $assetType ][ $targetKey ] );
					        }
				        }
			        }
		        }
	        }

            if (isset($currentPost->post_type) && $currentPost->post_type) {
	            self::$loadExceptions['post_type_via_tax'] = self::getTaxonomyValuesAssocToPostTypeLoadExceptions( $currentPost->post_type );

	            if ( ( ! empty( self::$loadExceptions['post_type_via_tax'][ $assetType ] ) ) && isset( $currentPost->ID ) && $currentPost->ID ) {
		            $currentPostTerms = self::getTaxonomyTermIdsAssocToPost( $currentPost->ID );

		            foreach ( self::$loadExceptions['post_type_via_tax'][ $assetType ] as $assetHandle => $assetData ) {
			            if ( isset( $assetData['enable'] ) && $assetData['enable'] && ! empty( $assetData['values'] ) ) {
				            // Go through the terms set and check if the current post ID is having the taxonomy value associated with it
				            foreach ( $assetData['values'] as $termId ) {
					            if ( in_array( $termId, $currentPostTerms ) && in_array( $assetHandle, $list ) ) {
						            // At least one match found; Stop here and remove the asset to the unloading list
						            $handleKey = array_search( $assetHandle, $list );
						            unset( $list[ $handleKey ] );
						            break;
					            }
				            }
			            }
		            }
	            }
            }
        }

	    return $list;
    }

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function filterDataVarTemplate($data)
	{
		if (isset($data['is_dashboard_view']) && $data['is_dashboard_view']) {
	        self::$unloads['regex'] = $this->filterThisVar('unloadsRegEx', $data); // Any RegEx unload matches?
	        self::$loadExceptions['regex'] = $this->filterThisVar('loadExceptionsRegEx', $data); // Any RegEx load exceptions matches?

	        self::$unloads['post_type_via_tax'] = $this->filterThisVar('unloadsPostTypeViaTax', $data); // Any post type via tax unload matches?
	        self::$loadExceptions['post_type_via_tax'] = $this->filterThisVar('loadExceptionsPostTypeViaTax', $data); // Any post type via tax load exception matches for current post?
        }

		/*
         * [START] Any matches for the current page?
         */
		$data['unloads_regex_matches'] = array(
			'styles'  => (isset(self::$unloads['regex']['current_url_matches']['styles'])  ? self::$unloads['regex']['current_url_matches']['styles']  : array()),
			'scripts' => (isset(self::$unloads['regex']['current_url_matches']['scripts']) ? self::$unloads['regex']['current_url_matches']['scripts'] : array())
		);
		$data['load_exceptions_regex_matches'] = array(
			'styles'  => (isset(self::$loadExceptions['regex']['current_url_matches']['styles'])  ? self::$loadExceptions['regex']['current_url_matches']['styles']  : array()),
			'scripts' => (isset(self::$loadExceptions['regex']['current_url_matches']['scripts']) ? self::$loadExceptions['regex']['current_url_matches']['scripts'] : array())
		);
		$data['unloads_post_type_via_tax_matches'] = array(
			'styles'  => (isset(self::$unloads['post_type_via_tax']['current_post_matches']['styles'])  ? self::$unloads['post_type_via_tax']['current_post_matches']['styles']  : array()),
			'scripts' => (isset(self::$unloads['post_type_via_tax']['current_post_matches']['scripts']) ? self::$unloads['post_type_via_tax']['current_post_matches']['scripts'] : array())
		);
		$data['load_exceptions_post_type_via_tax_matches'] = array(
			'styles'  => (isset(self::$loadExceptions['post_type_via_tax']['current_post_matches']['styles'])  ? self::$loadExceptions['post_type_via_tax']['current_post_matches']['styles']  : array()),
			'scripts' => (isset(self::$loadExceptions['post_type_via_tax']['current_post_matches']['scripts']) ? self::$loadExceptions['post_type_via_tax']['current_post_matches']['scripts'] : array())
		);
		/*
		 * [END] Any matches for the current page?
		 */

		$data['handle_unload_regex'] = self::$unloads['regex'];
		$data['handle_load_regex']   = self::$loadExceptions['regex'];

		if (isset($data['post_type']) && $data['post_type']) {
			$data['handle_unload_via_tax']   = self::$unloads['post_type_via_tax'];
			$data['handle_load_via_tax']     = self::$loadExceptions['post_type_via_tax'];
		}

		$data['media_queries_load'] = self::getMediaQueriesLoad();

		// "On this page", "Everywhere", "Not on this page (exception)" list
		$data = apply_filters('wpacu_pro_get_scripts_attributes_for_each_asset', $data);

		// Pull the other bulk unloads such as 'taxonomy' and 'author' pages
		$data = apply_filters('wpacu_pro_get_bulk_unloads', $data);

		if ( ! empty($data['all']['unloaded_plugins']) ) {
			$GLOBALS['wpacu_filtered_plugins'] = (array)$data['all']['unloaded_plugins'];
		}

        return $data;
	}

	/**
     * This is the base64 encoded list printed when /?wpassetcleanup_load=1 is used
     *
	 * @param $list
	 *
	 * @return mixed
	 */
	public static function filterListOnDashboardAjaxCall($list)
    {
	    // Any unloaded plugins from "Plugins Manager" (to be printed in the CSS/JS manager plugins area)
	    $list['unloaded_plugins'] = isset($GLOBALS['wpacu_filtered_plugins']) ? (array)$GLOBALS['wpacu_filtered_plugins'] : array();
	    return $list;
    }

	/**
	 *
	 */
	public function fallbacks()
	{
		// Fallback for the old filters
		add_filter('wpacu_pro_get_assets_unloaded_page_level', function ($list) { return apply_filters('wpacu_pro_get_assets_unloaded', $list); });
	}
}
