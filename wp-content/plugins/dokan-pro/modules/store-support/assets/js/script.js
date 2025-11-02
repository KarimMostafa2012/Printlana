
//dokan store support form submit
(function($){

    var wrapper            = $( '.dokan-store-tabs' );
    var support_btn        = $( '.dokan-store-support-btn' );
    var support_btn_text   = $( '.dokan-store-support-btn' ).html();
    var custom_support_btn = support_btn.html();
    var storeSuppportModal = null;

    var Dokan_Store_Support = {

        init : function() {
            $('.dokan-store-support-btn').on( 'click', this.popUp.show );
            $('body').on( 'submit', '#dokan-support-login', this.popUp.submitLogin );
            $('body').on( 'submit', '#dokan-support-form', this.popUp.submitSupportMsg );

            // If the iziModal container div does not exists.
            if ( ! $('.dokan-store-support-modals').length ) {
                var $div = $('<div />').appendTo('body');
                $div.attr('class', 'dokan-store-support-modals');
            }

            storeSuppportModal = $( '.dokan-store-support-modals' ).iziModal( {
                width: 430,
                closeButton: true,
                appendTo: 'body',
                title: '',
                headerColor: dokan.modal_header_color,
                onClosed: function(){
                    $( '.dokan-store-support-btn' ).html( support_btn_text );
                },
            } );
        },
        popUp : {
            show : function(e){
                e.preventDefault();
                support_btn_text = $( '.dokan-store-support-btn' ).html();
                support_btn.html( dokan_store_support_i18n.wait );
                if (support_btn.hasClass('user_logged_out')){
                    Dokan_Store_Support.popUp.getForm( 'login_form' );
                } else {
                    Dokan_Store_Support.popUp.getForm( 'get_support_form' );
                }
            },
            getForm : function( data ){
                var s_data = {
                    action: 'dokan_support_ajax_handler',
                    data: data,
                    store_id : support_btn.data( 'store_id' ),
                    order_id : support_btn.data( 'order_id' ),
                };

                $.post( dokan.ajaxurl, s_data, function ( resp ) {
                    if ( resp.success == true ) {
                        const template = '<div class="white-popup dokan-support-login-wrapper dokan-izimodal-wraper" style="position: relative;">' +
                            '<div class="dokan-izimodal-close-btn" style="position: absolute; top: 0; right: 0;"><button data-iziModal-close class="icon-close" style="background: white; padding: 0.2em 0.5em 0 0;"><i class="fa fa-times" aria-hidden="true"></i></button></div>' +
                            '<div id="ds-error-msg" ></div>' + resp.data + '</div>';
                        storeSuppportModal.iziModal( 'setContent', template.trim() );
                        storeSuppportModal.iziModal( 'open' );
                    } else {
                        alert('failed');
                        support_btn.html(custom_support_btn);
                    }
                } )
            },
            submitLogin : function(e){
                e.preventDefault();
                storeSuppportModal.iziModal('startLoading');

                var self = $(this);
                var s_data = {
                    action : 'dokan_support_ajax_handler',
                    data : 'login_data_submit',
                    form_data : self.serialize(),
                };
                var $e_msg = $('#ds-error-msg');
                $e_msg.addClass('dokan-hide');
                $.post( dokan.ajaxurl, s_data, function ( resp ) {
                    storeSuppportModal.iziModal('stopLoading');
                    if ( resp.success == true ) {
                        Dokan_Store_Support.popUp.getForm( 'get_support_form' );
                        support_btn.html(custom_support_btn);
                    }else if (resp.success == false){
                        $e_msg.removeClass('dokan-hide');
                        $e_msg.html(resp.msg);
                        $e_msg.addClass('dokan-alert dokan-alert-danger');
                    }
                    else {
                        alert('failed');
                        support_btn.html(custom_support_btn);
                    }
                } )
            },
            submitSupportMsg : function(e){
                e.preventDefault();
                //prevent multiple submission
                $( '#support-submit-btn' ).prop('disabled', true);
                storeSuppportModal.iziModal('startLoading');

                var self = $(this);
                var s_data = {
                    action : 'dokan_support_ajax_handler',
                    data : 'support_msg_submit',
                    form_data : self.serialize(),
                };
                var $e_msg = $('#ds-error-msg');

                $.post( dokan.ajaxurl, s_data, function ( resp ) {
                    storeSuppportModal.iziModal('stopLoading');
                    if ( resp.success == true ) {
                        self.trigger( 'reset' );

                        const template = '<div class="white-popup dokan-support-login-wrapper dokan-alert dokan-alert-success dokan-izimodal-wraper"><div class="dokan-izimodal-close-btn"><button data-iziModal-close class="icon-close"><i class="fa fa-times" aria-hidden="true"></i></button></div>' + resp.msg + '</div>';
                        storeSuppportModal.iziModal( 'setContent', template.trim() );
                    } else if ( resp.success == false ) {
                        $e_msg.removeClass('dokan-hide');
                        $e_msg.html(resp.msg);
                        $e_msg.addClass('dokan-alert dokan-alert-danger');
                    }
                    else {
                        alert('failed');
                        $( '#support-submit-btn' ).prop('disabled', false );
                    }

                    support_btn.html(custom_support_btn);
                } )
            }
        },
    };

    $(function() {
        Dokan_Store_Support.init();
    });

})(jQuery);

//dokan support comments
(function($){

    var wrapper = $( '.dokan-support-topic-wrapper' );
    var Dokan_support_comment = {

        init : function() {
            $('body').on( 'submit', '#dokan-support-commentform', this.submitComment );
            Dokan_support_comment.scroolTOBottomList();
        },

        submitComment : function(e){
            e.preventDefault();
            var self = $(this);
            var s_data = {
                action : 'dokan_support_ajax_handler',
                data : 'support_msg_submit',
                form_data : self.serialize(),
            };

            if( $('#comment').val().trim().length === 0 ){
                dokan_sweetalert( dokan_store_support_i18n.empty_comment_msg, { confirmButtonColor: '#f54242',icon: 'error', } );
                return;
            }

            var formurl = self.attr('action');

            $('.dokan-support-topic-wrapper').block({ message: null, overlayCSS: { background: '#fff url(' + dokan.ajax_loader + ') no-repeat center', opacity: 0.6 } });
            $.post( formurl, s_data.form_data, function ( resp ) {

                if(resp){
                    $('.dokan-support-topic-wrapper').unblock();
                    $('.dokan-support-topic-wrapper').html($(resp).find('.dokan-support-topic-wrapper').html());

                    Dokan_support_comment.scroolTOBottomList();
                }
            } );
        },

        scroolTOBottomList : function(){
            // let messageBody = $('.dokan-dss-chat-box');
            // messageBody.animate({ scrollTop: messageBody.height() }, "slow");

            const comments = $('.dokan-dss-chat-box');
            $.each( comments, function ( i, val ) {
                val.scrollIntoView();
            } );
        },

    };

    $(function() {
        Dokan_support_comment.init();
    });

})(jQuery);

//dokan support settings
(function($){

    var Dokan_support_settings = {

        init : function() {
            $('body').on( 'change', '#support_checkbox', this.toggle_name_input );
            $('body').on( 'change', '#support_checkbox_product', this.toggle_name_input );
        },
        toggle_name_input : function() {
            if ( $( '#support_checkbox' ).is( ':checked' ) || $( '#support_checkbox_product' ).is( ':checked' ) ) {
                $( '.support-enable-check' ).show();
            } else {
                $( '.support-enable-check' ).hide();
            }
        }
    };

    $(function() {
        Dokan_support_settings.init();
        Dokan_support_settings.toggle_name_input();
    });

})(jQuery);
