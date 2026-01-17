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
     class="wpacu_plugin_load_exception_options_wrap <?php if ($data['no_unload_rule_set']) { ?>wpacu_hide<?php } ?>">
	<div class="wpacu_plugin_rules_wrap">
		<fieldset>
			<legend>Make an exception from any unload rule &amp; <strong>always load it</strong> in the front-end:</legend>
			<ul class="wpacu_plugin_rules wpacu_exception_options_area">
				<li>
					<label for="wpacu_home_page_load_plugin_<?php echo Misc::sanitizeValueForHtmlAttr($data['plugin_path']); ?>"
						<?php if ($data['is_load_homepage']) { echo 'class="wpacu_plugin_load_rule_input_checked"'; } ?>>
						<input data-wpacu-plugin-path="<?php echo esc_attr($data['plugin_path']); ?>"
						       class="wpacu_plugin_load_home_page wpacu_plugin_load_rule_input"
						       id="wpacu_home_page_load_plugin_<?php echo Misc::sanitizeValueForHtmlAttr($data['plugin_path']); ?>"
						       type="checkbox"
						       name="wpacu_plugins[<?php echo esc_attr($data['plugin_path']); ?>][status][]"
							<?php if ($data['is_load_homepage']) { echo 'checked="checked"'; } ?>
							   value="load_home_page" />
						<span>On the homepage</span></label>
				</li>
				<li>
					<label for="wpacu_via_post_type_load_plugin_<?php echo Misc::sanitizeValueForHtmlAttr($data['plugin_path']); ?>"
						<?php if ($data['is_load_via_post_type']) { echo 'class="wpacu_plugin_load_rule_input_checked"'; } ?>>
						<input data-wpacu-plugin-path="<?php echo esc_attr($data['plugin_path']); ?>"
						       class="wpacu_plugin_load_via_post_type wpacu_plugin_load_rule_input"
						       id="wpacu_via_post_type_load_plugin_<?php echo Misc::sanitizeValueForHtmlAttr($data['plugin_path']); ?>"
						       type="checkbox"
						       name="wpacu_plugins[<?php echo esc_attr($data['plugin_path']); ?>][status][]"
							<?php if ($data['is_load_via_post_type']) { echo 'checked="checked"'; } ?>
							   value="load_via_post_type" />
						<span>On pages of these post types:</span></label>
                    <a class="help_link"
                       target="_blank"
                       href="https://www.assetcleanup.com/docs/?p=1613"><span style="color: #74777b;" class="dashicons dashicons-editor-help"></span></a>
					<div data-wpacu-plugin-path="<?php echo esc_attr($data['plugin_path']); ?>"
					     class="wpacu_plugin_load_via_post_type_select_wrap <?php if (! $data['is_load_via_post_type']) { ?>wpacu_hide<?php } ?>">
						<?php
						PluginsManagerPro::buildPostTypesListDd(
							'load_via_post_type',
							$data['is_load_via_post_type'],
							$data['post_types_list'],
							$data['load_via_post_type_chosen'],
							$data['plugin_path']
						);
						?>
					</div>
				</li>

				<!-- [Load exception based on taxonomy] -->
				<li>
					<label for="wpacu_via_tax_load_plugin_<?php echo Misc::sanitizeValueForHtmlAttr($data['plugin_path']); ?>"
						<?php if ($data['is_load_via_tax']) { echo 'class="wpacu_plugin_load_rule_input_checked"'; } ?>>
						<input data-wpacu-plugin-path="<?php echo esc_attr($data['plugin_path']); ?>"
						       class="wpacu_plugin_load_via_tax wpacu_plugin_load_rule_input"
						       id="wpacu_via_tax_load_plugin_<?php echo Misc::sanitizeValueForHtmlAttr($data['plugin_path']); ?>"
						       type="checkbox"
						       name="wpacu_plugins[<?php echo esc_attr($data['plugin_path']); ?>][status][]"
							<?php if ($data['is_load_via_tax']) { echo 'checked="checked"'; } ?>
							   value="load_via_tax" />
						<span>On these taxonomy pages:</span></label>
                    <a class="help_link"
                       target="_blank"
                       href="https://www.assetcleanup.com/docs/?p=1579"><span style="color: #74777b;" class="dashicons dashicons-editor-help"></span></a>
					<div data-wpacu-plugin-path="<?php echo esc_attr($data['plugin_path']); ?>"
					     class="wpacu_plugin_load_via_tax_select_wrap <?php if (! $data['is_load_via_tax']) { ?>wpacu_hide<?php } ?>">
						<?php
						PluginsManagerPro::buildTaxListDd(
							'load_via_tax',
							$data['is_load_via_tax'],
							$data['tax_group_list'],
							$data['load_via_tax_chosen'],
							$data['plugin_path']
						);
						?>
					</div>
				</li>
				<!-- [/Load exception based on the taxonomy -->

                <!-- [Load exception on archive (post list) pages] -->
                <li>
                    <label for="wpacu_via_archive_type_load_plugin_<?php echo Misc::sanitizeValueForHtmlAttr($data['plugin_path']); ?>"
						<?php if ($data['is_load_via_archive']) { echo 'class="wpacu_plugin_load_rule_input_checked"'; } ?>>
                        <input data-wpacu-plugin-path="<?php echo esc_attr($data['plugin_path']); ?>"
                               class="wpacu_plugin_load_via_archive wpacu_plugin_load_rule_input"
                               id="wpacu_via_archive_type_load_plugin_<?php echo Misc::sanitizeValueForHtmlAttr($data['plugin_path']); ?>"
                               type="checkbox"
                               name="wpacu_plugins[<?php echo esc_attr($data['plugin_path']); ?>][status][]"
							<?php if ($data['is_load_via_archive']) { echo 'checked="checked"'; } ?>
                               value="load_via_archive" />
                        <span>On these archive (page list) pages:</span></label>
                    <a class="help_link"
                       target="_blank"
                       href="https://www.assetcleanup.com/docs/?p=1647"><span style="color: #74777b;" class="dashicons dashicons-editor-help"></span></a>
                    <div data-wpacu-plugin-path="<?php echo esc_attr($data['plugin_path']); ?>"
                         class="wpacu_plugin_load_via_archive_select_wrap <?php if (! $data['is_load_via_archive']) { ?>wpacu_hide<?php } ?>">
						<?php
						PluginsManagerPro::buildArchiveTypesListDd(
							'load_via_archive',
							$data['is_load_via_archive'],
							$data['archive_group_list'],
							$data['load_via_archive_chosen'],
							$data['plugin_path']
						);
						?>
                    </div>
                </li>
                <!-- [/Load exception on archive (post list) pages] -->

				<li>
					<label for="wpacu_load_it_regex_option_plugin_<?php echo Misc::sanitizeValueForHtmlAttr($data['plugin_path']); ?>"
						<?php if ($data['is_load_via_regex']) { echo 'class="wpacu_plugin_load_rule_input_checked"'; } ?>
                           style="margin-right: 0;">
						<input data-wpacu-plugin-path="<?php echo esc_attr($data['plugin_path']); ?>"
						       id="wpacu_load_it_regex_option_plugin_<?php echo Misc::sanitizeValueForHtmlAttr($data['plugin_path']); ?>"
						       class="wpacu_plugin_load_exception_regex_option wpacu_plugin_load_rule_input"
						       type="checkbox"
							<?php if ($data['is_load_via_regex']) { echo 'checked="checked"'; } ?>
							   name="wpacu_plugins[<?php echo esc_attr($data['plugin_path']); ?>][load_via_regex][enable]"
							   value="1" />&nbsp;<span>If the URL (its URI) is matched by a RegEx(es):</span>
					</label>&nbsp;<a style="color: #74777b;" class="help_link" target="_blank" href="https://assetcleanup.com/docs/?p=372#wpacu-unload-plugins-via-regex"><span class="dashicons dashicons-editor-help"></span></a>&nbsp;
					<div class="wpacu_load_regex_input_wrap <?php if (! $data['is_load_via_regex']) { echo 'wpacu_hide'; } ?>"
					     data-wpacu-plugin-path="<?php echo esc_attr($data['plugin_path']); ?>">
                                            <textarea class="wpacu_regex_rule_textarea wpacu_regex_load_rule_textarea"
                                                      data-wpacu-adapt-height="1"
                                                      name="wpacu_plugins[<?php echo esc_attr($data['plugin_path']); ?>][load_via_regex][value]"><?php if (isset($data['rules'][$data['plugin_path']]['load_via_regex']['value']) && $data['rules'][$data['plugin_path']]['load_via_regex']['value']) {
		                                            echo esc_attr($data['rules'][$data['plugin_path']]['load_via_regex']['value']); } ?></textarea>
						<p><small><span style="font-weight: 500;">Note:</span> Multiple RegEx rules can be added as long as they are one per line.</small></p>
					</div>
				</li>
				<li>
					<label for="wpacu_load_it_logged_in_plugin_<?php echo Misc::sanitizeValueForHtmlAttr($data['plugin_path']); ?>"
                           <?php if ($data['is_load_if_logged_in']) { echo 'class="wpacu_plugin_load_rule_input_checked"'; } ?>
                           style="margin-right: 0;">
						<input data-wpacu-plugin-path="<?php echo esc_attr($data['plugin_path']); ?>"
						       id="wpacu_load_it_logged_in_plugin_<?php echo Misc::sanitizeValueForHtmlAttr($data['plugin_path']); ?>"
						       class="wpacu_plugin_load_exception_logged_in wpacu_plugin_load_rule_input"
						       type="checkbox"
							<?php if ($data['is_load_if_logged_in']) { echo 'checked="checked"'; } ?>
							   name="wpacu_plugins[<?php echo esc_attr($data['plugin_path']); ?>][load_logged_in][enable]"
							   value="1" />&nbsp;<span>If the user is logged in</span>
					</label>
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
			<div class="wpacu_clearfix"></div>
		</fieldset>
	</div>
</div>