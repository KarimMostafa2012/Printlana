<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$nickx_rendering_obj = new WC_PRODUCT_VIDEO_GALLERY_RENDERING();
echo $nickx_rendering_obj->nickx_show_product_image('template');
