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
     class="wpacu_plugin_unload_rules_options_wrap">
	<div class="wpacu_plugin_rules_wrap">
		<fieldset>
			<legend><strong>Unload this plugin</strong> within the Dashboard:</legend>
			<ul class="wpacu_plugin_rules">
				<li>
					<label for="wpacu_global_unload_plugin_<?php echo Misc::sanitizeValueForHtmlAttr($data['plugin_path']); ?>"
						<?php if ($data['is_unload_site_wide']) { echo 'class="wpacu_plugin_unload_rule_input_checked"'; } ?>>
						<input data-wpacu-plugin-path="<?php echo esc_attr($data['plugin_path']); ?>"
						       class="wpacu_plugin_unload_site_wide wpacu_plugin_unload_rule_input"
						       id="wpacu_global_unload_plugin_<?php echo Misc::sanitizeValueForHtmlAttr($data['plugin_path']); ?>"
						       type="checkbox"
						       name="wpacu_plugins[<?php echo esc_attr($data['plugin_path']); ?>][status][]"
							<?php if ($data['is_unload_site_wide']) { echo 'checked="checked"'; } ?>
							   value="unload_site_wide" />
						On all admin pages</label>
				</li>
				<li>
					<label for="wpacu_unload_it_regex_option_<?php echo Misc::sanitizeValueForHtmlAttr($data['plugin_path']); ?>"
						<?php if ($data['is_unload_via_regex']) { echo 'class="wpacu_plugin_unload_rule_input_checked"'; } ?>
						   style="margin-right: 0;">
						<input data-wpacu-plugin-path="<?php echo esc_attr($data['plugin_path']); ?>"
						       id="wpacu_unload_it_regex_option_<?php echo Misc::sanitizeValueForHtmlAttr($data['plugin_path']); ?>"
						       class="wpacu_plugin_unload_regex_option wpacu_plugin_unload_rule_input"
						       type="checkbox"
							<?php if ($data['is_unload_via_regex']) { echo 'checked="checked"'; } ?>
							   name="wpacu_plugins[<?php echo esc_attr($data['plugin_path']); ?>][status][]"
							   value="unload_via_regex">&nbsp;<span>For admin URLs with request URI matching the RegEx(es):</span></label>
					<a class="help_link unload_it_regex"
					   target="_blank"
					   href="https://assetcleanup.com/docs/?p=372#wpacu-unload-plugins-via-regex"><span style="color: #74777b;" class="dashicons dashicons-editor-help"></span></a>
					<div data-wpacu-plugin-path="<?php echo esc_attr($data['plugin_path']); ?>"
					     class="wpacu_plugin_unload_regex_input_wrap <?php if (! $data['is_unload_via_regex']) { ?>wpacu_hide<?php } ?>">
                                                <textarea class="wpacu_regex_rule_textarea wpacu_regex_unload_rule_textarea"
                                                          data-wpacu-adapt-height="1"
                                                          name="wpacu_plugins[<?php echo esc_attr($data['plugin_path']); ?>][unload_via_regex][value]"><?php if (isset($data['rules'][$data['plugin_path']]['unload_via_regex']['value']) && $data['rules'][$data['plugin_path']]['unload_via_regex']['value']) {
		                                                echo esc_textarea($data['rules'][$data['plugin_path']]['unload_via_regex']['value']); } ?></textarea>
						<p><small><span style="font-weight: 500;">Note:</span> Multiple RegEx rules can be added as long as they are one per line.</small></p>
					</div>
				</li>

                <?php
                ?>
                <li>
                    <label for="wpacu_unload_logged_in_via_role_plugin_<?php echo Misc::sanitizeValueForHtmlAttr($data['plugin_path']); ?>"
                        <?php if ($data['is_unload_logged_in_via_role']) { echo 'class="wpacu_plugin_unload_rule_input_checked"'; } ?>
                           style="margin-right: 0;">
                        <input data-wpacu-plugin-path="<?php echo esc_attr($data['plugin_path']); ?>"
                               id="wpacu_unload_logged_in_via_role_plugin_<?php echo Misc::sanitizeValueForHtmlAttr($data['plugin_path']); ?>"
                               class="wpacu_plugin_unload_logged_in_via_role wpacu_plugin_unload_rule_input"
                               type="checkbox"
                            <?php if ($data['is_unload_logged_in_via_role']) { echo 'checked="checked"'; } ?>
                               name="wpacu_plugins[<?php echo esc_attr($data['plugin_path']); ?>][status][]"
                               value="unload_logged_in_via_role" />&nbsp;<span>If the logged-in user has any of these roles:</span>
                    </label>
                    <a class="help_link"
                       target="_blank"
                       href="https://www.assetcleanup.com/docs/?p=1688"><span style="color: #74777b;" class="dashicons dashicons-editor-help"></span></a>
                    <div data-wpacu-plugin-path="<?php echo esc_attr($data['plugin_path']); ?>"
                         class="wpacu_plugin_unload_logged_in_via_role_select_wrap <?php if (! $data['is_unload_logged_in_via_role']) { ?>wpacu_hide<?php } ?>">
                        <?php
                        PluginsManagerPro::buildUserRolesDd(
                            'unload_logged_in_via_role',
                            $data['is_unload_logged_in_via_role'],
                            $data['all_users_roles'],
                            $data['unload_logged_in_via_role_chosen'],
                            $data['plugin_path']
                        );
                        ?>
                    </div>
                </li>
			</ul>
		</fieldset>
	</div>
	<div class="wpacu_clearfix"></div>
</div>