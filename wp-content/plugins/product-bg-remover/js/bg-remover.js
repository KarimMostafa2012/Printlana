/**
 * Client-Side Background Remover - Canvas-based Implementation
 * Removes backgrounds using color detection and canvas manipulation
 */

(function ($) {
    'use strict';

    class BackgroundRemover {
        constructor() {
            this.settings = bgRemoverSettings.settings;
            this.init();
        }

        init() {
            this.addMediaLibraryButton();
        }

        /**
         * Add "Remove Background" button to media library
         */
        addMediaLibraryButton() {
            if (wp.media) {
                const self = this;

                wp.media.view.Attachment.Details.TwoColumn = wp.media.view.Attachment.Details.TwoColumn.extend({
                    template: function (view) {
                        const template = wp.media.template('attachment-details-two-column');
                        const html = template(view);
                        const $html = $('<div>').html(html);

                        // Only add button for images
                        if (view.type === 'image' && !view.meta._background_removed) {
                            const button = `
                                <button type="button" class="button button-primary button-large remove-bg-btn"
                                        style="margin-top: 10px; width: 100%;">
                                    Remove Background
                                </button>
                            `;
                            $html.find('.attachment-info').append(button);
                        }

                        return $html.html();
                    },

                    events: function () {
                        return _.extend({}, wp.media.view.Attachment.Details.prototype.events, {
                            'click .remove-bg-btn': 'removeBackground'
                        });
                    },

                    removeBackground: function (e) {
                        e.preventDefault();
                        const attachmentId = this.model.get('id');
                        const imageUrl = this.model.get('url');
                        self.processImage(imageUrl, attachmentId);
                    }
                });
            }
        }

        /**
         * Process image and remove background
         */
        async processImage(imageUrl, attachmentId) {
            this.showNotification('Processing image...', 'processing');

            try {
                // Calculate file hash for duplicate detection
                const fileHash = await this.calculateImageHash(imageUrl);

                // Check if already processed
                const existingCheck = await this.checkExistingProcessed(fileHash);
                if (existingCheck.exists) {
                    this.showNotification('Using existing processed image', 'success');
                    this.replaceImage(attachmentId, existingCheck.url);
                    return;
                }

                // Load image
                const img = await this.loadImage(imageUrl);

                // Remove background
                const processedBlob = await this.removeBackground(img);

                // Upload processed image
                const newAttachmentId = await this.uploadImage(processedBlob, attachmentId);

                // Mark as processed
                await this.markAsProcessed(newAttachmentId, fileHash);

                this.showNotification('Background removed successfully!', 'success');

                // Refresh media library
                setTimeout(() => {
                    if (wp.media.frame) {
                        wp.media.frame.content.get().collection.props.set({ignore: (+ new Date())});
                    }
                }, 1000);

            } catch (error) {
                console.error('Background removal error:', error);
                this.showNotification('Error: ' + error.message, 'error');
            }
        }

        /**
         * Core background removal algorithm using canvas
         */
        async removeBackground(img) {
            return new Promise((resolve, reject) => {
                try {
                    // Create canvas
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');

                    canvas.width = img.width;
                    canvas.height = img.height;

                    // Draw image
                    ctx.drawImage(img, 0, 0);

                    // Get image data
                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    const data = imageData.data;

                    // Detect background color from corners
                    const bgColor = this.detectBackgroundColor(data, canvas.width, canvas.height);

                    // Remove background
                    this.removeColorFromImage(data, bgColor);

                    // Apply smoothing if enabled
                    if (this.settings.smoothing > 0) {
                        this.smoothEdges(data, canvas.width, canvas.height);
                    }

                    // Apply feathering if enabled
                    if (this.settings.feather > 0) {
                        this.featherEdges(data, canvas.width, canvas.height);
                    }

                    // Put processed data back
                    ctx.putImageData(imageData, 0, 0);

                    // Convert to blob
                    canvas.toBlob((blob) => {
                        if (blob) {
                            resolve(blob);
                        } else {
                            reject(new Error('Failed to create image blob'));
                        }
                    }, 'image/png', this.settings.quality);

                } catch (error) {
                    reject(error);
                }
            });
        }

        /**
         * Detect background color from image corners
         */
        detectBackgroundColor(data, width, height) {
            const corners = [
                this.getPixel(data, 0, 0, width),                    // Top-left
                this.getPixel(data, width - 1, 0, width),            // Top-right
                this.getPixel(data, 0, height - 1, width),           // Bottom-left
                this.getPixel(data, width - 1, height - 1, width)    // Bottom-right
            ];

            // Average the corner colors
            const avgColor = {
                r: Math.round(corners.reduce((sum, c) => sum + c.r, 0) / 4),
                g: Math.round(corners.reduce((sum, c) => sum + c.g, 0) / 4),
                b: Math.round(corners.reduce((sum, c) => sum + c.b, 0) / 4)
            };

            return avgColor;
        }

        /**
         * Get pixel color at coordinates
         */
        getPixel(data, x, y, width) {
            const index = (y * width + x) * 4;
            return {
                r: data[index],
                g: data[index + 1],
                b: data[index + 2],
                a: data[index + 3]
            };
        }

        /**
         * Remove background color from image
         */
        removeColorFromImage(data, bgColor) {
            const tolerance = this.settings.tolerance;

            for (let i = 0; i < data.length; i += 4) {
                const r = data[i];
                const g = data[i + 1];
                const b = data[i + 2];

                // Calculate color difference
                const diff = this.colorDistance(
                    { r, g, b },
                    bgColor
                );

                // If color is similar to background, make it transparent
                if (diff < tolerance) {
                    // Gradual transparency based on similarity
                    const alpha = Math.max(0, Math.min(255, (diff / tolerance) * 255));
                    data[i + 3] = alpha;
                } else {
                    // Keep original alpha
                    data[i + 3] = data[i + 3];
                }
            }
        }

        /**
         * Calculate Euclidean distance between two colors
         */
        colorDistance(color1, color2) {
            const rDiff = color1.r - color2.r;
            const gDiff = color1.g - color2.g;
            const bDiff = color1.b - color2.b;
            return Math.sqrt(rDiff * rDiff + gDiff * gDiff + bDiff * bDiff);
        }

        /**
         * Smooth edges using erosion/dilation technique
         */
        smoothEdges(data, width, height) {
            const iterations = this.settings.smoothing;

            for (let iter = 0; iter < iterations; iter++) {
                const tempData = new Uint8ClampedArray(data);

                for (let y = 1; y < height - 1; y++) {
                    for (let x = 1; x < width - 1; x++) {
                        const index = (y * width + x) * 4;

                        // Get neighboring alpha values
                        const neighbors = [
                            data[((y - 1) * width + x) * 4 + 3],       // Top
                            data[((y + 1) * width + x) * 4 + 3],       // Bottom
                            data[(y * width + (x - 1)) * 4 + 3],       // Left
                            data[(y * width + (x + 1)) * 4 + 3],       // Right
                            data[((y - 1) * width + (x - 1)) * 4 + 3], // Top-left
                            data[((y - 1) * width + (x + 1)) * 4 + 3], // Top-right
                            data[((y + 1) * width + (x - 1)) * 4 + 3], // Bottom-left
                            data[((y + 1) * width + (x + 1)) * 4 + 3]  // Bottom-right
                        ];

                        // Average alpha with neighbors
                        const avgAlpha = neighbors.reduce((sum, val) => sum + val, data[index + 3]) / 9;
                        tempData[index + 3] = avgAlpha;
                    }
                }

                // Copy back
                data.set(tempData);
            }
        }

        /**
         * Apply feathering to edges
         */
        featherEdges(data, width, height) {
            const featherRadius = this.settings.feather;
            const tempData = new Uint8ClampedArray(data);

            for (let y = 0; y < height; y++) {
                for (let x = 0; x < width; x++) {
                    const index = (y * width + x) * 4;
                    const currentAlpha = data[index + 3];

                    // Only feather edges (semi-transparent pixels)
                    if (currentAlpha > 0 && currentAlpha < 255) {
                        let alphaSum = 0;
                        let count = 0;

                        // Sample in feather radius
                        for (let fy = -featherRadius; fy <= featherRadius; fy++) {
                            for (let fx = -featherRadius; fx <= featherRadius; fx++) {
                                const ny = y + fy;
                                const nx = x + fx;

                                if (nx >= 0 && nx < width && ny >= 0 && ny < height) {
                                    const nIndex = (ny * width + nx) * 4 + 3;
                                    alphaSum += data[nIndex];
                                    count++;
                                }
                            }
                        }

                        tempData[index + 3] = alphaSum / count;
                    }
                }
            }

            data.set(tempData);
        }

        /**
         * Load image from URL
         */
        loadImage(url) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.crossOrigin = 'Anonymous';
                img.onload = () => resolve(img);
                img.onerror = () => reject(new Error('Failed to load image'));
                img.src = url;
            });
        }

        /**
         * Calculate image hash for duplicate detection
         */
        async calculateImageHash(url) {
            try {
                const response = await fetch(url);
                const buffer = await response.arrayBuffer();
                const hashBuffer = await crypto.subtle.digest('SHA-256', buffer);
                const hashArray = Array.from(new Uint8Array(hashBuffer));
                return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
            } catch (error) {
                console.warn('Hash calculation failed, using URL:', error);
                return btoa(url).substring(0, 64);
            }
        }

        /**
         * Check if image already processed
         */
        checkExistingProcessed(fileHash) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: bgRemoverSettings.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'check_processed_image',
                        nonce: bgRemoverSettings.nonce,
                        file_hash: fileHash
                    },
                    success: (response) => {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error('Check failed'));
                        }
                    },
                    error: () => reject(new Error('Server error'))
                });
            });
        }

        /**
         * Upload processed image to WordPress
         */
        uploadImage(blob, originalId) {
            return new Promise((resolve, reject) => {
                const formData = new FormData();
                formData.append('action', 'upload-attachment');
                formData.append('async-upload', blob, 'bg-removed.png');
                formData.append('name', 'bg-removed.png');
                formData.append('_wpnonce', $('#_wpnonce').val() || '');

                $.ajax({
                    url: bgRemoverSettings.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: (response) => {
                        if (response.success && response.data.id) {
                            resolve(response.data.id);
                        } else {
                            reject(new Error('Upload failed'));
                        }
                    },
                    error: () => reject(new Error('Upload error'))
                });
            });
        }

        /**
         * Mark image as processed
         */
        markAsProcessed(attachmentId, fileHash) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: bgRemoverSettings.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mark_image_processed',
                        nonce: bgRemoverSettings.nonce,
                        attachment_id: attachmentId,
                        file_hash: fileHash
                    },
                    success: (response) => {
                        if (response.success) {
                            resolve();
                        } else {
                            reject(new Error('Mark failed'));
                        }
                    },
                    error: () => reject(new Error('Server error'))
                });
            });
        }

        /**
         * Show notification to user
         */
        showNotification(message, type) {
            const existingNotification = $('.bg-remover-notification, .bg-remover-processing, .bg-remover-error');
            existingNotification.remove();

            const className = type === 'processing' ? 'bg-remover-processing' :
                            type === 'error' ? 'bg-remover-error' : 'bg-remover-notification';

            const notification = $(`<div class="${className}">${message}</div>`);
            $('body').append(notification);

            if (type !== 'processing') {
                setTimeout(() => {
                    notification.fadeOut(300, function () {
                        $(this).remove();
                    });
                }, 3000);
            }
        }

        /**
         * Replace image in media library
         */
        replaceImage(oldId, newUrl) {
            if (wp.media.frame) {
                wp.media.frame.content.get().collection.props.set({ignore: (+ new Date())});
            }
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function () {
        if (bgRemoverSettings && bgRemoverSettings.enabled) {
            new BackgroundRemover();
        }
    });

})(jQuery);