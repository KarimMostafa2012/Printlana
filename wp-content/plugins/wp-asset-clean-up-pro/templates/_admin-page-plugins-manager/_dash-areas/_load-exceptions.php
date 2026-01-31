<?php
use WpAssetCleanUp\Misc;

// [wpacu_pro]
use WpAssetCleanUpPro\PluginsManagerPro;
// [/wpacu_pro]

if (! isset($data)) {
	exit;
}
?>
<div data-wpacu-plugin-path="<?php echo esc_attr($data['plugin_path']); ?>"
     class="wpacu_plugin_load_exception_options_wrap <?php if ( ! ( $data['is_unload_site_wide'] || $data['is_unload_via_regex']) ) { ?>wpacu_hide<?php } ?>">
	<div class="wpacu_plugin_rules_wrap">
		<fieldset>
			<legend>Make an exception from any unload rule &amp; <strong>always load it</strong>:</legend>
			<ul class="wpacu_plugin_rules wpacu_exception_options_area">
				<li>
					<label for="wpacu_load_it_regex_option_plugin_<?php echo Misc::sanitizeValueForHtmlAttr($data['plugin_path']); ?>" style="margin-right: 0;">
						<input data-wpacu-plugin-path="<?php echo esc_attr($data['plugin_path']); ?>"
						       id="wpacu_load_it_regex_option_plugin_<?php echo Misc::sanitizeValueForHtmlAttr($data['plugin_path']); ?>"
						       class="wpacu_plugin_load_exception_regex_option wpacu_plugin_load_rule_input"
						       type="checkbox"
							<?php if ($data['is_load_via_regex']) { echo 'checked="checked"'; } ?>
							   name="wpacu_plugins[<?php echo esc_attr($data['plugin_path']); ?>][load_via_regex][enable]"
							   value="1" />&nbsp;<span>Make an exception and always load it if the admin URL (its URI) is matched by a RegEx(es):</span>
					</label>&nbsp;<a style="color: #74777b;" class="help_link" target="_blank" href="https://assetcleanup.com/docs/?p=372#wpacu-unload-plugins-via-regex"><span class="dashicons dashicons-editor-help"></span></a>&nbsp;
					<div class="wpacu_load_regex_input_wrap <?php if (! $data['is_load_via_regex']) { echo 'wpacu_hide'; } ?>"
					     data-wpacu-plugin-path="<?php echo esc_attr($data['plugin_path']); ?>">
                                        <textarea class="wpacu_regex_rule_textarea wpacu_regex_load_rule_textarea"
                                                  data-wpacu-adapt-height="1"
                                                  name="wpacu_plugins[<?php echo esc_attr($data['plugin_path']); ?>][load_via_regex][value]"><?php if (isset($data['rules'][$data['plugin_path']]['load_via_regex']['value']) && $data['rules'][$data['plugin_path']]['load_via_regex']['value']) { echo esc_textarea($data['rules'][$data['plugin_path']]['load_via_regex']['value']); } ?></textarea>
						<p><small><span style="font-weight: 500;">Note:</span> Multiple RegEx rules can be added as long as they are one per line.</small></p>
					</div>
				</li>
                <?php
                ?>
                <li>
                    <label for="wpacu_load_logged_in_via_role_plugin_<?php echo Misc::sanitizeValueForHtmlAttr($data['plugin_path']); ?>"
                           style="margin-right: 0;">
                        <input data-wpacu-plugin-path="<?php echo esc_attr($data['plugin_path']); ?>"
                               id="wpacu_load_logged_in_via_role_plugin_<?php echo Misc::sanitizeValueForHtmlAttr($data['plugin_path']); ?>"
                               class="wpacu_plugin_load_logged_in_via_role wpacu_plugin_load_rule_input"
                               type="checkbox"
                            <?php if ($data['is_load_logged_in_via_role']) { echo 'checked="checked"'; } ?>
                               name="wpacu_plugins[<?php echo esc_attr($data['plugin_path']); ?>][status][]"
                               value="load_logged_in_via_role" />&nbsp;<span>If the logged-in user has any of these roles:</span>
                    </label>
                    <a class="help_link"
                       target="_blank"
                       href="https://www.assetcleanup.com/docs/?p=1688"><span style="color: #74777b;" class="dashicons dashicons-editor-help"></span></a>
                    <div data-wpacu-plugin-path="<?php echo esc_attr($data['plugin_path']); ?>"
                         class="wpacu_plugin_load_logged_in_via_role_select_wrap <?php if (! $data['is_load_logged_in_via_role']) { ?>wpacu_hide<?php } ?>">
                        <?php
                        PluginsManagerPro::buildUserRolesDd(
                            'load_logged_in_via_role',
                            $data['is_load_logged_in_via_role'],
                            $data['all_users_roles'],
                            $data['load_logged_in_via_role_chosen'],
                            $data['plugin_path']
                        );
                        ?>
                    </div>
                </li>
			</ul>
		</fieldset>
	</div>
</div>
