// product-bg-remover.js
(async function($) {
    'use strict';
    
    // Import the background removal function
    const { removeBackground } = await import('https://unpkg.com/@imgly/background-removal@1.4.5/dist/index.es.js');
    
    let processingQueue = new Set();
    
    /**
     * Calculate file hash for duplicate detection
     */
    async function calculateFileHash(file) {
        const arrayBuffer = await file.arrayBuffer();
        const hashBuffer = await crypto.subtle.digest('SHA-256', arrayBuffer);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    }
    
    /**
     * Check if this file was already processed
     */
    async function checkIfAlreadyProcessed(fileHash) {
        return new Promise((resolve) => {
            $.ajax({
                url: bgRemoverData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'check_processed_image',
                    nonce: bgRemoverData.nonce,
                    file_hash: fileHash
                },
                success: function(response) {
                    resolve(response.data);
                }
            });
        });
    }
    
    /**
     * Mark image as processed in database
     */
    async function markAsProcessed(attachmentId, fileHash) {
        return new Promise((resolve) => {
            $.ajax({
                url: bgRemoverData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mark_image_processed',
                    nonce: bgRemoverData.nonce,
                    attachment_id: attachmentId,
                    file_hash: fileHash
                },
                success: function(response) {
                    resolve(response);
                }
            });
        });
    }
    
    /**
     * Process image - remove background
     */
    async function processImage(file, fileHash) {
        // Check if already in processing queue
        if (processingQueue.has(fileHash)) {
            console.log('Image already being processed:', file.name);
            return null;
        }
        
        processingQueue.add(fileHash);
        
        try {
            console.log('Removing background from:', file.name);
            
            // Show loading indicator
            showProcessingIndicator(file.name);
            
            // Remove background
            const blob = await removeBackground(file, {
                output: {
                    format: 'png',
                    quality: 0.8
                }
            });
            
            // Create new file with -no-bg suffix
            const newFileName = file.name.replace(/\.[^/.]+$/, '') + '-no-bg.png';
            const processedFile = new File([blob], newFileName, { type: 'image/png' });
            
            hideProcessingIndicator();
            
            return processedFile;
            
        } catch (error) {
            console.error('Background removal failed:', error);
            hideProcessingIndicator();
            showError('Failed to remove background: ' + error.message);
            return null;
        } finally {
            processingQueue.delete(fileHash);
        }
    }
    
    /**
     * Handle new image upload to product gallery
     */
    async function handleImageUpload(file) {
        // Calculate hash
        const fileHash = await calculateFileHash(file);
        
        // Check if already processed
        const existingCheck = await checkIfAlreadyProcessed(fileHash);
        
        if (existingCheck.exists) {
            console.log('Image already processed, reusing:', existingCheck.url);
            showNotification('Using existing processed image');
            // Return existing attachment ID to WordPress
            return existingCheck.attachment_id;
        }
        
        // Process new image
        const processedFile = await processImage(file, fileHash);
        
        if (!processedFile) {
            // If processing failed, upload original
            return null;
        }
        
        // Upload processed file
        const formData = new FormData();
        formData.append('file', processedFile);
        formData.append('action', 'upload-attachment');
        formData.append('post_id', $('#post_ID').val());
        
        try {
            const response = await fetch(bgRemoverData.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success && data.data.id) {
                // Mark as processed
                await markAsProcessed(data.data.id, fileHash);
                showNotification('Background removed successfully');
                return data.data.id;
            }
            
        } catch (error) {
            console.error('Upload failed:', error);
            showError('Failed to upload processed image');
        }
        
        return null;
    }
    
    /**
     * Hook into WordPress media uploader
     */
    function hookIntoMediaUploader() {
        // Hook into WooCommerce product gallery
        if (typeof wp !== 'undefined' && wp.media) {
            const originalUploader = wp.media.view.MediaFrame.Post;
            
            wp.media.view.MediaFrame.Post = originalUploader.extend({
                initialize: function() {
                    originalUploader.prototype.initialize.apply(this, arguments);
                    
                    this.on('content:render:browse', function() {
                        const uploader = this.uploader;
                        if (!uploader) return;
                        
                        // Intercept file added event
                        uploader.uploader.bind('FilesAdded', async function(up, files) {
                            for (const file of files) {
                                if (file.type.startsWith('image/')) {
                                    // Process in background
                                    handleImageUpload(file.getNative());
                                }
                            }
                        });
                    });
                }
            });
        }
    }
    
    /**
     * UI Helper functions
     */
    function showProcessingIndicator(fileName) {
        const $indicator = $('<div class="bg-remover-processing">')
            .text('Removing background from ' + fileName + '...')
            .css({
                position: 'fixed',
                top: '50px',
                right: '20px',
                background: '#0073aa',
                color: 'white',
                padding: '15px 20px',
                borderRadius: '4px',
                zIndex: 99999
            });
        $('body').append($indicator);
    }
    
    function hideProcessingIndicator() {
        $('.bg-remover-processing').fadeOut(300, function() {
            $(this).remove();
        });
    }
    
    function showNotification(message) {
        const $notif = $('<div class="bg-remover-notification">')
            .text(message)
            .css({
                position: 'fixed',
                top: '50px',
                right: '20px',
                background: '#46b450',
                color: 'white',
                padding: '15px 20px',
                borderRadius: '4px',
                zIndex: 99999
            });
        $('body').append($notif);
        setTimeout(() => $notif.fadeOut(300, function() { $(this).remove(); }), 3000);
    }
    
    function showError(message) {
        const $error = $('<div class="bg-remover-error">')
            .text(message)
            .css({
                position: 'fixed',
                top: '50px',
                right: '20px',
                background: '#dc3232',
                color: 'white',
                padding: '15px 20px',
                borderRadius: '4px',
                zIndex: 99999
            });
        $('body').append($error);
        setTimeout(() => $error.fadeOut(300, function() { $(this).remove(); }), 5000);
    }
    
    // Initialize
    $(document).ready(function() {
        hookIntoMediaUploader();
    });
    
})(jQuery);