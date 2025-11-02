<?php
/**
@package WC_PRODUCT_VIDEO_GALLERY_RENDERING
-------------------------------------------------*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
	RENDERING Class
 */
if ( ! class_exists( 'WC_PRODUCT_VIDEO_GALLERY_RENDERING' ) ) {
	class WC_PRODUCT_VIDEO_GALLERY_RENDERING {
		/** @var $extend Lic value */
		public $extend;

		function __construct() {
			$this->add_actions( new NICKX_LIC_CLASS() );
		}
		private function add_actions( $extend ) {
			$this->extend = $extend;
			add_action( 'wp_enqueue_scripts', array( $this, 'nickx_enqueue_scripts' ), 99 );
			add_shortcode( 'product_gallery_shortcode', array( $this, 'product_gallery_shortcode_callback' ) );
			add_filter( 'wc_get_template', array( $this, 'nickx_get_template' ), 99, 5 );
		}
		public function nickx_enqueue_scripts() {
			if ( ! is_admin() ) {
				if ( class_exists( 'WooCommerce' ) && is_product() || is_page_template( 'page-templates/template-products.php' ) ) {
					wp_enqueue_script( 'jquery' );
					if ( get_option( 'nickx_show_lightbox' ) == 'yes' ) {
						wp_enqueue_script( 'nickx-nfancybox-js', plugins_url( 'js/jquery.fancybox.js', __FILE__ ), array( 'jquery' ), NICKX_PLUGIN_VERSION, true );
						wp_enqueue_style( 'nickx-nfancybox-css', plugins_url( 'css/fancybox.css', __FILE__ ), array(), NICKX_PLUGIN_VERSION, 'all' );
					}
					if ( get_option( 'nickx_show_zoom' ) != 'off' ) {
						wp_enqueue_script( 'nickx-zoom-js', plugins_url( 'js/jquery.zoom.min.js', __FILE__ ), array( 'jquery' ), '1.7.4', true );
						wp_enqueue_script( 'nickx-elevatezoom-js', plugins_url( 'js/jquery.elevatezoom.min.js', __FILE__ ), array( 'jquery' ), NICKX_PLUGIN_VERSION, true );
					}
					wp_enqueue_style( 'nickx-swiper-css', plugins_url( 'css/swiper-bundle.min.css', __FILE__ ), array(), NICKX_PLUGIN_VERSION, 'all' );
					wp_enqueue_style( 'nickx-front-css', plugins_url( 'css/nickx-front.css', __FILE__ ), array('nickx-swiper-css'), NICKX_PLUGIN_VERSION, 'all' );
					wp_enqueue_script( 'nickx-swiper-js', plugins_url( 'js/swiper-bundle.min.js', __FILE__ ), array( 'jquery' ), NICKX_PLUGIN_VERSION, true );
					wp_register_script( 'nickx-front-js', plugins_url( 'js/nickx.front.js', __FILE__ ), array( 'jquery', 'nickx-swiper-js' ), NICKX_PLUGIN_VERSION, true );
					$video_type = get_post_meta( get_the_ID(), '_nickx_product_video_type', true );
					if( ( is_array( $video_type ) && in_array( 'nickx_video_url_vimeo', get_post_meta( get_the_ID(), '_nickx_product_video_type', true ) ) ) || get_post_meta( get_the_ID(), '_nickx_product_video_type', true ) == 'nickx_video_url_vimeo' ) {
						wp_enqueue_script( 'nickx-vimeo-js', 'https://player.vimeo.com/api/player.js', '1.0', true, array( 'strategy' => 'defer' ) );
					}
					$nfancybox_options = array(
						'slideShow' => array( 'speed'=> 3000 ),
						'fullScreen' => true,
						'transitionEffect'=> "slide",
						'arrows'=> true,
						'thumbs' => false,
						'infobar' => true,
						'loop' => true
					);
					$translation_array = array(
						'nickx_slider_layout'      => get_option( 'nickx_slider_layout' ),
						'nickx_slider_responsive'  => get_option( 'nickx_slider_responsive' ),
						'nickx_sliderautoplay'     => get_option( 'nickx_sliderautoplay' ),
						'nickx_sliderfade'         => get_option( 'nickx_sliderfade' ),
						'nickx_rtl'                => is_rtl(),
						'nickx_arrowinfinite'      => get_option( 'nickx_arrowinfinite' ),
						'nickx_arrowdisable'       => get_option( 'nickx_arrowdisable' ),
						'nickx_hide_thumbnails'    => get_option( 'nickx_hide_thumbnails' ),
						'nickx_hide_thumbnail'     => get_option( 'nickx_hide_thumbnail' ),
						'nickx_adaptive_height'    => get_option( 'nickx_adaptive_height', 'yes' ),
						'nickx_thumbnails_to_show' => get_option( 'nickx_thumbnails_to_show', 4 ),
						'nickx_thumnails_layout'   => get_option( 'nickx_thumnails_layout', 'slider' ),
						'nickx_show_lightbox'      => get_option( 'nickx_show_lightbox' ),
						'nickx_show_zoom'          => wp_is_mobile() && get_option( 'nickx_mobile_zoom') == 'yes' ? 'off' : get_option( 'nickx_show_zoom' ),
						'nickx_zoomlevel'          => get_option( 'nickx_zoomlevel', 1 ),
						'nickx_arrowcolor'         => get_option( 'nickx_arrowcolor' ),
						'nickx_arrowbgcolor'       => get_option( 'nickx_arrowbgcolor' ),
						'nickx_variation_selector' => apply_filters( 'nickx_variation_selector', 'document'),
						'nickx_lic'                => $this->extend->is_nickx_act_lic(),
						'nfancybox'                => apply_filters( 'nickx_nfancybox_options', $nfancybox_options ),
					);
					if ( $this->extend->is_nickx_act_lic() ) {
						$translation_array['nickx_place_of_the_video'] = get_option( 'nickx_place_of_the_video' );
						$translation_array['nickx_videoloop']          = get_option( 'nickx_videoloop' );
						$translation_array['nickx_vid_autoplay']       = get_option( 'nickx_vid_autoplay' );
					}
					wp_localize_script( 'nickx-front-js', 'wc_prd_vid_slider_setting', $translation_array );
					wp_enqueue_script( 'nickx-front-js' );
				}
			}
		}
		public function product_gallery_shortcode_callback( $atts = array() ) {
			ob_start();
			echo '<span id="product_gallery_shortcode">';
			$lic_chk_stateus = $this->extend->is_nickx_act_lic();
			if ( $lic_chk_stateus ) {
				$this->nickx_show_product_image('shortcode');
			} else {
				echo 'To use shortcode you need to activate license key...!!';
			}
			echo '</span>';
			return ob_get_clean();
		}
		public function nickx_get_template( $located, $template_name, $args, $template_path, $default_path ) {
			if ( is_product() && 'single-product/product-image.php' == $template_name && get_option( 'nickx_template' ) == 'yes' ) {
				$located = plugin_dir_path( __FILE__ ).'template/product-video-template.php';
			}
			return $located;
		}
		function nickx_get_embed_yt_url($url) {
			preg_match( '/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|shorts\/|watch\?v=|\&v=)([^#\&\?]*).*/', $url, $matches );
			if( !empty( $matches[2] ) ){
				$query_string = parse_url($url, PHP_URL_QUERY);
				$nocookie = '';   
				if( strpos($url, "nocookie" ) ){
					$nocookie = '-nocookie';   
				}
				$url = 'https://www.youtube'.$nocookie.'.com/embed/' . $matches[2] . '?rel=0&showinfo=0&enablejsapi=1';
				if( !empty( $query_string ) ){
					parse_str($query_string, $yt_params);
					unset($yt_params['v']);
					$query_string = http_build_query($yt_params);
					$url .= '&'.$query_string;
				}
			}
			return $url;
		}
		public function nickx_get_gmt_offset(){
			$offset  = (float) get_option( 'gmt_offset' );
			$hours   = (int) $offset;
			$minutes = ( $offset - $hours );

			$sign      = ( $offset < 0 ) ? '-' : '+';
			$abs_hour  = abs( $hours );
			$abs_mins  = abs( $minutes * 60 );
			$tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );
			return $tz_offset;
		}	
		public function nickx_get_nickx_video_schema(){
			if( is_product() ){
				$product_id = get_the_ID();
				$product_video_types = get_post_meta( $product_id, '_nickx_product_video_type', true );
				$product_video_urls  = get_post_meta( $product_id, '_nickx_video_text_url', true ); 
				$video_thumb_ids     = get_post_meta( $product_id, '_nickx_product_video_thumb_ids', true );
				$video_schemas       = get_post_meta( $product_id, '_video_schema', true );
				$video_upload_dates  = get_post_meta( $product_id, '_nickx_video_upload_date', true );
				$video_names         = get_post_meta( $product_id, '_nickx_video_name', true );
				$video_descriptions  = get_post_meta( $product_id, '_nickx_video_description', true );
				if ( is_array($product_video_urls) ) {
					$extend = new NICKX_LIC_CLASS();
					foreach ($product_video_urls as $key => $product_video_url) {
						if( !empty( $product_video_url ) ){
							$product_video_type = $product_video_types[$key];
							if ( $product_video_type == 'nickx_video_url_youtube' ) {
								$product_video_url = $this->nickx_get_embed_yt_url( $product_video_url );					
								echo '<link rel="preload" href="'.$product_video_url.'" as="fetch">'; 					
							}
							if( isset($video_schemas[$key]) && $video_schemas[$key] == 'yes' && !empty( $video_names[$key] ) && !empty( $video_upload_dates[$key] ) && !empty( $video_descriptions[$key] ) ) {
								$product_video_thumb_url = wc_placeholder_img_src();
								if ( ! empty( $video_thumb_ids[$key] ) ) {
									$product_video_thumb_url = wp_get_attachment_image_url( $video_thumb_ids[$key] );
								}
								echo '<script type="application/ld+json">
								{
								  "@context": "https://schema.org/",
								  "@type": "VideoObject",
								  "uploadDate": "' . $video_upload_dates[$key] . ':00'.$this->nickx_get_gmt_offset().'",
								  "thumbnailUrl" : "' . $product_video_thumb_url . '",
								  "name": "' . $video_names[$key] . '",
								  "description" : "' . $video_descriptions[$key] . '",
								  "@id": "' . $product_video_url . '",
								  "embedUrl" : "' . $product_video_url . '"	  
								}
								</script>';
							}
							if(!$extend->is_nickx_act_lic()){
								break;
							}				
						}
					}
				}
			}
		}
		public function nickx_get_nickx_video_html( $product_video_url, $extend, $key = 1, $product_video_type = 'nickx_video_url_youtube', $product_video_thumb_id = '' ) {
			if ( strpos( $product_video_url, 'youtube' ) > 0 || strpos( $product_video_url, 'youtu' ) > 0 ) {
				$product_video_url = $this->nickx_get_embed_yt_url( $product_video_url );
				return '<div class="tc_video_slide nswiper-slide"><iframe id="nickx_yt_video_'.$key.'" loading="lazy" width="100%" height="100%" class="product_video_iframe fitvidsignore" video-type="youtube" src="' . esc_url( $product_video_url ) . '" frameborder="0" allow="autoplay; accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe><span class="product_video_iframe_light nickx-popup nfancybox-media" data-nfancybox="product-gallery"></span></div>';
			} elseif ( strpos( $product_video_url, 'vimeo' ) > 0 && $extend->is_nickx_act_lic() ) {
				return '<div class="tc_video_slide nswiper-slide"><iframe style="display:none;" width="100%" loading="lazy" height="450px" class="product_video_iframe fitvidsignore" video-type="vimeo" src="' . esc_url( $product_video_url ) . '" frameborder="0" allow="autoplay; fullscreen" allowfullscreen=""></iframe><span href="' . esc_url( $product_video_url ) . '?enablejsapi=1&wmode=opaque" class="nickx-popup nfancybox-media" data-nfancybox="product-gallery"></span></div>';
			} elseif ( ( $product_video_type == 'nickx_video_url_local' || strpos( $product_video_url, $_SERVER['SERVER_NAME'] ) > 0 ) && $extend->is_nickx_act_lic() ) {
				if ( $product_video_thumb_id ) {
					$poster = 'poster="'.wp_get_attachment_image_url( $product_video_thumb_id, 'full' ).'"';
				}
				return '<div class="tc_video_slide nswiper-slide"><video '. ( $poster ?? '' ) .' width="100%" height="100%" preload="auto" class="product_video_iframe fitvidsignore" video-type="html5" ' . ( ( get_option( 'nickx_controls' ) == 'yes' ) ? 'controls' : '' ) . ' ' . ( ( get_option( 'nickx_vid_autoplay' ) == 'yes' && get_option( 'nickx_place_of_the_video' ) == 'yes' ) ? 'autoplay muted' : '' ) . ' playsinline><source src="' . esc_url( $product_video_url ) . '"><p>Your browser does not support HTML5</p></video><span href="' . esc_url( $product_video_url ) . '?enablejsapi=1&wmode=opaque" class="nickx-popup nfancybox-media" data-nfancybox="product-gallery"></span></div>';
			} elseif ( $product_video_type == 'nickx_video_url_iframe' && $extend->is_nickx_act_lic() ) {
				return '<div class="tc_video_slide nswiper-slide"><iframe style="display:none;" loading="lazy" width="100%" height="450px" class="product_video_iframe fitvidsignore" video-type="iframe" src="' . esc_url( $product_video_url ) . '" frameborder="0" allow="autoplay; fullscreen" allowfullscreen=""></iframe></div>';
			} else {
				return '<div class="tc_video_slide nswiper-slide"><iframe style="display:none;" data-skip-lazy="true" width="100%" height="100%" class="product_video_iframe fitvidsignore" video-type="youtube" data_src="' . esc_url( $product_video_url ) . '" src="" frameborder="0" allow="autoplay; accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
			}
		}
		public function get_css_classes(){
			$css_classes = array( 'images', 'nickx_product_images_with_video' );
			if( get_option( 'nickx_show_lightbox' ) == 'yes' ){
				$css_classes[] = 'show_lightbox';
			}
			if( get_option( 'nickx_slider_responsive' ) == 'yes' ){
				$css_classes[] = 'yes';
			}
			if( get_option( 'nickx_thumnails_layout' ) == 'grid' ){
				$css_classes[] = 'grid';
			} else {
				$css_classes[] = 'v-'.get_option( 'nickx_slider_layout' );
			}
			return implode( ' ', $css_classes );
		}
		public function nickx_show_product_image($call_type = 'action') {
			global $post, $product, $woocommerce;
			if ( $call_type != 'action' || !$product->is_type( 'gift-card' ) ) {
				$show_thumb = 0;
				$product_video_urls = get_post_meta( get_the_ID(), '_nickx_video_text_url', true );
				$product_video_types = get_post_meta( get_the_ID(), '_nickx_product_video_type', true );
				$extend = new NICKX_LIC_CLASS();
				$is_rtl = is_rtl() ? 'rlt' : '';
				echo '<div dir="'.$is_rtl.'" class="'. $this->get_css_classes() .'">';
				if(wp_is_mobile()){
					echo '<span class="nickx-popup_trigger"></span>';
				}
				echo '<div class="nickx-slider nswiper nickx-slider-for"><div class="nswiper-wrapper">';
				if ( has_post_thumbnail() || ! empty( $product_video_urls[0] ) ) {
					$attachment_ids    = ($product) ? $product->get_gallery_image_ids() : '';
					$imgfull_src       = get_the_post_thumbnail_url(get_the_ID(),'full');
					$htmlvideo         = '';
					if ( ! empty( $product_video_urls ) ) {
						$product_video_thumb_ids = get_post_meta( get_the_ID(), '_nickx_product_video_thumb_ids', true );
						if ( is_array($product_video_urls) ) {
							$poster_img = get_option( 'nickx_poster_img' );
							foreach ( $product_video_urls as $key => $product_video_url) {
								if( !empty( $product_video_url ) ) {
									$show_thumb++;
									$product_video_thumb_id = ( isset($product_video_thumb_ids[$key]) && $poster_img == 'yes' ) ? $product_video_thumb_ids[$key] : '';
									$htmlvideo .= $this->nickx_get_nickx_video_html($product_video_url,$extend,$key,$product_video_types[$key],$product_video_thumb_id);
								}
								if(!$extend->is_nickx_act_lic()){
									break;
								}
							}
						}
						else{
							$show_thumb++;
							$htmlvideo .= $this->nickx_get_nickx_video_html($product_video_urls,$extend,'nickx_video_url_youtube');
						}
					}
					$product_image = get_the_post_thumbnail( $post->ID, 'woocommerce_single', array( 'data-skip-lazy' => 'true', 'data-zoom-image' => $imgfull_src ) );
					$html = '';
					if( !empty($htmlvideo) && get_option( 'nickx_show_only_video' ) == 'yes' && $extend->is_nickx_act_lic() ){
						$html .= $htmlvideo;
					} else {
						$html .= ( ( get_option( 'nickx_place_of_the_video' ) == 'yes' && $extend->is_nickx_act_lic() ) ? $htmlvideo : '' );
						if( !empty ( $product_image ) ){
							$show_thumb++;
							$html .= '<div class="nswiper-slide zoom woocommerce-product-gallery__image">'.$product_image.'<span title="'.get_the_title(get_post_thumbnail_id()).'" href="'.$imgfull_src.'" class="nickx-popup" data-nfancybox="product-gallery"></span></div>';
						}
						$html .= ( ( get_option( 'nickx_place_of_the_video' ) == 'second' && $extend->is_nickx_act_lic() ) ? $htmlvideo : '' );
						foreach ( $attachment_ids as $attachment_id ) {
							$show_thumb++;
							$imgfull_src = wp_get_attachment_image_url( $attachment_id, 'full' );
							$html       .= '<div class="nswiper-slide zoom">' . wp_get_attachment_image( $attachment_id, 'woocommerce_single', 0, array( 'data-skip-lazy' => 'true', 'data-zoom-image' => $imgfull_src ) ) . '<span title="'.get_the_title($attachment_id).'" href="' . esc_url( $imgfull_src ) . '" class="nickx-popup" data-nfancybox="product-gallery"></span></div>';
						}
						$html .= ( ( get_option( 'nickx_place_of_the_video' ) == 'no' && get_option( 'nickx_place_of_the_video' ) != 'yes' &&  get_option( 'nickx_place_of_the_video' ) != 'second' || ! $extend->is_nickx_act_lic() ) ? $htmlvideo : '' );
					}
					echo apply_filters( 'woocommerce_single_product_image_html', $html, $post->ID );
				} else {
					echo apply_filters( 'woocommerce_single_product_image_html', sprintf( '<div class="nswiper-slide zoom woocommerce-product-gallery__image"><img class="attachment-woocommerce_single size-woocommerce_single wp-post-image" data-skip-lazy="true" src="%s" data-zoom-image="%s" alt="%s" /></div>', wc_placeholder_img_src(), wc_placeholder_img_src(), __( 'Placeholder', 'woocommerce' ) ), $post->ID );
				}
				echo '</div><div class="nswiper-button-next main_arrow"></div><div class="nswiper-button-prev main_arrow"></div></div>';
				if ( get_option( 'nickx_hide_thumbnails' ) != 'yes' ) {
					if( $show_thumb > 1 || get_option('nickx_hide_thumbnail') != 'yes' ){
						$this->nickx_show_product_thumbnails();
					}
				}
				do_action( 'nickx_after_product_video_gallery_thumbnails' );
				if ( get_option( 'nickx_thumbnails_hook' ) == 'yes' ) {
					do_action( 'woocommerce_product_thumbnails' );
				}
				echo '</div>';
			} else {
				woocommerce_show_product_images();
			}
		}
		public function nickx_get_video_thumbanil_html( $post, $thumbnail_size) {
			$product_video_urls = get_post_meta( get_the_ID(), '_nickx_video_text_url', true );
			$video_icon_color = get_option( 'nickx_video_icon_color', '#FFF' );
			$wc_placeholder_img = wc_placeholder_img_src();
			if ( ! empty( $product_video_urls ) ) {
				$gallery_thumbnail_size = wc_get_image_size( $thumbnail_size );
				$hwstring = image_hwstring( $gallery_thumbnail_size['width'], $gallery_thumbnail_size['height'] );
				$product_video_thumb_ids  = get_post_meta( get_the_ID(), '_nickx_product_video_thumb_ids', true );
				$custom_thumbnails        = get_post_meta( get_the_ID(), '_custom_thumbnail', true );
				if ( is_array($product_video_urls) ) {
					$extend = new NICKX_LIC_CLASS();
					foreach ($product_video_urls as $key => $product_video_url) {
						if( !empty( $product_video_url ) ) {
							$product_video_thumb_id   = isset($product_video_thumb_ids[$key]) ? $product_video_thumb_ids[$key] : '';
							$custom_thumbnail        = isset($custom_thumbnails[$key]) && !empty($product_video_thumb_id) ? 'custom_thumbnail="'.$custom_thumbnails[$key].'"' : '';
							$product_video_thumb_url = $wc_placeholder_img;
							$global_thumb = '';
							if ( $product_video_thumb_id ) {
								$product_video_thumb_url = wp_get_attachment_image_url( $product_video_thumb_id, $thumbnail_size );
							} elseif ($custom_icon = get_option( 'custom_icon' ) ) {
								$custom_thumbnail        = 'custom_thumbnail="yes"';
								if(is_numeric($custom_icon)){
									$product_video_thumb_url = wp_get_attachment_image_url( get_option( 'custom_icon' ), $thumbnail_size );
								} else {
									$product_video_thumb_url = $custom_icon;
								}
								$global_thumb = 'global-thumb="' . esc_url( $product_video_thumb_url ).'"';
							}
							echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', '<div title="video" class="nswiper-slide nickx-thumbnail video-thumbnail"><img ' . $hwstring . ' data-skip-lazy="true" ' . $global_thumb . ' src="' . esc_url( $product_video_thumb_url ) . '" ' . $custom_thumbnail . ' class="product_video_img img_'.$key.' attachment-thumbnail size-thumbnail" alt="video-thumb-'.$key.'"><svg class="video_icon_img" xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="80 35 80 85"><path fill="'.$video_icon_color.'" width="16px" height="16px" d="M140.3 77c.6.2.8.8.6 1.4-.1.3-.3.5-.6.6L110 96.5c-1 .6-1.7.1-1.7-1v-35c0-1.1.8-1.5 1.7-1L140.3 77z"/><path fill="none" stroke="'.$video_icon_color.'" stroke-width="5" d="M82.5 79c0-20.7 16.8-37.5 37.5-37.5s37.5 16.8 37.5 37.5-16.8 37.5-37.5 37.5S82.5 99.7 82.5 79z"/></svg></div>', '', $post->ID );
							if(!$extend->is_nickx_act_lic()){
								break;
							}
						}
					}
				} else {
					$product_video_thumb_urls = $wc_placeholder_img;
					$global_thumb = '';
					if ( $product_video_thumb_ids ) {
						$product_video_thumb_urls = wp_get_attachment_image_url( $product_video_thumb_ids, $thumbnail_size );
					} elseif ($custom_icon = get_option( 'custom_icon' ) ) {
						$custom_thumbnails        = 'custom_thumbnail="yes"';
						if(is_numeric($custom_icon)){
							$product_video_thumb_url = wp_get_attachment_image_url( get_option( 'custom_icon' ), $thumbnail_size );
						} else {
							$product_video_thumb_url = $custom_icon;
						}
						$global_thumb = 'global-thumb=" ' . esc_url( $product_video_thumb_urls ).' "';
					}
					echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', '<div title="video" class="nswiper-slide nickx-thumbnail video-thumbnail"><div class="video_icon_img" style="background: url( ' . plugins_url( 'css/mejs-controls.svg', __FILE__ ) . ' ) no-repeat;"></div><img ' . $hwstring . ' data-skip-lazy="true" ' . $global_thumb . ' src="' . esc_url( $product_video_thumb_urls ) . '" ' . $custom_thumbnails . ' class="product_video_img img_0 attachment-thumbnail size-thumbnail" alt="video-thumb-0"></div>', '', $post->ID );
				}
			} else {
				return;
			}
		}
		public function nickx_show_product_thumbnails() {
			global $post, $product, $woocommerce;
			if (empty($product->get_type()) || !$product->is_type( 'gift-card' ) ) {
				$product_video_urls = get_post_meta( get_the_ID(), '_nickx_video_text_url', true );
				$extend         = new NICKX_LIC_CLASS();
				$attachment_ids = $product->get_gallery_image_ids();
				if ( has_post_thumbnail() ) {
					$thumbanil_id   = array( get_post_thumbnail_id() );
					$attachment_ids = array_merge( $thumbanil_id, $attachment_ids );
				}
				$thumbnail_size    = apply_filters( 'woocommerce_gallery_thumbnail_size', 'woocommerce_gallery_thumbnail' );
				if ( ( $attachment_ids && $product->get_image_id() ) || ! empty( get_post_meta( get_the_ID(), '_nickx_video_text_url', true ) ) ) {
					echo '<div id="nickx-gallery" thumbsSlider class="thumbnail-slider nswiper nickx-slider-nav"><div class="nswiper-wrapper">';
					if( ( ! empty( $product_video_urls ) && get_option( 'nickx_show_only_video' ) == 'yes' && $extend->is_nickx_act_lic() ) || empty( $attachment_ids )){
						$this->nickx_get_video_thumbanil_html( $post, $thumbnail_size );
					} else {
						if ( ( get_option( 'nickx_place_of_the_video' ) == 'yes' || empty( $thumbanil_id[0] ) ) && $extend->is_nickx_act_lic() ) {
							$this->nickx_get_video_thumbanil_html( $post, $thumbnail_size );
						}
						foreach ( $attachment_ids as $attachment_id ) {
							$props = wc_get_product_attachment_props( $attachment_id, $post );
							if ( ! $props['url'] ) {
								continue;
							}
							echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', '<div class="nswiper-slide nickx-thumbnail product_thumbnail_item ' . ( ( !empty( $thumbanil_id[0] ) && $thumbanil_id[0] == $attachment_id ) ? 'wp-post-image-thumb' : '' ) . '" title="'.esc_attr( $props['caption'] ).'">'.wp_get_attachment_image( $attachment_id, $thumbnail_size, 0, array( 'data-skip-lazy' => 'true' ) ).'</div>', $attachment_id );
							if ( !empty( $thumbanil_id[0] ) && $thumbanil_id[0] == $attachment_id && get_option( 'nickx_place_of_the_video' ) == 'second' && $extend->is_nickx_act_lic() ) {
								$this->nickx_get_video_thumbanil_html( $post, $thumbnail_size );
							}
						}
						if ( get_option( 'nickx_place_of_the_video' ) == 'no' && get_option( 'nickx_place_of_the_video' ) != 'yes' && get_option( 'nickx_place_of_the_video' ) != 'second' || ! $extend->is_nickx_act_lic() ) {
							$this->nickx_get_video_thumbanil_html( $post, $thumbnail_size );
						}
					}
					echo '</div>'. ( ( get_option( 'nickx_arrow_thumb' ) == 'yes' ) ? '<div class="nswiper-button-next thumb_arrow"></div><div class="nswiper-button-prev thumb_arrow"></div>' : '' ). '</div>';
				}
			} else {
				woocommerce_show_product_thumbnails();
			}
		}
	}
}