<?php
/*
 * No direct access to this file
 */

use WpAssetCleanUp\Misc;

if (! isset($data, $criticalCssConfig)) {
    exit;
}

$postTypes = get_post_types( array( 'public' => true, '_builtin' => false, 'rewrite' => true ) );
$data['post_types_list'] = Misc::filterCustomPostTypesList($postTypes);

$chosenPostType = (isset($_GET['wpacu_current_post_type']) && $_GET['wpacu_current_post_type'])
	? $_GET['wpacu_current_post_type']
	: Misc::arrayKeyFirst($data['post_types_list']);
$data['chosen_post_type'] = $chosenPostType;

$locationKey = 'custom_post_type_'.$chosenPostType;

require_once __DIR__ . '/common/_settings.php';
