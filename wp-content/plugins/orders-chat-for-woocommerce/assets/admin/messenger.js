var OCAdminMessenger = function (wrapper) {

    var $ = jQuery;

    this.wrapper = wrapper;
    this.imageFrame = null;
    this.attachmentId = null;
    this.defaultError = 'Something went wrong, please refresh the page and try again.';
    this.isLoading = false;
    this.limit = null;
    this.offset = null;

    this.init = function () {

        this.limit = parseInt(wrapper.data('limit'));

        this.offset = this.limit;

        this.wrapper.find('[data-order-message-send]').on('click', this.send.bind(this));
        this.wrapper.find('[data-order-message-attach-file]').on('click', this.selectFile.bind(this));
        this.wrapper.find('[data-order-messenger-attachment-remove-image]').on('click', this.removeAttachedImage.bind(this));
        this.wrapper.find('[data-order-messenger-attachment-remove-file]').on('click', this.removeAttachedFile.bind(this));

        this.wrapper.on('click', '[data-delete-message]', this.deleteMessageHandler.bind(this));
        this.wrapper.on('click', '[data-unread-message]', this.unreadMessageHandler.bind(this));

        this.wrapper.find('.order-messenger__messages').scroll(this.handleLoadMore.bind(this));

        this.checkForScrolledClass();
        this.scrollToBottom();
    }

    this.unreadMessageHandler = function (e) {
        debugger;
        e.preventDefault();

        var messageId = parseInt(jQuery(e.target).data('unread-message'));

        if (confirm('Are you sure?')) {
            this.unreadMessage(messageId);
        }
    }

    this.deleteMessageHandler = function (e) {
        e.preventDefault();

        var messageId = parseInt(jQuery(e.target).data('delete-message'));

        if (confirm('Are you sure?')) {
            this.deleteMessage(messageId);
        }

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
                    'place': 'admin'
                },
                beforeSend: this.blockUI.bind(this),
                complete: (function (response) {

                    response = response.responseJSON;

                    if (response.success && response.messagesHTML !== undefined) {

                        var element = this.wrapper.find('.order-messenger__messages');

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
                    this.unBlockUI();
                }).bind(this),
            });
        }
    }

    this.hideNoMessagesNotice = function () {
        this.wrapper.find('[data-messenger-no-messages]').hide();
    }

    this.checkForScrolledClass = function () {
        var messagesContainer = this.wrapper.find('.order-messenger__messages')[0];

        if (messagesContainer.scrollHeight > messagesContainer.clientHeight) {
            this.wrapper.addClass('order-messenger--scrolled');
        } else {
            this.wrapper.removeClass('order-messenger--scrolled');
        }
    }

    this.removeAttachedFile = function (e) {
        e.preventDefault();

        this.setAttachmentId(0);
        this.hideAttachedFile();
        this.showFileSelection();
    }

    this.removeAttachedImage = function (e) {
        e.preventDefault();

        this.setAttachmentId(0);
        this.hideAttachedImage();
        this.showFileSelection();
    }

    this.hideFileSelection = function () {
        this.wrapper.find('[data-order-message-attach-file]').parent().hide();
    }

    this.showFileSelection = function () {
        this.wrapper.find('[data-order-message-attach-file]').parent().show();
    }

    this.wipeOutSendArea = function () {
        this.setRawMessage('');
        this.setAttachmentId(0);
        this.hideAttachedImage();
        this.hideAttachedFile();
        this.showFileSelection();
    }

    this.hideAttachedFile = function () {
        var $attachment = this.wrapper.find('[data-order-messenger-attachment]').filter('.order-messenger-attachment--file');
        $attachment.find('[data-order-messenger-attachment-filename]').text('');
        $attachment.find('[data-order-messenger-attachment-filename]').attr('src', '#');
        $attachment.hide();
    }

    this.showAttachedFile = function (fileName, fileSrc) {
        var $attachment = this.wrapper.find('[data-order-messenger-attachment]').filter('.order-messenger-attachment--file');
        $attachment.find('[data-order-messenger-attachment-filename]').text(fileName);
        $attachment.find('[data-order-messenger-attachment-filename]').attr('href', fileSrc);
        $attachment.show();
    }

    this.showAttachedImage = function (imageSrc) {
        var $attachment = this.wrapper.find('[data-order-messenger-attachment]').filter('.order-messenger-attachment--image');
        $attachment.find('[data-order-messenger-attachment-image]').attr('src', imageSrc);
        $attachment.show();
    }

    this.hideAttachedImage = function () {
        var $attachment = this.wrapper.find('[data-order-messenger-attachment]').filter('.order-messenger-attachment--image');
        $attachment.find('[data-order-messenger-attachment-image]').attr('src', '#');
        $attachment.hide();
    }

    this.onCloseMedia = function () {
        var selection = this.imageFrame.state().get('selection');

        selection.each((function (attachment) {
            if (attachment) {
                this.hideFileSelection();
                this.wrapper.find('textarea').removeAttr('required');
                this.setAttachmentId(attachment.attributes.id);

                if (attachment.attributes.type === 'image') {
                    this.showAttachedImage(attachment.attributes.url);
                } else {
                    this.showAttachedFile(attachment.attributes.filename, attachment.attributes.url);
                }
            }
        }).bind(this));
    }

    this.selectFile = function (e) {
        e.preventDefault();

        if (this.imageFrame) {
            this.imageFrame.open();
        } else {

            this.imageFrame = wp.media({
                title: 'Select Media',
                multiple: false,
            });

            this.imageFrame.on('close', this.onCloseMedia.bind(this));
            this.imageFrame.open();
        }

    }

    this.getRawMessage = function () {
        return this.wrapper.find('[data-order-message-textarea]').val();
    }

    this.setRawMessage = function (message) {
        return this.wrapper.find('[data-order-message-textarea]').val(message);
    }

    this.getURL = function () {
        return this.wrapper.data('url');
    }

    this.getOrderId = function () {
        return parseInt(this.wrapper.data('orderId'));
    }

    this.getNonce = function () {
        return this.wrapper.data('nonce');
    }

    this.getAttachmentId = function () {
        return this.attachmentId;
    }

    this.setAttachmentId = function (id) {
        this.attachmentId = id;
    }

    this.getTotalMessages = function () {
        return parseInt(this.wrapper.data('total'));
    }

    this.insertMessage = function (messageHTML) {
        this.wrapper.find('[data-order-messenger-messages-container]').append(messageHTML);
    }

    this.checkValidity = function () {
        var message = this.wrapper.find('[data-order-message-textarea]').val();
        console.log(this.getAttachmentId());
        if (!this.getAttachmentId() && message.length < 1) {
            alert('Please input your message');
            return false;
        }

        return true;
    }

    this.showError = function (error) {
        this.wrapper.find('[data-order-messenger-error]').show().find('p').text(error);
    }

    this.hideError = function () {
        this.wrapper.find('[data-order-messenger-error]').hide().find('p').text('');
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

    this.scrollToBottom = function () {
        var element = this.wrapper.find('.order-messenger__messages')[0];
        element.scrollTop = element.scrollHeight;
    }

    this.unreadMessage = function (messageId) {
        debugger;
        jQuery.ajax({
            url: this.getURL() + '/unread/' + messageId,
            headers: {
                'X-WP-Nonce': this.getNonce(),
            },
            method: 'POST',
            beforeSend: (function () {
                this.hideError();
                this.blockUI();
            }).bind(this),
            complete: (function (response) {
                response = response.responseJSON;

                if (response.success) {
                    this.wrapper.find('[data-unread-message=' + messageId + ']').hide();
                } else {
                    if (response.error) {
                        this.showError(response.error);
                    } else {
                        this.showError(this.defaultError);
                    }
                }

                this.unBlockUI();
                this.checkForScrolledClass();

            }).bind(this)
        });
    }

    this.deleteMessage = function (messageId) {
        jQuery.ajax({
            url: this.getURL() + '/delete/' + messageId,
            headers: {
                'X-WP-Nonce': this.getNonce(),
            },
            method: 'POST',
            beforeSend: (function () {
                this.hideError();
                this.blockUI();
            }).bind(this),
            complete: (function (response) {
                response = response.responseJSON;

                if (response.success) {
                    this.removeMessage(messageId);
                } else {
                    if (response.error) {
                        this.showError(response.error);
                    } else {
                        this.showError(this.defaultError);
                    }
                }

                this.unBlockUI();
                this.checkForScrolledClass();

            }).bind(this)
        });
    }

    this.removeMessage = function (messageId) {
        this.wrapper.find('.order-message').filter('[data-message-id=' + messageId + ']').fadeOut(100).remove();
    }

    this.getCustomData = function () {

        let data = $("[name^=order-messenger-custom-data]");

        if (data.length > 0) {
            return data.serializeArray();
        }

        return [];
    }

    this.send = function (e) {

        e.preventDefault();

        if (this.checkValidity()) {
            jQuery.ajax({
                url: this.getURL() + '/sendAdmin',
                headers: {
                    'X-WP-Nonce': this.getNonce(),
                    "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
                },
                method: 'POST',
                beforeSend: (function () {
                    this.hideError();
                    this.blockUI();
                }).bind(this),
                complete: (function (response) {
                    response = response.responseJSON;

                    if (response.success) {
                        this.insertMessage(response.messageHTML);
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

                    this.unBlockUI();
                    this.wipeOutSendArea();
                    this.checkForScrolledClass();
                    this.scrollToBottom();

                }).bind(this),
                data: {
                    'order_id': this.getOrderId(),
                    'attachment_id': this.getAttachmentId(),
                    'message': this.getRawMessage(),
                    'custom_data': this.getCustomData()
                }
            });
        }
    }
}

jQuery(document).ready(function ($) {

    $.each($('.order-messenger'), function (i, el) {
        var orderMessenger = new OCAdminMessenger($(el));
        orderMessenger.init();
    });
});