<?php
/*
 * No direct access to this file
 */

use WpAssetCleanUp\Overview;

if (! isset($data)) {
	exit;
}
?>
<hr style="margin: 15px 0;"/>
<h3><span class="dashicons dashicons-admin-appearance"></span> <?php _e('Stylesheets (.css)', 'wp-asset-clean-up'); ?>
	<?php
	if (isset($data['handles']['styles']) && count($data['handles']['styles']) > 0) {
		echo ' &#10230; Total handles with rules: '.count($data['handles']['styles']);
	}
	?></h3>
<?php
if (isset($data['handles']['styles']) && ! empty($data['handles']['styles'])) {
	?>
	<table class="wp-list-table wpacu-overview-list-table widefat fixed striped">
		<thead>
		<tr class="wpacu-top">
			<td><strong>Handle</strong></td>
			<td><strong>Unload &amp; Load Exception Rules</strong></td>
		</tr>
		</thead>
		<?php
		foreach ($data['handles']['styles'] as $handle => $handleData) {
			?>
			<tr id="wpacu-overview-css-<?php echo esc_attr($handle); ?>" class="wpacu_global_rule_row wpacu_bulk_change_row">
				<td>
					<?php Overview::renderHandleTd($handle, 'styles', $data); ?>
				</td>
				<td>
					<?php
					$handleData['handle'] = $handle;
					$handleData['asset_type'] = 'styles';
					$handleChangesOutput = Overview::renderHandleChangesOutput($handleData);

					if (! empty($handleChangesOutput)) {
						echo '<ul style="margin: 0;">' . "\n";

						foreach ( $handleChangesOutput as $handleChangesOutputPart ) {
							echo '<li>' . $handleChangesOutputPart . '</li>' . "\n";
						}

						echo '</ul>';
					} else {
						echo '<em style="color: #6d6d6d;">'.__('No unload/load exception rules of any kind are set for this stylesheet file', 'wp-asset-clean-up').'</em>.';
					}
					?>
				</td>
			</tr>
			<?php
		}
		?>
	</table>
	<?php
} else {
	?>
	<p><?php _e('There is no data added to (e.g. unload, load exceptions, notes, changing of location, preloading, etc.) to any stylesheet.', 'wp-asset-clean-up'); ?></p>
	<?php
}
