<?php
/**
@package WC_PRODUCT_VIDEO_GALLERY_VIDEO_FIELD
-------------------------------------------------*/

/**
	Video field Class
 */
if ( ! class_exists( 'WC_PRODUCT_VIDEO_GALLERY_VIDEO_FIELD' ) ) {
	class WC_PRODUCT_VIDEO_GALLERY_VIDEO_FIELD {
		/** @var $extend Lic value */
		public $extend;

		function __construct() {
			$this->add_actions( new NICKX_LIC_CLASS() );
		}
		private function add_actions( $extend ) {
			$this->extend = $extend;
			add_action( 'add_meta_boxes', array( $this, 'add_video_url_field' ) );
			add_action( 'save_post', array( $this, 'save_wc_video_url_field' ) );
			if( function_exists( 'dokan' ) ){
				add_action( 'dokan_product_updated', array( $this, 'save_wc_video_url_field' ) );
				add_action( 'dokan_product_updated', array( $this, 'save_wc_video_url_field' ) );
				add_action( 'dokan_product_edit_after_product_tags', array( $this, 'video_url_field_dokan' ) );
			}
		}
		public function add_video_url_field() {
			add_meta_box( 'video_url', 'Product Video Url', array( $this, 'video_url_field' ), 'product' );
		}
		public function video_url_field_dokan() {
			echo '<div class="dokan-product-video dokan-edit-row">
				<div class="dokan-section-heading" data-togglehandler="dokan_product_video">
        			<h2><i class="fas fa-video" aria-hidden="true"></i> Product Video</h2>
    				<p>Add your video URL.</p>
			        <a href="#" class="dokan-section-toggle">
			            <i class="fas fa-sort-down fa-flip-vertical" aria-hidden="true"></i>
			        </a>
			        <div class="dokan-clearfix"></div>
			    </div>
			    <div class="dokan-section-content">';
					$this->video_url_field();
			    echo '</div></div>';
		}
		public function get_video_field_html( $product_video_type, $product_video_url, $custom_thumbnail, $product_video_thumb_url, $product_video_thumb_id, $video_schema, $video_upload_date, $video_name, $video_description ) {
			echo '<tr>
				<td colspan="2">
					<div class="video_url_aria">
						<div>
							<label class="nickx_lbl nickx_product_video_type_lbl" for="nickx_product_video_type">Video Type</label>
							<select name="nickx_product_video_type[]" class="nickx_input">
								<option value="nickx_video_url_youtube" ' . selected( $product_video_type, 'nickx_video_url_youtube', false ) . '>Youtube Video</option>
								<option value="nickx_video_url_vimeo" ' . selected( $product_video_type, 'nickx_video_url_vimeo', false ) . '>Vimeo Video</option>
								<option value="nickx_video_url_local" ' . selected( $product_video_type, 'nickx_video_url_local', false ) . '>Self Hosted Video(MP4, WebM, and Ogg)</option>
								<option value="nickx_video_url_iframe" ' . selected( $product_video_type, 'nickx_video_url_iframe', false ) . '>Other (embedUrl)</option>
							</select>
						</div>
						<div style="display: inline-block;">
							<div style="display: inline-block; vertical-align: top;">
								<label class="nickx_lbl" for="nickx_video_text_urls">Video  Url</label>
							</div>
							<div style="display: inline-block;">
								<div>
									<input type="url" class="nickx_input nickx_video_text_urls" value="' . esc_url( $product_video_url ) . '" name="nickx_video_text_url[]" placeholder="URL of your video">
									<span><label style="display: none;" class="select_video_button button">Select Video</label><input type="hidden" name="video_attachment_id" id="video_attachment_id"></span>
								</div>
								<div>
									<small style="display: none;" class="nickx_url_info nickx_video_url_youtube">https://www.youtube.com/embed/.....</small>
									<small style="display: none;" class="nickx_url_info nickx_video_url_vimeo">https://player.vimeo.com/video/......</small>
									<small style="display: none;" class="nickx_url_info nickx_video_url_iframe">Your embed video url.</small>
									<small style="display: none;" class="nickx_url_info nickx_video_url_local">' . esc_url( get_site_url() ) . '/wp-content/upload/......</small>
								</div>
							</div>
						</div>
						<div>
							<div>							
								<input type="hidden" value="' . esc_attr( $custom_thumbnail ) . '" name="custom_thumbnail[]">
								<label class="nickx_tab"><input type="checkbox" class="custom_thumbnail" value="yes" ' . checked( 'yes', $custom_thumbnail, false ) . '> Use Custom video Thumbnail?</label>
							</div>
							<div class="select_video_thumbnail" style="display:' . ( ( $custom_thumbnail != 'yes' ) ? 'none' : 'block' ) . ';">
								<div class="video_thumbnail_aria">
									<img style="max-width:80px;max-height:80px;" class="product_video_thumb" src="' . esc_url( $product_video_thumb_url ) . '">
								</div>
								<div class="video_thumbnail_btn">
									<label class="select_video_thumb_button button">Select Video Thumbnail</label>
									<input type="hidden" value="' . esc_attr( $product_video_thumb_id ) . '" name="product_video_thumb_url[]" class="product_video_thumb_url">
									<lable type="submit" class="remove_image_button button">X</lable>
								</div>
							</div>
						</div>
						<div>
							<div>							
								<input type="hidden" value="' . esc_attr( $video_schema ) . '" name="video_schema[]">
								<label class="nickx_tab"><input type="checkbox" class="video_schema" value="yes" ' . checked( 'yes', $video_schema, false ) . '> Add Video Schema?</label>
							</div>
							<div class="select_video_schema" style="display:' . ( ( $video_schema != 'yes' ) ? 'none' : 'block' ) . ';">
								<div class="video_schema_aria">
									<label class="nickx_lbl_schema">Upload Date</label>
									<input type="datetime-local" value="' . esc_attr( $video_upload_date ) . '" name="nickx_video_upload_date[]"> <small>The date the video was first published.</small>
								</div>
								<div class="video_schema_aria">
									<label class="nickx_lbl_schema">Video Name</label>
									<input type="text" value="' . esc_attr( $video_name ) . '" name="nickx_video_name[]"> <small>The title of the video.</small>
								</div>
								<div class="video_schema_aria">
									<label class="nickx_lbl_schema">Video Description</label>
									<textarea name="nickx_video_description[]" rows="2" cols="20">' . $video_description . '</textarea><small>The description of the video.</small>
								</div>
							</div>
						</div>
					</div>
					<div class="video_delete_aria"><b class="button video-remove-btn" title="Remove Video"><span class="dashicons dashicons-remove"></span></b></div>
				</td>
			</tr>';
		}
		public function nickx_meta_extend_call( $product_id ) {
			wp_enqueue_script( 'media-upload' );
			wp_enqueue_media();
			$product_video_types     = get_post_meta( $product_id, '_nickx_product_video_type', true );
			$product_video_urls      = get_post_meta( $product_id, '_nickx_video_text_url', true ); 
			$product_video_thumb_ids = get_post_meta( $product_id, '_nickx_product_video_thumb_ids', true );
			$custom_thumbnails       = get_post_meta( $product_id, '_custom_thumbnail', true );
			$video_schemas           = get_post_meta( $product_id, '_video_schema', true );
			$video_upload_dates      = get_post_meta( $product_id, '_nickx_video_upload_date', true );
			$video_names             = get_post_meta( $product_id, '_nickx_video_name', true );
			$video_descriptions      = get_post_meta( $product_id, '_nickx_video_description', true );
			echo '
			<style type="text/css"> 
				.nickx_lbl,.video_delete_aria,.video_thumbnail_aria,.video_thumbnail_btn,.video_url_aria{display:inline-block}
				button.button.add_video{color:#fff;background-color:#5cb85c;border-color:#4cae4c}
				table.product_videos_tbl tbody tr td{background:#ddd;border:1px solid #aaa;padding:15px}
				.nickx_lbl{min-width:64px}
				.nickx_input{width:300px}
				.video_thumbnail_btn{vertical-align:bottom;padding-bottom:25px}
				b.button.video-remove-btn{padding:10px 20px 0;color:#b32d2e;background:#fff1f1;border-color:#b32d2e}
				.video_url_aria{width:92%}
				.video_delete_aria{text-align:right;width:7.4%;vertical-align:top}
				button.button.add_video span.dashicons.dashicons-insert{vertical-align:text-top}
				.video_schema_aria {display: inline-grid;}
			</style>
			<div class="nickx_product_video_url_section">
				<table class="product_videos_tbl" style="width: 100%;">
					<thead><tr><th style="text-align: left;">Select Video Source</th><td style="text-align: right;"><button type="button" class="button add_video"><b><span class="dashicons dashicons-insert"></span></b> Add Video</button></td></tr></thead>
					<tbody>';
					if ( is_array($product_video_urls) ) {
						foreach ($product_video_urls as $key => $product_video_url) {
							$product_video_type = $product_video_types[$key];						
							$product_video_thumb_url = wc_placeholder_img_src();
							$product_video_thumb_id = '';
							if ( ! empty( $product_video_thumb_ids[$key] ) ) {
								$product_video_thumb_id = $product_video_thumb_ids[$key];
								$product_video_thumb_url = wp_get_attachment_image_url( $product_video_thumb_id );
							}
							$custom_thumbnail  = (isset($custom_thumbnails[$key])) ? $custom_thumbnails[$key] : 'no';
							$video_schema      = (isset($video_schemas[$key])) ? $video_schemas[$key] : 'no';
							$video_upload_date = (isset($video_upload_dates[$key])) ? $video_upload_dates[$key] : '';
							$video_name        = (isset($video_names[$key])) ? $video_names[$key] : '';
							$video_description = (isset($video_descriptions[$key])) ? $video_descriptions[$key] : '';
							
							$this->get_video_field_html( $product_video_types[$key], $product_video_url, $custom_thumbnail, $product_video_thumb_url, $product_video_thumb_id, $video_schema, $video_upload_date, $video_name, $video_description );
						}
					} else {
						$product_video_thumb_url = wc_placeholder_img_src();
						if ( ! empty( $product_video_thumb_ids ) ) {
							$product_video_thumb_url = wp_get_attachment_image_url( $product_video_thumb_ids );
						}
						$this->get_video_field_html( $product_video_types, $product_video_urls, $custom_thumbnails, $product_video_thumb_url, $product_video_thumb_ids, $video_schemas, $video_upload_dates, $video_names, $video_descriptions );
					}
					echo'
					</tbody>
				</table>
			</div>'; ?>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					$(document).on('change','select[name^="nickx_product_video_type["]',function(e) {
						set_video_type(this);
					});
					$(document).on('change','input[name^="nickx_video_text_url["]',function(e) {
						let video_url = this.value;
						let video_aria = $(this).parents('.video_url_aria');
						if (video_url.indexOf("youtu") > 0) {
							video_aria.find('select[name^="nickx_product_video_type["]').val('nickx_video_url_youtube').change();
						} else if (video_url.indexOf("vimeo") > 0) {
							video_aria.find('select[name^="nickx_product_video_type["]').val('nickx_video_url_vimeo').change();
						} else if (video_url.indexOf(window.location.hostname) > 0 || video_url.indexOf("mp4") > 0) {
							video_aria.find('select[name^="nickx_product_video_type["]').val('nickx_video_url_local').change();
						} else {
							video_aria.find('select[name^="nickx_product_video_type["]').val('nickx_video_url_iframe').change();
						}
					});
					$(document).on('change','input.custom_thumbnail',function(e) {
						let video_aria = $(this).parents('.video_url_aria');
						if (this.checked) {
							video_aria.find(".select_video_thumbnail").show();
							video_aria.find('input[name^="custom_thumbnail["]').val('yes');
						} else {
							video_aria.find('input[name^="custom_thumbnail["]').val('no');
							video_aria.find(".select_video_thumbnail").hide();
						}
					});
					$(document).on('change','input.video_schema',function(e) {
						let video_aria = $(this).parents('.video_url_aria');
						if (this.checked) {
							video_aria.find(".select_video_schema").show();
							video_aria.find('input[name^="video_schema["]').val('yes');
						} else {
							video_aria.find(".select_video_schema").hide();
							video_aria.find('input[name^="video_schema["]').val('no');
						}
					});
					$('select[name^="nickx_product_video_type["]').each(function(e) {
						set_video_type(this);
					});
					$(document).on('click','.select_video_button',function(e) {
						let video_aria = $(this).parents('.video_url_aria');
						nickx_video_uploader = wp.media({ library: {type: "video"},title: "Select Video"});
						nickx_video_uploader.on("select", function(e) {
							var file = nickx_video_uploader.state().get("selection").first();
							var extension = file.changed.subtype;
							var video_url = file.changed.url;
							video_aria.find(".nickx_video_text_urls").val(video_url);
						});
						nickx_video_uploader.open();
					});
					$(document).on('click','.select_video_thumb_button',function(e) {				
						let video_aria = $(this).parents('.video_url_aria');
					  	nickx_video_thumb_uploader = wp.media({ library: {type: "image"},title: "Select Video Thumbnail"});
					  	nickx_video_thumb_uploader.on("select", function(e) {
							var file = nickx_video_thumb_uploader.state().get("selection").first();
							var id = file.attributes.id;
							var video_thumb_url = file.changed.url;
							video_aria.find(".product_video_thumb").attr("src",video_thumb_url).show();
							video_aria.find(".product_video_thumb_url").val(id);
					  	});
					  	nickx_video_thumb_uploader.open();
					});
					$(document).on('click','.remove_image_button',function(e) {
						let video_aria = $(this).parents('.video_url_aria');
						video_aria.find(".product_video_thumb").attr("src","").hide();
						video_aria.find(".product_video_thumb_url").val("");
						return false;
					});
					$(document).on('click','.product_videos_tbl b.button.video-remove-btn', function(e){
						$(this).parents('tr').remove();
					});
					$(document).on('click','.product_videos_tbl .add_video', function(e){
						const html = '<tr><td colspan="2"><div class="video_url_aria"><div><label class="nickx_lbl nickx_product_video_type_lbl" for="nickx_product_video_type">Video Type</label><select name="nickx_product_video_type[]" class="nickx_input"><option value="nickx_video_url_youtube">Youtube Video</option><option value="nickx_video_url_vimeo">Vimeo Video</option><option value="nickx_video_url_local">Self Hosted Video(MP4, WebM, and Ogg)</option><option value="nickx_video_url_iframe">Other (embedUrl)</option></select></div><div style="display: inline-block;"><div style="display: inline-block; vertical-align: top;"><label class="nickx_lbl" for="nickx_video_text_urls">Video Url</label></div><div style="display: inline-block;"><div><input type="url" class="nickx_input nickx_video_text_urls" name="nickx_video_text_url[]" placeholder="URL of your video"><span><label style="display: none;" class="select_video_button button">Select Video</label><input type="hidden" name="video_attachment_id" id="video_attachment_id"></span></div><div><small style="display: none;" class="nickx_url_info nickx_video_url_youtube">https://www.youtube.com/embed/.....</small><small style="display: none;" class="nickx_url_info nickx_video_url_vimeo">https://player.vimeo.com/video/......</small><small style="display: none;" class="nickx_url_info nickx_video_url_local">./wp-content/upload/......</small><small style="display: none;" class="nickx_url_info nickx_video_url_iframe">Your embed video url.</small></div></div></div><div><div><input type="checkbox" class="custom_thumbnail" value="yes"><input type="hidden" value="no" name="custom_thumbnail[]"><label class="nickx_tab" for="custom_thumbnail">Use Custom video Thumbnail?</label></div><div class="select_video_thumbnail" style="display:none;"><div class="video_thumbnail_aria"><img style="max-width:80px;max-height:80px;" class="product_video_thumb"></div><div class="video_thumbnail_btn"><label class="select_video_thumb_button button">Select Video Thumbnail</label><input type="hidden" name="product_video_thumb_url[]" class="product_video_thumb_url"><lable type="submit" class="remove_image_button button">X</lable></div></div></div></div><div class="video_delete_aria"><b class="button video-remove-btn" title="Remove Video"><span class="dashicons dashicons-remove"></span></b></div></td></tr>';
						$('.product_videos_tbl tbody').append(html);
					});
				});
				function set_video_type(video) {
					let video_type = video.value;
					let video_aria = jQuery(video).parents('.video_url_aria');
					video_aria.find(".nickx_url_info,.select_video_button").hide();
					video_aria.find("."+video_type).show();
					video_aria.find("label.nickx_tab").removeClass("active");
					video_aria.find("label[for="+video_type+"]").addClass("active");
					if (video_type=="nickx_video_url_local") {
						video_aria.find(".select_video_button").show();
					}
				}
			</script><?php
		}
		public function video_url_field() {
	        wp_nonce_field( 'nickx_video_url_nonce_action', 'nickx_video_url_nonce' );
			$product_video_url = get_post_meta( get_the_ID(), '_nickx_video_text_url', true );		
			$product_video_thumb_id = get_post_meta( get_the_ID(), '_nickx_product_video_thumb_ids', true );
			if ( ! $this->extend->is_nickx_act_lic() ) {
				$product_video_url = is_array($product_video_url) ? $product_video_url[0] : $product_video_url;
				$product_video_thumb_id = is_array($product_video_thumb_id) ? $product_video_thumb_id[0] : $product_video_thumb_id;
				echo '<style type="text/css">.nickx_product_video_url_section ul li { display: inline-block; vertical-align: middle; padding: 0; margin: 0 auto; }button.button.add_video{color:#fff;background-color:#5cb85c;border-color:#4cae4c}</style>
				<div class="nickx_product_video_url_section">
				<div style="display: inline-block; width: 80%;">
				<ul>
					<li>
						<input type="radio" checked name="nickx_product_video_type[]" value="nickx_video_url_youtube" id="nickx_video_url_youtube">
						<label class="nickx_tab active" for="nickx_video_url_youtube">Youtube</label>
					</li>
					<li>
						<input type="radio" name="nickx_product_video_type" disabled>
						<label class="nickx_tab" for="nickx_video_url_vimeo">Vimeo' . wc_help_tip( '<p style="font-size: 25px; font-weight: bold;>available in premium version<br>Buy Activation Key form Setting Page</p>', true ) . '</label>
					</li>
					<li>
						<input type="radio" name="nickx_product_video_type" disabled>
						<label class="nickx_tab" for="nickx_video_url_local">WP Library' . wc_help_tip( '<p style="font-size: 25px; font-weight: bold;>available in premium version<br>Buy Activation Key form Setting Page</p>', true ) . '</label>
					</li>
				</ul><input type="hidden" value="' . esc_attr( $product_video_thumb_id ) . '" name="product_video_thumb_url[]" class="product_video_thumb_url">
				</div>
				<div style="display: inline-block;"><button type="button" class="button add_video" disabled><b><span class="dashicons dashicons-insert" style="vertical-align: middle;"></span></b> Add More Videos ' . wc_help_tip( '<p style="font-size: 25px; font-weight: bold;>available in premium version<br>Buy Activation Key form Setting Page</p>', true ) . '</button></div><div class="video-url-cls"><p>Enter the URL of your YouTube video. Only direct YouTube video links are supported.</p><input class="video_input" style="width:100%;" type="url" class="nickx_video_text_url" value="' . esc_url( $product_video_url ) . '" name="nickx_video_text_url[]" Placeholder="https://www.youtube.com/embed/....."></div></div>';
			} else {
				$this->nickx_meta_extend_call( get_the_ID() );
			}
		}
		public function save_wc_video_url_field( $post_id ) {
			$nonce_name   = isset( $_POST['nickx_video_url_nonce'] ) ? $_POST['nickx_video_url_nonce'] : '';
			$nonce_action = 'nickx_video_url_nonce_action';
			if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) ) {
				return;
			}
			if ( isset( $_POST['nickx_video_text_url'] ) ) {
				update_post_meta( $post_id, '_nickx_video_text_url', array_map( 'sanitize_url', $_POST['nickx_video_text_url'] ) );
			} else {
				delete_post_meta( $post_id, '_nickx_video_text_url' );
			}
			if ( isset( $_POST['nickx_product_video_type'] ) ) {
				update_post_meta( $post_id, '_nickx_product_video_type', array_map( 'sanitize_text_field', $_POST['nickx_product_video_type'] ) );
			} else {
				delete_post_meta( $post_id, '_nickx_product_video_type' );
			}
			if ( isset( $_POST['custom_thumbnail'] ) ) {
				update_post_meta( $post_id, '_custom_thumbnail', array_map( 'sanitize_text_field', $_POST['custom_thumbnail'] ) );
			} else {
				delete_post_meta( $post_id, '_custom_thumbnail' );
			}
			if ( isset( $_POST['product_video_thumb_url'] ) ) {
				update_post_meta( $post_id, '_nickx_product_video_thumb_ids', array_map( 'sanitize_text_field', $_POST['product_video_thumb_url'] ) );
			} else {
				delete_post_meta( $post_id, '_nickx_product_video_thumb_ids' );
			}
			if ( isset( $_POST['video_schema'] ) ) {
				update_post_meta( $post_id, '_video_schema', array_map( 'sanitize_text_field', $_POST['video_schema'] ) );
			} else {
				delete_post_meta( $post_id, '_video_schema' );
			}
			if ( isset( $_POST['nickx_video_upload_date'] ) ) {
				update_post_meta( $post_id, '_nickx_video_upload_date', array_map( 'sanitize_text_field', $_POST['nickx_video_upload_date'] ) );
			} else {
				delete_post_meta( $post_id, '_nickx_video_upload_date' );
			}
			if ( isset( $_POST['nickx_video_name'] ) ) {
				update_post_meta( $post_id, '_nickx_video_name', array_map( 'sanitize_text_field', $_POST['nickx_video_name'] ) );
			} else {
				delete_post_meta( $post_id, '_nickx_video_name' );
			}
			if ( isset( $_POST['nickx_video_description'] ) ) {
				update_post_meta( $post_id, '_nickx_video_description', array_map( 'sanitize_textarea_field', $_POST['nickx_video_description'] ) );
			} else {
				delete_post_meta( $post_id, '_nickx_video_description' );
			}
		}
	}
}
