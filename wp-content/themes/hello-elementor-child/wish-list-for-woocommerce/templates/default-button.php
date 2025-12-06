<?php
// SOLUTION 1: Create a custom shortcode for thumbnail button
function alg_wc_wl_thumb_button_shortcode($atts = []) {
    // Parse attributes
    $atts = shortcode_atts([
        'product_id' => '',
    ], $atts);
    
    global $product;
    
    // Get product ID
    $product_id = !empty($atts['product_id']) ? 
                  intval($atts['product_id']) : 
                  ($product ? $product->get_id() : get_the_ID());
    
    if (!$product_id) {
        return '';
    }
    
    // Check if wishlist plugin functions exist
    if (!function_exists('alg_wc_wl')) {
        return '';
    }
    
    // Get wishlist instance and check if item is in wishlist
    $wishlist = alg_wc_wl();
    $is_in_wishlist = $wishlist->core->is_item_in_wishlist($product_id);
    
    // Get plugin settings (you might need to adjust these based on your plugin's settings)
    $btn_class = 'alg-wc-wl-toggle-btn alg-wc-wl-thumb-btn';
    $btn_icon_class = 'far fa-heart'; // Default heart icon
    $btn_icon_class_added = 'fas fa-heart'; // Filled heart icon
    $btn_data_action = $is_in_wishlist ? 'remove' : 'add';
    $show_loading = true;
    
    // Build the thumbnail button HTML
    ob_start();
    ?>
    <div data-item_id="<?php echo esc_attr($product_id); ?>" 
         data-action="<?php echo esc_attr($btn_data_action); ?>" 
         class="<?php echo esc_attr($btn_class); ?>" 
         style="cursor: pointer;">
        <div class="alg-wc-wl-view-state alg-wc-wl-view-state-add">
            <i class="<?php echo esc_attr($btn_icon_class); ?>" aria-hidden="true"></i>
        </div>
        <div class="alg-wc-wl-view-state alg-wc-wl-view-state-remove">
            <i class="<?php echo esc_attr($btn_icon_class_added); ?>" aria-hidden="true"></i>
        </div>
        <?php if ($show_loading): ?>
            <i class="loading fas fa-sync-alt fa-spin fa-fw" style="display: none;"></i>
        <?php endif; ?>
    </div>
    <?php
    
    return ob_get_clean();
}
add_shortcode('alg_wc_wl_thumb_btn', 'alg_wc_wl_thumb_button_shortcode');

// SOLUTION 2: Override the default button template to use thumbnail style
function override_wishlist_button_template($template, $template_name, $args) {
    // Check if this is the wishlist button template
    if (strpos($template_name, 'toggle-btn') !== false) {
        // Force thumbnail template instead of default
        $plugin_path = WP_PLUGIN_DIR . '/wishlist-for-woocommerce/templates/';
        $thumb_template = $plugin_path . 'toggle-btn-thumb.php';
        
        if (file_exists($thumb_template)) {
            return $thumb_template;
        }
    }
    return $template;
}
add_filter('wc_get_template', 'override_wishlist_button_template', 10, 3);

// SOLUTION 3: Enhanced shortcode with more customization options
function alg_wc_wl_custom_thumb_button($atts = []) {
    $atts = shortcode_atts([
        'product_id' => '',
        'icon_add' => 'far fa-heart',
        'icon_remove' => 'fas fa-heart',
        'size' => 'medium', // small, medium, large
        'style' => 'default' // default, circle, square
    ], $atts);
    
    global $product;
    
    // Get product ID
    $product_id = !empty($atts['product_id']) ? 
                  intval($atts['product_id']) : 
                  ($product ? $product->get_id() : get_the_ID());
    
    if (!$product_id) {
        return '';
    }
    
    // Size classes
    $size_class = '';
    switch($atts['size']) {
        case 'small':
            $size_class = 'alg-wc-wl-btn-small';
            break;
        case 'large':
            $size_class = 'alg-wc-wl-btn-large';
            break;
        default:
            $size_class = 'alg-wc-wl-btn-medium';
    }
    
    // Style classes
    $style_class = '';
    switch($atts['style']) {
        case 'circle':
            $style_class = 'alg-wc-wl-btn-circle';
            break;
        case 'square':
            $style_class = 'alg-wc-wl-btn-square';
            break;
        default:
            $style_class = 'alg-wc-wl-btn-default';
    }
    
    $btn_class = "alg-wc-wl-toggle-btn alg-wc-wl-thumb-btn {$size_class} {$style_class}";
    
    ob_start();
    ?>
    <div data-item_id="<?php echo esc_attr($product_id); ?>" 
         data-action="add" 
         class="<?php echo esc_attr($btn_class); ?>" 
         style="display: inline-block; cursor: pointer; padding: 8px; transition: all 0.3s ease;">
        <div class="alg-wc-wl-view-state alg-wc-wl-view-state-add">
            <i class="<?php echo esc_attr($atts['icon_add']); ?>" aria-hidden="true"></i>
        </div>
        <div class="alg-wc-wl-view-state alg-wc-wl-view-state-remove">
            <i class="<?php echo esc_attr($atts['icon_remove']); ?>" aria-hidden="true"></i>
        </div>
        <i class="loading fas fa-sync-alt fa-spin fa-fw" style="display: none;"></i>
    </div>
    <?php
    
    return ob_get_clean();
}
add_shortcode('alg_wc_wl_custom_thumb', 'alg_wc_wl_custom_thumb_button');

// SOLUTION 4: Add CSS for better styling
function add_wishlist_thumb_button_styles() {
    ?>
    <style>
    .alg-wc-wl-thumb-btn {
        position: relative;
        display: inline-block;
        background: none;
        border: none;
        font-size: 18px;
        color: #999;
        transition: color 0.3s ease;
    }
    
    .alg-wc-wl-thumb-btn:hover {
        color: #e74c3c;
    }
    
    .alg-wc-wl-thumb-btn.alg-wc-wl-btn-small {
        font-size: 14px;
    }
    
    .alg-wc-wl-thumb-btn.alg-wc-wl-btn-large {
        font-size: 24px;
    }
    
    .alg-wc-wl-thumb-btn.alg-wc-wl-btn-circle {
        background: #f8f8f8;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .alg-wc-wl-thumb-btn.alg-wc-wl-btn-square {
        background: #f8f8f8;
        border-radius: 4px;
        padding: 8px;
    }
    
    /* Hide states appropriately */
    .alg-wc-wl-thumb-btn .alg-wc-wl-view-state-remove {
        display: none;
    }
    
    .alg-wc-wl-thumb-btn.alg-wc-wl-item-added .alg-wc-wl-view-state-add {
        display: none;
    }
    
    .alg-wc-wl-thumb-btn.alg-wc-wl-item-added .alg-wc-wl-view-state-remove {
        display: block;
    }
    
    .alg-wc-wl-thumb-btn.alg-wc-wl-item-added {
        color: #e74c3c;
    }
    </style>
    <?php
}
add_action('wp_head', 'add_wishlist_thumb_button_styles');

// SOLUTION 5: Filter to force thumbnail template globally
function force_wishlist_thumbnail_style($btn_template) {
    // This will make all wishlist buttons use thumbnail style
    return 'thumb'; // or whatever template name the plugin uses for thumbnails
}
add_filter('alg_wc_wl_toggle_btn_template', 'force_wishlist_thumbnail_style');
?>