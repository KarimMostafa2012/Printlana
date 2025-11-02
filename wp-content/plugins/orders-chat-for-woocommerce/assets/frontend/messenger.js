var OCMessenger = function (wrapper) {

    var $ = jQuery;

    this.wrapper = wrapper;
    this.defaultError = 'Sorry, something went wrong. Please try again.';
    this.limit = null;
    this.offset = null;
    this.lightBox = null;
    this.isLoading = false;

    this.init = function () {
        this.limit = parseInt(wrapper.data('limit'));

        this.offset = this.limit;

        // Scroll down after images are loaded
        this.wrapper.find('img').one("load", this.scrollToBottom.bind(this)).each((function () {
            if (this.complete) {
                $(this).load();
                this.scrollToBottom();
            }
        }).bind(this));

        this.wrapper.find('[data-message-textarea]').keydown((function (e) {

            if (this.checkValidity() && e.ctrlKey && e.keyCode === 13) {
                this.sendMessage(e);
            }

        }).bind(this));

        this.wrapper.find('[data-message-sending-form]').on('submit', this.sendMessage.bind(this));
        this.wrapper.find('[data-messenger-fileinput]').on('change', this.handleFileChoosing.bind(this));
        this.wrapper.find('[data-messenger-attached-file-remove]').on('click', this.handleFileRemoving.bind(this));

        this.wrapper.find('.om-messages-wrapper').scroll(this.handleLoadMore.bind(this));

        this.scrollToBottom();
        this.initImageBox();
    }

    this.checkValidity = function () {
        var messageForm = this.wrapper.find('[data-message-sending-form]')[0];

        if (messageForm.checkValidity()) {
            return true;
        }

        messageForm.reportValidity();

        return false;
    }

    this.initImageBox = function () {
        if (this.lightBox) {
            this.lightBox.destroy();
        }

        this.lightBox = this.wrapper.find('.om-message-attachment--image a').simpleLightbox();
    }

    this.handleLoadMore = function (e) {

        if (this.isLoading || this.getTotalMessages() < this.offset) {
            return;
        }

        if (jQuery(e.target).scrollTop() < 100) {

            this.hideError();

            jQuery.ajax({
                url: this.getURL() + '/get/' + this.limit + '/' + this.offset,
                headers: {
                    'X-WP-Nonce': this.getNonce(),
                },
                method: 'GET',
                data: {
                    'order_id': this.getOrderId(),
                    'is_html': true,
                    'place': 'frontend'
                },
                beforeSend: this.blockUI.bind(this),
                complete: (function (response) {

                    response = response.responseJSON;

                    if (response.success && response.messagesHTML !== undefined) {

                        var element = this.wrapper.find('.om-messages-wrapper');

                        var oldBlockHeight = element[0].scrollHeight;

                        element.prepend(response.messagesHTML);

                        element.scrollTop(element[0].scrollHeight - oldBlockHeight); //

                        this.offset += this.limit;

                    } else {
                        if (response.error) {
                            this.showError(response.error);
                        } else {
                            this.showError(this.defaultError);
                        }
                    }

                    this.initImageBox();
                    this.unBlockUI();
                }).bind(this),
            });
        }
    }

    this.handleFileChoosing = function (e) {
        e.preventDefault();

        if (e.target.files[0].size > this.wrapper.data('max-file-size')) {
            alert('Max size is ' + this.wrapper.data('max-file-size-string'));
            return false;
        }


        this.showAttachedFile(e.target.files[0].name);
        this.wrapper.find('textarea').removeAttr('required');
        this.hideChoseFileInput();
    }

    this.handleFileRemoving = function (e = null) {
        if (e) {
            e.preventDefault();
        }

        this.hideAttachedFile();
        this.showChoseFileInput();
        this.wrapper.find('textarea').attr('required', true);
        this.wrapper.find('[data-messenger-fileinput]').val('');
    }

    this.hideChoseFileInput = function () {
        this.wrapper.find('[data-messenger-attach-file]').hide();
    }

    this.showChoseFileInput = function () {
        this.wrapper.find('[data-messenger-attach-file]').show();
    }

    this.showAttachedFile = function (fileName) {
        this.wrapper.find('[data-messenger-attached-file]').show().find('[data-messenger-attached-filename]').text(fileName);
    }

    this.hideAttachedFile = function () {
        this.wrapper.find('[data-messenger-attached-file]').hide().find('[data-messenger-attached-filename]').text('');
    }

    this.scrollToBottom = function () {
        var element = this.wrapper.find('.om-messages-wrapper')[0];
        element.scrollTop = element.scrollHeight;
    }

    this.getSelectedFile = function () {
        if (this.wrapper.find('[data-messenger-fileinput]').length) {
            return this.wrapper.find('[data-messenger-fileinput]')[0].files[0];
        }
        return null;
    }

    this.hideNoMessagesNotice = function () {
        this.wrapper.find('[data-messenger-no-messages]').hide();
    }

    this.sendMessage = function (e) {
        e.preventDefault();

        var formData = new FormData();

        formData.append('file', this.getSelectedFile());
        formData.append('order_id', this.getOrderId());
        formData.append('message', this.getCurrentInput());

        this.hideError();

        jQuery.ajax({
            url: this.getURL() + '/send',
            headers: {
                'X-WP-Nonce': this.getNonce(),
            },
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: this.blockUI.bind(this),
            complete: (function (response) {

                response = response.responseJSON;

                if (response.success && response.messageHTML !== undefined) {
                    this.insertMessage(response.messageHTML);
                    this.scrollToBottom();
                } else {
                    if (response.error) {
                        this.showError(response.error);
                    } else {
                        this.showError(this.defaultError);
                    }
                }

                if (this.getTotalMessages() < 1) {
                    this.hideNoMessagesNotice();
                }

                this.wipeOutSendArea();
                this.unBlockUI();
                this.initImageBox();

                this.scrollToBottom();

            }).bind(this),
        });
    }

    this.showError = function (error) {
        this.wrapper.find('[data-messenger-error]').text(error).show();
    }

    this.hideError = function () {
        this.wrapper.find('[data-messenger-error]').text('').hide();
    }

    this.wipeOutSendArea = function () {
        this.wrapper.find('[data-message-textarea]').val('');
        this.handleFileRemoving();
    }

    this.getOrderId = function () {
        return this.wrapper.data('orderId');
    }

    this.getCurrentInput = function () {
        return this.wrapper.find('[data-message-textarea]').val();
    }

    this.getTotalMessages = function () {
        return parseInt(this.wrapper.data('total'));
    }

    this.blockUI = function () {

        this.isLoading = true;

        this.wrapper.block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });
    }

    this.unBlockUI = function () {
        this.isLoading = false;
        this.wrapper.unblock();
    }

    this.insertMessage = function (message) {
        this.wrapper.find('.om-messages-wrapper').append(message);
    }

    this.getURL = function () {
        return this.wrapper.data('url');
    }

    this.getNonce = function () {
        return this.wrapper.data('nonce');
    }
}

jQuery(document).ready(function ($) {

    $.each($('.om-messenger'), function (i, el) {

        var orderMessenger = new OCMessenger($(el));

        orderMessenger.init();
    });

});