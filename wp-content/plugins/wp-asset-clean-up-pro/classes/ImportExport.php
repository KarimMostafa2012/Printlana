<?php
namespace WpAssetCleanUp;

use WpAssetCleanUp\OptimiseAssets\OptimizeCommon;

/**
 * Class ImportExport
 * @package WpAssetCleanUp
 */
class ImportExport
{
	/***** BEGIN EXPORT ******/
	/**
	 * @return string
	 */
	public function jsonSettings()
	{
		$wpacuSettings = new Settings();
		$settingsArray = $wpacuSettings->getAll();

		// Some "Site-wide Common Unloads" values are fetched outside the "Settings" option values
		// e.g., jQuery Migrate, Comment Reply
		$globalUnloadList = Main::instance()->getGlobalUnload();

		// CSS
		$settingsArray['disable_dashicons_for_guests'] = in_array( 'dashicons',        $globalUnloadList['styles'] );
		$settingsArray['disable_wp_block_library']     = in_array( 'wp-block-library', $globalUnloadList['styles'] );

		// JS
		$settingsArray['disable_jquery_migrate'] = in_array( 'jquery-migrate',   $globalUnloadList['scripts'] );
		$settingsArray['disable_comment_reply']  = in_array( 'comment-reply',    $globalUnloadList['scripts'] );

		return wp_json_encode($settingsArray);
	}

	/**
	 * Was the "Export" button clicked? Do verifications and send the right headers
	 */
	public function doExport()
	{
		if (! Menu::userCanManageAssets()) {
			return;
		}

		if (! Misc::getVar('post', 'wpacu_do_export_nonce')) {
			return;
		}

		$wpacuExportFor = Misc::getVar('post', 'wpacu_export_for');

		if (! $wpacuExportFor) {
			return;
		}

		// Last important check
		\check_admin_referer('wpacu_do_export', 'wpacu_do_export_nonce');

		$exportComment = 'Exported [exported_text] via '.WPACU_PLUGIN_TITLE.' (v'.WPACU_PLUGIN_VERSION.') - Timestamp: '.time();

		// "Settings" values (could be just default ones if none are found in the database)
		if ($wpacuExportFor === 'settings') {
			$exportComment = str_replace('[exported_text]', 'Settings', $exportComment);

			$settingsJson = $this->jsonSettings();

			$valuesArray = array(
				'__comment' => $exportComment,
				'settings'  => json_decode($settingsJson, ARRAY_A)
			);
		}

		// [wpacu_pro]
		if ($wpacuExportFor === 'critical_css') {
			$exportComment = str_replace('[exported_text]', 'Critical CSS', $exportComment);

			$criticalCssOptionsArray = \WpAssetCleanUpPro\ImportExportPro::getCriticalCssOptionsArray();

			$valuesArray = array(
				'__comment' => $exportComment,
				'critical_css_options' => $criticalCssOptionsArray
			);
		}
		// [/wpacu_pro]

		if ($wpacuExportFor === 'everything') {
			$exportComment = str_replace('[exported_text]', 'Everything', $exportComment);

			// "Settings"
			$settingsJson = $this->jsonSettings();

			// "Homepage"
			$frontPageNoLoad      = get_option(WPACU_PLUGIN_ID . '_front_page_no_load');
			$frontPageNoLoadArray = json_decode($frontPageNoLoad, ARRAY_A);

			$frontPageExceptionsListJson  = get_option(WPACU_PLUGIN_ID . '_front_page_load_exceptions');
			$frontPageExceptionsListArray = json_decode($frontPageExceptionsListJson, ARRAY_A);

			// "Site-wide" Unloads
			$globalUnloadListJson = get_option(WPACU_PLUGIN_ID . '_global_unload');
			$globalUnloadArray    = json_decode($globalUnloadListJson, ARRAY_A);

			// "Bulk" unloads (for all pages, posts, custom post type)
			$bulkUnloadListJson = get_option(WPACU_PLUGIN_ID . '_bulk_unload');
			$bulkUnloadArray    = json_decode($bulkUnloadListJson, ARRAY_A);

			// Post type: load exceptions
			$postTypeLoadExceptionsJson  = get_option(WPACU_PLUGIN_ID . '_post_type_load_exceptions');
			$postTypeLoadExceptionsArray = json_decode($postTypeLoadExceptionsJson, ARRAY_A);

			$globalDataListJson  = get_option(WPACU_PLUGIN_ID . '_global_data');
			$globalDataListArray = json_decode($globalDataListJson, ARRAY_A);

			// [wpacu_pro]
			// Load exceptions for extras: is_archive(), author, search, 404 pages
			$extrasLoadExceptionsListJson = get_option(WPACU_PLUGIN_ID . '_extras_load_exceptions');
			$extrasLoadExceptionsArray    = json_decode($extrasLoadExceptionsListJson, ARRAY_A);

			// Load exceptions for pages that have certain taxonomies set
			$postTypeViaTaxLoadExceptionsJson  = get_option(WPACU_PLUGIN_ID . '_post_type_via_tax_load_exceptions');
			$postTypeViaTaxLoadExceptionsArray = json_decode($postTypeViaTaxLoadExceptionsJson, ARRAY_A);
			// [/wpacu_pro]

			global $wpdb;

			$allMetaResults = array();

			$metaKeyLike = '_' . WPACU_PLUGIN_ID . '_%';

			$tableList = array($wpdb->postmeta);

			// [wpacu_pro]
			$tableList[] = $wpdb->termmeta;
			$tableList[] = $wpdb->usermeta;
			// [/wpacu_pro]

			foreach ($tableList as $tableName) {
				if ( $tableName === $wpdb->postmeta ) {
					$sqlFetchPostsMetas         = <<<SQL
SELECT post_id, meta_key, meta_value FROM `{$wpdb->postmeta}` WHERE meta_key LIKE '{$metaKeyLike}'
SQL;
					$allMetaResults['postmeta'] = $wpdb->get_results( $sqlFetchPostsMetas, ARRAY_A );
				}

				// [wpacu_pro]
				if ( $tableName === $wpdb->termmeta ) {
					$sqlFetchTermsMetas         = <<<SQL
SELECT term_id, meta_key, meta_value FROM `{$wpdb->termmeta}` WHERE meta_key LIKE '{$metaKeyLike}'
SQL;
					$allMetaResults['termmeta'] = $wpdb->get_results( $sqlFetchTermsMetas, ARRAY_A );
				}

				if ( $tableName === $wpdb->usermeta ) {
					$sqlFetchUserMetas          = <<<SQL
SELECT user_id, meta_key, meta_value FROM `{$wpdb->usermeta}` WHERE meta_key LIKE '{$metaKeyLike}'
SQL;
					$allMetaResults['usermeta'] = $wpdb->get_results( $sqlFetchUserMetas, ARRAY_A );
				}
				// [/wpacu_pro]
			}

			// Export Field Names should be kept as they are and in case
			// they are changed later on, a fallback should be in place
			$valuesArray = array(
				'__comment' => $exportComment,
				'settings'  => json_decode($settingsJson, ARRAY_A),

				'homepage' => array(
					'unloads'         => $frontPageNoLoadArray,
					'load_exceptions' => $frontPageExceptionsListArray
				),

				'global_unload' => $globalUnloadArray,
				'bulk_unload'   => $bulkUnloadArray,

				'post_type_exceptions' => $postTypeLoadExceptionsArray,

				'global_data'   => $globalDataListArray,

				'posts_metas'   => $allMetaResults['postmeta']
			);

			// [wpacu_pro]
			$valuesArray['post_type_via_tax_exceptions'] = $postTypeViaTaxLoadExceptionsArray;
			$valuesArray['extras_exceptions']            = $extrasLoadExceptionsArray;
			$valuesArray['terms_metas']                  = $allMetaResults['termmeta'];
			$valuesArray['users_metas']                  = $allMetaResults['usermeta'];
			$valuesArray['critical_css_options']         = \WpAssetCleanUpPro\ImportExportPro::getCriticalCssOptionsArray();
			// [/wpacu_pro]
		}

		// Was the right selection made? Continue
		$date = date('j-M-Y-H.i');
		$host = parse_url(site_url(), PHP_URL_HOST);

		$wpacuExportForPartOfFileName = str_replace('_', '-', $wpacuExportFor);

		header('Content-Type: application/json');
		header('Content-Disposition: attachment; filename="asset-cleanup-pro-exported-'.$wpacuExportForPartOfFileName.'-from-'.$host.'-'.$date.'.json"');

		echo wp_json_encode($valuesArray);
		exit();
	}
	/***** END EXPORT ******/

	/***** BEGIN IMPORT ******/
	/**
	 *
	 */
	public function doImport()
	{
		if (! Menu::userCanManageAssets()) {
			return;
		}

		if (! Misc::getVar('post', 'wpacu_do_import_nonce')) {
			return;
		}

		$jsonTmpName = isset($_FILES['wpacu_import_file']['tmp_name']) ? $_FILES['wpacu_import_file']['tmp_name'] : false;

		if (! $jsonTmpName) {
			return;
		}

		// Last important check
		\check_admin_referer('wpacu_do_import', 'wpacu_do_import_nonce');

		if (! is_file($jsonTmpName)) {
			return;
		}

		$valuesJson = FileSystem::fileGetContents($jsonTmpName);

		$valuesArray = json_decode($valuesJson, ARRAY_A);

		if ( ! (JSON_ERROR_NONE === Misc::jsonLastError())) {
			return;
		}

		$importedList = array();

		// NOTE: The values are not replaced, but added to the existing ones (if any)

		// "Settings" (Replace)
		if ( ! empty($valuesArray['settings']) ) {
			$wpacuSettings = new Settings();

			// "Site-wide Common Unloads" - apply settings

			// JS
			$disableJQueryMigrate            = isset( $valuesArray['settings']['disable_jquery_migrate'] ) ? $valuesArray['settings']['disable_jquery_migrate'] : false;
			$disableCommentReply             = isset( $valuesArray['settings']['disable_comment_reply'] ) ? $valuesArray['settings']['disable_comment_reply'] : false;

			// CSS
			$disableGutenbergCssBlockLibrary = isset( $valuesArray['settings']['disable_wp_block_library'] ) ? $valuesArray['settings']['disable_wp_block_library'] : false;
			$disableDashiconsForGuests       = isset( $valuesArray['settings']['disable_dashicons_for_guests'] ) ? $valuesArray['settings']['disable_dashicons_for_guests'] : false;

			$wpacuSettings->updateSiteWideRuleForCommonAssets(
				array(
					// JS
					'jquery_migrate'   => $disableJQueryMigrate,
					'comment_reply'    => $disableCommentReply,

					// CSS
					'wp_block_library' => $disableGutenbergCssBlockLibrary,
					'dashicons'        => $disableDashiconsForGuests,
				)
			);

			Misc::addUpdateOption(WPACU_PLUGIN_ID . '_settings', wp_json_encode($valuesArray['settings']));
			$importedList[] = 'settings';
		}

		// "Homepage" Unloads
		if (isset($valuesArray['homepage']['unloads']['scripts'])
		    || isset($valuesArray['homepage']['unloads']['styles'])) {
			Misc::addUpdateOption(WPACU_PLUGIN_ID . '_front_page_no_load', wp_json_encode($valuesArray['homepage']['unloads']));
			$importedList[] = 'homepage_unloads';
		}

		// "Homepage" Load Exceptions
		if (isset($valuesArray['homepage']['load_exceptions']['scripts'])
		    || isset($valuesArray['homepage']['load_exceptions']['styles'])) {
			Misc::addUpdateOption(WPACU_PLUGIN_ID . '_front_page_load_exceptions', wp_json_encode($valuesArray['homepage']['load_exceptions']));
			$importedList[] = 'homepage_exceptions';
		}

		// "Site-Wide" (Everywhere) Unloads
		if (isset($valuesArray['global_unload']['scripts'])
		    || isset($valuesArray['global_unload']['styles'])) {
			Misc::addUpdateOption(WPACU_PLUGIN_ID . '_global_unload', wp_json_encode($valuesArray['global_unload']));
			$importedList[] = 'sitewide_unloads';
		}

		// Bulk Unloads (e.g. Unload on all pages of product post type)
		if (isset($valuesArray['bulk_unload']['scripts'])
		    || isset($valuesArray['bulk_unload']['styles'])) {
			Misc::addUpdateOption(WPACU_PLUGIN_ID . '_bulk_unload', wp_json_encode($valuesArray['bulk_unload']));
			$importedList[] = 'bulk_unload';
		}

		// Post type: load exception
		if ( ! empty($valuesArray['post_type_exceptions']) ) {
			Misc::addUpdateOption(WPACU_PLUGIN_ID . '_post_type_load_exceptions', wp_json_encode($valuesArray['post_type_exceptions']));
			$importedList[] = 'post_type_load_exceptions';
		}

		// [wpacu_pro]
		// Post type: load exception via taxonomy
		if ( ! empty($valuesArray['post_type_via_tax_exceptions']) ) {
			Misc::addUpdateOption(WPACU_PLUGIN_ID . '_post_type_via_tax_exceptions', wp_json_encode($valuesArray['post_type_via_tax_exceptions']));
			$importedList[] = 'post_type_via_tax_exceptions';
		}
		// [/wpacu_pro]

		// Global Data
		if (isset($valuesArray['global_data']['scripts'])
		    || isset($valuesArray['global_data']['styles'])) {
			Misc::addUpdateOption(WPACU_PLUGIN_ID . '_global_data', wp_json_encode($valuesArray['global_data']));
			$importedList[] = 'global_data';

			// [wpacu_pro]
			$importedList['global_data_values'] = $valuesArray['global_data']; // this is used to print what was imported as it's a complex option value
			// [/wpacu_pro]
		}

		// [START] All Posts Metas (per page unloads, load exceptions, page options from side meta box)
		$targetKey = 'posts_metas';

		if ( ! empty($valuesArray[$targetKey]) ) {
			foreach ($valuesArray[$targetKey] as $metaValues) {
				// It needs to have a post ID and meta key starting with _' . WPACU_PLUGIN_ID . '
				if ( ! (isset($metaValues['post_id'], $metaValues['meta_key'])
					&& strpos($metaValues['meta_key'], '_' . WPACU_PLUGIN_ID) === 0) ) {
					continue;
				}

				$postId    = $metaValues['post_id'];
				$metaKey   = $metaValues['meta_key'];
				$metaValue = $metaValues['meta_value']; // already JSON encoded

				if (! add_post_meta($postId, $metaKey, $metaValue, true)) {
					update_post_meta($postId, $metaKey, $metaValue);
				}
			}

			$importedList[] = 'posts_metas';
		}
		// [END] All Posts Metas (per page unloads, load exceptions, page options from side meta box)

		// [wpacu_pro]
		// [START] All Terms Metas
		$targetKey = 'terms_metas';

		if ( ! empty($valuesArray[$targetKey]) ) {
			foreach ($valuesArray[$targetKey] as $metaValues) {
				// It needs to have a term ID and meta key starting with _' . WPACU_PLUGIN_ID . '
				if ( ! (isset($metaValues['term_id'], $metaValues['meta_key'])
				        && strpos($metaValues['meta_key'], '_' . WPACU_PLUGIN_ID) === 0) ) {
					continue;
				}

				$termId    = $metaValues['term_id'];
				$metaKey   = $metaValues['meta_key'];
				$metaValue = $metaValues['meta_value']; // already JSON encoded

				if (! add_term_meta($termId, $metaKey, $metaValue, true)) {
					update_term_meta($termId, $metaKey, $metaValue);
				}
			}

			$importedList[] = 'terms_metas';
		}
		// [END] All Terms Metas

		// [START] All Users Metas
		$targetKey = 'users_metas';

		if ( ! empty($valuesArray[$targetKey]) ) {
			foreach ($valuesArray[$targetKey] as $metaValues) {
				// It needs to have a user ID and meta key starting with _' . WPACU_PLUGIN_ID . '
				if ( ! (isset($metaValues['user_id'], $metaValues['meta_key'])
				        && strpos($metaValues['meta_key'], '_' . WPACU_PLUGIN_ID) === 0) ) {
					continue;
				}

				$userId    = $metaValues['user_id'];
				$metaKey   = $metaValues['meta_key'];
				$metaValue = $metaValues['meta_value']; // already JSON encoded

				if (! add_user_meta($userId, $metaKey, $metaValue, true)) {
					update_user_meta($userId, $metaKey, $metaValue);
				}
			}

			$importedList[] = 'users_metas';
		}
		// [END] All Users Metas

		if ( ! empty( $valuesArray['critical_css_options'] ) ) {
			foreach ( $valuesArray['critical_css_options'] as $optionName => $optionValue ) {
				if ( strpos( $optionName, WPACU_PLUGIN_ID . '_critical_css_' ) === 0 ) {
					Misc::addUpdateOption( $optionName, $optionValue );
				}
			}

			$importedList[] = 'critical_css_options';
		}
		// [/wpacu_pro]

		if (! empty($importedList)) {
			// After import was completed, clear all CSS/JS cache
			OptimizeCommon::clearCache();

			set_transient('wpacu_import_done', $importedList, 30);

			wp_redirect(admin_url('admin.php?page=wpassetcleanup_tools&wpacu_for=import_export&wpacu_import_done=1&wpacu_time=' . time()));
			exit();
		}
	}
	/***** END IMPORT ******/
}
