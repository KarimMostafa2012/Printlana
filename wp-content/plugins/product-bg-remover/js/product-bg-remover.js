// product-bg-remover.js
(function($) {
    'use strict';
    
    let removeBackgroundFunc = null;
    let isLibraryLoaded = false;
    let processingQueue = new Set();
    
    /**
     * Load the background removal library
     */
    async function loadBackgroundRemovalLibrary() {
        if (isLibraryLoaded) return true;
        
        try {
            // Use UMD version which doesn't have CORS issues
            if (typeof window.removeBackground !== 'undefined') {
                removeBackgroundFunc = window.removeBackground;
                isLibraryLoaded = true;
                console.log('Background removal library loaded');
                return true;
            }
            
            console.error('Background removal library not found');
            return false;
        } catch (error) {
            console.error('Failed to load background removal library:', error);
            return false;
        }
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
            // Fallback to filename + size + lastModified
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
        // Check if library is loaded
        if (!removeBackgroundFunc) {
            const loaded = await loadBackgroundRemovalLibrary();
            if (!loaded) {
                showError('Background removal library not loaded');
                return null;
            }
        }
        
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
            
            // Remove background using the UMD version
            const blob = await removeBackgroundFunc(file, {
                output: {
                    format: 'png',
                    quality: 0.8
                },
                model: 'small' // Use smaller model for faster processing
            });
            
            // Create new file with -no-bg suffix
            const originalName = file.name.replace(/\.[^/.]+$/, '');
            const newFileName = originalName + '-no-bg.png';
            const processedFile = new File([blob], newFileName, { type: 'image/png' });
            
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
        
        // Get nonce from the page
        const wpNonce = $('#_wpnonce').val();
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
            
            // Parse response to get attachment ID
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const idMatch = html.match(/id="media-item-(\d+)"/);
            
            if (idMatch) {
                return parseInt(idMatch[1]);
            }
            
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
        // Only process images
        if (!file.type.startsWith('image/')) {
            return;
        }
        
        try {
            // Calculate hash
            const fileHash = await calculateFileHash(file);
            
            // Check if already processed
            const existingCheck = await checkIfAlreadyProcessed(fileHash);
            
            if (existingCheck.exists) {
                console.log('Image already processed, notifying user');
                showNotification('This image was already processed. Using existing version.');
                return;
            }
            
            // Process new image
            const processedFile = await processImage(file, fileHash);
            
            if (!processedFile) {
                // If processing failed, continue with original upload
                console.log('Processing failed, continuing with original file');
                return;
            }
            
            // Upload processed file
            const attachmentId = await uploadToWordPress(processedFile, postId);
            
            if (attachmentId) {
                // Mark as processed
                await markAsProcessed(attachmentId, fileHash);
                showNotification('Background removed successfully!');
            }
            
        } catch (error) {
            console.error('Error in handleImageUpload:', error);
            showError('An error occurred. Image uploaded without background removal.');
        }
    }
    
    /**
     * Hook into WordPress/WooCommerce uploader
     */
    function initializeUploadHook() {
        // Wait for WordPress media library to be ready
        if (typeof wp === 'undefined' || !wp.media) {
            console.log('WordPress media not ready, retrying...');
            setTimeout(initializeUploadHook, 500);
            return;
        }
        
        console.log('Initializing background remover upload hook');
        
        // Get current post ID
        const postId = $('#post_ID').val() || 0;
        
        // Hook into plupload (WordPress native uploader)
        $(document).on('click', '.upload_image_button, #set-post-thumbnail, .woocommerce-product-gallery__wrapper', function() {
            setTimeout(function() {
                if (typeof wp.Uploader !== 'undefined' && wp.Uploader.queue) {
                    wp.Uploader.queue.bind('UploaderReady', function(uploader) {
                        uploader.bind('FilesAdded', function(up, files) {
                            console.log('Files added to uploader:', files.length);
                            
                            files.forEach(function(file) {
                                // Get native file object
                                const nativeFile = file.getNative ? file.getNative() : file;
                                
                                // Process in background (don't block upload)
                                handleImageUpload(nativeFile, postId).catch(function(error) {
                                    console.error('Background removal error:', error);
                                });
                            });
                        });
                    });
                }
            }, 100);
        });
        
        // Also hook into drag and drop
        $(document).on('drop', '.uploader-inline, .drag-drop', function(e) {
            if (e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files) {
                const files = e.originalEvent.dataTransfer.files;
                Array.from(files).forEach(function(file) {
                    handleImageUpload(file, postId).catch(function(error) {
                        console.error('Background removal error:', error);
                    });
                });
            }
        });
    }
    
    /**
     * UI Helper functions
     */
    function showProcessingIndicator(fileName) {
        // Remove any existing indicators
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
            $notif.fadeOut(300, function() {
                $(this).remove();
            });
        }, 4000);
    }
    
    function showError(message) {
        const $error = $('<div class="bg-remover-error">')
            .html('<strong>⚠</strong> ' + message);
        
        $('body').append($error);
        
        setTimeout(function() {
            $error.fadeOut(300, function() {
                $(this).remove();
            });
        }, 6000);
    }
    
    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        console.log('Product BG Remover: Document ready');
        
        // Load library first
        loadBackgroundRemovalLibrary().then(function(loaded) {
            if (loaded) {
                // Initialize upload hooks
                initializeUploadHook();
            } else {
                console.error('Failed to load background removal library');
                showError('Background removal feature unavailable');
            }
        });
    });
    
})(jQuery);