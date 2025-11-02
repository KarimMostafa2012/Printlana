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
        $opt_key = self::OPTS[$i - 1];
        $attachment_id = (int) get_option($opt_key, 0);
        $url = $attachment_id ? wp_get_attachment_image_url($attachment_id, 'medium') : '';

        ?>
        <div class="pl-nh-controls">
            <div>
                image number <?php echo $i ?>
            </div>
            <input type="hidden" id="pl_newandhot_<?php echo $i; ?>" name="<?php echo esc_attr($opt_key); ?>"
                value="<?php echo esc_attr($attachment_id); ?>">
            <button type="button" class="button pl-nh-upload"
                data-target="#pl_newandhot_<?php echo $i; ?>">Upload/Choose</button>
            <button type="button" class="button button-secondary pl-nh-remove"
                data-target="#pl_newandhot_<?php echo $i; ?>">Remove</button>
        </div>
        <?php
    }

    public function field_preview($args)
    {
        $i = (int) $args['index'];
        $opt_key = self::OPTS[$i - 1];
        $attachment_id = (int) get_option($opt_key, 0);
        $url = $attachment_id ? wp_get_attachment_image_url($attachment_id, 'medium') : '';

        ?>
        <div class="pl-nh-item <?php echo 'preview' . $i ?>" data-index="<?php echo esc_attr($i); ?>">
            <div class="pl-nh-preview">
                <?php if ($url): ?>
                    <img src="<?php echo esc_url($url); ?>" alt="Preview <?php echo esc_attr($i); ?>" />
                <?php else: ?>
                    <div class="pl-nh-placeholder">No image <?php echo $i ?></div>
                <?php endif; ?>
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
                return rest_ensure_response([
                    'key' => $key,
                    'size' => $size,
                    'url' => $url,
                ]);
            },
            'permission_callback' => '__return_true',
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
                    'label' => __('Size', 'new-and-hot'),
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

                if (!function_exists('newandhot_get')) {
                    return [];
                }

                $url = newandhot_get($key, $size);
                if (empty($url)) {
                    return [];
                }

                // Return a real array (not JSON)
                return [
                    'id' => 0,
                    'url' => esc_url_raw($url),
                    'size' => $size,
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
