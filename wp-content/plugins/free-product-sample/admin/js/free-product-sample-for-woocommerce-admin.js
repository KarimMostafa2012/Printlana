(function ($) {
	'use strict';

	// add currunt menu class in main manu
    $(window).load(function () {
        $('a[href="admin.php?page=fps-sample-settings"]').parents().addClass('current wp-has-current-submenu');
        $('a[href="admin.php?page=fps-sample-settings"]').addClass('current');
    });
    
	$(document).ready(function() {
		/** tiptip js implementation */
	    $( '.woocommerce-help-tip' ).tipTip( {
	        'attribute': 'data-tip',
	        'fadeIn': 50,
	        'fadeOut': 50,
	        'delay': 200,
	        'keepAlive': true
	    } );

		$('.fps_sliders_colorpick').wpColorPicker();
		
		// script for product/category selection
		let selectSampleType;
		selectSampleType = $('select#fps_sample_enable_type').val();
		if (selectSampleType === 'product_wise') {
			$('.fps-product-wise').show();
			$('.fps-category-wise').hide();
		} else {
			$('.fps-product-wise').hide();
			$('.fps-category-wise').show();
		}

		// script for price selection
		let selectPriceType;
		selectPriceType = $('select#fps_sample_price_type').val();
		if (selectPriceType === 'flat_price') {
			$('.fps-price-flat').show();
			$('.fps-price-percent').hide();
		} else {
			$('.fps-price-flat').hide();
			$('.fps-price-percent').show();
		}

		// script for maximum quantity selection
		let selectQuantityType;
		selectQuantityType = $('select#fps_sample_quantity_type').val();
		if (selectQuantityType === 'per_product') {
			$('.fps-quantity-product').show();
			$('.fps-quantity-product-msg').show();
			$('.fps-quantity-order').hide();
			$('.fps-quantity-order-msg').hide();
		} else {
			$('.fps-quantity-order').show();
			$('.fps-quantity-order-msg').show();
			$('.fps-quantity-product').hide();
			$('.fps-quantity-product-msg').hide();
		}

		// JS multi selection using select2
		getUsersListBasedOnSearch();
	    getProductListBasedOnSearch();

		// js for plugin help tip
		$( 'span.fps_tooltip_icon' ).click( function( event ) {
			event.preventDefault();
			$( this ).next( '.fps-woocommerce-help-tip' ).toggle();
		} );

		// script for plugin rating
		$(document).on('click', '.dotstore-sidebar-section .content_box .et-star-rating label', function(e){
			e.stopImmediatePropagation();
			var rurl = $('#et-review-url').val();
			window.open( rurl, '_blank' );
		});

		// script for updagrade to pro modal
		$(document).on('click', '#dotsstoremain .fps-pro-label, .fps-section-left .preimium-feature-block, .fps-section-left .fps-lock-option', function(){
			$('body').addClass('fps-modal-visible');
		});

		$(document).on('click', '#dotsstoremain .modal-close-btn', function(){
			$('body').removeClass('fps-modal-visible');
		});

		// script for Ajax dialog box settings
		$('#fps_ajax_enable_dialog').change(function () {
	        let checked;
	        checked = $(this).is(':checked');
	        if ( checked ) {
	            $('.fps-ajax-dialog-box-field').show();
	        } else {
	            $('.fps-ajax-dialog-box-field').hide();
	        }
	    });

	    var sampleAjaxDialog = $('#fps_ajax_enable_dialog').is(':checked');
	    if ( sampleAjaxDialog ) {
	        $('.fps-ajax-dialog-box-field').show();
	    } else {
	        $('.fps-ajax-dialog-box-field').hide();
	    }

	    // script for advanced button styling settings
		$('#fps_button_advance_enable_disable').change(function () {
	        let checked;
	        checked = $(this).is(':checked');
	        if ( checked ) {
	            $('.fps-sample-button-advance-fields').show();
	        } else {
	            $('.fps-sample-button-advance-fields').hide();
	        }
	    });

		var buttonAdvanceSettings = $('#fps_button_advance_enable_disable').is(':checked');
	    if ( buttonAdvanceSettings ) {
	        $('.fps-sample-button-advance-fields').show();
	    } else {
	        $('.fps-sample-button-advance-fields').hide();
	    }

	    // script for Ajax feature note enable
		$('#fps_ajax_enable_disable').change(function () {
	        let checked;
	        checked = $(this).is(':checked');
	        if ( checked ) {
	            $(this).parent().next().next('.fps-woocommerce-help-tip').show();
	        } else {
	            $(this).parent().next().next('.fps-woocommerce-help-tip').hide();
	        }
	    });

	    /** Plugin Setup Wizard Script START */
		// Hide & show wizard steps based on the url params 
	  	var urlParams = new URLSearchParams(window.location.search);
	  	if (urlParams.has('require_license')) {
	    	$('.ds-plugin-setup-wizard-main .tab-panel').hide();
	    	$( '.ds-plugin-setup-wizard-main #step5' ).show();
	  	} else {
	  		$( '.ds-plugin-setup-wizard-main #step1' ).show();
	  	}
	  	
        // Plugin setup wizard steps script
        $(document).on('click', '.ds-plugin-setup-wizard-main .tab-panel .btn-primary:not(.ds-wizard-complete)', function () {
	        var curruntStep = $(this).closest('.tab-panel').attr('id');
	        var nextStep = 'step' + ( parseInt( curruntStep.slice(4,5) ) + 1 ); // Masteringjs.io

	        if( 'step5' !== curruntStep ) {
	        	// Youtube videos stop on next step
				$('iframe[src*="https://www.youtube.com/embed/"]').each(function(){
				   $(this).attr('src', $(this).attr('src'));
				   return false;
				});

	         	$( '#' + curruntStep ).hide();
	            $( '#' + nextStep ).show();   
	        }
	    });

	    // Get allow for marketing or not
	    if ( $( '.ds-plugin-setup-wizard-main .ds_count_me_in' ).is( ':checked' ) ) {
	    	$('#fs_marketing_optin input[name="allow-marketing"][value="true"]').prop('checked', true);
	    } else {
	    	$('#fs_marketing_optin input[name="allow-marketing"][value="false"]').prop('checked', true);
	    }

		// Get allow for marketing or not on change	    
	    $(document).on( 'change', '.ds-plugin-setup-wizard-main .ds_count_me_in', function() {
			if ( this.checked ) {
				$('#fs_marketing_optin input[name="allow-marketing"][value="true"]').prop('checked', true);
			} else {
		    	$('#fs_marketing_optin input[name="allow-marketing"][value="false"]').prop('checked', true);
		    }
		});

	    // Complete setup wizard
	    $(document).on( 'click', '.ds-plugin-setup-wizard-main .tab-panel .ds-wizard-complete', function() {
			if ( $( '.ds-plugin-setup-wizard-main .ds_count_me_in' ).is( ':checked' ) ) {
				$( '.fs-actions button'  ).trigger('click');
			} else {
		    	$('.fs-actions #skip_activation')[0].click();
		    }
		});

	    // Send setup wizard data on Ajax callback
		$(document).on( 'click', '.ds-plugin-setup-wizard-main .fs-actions button', function() {
			var wizardData = {
                'action': 'dsfps_plugin_setup_wizard_submit',
                'survey_list': $('.ds-plugin-setup-wizard-main .ds-wizard-where-hear-select').val(),
                'nonce': dsfps_coditional_vars.setup_wizard_ajax_nonce
            };

            $.ajax({
                url: dsfps_coditional_vars.ajaxurl,
                data: wizardData,
                success: function ( success ) {
                    console.log(success);
                }
            });
		});
		/** Plugin Setup Wizard Script End */

		/** Upgrade Dashboard Script START */
	    // Dashboard features popup script
	    $(document).on('click', '.dotstore-upgrade-dashboard .premium-key-fetures .premium-feature-popup', function (event) {
	        let $trigger = $('.feature-explanation-popup, .feature-explanation-popup *');
	        if(!$trigger.is(event.target) && $trigger.has(event.target).length === 0){
	            $('.feature-explanation-popup-main').not($(this).find('.feature-explanation-popup-main')).hide();
	            $(this).parents('li').find('.feature-explanation-popup-main').show();
	            $('body').addClass('feature-explanation-popup-visible');
	        }
	    });
	    $(document).on('click', '.dotstore-upgrade-dashboard .popup-close-btn', function () {
	        $(this).parents('.feature-explanation-popup-main').hide();
	        $('body').removeClass('feature-explanation-popup-visible');
	    });
	    /** Upgrade Dashboard Script End */

	    /** Dynamic Promotional Bar START */
        $(document).on('click', '.dpbpop-close', function () {
            var popupName 		= $(this).attr('data-popup-name');
            setCookie( 'banner_' + popupName, 'yes', 60 * 24 * 7);
            $('.' + popupName).hide();
        });

		$(document).on('click', '.dpb-popup .dpb-popup-meta a', function () {
			var promotional_id = $(this).parents().find('.dpbpop-close').attr('data-bar-id');

			//Create a new Student object using the values from the textfields
			var apiData = {
				'bar_id' : promotional_id
			};

			$.ajax({
				type: 'POST',
				url: dsfps_coditional_vars.dpb_api_url + 'wp-content/plugins/dots-dynamic-promotional-banner/bar-response.php',
				data: JSON.stringify(apiData),// now data come in this function
		        dataType: 'json',
		        cors: true,
		        contentType:'application/json',
		        
				success: function (data) {
					console.log(data);
				},
				error: function () {
				}
			 });
        });
        /** Dynamic Promotional Bar END */

        // Script for Beacon configuration
        var helpBeaconCookie = getCookie( 'dsfps-help-beacon-hide' );
        if ( ! helpBeaconCookie ) {
            Beacon('init', 'afe1c188-3c3b-4c5f-9dbd-87329301c920');
            Beacon('config', {
                display: {
                    style: 'icon',
                    iconImage: 'message',
                    zIndex: '99999'
                }
            });

            // Add plugin articles IDs to display in beacon
            Beacon('suggest', ['6266a39a6c886c75aabe9e0b', '6266a54493a48c4448335083', '6266a5d06c886c75aabe9e25', '6266a6fdb065ad1af4f80866', '6266a784a535c33d541a1b4c']);

            // Add custom close icon form beacon
            setTimeout(function() {
                if ( $( '.hsds-beacon .BeaconFabButtonFrame' ).length > 0 ) {
                    let newElement = document.createElement('span');
                    newElement.classList.add('dashicons', 'dashicons-no-alt', 'dots-beacon-close');
                    let container = document.getElementsByClassName('BeaconFabButtonFrame');
                    container[0].appendChild( newElement );
                }
            }, 3000);

            // Hide beacon
            $(document).on('click', '.dots-beacon-close', function(){
                Beacon('destroy');
                setCookie( 'dsfps-help-beacon-hide' , 'true', 24 * 60 );
            });
        }

        /** Script for Freemius upgrade popup */
        $(document).on('click', '.dots-header .dots-upgrade-btn, .dotstore-upgrade-dashboard .upgrade-now', function(e){
            e.preventDefault();
            upgradeToProFreemius( '' );
        });
        $(document).on('click', '.upgrade-to-pro-modal-main .upgrade-now', function(e){
            e.preventDefault();
            $('body').removeClass('fps-modal-visible');
            let couponCode = $('.upgrade-to-pro-discount-code').val();
            upgradeToProFreemius( couponCode );
        });
	});

	// script for product/category selection
	$('select#fps_sample_enable_type').change(function () {
		let selectSampleType;
		selectSampleType = $(this).val();
		if (selectSampleType === 'product_wise') {
			$('.fps-product-wise').show();
			$('.fps-category-wise').hide();
		} else {
			$('.fps-product-wise').hide();
			$('.fps-category-wise').show();
		}
	});

	// script for price selection
	$('select#fps_sample_price_type').change(function () {
		let selectPriceType;
		selectPriceType = $('select#fps_sample_price_type').val();
		if (selectPriceType === 'flat_price') {
			$('.fps-price-flat').show();
			$('.fps-price-percent').hide();
		} else {
			$('.fps-price-flat').hide();
			$('.fps-price-percent').show();
		}
	});

	// script for maximum quantity selection
	$('select#fps_sample_quantity_type').change(function () {
		let selectQuantityType;
		selectQuantityType = $('select#fps_sample_quantity_type').val();
		if (selectQuantityType === 'per_product') {
			$('.fps-quantity-product').show();
			$('.fps-quantity-product-msg').show();
			$('.fps-quantity-order').hide();
			$('.fps-quantity-order-msg').hide();
		} else {
			$('.fps-quantity-order').show();
			$('.fps-quantity-order-msg').show();
			$('.fps-quantity-product').hide();
			$('.fps-quantity-product-msg').hide();
		}
	});

	// script for save the setting data
    $(document).on('click', '#save_top_dsfps_setting', function () {
    	dsfps_validation();

    	if ( dsfps_validation() ) {
    		saveSampleProductSettings();
			$('html, body').animate({scrollTop: 0}, 2000);
            return false;
    	}
    });

    $(document).on('click', '#save_dsfps_setting', function () {
    	dsfps_validation();

    	if ( dsfps_validation() ) {
    		saveSampleProductSettings();
			$('html, body').animate({scrollTop: 0}, 2000);
            return false;
    	}
    });

    function dsfps_validation() {
    	let fps_sample_enable_type = $('select#fps_sample_enable_type').val();
    	let fps_sample_price_type = $('select#fps_sample_price_type').val();

    	$('html, body').animate({ scrollTop: 0 }, 2000);

    	if ( 'category_wise' === fps_sample_enable_type ) {
    		$('select#fps_sample_enable_type').css('border-color', '#dc3232');
    		$('.warning_message_fps .warning_message_for_cat').css('display', 'inline-block');
    		setTimeout(function () {
				$('.warning_message_fps .warning_message_for_cat').css('display', 'none');
			},7000);
            return false;
    	} else {
    		$('select#fps_sample_enable_type').css('border-color', '#8c8f94');
    	}

    	if ( 'percentage_price' === fps_sample_price_type ) {
    		$('select#fps_sample_price_type').css('border-color', '#dc3232');
    		$('.warning_message_fps .warning_message_for_price').css('display', 'inline-block');
    		setTimeout(function () {
				$('.warning_message_fps .warning_message_for_price').css('display', 'none');
			},7000);
            return false;
    	} else {
    		$('select#fps_sample_price_type').css('border-color', '#8c8f94');
    	}

    	return true;
    }

    function saveSampleProductSettings() {
    	let fps_settings_enable_disable;
		if ($('#fps_settings_enable_disable').prop('checked') === true) {
			fps_settings_enable_disable = 'on';
		} else {
			fps_settings_enable_disable = 'off';
		}
		let fps_settings_hide_on_shop;
		if ($('#fps_settings_hide_on_shop').prop('checked') === true) {
			fps_settings_hide_on_shop = 'on';
		} else {
			fps_settings_hide_on_shop = 'off';
		}
		let fps_settings_hide_on_category;
		if ($('#fps_settings_hide_on_category').prop('checked') === true) {
			fps_settings_hide_on_category = 'on';
		} else {
			fps_settings_hide_on_category = 'off';
		}
		let fps_settings_include_store_menu;
		if ($('#fps_settings_include_store_menu').prop('checked') === true) {
			fps_settings_include_store_menu = 'on';
		} else {
			fps_settings_include_store_menu = 'off';
		}
		let fps_settings_button_label = $('#fps_settings_button_label').val();
		let fps_sample_enable_type = $('select#fps_sample_enable_type').val();

		let fps_select_product_list_arr = [];
		let fps_select_product_list;
		fps_select_product_list = $('#fps_select_product_list').val();
        if ('' !== fps_select_product_list) {
            fps_select_product_list_arr = fps_select_product_list;
        }

        let fps_sample_price_type = $('select#fps_sample_price_type').val();
		let fps_sample_flat_price = $('#fps_sample_flat_price').val();
		let fps_sample_quantity_type = $('select#fps_sample_quantity_type').val();
		let fps_quantity_per_product = $('#fps_quantity_per_product').val();
		let fps_quantity_per_order = $('#fps_quantity_per_order').val();
		let fps_max_quantity_message = $('#fps_max_quantity_message').val();
		let fps_max_quantity_per_order_msg = $('#fps_max_quantity_per_order_msg').val();

		let fps_select_users_list_arr = [];
		let fps_select_users_list;
		fps_select_users_list = $('#fps_select_users_list').val();
        if ('' !== fps_select_users_list) {
            fps_select_users_list_arr = fps_select_users_list;
        }

        let fps_sample_button_color = $('#fps_sample_button_color').val();
		let fps_sample_button_bg_color = $('#fps_sample_button_bg_color').val();

		let fps_ajax_enable_disable;
		if ($('#fps_ajax_enable_disable').prop('checked') === true) {
			fps_ajax_enable_disable = 'on';
		} else {
			fps_ajax_enable_disable = 'off';
		}

		let fps_ajax_enable_dialog;
		if ($('#fps_ajax_enable_dialog').prop('checked') === true) {
			fps_ajax_enable_dialog = 'on';
		} else {
			fps_ajax_enable_dialog = 'off';
		}

		let fps_ajax_sucess_message = $('#fps_ajax_sucess_message').val();

		$('.fps-setting-loader').css('display', 'inline-block');

		$.ajax({
			type: 'POST',
			url: dsfps_coditional_vars.ajaxurl,
			data: {
				'action': 'dsfps_save_sample_prod_settings',
				'security': dsfps_coditional_vars.fps_ajax_nonce,
				'fps_settings_enable_disable': fps_settings_enable_disable,
				'fps_settings_button_label': fps_settings_button_label,
				'fps_settings_hide_on_shop': fps_settings_hide_on_shop,
				'fps_settings_hide_on_category': fps_settings_hide_on_category,
				'fps_settings_include_store_menu': fps_settings_include_store_menu,
				'fps_sample_enable_type': fps_sample_enable_type,
				'fps_select_product_list': fps_select_product_list_arr,
				'fps_sample_price_type': fps_sample_price_type,
				'fps_sample_flat_price': fps_sample_flat_price,
				'fps_sample_quantity_type': fps_sample_quantity_type,
				'fps_quantity_per_product': fps_quantity_per_product,
				'fps_quantity_per_order': fps_quantity_per_order,
				'fps_max_quantity_message': fps_max_quantity_message,
				'fps_max_quantity_per_order_msg': fps_max_quantity_per_order_msg,
				'fps_select_users_list': fps_select_users_list_arr,
				'fps_sample_button_color': fps_sample_button_color,
				'fps_sample_button_bg_color': fps_sample_button_bg_color,
				'fps_ajax_enable_disable': fps_ajax_enable_disable,
				'fps_ajax_enable_dialog': fps_ajax_enable_dialog,
				'fps_ajax_sucess_message': fps_ajax_sucess_message,
			},
			success: function() {
				$('.fps-setting-loader').css('display', 'none');
                $('#succesful_message_fps').css('display', 'block');
				setTimeout(function () {
					$('#succesful_message_fps').css('display', 'none');
				},7000);
			}
		});
    }

    /**
	 * Replace the special character code to symbol
	 *
	 * @param str
	 * @returns {string}
	 */
	function allowSpeicalCharacter( str ) {
		return str.replace( '&#8211;', '–' ).replace( '&gt;', '>' ).replace( '&lt;', '<' ).replace( '&#197;', 'Å' );
	}

	/**
	 * Return product list based on product search
	 */
	function getProductListBasedOnSearch() {
		$( '#fps_select_product_list' ).select2({
			ajax: {
				url: dsfps_coditional_vars.ajaxurl,
				dataType: 'json',
				delay: 250,
				cache: true,
                minimumInputLength: 3,
				data: function( params ) {
					return {
						value: params.term,
						action: 'dsfps_get_simple_and_variation_product_list_ajax',
						security: dsfps_coditional_vars.fps_ajax_nonce,
                        _page: params.page || 1,
                        posts_per_page: 10 
					};
				},
				processResults: function( data ) {
					var options = [], more = true;
					if ( data ) {
						$.each( data, function( index, text ) {
							options.push( { id: text[ 0 ], text: allowSpeicalCharacter( text[ 1 ] ) } );
						} );
					}
                    //for stop paination on all data laod 
                    if( 0 === options.length ){ 
                        more = false; 
                    }
					return {
						results: options,
                        pagination: {
                            more: more
                        }
					};
				},
			},
		});
	}

	/**
	 * Return users list based on user search
	 */
	function getUsersListBasedOnSearch() {
		$( '#fps_select_users_list' ).select2({
			ajax: {
				url: dsfps_coditional_vars.ajaxurl,
				dataType: 'json',
				delay: 250,
				cache: true,
                minimumInputLength: 3,
				data: function( params ) {
					return {
						value: params.term,
						action: 'dsfps_get_users_list_ajax',
						security: dsfps_coditional_vars.fps_ajax_nonce,
                        _page: params.page || 1,
                        posts_per_page: 10 
					};
				},
				processResults: function( data ) {
					var options = [], more = true;
					if ( data ) {
						$.each( data, function( index, text ) {
							options.push( { id: text[ 0 ], text: allowSpeicalCharacter( text[ 1 ] ) } );
						} );
					}
                    //for stop paination on all data laod 
                    if( 0 === options.length ){ 
                        more = false; 
                    }
					return {
						results: options,
                        pagination: {
                            more: more
                        }
					};
				},
			},
		});
	}

	// Set cookies
    function setCookie(name, value, minutes) {
        var expires = '';
        if (minutes) {
            var date = new Date();
            date.setTime(date.getTime() + (minutes * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + (value || '') + expires + '; path=/';
    }

    // Get cookies
    function getCookie(name) {
        let nameEQ = name + '=';
        let ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i].trim();
            if (c.indexOf(nameEQ) === 0) {
                return c.substring(nameEQ.length, c.length);
            }
        }
        return null;
    }

    /** Script for Freemius upgrade popup */
    function upgradeToProFreemius( couponCode ) {
        let handler;
        handler = FS.Checkout.configure({
            plugin_id: '9758',
            plan_id: '16421',
            public_key:'pk_9edf804dccd14eabfd00ff503acaf',
            coupon: couponCode,
        });
        handler.open({
            name: 'Product Sample for WooCommerce',
            subtitle: 'Product Sample for WooCommerce',
            licenses: jQuery('input[name="licence"]:checked').val(),
            purchaseCompleted: function( response ) {
                console.log (response);
            },
            success: function (response) {
                console.log (response);
            }
        });
    }
})(jQuery);