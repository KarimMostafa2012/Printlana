<?php namespace EmailLog\Core;

use EmailLog\Core\DB\TableManager;
use EmailLog\EmailLogAutoloader;

/**
 * The main plugin class.
 *
 * @since Genesis
 */
class EmailLog {

	/**
	 * Plugin Version number.
	 *
	 * @since Genesis
	 *
	 * @var string
	 */
	const VERSION = '2.4.9';

	/**
	 * Email Log Store URL.
	 */
	const STORE_URL = 'https://wpemaillog.com';

	/**
	 * Flag to track if the plugin is loaded.
	 *
	 * @since 2.0
	 * @access private
	 *
	 * @var bool
	 */
	private $loaded = false;

	/**
	 * Flag to override plugin API.
	 *
	 * @since 2.4.5
	 * @access private
	 *
	 * @var bool
	 */
	private $plugins_api_overridden = false;

	/**
	 * Plugin file path.
	 *
	 * @since 2.0
	 * @access private
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Filesystem directory path where translations are stored.
	 *
	 * @since 2.0
	 *
	 * @var string
	 */
	public $translations_path;

	/**
	 * Auto loader.
	 *
	 * @var \EmailLog\EmailLogAutoloader
	 */
	public $loader;

	/**
	 * Database Table Manager.
	 *
	 * @since 2.0
	 *
	 * @var \EmailLog\Core\DB\TableManager
	 */
	public $table_manager;

	/**
	 * List of loadies.
	 *
	 * @var Loadie[]
	 */
	private $loadies = array();
    private $loadies_init = array();
	/**
	 * Initialize the plugin.
	 *
	 * @param string             $file          Plugin file.
	 * @param EmailLogAutoloader $loader        EmailLog Autoloader.
	 * @param TableManager       $table_manager Table Manager.
	 */
	public function __construct( $file, $loader, $table_manager ) {
		$this->plugin_file   = $file;
		$this->loader        = $loader;
		$this->table_manager = $table_manager;

		$this->add_loadie( $table_manager );

		$this->translations_path = dirname( plugin_basename( $this->plugin_file ) ) . '/languages/' ;
	}

	/**
	 * Add an Email Log Loadie.
	 * The `load()` method of the Loadies will be called when Email Log is loaded.
	 *
	 * @param \EmailLog\Core\Loadie $loadie Loadie to be loaded.
	 *
	 * @return bool False if Email Log is already loaded or if $loadie is not of `Loadie` type. True otherwise.
	 */
	public function add_loadie( $loadie, $loadie_init = false ) {
		if ( $this->loaded ) {
			return false;
		}

		if ( ! $loadie instanceof Loadie ) {
			return false;
		}
		
        if($loadie_init === true){
            $this->loadies_init[] = $loadie;
        } else {
            $this->loadies[] = $loadie;
        }

		return true;
	}

	/**
	 * Load the plugin.
	 */
	public function load() {
		if ( $this->loaded ) {
			return;
		}

		load_plugin_textdomain( 'email-log', false, $this->translations_path );

		$this->table_manager->load();

		foreach ( $this->loadies as $loadie ) {
            $loadie->load();
		}

        foreach ( $this->loadies_init as $loadie_init ) {
            add_action('init', array($loadie_init, 'load'));
		}

		$this->loaded = true;

		/**
		 * Email Log plugin loaded.
		 *
		 * @since 2.0
		 */
		do_action( 'el_loaded' );
	}

	/**
	 * Plugin API has been overridden.
	 *
	 * @since 2.4.5
	 */
	public function plugin_api_overridden() {
		$this->plugins_api_overridden = true;
	}

	/**
	 * Has the plugin API have been overridden?
	 *
	 * @since 2.4.5
	 *
	 * @return bool True if overridden, False otherwise.
	 */
	public function is_plugin_api_overridden() {
		return $this->plugins_api_overridden;
	}

	/**
	 * Return Email Log version.
	 *
	 * @return string Email Log Version.
	 */
	public function get_version() {
		return self::VERSION;
	}

	/**
	 * Return the Email Log plugin directory path.
	 *
	 * @return string Plugin directory path.
	 */
	public function get_plugin_path() {
		return plugin_dir_path( $this->plugin_file );
	}

	/**
	 * Return the Email Log plugin file.
	 *
	 * @since 2.0.0
	 *
	 * @return string Plugin directory path.
	 */
	public function get_plugin_file() {
		return $this->plugin_file;
	}

	/**
	 * Get Email Log Store URL.
	 *
	 * @since 2.0.0
	 *
	 * @return string Store URL
	 */
	public function get_store_url() {
		return self::STORE_URL;
	}

    public static function wp_kses_wf($html)
    {
        add_filter('safe_style_css', function ($styles) {
            $styles_wf = array(
                'text-align',
                'margin',
                'color',
                'float',
                'border',
                'background',
                'background-color',
                'border-bottom',
                'border-bottom-color',
                'border-bottom-style',
                'border-bottom-width',
                'border-collapse',
                'border-color',
                'border-left',
                'border-left-color',
                'border-left-style',
                'border-left-width',
                'border-right',
                'border-right-color',
                'border-right-style',
                'border-right-width',
                'border-spacing',
                'border-style',
                'border-top',
                'border-top-color',
                'border-top-style',
                'border-top-width',
                'border-width',
                'caption-side',
                'clear',
                'cursor',
                'direction',
                'font',
                'font-family',
                'font-size',
                'font-style',
                'font-variant',
                'font-weight',
                'height',
                'letter-spacing',
                'line-height',
                'margin-bottom',
                'margin-left',
                'margin-right',
                'margin-top',
                'overflow',
                'padding',
                'padding-bottom',
                'padding-left',
                'padding-right',
                'padding-top',
                'text-decoration',
                'text-indent',
                'vertical-align',
                'width',
                'display',
            );

            foreach ($styles_wf as $style_wf) {
                $styles[] = $style_wf;
            }
            return $styles;
        });

        $allowed_tags = wp_kses_allowed_html('post');
        $allowed_tags['input'] = array(
            'type' => true,
            'style' => true,
            'class' => true,
            'id' => true,
            'checked' => true,
            'disabled' => true,
            'name' => true,
            'size' => true,
            'placeholder' => true,
            'value' => true,
            'data-*' => true,
            'size' => true,
            'disabled' => true
        );

        $allowed_tags['textarea'] = array(
            'type' => true,
            'style' => true,
            'class' => true,
            'id' => true,
            'checked' => true,
            'disabled' => true,
            'name' => true,
            'size' => true,
            'placeholder' => true,
            'value' => true,
            'data-*' => true,
            'cols' => true,
            'rows' => true,
            'disabled' => true,
            'autocomplete' => true
        );

        $allowed_tags['select'] = array(
            'type' => true,
            'style' => true,
            'class' => true,
            'id' => true,
            'checked' => true,
            'disabled' => true,
            'name' => true,
            'size' => true,
            'placeholder' => true,
            'value' => true,
            'data-*' => true,
            'multiple' => true,
            'disabled' => true
        );

        $allowed_tags['option'] = array(
            'type' => true,
            'style' => true,
            'class' => true,
            'id' => true,
            'checked' => true,
            'disabled' => true,
            'name' => true,
            'size' => true,
            'placeholder' => true,
            'value' => true,
            'selected' => true,
            'data-*' => true
        );
        $allowed_tags['optgroup'] = array(
            'type' => true,
            'style' => true,
            'class' => true,
            'id' => true,
            'checked' => true,
            'disabled' => true,
            'name' => true,
            'size' => true,
            'placeholder' => true,
            'value' => true,
            'selected' => true,
            'data-*' => true,
            'label' => true
        );

        $allowed_tags['a'] = array(
            'href' => true,
            'data-*' => true,
            'class' => true,
            'style' => true,
            'id' => true,
            'target' => true,
            'data-*' => true,
            'role' => true,
            'aria-controls' => true,
            'aria-selected' => true,
            'disabled' => true
        );

        $allowed_tags['div'] = array(
            'style' => true,
            'class' => true,
            'id' => true,
            'data-*' => true,
            'role' => true,
            'aria-labelledby' => true,
            'value' => true,
            'aria-modal' => true,
            'tabindex' => true
        );

        $allowed_tags['li'] = array(
            'style' => true,
            'class' => true,
            'id' => true,
            'data-*' => true,
            'role' => true,
            'aria-labelledby' => true,
            'value' => true,
            'aria-modal' => true,
            'tabindex' => true
        );

        $allowed_tags['span'] = array(
            'style' => true,
            'class' => true,
            'id' => true,
            'data-*' => true,
            'aria-hidden' => true
        );

        $allowed_tags['style'] = array(
            'class' => true,
            'id' => true,
            'type' => true,
            'style' => true
        );

        $allowed_tags['fieldset'] = array(
            'class' => true,
            'id' => true,
            'type' => true,
            'style' => true
        );

        $allowed_tags['link'] = array(
            'class' => true,
            'id' => true,
            'type' => true,
            'rel' => true,
            'href' => true,
            'media' => true,
            'style' => true
        );

        $allowed_tags['form'] = array(
            'style' => true,
            'class' => true,
            'id' => true,
            'method' => true,
            'action' => true,
            'data-*' => true,
            'style' => true
        );

        $allowed_tags['script'] = array(
            'class' => true,
            'id' => true,
            'type' => true,
            'src' => true,
            'style' => true
        );

        $allowed_tags['table'] = array(
            'class' => true,
            'id' => true,
            'type' => true,
            'cellpadding' => true,
            'cellspacing' => true,
            'border' => true,
            'style' => true
        );

        $allowed_tags['canvas'] = array(
            'class' => true,
            'id' => true,
            'style' => true
        );

        echo wp_kses($html, $allowed_tags);

        add_filter('safe_style_css', function ($styles) {
            $styles_wf = array(
                'text-align',
                'margin',
                'color',
                'float',
                'border',
                'background',
                'background-color',
                'border-bottom',
                'border-bottom-color',
                'border-bottom-style',
                'border-bottom-width',
                'border-collapse',
                'border-color',
                'border-left',
                'border-left-color',
                'border-left-style',
                'border-left-width',
                'border-right',
                'border-right-color',
                'border-right-style',
                'border-right-width',
                'border-spacing',
                'border-style',
                'border-top',
                'border-top-color',
                'border-top-style',
                'border-top-width',
                'border-width',
                'caption-side',
                'clear',
                'cursor',
                'direction',
                'font',
                'font-family',
                'font-size',
                'font-style',
                'font-variant',
                'font-weight',
                'height',
                'letter-spacing',
                'line-height',
                'margin-bottom',
                'margin-left',
                'margin-right',
                'margin-top',
                'overflow',
                'padding',
                'padding-bottom',
                'padding-left',
                'padding-right',
                'padding-top',
                'text-decoration',
                'text-indent',
                'vertical-align',
                'width'
            );

            foreach ($styles_wf as $style_wf) {
                if (($key = array_search($style_wf, $styles)) !== false) {
                    unset($styles[$key]);
                }
            }
            return $styles;
        });
    }
}
