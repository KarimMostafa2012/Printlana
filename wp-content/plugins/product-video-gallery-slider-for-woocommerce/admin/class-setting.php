<?php
/**
@package WC_PRODUCT_VIDEO_GALLERY_SETTING
-------------------------------------------------*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
	Settings Class
 */
if ( ! class_exists( 'WC_PRODUCT_VIDEO_GALLERY_SETTING' ) ) {
	class WC_PRODUCT_VIDEO_GALLERY_SETTING {
		/** @var $extend Lic value */
		public $extend;

		function __construct() {
			$this->add_actions( new NICKX_LIC_CLASS() );
		}
		private function add_actions( $extend ) {
			$this->extend = $extend;
			add_action( 'admin_notices', array( $this, 'nickx_notice_callback_notice' ) );
			add_action( 'admin_menu', array( $this, 'wc_product_video_gallery_setup' ) );
			add_action( 'admin_init', array( $this, 'update_wc_product_video_gallery_options' ) );
			add_filter( 'plugin_action_links_' . NICKX_PLUGIN_BASE, array( $this, 'wc_prd_vid_slider_settings_link' ) );
		}
		public function nickx_notice_callback_notice() {
			if ( get_transient( 'nickx-plugin_setting_notice' ) ) {
				echo '<div class="notice-info notice is-dismissible"><p><strong>Product Video Gallery for Woocommerce is almost ready.</strong> To Complete Your Configuration, <a href="' . esc_url( admin_url() ) . 'edit.php?post_type=product&page=wc-product-video">Complete the setup</a>.</p></div>';
				delete_transient( 'nickx-plugin_setting_notice' );
			}
		}
		public function wc_product_video_gallery_setup() {
			add_submenu_page( 'edit.php?post_type=product', 'Product Video Gallery for Woocommerce', 'Product Video WC', 'manage_options', 'wc-product-video', array( $this, 'wc_product_video_callback' ) );
		}
		public function wc_product_video_callback() {
			wp_enqueue_media();
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
			echo '<style type="text/css">
			.boxed{padding:30px 0}
			.techno_tabs label{font-family:sans-serif;font-weight:400;vertical-align:top;font-size:15px}
			.wc_product_video_aria .techno_main_tabs{float:left;border:1px solid #ccc;border-bottom:none;margin-right:.5em;font-size:14px;line-height:1.71428571;font-weight:600;background:#e5e5e5;text-decoration:none;white-space:nowrap}
			.wc_product_video_aria .techno_main_tabs a{display:block;padding:5px 10px;text-decoration:none;color:#555}
			.wc_product_video_aria .main-panel{overflow:hidden;border-bottom:1px solid #ccc}
			.wc_product_video_aria .techno_main_tabs.active a{background:#f1f1f1}
			.wc_product_video_aria .techno_main_tabs a:focus{box-shadow:none;outline:0 solid transparent}
			.wc_product_video_aria .techno_main_tabs{display:inline-block;float:left}
			.wc_product_video_aria .techno_main_tabs.active{margin-bottom:-1px}
			.techno_tabs.tab_premium label{vertical-align:middle}
			.col-50{width:46%;float:left}
			.submit_btn_cls p{text-align: right;}
			.col-50 img{width:183px;float:left}tr.primium_aria {opacity: 0.5;cursor: help;}
			.primium_aria label, .primium_aria input { pointer-events: none; cursor: not-allowed;}
			.content_right a{background:#00f;font-family:"Trebuchet MS",sans-serif!important;display:inline-block;text-decoration:none;color:#fff;font-weight:700;background-color:#538fbe;padding:10px 40px;font-size:20px;border:1px solid #2d6898;background-image:linear-gradient(bottom,#4984b4 0,#619bcb 100%);background-image:-o-linear-gradient(bottom,#4984b4 0,#619bcb 100%);background-image:-moz-linear-gradient(bottom,#4984b4 0,#619bcb 100%);background-image:-webkit-linear-gradient(bottom,#4984b4 0,#619bcb 100%);background-image:-ms-linear-gradient(bottom,#4984b4 0,#619bcb 100%);background-image:-webkit-gradient(linear,left bottom,left top,color-stop(0,#4984b4),color-stop(1,#619bcb) );-webkit-border-radius:5px;-moz-border-radius:5px;border-radius:5px;text-shadow:0 -1px 0 rgba(0,0,0,.5);-webkit-box-shadow:0 0 0 #2b638f,0 3px 15px rgba(0,0,0,.4),inset 0 1px 0 rgba(255,255,255,.3),inset 0 0 3px rgba(255,255,255,.5);-moz-box-shadow:0 0 0 #2b638f,0 3px 15px rgba(0,0,0,.4),inset 0 1px 0 rgba(255,255,255,.3),inset 0 0 3px rgba(255,255,255,.5);box-shadow:0 0 0 #2b638f,0 3px 15px rgba(0,0,0,.4),inset 0 1px 0 rgba(255,255,255,.3),inset 0 0 3px rgba(255,255,255,.5);margin-top:10px}</style>
			<div class="wc-product-video-title">
				<h1>Product Video Gallery for Woocommerce</h1>
			</div>';
			if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'nickx-license-deactive' ) && isset( $_REQUEST['deactivate_techno_wc_product_video_license'] ) ) {
				if ( $this->extend->nickx_deactive() ) {
					echo '<div id="message" class="updated fade"><p><strong>You license Deactivated successfuly...!!!</strong></p></div>';
				} else {
					echo '<div id="message" class="updated fade" style="border-left-color:#a00;"><p><strong>' . esc_html( $this->extend->err ) . '</strong></p></div>';
				}
			}
			$lic_chk_stateus = $this->extend->is_nickx_act_lic();
			if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'nickx-license-active' ) && isset( $_REQUEST['activate_license_techno'] ) && ! empty( $_POST['techno_wc_product_video_license_key'] ) ) {
				delete_transient( 'nickx_wp_plugin_status' );
				$lic_chk_stateus = $this->extend->nickx_act_call( sanitize_text_field( $_POST['techno_wc_product_video_license_key'] ) );
			}
			echo '<div class="wrap tab_wrapper wc_product_video_aria">
			<div class="main-panel">
				<div id="tab_dashbord" class="techno_main_tabs active"><a href="#dashbord">Dashboard</a></div>
				<div id="tab_premium" class="techno_main_tabs"><a href="#premium">Premium</a></div>
			</div>
			<div class="boxed" id="percentage_form">
				<div class="techno_tabs tab_dashbord">
					<div class="wrap woocommerce">
						<form method="post" action="options.php">';
							settings_fields( 'wc_product_video_gallery_options' );
							do_settings_sections( 'wc_product_video_gallery_options' ); echo '
							<h2>WC Product Video Gallery Settings</h2>
							<div id="wc_prd_vid_slider-description">
								<p>The following options are used to configure WC Product Video Gallery</p>
							</div>
							<table class="form-table">
								<tbody>
									<tr valign="top">
										<th scope="row" class="titledesc">
											<label for="nickx_slider_layout">Slider Layout </label>
										</th>
										<td class="forminp forminp-select">
											<select name="nickx_slider_layout" id="nickx_slider_layout">
												<option value="horizontal" ' . selected( 'horizontal', get_option( 'nickx_slider_layout' ), false ) . '>Horizontal</option>
												<option value="left" ' . selected( 'left', get_option( 'nickx_slider_layout' ), false ) . '>Vertical Left</option>
												<option value="right" ' . selected( 'right', get_option( 'nickx_slider_layout' ), false ) . '>Vertical Right</option>
											</select>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" class="titledesc"><label for="nickx_slider_responsive">Slider Responsive</label></th>
										<td class="forminp forminp-checkbox">
											<input name="nickx_slider_responsive" id="nickx_slider_responsive" type="checkbox" value="yes" ' . checked( 'yes', get_option( 'nickx_slider_responsive' ), false ) . '>
											<samll class="lbl_tc">This option set the slider layout as Horizontal on mobile.</samll>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" class="titledesc"><label for="nickx_sliderautoplay">Slider Auto-play</label></th>
										<td class="forminp forminp-checkbox">
											<input name="nickx_sliderautoplay" id="nickx_sliderautoplay" type="checkbox" value="yes" ' . checked( 'yes', get_option( 'nickx_sliderautoplay' ), false ) . '>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" class="titledesc"><label for="nickx_sliderfade">Slider Fade</label></th>
										<td class="forminp forminp-checkbox">
											<input name="nickx_sliderfade" id="nickx_sliderfade" type="checkbox" value="yes" ' . checked( 'yes', get_option( 'nickx_sliderfade' ), false ) . '>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" class="titledesc"><label for="nickx_arrowinfinite">Slider Infinite Loop</label></th>
										<td class="forminp forminp-checkbox">
											<input name="nickx_arrowinfinite" id="nickx_arrowinfinite" type="checkbox" value="yes" ' . checked( 'yes', get_option( 'nickx_arrowinfinite' ), false ) . '>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" class="titledesc"><label for="nickx_arrowdisable">Arrow on Slider</label></th>
										<td class="forminp forminp-checkbox">
											<input name="nickx_arrowdisable" id="nickx_arrowdisable" type="checkbox" value="yes" ' . checked( 'yes', get_option( 'nickx_arrowdisable' ), false ) . '>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" class="titledesc"><label for="nickx_arrow_thumb">Arrow on Thumbnails</label></th>
										<td class="forminp forminp-checkbox">
											<input name="nickx_arrow_thumb" id="nickx_arrow_thumb" type="checkbox" value="yes" ' . checked( 'yes', get_option( 'nickx_arrow_thumb' ), false ) . '>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" class="titledesc"><label for="custom_icon">Video Thumbnail for all Products.</label></th>
										<td class="forminp forminp-checkbox">
											<img style="max-width:80px;max-height:80px;" id="custom_video_thumb" src="' . esc_url( wp_get_attachment_image_url( get_option( 'custom_icon' ), 'thumbnail' ) ) . '">
											<input type="hidden" name="custom_icon" id="custom_icon" value="' . esc_attr( get_option( 'custom_icon' ) ) . '"/>
											<lable type="submit" class="upload_image_button button">Select Thumbnail</lable>
											<lable type="submit" class="remove_image_button button">X</lable>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" class="titledesc"><label for="nickx_show_lightbox">Light-box</label></th>
										<td class="forminp forminp-checkbox">
											<input name="nickx_show_lightbox" id="nickx_show_lightbox" type="checkbox" value="yes" ' . checked( 'yes', get_option( 'nickx_show_lightbox' ), false ) . '>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" class="titledesc"><label for="nickx_show_zoom">Zoom style</label></th>
										<td class="forminp forminp-checkbox">
											<select name="nickx_show_zoom" id="nickx_show_zoom">
												<option value="window" ' . selected( 'window', get_option( 'nickx_show_zoom' ), false ) . '>Window Right side</option>
												<option value="yes" ' . selected( 'yes', get_option( 'nickx_show_zoom' ), false ) . '>Inner</option>
												<option value="lens" ' . selected( 'lens', get_option( 'nickx_show_zoom' ), false ) . '>Lens</option>
												<option value="off" ' . selected( 'off', get_option( 'nickx_show_zoom' ), false ) . '>Off</option>
											</select>
										</td>
									</tr>									
									<tr valign="top">
										<th scope="row" class="titledesc"><label for="nickx_mobile_zoom">Disable Zoom on Mobile</label></th>
										<td class="forminp forminp-checkbox">
											<input name="nickx_mobile_zoom" id="nickx_mobile_zoom" type="checkbox" value="yes" ' . checked( 'yes', get_option( 'nickx_mobile_zoom' ), false ) . '>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" class="titledesc"><label for="nickx_zoomlevel">Zoom Level</label></th>
										<td class="forminp forminp-checkbox">
											<input name="nickx_zoomlevel" id="nickx_zoomlevel" type="number" min="0.1" max="10" step="0.01" value="' . esc_attr( get_option( 'nickx_zoomlevel', 1 ) ) . '">
										</td>
									</tr>									
									<tr valign="top">
										<th scope="row" class="titledesc"><label for="nickx_template">Allow Template Filter</label></th>
										<td class="forminp forminp-checkbox">
											<input name="nickx_template" id="nickx_template" type="checkbox" value="yes" ' . checked( 'yes', get_option( 'nickx_template', 'no' ), false ) . '>
											<samll class="lbl_tc">Enable this if your single product pages edited with help of any page builders Divi Builder, Elementor Builder etc.</samll>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" class="titledesc"><label for="nickx_gallery_action">Remove Action</label></th>
										<td class="forminp forminp-checkbox">
											<input name="nickx_gallery_action" id="nickx_gallery_action" type="checkbox" value="yes" ' . checked( 'yes', get_option( 'nickx_gallery_action', 'no' ), false ) . '>
											<samll class="lbl_tc">Enable this if your single product pages edited with help of Divi Builder.</samll>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" class="titledesc"><label for="nickx_thumbnails_hook">Enable Thumbnails Hook</label></th>
										<td class="forminp forminp-checkbox">
											<input name="nickx_thumbnails_hook" id="nickx_thumbnails_hook" type="checkbox" value="yes" ' . checked( 'yes', get_option( 'nickx_thumbnails_hook', 'no' ), false ) . '>
											<samll class="lbl_tc"><code>woocommerce_product_thumbnails</code> This hook is used to inject custom code, such as adding a badge to the product gallery.</samll>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" class="titledesc"><label for="nickx_hide_thumbnails">Hide Thumbnails</label></th>
										<td class="forminp forminp-checkbox">
											<input name="nickx_hide_thumbnails" id="nickx_hide_thumbnails" type="checkbox" value="yes" ' . checked( 'yes', get_option( 'nickx_hide_thumbnails' ), false ) . '>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" class="titledesc"><label for="nickx_hide_thumbnail">Hide Thumbnail</label></th>
										<td class="forminp forminp-checkbox">
											<input name="nickx_hide_thumbnail" id="nickx_hide_thumbnail" type="checkbox" value="yes" ' . checked( 'yes', get_option( 'nickx_hide_thumbnail', 'yes' ), false ) . '>
											<samll class="lbl_tc">Hide thumbnail if product have only one image/video.</samll>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" class="titledesc"><label for="nickx_thumbnails_to_show">Thumbnails to show</label></th>
										<td class="forminp forminp-checkbox">
											<input name="nickx_thumbnails_to_show" id="nickx_thumbnails_to_show" type="number" min="3" max="8" value="' . esc_attr( get_option( 'nickx_thumbnails_to_show', 4 ) ) . '"><small> Set how many thumbnails to show. You can show min 3 and  max 8 thumbnails.</small>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" class="titledesc"><label for="nickx_adaptive_height">Adaptive Height</label></th>
										<td class="forminp forminp-checkbox">
											<input name="nickx_adaptive_height" id="nickx_adaptive_height" type="checkbox" value="yes" ' . checked( 'yes', get_option( 'nickx_adaptive_height', 'yes' ), false ) . '>
											<samll class="lbl_tc">Slider height based on images automatically.</samll>
										</td>
									</tr>
									<tr valign="top" ' . ( ( $lic_chk_stateus ) ? '' : 'class="primium_aria" title="AVAILABLE IN PREMIUM VERSION"' ) . '">
										<th scope="row" class="titledesc"><label for="nickx_show_only_video">Show Only Videos</label></th>
										<td class="forminp forminp-checkbox">
											<input name="nickx_show_only_video" id="nickx_show_only_video" type="checkbox" value="yes" ' . checked( 'yes', get_option( 'nickx_show_only_video', 'no' ), false ) . '>
											<samll>Only show the videos on gallery.</samll>
										</td>
									</tr>
									<tr valign="top" ' . ( ( $lic_chk_stateus ) ? '' : 'class="primium_aria" title="AVAILABLE IN PREMIUM VERSION"' ) . '">
										<th scope="row" class="titledesc"><label for="nickx_controls">Show Video Controls</label></th>
										<td class="forminp forminp-checkbox">
											<input name="nickx_controls" id="nickx_controls" type="checkbox" value="yes" ' . checked( 'yes', get_option( 'nickx_controls', 'yes' ), false ) . '>
											<samll class="lbl_tc">Only for Self Hosted Video</samll>
										</td>
									</tr>
									<tr valign="top" ' . ( ( $lic_chk_stateus ) ? '' : 'class="primium_aria" title="AVAILABLE IN PREMIUM VERSION"' ) . '">
										<th scope="row" class="titledesc"><label for="nickx_poster_img">Video Poster Image</label></th>
										<td class="forminp forminp-checkbox">
											<input name="nickx_poster_img" id="nickx_poster_img" type="checkbox" value="yes" ' . checked( 'yes', get_option( 'nickx_poster_img', 'no' ), false ) . '>
											<samll class="lbl_tc">Only for Self Hosted Video, Enable this option if you set a custom thumbnail as the video poster image.<p> It'."'".'s available on the product edit page where you add the video.</p></samll>
										</td>
									</tr>
									<tr valign="top" ' . ( ( $lic_chk_stateus ) ? '' : 'class="primium_aria" title="AVAILABLE IN PREMIUM VERSION"' ) . '">
										<th scope="row" class="titledesc"><label for="nickx_videoloop">Video Looping</label></th>
										<td class="forminp forminp-checkbox">
											<input name="nickx_videoloop" id="nickx_videoloop" type="checkbox" value="yes" ' . checked( 'yes', get_option( 'nickx_videoloop' ), false ) . '>
											<samll class="lbl_tc">Looping a video is allowing the video to play in a repeat mode.
											<p>Auto play works only when <b>Place of The Video</b> is <b>Before Product Gallery Images</b>.</p></samll>
										</td>
									</tr>
									<tr valign="top" ' . ( ( $lic_chk_stateus ) ? '' : 'class="primium_aria" title="AVAILABLE IN PREMIUM VERSION"' ) . '">
										<th scope="row" class="titledesc"><label for="nickx_vid_autoplay">Auto Play Video</label></th>
										<td class="forminp forminp-checkbox">
											<input name="nickx_vid_autoplay" id="nickx_vid_autoplay" type="checkbox" value="yes" ' . checked( 'yes', get_option( 'nickx_vid_autoplay' ), false ) . '>
											<samll>Auto play works only when <b>Place of The Video</b> is <b>Before Product Gallery Images</b>.
											<p>If you enable this option, the video will be muted by default, so you have to manually unmute the video.</p>
											<p>Please pass <b>autoplay=1</b> parameter with your video url if you are using YouTube or Vimeo video.</p></samll>
										</td>
									</tr>
									<tr valign="top" ' . ( ( $lic_chk_stateus ) ? '' : 'class="primium_aria" title="AVAILABLE IN PREMIUM VERSION"' ) . '">
										<th scope="row" class="titledesc"><label for="nickx_place_of_the_video">Place Of The Video</label></th>
										<td class="forminp forminp-checkbox">
											<select name="nickx_place_of_the_video" id="nickx_place_of_the_video">
												<option value="no" ' . selected( 'no', get_option( 'nickx_place_of_the_video' ), false ) . '>After Product Gallery Images</option>
												<option value="second" ' . selected( 'second', get_option( 'nickx_place_of_the_video' ), false ) . '>After Product Image</option>
												<option value="yes" ' . selected( 'yes', get_option( 'nickx_place_of_the_video' ), false ) . '>Before Product Gallery Images</option>
											</select>
										</td>
									</tr>
									<tr valign="top" ' . ( ( $lic_chk_stateus ) ? '' : 'class="primium_aria" title="AVAILABLE IN PREMIUM VERSION"' ) . '">
										<th scope="row" class="titledesc"><label for="nickx_thumnails_layout">Thumbnails Layout</label></th>
										<td class="forminp forminp-checkbox">
											<select name="nickx_thumnails_layout" id="nickx_thumnails_layout">
												<option value="slider" ' . selected( 'slider', get_option( 'nickx_thumnails_layout', 'grid' ), false ) . '>Slider</option>
												<option value="grid" ' . selected( 'grid', get_option( 'nickx_thumnails_layout' ), false ) . '>Grid</option>
											</select>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" class="titledesc"><label for="nickx_arrowcolor">Arrow Color</label></th>
										<td class="forminp forminp-color">
											<input name="nickx_arrowcolor" id="nickx_arrowcolor" type="text" value="' . esc_attr( get_option( 'nickx_arrowcolor' ) ) . '" class="colorpick">
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" class="titledesc"><label for="nickx_arrowbgcolor">Arrow Background Color</label></th>
										<td class="forminp forminp-color">
											<input name="nickx_arrowbgcolor" id="nickx_arrowbgcolor" type="text" value="' . esc_attr( get_option( 'nickx_arrowbgcolor' ) ) . '" class="colorpick">
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" class="titledesc"><label for="nickx_video_icon_color">Video Icon Color</label></th>
										<td class="forminp forminp-color">
											<input name="nickx_video_icon_color" id="nickx_video_icon_color" type="text" value="' . esc_attr( get_option( 'nickx_video_icon_color', '#FFF' ) ) . '" class="colorpick">
											<samll>To set the color of the video icon on the video thumbnail.</samll>
										</td>
									</tr>
									<tr valign="top" ' . ( ( $lic_chk_stateus ) ? '' : 'class="primium_aria" title="AVAILABLE IN PREMIUM VERSION"' ) . '">
										<th scope="row" class="titledesc"><label for="nickx_shortcode">Shortcode</label></th>
										<td class="forminp forminp-info">
											<small id="nickx_shortcode">Use this <b>[product_gallery_shortcode]</b> shortcode if your product pages edited with help of any page builders (Divi Builder, Elementor Builder etc.)</small>
										</td>
									</tr>
								</tbody>
								<tfoot><tr><td class="submit_btn_cls">';
								submit_button();
								echo '</td></tr></tfoot>
							</table>
						</form>
					</div>
				</div>
				<div class="techno_tabs tab_premium" style="display:none;">';
			if ( isset( $_REQUEST['activate_license_techno'] ) && isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ), 'nickx-license-active' ) ) {
				if ( $lic_chk_stateus ) {
					echo '<div id="message" class="updated fade"><p><strong>You license Activated successfuly...!!!</strong></p></div>
					<form method="POST">';
						wp_nonce_field( 'nickx-license-deactive' );
						echo '<div class="col-50">
							<h2> hank You For Purchasing...!!!</h2>
							<h4 class="paid_color">Deactivate Your License:</h4>
							<p class="submit">
								<input type="submit" name="deactivate_techno_wc_product_video_license" value="Deactive" class="button button-primary">
							</p>
						</div>
					</form>';
				} else {
					$this->techno_wc_product_video_pro_html();
					echo '<div id="message" class="updated fade" style="border-left-color:#a00;"><p><strong>' . esc_html( $this->extend->err ) . '</strong></p></div>';
				}
			} elseif ( $this->extend->is_nickx_act_lic() ) {

				echo '<form method="POST">';
						wp_nonce_field( 'nickx-license-deactive' );
						echo '<div class="col-50">
						<h2> Thank You Phurchasing ...!!!</h2>
						<h4 class="paid_color">Deactivate Your License:</h4>
						<p class="submit">
							<input type="submit" name="deactivate_techno_wc_product_video_license" value="Deactive" class="button button-primary">
						</p>
					</div>
				</form>';
			} else {
				$this->techno_wc_product_video_pro_html();
				echo esc_html( $this->extend->err );
			}
			echo '</div></div></div>
			<script type="text/javascript">
				jQuery(document).ready(function(e)
				{
					jQuery(".colorpick").each(function(w)
					{
						jQuery(this).wpColorPicker();
					});
					jQuery("div.techno_main_tabs").click(function(e)
					{
						jQuery(".techno_main_tabs").removeClass("active");
						jQuery(this).addClass("active");
						jQuery(".techno_tabs").hide();
						jQuery("."+this.id).show();
					});
					jQuery("tr.primium_aria").click(function(e) {
						jQuery("#tab_premium").trigger("click");
					});
					jQuery(".upload_image_button").click(function(e) {
						var send_attachment_bkp = wp.media.editor.send.attachment;
						wp.media.editor.send.attachment = function(props, attachment)
						{
							jQuery("#custom_icon").val(attachment.id);
							jQuery("#custom_video_thumb").attr("src",attachment.url).show();
							wp.media.editor.send.attachment = send_attachment_bkp;
						}
						wp.media.editor.open(this);
						return false;
		  			});
					jQuery(".remove_image_button").click(function(e) {
						var answer = confirm("Are you sure?");
						if (answer == true)
						{
							jQuery("#custom_icon").val("");
							jQuery("#custom_video_thumb").attr("src","").hide();
						}
						return false;
					});
				});
			</script>';
		}
		public function techno_wc_product_video_pro_html() {
			$pugin_path = plugin_dir_url( __FILE__ ); 
			echo '<form method="POST">';
			wp_nonce_field( 'nickx-license-active' );
			echo '<div class="col-50">
				<h2>Product Video Gallery for Woocommerce</h2>
				<h4 class="paid_color">Premium Features:</h4>
				<p class="paid_color">01. You Can Use Vimeo And Html5 Video(MP4, WebM, and Ogg).</p>
				<p class="paid_color">02. You Can Add Multiple videos.</p>
				<p class="paid_color">03. Change The Place Of The Video(After Product Gallery Images, After Product Image and Before Product Gallery Images).</p>
				<p class="paid_color">04. Video Looping (Looping a video is allowing the video to play in a repeat mode).</p>
				<p class="paid_color">05. Show Only Videos (Display only videos on gallery).</p>
				<p class="paid_color">06. Shortcode (Use shortcode if your product pages edited with help of any page builders <b>Divi Builder, Elementor Builder etc.</b>).</p>
				<p><label for="techno_wc_product_videokey">License Key : </label><input class="regular-text" type="text" id="techno_wc_product_video_license_key" name="techno_wc_product_video_license_key"></p>
				<p class="submit">
				<input type="submit" name="activate_license_techno" value="Activate" class="button button-primary">
				</p>
			</div>
			<div class="col-50">
				<div class="content_right" style="text-align: center;">
					<p style="font-size: 25px; font-weight: bold; color: #f00;">Buy Activation Key form Here...</p>
					<p><a href="https://www.technosoftwebs.com/wc-product-video-gallery/" target="_blank">Buy Now...</a></p>
				</div>
			</div>
			</form>';
		}
		public function update_wc_product_video_gallery_options( $value = '' ) {
			register_setting( 'wc_product_video_gallery_options', 'nickx_slider_layout' );
			register_setting( 'wc_product_video_gallery_options', 'nickx_slider_responsive' );
			register_setting( 'wc_product_video_gallery_options', 'nickx_sliderautoplay' );
			register_setting( 'wc_product_video_gallery_options', 'nickx_sliderfade' );
			register_setting( 'wc_product_video_gallery_options', 'nickx_arrowinfinite' );
			register_setting( 'wc_product_video_gallery_options', 'nickx_arrowdisable' );
			register_setting( 'wc_product_video_gallery_options', 'nickx_arrow_thumb' );
			register_setting( 'wc_product_video_gallery_options', 'nickx_show_lightbox' );
			register_setting( 'wc_product_video_gallery_options', 'nickx_show_zoom' );
			register_setting( 'wc_product_video_gallery_options', 'nickx_mobile_zoom' );
			register_setting( 'wc_product_video_gallery_options', 'nickx_zoomlevel' );
			register_setting( 'wc_product_video_gallery_options', 'nickx_arrowcolor' );
			register_setting( 'wc_product_video_gallery_options', 'nickx_show_only_video' );
			register_setting( 'wc_product_video_gallery_options', 'custom_icon' );
			register_setting( 'wc_product_video_gallery_options', 'nickx_hide_thumbnails' );
			register_setting( 'wc_product_video_gallery_options', 'nickx_hide_thumbnail' );
			register_setting( 'wc_product_video_gallery_options', 'nickx_gallery_action' );
			register_setting( 'wc_product_video_gallery_options', 'nickx_thumbnails_hook' );
			register_setting( 'wc_product_video_gallery_options', 'nickx_template' );
			register_setting( 'wc_product_video_gallery_options', 'nickx_thumbnails_to_show' );
			register_setting( 'wc_product_video_gallery_options', 'nickx_arrowbgcolor' );
			register_setting( 'wc_product_video_gallery_options', 'nickx_video_icon_color' );
			register_setting( 'wc_product_video_gallery_options', 'nickx_adaptive_height' );
			if ( $this->extend->is_nickx_act_lic() ) {
				register_setting( 'wc_product_video_gallery_options', 'nickx_videoloop' );
				register_setting( 'wc_product_video_gallery_options', 'nickx_vid_autoplay' );
				register_setting( 'wc_product_video_gallery_options', 'nickx_controls' );
				register_setting( 'wc_product_video_gallery_options', 'nickx_poster_img' );
				register_setting( 'wc_product_video_gallery_options', 'nickx_place_of_the_video' );
				register_setting( 'wc_product_video_gallery_options', 'nickx_thumnails_layout' );
			}
		}
		public function wc_prd_vid_slider_settings_link( $links ) {
			$links[] = '<a href="' . esc_url( admin_url() ) . 'edit.php?post_type=product&page=wc-product-video">Settings</a>';
			return $links;
		}
	}
}
