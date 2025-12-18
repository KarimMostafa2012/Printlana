<?php

use WpAssetCleanUp\Main;
use WpAssetCleanUp\Misc;

if (! isset($data)) {
	exit; // no direct access
}

$allAssets = $data['all']['styles'];
$allAssetsFinal = $data['unloaded_css_handles'] = array();

foreach ($allAssets as $obj) {
	$row        = array();
	$row['obj'] = $obj;

	// e.g. Unload on this page, Unload on all 404 pages, etc.
	$activePageLevel = isset( $data['current_unloaded_page_level']['styles'] ) && in_array( $row['obj']->handle, $data['current_unloaded_page_level']['styles'] );

	$row['class']   = $activePageLevel ? 'wpacu_not_load' : '';
	$row['checked'] = $activePageLevel ? 'checked="checked"' : '';

	/*
	 * $row['is_group_unloaded'] is only used to apply a red background in the asset's area to point out that the style is unloaded
	 * is set to `true` if either the asset is unloaded everywhere or it's unloaded on a group of pages (such as all pages belonging to 'page' post type)
	*/
	$row['global_unloaded'] = $row['is_post_type_unloaded'] = $row['is_load_exception_per_page'] = $row['is_group_unloaded'] = false;

	// Mark it as unloaded - Everywhere
	if ( in_array( $row['obj']->handle, $data['global_unload']['styles'] ) ) {
		$row['global_unloaded'] = $row['is_group_unloaded'] = true;
	}

	// Mark it as unloaded - for the Current Post Type
	if ( isset($data['bulk_unloaded_type']) &&
	     $data['bulk_unloaded_type'] &&
	     is_array($data['bulk_unloaded'][$data['bulk_unloaded_type']]['styles']) &&
	     in_array($row['obj']->handle, $data['bulk_unloaded'][$data['bulk_unloaded_type']]['styles']) ) {
		$row['is_group_unloaded'] = true;

		if ( $data['bulk_unloaded_type'] === 'post_type' ) {
			$row['is_post_type_unloaded'] = true;
		}
	}

	$isLoadExceptionPerPage              = isset($data['load_exceptions_per_page']['styles']) && in_array($row['obj']->handle, $data['load_exceptions_per_page']['styles']);
	$isLoadExceptionForCurrentPostType   = isset($data['load_exceptions_post_type']['styles']) && in_array($row['obj']->handle, $data['load_exceptions_post_type']['styles']);

	// [wpacu_pro]
	$isUnloadRegExMatch                  = isset( $data['unloads_regex_matches']['styles'] ) && in_array( $row['obj']->handle, $data['unloads_regex_matches']['styles'] );
	$isLoadExceptionRegExMatch           = isset( $data['load_exceptions_regex_matches']['styles'] ) && in_array( $row['obj']->handle, $data['load_exceptions_regex_matches']['styles'] );
	$isLoadExceptionForCurrentPostViaTax = isset( $data['load_exceptions_post_type_via_tax_matches']['styles'] ) && in_array( $row['obj']->handle, $data['load_exceptions_post_type_via_tax_matches']['styles'] );
	// [/wpacu_pro]

	$row['is_load_exception_per_page']  = $isLoadExceptionPerPage;
	$row['is_load_exception_post_type'] = $isLoadExceptionForCurrentPostType;

	$isLoadException = $isLoadExceptionPerPage || $isLoadExceptionForCurrentPostType
		/* [wpacu_pro] */ || $isLoadExceptionRegExMatch || $isLoadExceptionForCurrentPostViaTax /* [/wpacu_pro] */;

	// No load exception to any kind and a bulk unload rule is applied? Append the CSS class for unloading
	if ( ! $isLoadException && ($row['is_group_unloaded']
        /* [wpacu_pro] */ || $isUnloadRegExMatch /* [/wpacu_pro] */) ) {
		$row['class'] .= ' wpacu_not_load';
	}

	// Probably most reliable to use in order to check the unloaded styles; it might be the only one used in future plugin versions
	if (strpos($row['class'], 'wpacu_not_load') === false && isset($data['current_unloaded_all']['styles']) && in_array($row['obj']->handle, $data['current_unloaded_all']['styles'])) {
		$row['class'] .= ' wpacu_not_load';
	}

	if (strpos($row['class'], 'wpacu_not_load') !== false) {
		// Actually unloaded CSS, not just marked for unload
		$data['unloaded_css_handles'][] = $row['obj']->handle;
    }

	$row['extra_data_css_list'] = ( is_object( $row['obj']->extra ) && isset( $row['obj']->extra->after ) ) ? $row['obj']->extra->after : array();

	if ( ! $row['extra_data_css_list'] ) {
		$row['extra_data_css_list'] = ( is_array( $row['obj']->extra ) && isset( $row['obj']->extra['after'] ) ) ? $row['obj']->extra['after'] : array();
	}

	$row['class'] .= ' style_' . $row['obj']->handle;

	$row['asset_type'] = 'styles';

	$allAssetsFinal[$obj->handle] = $row;
}

foreach ($allAssetsFinal as $assetHandle => $row) {
	$data['row'] = $row;

	// Load Template
	$parseTemplate = Main::instance()->parseTemplate(
		'/meta-box-loaded-assets/_asset-style-single-row',
		$data,
		false,
		true
	);

	$templateRowOutput = $parseTemplate['output'];
	$data = $parseTemplate['data'];

	if (isset($data['rows_build_array']) && $data['rows_build_array']) {
		$uniqueHandle = $uniqueHandleOriginal = $row['obj']->handle;

		if (array_key_exists($uniqueHandle, $data['rows_assets'])) {
			$uniqueHandle .= 1; // make sure each key is unique
		}

		if (isset($data['rows_by_location']) && $data['rows_by_location']) {
			$data['rows_assets']
	          [$row['obj']->locationMain] // 'plugins', 'themes' etc.
			    [$row['obj']->locationChild] // Theme/Plugin Title
			      [$uniqueHandle]
			        ['style'] = $templateRowOutput;
		} elseif (isset($data['rows_by_position']) && $data['rows_by_position']) {
			$handlePosition = /* [wpacu_pro] */ (isset($row['obj']->position_new) && $row['obj']->position_new) ? $row['obj']->position_new : /* [/wpacu_pro] */ $row['obj']->position;

			$data['rows_assets']
			  [$handlePosition] // 'head', 'body'
			    [$uniqueHandle]
			      ['style'] = $templateRowOutput;
		} elseif (isset($data['rows_by_preload']) && $data['rows_by_preload']) {
			$preloadStatus = $row['obj']->preload_status;

			$data['rows_assets']
				[$preloadStatus] // 'preloaded', 'not_preloaded'
					[$uniqueHandle]
						['style'] = $templateRowOutput;
		} elseif (isset($data['rows_by_parents']) && $data['rows_by_parents'])  {
			$childHandles = isset($data['all_deps']['parent_to_child']['styles'][$row['obj']->handle]) ? $data['all_deps']['parent_to_child']['styles'][$row['obj']->handle] : array();

			if (! empty($childHandles)) {
				$handleStatus = 'parent';
			} elseif (isset($row['obj']->deps) && ! empty($row['obj']->deps)) {
				$handleStatus = 'child';
			} else {
				$handleStatus = 'independent';
			}

			$data['rows_assets']
				[$handleStatus] // 'parent', 'child', 'independent'
					[$uniqueHandle]
						['style'] = $templateRowOutput;
		} elseif (isset($data['rows_by_loaded_unloaded']) && $data['rows_by_loaded_unloaded']) {
			if (isset($data['current_unloaded_all']['styles']) && in_array($row['obj']->handle, $data['current_unloaded_all']['styles'])) {
				$handleStatus = 'unloaded';
			} else {
				$handleStatus = ( strpos( $row['class'], 'wpacu_not_load' ) !== false ) ? 'unloaded' : 'loaded';
			}

			$data['rows_assets']
				[$handleStatus] // 'loaded', 'unloaded'
					[$uniqueHandle]
						['style'] = $templateRowOutput;
		} elseif (isset($data['rows_by_size']) && $data['rows_by_size']) {
			$sizeStatus = (isset($row['obj']->size_raw) && is_int($row['obj']->size_raw)) ? 'with_size' : 'external_na';
			$data['rows_assets']
				[$sizeStatus] // 'with_size', 'external_na'
					[$uniqueHandle]
						['style'] = $templateRowOutput;

			if ($sizeStatus === 'with_size') {
				// Associated the handle with the raw size of the file
				$data['handles_sizes'][$uniqueHandle] = $row['obj']->size_raw;
			}
		} elseif (isset($data['rows_by_rules']) && $data['rows_by_rules']) {
			$ruleStatus = (isset($data['row']['at_least_one_rule_set']) && $data['row']['at_least_one_rule_set']) ? 'with_rules' : 'with_no_rules';
			$data['rows_assets']
				[$ruleStatus] // 'with_rules', 'with_no_rules'
					[$uniqueHandle]
						['style'] = $templateRowOutput;
		} else {
			$data['rows_assets'][$uniqueHandle] = $templateRowOutput;
		}
	} else {
		echo Misc::stripIrrelevantHtmlTags($templateRowOutput);
	}
}
