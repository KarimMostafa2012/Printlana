// product-bg-remover.js
(function($) {
    'use strict';
    
    console.log('=== BG Remover Script Loaded ===');
    console.log('jQuery available:', typeof $ !== 'undefined');
    console.log('bgRemoverData available:', typeof bgRemoverData !== 'undefined');
    
    let removeBackgroundFunc = null;
    let isLibraryLoaded = false;
    let processingQueue = new Set();
    
    /**
     * Wait for the UMD library to be available
     */
    function waitForLibrary() {
        return new Promise((resolve) => {
            let attempts = 0;
            const maxAttempts = 100; // 10 seconds
            
            console.log('Waiting for background removal library...');
            console.log('Checking for window.removeBackground...');
            
            const checkLibrary = setInterval(() => {
                attempts++;
                
                // Log what's available on window object
                if (attempts === 1) {
                    console.log('Window keys related to remove:', 
                        Object.keys(window).filter(k => k.toLowerCase().includes('remove')));
                }
                
                // Check multiple possible global names
                if (typeof window.removeBackground !== 'undefined') {
                    clearInterval(checkLibrary);
                    removeBackgroundFunc = window.removeBackground;
                    isLibraryLoaded = true;
                    console.log('✓ Background removal library loaded successfully');
                    resolve(true);
                } else if (typeof window.imglyRemoveBackground !== 'undefined') {
                    clearInterval(checkLibrary);
                    removeBackgroundFunc = window.imglyRemoveBackground.removeBackground;
                    isLibraryLoaded = true;
                    console.log('✓ Background removal library loaded (imglyRemoveBackground)');
                    resolve(true);
                } else if (attempts >= maxAttempts) {
                    clearInterval(checkLibrary);
                    console.error('✗ Background removal library failed to load after ' + (maxAttempts * 100) + 'ms');
                    console.error('window.removeBackground:', typeof window.removeBackground);
                    resolve(false);
                }
                
                if (attempts % 10 === 0) {
                    console.log('Still waiting... attempt', attempts);
                }
            }, 100);
        });
    }
    
    /**
     * Calculate file hash for duplicate detection
     */
    async function calculateFileHash(file) {
        try {
            const arrayBuffer = await file.arrayBuffer();
            const hashBuffer = await crypto.subtle.digest('SHA-256', arrayBuffer);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
        } catch (error) {
            console.error('Hash calculation failed:', error);
            return `${file.name}_${file.size}_${file.lastModified}`;
        }
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
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        resolve({ exists: false });
                    }
                },
                error: function() {
                    resolve({ exists: false });
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
                },
                error: function() {
                    resolve(false);
                }
            });
        });
    }
    
    /**
     * Process image - remove background
     */
    async function processImage(file, fileHash) {
        if (!isLibraryLoaded) {
            console.error('Cannot process: library not loaded');
            showError('Background removal library not loaded yet');
            return null;
        }
        
        if (processingQueue.has(fileHash)) {
            console.log('Image already being processed:', file.name);
            return null;
        }
        
        processingQueue.add(fileHash);
        
        try {
            console.log('Starting background removal for:', file.name);
            showProcessingIndicator(file.name);
            
            const blob = await removeBackgroundFunc(file, {
                output: {
                    format: 'png',
                    quality: 0.8
                }
            });
            
            const originalName = file.name.replace(/\.[^/.]+$/, '');
            const newFileName = originalName + '-no-bg.png';
            const processedFile = new File([blob], newFileName, { type: 'image/png' });
            
            console.log('Background removal complete:', newFileName);
            hideProcessingIndicator();
            
            return processedFile;
            
        } catch (error) {
            console.error('Background removal failed:', error);
            hideProcessingIndicator();
            showError('Failed to remove background. Image uploaded without processing.');
            return null;
        } finally {
            processingQueue.delete(fileHash);
        }
    }
    
    /**
     * Upload file to WordPress media library
     */
    async function uploadToWordPress(file, postId) {
        const formData = new FormData();
        formData.append('async-upload', file);
        formData.append('name', file.name);
        formData.append('action', 'upload-attachment');
        formData.append('post_id', postId);
        
        const wpNonce = $('#_wpnonce').val() || 
                       $('input[name="_wpnonce"]').val() || 
                       $('#_ajax_nonce').val();
        
        if (wpNonce) {
            formData.append('_wpnonce', wpNonce);
        }
        
        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            
            const html = await response.text();
            const idMatch = html.match(/id="media-item-(\d+)"/);
            
            if (idMatch) {
                return parseInt(idMatch[1]);
            }
            
            const altMatch = html.match(/"id":(\d+)/);
            if (altMatch) {
                return parseInt(altMatch[1]);
            }
            
            console.log('Could not extract attachment ID from response');
            return null;
            
        } catch (error) {
            console.error('Upload failed:', error);
            return null;
        }
    }
    
    /**
     * Handle new image upload to product gallery
     */
    async function handleImageUpload(file, postId) {
        if (!file.type || !file.type.startsWith('image/')) {
            console.log('Not an image file, skipping:', file.name);
            return;
        }
        
        try {
            const fileHash = await calculateFileHash(file);
            const existingCheck = await checkIfAlreadyProcessed(fileHash);
            
            if (existingCheck.exists) {
                console.log('Image already processed');
                showNotification('This image was already processed. Using existing version.');
                return;
            }
            
            const processedFile = await processImage(file, fileHash);
            
            if (!processedFile) {
                console.log('Processing failed, continuing with original file');
                return;
            }
            
            const attachmentId = await uploadToWordPress(processedFile, postId);
            
            if (attachmentId) {
                await markAsProcessed(attachmentId, fileHash);
                showNotification('Background removed successfully!');
            }
            
        } catch (error) {
            console.error('Error in handleImageUpload:', error);
        }
    }
    
    /**
     * Hook into WordPress/WooCommerce uploader
     */
    function initializeUploadHook() {
        if (typeof wp === 'undefined' || !wp.media) {
            console.log('WordPress media not ready, retrying in 500ms...');
            setTimeout(initializeUploadHook, 500);
            return;
        }
        
        console.log('✓ Initializing upload hook');
        
        const postId = $('#post_ID').val() || 0;
        const originalMediaFrame = wp.media.view.MediaFrame.Post;
        
        wp.media.view.MediaFrame.Post = originalMediaFrame.extend({
            initialize: function() {
                originalMediaFrame.prototype.initialize.apply(this, arguments);
                
                const self = this;
                
                this.on('uploader:ready', function() {
                    if (self.uploader && self.uploader.uploader) {
                        self.uploader.uploader.bind('FilesAdded', function(up, files) {
                            console.log('Files added to uploader:', files.length);
                            
                            files.forEach(function(file) {
                                const nativeFile = file.getNative ? file.getNative() : file;
                                handleImageUpload(nativeFile, postId).catch(console.error);
                            });
                        });
                    }
                });
            }
        });
        
        console.log('✓ Upload hook initialized');
    }
    
    /**
     * UI Helper functions
     */
    function showProcessingIndicator(fileName) {
        $('.bg-remover-processing').remove();
        const $indicator = $('<div class="bg-remover-processing">')
            .html('<strong>Processing:</strong> Removing background from ' + fileName + '...');
        $('body').append($indicator);
    }
    
    function hideProcessingIndicator() {
        $('.bg-remover-processing').fadeOut(300, function() {
            $(this).remove();
        });
    }
    
    function showNotification(message) {
        const $notif = $('<div class="bg-remover-notification">')
            .html('<strong>✓</strong> ' + message);
        $('body').append($notif);
        setTimeout(function() {
            $notif.fadeOut(300, function() { $(this).remove(); });
        }, 4000);
    }
    
    function showError(message) {
        const $error = $('<div class="bg-remover-error">')
            .html('<strong>⚠</strong> ' + message);
        $('body').append($error);
        setTimeout(function() {
            $error.fadeOut(300, function() { $(this).remove(); });
        }, 6000);
    }
    
    /**
     * Initialize
     */
    $(document).ready(function() {
        console.log('=== Product BG Remover: Document Ready ===');
        
        waitForLibrary().then(function(loaded) {
            if (loaded) {
                console.log('✓ Library loaded, initializing upload hooks');
                initializeUploadHook();
            } else {
                console.error('✗ Failed to load background removal library');
                console.error('Please check if the CDN is accessible and the script is loading correctly');
            }
        });
    });
    
})(jQuery);