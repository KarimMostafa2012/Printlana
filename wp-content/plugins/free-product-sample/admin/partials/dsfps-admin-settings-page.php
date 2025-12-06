<?php

/**
 * Handles plugin settings page
 * 
 * @package DSFPS_Free_Product_Sample_Pro
 * @since   1.0.0
 */
// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
require_once plugin_dir_path( __FILE__ ) . 'header/plugin-header.php';
$fps_settings_enable_disable = get_option( 'fps_settings_enable_disable' );
$fps_settings_button_label = get_option( 'fps_settings_button_label' );
$fps_settings_hide_on_shop = get_option( 'fps_settings_hide_on_shop' );
$fps_settings_hide_on_category = get_option( 'fps_settings_hide_on_category' );
$fps_settings_include_store_menu = get_option( 'fps_settings_include_store_menu' );
$fps_sample_enable_type = get_option( 'fps_sample_enable_type' );
$fps_select_product_list = get_option( 'fps_select_product_list' );
$fps_sample_price_type = get_option( 'fps_sample_price_type' );
$fps_sample_flat_price = get_option( 'fps_sample_flat_price' );
$fps_sample_quantity_type = get_option( 'fps_sample_quantity_type' );
$fps_quantity_per_product = get_option( 'fps_quantity_per_product' );
$fps_quantity_per_order = get_option( 'fps_quantity_per_order' );
$fps_max_quantity_message = get_option( 'fps_max_quantity_message' );
$fps_max_quantity_per_order_msg = get_option( 'fps_max_quantity_per_order_msg' );
$fps_select_users_list = get_option( 'fps_select_users_list' );
$fps_sample_button_color = get_option( 'fps_sample_button_color' );
$fps_sample_button_bg_color = get_option( 'fps_sample_button_bg_color' );
$fps_ajax_enable_disable = get_option( 'fps_ajax_enable_disable' );
$fps_ajax_enable_dialog = get_option( 'fps_ajax_enable_dialog' );
$fps_ajax_sucess_message = get_option( 'fps_ajax_sucess_message' );
$dsfps_admin_object = new DSFPS_Free_Product_Sample_Pro_Admin('', '');
$allowed_tooltip_html = wp_kses_allowed_html( 'post' )['span'];
?>
<div class="fps-section-left">
	<div class="notice notice-success is-dismissible" id="succesful_message_fps">
		<p><?php 
esc_html_e( 'Sample product settings have been successfully saved!', 'free-product-sample' );
?></p>
	</div>
	<div class="warning_message_fps">
		<p class="warning_message_for_cat"><?php 
esc_html_e( 'Category wise feature is only available on Premium version. Please upgrade to use it.', 'free-product-sample' );
?></p>
		<p class="warning_message_for_price"><?php 
esc_html_e( 'Percentage price feature is only available on Premium version. Please upgrade to use it.', 'free-product-sample' );
?></p>
	</div>
	<div class="woocommerce-dsfps-setting-content fps-table-tooltip">
		<div class="fps-setting-main">
			<div class="fps-setting-header fps-setting-wrap">
				<h2><?php 
esc_html_e( 'Basic Configuration', 'free-product-sample' );
?></h2>
				<div class="fps-save-button">
					<img class="fps-setting-loader"
						src="<?php 
echo esc_url( plugin_dir_url( __FILE__ ) . 'images/ajax-loader.gif' );
?>"
						alt="ajax-loader" />
					<input type="button" name="save_dsfps" id="save_top_dsfps_setting" class="button button-primary button-large"
						value="<?php 
esc_attr_e( 'Save Changes', 'free-product-sample' );
?>">
				</div>
			</div>
			<div class="fps-section-content">
				<table class="form-table table-outer">
					<tbody>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_settings_enable_disable"><?php 
esc_html_e( 'Enable/Disable', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Use this toggle to enable or disable product samples (Customers will only be able to see the sample button while it is enabled).', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<label class="fps_toggle_switch">
									<input type="checkbox" value="on" id="fps_settings_enable_disable" class="fps_settings_enable_disable" <?php 
checked( $fps_settings_enable_disable, 'on' );
?>>
									<span class="fps_toggle_btn"></span>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_settings_button_label"><?php 
esc_html_e( 'Button Label', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Set your custom label for the sample "Add to Cart" button. Use {PRICE} to dynamically display the sample price.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<input type="text" placeholder="<?php 
esc_attr_e( 'Order a Sample: {PRICE}', 'free-product-sample' );
?>" value="<?php 
esc_attr_e( $fps_settings_button_label, 'free-product-sample' );
?>" id="fps_settings_button_label" class="fps_settings_button_label">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_sample_btn_position">
									<?php 
esc_html_e( 'Sample Button Position', 'free-product-sample' );
?>
									<?php 
if ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ) {
    ?><span class="fps-pro-label"></span><?php 
}
?>
									<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Choose the position of your sample button on the product detail page.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<?php 
?>
									<div class="fps-lock-option">
										<select class="fps-pro-features" name="fps_sample_btn_position" id="fps_sample_btn_position" disabled="disabled">
											<option value="after-add-to-cart"><?php 
esc_html_e( 'After Add To Cart', 'free-product-sample' );
?></option>
											<option value="before-add-to-cart"><?php 
esc_html_e( 'Before Add To Cart', 'free-product-sample' );
?></option>
										</select>
									</div>	
									<?php 
?>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting"><?php 
esc_html_e( 'Sample Button Color', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Customize the colors of your sample "Add to Cart" button.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<div class="fps-multi-options">
									<div class="fps-multi-options-inner">
										<div class="fps-multi-option-input">
											<p><?php 
esc_html_e( 'Color', 'free-product-sample' );
?></p>
											<input type="text" id="fps_sample_button_color" class="fps_sliders_colorpick" name="fps_sample_button_color" value="<?php 
echo esc_attr( $fps_sample_button_color );
?>">
										</div>
										<div class="fps-multi-option-input">
											<p><?php 
esc_html_e( 'Background', 'free-product-sample' );
?></p>
											<input type="text" id="fps_sample_button_bg_color" class="fps_sliders_colorpick" name="fps_sample_button_bg_color" value="<?php 
echo esc_attr( $fps_sample_button_bg_color );
?>">
										</div>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label class="fps_button_advance_enable_disable"><?php 
esc_html_e( 'Sample Button Advanced Styles', 'free-product-sample' );
?>
								<?php 
if ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ) {
    ?><span class="fps-pro-label"></span><?php 
}
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Enable this option for more advanced styling options for the sample button, such as border font size, etc.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<?php 
if ( fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code() ) {
    ?>
									<label class="fps_toggle_switch">
										<input type="checkbox" value="on" id="fps_button_advance_enable_disable" class="fps_button_advance_enable_disable" <?php 
    checked( $fps_button_advance_enable_disable, 'on' );
    ?>>
										<span class="fps_toggle_btn"></span>
									</label>
									<?php 
} else {
    ?>
									<label class="fps_toggle_switch">
										<input type="checkbox" value="on" id="fps_button_advance_enable_disable" class="fps_button_advance_enable_disable">
										<span class="fps_toggle_btn"></span>
									</label>
									<?php 
}
?>
							</td>
						</tr>
						<tr class="fps-sample-button-advance-fields fps-inner-setting">
							<th scope="row">
								<label class="fps_leble_setting" for="fps_sample_button_border_color"><?php 
esc_html_e( 'Button Border', 'free-product-sample' );
?>
								<?php 
if ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ) {
    ?><span class="fps-pro-label"></span><?php 
}
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Set sample button border color and width. ', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<div class="fps-multi-options">
									<div class="fps-multi-options-inner">
										<?php 
if ( fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code() ) {
    ?>
											<div class="fps-multi-option-input">
												<p><?php 
    esc_html_e( 'Border Color', 'free-product-sample' );
    ?></p>
												<input type="text" id="fps_sample_button_border_color" class="fps_sliders_colorpick" name="fps_sample_button_border_color" value="<?php 
    echo esc_attr( $fps_sample_button_border_color );
    ?>">
											</div>
											<div class="fps-multi-option-input">
												<p><?php 
    esc_html_e( 'Border Width (px)', 'free-product-sample' );
    ?></p>
												<input type="number" placeholder="<?php 
    esc_attr_e( '1', 'free-product-sample' );
    ?>" value="<?php 
    esc_attr_e( $fps_sample_button_border_width, 'free-product-sample' );
    ?>" id="fps_sample_button_border_width" class="fps_sample_button_border_width">
											</div>
											<?php 
} else {
    ?>
											<div class="fps-multi-option-input preimium-feature-block">
												<p><?php 
    esc_html_e( 'Border Color', 'free-product-sample' );
    ?></p>
												<input type="text" id="fps_sample_button_border_color" class="fps_sliders_colorpick" name="fps_sample_button_border_color" value="">
											</div>
											<div class="fps-multi-option-input preimium-feature-block">
												<p><?php 
    esc_html_e( 'Border Width (px)', 'free-product-sample' );
    ?></p>
												<input type="number" placeholder="<?php 
    esc_attr_e( '1', 'free-product-sample' );
    ?>" value="" id="fps_sample_button_border_width" class="fps_sample_button_border_width">
											</div>
											<?php 
}
?>
									</div>
								</div>
							</td>
						</tr>
						<tr class="fps-sample-button-advance-fields fps-inner-setting">
							<th scope="row">
								<label class="fps_leble_setting" for="fps_sample_button_fontsize">
								<?php 
esc_html_e( 'Button Font Style', 'free-product-sample' );
?>
								<?php 
if ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ) {
    ?><span class="fps-pro-label"></span><?php 
}
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Set sample button font size and font weight.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>	
							</label>
							</th>
							<td>
								<div class="fps-multi-options">
									<div class="fps-multi-options-inner">
										<?php 
if ( fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code() ) {
    ?>
											<div class="fps-multi-option-input">
												<p><?php 
    esc_html_e( 'Font Size (px)', 'free-product-sample' );
    ?></p>
												<input type="number" placeholder="<?php 
    esc_attr_e( '18', 'free-product-sample' );
    ?>" value="<?php 
    esc_attr_e( $fps_sample_button_fontsize, 'free-product-sample' );
    ?>" id="fps_sample_button_fontsize" class="fps_sample_button_fontsize">
											</div>
											<div class="fps-multi-option-input">
												<p><?php 
    esc_html_e( 'Font Weight', 'free-product-sample' );
    ?></p>
												<select name="fps_sample_button_fontweight" id="fps_sample_button_fontweight">
													<option value="100" <?php 
    echo ( $fps_sample_button_fontweight === '100' ? 'selected' : '' );
    ?>><?php 
    esc_html_e( '100', 'free-product-sample' );
    ?></option>
													<option value="200" <?php 
    echo ( $fps_sample_button_fontweight === '200' ? 'selected' : '' );
    ?>><?php 
    esc_html_e( '200', 'free-product-sample' );
    ?></option>
													<option value="300" <?php 
    echo ( $fps_sample_button_fontweight === '300' ? 'selected' : '' );
    ?>><?php 
    esc_html_e( '300', 'free-product-sample' );
    ?></option>
													<option value="400" <?php 
    echo ( $fps_sample_button_fontweight === '400' ? 'selected' : '' );
    ?>><?php 
    esc_html_e( '400', 'free-product-sample' );
    ?></option>
													<option value="500" <?php 
    echo ( $fps_sample_button_fontweight === '500' ? 'selected' : '' );
    ?>><?php 
    esc_html_e( '500', 'free-product-sample' );
    ?></option>
													<option value="600" <?php 
    echo ( $fps_sample_button_fontweight === '600' ? 'selected' : '' );
    ?>><?php 
    esc_html_e( '600', 'free-product-sample' );
    ?></option>
													<option value="700" <?php 
    echo ( $fps_sample_button_fontweight === '700' ? 'selected' : '' );
    ?>><?php 
    esc_html_e( '700', 'free-product-sample' );
    ?></option>
													<option value="800" <?php 
    echo ( $fps_sample_button_fontweight === '800' ? 'selected' : '' );
    ?>><?php 
    esc_html_e( '800', 'free-product-sample' );
    ?></option>
													<option value="900" <?php 
    echo ( $fps_sample_button_fontweight === '900' ? 'selected' : '' );
    ?>><?php 
    esc_html_e( '900', 'free-product-sample' );
    ?></option>
												</select>
											</div>
											<?php 
} else {
    ?>
											<div class="fps-multi-option-input preimium-feature-block">
												<p><?php 
    esc_html_e( 'Font Size (px)', 'free-product-sample' );
    ?></p>
												<input type="number" placeholder="<?php 
    esc_attr_e( '18', 'free-product-sample' );
    ?>" value="" id="fps_sample_button_fontsize" class="fps_sample_button_fontsize">
											</div>
											<div class="fps-multi-option-input preimium-feature-block">
												<p><?php 
    esc_html_e( 'Font Weight', 'free-product-sample' );
    ?></p>
												<select name="fps_sample_button_fontweight" id="fps_sample_button_fontweight">
													<option value="100"><?php 
    esc_html_e( '100', 'free-product-sample' );
    ?></option>
												</select>
											</div>
											<?php 
}
?>
									</div>
								</div>
							</td>
						</tr>
						<tr class="fps-sample-button-advance-fields fps-inner-setting">
							<th scope="row">
								<label class="fps_leble_setting" for="fps_sample_button_padding_top">
								<?php 
esc_html_e( 'Button Padding (px)', 'free-product-sample' );
?>
								<?php 
if ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ) {
    ?><span class="fps-pro-label"></span><?php 
}
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Set padding for sample button.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>	
								</label>
							</th>
							<td>
								<div class="fps-multi-options">
									<div class="fps-multi-options-inner">
										<?php 
if ( fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code() ) {
    ?>
											<div class="fps-multi-option-input">
												<p><?php 
    esc_html_e( 'Top', 'free-product-sample' );
    ?></p>
												<input type="number" placeholder="<?php 
    esc_attr_e( '8', 'free-product-sample' );
    ?>" value="<?php 
    esc_attr_e( $top_padding, 'free-product-sample' );
    ?>" id="fps_sample_button_padding_top" class="fps_sample_button_padding_top">
											</div>
											<div class="fps-multi-option-input">
												<p><?php 
    esc_html_e( 'Bottom', 'free-product-sample' );
    ?></p>
												<input type="number" placeholder="<?php 
    esc_attr_e( '8', 'free-product-sample' );
    ?>" value="<?php 
    esc_attr_e( $bottom_padding, 'free-product-sample' );
    ?>" id="fps_sample_button_padding_bottom" class="fps_sample_button_padding_bottom">
											</div>
											<div class="fps-multi-option-input">
												<p><?php 
    esc_html_e( 'Left', 'free-product-sample' );
    ?></p>
												<input type="number" placeholder="<?php 
    esc_attr_e( '15', 'free-product-sample' );
    ?>" value="<?php 
    esc_attr_e( $left_padding, 'free-product-sample' );
    ?>" id="fps_sample_button_padding_left" class="fps_sample_button_padding_left">
											</div>
											<div class="fps-multi-option-input">
												<p><?php 
    esc_html_e( 'Right', 'free-product-sample' );
    ?></p>
												<input type="number" placeholder="<?php 
    esc_attr_e( '15', 'free-product-sample' );
    ?>" value="<?php 
    esc_attr_e( $right_padding, 'free-product-sample' );
    ?>" id="fps_sample_button_padding_right" class="fps_sample_button_padding_right">
											</div>
											<?php 
} else {
    ?>
											<div class="fps-multi-option-input preimium-feature-block">
												<p><?php 
    esc_html_e( 'Top', 'free-product-sample' );
    ?></p>
												<input type="number" placeholder="<?php 
    esc_attr_e( '8', 'free-product-sample' );
    ?>" value="" id="fps_sample_button_padding_top" class="fps_sample_button_padding_top">
											</div>
											<div class="fps-multi-option-input preimium-feature-block">
												<p><?php 
    esc_html_e( 'Bottom', 'free-product-sample' );
    ?></p>
												<input type="number" placeholder="<?php 
    esc_attr_e( '8', 'free-product-sample' );
    ?>" value="" id="fps_sample_button_padding_bottom" class="fps_sample_button_padding_bottom">
											</div>
											<div class="fps-multi-option-input preimium-feature-block">
												<p><?php 
    esc_html_e( 'Left', 'free-product-sample' );
    ?></p>
												<input type="number" placeholder="<?php 
    esc_attr_e( '15', 'free-product-sample' );
    ?>" value="" id="fps_sample_button_padding_left" class="fps_sample_button_padding_left">
											</div>
											<div class="fps-multi-option-input preimium-feature-block">
												<p><?php 
    esc_html_e( 'Right', 'free-product-sample' );
    ?></p>
												<input type="number" placeholder="<?php 
    esc_attr_e( '15', 'free-product-sample' );
    ?>" value="" id="fps_sample_button_padding_right" class="fps_sample_button_padding_right">
											</div>
											<?php 
}
?>
									</div>
								</div>
							</td>
						</tr>
						<tr class="fps-sample-button-advance-fields fps-inner-setting">
							<th scope="row">
								<label class="fps_leble_setting" for="fps_sample_order_limit"><?php 
esc_html_e( 'Button Margin (px)', 'free-product-sample' );
?>
								<?php 
if ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ) {
    ?><span class="fps-pro-label"></span><?php 
}
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Set margin for sample button.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>	</label>
							</th>
							<td>
								<div class="fps-multi-options">
									<div class="fps-multi-options-inner">
										<?php 
if ( fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code() ) {
    ?>
											<div class="fps-multi-option-input">
												<p><?php 
    esc_html_e( 'Top', 'free-product-sample' );
    ?></p>
												<input type="number" placeholder="<?php 
    esc_attr_e( '0', 'free-product-sample' );
    ?>" value="<?php 
    esc_attr_e( $top_margin, 'free-product-sample' );
    ?>" id="fps_sample_button_margin_top" class="fps_sample_button_margin_top">
											</div>
											<div class="fps-multi-option-input">
												<p><?php 
    esc_html_e( 'Bottom', 'free-product-sample' );
    ?></p>
												<input type="number" placeholder="<?php 
    esc_attr_e( '0', 'free-product-sample' );
    ?>" value="<?php 
    esc_attr_e( $bottom_margin, 'free-product-sample' );
    ?>" id="fps_sample_button_margin_bottom" class="fps_sample_button_margin_bottom">
											</div>
											<div class="fps-multi-option-input">
												<p><?php 
    esc_html_e( 'Left', 'free-product-sample' );
    ?></p>
												<input type="number" placeholder="<?php 
    esc_attr_e( '0', 'free-product-sample' );
    ?>" value="<?php 
    esc_attr_e( $left_margin, 'free-product-sample' );
    ?>" id="fps_sample_button_margin_left" class="fps_sample_button_margin_left">
											</div>
											<div class="fps-multi-option-input">
												<p><?php 
    esc_html_e( 'Right', 'free-product-sample' );
    ?></p>
												<input type="number" placeholder="<?php 
    esc_attr_e( '0', 'free-product-sample' );
    ?>" value="<?php 
    esc_attr_e( $right_margin, 'free-product-sample' );
    ?>" id="fps_sample_button_margin_right" class="fps_sample_button_margin_right">
											</div>
											<?php 
} else {
    ?>
											<div class="fps-multi-option-input preimium-feature-block">
												<p><?php 
    esc_html_e( 'Top', 'free-product-sample' );
    ?></p>
												<input type="number" placeholder="<?php 
    esc_attr_e( '0', 'free-product-sample' );
    ?>" value="" id="fps_sample_button_margin_top" class="fps_sample_button_margin_top">
											</div>
											<div class="fps-multi-option-input preimium-feature-block">
												<p><?php 
    esc_html_e( 'Bottom', 'free-product-sample' );
    ?></p>
												<input type="number" placeholder="<?php 
    esc_attr_e( '0', 'free-product-sample' );
    ?>" value="" id="fps_sample_button_margin_bottom" class="fps_sample_button_margin_bottom">
											</div>
											<div class="fps-multi-option-input preimium-feature-block">
												<p><?php 
    esc_html_e( 'Left', 'free-product-sample' );
    ?></p>
												<input type="number" placeholder="<?php 
    esc_attr_e( '0', 'free-product-sample' );
    ?>" value="" id="fps_sample_button_margin_left" class="fps_sample_button_margin_left">
											</div>
											<div class="fps-multi-option-input preimium-feature-block">
												<p><?php 
    esc_html_e( 'Right', 'free-product-sample' );
    ?></p>
												<input type="number" placeholder="<?php 
    esc_attr_e( '0', 'free-product-sample' );
    ?>" value="" id="fps_sample_button_margin_right" class="fps_sample_button_margin_right">
											</div>
											<?php 
}
?>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_product_title_type"><?php 
esc_html_e( 'Sample Product Type Title', 'free-product-sample' );
?>
								<?php 
if ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ) {
    ?><span class="fps-pro-label"></span><?php 
}
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Choose the title format for your sample products. For instance, if you select the prefix option and set the title as "Sample", it will display as "Sample - Your Product Name" on the cart page.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<?php 
?>
									<div class="fps-lock-option">
										<select class="fps-pro-features" name="fps_product_title_type" id="fps_product_title_type" disabled="disabled">
											<option value="prefix"><?php 
esc_html_e( 'Prefix', 'free-product-sample' );
?></option>
											<option value="suffix"><?php 
esc_html_e( 'Suffix', 'free-product-sample' );
?></option>
										</select>
									</div>
									<?php 
?>
							</td>
						</tr>
						<tr class="fps-product-prefix">
							<th scope="row">
								<label class="fps_leble_setting" for="fps_product_prefix_title"><?php 
esc_html_e( 'Prefix Title', 'free-product-sample' );
?>
								<?php 
if ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ) {
    ?><span class="fps-pro-label"></span><?php 
}
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Define a prefix title for your sample products. For example, you can set it as "Sample".', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<?php 
?>
									<div class="fps-lock-option">
										<input type="text" placeholder="<?php 
esc_attr_e( 'Sample', 'free-product-sample' );
?>" id="fps_product_prefix_title" class="fps_product_prefix_title fps-pro-features" disabled="disabled">
									</div>
									<?php 
?>
							</td>
						</tr>
						<?php 
?>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_settings_hide_on_shop"><?php 
esc_html_e( 'Hide on Shop Page', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Hide the sample button from the shop page by enabling this option.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<label class="fps_toggle_switch">
									<input type="checkbox" value="on" id="fps_settings_hide_on_shop"
									class="fps_settings_hide_on_shop" <?php 
checked( $fps_settings_hide_on_shop, 'on' );
?>>
									<span class="fps_toggle_btn"></span>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_settings_hide_on_category"><?php 
esc_html_e( 'Hide on Categories Page', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Hide the sample button from the categories page by enabling this option.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<label class="fps_toggle_switch">
									<input type="checkbox" value="on" id="fps_settings_hide_on_category"
									class="fps_settings_hide_on_category" <?php 
checked( $fps_settings_hide_on_category, 'on' );
?>>
									<span class="fps_toggle_btn"></span>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_settings_include_store_menu"><?php 
esc_html_e( 'Include on Store Menu', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'When enabled, the new menu item "Sample Products" will be added to the primary menu, and a list of all sample products will be showcased.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<label class="fps_toggle_switch">
									<input type="checkbox" value="on" id="fps_settings_include_store_menu"
									class="fps_settings_include_store_menu" <?php 
checked( $fps_settings_include_store_menu, 'on' );
?>>
									<span class="fps_toggle_btn"></span>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_sample_actual_prod_restriction"><?php 
esc_html_e( 'Prevent Sample & Real Products to Ordered', 'free-product-sample' );
?>
								<?php 
if ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ) {
    ?><span class="fps-pro-label"></span><?php 
}
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Enable this option to prevent customers from adding both sample products and actual products to the same cart. They will be unable to add sample products if actual products are already in the cart, and vice versa.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<?php 
?>
									<div class="fps-lock-option">
										<label class="fps_toggle_switch fps-pro-features">
											<input type="checkbox" value="on" id="fps_sample_actual_prod_restriction" class="fps_sample_actual_prod_restriction" disabled="disabled">
											<span class="fps_toggle_btn"></span>
										</label>
									</div>
									<?php 
?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<div class="fps-setting-main">
			<div class="fps-setting-header">
				<h2><?php 
esc_html_e( 'Ajax Add To Cart', 'free-product-sample' );
?></h2>
			</div>
			<div class="fps-section-content">
				<table class="form-table table-outer">
					<tbody>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_ajax_enable_disable"><?php 
esc_html_e( 'Enable Ajax Add to Cart', 'free-product-sample' );
?>
								<?php 
$url = esc_url( add_query_arg( array(
    'page' => 'wc-settings',
    'tab'  => 'products',
), admin_url( 'admin.php' ) ) );
echo sprintf( wp_kses_post( wc_help_tip( __( 'Enable AJAX add to cart for samples on the single product page. Please ensure that the "<a href="%1$s" target="_blank">Enable AJAX add to cart buttons on archives</a>" feature is enabled to utilize this functionality.', 'free-product-sample' ) ) ), esc_url( $url ) );
?>
								</label>
							</th>
							<td>
								<label class="fps_toggle_switch">
									<input type="checkbox" value="on" id="fps_ajax_enable_disable"
									class="fps_ajax_enable_disable" <?php 
checked( $fps_ajax_enable_disable, 'on' );
?>>
									<span class="fps_toggle_btn"></span>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_ajax_enable_dialog"><?php 
esc_html_e( 'Enable Ajax Dialog', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Enable a success message or dialog box to display after adding a sample to the cart using AJAX.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<label class="fps_toggle_switch">
									<input type="checkbox" value="on" id="fps_ajax_enable_dialog"
									class="fps_ajax_enable_dialog" <?php 
checked( $fps_ajax_enable_dialog, 'on' );
?>>
									<span class="fps_toggle_btn"></span>
								</label>
							</td>
						</tr>
						<tr class="fps-ajax-dialog-box-field fps-inner-setting">
							<th scope="row">
								<label class="fps_leble_setting" for="fps_ajax_sucess_message"><?php 
esc_html_e( 'Button Label', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Customize the message displayed in the AJAX dialog box after adding a sample to the cart.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<input type="text" placeholder="<?php 
esc_attr_e( 'Done!', 'free-product-sample' );
?>" value="<?php 
esc_attr_e( $fps_ajax_sucess_message, 'free-product-sample' );
?>" id="fps_ajax_sucess_message" class="fps_ajax_sucess_message">
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<div class="fps-setting-main">
			<div class="fps-setting-header">
				<h2><?php 
esc_html_e( 'Sample Specific', 'free-product-sample' );
?></h2>
			</div>
			<div class="fps-section-content">
				<table class="form-table table-outer">
					<tbody>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_sample_enable_type"><?php 
esc_html_e( 'Enable Type', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Select the enable type option to specify whether you want to enable samples for specific products or categories.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<select name="fps_sample_enable_type" id="fps_sample_enable_type">
									<option value="product_wise" <?php 
echo ( $fps_sample_enable_type === 'product_wise' ? 'selected' : '' );
?>><?php 
esc_html_e( 'Product Wise', 'free-product-sample' );
?></option>
									<option value="category_wise" <?php 
echo ( $fps_sample_enable_type === 'category_wise' ? 'selected' : '' );
?>><?php 
esc_html_e( 'Category Wise', 'free-product-sample' );
?></option>
								</select>
							</td>
						</tr>
						<tr class="fps-product-wise">
							<th scope="row">
								<label class="fps_leble_setting" for="fps_select_product_list"><?php 
esc_html_e( 'Select Products', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'If you want to add a sample button to the specific products, select the desired products. Leave empty to enable samples for the entire store.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<select name="fps_select_product_list[]" id="fps_select_product_list" multiple="multiple" data-placeholder="<?php 
esc_attr_e( 'Select a product', 'free-product-sample' );
?>" data-minimum_input_length="3">
									<?php 
$fps_select_product_list = ( is_array( $fps_select_product_list ) ? $fps_select_product_list : [$fps_select_product_list] );
echo wp_kses( $dsfps_admin_object->dsfps_get_simple_and_variation_product_options( $fps_select_product_list ), $dsfps_admin_object->dsfps_allowed_html_tags() );
?>
								</select>
							</td>
						</tr>
						<tr class="fps-category-wise">
							<th scope="row">
								<label class="fps_leble_setting" for="fps_select_category_list"><?php 
esc_html_e( 'Select Categories', 'free-product-sample' );
?>
								<?php 
if ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ) {
    ?><span class="fps-pro-label"></span><?php 
}
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'If you want to add a sample button to the specific categories, select the desired categories. Leave empty to enable samples for the entire store.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<?php 
?>
									<div class="fps-lock-option">
										<select class="fps-pro-features" name="fps_select_category_list[]" id="fps_select_category_list" disabled="disabled">
											<option value="none"><?php 
esc_html_e( 'Select a Category', 'free-product-sample' );
?></option>
										</select>
									</div>
									<?php 
?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<div class="fps-setting-main">
			<div class="fps-setting-header">
				<h2><?php 
esc_html_e( 'Price Adjustment', 'free-product-sample' );
?></h2>
			</div>
			<div class="fps-section-content">
				<table class="form-table table-outer">
					<tbody>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_sample_price_type"><?php 
esc_html_e( 'Price Type', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Select the price type option for the sample products.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<select name="fps_sample_price_type" id="fps_sample_price_type">
									<option value="flat_price" <?php 
echo ( $fps_sample_price_type === 'flat_price' ? 'selected' : '' );
?>><?php 
esc_html_e( 'Flat', 'free-product-sample' );
?></option>
									<option value="percentage_price" <?php 
echo ( $fps_sample_price_type === 'percentage_price' ? 'selected' : '' );
?>><?php 
esc_html_e( 'Percentage', 'free-product-sample' );
?></option>
								</select>
							</td>
						</tr>
						<tr class="fps-price-flat">
							<th scope="row">
								<label class="fps_leble_setting" for="fps_sample_flat_price"><?php 
esc_html_e( 'Add Flat Price', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Specify the flat or fixed price for your sample product.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<input type="number" placeholder="<?php 
esc_attr_e( '0.00', 'free-product-sample' );
?>" value="<?php 
esc_attr_e( $fps_sample_flat_price, 'free-product-sample' );
?>" id="fps_sample_flat_price" class="fps_sample_flat_price">
							</td>
						</tr>
						<tr class="fps-price-percent">
							<th scope="row">
								<label class="fps_leble_setting" for="fps_sample_percent_price"><?php 
esc_html_e( 'Add Percentage Price', 'free-product-sample' );
?>
								<?php 
if ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ) {
    ?><span class="fps-pro-label"></span><?php 
}
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Specify the percentage price for your sample product.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<?php 
?>
									<div class="fps-lock-option">
										<input type="number" placeholder="<?php 
esc_attr_e( '0.00', 'free-product-sample' );
?>" id="fps_sample_percent_price" class="fps_sample_percent_price fps-pro-features" disabled="disabled">
									</div>
									<?php 
?>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_sample_product_fixed_price"><?php 
esc_html_e( 'Fixed Price Per Product', 'free-product-sample' );
?>
								<?php 
if ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ) {
    ?><span class="fps-pro-label"></span><?php 
}
?>
								<?php 
$url = esc_url( add_query_arg( array(
    'page' => 'wc-settings',
    'tab'  => 'products',
), admin_url( 'admin.php' ) ) );
echo sprintf( wp_kses_post( wc_help_tip( __( 'When enabled, the product price remains fixed, no matter how many quantities are added to the cart. The total cost will always equal the sample price, multiplied by one. For example, if the sample price is $1 and you add 2 quantities of a sample product, the subtotal will still be $1 instead of $2.', 'free-product-sample' ) ) ), esc_url( $url ) );
?>
								</label>
							</th>
							<td>
								<?php 
?>
									<div class="fps-lock-option">
										<label class="fps_toggle_switch fps-pro-features">
											<input type="checkbox" value="on" id="fps_sample_product_fixed_price" class="fps_sample_product_fixed_price" disabled="disabled">
											<span class="fps_toggle_btn"></span>
										</label>
									</div>
									<?php 
?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<div class="fps-setting-main">
			<div class="fps-setting-header">
				<h2><?php 
esc_html_e( 'Order Restrictions', 'free-product-sample' );
?></h2>
			</div>
			<div class="fps-section-content">
				<table class="form-table table-outer">
					<tbody>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_sample_quantity_type"><?php 
esc_html_e( 'Maximum Quantity Type', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Choose the maximum quantity type for your sample products.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<select name="fps_sample_quantity_type" id="fps_sample_quantity_type">
									<option value="per_product" <?php 
echo ( $fps_sample_quantity_type === 'per_product' ? 'selected' : '' );
?>><?php 
esc_html_e( 'Per Sample Quantity', 'free-product-sample' );
?></option>
									<option value="per_order" <?php 
echo ( $fps_sample_quantity_type === 'per_order' ? 'selected' : '' );
?>><?php 
esc_html_e( 'Per Order Quantity', 'free-product-sample' );
?></option>
								</select>
							</td>
						</tr>
						<tr class="fps-quantity-product">
							<th scope="row">
								<label class="fps_leble_setting" for="fps_quantity_per_product"><?php 
esc_html_e( 'Per Sample Quantity', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Specify the maximum quantity for each sample product. Leave it empty if you don\'t want to set a maximum limit.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<input type="number" placeholder="<?php 
esc_attr_e( '5', 'free-product-sample' );
?>" value="<?php 
esc_attr_e( $fps_quantity_per_product, 'free-product-sample' );
?>" id="fps_quantity_per_product" class="fps_quantity_per_product">
							</td>
						</tr>
						<tr class="fps-quantity-product-msg">
							<th scope="row">
								<label class="fps_leble_setting" for="fps_max_quantity_message"><?php 
esc_html_e( 'Maximum Quantity Message', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Enter a custom message for the per-sample quantity limitation. Use {PRODUCT} to show the product name and {QTY} to show the maximum quantity.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<textarea id="fps_max_quantity_message" class="fps_max_quantity_message" placeholder="<?php 
esc_attr_e( 'Sorry! you reached maximum sample limit for this product!', 'free-product-sample' );
?>"><?php 
esc_html_e( $fps_max_quantity_message, 'free-product-sample' );
?></textarea>
							</td>
						</tr>
						<tr class="fps-quantity-order">
							<th scope="row">
								<label class="fps_leble_setting" for="fps_quantity_per_order"><?php 
esc_html_e( 'Per Order Quantity', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Specify the maximum quantity allowed for each sample product order. Leave it empty if you don\'t want to set any maximum limit.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<input type="number" placeholder="<?php 
esc_attr_e( '5', 'free-product-sample' );
?>" value="<?php 
esc_attr_e( $fps_quantity_per_order, 'free-product-sample' );
?>" id="fps_quantity_per_order" class="fps_quantity_per_order">
							</td>
						</tr>
						<tr class="fps-quantity-order-msg">
							<th scope="row">
								<label class="fps_leble_setting" for="fps_max_quantity_per_order_msg"><?php 
esc_html_e( 'Maximum Quantity Message', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Enter your custom message here to display quantity limitations per order. Use {MAX_QTY} to represent the maximum quantity allowed and {CART_QTY} to display the current quantity in the cart.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<textarea id="fps_max_quantity_per_order_msg" class="fps_max_quantity_per_order_msg" placeholder="<?php 
esc_attr_e( 'The maximum allows order quantity is {MAX_QTY} for sample product and you have {CART_QTY} in your cart.', 'free-product-sample' );
?>"><?php 
esc_html_e( $fps_max_quantity_per_order_msg, 'free-product-sample' );
?></textarea>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_convert_samples_to_order"><?php 
esc_html_e( 'Convert Samples to Orders on Re-order', 'free-product-sample' );
?>
								<?php 
if ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ) {
    ?><span class="fps-pro-label"></span><?php 
}
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Enable to allow customers to convert their sample orders with regular price orders on the My Account page Re-order actions.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<?php 
?>
									<div class="fps-lock-option">
										<label class="fps_toggle_switch fps-pro-features">
											<input type="checkbox" value="on" id="fps_convert_samples_to_order" class="fps_convert_samples_to_order" disabled="disabled">
											<span class="fps_toggle_btn"></span>
										</label>
									</div>
									<?php 
?>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_enable_sample_order_limit"><?php 
esc_html_e( 'Enable Sample Order Limit', 'free-product-sample' );
?>
								<?php 
if ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ) {
    ?><span class="fps-pro-label"></span><?php 
}
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Set order limits for samples to restrict multiple sample orders from same customer. For instance, if the limit is set to 1, A customer can only submit a sample order once.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<?php 
?>
									<div class="fps-lock-option">
										<label class="fps_toggle_switch fps-pro-features">
											<input type="checkbox" value="on" id="fps_enable_sample_order_limit" class="fps_enable_sample_order_limit" disabled="disabled">
											<span class="fps_toggle_btn"></span>
										</label>
									</div>
									<?php 
?>
							</td>
						</tr>
						<?php 
?>
					</tbody>
				</table>
			</div>
		</div>
		<div class="fps-setting-main">
			<div class="fps-setting-header">
				<h2><?php 
esc_html_e( 'User Specific', 'free-product-sample' );
?></h2>
			</div>
			<div class="fps-section-content">
				<table class="form-table table-outer">
					<tbody>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_select_users_list"><?php 
esc_html_e( 'User Based', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Choose your desired users if you want to enable the sample button for specific users.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<select name="fps_select_users_list[]" id="fps_select_users_list" multiple="multiple" data-placeholder="<?php 
esc_attr_e( 'Select a user', 'free-product-sample' );
?>">
									<?php 
// get user list
$fps_select_users_list = ( is_array( $fps_select_users_list ) ? $fps_select_users_list : [$fps_select_users_list] );
echo wp_kses( $dsfps_admin_object->dsfps_get_users_list( $fps_select_users_list ), $dsfps_admin_object->dsfps_allowed_html_tags() );
?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_select_user_role_list"><?php 
esc_html_e( 'User Role Based', 'free-product-sample' );
?>
								<?php 
if ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ) {
    ?><span class="fps-pro-label"></span><?php 
}
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Choose your desired user roles if you want to enable the sample button for specific user roles.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<?php 
?>
									<div class="fps-lock-option">
										<select class="fps-pro-features" name="fps_select_user_role_list[]" id="fps_select_user_role_list" disabled="disabled">
											<option value="none"><?php 
esc_html_e( 'Select a User Role', 'free-product-sample' );
?></option>
										</select>
									</div>
									<?php 
?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<div class="fps-setting-main <?php 
echo ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ? esc_attr( 'preimium-feature-block' ) : '' );
?>">
			<div class="fps-setting-header">
				<h2>
					<?php 
esc_html_e( 'Shipping & Tax Class Specific', 'free-product-sample' );
if ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ) {
    ?><span class="fps-pro-label"></span><?php 
}
?>		
				</h2>
			</div>
			<div class="fps-section-content">
				<table class="form-table table-outer">
					<tbody>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_select_shipping_class"><?php 
esc_html_e( 'Shipping Class', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Choose a shipping class to apply to sample products.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<?php 
?>
									<div class="fps-lock-option">
										<select class="fps-pro-features" name="fps_select_shipping_class" id="fps_select_shipping_class" disabled="disabled">
											<option value="none"><?php 
esc_html_e( 'No Shipping Class', 'free-product-sample' );
?></option>
										</select>
									</div>
									<?php 
?>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_select_tax_class"><?php 
esc_html_e( 'Tax Class', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Choose a tax class to apply to sample products.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<?php 
?>
									<div class="fps-lock-option">
										<select class="fps-pro-features" name="fps_select_tax_class" id="fps_select_tax_class" disabled="disabled">
											<option value="none"><?php 
esc_html_e( 'No Tax Class', 'free-product-sample' );
?></option>
										</select>
									</div>
									<?php 
?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<div class="fps-setting-main <?php 
echo ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ? esc_attr( 'preimium-feature-block' ) : '' );
?>">
			<div class="fps-setting-header">
				<h2>
					<?php 
esc_html_e( 'Sample Promo', 'free-product-sample' );
if ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ) {
    ?><span class="fps-pro-label"></span><?php 
}
?>
				</h2>
			</div>
			<div class="fps-section-content">
				<table class="form-table table-outer">
					<tbody>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_promo_enable_disable"><?php 
esc_html_e( 'Enable/Disable', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Enable this option to add a promotional button on all pages for effective sample product promotion.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<?php 
?>
									<label class="fps_toggle_switch fps-pro-features">
										<input type="checkbox" value="on" id="fps_promo_enable_disable" class="fps_promo_enable_disable" disabled="disabled">
										<span class="fps_toggle_btn"></span>
									</label>
									<?php 
?>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_promo_button_label"><?php 
esc_html_e( 'Promo Button Label', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Set a custom text for your sample promo button here.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<?php 
?>
									<input type="text" placeholder="<?php 
esc_attr_e( 'Exclusive Offers', 'free-product-sample' );
?>" id="fps_promo_button_label" class="fps_promo_button_label fps-pro-features" disabled="disabled">
									<?php 
?>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting"><?php 
esc_html_e( 'Promo Button Color', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Customize the colors of your sample promo button.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<div class="fps-multi-options">
									<div class="fps-multi-options-inner">
										<?php 
?>
											<div class="fps-multi-option-input fps-pro-color-feature">
												<p><?php 
esc_html_e( 'Color', 'free-product-sample' );
?></p>
												<input type="text" id="fps_promo_button_color" class="fps_sliders_colorpick fps-pro-features" name="fps_promo_button_color" disabled="disabled">
											</div>
											<div class="fps-multi-option-input fps-pro-color-feature">
												<p><?php 
esc_html_e( 'Background', 'free-product-sample' );
?></p>
												<input type="text" id="fps_promo_button_bg_color" class="fps_sliders_colorpick fps-pro-features" name="fps_promo_button_bg_color" disabled="disabled">
											</div>
											<?php 
?>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_promo_button_url"><?php 
esc_html_e( 'Promo Button URL', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Enter the link for your sample promo button. It will redirect to provided link when any user clicks on this promo button.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<?php 
?>
									<input type="text" placeholder="<?php 
esc_attr_e( 'https://www.google.com/', 'free-product-sample' );
?>" id="fps_promo_button_url" class="fps_promo_button_url fps-pro-features" disabled="disabled">
									<?php 
?>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_promo_btn_link_target"><?php 
esc_html_e( 'Promo Link Target', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Choose the target for your sample promo button link.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<?php 
?>
									<select name="fps_promo_btn_link_target" id="fps_promo_btn_link_target" class="fps-pro-features" disabled="disabled">
										<option value="_self"><?php 
esc_html_e( '_self', 'free-product-sample' );
?></option>
										<option value="_blank"><?php 
esc_html_e( '_blank', 'free-product-sample' );
?></option>
									</select>
									<?php 
?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<div class="fps-setting-main <?php 
echo ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ? esc_attr( 'preimium-feature-block' ) : '' );
?>">
			<div class="fps-setting-header">
				<h2>
					<?php 
esc_html_e( 'Sample Follow Up Email', 'free-product-sample' );
if ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ) {
    ?><span class="fps-pro-label"></span><?php 
}
?>	
				</h2>
			</div>
			<div class="fps-section-content fps-follow-up-email-content">
				<?php 
?>
				<table class="form-table table-outer">
					<tbody>
						<tr>
							<th scope="row">
								<label class="fps_leble_setting" for="fps_follow_email_gl_number"><?php 
esc_html_e( 'Follow Up Email After', 'free-product-sample' );
?>
								<?php 
echo wp_kses( wc_help_tip( esc_html__( 'Specify the timing for the follow-up email.', 'free-product-sample' ) ), array(
    'span' => $allowed_tooltip_html,
) );
?>
								</label>
							</th>
							<td>
								<div class="fps-multi-options">
									<div class="fps-multi-options-inner">
										<div class="fps-multi-option-input">
											<?php 
?>
												<input type="number" placeholder="<?php 
esc_attr_e( '10', 'free-product-sample' );
?>" id="fps_follow_email_gl_number" class="fps_follow_email_gl_number fps-pro-features" disabled="disabled">
												<?php 
?>
										</div>
										<div class="fps-multi-option-input">
											<?php 
?>
												<select name="fps_follow_email_gl_time_type" id="fps_follow_email_gl_time_type" class="fps-pro-features" disabled="disabled">
													<option value="minutes"><?php 
esc_html_e( 'Minutes', 'free-product-sample' );
?></option>
													<option value="hours"><?php 
esc_html_e( 'Hours', 'free-product-sample' );
?></option>
													<option value="days"><?php 
esc_html_e( 'Days', 'free-product-sample' );
?></option>
													<option value="weeks"><?php 
esc_html_e( 'Weeks', 'free-product-sample' );
?></option>
													<option value="months"><?php 
esc_html_e( 'Months', 'free-product-sample' );
?></option>
												</select>
												<?php 
?>
										</div>
									</div>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<div class="fps-save-button bottom-save-button">
			<img class="fps-setting-loader"
				src="<?php 
echo esc_url( plugin_dir_url( __FILE__ ) . 'images/ajax-loader.gif' );
?>"
				alt="ajax-loader" />
			<input type="button" name="save_dsfps" id="save_dsfps_setting" class="button button-primary button-large"
				value="<?php 
esc_attr_e( 'Save Changes', 'free-product-sample' );
?>">
		</div>
	</div>
	<!-- Upgrade to pro popup -->
    <?php 
if ( !(fps_fs()->is__premium_only() && fps_fs()->can_use_premium_code()) ) {
    require_once DSFPS_PLUGIN_DIR_PATH . 'admin/partials/dots-upgrade-popup.php';
}
?>
</div>
</div>
</div>
</div>
