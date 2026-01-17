<?php
namespace WpAssetCleanUpPro;

use WpAssetCleanUp\Misc;

/**
 * Class PluginPro
 * @package WpAssetCleanUpPro
 */
class PluginPro
{
	/**
	 * @var string
	 */
	public static $muPluginFileName = 'wpacu-plugins-filter.php';

	/**
	 * PluginPro constructor.
	 */
	public function __construct()
	{
		// Only trigger when a plugin page is accessed within the Dashboard
		if (isset($_GET['page']) && is_string($_GET['page']) && is_admin() && (strpos($_GET['page'], WPACU_PLUGIN_ID.'_') !== false)) {
			self::copyMuPluginFilter();
		}

		// e.g. Plugin update failed notice instructions
		add_action('admin_footer', array($this, 'adminFooter'));

		add_action( 'upgrader_process_complete', static function( $upgrader_object, $options ) {
			self::copyMuPluginFilter();
		}, 10, 2 );

		register_activation_hook(WPACU_PLUGIN_FILE, array($this, 'whenActivated'));
		register_deactivation_hook(WPACU_PLUGIN_FILE, array($this, 'whenDeactivated'));

        }

    /**
	 *
	 */
	public function init()
    {
	    add_filter('plugin_action_links_'.WPACU_PLUGIN_BASE, array($this, 'addActionLinksInPluginsPage'), 11);

	    if (is_admin() && strpos($_SERVER['REQUEST_URI'], 'update-core.php') !== false) {
		    add_action('admin_head', array($this, 'pluginIconUpdateCorePage'));
	    }
    }

	/**
	 *
	 */
	public static function copyMuPluginFilter()
	{
		// Isn't the MU plugin there? Copy it
		$copyFrom = dirname( WPACU_PLUGIN_FILE ) . '/pro/mu-plugins/to-copy/' . self::$muPluginFileName;
		$copyTo   = WPMU_PLUGIN_DIR . '/' . self::$muPluginFileName;

		if (! is_file(WPMU_PLUGIN_DIR . '/' . self::$muPluginFileName)) {
			// MU plugins directory has to be there first
			if (! is_dir( WPMU_PLUGIN_DIR )) {
				// Attempt directory creation
				$muPluginsCreateDir = ( @mkdir(WPMU_PLUGIN_DIR, FS_CHMOD_DIR ) && is_dir( WPMU_PLUGIN_DIR ) );

				if ( $muPluginsCreateDir ) {
					@copy( $copyFrom, $copyTo );
					return;
				}

				// The directory couldn't be created / The error will be shown from /classes/PluginsManager.php
				return;
			}

			// MU plugin directory was already created; copy the MU plugin
			@copy( $copyFrom, $copyTo );
		}
	}

	/**
	 * Replaces default plugin icon ('Dashicons' type) with the actual Asset CleanUp Pro icon
	 */
	public function pluginIconUpdateCorePage()
	{
		?>
		<style <?php echo Misc::getStyleTypeAttribute(); ?>>
            .wp-asset-clean-up-pro.plugin-title .dashicons.dashicons-admin-plugins {
                position: relative;
            }

            .wp-asset-clean-up-pro.plugin-title .dashicons.dashicons-admin-plugins::before {
                content: '';
                position: absolute;
                background: transparent url('https://ps.w.org/wp-asset-clean-up/assets/icon-256x256.png') no-repeat 0 0;
                height: 100%;
                left: 0;
                top: 0;
                width: 100%;
                background-size: cover;
                max-width: 60px;
                max-height: 60px;
                box-shadow: 0 0 0 0 transparent;
            }
		</style>

		<script type="text/javascript">
            jQuery(document).ready(function($) {
                // Append the right class to the plugin row so the CSS above would take effect
                $('input[value="wp-asset-clean-up-pro/wpacu.php"]').parent().next().addClass('wp-asset-clean-up-pro');
            });
		</script>
		<?php
	}

	/**
	 *
	 */
	public function adminFooter()
	{
		$isPluginsAdminPage = is_admin() && isset($_SERVER['REQUEST_URI']) && (strpos($_SERVER['REQUEST_URI'], '/plugins.php') !== false);

		if ( ! $isPluginsAdminPage ) {
			return;
		}

		$wpUpdatesUrl = esc_url(admin_url( 'update-core.php' ));
		?>
        <span style="display: none;" id="wpacu-try-alt-plugin-update">
            &nbsp;&nbsp;Please do the following actions, depending on the error you've noticed:<br/>
            <span style="display: block; margin-bottom: 11px; margin-top: 11px;">&#10141; <strong>"<?php echo __( 'Plugin update failed.' ); ?>", "<?php echo __( 'The plugin is at the latest version.' ); ?>":</strong>&nbsp;Go to <em><a target="_blank" href="<?php echo esc_url( $wpUpdatesUrl ); ?>">"Dashboard" &#187; "Updates"</a></em>, tick the corresponding plugin checkbox and use the <em>"Update Plugins"</em> button. This will reload the page and there are higher chances the plugin will update, thus avoiding any timeout.</span>
            <span style="display: block; margin-bottom: 10px;">&#10141; <strong>"Unauthorized":</strong> It is likely that you are trying to update the plugin for a website that is not active in the system, although it is marked as "active" on your end (e.g. you moved it from Staging to Live, and it remained marked as active in the records). Please go to <em><a target="_blank" href="https://www.gabelivan.com/customer-dashboard/">Customer Dashboard</a> -&gt; Purchase History -&gt; View Licenses</em> to manage the active websites or deactivate the license from the website you made the import from and re-activate it here, on the current website.</span>
        </span>
		<?php
		$wpacuProDataPlugin = WPACU_PLUGIN_BASE;
		$wpacuProDataPluginBase = substr(strrchr(WPACU_PLUGIN_BASE, '/'), 1);
		?>
		<script type="text/javascript">
            jQuery(document).ready( function($) {
                $(document).ajaxComplete(function(event, xhr, settings) {
                    var $wpacuTryAltPluginUpdateElement = $('#wpacu-try-alt-plugin-update'),
                        $wpacuPluginUpdateFailedElement = $('tr.plugin-update-tr[data-plugin="<?php echo esc_js($wpacuProDataPlugin); ?>"]')
                            .find('.update-message.notice-error > p');

                    if ($wpacuPluginUpdateFailedElement.length > 0 && settings.url.indexOf('admin-ajax.php') !== -1
                        && xhr.responseText.indexOf('<?php echo esc_js($wpacuProDataPluginBase); ?>') !== -1
                        && xhr.responseText.indexOf('errorMessage') !== -1
                    ) {
                        setTimeout(function() {
                            $wpacuPluginUpdateFailedElement.append($wpacuTryAltPluginUpdateElement);
                            $wpacuTryAltPluginUpdateElement.show();
                        }, 100);
                    }
                });
            });
		</script>
		<?php
	}

	/**
	 * Copy/Update the MU plugin file
	 */
	public function whenActivated()
	{
		self::copyMuPluginFilter();
	}

	/**
	 * Remove the MU plugin file
	 */
	public function whenDeactivated()
	{
		@unlink(WPMU_PLUGIN_DIR.'/'.self::$muPluginFileName);
	}

	/**
	 * @param $links
	 *
	 * @return mixed
	 */
	public static function addActionLinksInPluginsPage($links)
    {
        // [License Related]
	    $licenseStatus  = get_option( WPACU_PLUGIN_ID . '_pro_license_status');

	    $activateLicenseSvg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" focusable="false" width="20px" height="20px" style="position: absolute; left: 0; top: -2px; -ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 20 20"><path d="M10 2c4.42 0 8 3.58 8 8s-3.58 8-8 8-8-3.58-8-8 3.58-8 8-8zm1.13 9.38l.35-6.46H8.52l.35 6.46h2.26zm-.09 3.36c.24-.23.37-.55.37-.96 0-.42-.12-.74-.36-.97s-.59-.35-1.06-.35-.82.12-1.07.35-.37.55-.37.97c0 .41.13.73.38.96.26.23.61.34 1.06.34s.8-.11 1.05-.34z" fill="#c00"/><rect x="0" y="0" width="20" height="20" fill="rgba(0, 0, 0, 0)" /></svg>
SVG;

	    $renewExpiredLicense = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px" style="position: absolute; left: 0; top: -2px; -ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg); fill: green;" viewBox="0 0 20 20"><rect x="0" fill="none" width="20" height="20"/><g><path d="M10.2 3.28c3.53 0 6.43 2.61 6.92 6h2.08l-3.5 4-3.5-4h2.32c-.45-1.97-2.21-3.45-4.32-3.45-1.45 0-2.73.71-3.54 1.78L4.95 5.66C6.23 4.2 8.11 3.28 10.2 3.28zm-.4 13.44c-3.52 0-6.43-2.61-6.92-6H.8l3.5-4c1.17 1.33 2.33 2.67 3.5 4H5.48c.45 1.97 2.21 3.45 4.32 3.45 1.45 0 2.73-.71 3.54-1.78l1.71 1.95c-1.28 1.46-3.15 2.38-5.25 2.38z"/></g></svg>
SVG;

	    if ($licenseStatus !== 'valid') {
		    if ($licenseStatus === 'expired') {
                $renewalLinkDefault = 'https://www.gabelivan.com/customer-dashboard/license-update/';
			    $renewalLink = get_option(WPACU_PLUGIN_ID . '_pro_license_renewal_link');

                // If there is no renewal link, or it's not valid, use the default one
                if ( ! ( $renewalLink && (strpos($renewalLink, 'wpacu_str_one=') !== false && strpos($renewalLink, 'wpacu_str_two=') !== false) ) ) {
                    $renewalLink = $renewalLinkDefault;
                }

			    $links['renew_license'] = '<a data-wpacu-renewal-link="true" target="_blank" href="'.$renewalLink.'" style="font-weight: bold; color: green; position: relative; padding-left: 23px;">'.$renewExpiredLicense.' Renew license, save 15%</a><div style="margin-top: 2px;"><small style="color: green; font-style: italic; font-weight: 400;">An active license allows you to get the latest updates &amp; bug fixes from the Dashboard</small></div>';
		    } elseif($licenseStatus === 'disabled') {
			    $links['disabled_license'] = '<div style="margin-top: 2px;"><small style="color: #32373c; font-style: italic; font-weight: 400;">It looks like the license has been disabled. It usually happens when a refund has been issued.</small><br /><small style="color: #32373c; font-style: italic; font-weight: 400;"> If you believe this is a mistake and the license should be active, <a target="_blank" href="https://www.gabelivan.com/contact/">please write a support ticket</a>.</small></div>';
		    } else {
			    // Default (for inactive)
			    $links['activate_license'] = '<a href="admin.php?page=' . WPACU_PLUGIN_ID . '_license" style="font-weight: bold; color: darkred; position: relative; padding-left: 23px;">' . $activateLicenseSvg . ' Activate License</a><div style="margin-top: 2px;"><small style="color: darkred; font-style: italic; font-weight: 400;">An activated license allows you to get the latest updates &amp; bug fixes from the Dashboard</small></div>';
		    }
	    }
        // [/License Related]

        return $links;
    }

	}
