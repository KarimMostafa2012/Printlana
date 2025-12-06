<?php // plugin core

if (!defined('ABSPATH')) exit;

function disable_media_sizes_intermediate_image_sizes($sizes) {
	
	$options = disable_media_sizes_get_options();
	
	$thumbnail      = isset($options['disable-size-thumbnail'])    ? $options['disable-size-thumbnail']    : 0;
	$medium         = isset($options['disable-size-medium'])       ? $options['disable-size-medium']       : 0;
	$large          = isset($options['disable-size-large'])        ? $options['disable-size-large']        : 0;
	$medium_large   = isset($options['disable-size-medium-large']) ? $options['disable-size-medium-large'] : 0;
	$twox_med_large = isset($options['disable-size-1536x1536'])    ? $options['disable-size-1536x1536']    : 0;
	$twox_large     = isset($options['disable-size-2048x2048'])    ? $options['disable-size-2048x2048']    : 0;
	
	if ($thumbnail)      unset($sizes['thumbnail']);    // disable thumbnail size
	if ($medium)         unset($sizes['medium']);       // disable medium size
	if ($large)          unset($sizes['large']);        // disable large size
	if ($medium_large)   unset($sizes['medium_large']); // disable medium-large size
	if ($twox_med_large) unset($sizes['1536x1536']);    // disable 2x medium-large size
	if ($twox_large)     unset($sizes['2048x2048']);    // disable 2x large size
	
	return $sizes;
	
}


function disable_media_sizes_big_image_size($threshold) {
	
	$options = disable_media_sizes_get_options();
	
	$big = isset($options['disable-size-big']) ? $options['disable-size-big'] : 0;
	
	return $big ? false : $threshold; // disable big/scaled image size
	
}