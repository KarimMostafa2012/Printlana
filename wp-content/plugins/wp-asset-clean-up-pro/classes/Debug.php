<?php
namespace WpAssetCleanUp;

use WpAssetCleanUp\OptimiseAssets\OptimizeCommon;

// [wpacu_pro]
use WpAssetCleanUpPro\DebugPro;
// [/wpacu_pro]

/**
 * Class Debug
 * @package WpAssetCleanUp
 */
class Debug
{
	/**
	 * Debug constructor.
	 */
	public function __construct()
	{
		if ( isset($_GET['wpacu_debug']) && ! is_admin() ) {
            add_action('wp_footer', array($this, 'showDebugOptionsFront'), PHP_INT_MAX);

            }

		foreach( array('wp', 'admin_init') as $wpacuActionHook ) {
			add_action( $wpacuActionHook, static function() {
				if (isset( $_GET['wpacu_get_cache_dir_size'] ) && Menu::userCanManageAssets()) {
					self::printCacheDirInfo();
				}

				// For debugging purposes
				if (isset($_GET['wpacu_get_already_minified']) && Menu::userCanManageAssets()) {
                    echo '<pre>'; print_r(OptimizeCommon::getAlreadyMarkedAsMinified()); echo '</pre>';
                    exit();
                }

				if (isset($_GET['wpacu_remove_already_minified']) && Menu::userCanManageAssets()) {
					echo '<pre>'; OptimizeCommon::removeAlreadyMarkedAsMinified(); echo '</pre>';
					exit();
				}

				if (isset($_GET['wpacu_limit_already_minified']) && Menu::userCanManageAssets()) {
					OptimizeCommon::limitAlreadyMarkedAsMinified();
					echo '<pre>'; print_r(OptimizeCommon::getAlreadyMarkedAsMinified()); echo '</pre>';
					exit();
				}
			} );
		}
	}

    /**
     * @param $wpacuCacheKey
     *
     * @return array
     */
    public static function getTimingValues($wpacuCacheKey)
    {
        $wpacuExecTiming = ObjectCache::wpacu_cache_get( $wpacuCacheKey, 'wpacu_exec_time' ) ?: 0;

        $wpacuExecTimingMs = $wpacuExecTiming;

        $wpacuTimingFormatMs = str_replace('.00', '', number_format($wpacuExecTimingMs, 2));
        $wpacuTimingFormatS  = str_replace(array('.00', ','), '', number_format(($wpacuExecTimingMs / 1000), 3));

        return array('ms' => $wpacuTimingFormatMs, 's' => $wpacuTimingFormatS);
    }

    /**
     * @param $timingKey
     * @param $htmlSource
     *
     * @return string|string[]
     */
    public static function printTimingFor($timingKey, $htmlSource)
    {
        $wpacuCacheKey       = 'wpacu_' . $timingKey . '_exec_time';
        $timingValues        = self::getTimingValues( $wpacuCacheKey);
        $wpacuTimingFormatMs = $timingValues['ms'];
        $wpacuTimingFormatS  = $timingValues['s'];

        return str_replace(
            array(
                '{' . $wpacuCacheKey . '}',
                '{' . $wpacuCacheKey . '_sec}'
            ),

            array(
                $wpacuTimingFormatMs . 'ms',
                $wpacuTimingFormatS . 's',
            ), // clean it up

            $htmlSource
        );
    }

	/**
	 * @param $htmlSource
     *
	 * @return string|string[]
	 */
	public static function applyDebugTiming($htmlSource)
	{
		$timingKeys = array(
			'prepare_optimize_files_css',
			'prepare_optimize_files_js',

			// All HTML alteration via "wp_loaded" action hook
			'alter_html_source',

			// HTML CleanUp
			'alter_html_source_cleanup',
			'alter_html_source_for_remove_html_comments',
			'alter_html_source_for_remove_meta_generators',

			// CSS
			'alter_html_source_for_optimize_css',
			'alter_html_source_unload_ignore_deps_css',
			'alter_html_source_for_google_fonts_optimization_removal',
			'alter_html_source_for_inline_css',

			// [wpacu_pro]
			'alter_html_source_for_change_css_position',
			// [/wpacu_pro]

			'alter_html_source_original_to_optimized_css',
			'alter_html_source_for_preload_css',

			// [wpacu_pro]
			'alter_html_source_for_add_async_preloads_noscript',
			// [/wpacu_pro]

			'alter_html_source_for_combine_css',
			'alter_html_source_for_minify_inline_style_tags',

			// [wpacu_pro]
			'alter_html_source_for_defer_footer_css',
			'alter_html_source_for_local_fonts_display_style_inline',
			// [/wpacu_pro]

			'alter_html_source_strip_any_references_for_unloaded_styles',
			'alter_html_source_for_optimize_css_final_cleanups',

			// JS
			'alter_html_source_for_optimize_js',
			'alter_html_source_maybe_move_jquery_after_body_tag',
			'alter_html_source_unload_ignore_deps_js',

			// [wpacu_pro]
			'alter_html_source_for_inline_js',
			// [/wpacu_pro]

			'alter_html_source_original_to_optimized_js',
			'alter_html_source_for_preload_js',

			'alter_html_source_for_combine_js',

			// [wpacu_pro]
			'alter_html_source_move_scripts_to_body',
			'alter_html_source_for_minify_inline_script_tags',
			// [/wpacu_pro]

			'alter_html_source_move_inline_jquery_after_src_tag',
			'alter_html_source_strip_any_references_for_unloaded_scripts',
			'alter_html_source_for_optimize_js_final_cleanups',

			'fetch_strip_hardcoded_assets',

			// [wpacu_pro]
			'fetch_rules_hardcoded_assets',
			// [/wpacu_pro]

			'fetch_all_hardcoded_assets',

			// [wpacu_pro]
			'strip_change_marked_hardcoded_assets',
                'strip_marked_hardcoded_assets',
                'change_positions_hardcoded_assets',
                'preload_and_tag_changes_hardcoded_assets',
			// [/wpacu_pro]

			'output_css_js_manager',

			'style_loader_tag',
			'script_loader_tag',

			'style_loader_tag_preload_css',
			'script_loader_tag_preload_js',

			'style_loader_tag_pro_changes',
			'script_loader_tag_pro_changes',

            'all_timings'
		);

		foreach ( $timingKeys as $timingKey ) {
            $htmlSource = self::printTimingFor($timingKey, $htmlSource);
		}

		return $htmlSource;
	}

	/**
	 *
	 */
	public function showDebugOptionsFront()
	{
	    if (! Menu::userCanManageAssets()) {
	        return;
        }

	    $markedCssListForUnload = array_unique(Main::instance()->allUnloadedAssets['styles']);
		$markedJsListForUnload  = array_unique(Main::instance()->allUnloadedAssets['scripts']);

		$allDebugOptions = array(
			// [For CSS]
			'wpacu_no_css_unload'  => 'Do not apply any CSS unload rules',
			'wpacu_no_css_minify'  => 'Do not minify any CSS',
			'wpacu_no_css_combine' => 'Do not combine any CSS',

			'wpacu_no_css_preload_basic' => 'Do not preload any CSS (Basic)',

			// [wpacu_pro]
            'wpacu_no_css_position_change' => 'Do not change any CSS Position (e.g. from HEAD to BODY)',
			'wpacu_no_css_preload_async' => 'Do not preload any CSS (Async)',
			// [/wpacu_pro]

            // [/For CSS]

			// [For JS]
			'wpacu_no_js_unload'  => 'Do not apply any JavaScript unload rules',
			'wpacu_no_js_minify'  => 'Do not minify any JavaScript',
			'wpacu_no_js_combine' => 'Do not combine any JavaScript',

			// [wpacu_pro]
			'wpacu_no_async'      => 'Do not async load any JavaScript',
			'wpacu_no_defer'     => 'Do not defer load any JavaScript',
			// [/wpacu_pro]

			'wpacu_no_js_preload_basic' => 'Do not preload any JS (Basic)',

            // [wpacu_pro]
			'wpacu_no_js_position_change' => 'Do not change any JS Position (e.g. from HEAD to BODY)',
			// [/wpacu_pro]

			// [/For JS]

			// Others
			'wpacu_no_frontend_show' => 'Do not show the bottom CSS/JS managing list',
			'wpacu_no_admin_bar'     => 'Do not show the admin bar',
			'wpacu_no_html_changes'  => 'Do not alter the HTML DOM (this will also load all assets non-minified and non-combined)',
		);
		?>
		<style <?php echo Misc::getStyleTypeAttribute(); ?>>
			<?php echo file_get_contents(WPACU_PLUGIN_DIR.'/assets/wpacu-debug.css'); ?>
		</style>

        <script <?php echo Misc::getScriptTypeAttribute(); ?>>
	        <?php echo file_get_contents(WPACU_PLUGIN_DIR.'/assets/wpacu-debug.js'); ?>
        </script>

		<div id="wpacu-debug-options">
            <table>
                <tr>
                    <td style="vertical-align: top;">
                        <p>View the page with the following options <strong>disabled</strong> (for debugging purposes):</p>
                        <form method="post">
                            <ul class="wpacu-options">
                            <?php
                            foreach ($allDebugOptions as $debugKey => $debugText) {
                            ?>
                                <li>
                                    <label>
                                        <input type="checkbox"
                                           name="<?php echo esc_attr($debugKey); ?>"
                                           <?php if ( isset($_REQUEST[$debugKey]) ) { echo 'checked="checked"'; } ?> /> &nbsp;<?php echo esc_html($debugText); ?>
                                    </label>
                                </li>
                            <?php
                            }
                            ?>
                            </ul>

                            <!-- [wpacu_pro] -->
                            <?php
                            DebugPro::showDebugPluginsListToUnload();
                            ?>
                            <!-- [/wpacu_pro] -->

                            <div>
                                <input type="submit"
                                       value="Preview this page with the changes made above" />
                            </div>
                            <input type="hidden" name="wpacu_debug" value="on" />
                        </form>
                    </td>
                    <td style="vertical-align: top;">
                        <?php
                        // [wpacu_pro]
                        if (isset($GLOBALS['wpacu_filtered_plugins']) && $wpacuFilteredPlugins = $GLOBALS['wpacu_filtered_plugins']) {
                            sort($wpacuFilteredPlugins);
                            ?>
                            <p><strong>Unloaded plugins:</strong> The following plugins were unloaded on this page as they have matching unload rules.</p>
                            <ul>
                                <?php
                                foreach ($wpacuFilteredPlugins as $filteredPlugin) {
                                    echo '<li style="color: darkred;">'.esc_html($filteredPlugin).'</li>'."\n";
                                }
                                ?>
                            </ul>
                        <?php
                        } elseif ((int)Main::instance()->settings['plugins_manager_front_disable'] === 1) {
                        ?>
                            <p><strong>Note:</strong> No plugin unload rules that might be set are taking effect because they are set to be ignored (turned "OFF" in <em><a target="_blank" href="<?php echo admin_url('admin.php?page='.WPACU_PLUGIN_ID.'_plugins_manager') ?>">"Plugins Manager" -&gt; "IN FRONTEND VIEW (your visitors)"</a></em>), perhaps for debugging purposes.</p>
                            <?php
                        }
                        // [/wpacu_pro]
                        ?>

	                    <div style="margin: 0 0 10px; padding: 10px 0;">
	                        <strong>CSS handles marked for unload:</strong>&nbsp;
	                        <?php
	                        if (! empty($markedCssListForUnload)) {
	                            sort($markedCssListForUnload);
		                        $markedCssListForUnloadFiltered = array_map(static function($handle) {
		                        	return '<span style="color: darkred;">'.esc_html($handle).'</span>';
		                        }, $markedCssListForUnload);
	                            echo implode(' &nbsp;/&nbsp; ', $markedCssListForUnloadFiltered);
	                        } else {
	                            echo 'None';
	                        }
	                        ?>
	                    </div>

	                    <div style="margin: 0 0 10px; padding: 10px 0;">
	                        <strong>JS handles marked for unload:</strong>&nbsp;
	                        <?php
	                        if (! empty($markedJsListForUnload)) {
	                            sort($markedJsListForUnload);
		                        $markedJsListForUnloadFiltered = array_map(static function($handle) {
			                        return '<span style="color: darkred;">'.esc_html($handle).'</span>';
		                        }, $markedJsListForUnload);

	                            echo implode(' &nbsp;/&nbsp; ', $markedJsListForUnloadFiltered);
	                        } else {
	                            echo 'None';
	                        }
	                        ?>
	                    </div>

	                    <hr />

                        <div style="margin: 0 0 10px; padding: 10px 0;">
							<ul style="list-style: none; padding-left: 0;">
                                <script>
                                    jQuery(document).ready(function($) {
                                        let valueNum = 0;

                                        $('[data-wpacu-count-it]').each(function(index, value) {
                                            let extractedNumber = parseFloat($(this).attr("data-wpacu-count-it").replace('ms', ''));
                                            console.log(extractedNumber);

                                            valueNum += extractedNumber;
                                        });

                                        valueNum = valueNum.toFixed(2);

                                        $('#wpacu-total-all-timings').html(valueNum);
                                        });
                                </script>
                                <li style="margin-bottom: 15px; border-bottom: 1px solid #e7e7e7;"><strong>Total timing for all recorded actions:</strong> <span id="wpacu-total-all-timings"></span>ms</li>

                                <li style="margin-bottom: 10px;" data-wpacu-count-it="<?php echo self::printTimingFor('filter_dequeue_styles',  '{wpacu_filter_dequeue_styles_exec_time}'); ?>">Dequeue any chosen styles (.css): <?php echo self::printTimingFor('filter_dequeue_styles',  '{wpacu_filter_dequeue_styles_exec_time} ({wpacu_filter_dequeue_styles_exec_time_sec})'); ?></li>
                                <li style="margin-bottom: 20px;" data-wpacu-count-it="<?php echo self::printTimingFor('filter_dequeue_scripts',  '{wpacu_filter_dequeue_scripts_exec_time}'); ?>">Dequeue any chosen scripts (.js): <?php echo self::printTimingFor('filter_dequeue_scripts', '{wpacu_filter_dequeue_scripts_exec_time} ({wpacu_filter_dequeue_scripts_exec_time_sec})'); ?></li>

                                <li style="margin-bottom: 10px;" data-wpacu-count-it="{wpacu_prepare_optimize_files_css_exec_time}">Prepare CSS files to optimize: {wpacu_prepare_optimize_files_css_exec_time} ({wpacu_prepare_optimize_files_css_exec_time_sec})</li>
                                <li style="margin-bottom: 20px;" data-wpacu-count-it="{wpacu_prepare_optimize_files_js_exec_time}">Prepare JS files to optimize: {wpacu_prepare_optimize_files_js_exec_time} ({wpacu_prepare_optimize_files_js_exec_time_sec})</li>

                                <li style="margin-bottom: 10px;" data-wpacu-count-it="{wpacu_alter_html_source_exec_time}">OptimizeCommon - HTML alteration via <em>wp_loaded</em>: {wpacu_alter_html_source_exec_time} ({wpacu_alter_html_source_exec_time_sec})
                                    <ul id="wpacu-debug-timing">
                                        <li style="margin-top: 10px; margin-bottom: 10px;">&nbsp;OptimizeCSS: {wpacu_alter_html_source_for_optimize_css_exec_time} ({wpacu_alter_html_source_for_optimize_css_exec_time_sec})
                                            <ul>
                                                <li>Google Fonts Optimization/Removal: {wpacu_alter_html_source_for_google_fonts_optimization_removal_exec_time}</li>
                                                <li>From CSS file to Inline: {wpacu_alter_html_source_for_inline_css_exec_time}</li>
                                                <li>Update Original to Optimized: {wpacu_alter_html_source_original_to_optimized_css_exec_time}</li>
                                                <li>Move CSS LINKs (HEAD to BODY and vice-versa): {wpacu_alter_html_source_for_change_css_position_exec_time}</li>
                                                <li>Preloads: {wpacu_alter_html_source_for_preload_css_exec_time}</li>

	                                            <!-- [wpacu_pro] -->
                                                    <li>Preloads (NOSCRIPT fallback): {wpacu_alter_html_source_for_add_async_preloads_noscript_exec_time}</li>
	                                            <!-- [/wpacu_pro] -->

                                                <!-- -->

                                                <li>Combine: {wpacu_alter_html_source_for_combine_css_exec_time}</li>
                                                <li>Minify Inline Tags: {wpacu_alter_html_source_for_minify_inline_style_tags_exec_time}</li>
                                                <li>Unload (ignore dependencies): {wpacu_alter_html_source_unload_ignore_deps_css_exec_time}</li>

	                                            <!-- [wpacu_pro] -->
                                                    <li>Defer Footer CSS: {wpacu_alter_html_source_for_defer_footer_css_exec_time}</li>
	                                                <li>Alter Inline CSS (font-display): {wpacu_alter_html_source_for_local_fonts_display_style_inline_exec_time}</li>
	                                            <!-- [/wpacu_pro] -->

                                                <li>Strip any references for unloaded styles: {wpacu_alter_html_source_strip_any_references_for_unloaded_styles_exec_time}</li>
                                                <li>Final Cleanups for the HTML source: {wpacu_alter_html_source_for_optimize_css_final_cleanups_exec_time}</li>
                                            </ul>
                                        </li>

                                        <li style="margin-top: 10px; margin-bottom: 10px;">OptimizeJs: {wpacu_alter_html_source_for_optimize_js_exec_time} ({wpacu_alter_html_source_for_optimize_js_exec_time_sec})
                                            <ul>

	                                            <!-- [wpacu_pro] -->
                                                    <li>From JS File to Inline: {wpacu_alter_html_source_for_inline_js_exec_time}</li>
	                                            <!-- [/wpacu_pro] -->

                                                <li>Update Original to Optimized: {wpacu_alter_html_source_original_to_optimized_js_exec_time}</li>
                                                <li>Preloads: {wpacu_alter_html_source_for_preload_js_exec_time}</li>
                                                <!-- -->

                                                <li>Combine: {wpacu_alter_html_source_for_combine_js_exec_time}</li>

	                                            <!-- [wpacu_pro] -->
	                                                <li>Move scripts within the BODY tag: {wpacu_alter_html_source_move_scripts_to_body_exec_time}</li>
                                                    <li>Minify Inline Tags: {wpacu_alter_html_source_for_minify_inline_script_tags_exec_time}</li>
	                                            <!-- [/wpacu_pro] -->

                                                <li>Move jQuery within the BODY tag: {wpacu_alter_html_source_maybe_move_jquery_after_body_tag_exec_time}</li>
                                                <li>Unload (ignore dependencies): {wpacu_alter_html_source_unload_ignore_deps_js_exec_time}</li>
                                                <li>Move any inline with jQuery code after jQuery library: {wpacu_alter_html_source_move_inline_jquery_after_src_tag_exec_time}</li>
                                                <li>Strip any references for unloaded scripts: {wpacu_alter_html_source_strip_any_references_for_unloaded_scripts_exec_time}</li>
                                                <li>Final Cleanups for the HTML source: {wpacu_alter_html_source_for_optimize_js_final_cleanups_exec_time}</li>
                                            </ul>
                                        </li>

                                        <!-- [wpacu_pro] -->
                                        <li style="margin-top: 10px; margin-bottom: 10px;">Hardcoded CSS/JS (fetch &amp; strip): {wpacu_fetch_strip_hardcoded_assets_exec_time}
                                            <ul>
	                                            <li>Fetch Rules for Hardcoded Assets: {wpacu_fetch_rules_hardcoded_assets_exec_time}</li>
	                                            <li>Fetch All from the Current Page: {wpacu_fetch_all_hardcoded_assets_exec_time}</li>
	                                            <li>Alter All marked for change/unload: {wpacu_strip_change_marked_hardcoded_assets_exec_time}
                                                    <ul>
                                                        <li>Strip Marked for Unload for Hardcoded Assets: {wpacu_strip_marked_hardcoded_assets_exec_time}</li>
                                                        <li>Change Positions for Hardcoded Assets: {wpacu_change_positions_hardcoded_assets_exec_time}</li>
                                                        <li>Preload &amp; Tag Changes for Hardcoded Assets: {wpacu_preload_and_tag_changes_hardcoded_assets_exec_time}</li>
                                                    </ul>
                                                </li>
                                            </ul>
                                        </li>
                                        <!-- [/wpacu_pro] -->

                                        <li>HTML CleanUp: {wpacu_alter_html_source_cleanup_exec_time}
                                            <ul>
                                                <li>Strip HTML Comments: {wpacu_alter_html_source_for_remove_html_comments_exec_time}</li>
	                                            <li>Remove Generator Meta Tags: {wpacu_alter_html_source_for_remove_meta_generators_exec_time}</li>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>

								<li style="margin-bottom: 10px;" data-wpacu-count-it="{wpacu_output_css_js_manager_exec_time}">Output CSS &amp; JS Management List: {wpacu_output_css_js_manager_exec_time} ({wpacu_output_css_js_manager_exec_time_sec})</li>

                                <li style="margin-bottom: 10px;" data-wpacu-count-it="{wpacu_style_loader_tag_exec_time}">"style_loader_tag" filters: {wpacu_style_loader_tag_exec_time} ({wpacu_style_loader_tag_exec_time_sec})</li>
                                <li style="margin-bottom: 10px;" data-wpacu-count-it="{wpacu_script_loader_tag_exec_time}">"script_loader_tag" filters: {wpacu_script_loader_tag_exec_time} ({wpacu_script_loader_tag_exec_time_sec})</li>

								<li style="margin-bottom: 10px;" data-wpacu-count-it="{wpacu_style_loader_tag_preload_css_exec_time}">"style_loader_tag" filters (Preload CSS): {wpacu_style_loader_tag_preload_css_exec_time} ({wpacu_style_loader_tag_preload_css_exec_time_sec})</li>
								<li style="margin-bottom: 10px;" data-wpacu-count-it="{wpacu_script_loader_tag_preload_js_exec_time}">"script_loader_tag" filters (Preload JS): {wpacu_script_loader_tag_preload_js_exec_time} ({wpacu_script_loader_tag_preload_js_exec_time_sec})</li>

                                <li style="margin-bottom: 10px;" data-wpacu-count-it="{wpacu_style_loader_tag_preload_css_exec_time}">"style_loader_tag" filters (Pro changes): {wpacu_style_loader_tag_pro_changes_exec_time} ({wpacu_style_loader_tag_pro_changes_exec_time_sec})</li>
                                <li style="margin-bottom: 10px;" data-wpacu-count-it="{wpacu_script_loader_tag_preload_js_exec_time}">"script_loader_tag" filters (Pro changes): {wpacu_script_loader_tag_pro_changes_exec_time} ({wpacu_script_loader_tag_pro_changes_exec_time_sec})</li>
							</ul>
	                    </div>
                    </td>
                </tr>
            </table>
		</div>
		<?php
	}

	/**
	 *
	 */
	public static function printCacheDirInfo()
    {
    	$assetCleanUpCacheDirRel = OptimizeCommon::getRelPathPluginCacheDir();
	    $assetCleanUpCacheDir  = WP_CONTENT_DIR . $assetCleanUpCacheDirRel;

	    echo '<h3>'.WPACU_PLUGIN_TITLE.': Caching Directory Stats</h3>';

	    if (is_dir($assetCleanUpCacheDir)) {
	    	$printCacheDirOutput = '<em>'.str_replace($assetCleanUpCacheDirRel, '<strong>'.$assetCleanUpCacheDirRel.'</strong>', $assetCleanUpCacheDir).'</em>';

	    	if (! is_writable($assetCleanUpCacheDir)) {
			    echo '<span style="color: red;">'.
			            'The '.wp_kses($printCacheDirOutput, array('em' => array(), 'strong' => array())).' directory is <em>not writable</em>.</span>'.
			         '<br /><br />';
		    } else {
			    echo '<span style="color: green;">The '.wp_kses($printCacheDirOutput, array('em' => array(), 'strong' => array())).' directory is <em>writable</em>.</span>' . '<br /><br />';
		    }

		    $dirItems = new \RecursiveDirectoryIterator( $assetCleanUpCacheDir, \RecursiveDirectoryIterator::SKIP_DOTS );

		    $totalFiles = 0;
		    $totalSize  = 0;

		    foreach (
			    new \RecursiveIteratorIterator( $dirItems, \RecursiveIteratorIterator::SELF_FIRST,
				    \RecursiveIteratorIterator::CATCH_GET_CHILD ) as $item
		    ) {
			    $appendAfter = '';

			    if ($item->isDir()) {
			    	echo '<br />';

				    $appendAfter = ' - ';

			    	if (is_writable($item)) {
					    $appendAfter .= ' <em><strong>writable</strong> directory</em>';
				    } else {
					    $appendAfter .= ' <em><strong style="color: red;">not writable</strong> directory</em>';
				    }
			    } elseif ($item->isFile()) {
			    	$appendAfter = '(<em>'.Misc::formatBytes($item->getSize()).'</em>)';

			    	echo '&nbsp;-&nbsp;';
			    }

			    echo wp_kses($item.' '.$appendAfter, array(
			            'em' => array(),
                        'strong' => array('style' => array()),
                        'br' => array(),
                        'span' => array('style' => array())
                    ))

                     .'<br />';

			    if ( $item->isFile() ) {
				    $totalSize += $item->getSize();
				    $totalFiles ++;
			    }
		    }

		    echo '<br />'.'Total Files: <strong>'.$totalFiles.'</strong> / Total Size: <strong>'.Misc::formatBytes($totalSize).'</strong>';
	    } else {
		    echo 'The directory does not exists.';
	    }

	    exit();
    }
}
