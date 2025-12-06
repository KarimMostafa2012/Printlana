<?php // plugin settings

if (!defined('ABSPATH')) exit;

function disable_media_sizes_options() {
	
	return array(
		
		'disable-size-thumbnail'    => 0,
		'disable-size-medium'       => 0,
		'disable-size-large'        => 0,
		'disable-size-medium-large' => 0,
		'disable-size-1536x1536'    => 0,
		'disable-size-2048x2048'    => 0,
		'disable-size-big'          => 0,
		
	);
	
}

function disable_media_sizes_get_options() {
	
	return get_option('disable_media_sizes_options', disable_media_sizes_options());
	
}

function disable_media_sizes_menu_page() {
	
	// add_options_page( $page_title, $menu_title, $capability, $menu_slug, $function )
	add_options_page('Disable Media Sizes', 'Disable Media Sizes', 'manage_options', 'disable-media-sizes', 'disable_media_sizes_display_settings');
	
}

function disable_media_sizes_display_settings() { ?>
	
	<div class="wrap">
		<h1><span class="fa fa-pad fa-arrows"></span> <?php _e('Disable Media Sizes', 'disable-media-sizes'); ?> <span class="disable-media-sizes-version"><?php echo DISABLE_MEDIA_SIZES_VERSION; ?></span></h1>
		<p>
			<?php esc_html_e('Thank you for using Disable Media Sizes :) Below you can choose which image sizes should be disabled.', 'disable-media-sizes'); ?>
		</p>
		<p>
			<strong><a class="toggle" href="#notes"><?php esc_html_e('Click to read notes', 'disable-media-sizes'); ?></a></strong> 
			<span class="notes">
				<?php esc_html_e('If all image sizes below are disabled, only original images will be uploaded. It is recommended to enable at least one smaller size image, so WordPress can use it for thumbnails in the Admin Area.', 'disable-media-sizes'); ?> 
				<a target="_blank" rel="noopener noreferrer" href="https://perishablepress.com/disable-wordpress-generated-images/" title="<?php esc_attr_e('How to Disable WordPress Automatically Generated Images', 'disable-media-sizes'); ?>"><?php esc_html_e('More info', 'disable-media-sizes'); ?>&nbsp;&raquo;</a>
			</span>
		</p>
		<form method="post" action="options.php">
			
			<?php 
				settings_fields('disable_media_sizes_options');
				do_settings_sections('disable_media_sizes_options');
				submit_button();
			?>
			
		</form>
		<style>
			.notes { padding: 0 0 0 5px; }
		</style>
		<script>
			jQuery('.notes').hide();
			jQuery('.toggle').click(function(){ jQuery('.notes').toggle(); });
		</script>
	</div>
	
<?php }

function disable_media_sizes_register_settings() {
	
	// register_setting( $option_group, $option_name, $sanitize_callback );
	register_setting('disable_media_sizes_options', 'disable_media_sizes_options', 'disable_media_sizes_validate_options');
	
	// add_settings_section( $id, $title, $callback, $page ); 
	add_settings_section('settings_1', 'Media Sizes', 'disable_media_sizes_settings_section_1', 'disable_media_sizes_options');
	
	// add_settings_field( $id, $title, $callback, $page, $section, $args );
	add_settings_field('disable-size-thumbnail',  __('Disable Thumbnail Size', 'disable-media-sizes'), 'disable_media_sizes_callback_checkbox', 'disable_media_sizes_options', 'settings_1', array('id' => 'disable-size-thumbnail', 'label' => __('Prevent WordPress from generating thumbnail images', 'disable-media-sizes')));
	add_settings_field('disable-size-medium',     __('Disable Medium Size',    'disable-media-sizes'), 'disable_media_sizes_callback_checkbox', 'disable_media_sizes_options', 'settings_1', array('id' => 'disable-size-medium',    'label' => __('Prevent WordPress from generating medium images',    'disable-media-sizes')));
	add_settings_field('disable-size-large',      __('Disable Large Size',     'disable-media-sizes'), 'disable_media_sizes_callback_checkbox', 'disable_media_sizes_options', 'settings_1', array('id' => 'disable-size-large',     'label' => __('Prevent WordPress from generating large images',     'disable-media-sizes')));
	
	// add_settings_section( $id, $title, $callback, $page ); 
	add_settings_section('settings_2', 'Other Sizes', 'disable_media_sizes_settings_section_2', 'disable_media_sizes_options');
	
	add_settings_field('disable-size-medium-large', __('Disable Medium Large',   'disable-media-sizes'), 'disable_media_sizes_callback_checkbox', 'disable_media_sizes_options', 'settings_2', array('id' => 'disable-size-medium-large', 'label' => __('Prevent WordPress from generating medium-large (768px) images', 'disable-media-sizes')));
	add_settings_field('disable-size-1536x1536',    __('Disable 1536x1536 Size', 'disable-media-sizes'), 'disable_media_sizes_callback_checkbox', 'disable_media_sizes_options', 'settings_2', array('id' => 'disable-size-1536x1536',    'label' => __('Prevent WordPress from generating 1536x1536 images',            'disable-media-sizes')));
	add_settings_field('disable-size-2048x2048',    __('Disable 2048x2048 Size', 'disable-media-sizes'), 'disable_media_sizes_callback_checkbox', 'disable_media_sizes_options', 'settings_2', array('id' => 'disable-size-2048x2048',    'label' => __('Prevent WordPress from generating 2048x2048 images',            'disable-media-sizes')));
	add_settings_field('disable-size-big',          __('Disable "Big" Size',     'disable-media-sizes'), 'disable_media_sizes_callback_checkbox', 'disable_media_sizes_options', 'settings_2', array('id' => 'disable-size-big',          'label' => __('Prevent WordPress from generating "big" (scaled) size images',  'disable-media-sizes')));
	
	add_settings_field('reset_options',             __('Reset Options',          'disable-media-sizes'), 'disable_media_sizes_callback_reset',    'disable_media_sizes_options', 'settings_2', array('id' => 'reset_options',             'label' => __('Restore default plugin options',                                'disable-media-sizes')));
	add_settings_field('rate_plugin',               __('Rate Plugin',            'disable-media-sizes'), 'disable_media_sizes_callback_rate',     'disable_media_sizes_options', 'settings_2', array('id' => 'rate_plugin',               'label' => __('Show support with a 5-star rating &raquo;',                     'disable-media-sizes')));
	add_settings_field('show_support',              __('Show Support',           'disable-media-sizes'), 'disable_media_sizes_callback_support',  'disable_media_sizes_options', 'settings_2', array('id' => 'show_support',              'label' => __('Show support with a small donation&nbsp;&raquo;',               'disable-media-sizes')));
	
}

function disable_media_sizes_validate_options($input) {
	
	if (isset($input['disable-size-thumbnail']))    $input['disable-size-thumbnail']    = sanitize_text_field(trim($input['disable-size-thumbnail']));
	if (isset($input['disable-size-medium']))       $input['disable-size-medium']       = sanitize_text_field(trim($input['disable-size-medium']));
	if (isset($input['disable-size-large']))        $input['disable-size-large']        = sanitize_text_field(trim($input['disable-size-large']));
	if (isset($input['disable-size-medium-large'])) $input['disable-size-medium-large'] = sanitize_text_field(trim($input['disable-size-medium-large']));
	if (isset($input['disable-size-1536x1536']))    $input['disable-size-1536x1536']    = sanitize_text_field(trim($input['disable-size-1536x1536']));
	if (isset($input['disable-size-2048x2048']))    $input['disable-size-2048x2048']    = sanitize_text_field(trim($input['disable-size-2048x2048']));
	if (isset($input['disable-size-big']))          $input['disable-size-big']          = sanitize_text_field(trim($input['disable-size-big']));
	
	return $input;
	
}

function disable_media_sizes_settings_section_1() {
	
	echo '<p>'. esc_html__('These sizes are set under WP Menu &#9656; Settings &#9656; Media &#9656; Image sizes.', 'disable-media-sizes') .'</p>';
	
}

function disable_media_sizes_settings_section_2() {
	
	echo '<p>'. esc_html__('These are the additional image sizes that WordPress generates quietly behind the scenes.', 'disable-media-sizes') .'</p>';
	
}

function disable_media_sizes_callback_checkbox($args) {
	
	$options = disable_media_sizes_get_options();
	
	$id    = isset($args['id'])    ? $args['id']    : '';
	$label = isset($args['label']) ? $args['label'] : '';
	$value = isset($options[$id])  ? $options[$id]  : '';
	
	$name = 'disable_media_sizes_options['. $id .']';
	
	echo '<input id="'. esc_attr($name) .'" name="'. esc_attr($name) .'" type="checkbox" '. checked($value, 1, false) .' value="1"> ';
	echo '<label for="'. esc_attr($name) .'" class="inline-block">'. $label .'</label>';
	
}

function disable_media_sizes_callback_rate($args) {
	
	$href  = 'https://wordpress.org/support/plugin/disable-media-sizes/reviews/?rate=5#new-post';
	$title = esc_attr__('Please give a 5-star rating! A huge THANK YOU for your support!', 'disable-media-sizes');
	$text  = isset($args['label']) ? $args['label'] : esc_html__('Show support with a 5-star rating &raquo;', 'disable-media-sizes');
	
	echo '<a target="_blank" rel="noopener noreferrer" class="disable-media-sizes-rate-plugin" href="'. $href .'" title="'. $title .'">'. $text .'</a>';
	
}

function disable_media_sizes_callback_support($args) {
	
	$href  = 'https://monzillamedia.com/donate.html';
	$title = esc_attr__('Donate via PayPal, credit card, or cryptocurrency', 'disable-media-sizes');
	$text  = isset($args['label']) ? $args['label'] : esc_html__('Show support with a small donation&nbsp;&raquo;', 'disable-media-sizes');
	
	echo '<a target="_blank" rel="noopener noreferrer" class="disable-media-sizes-show-support" href="'. $href .'" title="'. $title .'">'. $text .'</a>';
	
}

function disable_media_sizes_callback_reset($args) {
	
	$nonce = wp_create_nonce('disable-media-sizes-reset-options');
	
	$href  = add_query_arg(array('disable-media-sizes-reset-options' => $nonce), admin_url('options-general.php?page=disable-media-sizes'));
	
	$label = isset($args['label']) ? $args['label'] : esc_html__('Restore default plugin options', 'disable-media-sizes');
	
	echo '<a class="disable-media-sizes-reset-options" href="'. esc_url($href) .'">'. esc_html($label) .'</a>';
	
}

function disable_media_sizes_plugin_action_links($links, $file) {
	
	if (($file === 'disable-media-sizes/disable-media-sizes.php') && (current_user_can('manage_options'))) {
		
		$settings = '<a href="'. admin_url('options-general.php?page=disable-media-sizes') .'">'. esc_html__('Settings', 'disable-media-sizes') .'</a>';
		
		array_unshift($links, $settings);
		
	}
	
	return $links;
	
}

function disable_media_sizes_plugin_row_meta($links, $file) {
	
	if ($file === 'disable-media-sizes/disable-media-sizes.php') {
		
		$home_href  = 'https://perishablepress.com/wordpress-disable-media-sizes/';
		$home_title = esc_attr__('Plugin Homepage', 'disable-media-sizes');
		$home_text  = esc_html__('Homepage', 'disable-media-sizes');
		
		$links[] = '<a target="_blank" rel="noopener noreferrer" href="'. $home_href .'" title="'. $home_title .'">'. $home_text .'</a>';
		
		$rate_href  = 'https://wordpress.org/support/plugin/disable-media-sizes/reviews/?rate=5#new-post';
		$rate_title = esc_attr__('Click here to rate and review this plugin on WordPress.org', 'disable-media-sizes');
		$rate_text  = esc_html__('Rate this plugin', 'disable-media-sizes') .'&nbsp;&raquo;';
		
		$links[] = '<a target="_blank" rel="noopener noreferrer" href="'. $rate_href .'" title="'. $rate_title .'">'. $rate_text .'</a>';
		
	}
	
	return $links;
	
}

function disable_media_sizes_admin_footer_text($text) {
	
	$screen_id = disable_media_sizes_get_current_screen_id();
	
	$ids = array('settings_page_disable-media-sizes');
	
	if ($screen_id && apply_filters('disable_media_sizes_admin_footer_text', in_array($screen_id, $ids))) {
		
		$text = __('Like this plugin? Give it a', 'disable-media-sizes');
		
		$text .= ' <a target="_blank" rel="noopener noreferrer" href="https://wordpress.org/support/plugin/disable-media-sizes/reviews/?rate=5#new-post">';
		
		$text .= __('★★★★★ rating&nbsp;&raquo;', 'disable-media-sizes') .'</a>';
		
	}
	
	return $text;
	
}

function disable_media_sizes_get_current_screen_id() {
	
	if (!function_exists('get_current_screen')) require_once ABSPATH .'/wp-admin/includes/screen.php';
	
	$screen = get_current_screen();
	
	if ($screen && property_exists($screen, 'id')) return $screen->id;
	
	return false;
	
}

//

function disable_media_sizes_admin_notice() {
	
	if (disable_media_sizes_get_current_screen_id() === 'settings_page_disable-media-sizes') {
		
		if (!disable_media_sizes_check_date_expired() && !disable_media_sizes_dismiss_notice_check()) {
			
			?>
			
			<div class="notice notice-success notice-lh">
				<p>
					<strong><?php esc_html_e('Fall Sale!', 'disable-media-sizes'); ?></strong> 
					<?php esc_html_e('Take 25% OFF any of our', 'disable-media-sizes'); ?> 
					<a target="_blank" rel="noopener noreferrer" href="https://plugin-planet.com/"><?php esc_html_e('Pro WordPress plugins', 'disable-media-sizes'); ?></a> 
					<?php esc_html_e('and', 'disable-media-sizes'); ?> 
					<a target="_blank" rel="noopener noreferrer" href="https://books.perishablepress.com/"><?php esc_html_e('books', 'disable-media-sizes'); ?></a>. 
					<?php esc_html_e('Apply code', 'disable-media-sizes'); ?> <code>FALL2025</code> <?php esc_html_e('at checkout. Sale ends 1/11/2026.', 'disable-media-sizes'); ?> 
					<?php echo disable_media_sizes_dismiss_notice_link(); ?>
				</p>
			</div>
			
			<?php
			
		}
		
	}
	
}

function disable_media_sizes_dismiss_notice_activate() {
	
	delete_option('disable-media-sizes-dismiss-notice');
	
}

function disable_media_sizes_dismiss_notice_version() {
	
	$version_current = DISABLE_MEDIA_SIZES_VERSION;
	
	$version_previous = get_option('disable-media-sizes-dismiss-notice');
	
	$version_previous = ($version_previous) ? $version_previous : $version_current;
	
	if (version_compare($version_current, $version_previous, '>')) {
		
		delete_option('disable-media-sizes-dismiss-notice');
		
	}
	
}

function disable_media_sizes_dismiss_notice_check() {
	
	$check = get_option('disable-media-sizes-dismiss-notice');
	
	return ($check) ? true : false;
	
}

function disable_media_sizes_dismiss_notice_save() {
	
	if (isset($_GET['dismiss-notice-verify']) && wp_verify_nonce($_GET['dismiss-notice-verify'], 'disable_media_sizes_dismiss_notice')) {
		
		if (!current_user_can('manage_options')) exit;
		
		$result = update_option('disable-media-sizes-dismiss-notice', DISABLE_MEDIA_SIZES_VERSION, false);
		
		$result = $result ? 'true' : 'false';
		
		$location = admin_url('options-general.php?page=disable-media-sizes&dismiss-notice='. $result);
		
		wp_redirect($location);
		
		exit;
		
	}
	
}

function disable_media_sizes_dismiss_notice_link() {
	
	$nonce = wp_create_nonce('disable_media_sizes_dismiss_notice');
	
	$href  = add_query_arg(array('dismiss-notice-verify' => $nonce), admin_url('options-general.php?page=disable-media-sizes'));
	
	$label = esc_html__('Dismiss', 'disable-media-sizes');
	
	return '<a class="disable-media-sizes-dismiss-notice" href="'. esc_url($href) .'">'. esc_html($label) .'</a>';
	
}

function disable_media_sizes_check_date_expired() {
	
	$expires = apply_filters('disable_media_sizes_check_date_expired', '2026-01-11');
	
	return (new DateTime() > new DateTime($expires)) ? true : false;
	
}

//

function disable_media_sizes_enqueue_resources_admin() {
	
	$screen_id = disable_media_sizes_get_current_screen_id();
	
	if (!$screen_id) return;
	
	if ($screen_id === 'settings_page_disable-media-sizes') {
		
		wp_enqueue_style('wp-jquery-ui-dialog');
		
		wp_enqueue_style('disable-media-sizes', DISABLE_MEDIA_SIZES_URL .'css/settings.css', array(), DISABLE_MEDIA_SIZES_VERSION);
		
		wp_enqueue_style('disable-media-sizes-fonts', DISABLE_MEDIA_SIZES_URL .'css/font-icons.css', array(), DISABLE_MEDIA_SIZES_VERSION);
		
		wp_enqueue_script('disable-media-sizes', DISABLE_MEDIA_SIZES_URL .'js/settings.js', array('jquery', 'jquery-ui-core', 'jquery-ui-dialog'), DISABLE_MEDIA_SIZES_VERSION);
		
	}
	
}

function disable_media_sizes_admin_print_scripts() {
	
	$screen_id = disable_media_sizes_get_current_screen_id();
	
	if (!$screen_id) return;
	
	if ($screen_id === 'settings_page_disable-media-sizes') : 
	
	?>
	
	<script>
		var 
		disable_media_sizes_reset_title   = '<?php _e('Confirm Reset',            'disable-media-sizes'); ?>',
		disable_media_sizes_reset_message = '<?php _e('Restore default options?', 'disable-media-sizes'); ?>',
		disable_media_sizes_reset_true    = '<?php _e('Yes, make it so.',         'disable-media-sizes'); ?>',
		disable_media_sizes_reset_false   = '<?php _e('No, abort mission.',       'disable-media-sizes'); ?>';
	</script>
	
	<?php endif;
	
}

//

function disable_media_sizes_reset_options() {
	
	if (isset($_GET['disable-media-sizes-reset-options']) && wp_verify_nonce($_GET['disable-media-sizes-reset-options'], 'disable-media-sizes-reset-options')) {
		
		if (!current_user_can('manage_options')) exit;
		
		$update = delete_option('disable_media_sizes_options');
		
		$result = $update ? 'true' : 'false';
		
		$location = add_query_arg(array('disable-media-sizes-reset-options' => $result), admin_url('options-general.php?page=disable-media-sizes'));
		
		wp_redirect($location);
		
		exit;
		
	}
	
}