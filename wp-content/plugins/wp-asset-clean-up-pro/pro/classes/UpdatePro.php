<?php
namespace WpAssetCleanUpPro;

use \WpAssetCleanUp\Main;
use \WpAssetCleanUp\Misc;
use \WpAssetCleanUp\Overview;
use \WpAssetCleanUp\Update;

/**
 * Class UpdatePro
 * @package WpAssetCleanUpPro
 */
class UpdatePro
{
	/**
	 *
	 */
	public function init()
	{
		// Called from frontendUpdate()

		// "Everywhere" defer, async checkbox ticked
		add_action('wpacu_pro_asset_global_info_update', array($this, 'assetGlobalInfoUpdate'));

		// "On this page" defer, async checkbox ticked
		add_action('wpacu_pro_asset_info_update', array($this, 'assetInfoUpdate'), 10, 2);

        // Are there any plugins such as "WPML Multilingual CMS" that have the same page translated in several languages
        // each having its own ID in the "posts" table? Make sure to sync all of them whenever a specific page setting is applied
        add_filter('wpacu_get_all_assoc_tax_ids', array($this, 'getAllAssocTaxIds'));

		// Triggers after "Update" button on the front-end view is clicked
		// Called from Update.php, method frontendUpdate()
		add_action('wpacu_pro_frontend_update', array($this, 'frontendUpdate'));

		// Triggers for edit taxonomy page within the Dashboard
		add_action('admin_init', array($this, 'dashboardUpdateForEditTaxonomyPage'));

		// "Updated CSS/JS positions" tab within "BULK CHANGES" - "Restore CSS/JS positions"
		add_action('admin_init', array($this, 'restoreAssetsPositions'));

		// "Defer & Async used on all pages" tab within "BULK CHANGES" -> "Remove chosen site-wide attributes"
		add_action('admin_init', array($this, 'removeEverywhereScriptsAttributesViaBulkChanges'));

		// Case 1: Unload it for URLs matching this RegEx
		// Case 2: Load it for URLs matching this RegEx (make an exception * relevant IF any of bulk rule above is selected)
            // This action takes priority over the unloading via RegEx action
            // e.g. if unload regex matches URL, but there is an unload rule to make an exception as well, then the asset will be loaded
		add_action('wpacu_pro_update_regex_rules', array($this, 'updateRegExRules'), 10, 1);

		add_action('admin_init', array($this, 'updatePluginRules'), 10, 1);

		// If called from "BULK CHANGES" -> "RegEx Unloads" -> "Apply changes" (button)
		add_action('admin_init', static function() {
		    self::maybeUpdateBulkRegExUnloads();
			self::maybeUpdateBulkRegExLoadExceptions();
		});

		// e.g. when "+" or "-" is used within an asset's row (CSS/JS manager), the state is updated in the background to be remembered
		add_action( 'wp_ajax_' . WPACU_PLUGIN_ID . '_update_plugin_row_state',          array($this, 'ajaxUpdatePluginRowState') );
		add_action( 'wp_ajax_' . WPACU_PLUGIN_ID . '_update_plugins_row_state_in_area', array($this, 'ajaxUpdatePluginsRowStateInArea') );
	}

    /**
     * @param $postId
     *
     * @return array
     */
    public function getAllAssocTaxIds($termId)
    {
        $allTaxIds = array($termId); // default (the original ID)

        // "WPML Multilingual CMS" compatibility: Syncing post changes on all its associated translated taxonomies
        // e.g. if a taxonomy (e.g. category) in Spanish is having a CSS unloaded, then that unload will also apply for the German version of the taxonomy
        // If, for any reason, one wants to stop syncing on translated pages, the following snippet could be used in functions.php (Child Theme)
        // add_filter('wpacu_manage_assets_update_sync_wpml_translated_tax', '__return_false');
        if ( Misc::isPluginActive('sitepress-multilingual-cms/sitepress.php')
             && apply_filters('wpacu_manage_assets_update_sync_wpml_translated_tax', true) ) {
            $translations = Update::getAnyAssocTranslationsForWpml($termId, 'tax');

            if ( ! empty($translations) ) {
                foreach ( $translations as $translation ) {
                    if ( isset($translation->element_id) && $translation->element_id ) {
                        $allTaxIds[] = (int) $translation->element_id;
                    }
                }
            }
        }

        return array_unique($allTaxIds);
    }

	/**
	 * This method should be triggered via "wpacu_pro_frontend_update" action
	 * For is_archive(), author, search, 404 pages
	 *
	 */
	public function frontendUpdate()
	{
		global $wp_query, $wpdb;

		$this->updateGlobalUnloads();

		// Sometimes is_404() returns true for singular pages that are set to be 404 ones
		// Example: Through the "404page – your smart custom 404 error page" plugin
		if (Misc::getVar('post', 'wpacu_is_singular_page')) {
			return;
		}

		/*
			Possible pages:
		        - 404 page (loaded from 404.php within the theme)
		        - Search page - Default WordPress - (loaded from search.php within the theme)
				- Date page (any requested date)

		   Note: The unload list will be added to bulk unloads: "wpassetcleanup_bulk_unload" option
		*/

		$wpacuNoLoadAssets = Misc::getVar('post', WPACU_PLUGIN_ID, array());

		$bulkType = false;

		if (is_404()) {
			$bulkType = '404';
		} elseif (Main::isWpDefaultSearchPage()) {
			$bulkType = 'search';
		} elseif (is_date()) {
			$bulkType = 'date';
		} elseif ($wpacuQueriedObjForCustomPostType = MainPro::isCustomPostTypeArchivePage()) {
		    $bulkType = 'custom_post_type_archive_' . $wpacuQueriedObjForCustomPostType->name;
        }

		if ($bulkType) {
			// async, defer etc.
			$this->assetInfoUpdate($bulkType);

			// Is there any entry already in JSON format?
			$existingListJson = get_option( WPACU_PLUGIN_ID . '_bulk_unload');

			// Default list as array
			$existingListEmpty = array(
				'styles'  => array($bulkType => array()),
				'scripts' => array($bulkType => array())
			);

			$existingListData = Main::instance()->existingList($existingListJson, $existingListEmpty);
			$existingList = $existingListData['list'];

			if ($existingListData['not_empty']) {
				$existingList['styles'][$bulkType] = $existingList['scripts'][$bulkType] = array();

				foreach (array('styles', 'scripts') as $assetType) {
					// Is the list empty? Then set it to empty for $existingList which will later be updated in the database
					if (empty($wpacuNoLoadAssets[$assetType])) {
						$existingList[$assetType][$bulkType] = array();
						continue;
					}

					$existingList[$assetType][$bulkType] = $wpacuNoLoadAssets[$assetType];
				}
			}

			Misc::addUpdateOption(WPACU_PLUGIN_ID . '_bulk_unload', wp_json_encode(Misc::filterList($existingList)));

			// If globally disabled, make an exception to load for submitted assets
			if (class_exists('\\WpAssetCleanUp\\Update')) {
				$updateWpacu = new Update();
				$updateWpacu->saveLoadExceptions('for_pro');
			}

			return;
		}

		$object = $wp_query->get_queried_object();

    	/*
    	* Taxonomy page (e.g. 'product_cat' (WooCommerce) or default WordPress 'category', 'post_tag')
    	*/
		if (isset($object->taxonomy)) {
            $allTaxIds = apply_filters('wpacu_get_all_assoc_tax_ids', $object->term_id);

			$noUpdate = false;

			// Is the list empty?
			if (empty($wpacuNoLoadAssets)) {
                foreach ($allTaxIds as $taxId) {
                    // Remove any row with no results
                    $wpdb->delete(
                        $wpdb->termmeta,
                        array('term_id' => $taxId, 'meta_key' => '_' . WPACU_PLUGIN_ID . '_no_load')
                    );
                }

				$noUpdate = true;
			}

			if (! $noUpdate) {
				$jsonNoAssetsLoadList = wp_json_encode($wpacuNoLoadAssets);

                foreach ($allTaxIds as $taxId) {
                    if ( ! add_term_meta($taxId, '_' . WPACU_PLUGIN_ID . '_no_load', $jsonNoAssetsLoadList, true)) {
                        update_term_meta($taxId, '_' . WPACU_PLUGIN_ID . '_no_load', $jsonNoAssetsLoadList);
                    }
                }
			}

			// If globally disabled, make an exception to load for submitted assets
			if (class_exists('\\WpAssetCleanUp\\Update')) {
				$updateWpacu = new Update();
				$updateWpacu->saveLoadExceptions('for_pro');
			}

			$this->saveToBulkUnloads('taxonomy', $object);
			$this->removeBulkUnloads('taxonomy');

			// async, defer etc.
            foreach ($allTaxIds as $taxId) {
			    $this->assetInfoUpdate('taxonomy', $taxId);
            }

			return;
		}

		/*
		 * Author page
		 * */
		if (is_author() && ($author_id = MainPro::getAuthorIdOnAuthorArchivePage(__FILE__, __LINE__))) {
			$noUpdate = false;

			// Is the list empty?
			if (empty($wpacuNoLoadAssets)) {
				// Remove any row with no results
				$wpdb->delete(
					$wpdb->usermeta,
					array('user_id' => $author_id, 'meta_key' => '_' . WPACU_PLUGIN_ID . '_no_load')
				);

				$noUpdate = true;
			}

			if (! $noUpdate) {
				$jsonNoAssetsLoadList = wp_json_encode($wpacuNoLoadAssets);

				if (! add_user_meta($author_id, '_' . WPACU_PLUGIN_ID . '_no_load', $jsonNoAssetsLoadList, true)) {
					update_user_meta($author_id, '_' . WPACU_PLUGIN_ID . '_no_load', $jsonNoAssetsLoadList);
				}
			}

			// If globally disabled, make an exception to load for submitted assets
			if (class_exists('\\WpAssetCleanUp\\Update')) {
				$updateWpacu = new Update();
				$updateWpacu->saveLoadExceptions('for_pro');
			}

			$this->saveToBulkUnloads('author');
			$this->removeBulkUnloads('author');

			// async, defer etc.
			$this->assetInfoUpdate('author', $author_id);

			return;
		}

		// Unload it on pages that matches RegEx
		do_action('wpacu_pro_update_regex_rules', 'unload');

		// Load it on pages that matches RegEx (global rule, different from "load it on this page")
		do_action('wpacu_pro_update_regex_rules', 'load_exception');

		// Note: the caching is cleared after the page is updated (via AJAX)
    }

	/**
	 * @param $type
	 * @param int $dataId
	 */
	public function assetInfoUpdate($type, $dataId = 0)
    {
	    // Do not apply async, defer attributes on this page, if site-wide is enabled (make an exception)
	    // This will be the same as not having defer or async added in the first place for this page
		$this->updateAssetAttributesLoadExceptions($type, $dataId);

	    // Apply / Remove async, defer everywhere (site-wide)
	    do_action('wpacu_pro_asset_global_info_update');

	    // Apply / Remove async, defer (on this page)
	    $this->doAttributesUpdate($type, $dataId);
    }

	/**
	 *
	 */
	public function assetGlobalInfoUpdate()
    {
	    $this->removeEverywhereScriptsAttributes();

	    $asyncPost = ! empty($_POST['wpacu_async']);
	    $deferPost = ! empty($_POST['wpacu_defer']);

	    if (! $asyncPost && ! $deferPost) {
		    return;
	    }

	    $optionToUpdate = WPACU_PLUGIN_ID . '_global_data';
	    $globalKey = 'everywhere';

	    $existingListEmpty = array('scripts' => array($globalKey => array()));
	    $existingListJson = get_option($optionToUpdate);

	    $existingListData = Main::instance()->existingList($existingListJson, $existingListEmpty);
	    $existingList = $existingListData['list'];

	    foreach (array('async', 'defer') as $attrType) {
	    	$attrIndex = 'wpacu_'.$attrType;

		    if (! isset($_POST[$attrIndex])) {
			    continue;
		    }

		    foreach ($_POST[$attrIndex] as $asset => $value) {
			    if ($value === $globalKey) {
				    $existingList['scripts'][$globalKey][$asset]['attributes'][] = $attrType;
			    }
		    }
	    }

	    Misc::addUpdateOption($optionToUpdate, wp_json_encode(Misc::filterList($existingList)));
    }

	/**
	 *
	 */
	public static function updateHandleMediaQueriesLoad()
	{
		$useGlobalPost = false;

		if ( ! empty($_POST[WPACU_FORM_ASSETS_POST_KEY]['styles']) || ! empty($_POST[WPACU_FORM_ASSETS_POST_KEY]['scripts']) ) {
			$mainVarToUse = self::updateHandleMediaQueriesLoadAdapt($_POST[WPACU_FORM_ASSETS_POST_KEY]); // New form fields (starting from v1.1.9.9)
		} elseif (Misc::isValidRequest('post', 'wpacu_media_queries_load')) {
			$useGlobalPost = true;
		} else {
			return;
		}

	    $optionToUpdate = WPACU_PLUGIN_ID . '_global_data';
		$globalKey = 'media_queries_load';

		$existingListEmpty = array('styles' => array($globalKey => array()), 'scripts' => array($globalKey => array()));
		$existingListJson = get_option($optionToUpdate);

		$existingListData = Main::instance()->existingList($existingListJson, $existingListEmpty);
		$existingList = $existingListData['list'];

		if (! $useGlobalPost && isset($mainVarToUse['wpacu_media_queries_load'])) {
			$bucketToUse = $mainVarToUse['wpacu_media_queries_load'];
		} elseif (isset($_POST['wpacu_media_queries_load'])) {
			$bucketToUse = $_POST['wpacu_media_queries_load'];
		}

		if (! isset($bucketToUse['styles']) && ! isset($bucketToUse['scripts'])) {
			return;
		}

		foreach ( array( 'styles', 'scripts' ) as $assetType ) {
			if ( ! empty( $bucketToUse[ $assetType ] ) ) {
				foreach ( $bucketToUse[ $assetType ] as $assetHandle => $assetMediaQueryArray ) {
                    // 'enable' could be:
                    // "1": a specified media query
                    // "2": the media query already assigned to the tag
					$mediaQueryLoadStatus = isset( $assetMediaQueryArray['enable'] ) ? (int)$assetMediaQueryArray['enable'] : false;
					$mediaQueryLoadValue  = isset( $assetMediaQueryArray['value'] )  ? stripslashes( trim( $assetMediaQueryArray['value'] ) ) : '';

					// If the status is "1" and the media query was not specified, delete the rule (leave it as default, to load for all screens)
					if ( $mediaQueryLoadStatus === 1 && $mediaQueryLoadValue === '' ) {
						unset( $existingList[ $assetType ][ $globalKey ][ $assetHandle ] );
                        continue;
					}

                    if ( $mediaQueryLoadStatus === 2 ) {
                        $mediaQueryLoadValue = 'current';
                    }

                    if ( $mediaQueryLoadStatus === 1 ) {
                        $mediaQueryLoadValue = trim(str_ireplace('@media', '', $mediaQueryLoadValue));

                        // Auto fix in case the user forgot to use the parenthesis
                        // Does not start with "(" and ")"
                        if ( $mediaQueryLoadValue[0] !== '(' && substr($mediaQueryLoadValue, -1) !== ')' ) {
                            // Check if it starts with "min-" or "max-"
                            if ((strpos($mediaQueryLoadValue, 'min-') === 0 || strpos($mediaQueryLoadValue, 'max-') === 0)
                                && (substr($mediaQueryLoadValue, -2) === 'px' || substr($mediaQueryLoadValue, -2) === 'em')) {
                                $mediaQueryLoadValue = '(' . $mediaQueryLoadValue . ')';
                            }
                        }
                    }

                    $existingList[ $assetType ][ $globalKey ][ $assetHandle ] = array(
                        'enable' => $mediaQueryLoadStatus,
                        'value'  => $mediaQueryLoadValue
                    );

				}
			}
		}

        Misc::addUpdateOption($optionToUpdate, wp_json_encode(Misc::filterList($existingList)));
	}

	/**
	 * @param $mainFormArray
	 *
	 * @return array
	 */
	public static function updateHandleMediaQueriesLoadAdapt($mainFormArray)
    {
	    $wpacuMediaQueryList = array();

	    foreach (array('styles', 'scripts') as $assetKey) {
		    if ( ! empty($mainFormArray[$assetKey]) ) {
			    foreach ($mainFormArray[$assetKey] as $assetHandle => $assetData) {
				    $wpacuMediaQueryList['wpacu_media_queries_load'][$assetKey][$assetHandle] = array('enable' => ''); // default

				    if (isset($assetData['media_query_load']['enable']) && $assetData['media_query_load']['enable']) {
                        $enableStatus = (int)$assetData['media_query_load']['enable'];

                        if ($enableStatus === 1) {
                            $wpacuMediaQueryList['wpacu_media_queries_load'][$assetKey][$assetHandle] = array(
                                'enable' => 1,
                                'value'  => $assetData['media_query_load']['value']
                            );
                        } elseif ($enableStatus === 2) {
                            $wpacuMediaQueryList['wpacu_media_queries_load'][$assetKey][$assetHandle] = array(
                                'enable' => 2,
                                'value'  => 'current' // for reference
                            );
                        }
				    }
			    }
		    }
	    }

	    return $wpacuMediaQueryList;
    }

	/**
	 * This is only triggered within the Dashboard on pages such as the following below:
	 * Edit Category | Edit Tag | Edit Custom Taxonomy (e.g. `product_cat` from WooCommerce)
	 */
	public function dashboardUpdateForEditTaxonomyPage()
    {
		if ( ! isset($_POST['tag_ID'], $_POST['taxonomy']) ) {
			return;
		}

		global $wpdb;

	    $this->updateGlobalUnloads();

	    $wpacuNoLoadAssets = Misc::getVar('post', WPACU_PLUGIN_ID, array());

		$termId = (int)$_POST['tag_ID'];

        if ( $termId === 0 ) {
            return;
        }

		$term = term_exists($termId) ? get_term($termId) : false;

        if ( ! $term ) {
            return;
        }

        $allTaxIds = apply_filters('wpacu_get_all_assoc_tax_ids', $termId);

	    $noUpdate = false;

	    // Is the list empty?
	    if ( empty( $wpacuNoLoadAssets ) ) {
            foreach ( $allTaxIds as $taxId ) {
                // Remove any row with no results
                $wpdb->delete(
                    $wpdb->termmeta,
                    array('term_id' => $taxId, 'meta_key' => '_' . WPACU_PLUGIN_ID . '_no_load')
                );
            }

		    $noUpdate = true;
	    }

	    if ( ! $noUpdate ) {
		    $jsonNoAssetsLoadList = wp_json_encode($wpacuNoLoadAssets);

            foreach ( $allTaxIds as $taxId ) {
                if ( ! add_term_meta($taxId, '_' . WPACU_PLUGIN_ID . '_no_load', $jsonNoAssetsLoadList, true) ) {
                    update_term_meta($taxId, '_' . WPACU_PLUGIN_ID . '_no_load', $jsonNoAssetsLoadList);
                }
            }
	    }

	    // If globally disabled, make an exception to load for submitted assets
	    if (class_exists('\\WpAssetCleanUp\\Update')) {
		    $updateWpacu = new Update();
		    $updateWpacu->saveLoadExceptions( 'for_pro' );
	    }

        $this->saveToBulkUnloads( 'taxonomy', $term );

	    $this->removeBulkUnloads('taxonomy');

	    // Was the "Assets' List Layout" changed?
	    Update::updateSettingsChangedOutsideTheMainArea();

	    // async, defer etc.
        foreach ($allTaxIds as $taxId) {
            $this->assetInfoUpdate('taxonomy', $taxId);
        }

	    // [wpacu_pro]
        // Unload it on pages that matches RegEx
        do_action('wpacu_pro_update_regex_rules', 'unload');

        // Load it on pages that matches RegEx (global rule, different from "load it on this page")
        do_action('wpacu_pro_update_regex_rules', 'load_exception');

        // Any positions changed?
        self::updateAssetsPositions();
        self::updateHandleMediaQueriesLoad();
	    // [/wpacu_pro]

        // Any preloads
	    Update::updatePreloads();

	    // Any handle notes?
	    Update::updateHandleNotes();

	    // Any always load it if the user is logged in?
	    Update::saveGlobalLoadExceptions();

	    // Any ignore deps
	    Update::updateIgnoreChild();

        // [wpacu_pro]
            // When any unload rule for a hardcoded asset is set in the form, the contents of the tag will be stored in the database
            // To be later viewed in places such as "Overview"
            // This one NEEDS to trigger AFTER all other updates have been made
            self::storeHardcodedAssetsInfo();
        // [wpacu_pro]

	    Update::clearTransients();

	    // Note: the cache is cleared via AJAX after the taxonomy is updated
    }

	/**
	 *
	 */
	public function restoreAssetsPositions()
	{
		// It has to be on the right page: "wpacu_bulk_menu_tab=assets_positions"
		// "Updated CSS/JS positions" tab
		if ( ! (isset($_REQUEST['wpacu_bulk_menu_tab']) && $_REQUEST['wpacu_bulk_menu_tab'] === 'assets_positions') ) {
			return;
		}

		$chosenStyles = (isset($_POST['wpacu_styles_new_positions'])  && ! empty($_POST['wpacu_styles_new_positions']));
		$chosenScripts = (isset($_POST['wpacu_scripts_new_positions']) && ! empty($_POST['wpacu_scripts_new_positions']));

		if (! ($chosenStyles || $chosenScripts)) {
			return;
		}

		check_admin_referer('wpacu_restore_assets_positions', 'wpacu_restore_assets_positions_nonce');

		$optionToUpdate = WPACU_PLUGIN_ID . '_global_data';
		$globalKey = 'positions'; // HEAD or BODY

		$existingListEmpty = array('styles' => array($globalKey => array()), 'scripts' => array($globalKey => array()));
		$existingListJson = get_option($optionToUpdate);

		$existingListData = Main::instance()->existingList($existingListJson, $existingListEmpty);
		$existingList = $existingListData['list'];

		if ($chosenStyles) {
			foreach ($_POST['wpacu_styles_new_positions'] as $styleHandle => $action) {
				if ($action === 'remove') {
					unset($existingList['styles']['positions'][$styleHandle]);
				}
			}
		}

		if ($chosenScripts) {
			foreach ($_POST['wpacu_scripts_new_positions'] as $scriptHandle => $action) {
				if ($action === 'remove') {
					unset($existingList['scripts']['positions'][$scriptHandle]);
				}
			}
		}

		Misc::addUpdateOption($optionToUpdate, wp_json_encode(Misc::filterList($existingList)));

		add_action('wpacu_admin_notices', function() {
			?>
			<div class="updated notice wpacu-notice wpacu-reset-notice is-dismissible">
				<p><span class="dashicons dashicons-yes"></span> The chosen CSS/JS were restored to their original location, thus they are not showing anymore in the list below.</p>
			</div>
			<?php
		});
	}

	/**
	 * This method triggers on pages such as: taxonomy, 404, search, date archives etc.
	 */
	public function updateGlobalUnloads()
    {
	    // Initialize "Update" class from the standard (free) plugin
	    if (class_exists('\\WpAssetCleanUp\\Update')) {
		    $updateWpacu = new Update();
		    $updateWpacu->updateEverywhereUnloads();
	    }
    }

	/**
	 * @param $forBulkType
	 * @param $object
	 */
	public function saveToBulkUnloads($forBulkType, $object = null)
    {
	    $postStyles  = Misc::getVar('post', 'wpacu_bulk_unload_styles', array());
	    $postScripts = Misc::getVar('post', 'wpacu_bulk_unload_scripts', array());

	    // Is there any entry already in JSON format?
	    $existingListJson = get_option( WPACU_PLUGIN_ID . '_bulk_unload');

	    // Default list as array
	    if ($forBulkType === 'taxonomy' && isset($object->taxonomy)) {
		    $existingListEmpty = array(
			    'styles'  => array( $forBulkType => array( $object->taxonomy => array() ) ),
			    'scripts' => array( $forBulkType => array( $object->taxonomy => array() ) )
		    );
	    } elseif ($forBulkType === 'author') {
		    $existingListEmpty = array(
			    'styles'  => array( $forBulkType => array( 'all' => array() ) ),
			    'scripts' => array( $forBulkType => array( 'all' => array() ) )
		    );
	    }

	    $existingListData = Main::instance()->existingList($existingListJson, $existingListEmpty);
	    $existingList = $existingListData['list'];

	    // Append to the list anything from the POST (if any)
	    // Make sure all entries are unique (no handle duplicates)
	    $list = array();

	    foreach (array('styles', 'scripts') as $assetType) {
		    if ($assetType === 'styles') {
			    $list = $postStyles;
		    } elseif ($assetType === 'scripts') {
			    $list = $postScripts;
		    }

		    if (empty($list)) {
			    continue;
		    }

		    foreach ($list as $bulkType => $values) {
			    if (empty($values)) {
				    continue;
			    }

				if ($bulkType === 'taxonomy') {
					foreach ($values as $taxonomySlug => $handles ) {
						foreach (array_unique($handles) as $handle ) {
							$existingList[$assetType][$bulkType][$taxonomySlug][] = $handle;
						}

						$existingList[$assetType][$bulkType][$taxonomySlug] = array_unique( $existingList[$assetType][$bulkType][$taxonomySlug]);
					}
				} elseif ($bulkType === 'author' && isset($list['author']['all']) && ! empty($list['author']['all'])) {
			    	foreach ($list['author']['all'] as $handle) {
					    $existingList[$assetType][$bulkType]['all'][] = $handle;
				    }

					$existingList[$assetType][$bulkType]['all'] = array_unique($existingList[$assetType][$bulkType]['all']);
				}
		    }
	    }

	    Misc::addUpdateOption( WPACU_PLUGIN_ID . '_bulk_unload', wp_json_encode(Misc::filterList($existingList)));
    }

	/**
	 * Applies for taxonomy, author page
	 * Triggers when "Remove bulk rule" radio button is selected
	 *
	 * @param string $bulkType
	 *
	 * @return void
	 */
	public function removeBulkUnloads($bulkType = '')
	{
		if (empty($_POST)) {
			return;
		}

		$stylesList  = Misc::getVar('post', 'wpacu_options_' . $bulkType . '_styles',  array());
		$scriptsList = Misc::getVar('post', 'wpacu_options_' . $bulkType . '_scripts', array());

		$removeStylesList = $removeScriptsList = array();

		if (! empty($stylesList)) {
			foreach ($stylesList as $handle => $value) {
				if ($value === 'remove') {
					$removeStylesList[] = $handle;
				}
			}
		}

		if (! empty($scriptsList)) {
			foreach ($scriptsList as $handle => $value) {
				if ($value === 'remove') {
					$removeScriptsList[] = $handle;
				}
			}
		}

		$optionToUpdate = WPACU_PLUGIN_ID . '_bulk_unload';

		$existingListJson = get_option($optionToUpdate);

		if (! $existingListJson) {
			return;
		}

		$existingList = json_decode($existingListJson, true);

		if (Misc::jsonLastError() === JSON_ERROR_NONE) {
			$list = array();

			foreach (array('styles', 'scripts') as $assetType) {
				if ($assetType === 'styles') {
					$list = $removeStylesList;
				} elseif ($assetType === 'scripts') {
					$list = $removeScriptsList;
				}

				if (empty($list)) {
					continue;
				}

				if (! isset($existingList[ $assetType ][ $bulkType ])) {
					return;
				}

				// $bulkTypeKey could be:
				// If Taxonomy: 'category', 'product_cat', 'post_tag' etc.
				// If Author: 'all'
				foreach ( $existingList[ $assetType ][ $bulkType ] as $bulkTypeKey => $values ) {
					foreach ($values as $handleKey => $handle) {
						if ( in_array( $handle, $list ) ) {
							unset( $existingList[ $assetType ][ $bulkType ] [$bulkTypeKey] [ $handleKey ] );
						}
					}
				}
			}

			Misc::addUpdateOption($optionToUpdate, wp_json_encode(Misc::filterList($existingList)));
		}
	}

	/**
	 *
	 */
	public function removeEverywhereScriptsAttributesViaBulkChanges()
	{
		// It has to be on the right page: "wpacu_bulk_menu_tab=assets_positions"
		// "Updated CSS/JS positions" tab
		if ( ! (isset($_REQUEST['wpacu_bulk_menu_tab']) && $_REQUEST['wpacu_bulk_menu_tab'] === 'script_attrs') ) {
			return;
		}

	    if (! Misc::getVar('post', 'wpacu_remove_global_attrs_nonce')) {
	        return;
	    }

		$asyncAttrs = (isset($_POST['wpacu_options_global_attribute_scripts']['async']) && ! empty($_POST['wpacu_options_global_attribute_scripts']['async']));
		$deferAttrs = (isset($_POST['wpacu_options_global_attribute_scripts']['defer']) && ! empty($_POST['wpacu_options_global_attribute_scripts']['defer']));

		if (! ($asyncAttrs || $deferAttrs)) {
			return;
		}

		check_admin_referer('wpacu_remove_global_attrs', 'wpacu_remove_global_attrs_nonce');

	    $this->removeEverywhereScriptsAttributes();

		add_action('wpacu_admin_notices', function() {
            ?>
            <div class="updated notice wpacu-notice wpacu-reset-notice is-dismissible">
                <p><span class="dashicons dashicons-yes"></span> The async/defer attributes were removed on all pages for the chosen script tags.</p>
            </div>
			<?php
		});
	}

	/**
	 * @return bool
	 */
	public function removeEverywhereScriptsAttributes()
	{
		$scriptsToUpdate = Misc::getVar('post', 'wpacu_options_global_attribute_scripts', array());

		// Nothing selected for removal by the admin
		if (empty($scriptsToUpdate)) {
			return false;
		}

		$optionToUpdate = WPACU_PLUGIN_ID . '_global_data';
		$globalKey = 'everywhere';

		$isUpdated = false;

		$existingListJson = get_option($optionToUpdate);

		// Nothing to update from the database?
		if (! $existingListJson) {
			return false;
		}

		$existingList = json_decode($existingListJson, true);

		// JSON has to be valid
		if (Misc::jsonLastError() !== JSON_ERROR_NONE) {
			return false;
		}

		foreach ($scriptsToUpdate as $attrType => $values) {
			foreach ($values as $handle => $action) {
				$existingListAttrs = array();

				if (isset($existingList['scripts'][$globalKey][$handle]['attributes'])) {
					$existingListAttrs = $existingList['scripts'][$globalKey][$handle]['attributes'];
				}

				if ($action === 'remove' && in_array($attrType, $existingListAttrs)) {
					$targetKey = array_search($attrType, $existingListAttrs);
					unset($existingList['scripts'][$globalKey][$handle]['attributes'][$targetKey]);

					$isUpdated = true;
				}
			}
		}

		if ($isUpdated) {
			Misc::addUpdateOption($optionToUpdate, wp_json_encode(Misc::filterList($existingList)));
		}

		return $isUpdated;
	}

	/**
	 * @param string $type
	 * @param int $dataId
	 */
	public function doAttributesUpdate($type, $dataId = 0)
	{
		$asyncPost = ! empty($_POST['wpacu_async']);
		$deferPost = ! empty($_POST['wpacu_defer']);

		// [START] Remove existing entries per page (if any)
		$attrsData = $this->getAttrsData($type, $dataId);

		$existingListJson = $attrsData['existing_list_json'];
		$pageType = $attrsData['page_type'];

		if (! $asyncPost && ! $deferPost && ! $existingListJson && ! $pageType) {
			return;
		}

		$existingListData = Main::instance()->existingList($existingListJson, array());
		$existingList = $existingListData['list'];

		// Has to be valid JSON format
		if ($existingListData['not_empty'] && isset($existingList['scripts'])) {
			$targetList = array();

			if ($pageType === 'single') {
				$targetList = ! empty($existingList['scripts']) ? $existingList['scripts'] : array();
			} elseif ($pageType === 'bulk') {
				$targetList = isset($existingList['scripts'][$type]) ? $existingList['scripts'][$type] : array();
			}

			if (! empty($targetList)) {
				// Clear any existing attributes (in case checkboxes were unchecked for "on this page")
				foreach ($targetList as $handle => $values) {
					if (isset($existingList['scripts'][$type][$handle]['attributes']) && $pageType === 'bulk') {
						unset($existingList['scripts'][$type][$handle]['attributes']);
					}

					if (isset($existingList['scripts'][$handle]['attributes']) && $pageType === 'single') {
						unset($existingList['scripts'][$handle]['attributes']);
					}
				}

				// If new checkboxes were checked or existing ones remained checked, they will be added / kept
				// by the code below that generates the new list of attributes
			}
		}
		// [END] Remove existing entries per page (if any)

		// [START] Generate the new list of attributes
		$newList = ! empty($existingList) ? $existingList : array();

		if ($pageType === 'single') {
			foreach (array('async', 'defer') as $attrType) {
				$attrIndex = 'wpacu_'.$attrType;

				if (! isset($_POST[$attrIndex])) {
					continue;
				}

				foreach ($_POST[$attrIndex] as $asset => $value) {
					if ($value === 'on_this_page') {
						$newList['scripts'][$asset]['attributes'][] = $attrType;
					}
				}
			}
		} elseif ($pageType === 'bulk') {
			foreach (array('async', 'defer') as $attrType) {
				$attrIndex = 'wpacu_'.$attrType;

				if (! isset($_POST[$attrIndex])) {
					continue;
				}

				foreach ($_POST[$attrIndex] as $asset => $value) {
					if ($value === 'on_this_page') {
						$newList['scripts'][$type][$asset]['attributes'][] = $attrType;
					}
				}
			}
		}
		// [END] Generate the new list of attributes

		$this->updateAttrsData($existingListJson, wp_json_encode(Misc::filterList($newList)), $type, $dataId);
	}

	/**
	 * @param $type
	 * @param int $dataId
	 */
	public function updateAssetAttributesLoadExceptions($type, $dataId = 0)
	{
		$existingList = array();

		$attrsData = $this->getAttrsData($type, $dataId);

		$existingListJson = $attrsData['existing_list_json'];
		$pageType = $attrsData['page_type'];

		$listKey = 'scripts_attributes_no_load';

		// Clear existing data first (before applying the new one)
		if ($existingListJson) {
			$existingList = json_decode( $existingListJson, true );

			// Has to be valid JSON format
			if (isset($existingList[$listKey]) && Misc::jsonLastError() === JSON_ERROR_NONE) {
				if (! empty($existingList[$listKey])) {
					$targetList = array();

					if ($pageType === 'single') {
						$targetList = $existingList[$listKey];
					} elseif ($pageType === 'bulk') {
						$targetList = isset($existingList[$listKey][$type]) ? $existingList[$listKey][$type] : array();
					}

					if (! empty($targetList)) {
						foreach ( $targetList as $handle => $values ) {
							if ( isset( $existingList[ $listKey ][ $type ][ $handle ] ) && $pageType === 'bulk' ) {
								unset( $existingList[ $listKey ][ $type ][ $handle ] );
							}

							if ( isset( $existingList[ $listKey ][ $handle ] ) && $pageType === 'single' ) {
								unset( $existingList[ $listKey ][ $handle ] );
							}
						}
					}
				}
			}
		}

		// [START] Generate the new list of attributes "no load" exceptions
		$newList = ! empty($existingList) ? $existingList : array();

		foreach (array('async', 'defer') as $attrType) {
			if (isset($_POST['wpacu_'.$attrType]['no_load']) && ! empty($_POST['wpacu_'.$attrType]['no_load'])) {
				foreach ($_POST['wpacu_'.$attrType]['no_load'] as $handle) {
					if ($pageType === 'single') {
						$newList[$listKey][$handle][] = $attrType;
					}

					if ($pageType === 'bulk') {
						$newList[$listKey][$type][$handle][] = $attrType;
					}
				}
			}
		}
		// [END] Generate the new list of attributes "no load" exceptions

		$this->updateAttrsData($existingListJson, wp_json_encode(Misc::filterList($newList)), $type, $dataId);
	}

	/**
	 * @param $type
	 * @param $dataId
	 *
	 * @return array
	 */
	public function getAttrsData($type, $dataId)
	{
		$existingListJson = $pageType = false;

		// HOME PAGE (e.g. latest posts, not a single page assigned as front page)
		if ($type === 'homepage' && $dataId < 1) {
			$pageType = 'single';
			$existingListJson = get_option( WPACU_PLUGIN_ID . '_front_page_data');

		// POST, PAGE, PAGE set as front-page, CUSTOM POST TYPE
		} elseif ($type === 'post' && $dataId > 0) {
			$pageType = 'single';
			$existingListJson = get_post_meta($dataId, '_' . WPACU_PLUGIN_ID . '_data', true);
		}

		// CATEGORIES, TAGS etc.
		elseif ($type === 'taxonomy' && $dataId > 0) {
			$pageType = 'single';
			$existingListJson = get_term_meta($dataId, '_' . WPACU_PLUGIN_ID . '_data', true);
		}

		// AUTHOR
		elseif ($type === 'author' && $dataId > 0) {
			$pageType = 'single';
			$existingListJson = get_user_meta($dataId, '_' . WPACU_PLUGIN_ID . '_data', true);

		// BULK PAGES
		} elseif ((in_array($type, array('404', 'search', 'date')) || (strpos($type, 'custom_post_type_archive_') !== false)) && $dataId < 1) {
			$pageType = 'bulk';
			$existingListJson = get_option( WPACU_PLUGIN_ID . '_global_data');
		}

		return array(
			'page_type' => $pageType,
			'existing_list_json' => $existingListJson
		);
	}

	/**
	 * @param $existingListJson
	 * @param $jsonNewList
	 * @param $type
	 * @param $dataId
	 */
	public function updateAttrsData($existingListJson, $jsonNewList, $type, $dataId)
	{
		// Note: As in some cases (at least with older version of WordPress), update_option() didn't 'behave' exactly like add_option() (when it should have)
		// both add_option() and update_option() would be used for maximum compatibility

		// HOME PAGE
		if ($type === 'homepage' && $dataId < 1) {
			Misc::addUpdateOption(WPACU_PLUGIN_ID . '_front_page_data', $jsonNewList);
		}

		// POST, PAGE, CUSTOM POST TYPE, HOME PAGE (static page selected as front page)
		elseif ($type === 'post' && $dataId > 0) {
			if (! $existingListJson) {
				add_post_meta($dataId, '_' . WPACU_PLUGIN_ID . '_data', $jsonNewList, true);
			} else {
				update_post_meta($dataId, '_' . WPACU_PLUGIN_ID . '_data', $jsonNewList);
			}
		}

		// TAXONOMY
		elseif ($type === 'taxonomy') {
			if (! $existingListJson) {
				add_term_meta($dataId, '_' . WPACU_PLUGIN_ID . '_data', $jsonNewList, true);
			} else {
				update_term_meta($dataId, '_' . WPACU_PLUGIN_ID . '_data', $jsonNewList);
			}
		}

		// AUTHOR
		elseif ($type === 'author') {
			if (! $existingListJson) {
				add_user_meta($dataId, '_' . WPACU_PLUGIN_ID . '_data', $jsonNewList, true);
			} else {
				update_user_meta($dataId, '_' . WPACU_PLUGIN_ID . '_data', $jsonNewList);
			}
		}

		// 404, SEARCH, DATE
		// These ones would trigger only in the front-end view as there is no Dashboard view available for them
		elseif ((in_array($type, array('404', 'search', 'date')) || (strpos($type, 'custom_post_type_archive_') !== false)) && $dataId < 1) {
			Misc::addUpdateOption(WPACU_PLUGIN_ID . '_global_data', $jsonNewList);
		}
	}

	/**
	 * Move scripts from HEAD to BODY or vice-versa
	 */
	public static function updateAssetsPositions()
	{
		// No $mainVarToUse passed? Then it's a $_POST
		// Check if $_POST is empty via Misc::isValidRequest()
		$useGlobalPost = false;

        if ( (isset($_POST[WPACU_FORM_ASSETS_POST_KEY]['styles']) && ! empty($_POST[WPACU_FORM_ASSETS_POST_KEY]['styles']))
             || (isset($_POST[WPACU_FORM_ASSETS_POST_KEY]['scripts']) && ! empty($_POST[WPACU_FORM_ASSETS_POST_KEY]['scripts'])) ) {
            $mainVarToUse = self::updateAssetsPositionsAdapt($_POST[WPACU_FORM_ASSETS_POST_KEY]); // New form fields (starting from v1.1.9.9)
        } elseif (Misc::isValidRequest('post', 'wpacu_styles_positions')
              || Misc::isValidRequest('post', 'wpacu_scripts_positions')) {
	        $useGlobalPost = true;
        } else {
            return;
        }

		$optionToUpdate = WPACU_PLUGIN_ID . '_global_data';
		$globalKey = 'positions'; // HEAD or BODY

		$existingListEmpty = array('styles' => array($globalKey => array()), 'scripts' => array($globalKey => array()));
		$existingListJson = get_option($optionToUpdate);

		$existingListData = Main::instance()->existingList($existingListJson, $existingListEmpty);
		$existingList = $existingListData['list'];

		foreach (array('styles', 'scripts') as $assetKey) {
			$postKey = 'wpacu_'.$assetKey.'_positions';

			if (! $useGlobalPost && isset($mainVarToUse[$postKey])) {
				$bucketToUse = $mainVarToUse[ $postKey ];
			} else if (isset($_POST[$postKey])) {
			    $bucketToUse = $_POST[$postKey];
			}

			if (isset($bucketToUse) && ! empty($bucketToUse)) {
				foreach ($bucketToUse as $handle => $position) {
					if (! $position || $position === 'initial') {
						if (isset($existingList[$assetKey][$globalKey][$handle])) {
							unset( $existingList[ $assetKey ][ $globalKey ][ $handle ] );
						}
					} elseif (in_array($position, array('head', 'body'))) {
						$existingList[$assetKey][$globalKey][$handle] = $position;
					}
				}
			}
			}

		update_option($optionToUpdate, wp_json_encode(Misc::filterList($existingList)));
	}

	/**
	 * @param $mainFormArray
	 *
	 * @return array
	 */
	public static function updateAssetsPositionsAdapt($mainFormArray)
    {
	    $wpacuPositionsList = array();

	    foreach (array('styles', 'scripts') as $assetKey) {
		    if (isset($mainFormArray[$assetKey]) && ! empty($mainFormArray[$assetKey])) {
		        $postKey = 'wpacu_'.$assetKey.'_positions';
			    foreach ($mainFormArray[$assetKey] as $assetHandle => $assetData) {
				    $wpacuPositionsList[$postKey][$assetHandle] = ''; // default

				    if (isset($assetData['position']) && $assetData['position']) {
					    $wpacuPositionsList[$postKey][$assetHandle] = $assetData['position'];
				    }
			    }
		    }
	    }

	    return $wpacuPositionsList;
    }

	/**
	 * @param $currentPostType
	 */
	public static function updateTaxonomyValuesAssocToPostType($currentPostType)
    {
	    $allFormStyles  = isset($_POST[WPACU_FORM_ASSETS_POST_KEY]['styles']) && is_array($_POST[WPACU_FORM_ASSETS_POST_KEY]['styles'])
            ? array_keys($_POST[WPACU_FORM_ASSETS_POST_KEY]['styles']) : array();
	    $allFormScripts  = isset($_POST[WPACU_FORM_ASSETS_POST_KEY]['scripts']) && is_array($_POST[WPACU_FORM_ASSETS_POST_KEY]['scripts'])
		    ? array_keys($_POST[WPACU_FORM_ASSETS_POST_KEY]['scripts']) : array();

        // Is there any entry already in JSON format?
	    $existingListJson = get_option( WPACU_PLUGIN_ID . '_bulk_unload');

	    // Default list as array
	    $existingListEmpty = array(
		    'styles'  => array('post_type_via_tax' => array($currentPostType => array())),
		    'scripts' => array('post_type_via_tax' => array($currentPostType => array()))
	    );

	    $existingListData = Main::instance()->existingList($existingListJson, $existingListEmpty);
	    $existingList = $existingListData['list'];

	    // Append to the list anything from the POST (if any)
	    // Make sure all entries are unique (no handle duplicates)
	    $list = array();

	    foreach (array('styles', 'scripts') as $assetType) {
		    if ($assetType === 'styles') {
			    $list = $allFormStyles;
		    } elseif ($assetType === 'scripts') {
			    $list = $allFormScripts;
		    }

		    if (empty($list)) {
			    continue;
		    }

		    foreach ($list as $handle) {
			    $enable = isset($_POST[WPACU_FORM_ASSETS_POST_KEY][$assetType]['unload_post_type_via_tax'][$handle]['enable']) ? $_POST[WPACU_FORM_ASSETS_POST_KEY][$assetType]['unload_post_type_via_tax'][$handle]['enable'] : false;
			    $taxTermsIds = isset($_POST[WPACU_FORM_ASSETS_POST_KEY][$assetType]['unload_post_type_via_tax'][$handle]['values']) ? $_POST[WPACU_FORM_ASSETS_POST_KEY][$assetType]['unload_post_type_via_tax'][$handle]['values'] : array();

			    if ( $enable ) {
                    // Marked as enabled without any terms set (leave it as it was, unchecked)
                    if (empty($taxTermsIds)) {
	                    unset( $existingList[ $assetType ]['post_type_via_tax'][ $currentPostType ][ $handle ] );
                    } else {
	                    // Enabled with terms set
	                    $existingList[ $assetType ]['post_type_via_tax'][ $currentPostType ][ $handle ] = array('enable' => $enable, 'values' => $taxTermsIds);
                    }
                } else if ( ! empty($taxTermsIds) ) {
                    // Preserve any terms set there
                    $existingList[ $assetType ]['post_type_via_tax'][ $currentPostType ][ $handle ] = array('values' => $taxTermsIds);
                } else {
                    // Not enabled and without any terms? Clear it
                    unset( $existingList[ $assetType ]['post_type_via_tax'][ $currentPostType ][ $handle ] );
                }
		    }
	    }

	    Misc::addUpdateOption( WPACU_PLUGIN_ID . '_bulk_unload', wp_json_encode(Misc::filterList($existingList)));
    }

	/**
	 * @param $currentPostType
	 */
	public static function updateTaxonomyValuesAssocToPostTypeLoadExceptions($currentPostType)
	{
		$allFormStyles  = isset($_POST[WPACU_FORM_ASSETS_POST_KEY]['styles']) && is_array($_POST[WPACU_FORM_ASSETS_POST_KEY]['styles'])
			? array_keys($_POST[WPACU_FORM_ASSETS_POST_KEY]['styles']) : array();
		$allFormScripts  = isset($_POST[WPACU_FORM_ASSETS_POST_KEY]['scripts']) && is_array($_POST[WPACU_FORM_ASSETS_POST_KEY]['scripts'])
			? array_keys($_POST[WPACU_FORM_ASSETS_POST_KEY]['scripts']) : array();

		// Is there any entry already in JSON format?
		$existingListJson = get_option( WPACU_PLUGIN_ID . '_post_type_via_tax_load_exceptions' );

		// Default list as array
		$existingListEmpty = array( $currentPostType => array('styles' => array(), 'scripts' => array()) );

		$existingListData = Main::instance()->existingList($existingListJson, $existingListEmpty);
		$existingList = $existingListData['list'];

		// Append to the list anything from the POST (if any)
		// Make sure all entries are unique (no handle duplicates)
		$list = array();

		foreach (array('styles', 'scripts') as $assetType) {
			if ($assetType === 'styles') {
				$list = $allFormStyles;
			} elseif ($assetType === 'scripts') {
				$list = $allFormScripts;
			}

			if (empty($list)) {
				continue;
			}

			foreach ($list as $handle) {
				$enable = isset($_POST[WPACU_FORM_ASSETS_POST_KEY][$assetType]['load_it_post_type_via_tax'][$handle]['enable'])
                    ? $_POST[WPACU_FORM_ASSETS_POST_KEY][$assetType]['load_it_post_type_via_tax'][$handle]['enable'] : false;
				$taxTermsIds = isset($_POST[WPACU_FORM_ASSETS_POST_KEY][$assetType]['load_it_post_type_via_tax'][$handle]['values'])
                    ? $_POST[WPACU_FORM_ASSETS_POST_KEY][$assetType]['load_it_post_type_via_tax'][$handle]['values'] : array();

				if ( $enable ) {
					// Marked as enabled without any terms set (leave it as it was, unchecked)
					if (empty($taxTermsIds)) {
						unset( $existingList[ $currentPostType ][ $assetType ][ $handle ] );
					} else {
						// Enabled with terms set
						$existingList[ $currentPostType ][ $assetType ][ $handle ] = array('enable' => $enable, 'values' => $taxTermsIds);
					}
				} else if ( ! empty($taxTermsIds) ) {
					// Preserve any terms set there
					$existingList[ $currentPostType ][ $assetType ][ $handle ] = array('values' => $taxTermsIds);
				} else {
					// Not enabled and without any terms? Clear it
					unset( $existingList[ $currentPostType ][ $assetType ][ $handle ] );
				}
			}
		}

		Misc::addUpdateOption( WPACU_PLUGIN_ID . '_post_type_via_tax_load_exceptions', wp_json_encode(Misc::filterList($existingList)));
	}

	/**
     * @param $for
     *
     * Case 1: Load it for URLs matching this RegExp
	 * Case 2: Unload it for URLs matching this RegExp
     *
	 * Enable/Disable and update the input value
	 */
	public function updateRegExRules($for)
	{
		// Form Key (taken from the management form)
		// DB Key (how it's saved in the database)
	    if ($for === 'load_exception') {
		    $formKey   = 'wpacu_handle_load_regex';
		    $globalKey = 'load_regex';
        } else {
		    $formKey   = 'wpacu_handle_unload_regex';
		    $globalKey = 'unload_regex';
	    }

		if (! Misc::isValidRequest('post', $formKey)) {
			return;
		}

		if (! isset($_POST[$formKey]['styles']) && ! isset($_POST[$formKey]['scripts'])) {
			return;
		}

		$optionToUpdate = WPACU_PLUGIN_ID . '_global_data';

		$existingListEmpty = array('styles' => array($globalKey => array()), 'scripts' => array($globalKey => array()));
		$existingListJson = get_option($optionToUpdate);

		$existingListData = Main::instance()->existingList($existingListJson, $existingListEmpty);
		$existingList = $existingListData['list'];

		foreach (array('styles', 'scripts') as $assetKey) {
			if ( isset( $_POST[ $formKey ][$assetKey] ) && ! empty( $_POST[ $formKey ][$assetKey] ) ) {
			    foreach ( $_POST[ $formKey ][$assetKey] as $assetHandle => $assetRegExData ) {
					$regExpEnable = isset( $assetRegExData['enable'] ) && $assetRegExData['enable'];
					$regExpValue  = isset( $assetRegExData['value'] ) ? stripslashes( trim($assetRegExData['value']) ) : '';
					$regExpValue  = MiscPro::purifyTextareaRegexValue($regExpValue);

					// If enabled, it needs an input (regular expression / string), otherwise it's useless
					if ( $regExpValue === '' ) {
						unset( $existingList[$assetKey][ $globalKey ][ $assetHandle ] );
					} else {
						$existingList[$assetKey][ $globalKey ][ $assetHandle ] = array(
							'enable' => $regExpEnable,
							'value'  => $regExpValue
						);
					}
				}
			}
		}

		Misc::addUpdateOption($optionToUpdate, wp_json_encode(Misc::filterList($existingList)));
	}

	/**
	 * For "BULK CHANGES" -> "RegEx Unloads" -> "Apply changes" (button)
	 */
	public static function maybeUpdateBulkRegExUnloads()
    {
	    $nonceAction = 'wpacu_bulk_regex_update_unloads';
	    $nonceName = $nonceAction.'_nonce';

	    if (! isset($_POST[$nonceName])) {
		    return;
	    }

	    if (! wp_verify_nonce($_POST[$nonceName], $nonceAction)) {
	        $postUrlAnchor = $_SERVER['REQUEST_URI'].'#wpacu_wrap_assets';
		    wp_die(
			    sprintf(
				    __('The nonce expired or is not correct, thus the request was not processed. %sPlease retry%s.', 'wp-asset-clean-up'),
				    '<a href="'.$postUrlAnchor.'">',
				    '</a>'
			    ),
			    __('Nonce Expired', 'wp-asset-clean-up')
		    );
	    }

	    do_action('wpacu_pro_update_regex_rules', 'unload');

	    add_action('wpacu_admin_notices', static function() {
	        ?>
            <div class="updated notice wpacu-notice is-dismissible">
                <p><span class="dashicons dashicons-yes"></span> The RegEx unload rules have been successfully updated.</p>
            </div>
            <?php
        });
    }

	/**
	 * For "BULK CHANGES" -> "RegEx Load Exceptions" (relevant if any bulk rule is already applied like unload site-wide) -> "Apply changes" (button)
	 */
	public static function maybeUpdateBulkRegExLoadExceptions()
	{
	    $nonceAction = 'wpacu_bulk_regex_update_load_exceptions';
	    $nonceName = $nonceAction.'_nonce';

	    if (! isset($_POST[$nonceName])) {
	        return;
        }

		if (! wp_verify_nonce($_POST[$nonceName], $nonceAction)) {
			$postUrlAnchor = $_SERVER['REQUEST_URI'].'#wpacu_wrap_assets';
			wp_die(
				sprintf(
					__('The nonce expired or is not correct, thus the request was not processed. %sPlease retry%s.', 'wp-asset-clean-up'),
					'<a href="'.$postUrlAnchor.'">',
					'</a>'
				),
				__('Nonce Expired', 'wp-asset-clean-up')
			);
		}

		do_action('wpacu_pro_update_regex_rules', 'load_exception');

		add_action('wpacu_admin_notices', static function() {
			?>
            <div class="updated notice wpacu-notice is-dismissible">
                <p><span class="dashicons dashicons-yes"></span> The RegEx load exception rules have been successfully updated.</p>
            </div>
			<?php
		});
	}

	/**
	 * Triggers in /wp-admin/admin.php?page=wpassetcleanup_plugins_manager (form submit)
	 */
	public function updatePluginRules()
	{
		if (! Misc::getVar('post', 'wpacu_plugins_manager_submit')) {
			return;
		}

		check_admin_referer('wpacu_plugin_manager_update', 'wpacu_plugin_manager_nonce');

		$wpacuSubPage = (isset($_GET['wpacu_sub_page']) && $_GET['wpacu_sub_page']) ? $_GET['wpacu_sub_page'] : 'manage_plugins_front';

		// For assets, it's either 'styles' or 'scripts' |  for plugins it's "plugins" (frontend view) or "plugins_dash" (Dashboard view)
		if ($wpacuSubPage === 'manage_plugins_front') {
			$mainGlobalKey = 'plugins';
		} elseif ($wpacuSubPage === 'manage_plugins_dash') {
			$mainGlobalKey = 'plugins_dash';
		}

		$formKey = 'wpacu_plugins';

		if (! Misc::isValidRequest('post', $formKey)) { // also check if it's NOT empty
			return;
		}

		$optionToUpdate = WPACU_PLUGIN_ID . '_global_data';

		$existingListEmpty = array($mainGlobalKey => array());
		$existingListJson = get_option($optionToUpdate);

		$existingListData = Main::instance()->existingList($existingListJson, $existingListEmpty);
		$existingList = $existingListData['list'];

		$wpacuPluginsPostData = $_POST[ $formKey ];

        $allKeysToCheckForEmptyValue = array(
            'unload_via_regex',
            'load_via_regex',

            'unload_via_post_type',
            'load_via_post_type',

            'unload_via_tax',
            'load_via_tax',

            'unload_via_archive',
            'load_via_archive',

            'unload_logged_in_via_role',
	        'load_logged_in_via_role'
        );

		foreach ( $wpacuPluginsPostData as $pluginPath => $pluginRuleData ) {
			// If there's no status set, it defaults to "Load it"
			$pluginStatus = isset($pluginRuleData['status']) ? $pluginRuleData['status'] : '';

			foreach ($allKeysToCheckForEmptyValue as $keyStatus) {
			    if (in_array($keyStatus, array('unload_via_regex', 'load_via_regex'))) {
				    $hasValueAndEmpty = isset( $pluginRuleData[ $keyStatus ]['value'] ) && $pluginRuleData[ $keyStatus ]['value'] === '';

				    if ( $keyStatus === $pluginStatus && $hasValueAndEmpty ) {
					    unset( $wpacuPluginsPostData[ $pluginPath ] );
				    }

				    if ( $hasValueAndEmpty ) {
					    unset( $wpacuPluginsPostData[ $pluginPath ][ $keyStatus ] );
				    }

				    // Has value (not empty)
				    if ( isset( $pluginRuleData[ $keyStatus ]['value'] ) && $pluginRuleData[ $keyStatus ]['value'] ) {
					    $textareaValue = MiscPro::purifyTextareaRegexValue( $pluginRuleData[ $keyStatus ]['value'] );

					    if ( $textareaValue ) {
						    $wpacuPluginsPostData[ $pluginPath ][ $keyStatus ]['value'] = $textareaValue;
					    } else {
						    unset( $wpacuPluginsPostData[ $pluginPath ][ $keyStatus ] );
					    }
				    }
			    } elseif (in_array(
                    $keyStatus,
                    array(
	                    'unload_via_post_type',
	                    'load_via_post_type',

                        'unload_via_tax',
	                    'load_via_tax',

                        'unload_via_archive',
	                    'load_via_archive',

                        'unload_logged_in_via_role',
                        'load_logged_in_via_role'
                    )
                )) {
                    $isEmptyList = isset( $pluginRuleData[ $keyStatus ]['values'] ) && empty( $pluginRuleData[ $keyStatus ]['values'] );

				    if ( $keyStatus === $pluginStatus && $isEmptyList ) {
					    unset( $wpacuPluginsPostData[ $pluginPath ] );
				    }

				    if ( $isEmptyList ) {
					    unset( $wpacuPluginsPostData[ $pluginPath ][ $keyStatus ] );
				    }

				    // The list has at least one value (not empty)
				    if ( isset( $pluginRuleData[ $keyStatus ]['values'] ) && ! empty( $pluginRuleData[ $keyStatus ]['values'] ) && ( $postTypesList = $pluginRuleData[ $keyStatus ]['values'] ) ) {
				        $wpacuPluginsPostData[ $pluginPath ][ $keyStatus ]['values'] = $postTypesList;
				    }
			    }
			}
		}

		$existingList[$mainGlobalKey] = array_map('stripslashes_deep', $wpacuPluginsPostData);

		// Are there empty values but the option is enabled? Clear it as it's not relevant
        foreach ($existingList[$mainGlobalKey] as $pluginPath => $values) {
            if (isset($values['status']) && ! empty($values['status'])) {
                foreach ($values['status'] as $statusKey => $status) {
                    if (! isset($values[$status]) &&
                        in_array($status, $allKeysToCheckForEmptyValue)) {
                        unset($existingList[$mainGlobalKey][$pluginPath]['status'][$statusKey]);
                    }
	            }
            }

            if (isset($existingList[$mainGlobalKey][$pluginPath]['status']) && empty($existingList[$mainGlobalKey][$pluginPath]['status'])) {
	            unset($existingList[$mainGlobalKey][$pluginPath]['status']);
            }
        }

        Misc::addUpdateOption($optionToUpdate, wp_json_encode(Misc::filterList($existingList)));

		/* Redirect after update */
		set_transient('wpacu_plugins_manager_updated', 1, 30);

		$wpacuQueryString = array(
			'page' => 'wpassetcleanup_plugins_manager',
			'wpacu_sub_page' => $wpacuSubPage,
			'wpacu_time' => time()
		);

		if ( isset($_GET['wpacu_no_dash_plugin_unload']) ) {
		    $wpacuQueryString['wpacu_no_dash_plugin_unload'] = 1;
		}

		wp_redirect(add_query_arg($wpacuQueryString, esc_url(admin_url('admin.php'))));
		exit();
	}

	/**
	 * This function is called via AJAX whenever "+" or "-" is used on an asset's row
	 *
	 * @param $newState
	 * @param $pluginPath
	 * @param $area
	 *
	 * @return array|false
	 */
	public static function updatePluginRowStatus($newState, $pluginPath, $area)
	{
		if ( ! in_array($area, array('front', 'dash')) || ! in_array($newState, array('contracted', 'expanded')) ) {
			return false;
		}

		$optionToUpdate    = WPACU_PLUGIN_ID . '_global_data';
		$globalKey         = 'plugin_row_contracted'; // Contracted or Expanded (default)

		$existingListEmpty = array($globalKey => array('front' => array(), 'dash' => array()));
		$existingListJson  = get_option($optionToUpdate);

		$existingListData  = Main::instance()->existingList($existingListJson, $existingListEmpty);
		$existingList      = $existingListData['list'];

		// The database value should be equal with '1' suggesting it's contracted (no value means it's expanded by default)
		if ( $newState === 'expanded' && isset($existingList[$globalKey][$area][$pluginPath]) ) {
			unset( $existingList[$globalKey][$area][$pluginPath] ); // "expanded" (default)
		} elseif ( $newState === 'contracted' ) {
			$existingList[$globalKey][$area][$pluginPath] = 1; // "contracted"
		}

		Misc::addUpdateOption($optionToUpdate, wp_json_encode(Misc::filterList($existingList)));

		if (isset($existingList[$globalKey][$area]) && empty($existingList[$globalKey][$area])) {
            unset($existingList[$globalKey][$area]);
		}

		return $existingList;
	}

	/**
	 *
	 */
	public function ajaxUpdatePluginRowState()
	{
		if (isset($_POST['wpacu_update_plugin_row_state'])) {
			if ( ! isset( $_POST['action'],
                          $_POST['wpacu_plugin_row_state'],
                          $_POST['wpacu_plugin_path'],
                          $_POST['wpacu_manage_plugins_in_area'] )
			     || ! \WpAssetCleanUp\Menu::userCanManageAssets() ) {
                echo 'Error: Invalid parameters';
				return;
			}

			if ( $_POST['wpacu_update_plugin_row_state'] !== 'yes' ) {
				echo 'Error: Invalid parameter for "wpacu_plugin_row_state"';
				return;
			}

			if ( ! isset($_POST['wpacu_nonce']) ) {
				echo 'Error: The security nonce was not sent for verification. Location: '.__METHOD__;
				return;
			}

			if ( ! wp_verify_nonce($_POST['wpacu_nonce'], 'wpacu_update_plugin_row_state_nonce') ) {
				echo 'Error: The security check has failed. Location: '.__METHOD__;
				return;
			}

			$pluginRowState = $_POST['wpacu_plugin_row_state']; // 'contracted' or 'expanded' (will get validated)
            $pluginPath     = $_POST['wpacu_plugin_path'];
			$pluginArea     = $_POST['wpacu_manage_plugins_in_area']; // 'front' or 'dash' (will get validated)

			$newContractedList = self::updatePluginRowStatus($pluginRowState, $pluginPath, $pluginArea);

			echo '<pre>' . print_r($newContractedList, true);
		}

		exit();
	}

	/**
	 * Update state for all assets within a plugin
	 *
	 * @return void
	 */
	public function ajaxUpdatePluginsRowStateInArea()
	{
		if (isset($_POST['wpacu_area_update_plugins_row_state'])) {
			if ( ! isset( $_POST['action'],
                          $_POST['wpacu_area_plugins_row_state'],
                          $_POST['wpacu_area_plugins'],
                          $_POST['wpacu_nonce'] )
			     || ! \WpAssetCleanUp\Menu::userCanManageAssets() ) {
				return;
			}

			if ( $_POST['wpacu_area_update_plugins_row_state'] !== 'yes' ) {
				return;
			}

			if ( ! isset($_POST['wpacu_nonce']) ) {
				echo 'Error: The security nonce was not sent for verification. Location: '.__METHOD__;
				return;
			}

			if ( ! wp_verify_nonce($_POST['wpacu_nonce'], 'wpacu_area_update_plugins_row_state_nonce') ) {
				echo 'Error: The security check has failed. Location: '.__METHOD__;
				return;
			}

			$areaAllAssetsRowState = $_POST['wpacu_area_plugins_row_state'];
            $pluginsForArea        = $_POST['wpacu_manage_plugins_in_area']; // 'front' or 'dash'

			if ( ! empty($_POST['wpacu_area_plugins']) && is_array($_POST['wpacu_area_plugins']) ) {
				foreach ($_POST['wpacu_area_plugins'] as $pluginPath) {
					echo 'New State: '.$areaAllAssetsRowState.' / Plugin: '.$pluginPath . ' / Area: '.$pluginsForArea."\n<br />";
					self::updatePluginRowStatus( $areaAllAssetsRowState, $pluginPath, $pluginsForArea );
				}
			}

			exit();
		}
	}

	/**
	 * Make sure to check if the hardcoded asset has at least one unload rule
     * as there is no point in overwhelming the database with hardcoded data of assets that have no rules
	 */
	public static function storeHardcodedAssetsInfo()
	{
		$handlesWithAtLeastOneRule = Overview::handlesWithAtLeastOneRule();

		$formKey = 'wpacu_assets_info_hardcoded_data';

		foreach (array('styles', 'scripts') as $assetType) {
		    if ( ! empty( $_POST[ $formKey ][ $assetType ] ) ) {
			    foreach ( $_POST[ $formKey ][ $assetType ] as $generatedHandle => $value ) {
				    $hasAtLeastOneRule = isset($handlesWithAtLeastOneRule[$assetType][$generatedHandle]);

			        if ( ! $hasAtLeastOneRule ) {
			    	    self::hardcodedAssetRemoveItsInfo($generatedHandle, $assetType);
			    		continue;
				    }

				    $wpacuHardcodedInfoToStore[ $assetType ][ $generatedHandle ] = json_decode( base64_decode( $value ), true );

				    Update::updateHandlesInfo( $wpacuHardcodedInfoToStore );
			    }
		    }
	    }

		}

	/**
	 * No need to keep the information of the hardcoded assets as there are no unload rules set anymore?
	 * Remove the entry from the database, making the `options` table smaller (some hardcoded tags can be large)
	 *
	 * @param $generatedHandle
	 * @param $assetType
	 */
	public static function hardcodedAssetRemoveItsInfo($generatedHandle, $assetType)
    {
    	$optionToUpdate = WPACU_PLUGIN_ID . '_global_data';
	    $globalKey = 'assets_info';

	    $existingListEmpty = array('styles' => array($globalKey => array()), 'scripts' => array($globalKey => array()));
	    $existingListJson = get_option($optionToUpdate);

	    $existingListData = Main::instance()->existingList($existingListJson, $existingListEmpty);
	    $existingList = $existingListData['list'];

	    // $assetType could be 'styles' or 'scripts'
		if (isset($existingList[$assetType][$globalKey][$generatedHandle])) {
			unset($existingList[$assetType][$globalKey][$generatedHandle]);
		}

	    Misc::addUpdateOption($optionToUpdate, wp_json_encode(Misc::filterList($existingList)));
    }
}
