<?php
/**
 * Plugin Name: New & Hot
 * Description: Upload 4 “New & Hot” images with previews, and access them via keys like newAndHot-1 on the frontend.
 * Version: 1.0.0
 * Author: Printlana
 * License: GPLv2 or later
 */

if (!defined('ABSPATH'))
    exit;

class PL_New_And_Hot
{
    const KEYS = ['newAndHot-1', 'newAndHot-2', 'newAndHot-3', 'newAndHot-4'];
    const OPTS = ['newandhot_1', 'newandhot_2', 'newandhot_3', 'newandhot_4'];
    const TITLES = ['newandhot_title_1', 'newandhot_title_2', 'newandhot_title_3', 'newandhot_title_4'];
    const DESCS = ['newandhot_desc_1', 'newandhot_desc_2', 'newandhot_desc_3', 'newandhot_desc_4'];

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);

        // Frontend helpers
        add_shortcode('newandhot', [$this, 'sc_img']);
        add_shortcode('newandhot_url', [$this, 'sc_url']);
        add_action('rest_api_init', [$this, 'register_rest']);

    }

    /** ---------------- Admin ---------------- */

    public function add_menu()
    {
        // Top-level menu: New & Hot
        add_menu_page(
            'New & Hot',                 // Page title
            'New & Hot',                 // Menu title
            'manage_options',            // Capability
            'pl-new-and-hot',            // Menu slug
            [$this, 'render_page'],       // Callback
            'dashicons-fire',            // Dashicon (🔥-ish)
            26                           // Position (below Comments; adjust if you like)
        );

        // Optional: also add a submenu "All Images" pointing to the same page
        add_submenu_page(
            'pl-new-and-hot',
            'New & Hot',
            'All Images',
            'manage_options',
            'pl-new-and-hot',
            [$this, 'render_page']
        );
    }


    public function register_settings()
    {
        foreach (self::OPTS as $opt) {
            register_setting('pl_newandhot_group', $opt, [
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 0,
            ]);
        }
        // Register title/description options
        for ($i = 0; $i < 4; $i++) {
            register_setting('pl_newandhot_group', self::TITLES[$i], [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]);
            register_setting('pl_newandhot_group', self::DESCS[$i], [
                'type' => 'string',
                'sanitize_callback' => 'wp_kses_post', // allow basic markup if needed
                'default' => '',
            ]);
        }


        add_settings_section('pl_newandhot_section', 'New & Hot Images', function () {
            echo '<p>Upload up to four images. Each field shows a live preview.</p>';
        }, 'pl-new-and-hot');

        for ($i = 1; $i <= 4; $i++) {
            add_settings_field(
                'pl_newandhot_field_' . $i,
                'Image ' . $i,
                [$this, 'field_uploader'],
                'pl-new-and-hot',
                'pl_newandhot_section',
                ['index' => $i]
            );
        }
        // Text fields: Title + Description per image
        for ($i = 1; $i <= 4; $i++) {
            add_settings_field(
                'pl_newandhot_title_' . $i,
                'Title ' . $i,
                [$this, 'field_text'],
                'pl-new-and-hot',
                'pl_newandhot_section',
                ['index' => $i, 'type' => 'title']
            );
            add_settings_field(
                'pl_newandhot_desc_' . $i,
                'Description ' . $i,
                [$this, 'field_textarea'],
                'pl-new-and-hot',
                'pl_newandhot_section',
                ['index' => $i]
            );
        }

    }

    public function admin_assets($hook)
    {
        if ($hook !== 'toplevel_page_pl-new-and-hot')
            return;
        wp_enqueue_media();
        wp_enqueue_style('pl-newhot-admin', plugin_dir_url(__FILE__) . 'assets/admin.css', [], '1.0.0');
        wp_enqueue_script('pl-newhot-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', ['jquery'], '1.0.0', true);
    }


    public function field_uploader($args)
    {
        $i = (int) $args['index'];
        $opt_key_image = self::OPTS[$i - 1];    // newandhot_#
        $opt_key_title = self::TITLES[$i - 1];  // newandhot_title_#
        $opt_key_desc = self::DESCS[$i - 1];   // newandhot_desc_#

        $attachment_id = (int) get_option($opt_key_image, 0);
        $title_val = get_option($opt_key_title, '');
        $desc_val = get_option($opt_key_desc, '');
        $url = $attachment_id ? wp_get_attachment_image_url($attachment_id, 'medium') : '';

        ?>
        <div class="pl-nh-controls">
            <label for="pl_nh_title_<?php echo $i; ?>"><strong>Title <?php echo $i; ?></strong></label>
            <input type="text" id="pl_nh_title_<?php echo $i; ?>" class="regular-text"
                name="<?php echo esc_attr($opt_key_title); ?>" value="<?php echo esc_attr($title_val); ?>"
                style="width:100%;" />

            <label for="pl_nh_desc_<?php echo $i; ?>"><strong>Description <?php echo $i; ?></strong></label>
            <textarea id="pl_nh_desc_<?php echo $i; ?>" class="large-text" rows="3"
                name="<?php echo esc_attr($opt_key_desc); ?>"
                style="resize:none;"><?php echo esc_textarea($desc_val); ?></textarea>

            <label for="pl_newandhot_<?php echo $i; ?>"><strong>Image <?php echo $i; ?></strong></label>
            <input type="hidden" id="pl_newandhot_<?php echo $i; ?>" name="<?php echo esc_attr($opt_key_image); ?>"
                value="<?php echo esc_attr($attachment_id); ?>" />
            <?php if ($url): ?>
                <div class="pl-nh-inline-preview" style="margin-top:8px;">
                    <img src="<?php echo esc_url($url); ?>" alt="Preview <?php echo esc_attr($i); ?>" />
                </div>
            <?php else: ?>
                <div class="pl-nh-inline-preview" style="margin-top:8px;">
                    <img src="https://printlana.com/wp-content/uploads/2025/09/placeholder-3.png" alt="Preview <?php echo esc_attr($i); ?>" />
                </div>
            <?php endif; ?>
            <?php if (!$url): ?>
                <button type="button" class="button pl-nh-upload"
                    data-target="#pl_newandhot_<?php echo $i; ?>">Upload/Choose</button>
            <?php endif; ?>
            <?php if ($url): ?>
                <button type="button" class="button button-secondary pl-nh-remove"
                    data-target="#pl_newandhot_<?php echo $i; ?>">Remove</button>
            <?php endif; ?>

        </div>
        <?php
    }

    public function field_preview($args)
    {
        $i = (int) $args['index'];
        $opt_key = self::OPTS[$i - 1];
        $attachment_id = (int) get_option($opt_key, 0);
        $url = $attachment_id ? wp_get_attachment_image_url($attachment_id, 'medium') : '';
        $title = get_option(self::TITLES[$i - 1], '');
        $desc = get_option(self::DESCS[$i - 1], '');

        ?>
        <div class="pl-nh-item <?php echo 'preview' . $i ?>" data-index="<?php echo esc_attr($i); ?>">
            <div class="pl-nh-preview">
                <?php if ($url): ?>
                    <img src="<?php echo esc_url($url); ?>" alt="Preview <?php echo esc_attr($i); ?>" />
                <?php else: ?>
                    <div class="pl-nh-placeholder">No image <?php echo $i ?></div>
                <?php endif; ?>
                <?php if ($title): ?><strong><?php echo esc_html($title); ?></strong><?php endif; ?>
                <?php if ($desc): ?>
                    <div class="description"><?php echo esc_html(wp_strip_all_tags($desc)); ?></div><?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function render_page()
    {
        if (!current_user_can('manage_options'))
            wp_die(__('Unauthorized', 'new-and-hot'));
        ?>
        <div class="wrap pl-nh-wrap">
            <h1>New & Hot</h1>
            <form class="pl-nh-form" action="options.php" method="post">
                <div>
                    first image: 1/1 ratio.
                </div>
                <div>
                    second image: 1/1 ratio.
                </div>
                <div>
                    third image: 2/1 ratio.
                </div>
                <div>
                    fourth image: 1/1 ratio.
                </div>
                <?php
                settings_fields('pl_newandhot_group');
                ?>
                <div class="pl-nh-grid">
                    <?php
                    // Render the four uploader fields inline
                    for ($i = 1; $i <= 4; $i++) {
                        $this->field_uploader(['index' => $i]);
                    }
                    ?>
                </div>
                <div class="pl-nh-grid-preview">
                    <?php
                    // Render the four uploader fields inline
                    for ($i = 1; $i <= 4; $i++) {
                        $this->field_preview(['index' => $i]);
                    }
                    ?>
                </div>
                <?php submit_button('Save Changes'); ?>
            </form>
            <hr>
            <h2>How to use</h2>
            <ol>
                <li>Template helper: <code>&lt;?php echo esc_url( newandhot_get('newAndHot-1') ); ?&gt;</code></li>
                <li>Shortcode (image tag): <code>[newandhot key="newAndHot-1" alt="New item"]</code></li>
                <li>Shortcode (URL only): <code>[newandhot_url key="newAndHot-1"]</code></li>
                <li>REST: <code>/wp-json/newandhot/v1/get?key=newAndHot-1</code></li>
            </ol>
        </div>
        <?php
    }


    /** ---------------- Frontend API ---------------- */

    /**
     * Return the image URL by key (public helper).
     * @param string $key e.g. newAndHot-1 .. newAndHot-4
     * @param string $size any WP size (default 'full')
     * @return string URL or empty
     */
    public static function get_url_by_key($key, $size = 'full')
    {
        $map = array_combine(self::KEYS, self::OPTS);
        if (!isset($map[$key]))
            return '';
        $id = (int) get_option($map[$key], 0);
        if (!$id)
            return '';
        $url = wp_get_attachment_image_url($id, $size);
        return $url ?: '';
    }

    /** [newandhot key="newAndHot-1" size="medium" alt=""] */
    public function sc_img($atts)
    {
        $a = shortcode_atts([
            'key' => 'newAndHot-1',
            'size' => 'large',
            'alt' => '',
            'class' => 'newandhot-img',
            'decoding' => 'async',
            'loading' => 'lazy',
        ], $atts, 'newandhot');

        $url = self::get_url_by_key($a['key'], $a['size']);
        if (!$url)
            return '';
        $alt = esc_attr($a['alt']);
        $class = esc_attr($a['class']);
        $decoding = esc_attr($a['decoding']);
        $loading = esc_attr($a['loading']);
        return sprintf('<img src="%s" alt="%s" class="%s" decoding="%s" loading="%s">', esc_url($url), $alt, $class, $decoding, $loading);
    }

    /** [newandhot_url key="newAndHot-1" size="full"] */
    public function sc_url($atts)
    {
        $a = shortcode_atts([
            'key' => 'newAndHot-1',
            'size' => 'full',
        ], $atts, 'newandhot_url');
        $url = self::get_url_by_key($a['key'], $a['size']);
        return esc_url($url);
    }

    /** REST: /wp-json/newandhot/v1/get?key=newAndHot-1&size=large */
    public function register_rest()
    {
        register_rest_route('newandhot/v1', '/get', [
            'methods' => 'GET',
            'callback' => function (\WP_REST_Request $req) {
                $key = $req->get_param('key') ?: 'newAndHot-1';
                $size = $req->get_param('size') ?: 'full';
                $url = self::get_url_by_key($key, $size);

                // Map key -> index (1..4)
                $idx = array_search($key, self::KEYS, true);
                if ($idx === false) {
                    $idx = 0;
                } // default to first
    
                $title = get_option(self::TITLES[$idx], '');
                $desc = get_option(self::DESCS[$idx], '');

                return rest_ensure_response([
                    'key' => $key,
                    'size' => $size,
                    'url' => $url,
                    'title' => $title,
                    'description' => wpautop($desc), // format nicely for consumers
                ]);
            },
            'args' => [
                'key' => ['type' => 'string', 'required' => false],
                'size' => ['type' => 'string', 'required' => false],
            ],

        ]);
    }
}
new PL_New_And_Hot();

/** -------- Template helper function -------- */
/**
 * Get a New & Hot image URL by key.
 * Usage: echo newandhot_get('newAndHot-1', 'large');
 */
function newandhot_get($key = 'newAndHot-1', $size = 'full')
{
    if (!class_exists('PL_New_And_Hot'))
        return '';
    return PL_New_And_Hot::get_url_by_key($key, $size);
}

// === Elementor Dynamic Tags: New & Hot (Image + URL) ===
add_action('elementor/dynamic_tags/register', function ($dynamic_tags) {
    // Ensure Elementor dynamic tag base exists (prevents fatal if Elementor/Pro is inactive or not loaded yet)
    if (!class_exists('\Elementor\Core\DynamicTags\Tag') || !class_exists('\Elementor\Modules\DynamicTags\Module')) {
        return;
    }

    // ----- IMAGE TAG -----
    if (!class_exists('PL_NewAndHot_Image_Tag')) {
        class PL_NewAndHot_Image_Tag extends \Elementor\Core\DynamicTags\Tag
        {
            public function get_name()
            {
                return 'pl_newandhot_image';
            }

            public function get_title()
            {
                return __('New & Hot Image', 'new-and-hot');
            }

            public function get_group()
            {
                return 'site';
            }

            public function get_categories()
            {
                // IMAGE_CATEGORY is for <img> and backgrounds
                return [\Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY];
            }

            protected function register_controls()
            {
                $this->add_control('key', [
                    'label' => __('Select Image', 'new-and-hot'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'newAndHot-1' => 'New & Hot 1',
                        'newAndHot-2' => 'New & Hot 2',
                        'newAndHot-3' => 'New & Hot 3',
                        'newAndHot-4' => 'New & Hot 4',
                    ],
                    'default' => 'newAndHot-1',
                ]);

                $this->add_control('size', [
                    'label' => __('Image Size', 'new-and-hot'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'thumbnail' => 'Thumbnail',
                        'medium' => 'Medium',
                        'large' => 'Large',
                        'full' => 'Full',
                    ],
                    'default' => 'large',
                ]);
            }

            public function get_value(array $options = [])
            {
                $key = $this->get_settings('key') ?: 'newAndHot-1';
                $size = $this->get_settings('size') ?: 'full';

                if (!function_exists('newandhot_get'))
                    return [];

                // Map key -> option name, then get attachment ID
                $map = [
                    'newAndHot-1' => 'newandhot_1',
                    'newAndHot-2' => 'newandhot_2',
                    'newAndHot-3' => 'newandhot_3',
                    'newAndHot-4' => 'newandhot_4',
                ];
                $opt = $map[$key] ?? 'newandhot_1';
                $id = (int) get_option($opt, 0);
                if (!$id)
                    return [];

                // Get the requested size URL + dims
                $src = wp_get_attachment_image_src($id, $size);
                if (!$src || empty($src[0]))
                    return [];

                // Also fetch common sizes to help Elementor pick variants if needed
                $sizes = ['thumbnail', 'medium', 'large', 'full'];
                $sizes_obj = [];
                foreach ($sizes as $s) {
                    $info = wp_get_attachment_image_src($id, $s);
                    if ($info && !empty($info[0])) {
                        $sizes_obj[$s] = [
                            'url' => esc_url_raw($info[0]),
                            'width' => isset($info[1]) ? (int) $info[1] : null,
                            'height' => isset($info[2]) ? (int) $info[2] : null,
                            'id' => $id,
                        ];
                    }
                }

                // ➕ Add title/description + correct main URL
                $idx = array_search($key, PL_New_And_Hot::KEYS, true);
                if ($idx === false) {
                    $idx = 0;
                }

                $title = get_option(PL_New_And_Hot::TITLES[$idx], '');
                $desc = get_option(PL_New_And_Hot::DESCS[$idx], '');

                $main = wp_get_attachment_image_src($id, $size);

                return [
                    'id' => $id,
                    'url' => esc_url_raw($main ? $main[0] : ''),
                    'sizes' => $sizes_obj,
                    'size' => $size,
                    'title' => $title,
                    'description' => $desc,
                ];
            }


        }
    }


    $dynamic_tags->register_tag('PL_NewAndHot_Image_Tag');

    // ----- URL TAG (optional but handy) -----
    if (!class_exists('PL_NewAndHot_URL_Tag')) {
        class PL_NewAndHot_URL_Tag extends \Elementor\Core\DynamicTags\Tag
        {
            public function get_name()
            {
                return 'pl_newandhot_url';
            }
            public function get_title()
            {
                return __('New & Hot Image URL', 'new-and-hot');
            }
            public function get_group()
            {
                return 'site';
            }
            public function get_categories()
            {
                return [\Elementor\Modules\DynamicTags\Module::URL_CATEGORY];
            }

            protected function register_controls()
            {
                $this->add_control('key', [
                    'label' => __('Select Image', 'new-and-hot'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'newAndHot-1' => 'New & Hot 1',
                        'newAndHot-2' => 'New & Hot 2',
                        'newAndHot-3' => 'New & Hot 3',
                        'newAndHot-4' => 'New & Hot 4',
                    ],
                    'default' => 'newAndHot-1',
                ]);
                $this->add_control('size', [
                    'label' => __('Size', 'new-and-hot'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'thumbnail' => 'Thumbnail',
                        'medium' => 'Medium',
                        'large' => 'Large',
                        'full' => 'Full',
                    ],
                    'default' => 'full',
                ]);
            }

            public function render()
            {
                $key = $this->get_settings('key') ?: 'newAndHot-1';
                $size = $this->get_settings('size') ?: 'full';
                if (!function_exists('newandhot_get')) {
                    echo '';
                    return;
                }
                echo esc_url(newandhot_get($key, $size));
            }
        }
    }
    $dynamic_tags->register_tag('PL_NewAndHot_URL_Tag');
}, 20); // priority ensures Elementor is ready
// === Shortcodes for Title and Description ===
add_shortcode('newandhot_title', function ($atts) {
    $a = shortcode_atts(['key' => 'newAndHot-1'], $atts, 'newandhot_title');
    $idx = array_search($a['key'], PL_New_And_Hot::KEYS, true);
    if ($idx === false) {
        return '';
    }
    return esc_html(get_option(PL_New_And_Hot::TITLES[$idx], ''));
});

add_shortcode('newandhot_desc', function ($atts) {
    $a = shortcode_atts(['key' => 'newAndHot-1'], $atts, 'newandhot_desc');
    $idx = array_search($a['key'], PL_New_And_Hot::KEYS, true);
    if ($idx === false) {
        return '';
    }
    // allow basic formatting
    return wpautop(wp_kses_post(get_option(PL_New_And_Hot::DESCS[$idx], '')));
});
