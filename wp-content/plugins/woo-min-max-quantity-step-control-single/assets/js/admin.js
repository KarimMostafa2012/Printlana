(function($){
    'use strict';
    $(document).ready(function(){
        
        // $('.ua_input_select,.wcmmq_select_terms').select2();
        // $('#select#wcmmq_term_ids').select2();
        $('select#wcmmq_supported_terms').select2();
        

        $(document).on('click', '.wcmmq-offer-notice .notice-dismiss', function () {

            //For Coupon ajax
            var wrap = $(this).closest('.wcmmq-offer-notice');
            var coupon_ajax_url = wrap.data('coupon_ajax_url');
            var coupon = wrap.data('coupon');
            var nonce = wrap.data('nonce');
            var action = wrap.data('action');

            $.post(coupon_ajax_url, {
                action: action,
                coupon: coupon,
                nonce: nonce
            }).done(function (response) {
                console.log(response);
            });
        });

        /**
         * Support terms -> on after change,
         * form will be submit
         */
         $(document.body).on('change','#wcmmq_supported_terms',function(){
            
            $('button.wcmmq-btn.configure_submit').trigger('click');
        });
        
        $(document.body).on('click','.wcmmq-premium',function(){
            
            var img_link = $(this).find('img').attr('src');
            var link = 'https://codeastrology.com/min-max-quantity/pricing/';
            window.open(img_link,'_blank');
            // window.location.href = 'https://codeastrology.com/min-max-quantity/pricing/';
        });
        
        $(document.body).on('submit', 'form#wcmmq-main-configuration-form', function (e){

            var min_val = $(this).find('.config_min_qty').val();
            var max_val = $(this).find('.config_max_qty').val();
            if(min_val!=''){
                //Just added parseInt() beacuse of data type was string.
                if (parseInt(min_val) > parseInt(max_val)) {
                    alert("Please make sure that your minimum quantity is smaller than maximum quantity!");
                    return false;
                }
            }

            let submitBtn = $(this).find('button.configure_submit');
            let submitBtnInForm = submitBtn.not('.float-btn');
            let submitBtnIcon = submitBtn.find('span i');
            submitBtn.find('strong.form-submit-text').text('Saving...');
            submitBtnIcon.attr('class', 'wcmmq_icon-spin5 animate-spin');
            // submitBtnIcon.attr('class', 'wcmmq_icon-floppy');
            
            
        });

       
    });

    /** Save Floating button  **/
    var saveChangeText = 'Save';
    var btnHtml = '<div class="">';
    btnHtml += '<button type="submit" name="configure_submit" class="float-btn wcmmq-btn wcmmq-has-icon configure_submit"><span><i class="wcmmq_icon-floppy"></i></span><strong>' + saveChangeText + '</strong></button>';
    btnHtml += '</div>';
    //wcmmq-main-configuration-form

    var colSetsLen = $('form#wcmmq-main-configuration-form').length;
    if( colSetsLen > 0 ){
        $('#wcmmq-main-configuration-form').append(btnHtml);
    } 
   
   //var $elem = $('.float-section');
   $(window).on('scroll',function(){
    
        let targetElement = $('.float-btn');
        let topbarElement = $('div.wcmmq-header.wcmmq-clearfix');
        
        let bodyHeight = $('#wpbody').height();
        let scrollTop = $(this).scrollTop();
        let screenHeight = $(this).height();

        let configFormElement = $('form#wcmmq-main-configuration-form');
        if(configFormElement.length < 1) return;

        let conPass = bodyHeight - screenHeight - 100 - targetElement.height();
        let leftWill = configFormElement.width() - targetElement.width() - 20;
        

        // targetElement.css({
        //     left: leftWill,
        //     right: 'unset'
        // });
        if(scrollTop < conPass){
            targetElement.addClass('stick_on_scroll-on');
        }else{
            targetElement.removeClass('stick_on_scroll-on');
        }
        if(scrollTop > 50){
            configFormElement.addClass('topbar-fixed-on-scroll-main-element');
            topbarElement.addClass('topbar-fixed-on-scroll');
        }else{
            configFormElement.removeClass('topbar-fixed-on-scroll-main-element');
            topbarElement.removeClass('topbar-fixed-on-scroll');
        }
        
        if(scrollTop > 100 && colSetsLen > 0){
            targetElement.attr('id','stick_on_scroll-on');
        }else if(colSetsLen > 0){
            targetElement.removeAttr('id');
        }
        

    });


    /**
     * Tab Area Handle
     */
    configureTabAreaAdded('#wcmmq-main-configuration-form'); //Specially for Configure Page
    
    function configureTabAreaAdded( mainSelector = '#wcmmq-main-configuration-form' ){
        var tabSerial = 0;
        var tabArray = new Array();
        var tabHtml = ""
        var tabArea = $(mainSelector + ' .wcmmq-configure-tab-wrapper');
        if(tabArea.length < 1){
            $(mainSelector).prepend('<div class="wcmmq-configure-tab-wrapper wcmmq-section-panel no-background"></div>');
            tabArea = $(mainSelector + ' .wcmmq-configure-tab-wrapper');
        }
        var sectionPanel = $(mainSelector + ' div.wcmmq-section-panel');
        sectionPanel.each(function(index, content){
            
            let table = $(this).find('table');
            let tableCount = table.length;
            if(tableCount > 0){
                
                let firstTable = table.first();
                let tableId = $(this).attr('id');

                if(!tableId){
                    tableId = 'section-panel-' + index;
                    $(this).attr('id', tableId);
                }
                let tableTitle = firstTable.find('thead tr th:first-child h3').text();
                tabArray[tableId] = tableTitle;

                if(tabSerial !== 0){
                    $(this).hide();
                    tabHtml += "<a href='#" + tableId + "' class='tab-button wcmmq-button link-" + tableId + "'>" + tableTitle + "</a>"
                }else{
                    $(this).addClass('active');
                    tabHtml += "<a href='#" + tableId + "' class='tab-button wcmmq-button link-" + tableId + " active'>" + tableTitle + "</a>"
                }

                tabSerial++;

            }
            
        });
        
        if(tabSerial > 1){
            tabHtml += "<a href='#show-all' class='tab-button wcmmq-button'>Show All</a>";
            tabArea.html(tabHtml);
        }
        
        $(document.body).on('click','.wcmmq-configure-tab-wrapper a.tab-button',function(e){
            e.preventDefault();
            $('.wcmmq-configure-tab-wrapper a').removeClass('active');
            $(this).addClass('active');
            $(mainSelector + ' div.wcmmq-section-panel.active').hide();
            let target = $(this).attr('href');
            if(target == '#show-all'){
                sectionPanel.fadeIn();
                return;
            }
            $(mainSelector + ' ' + target).fadeIn('fast').addClass('active');
            
        });
    }

    function findOnlyText(Element){
        var output = Element.map(function () {
            var val = $(this).val();
            var text = $(this).text();
            return val + ' ' + text; // Get text from each element
        })
        .get() // Convert jQuery object to plain array
        .join(' ') // Join with space
        .replace(/\s+/g, ' ') // Replace multiple whitespaces with one space
        .trim();
        return output;
    }

    function urlUpdateBasedOnSearchTerm( searchTerm ){
        let url = new URL(window.location.href);

        url.hash = 'search=' + searchTerm;
        window.history.replaceState(null, '', url);
    }

    $(document.body).on('input','#wcmmq-setting-search-input', function() {
        var searchTerm = $(this).val().replace(/\s+/g, ' ').trim();
        searchTerm = searchTerm.toLowerCase();

        if(searchTerm !== ''){
            $('.wcmmq-configure-tab-wrapper').hide();
        }else{

            $('.wcmmq-configure-tab-wrapper').show();
            $('.wcmmq-configure-tab-wrapper').find('a').first().trigger('click');
        }

        var singlePanel = $('#wcmmq-main-configuration-form').find('.wcmmq-section-panel');
        singlePanel.each(function(){
            var selectedElName = 'td label, td input,td select option,.wcmmq-custom-select-box';
            var targetElement = $(this).find(selectedElName);
            var text = findOnlyText( targetElement ).toLowerCase();
            if(text == ''){return;}

            if (text.indexOf(searchTerm) > -1) {

                $(this).show();
                var TableTr = $(this).find('table tr');
                TableTr.each(function(){
                    var tableHead = $(this).find('div.wcmmq-table-header-inside');
                    var targetRow = $(this).find(selectedElName);
                    var towText = findOnlyText( targetRow ).toLowerCase();// $(this).find('label').text();

                   if(towText.indexOf(searchTerm) > -1 || tableHead.length > 0){

                       $(this).show();
                   }else{
                       $(this).fadeOut('fast');
                   }
                });
            } else {
                $(this).hide();
            }
        });
    });



})(jQuery);