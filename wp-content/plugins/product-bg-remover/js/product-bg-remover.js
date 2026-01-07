// product-bg-remover.js
(function ($) {
  "use strict";

  let removeBackgroundFunc = null;
  let isLibraryLoaded = false;
  let processingQueue = new Set();

  /**
   * Wait for the UMD library to be available
   */
  function waitForLibrary() {
    return new Promise((resolve) => {
      let attempts = 0;
      const maxAttempts = 50; // 5 seconds

      const checkLibrary = setInterval(() => {
        attempts++;

        // Check if the global removeBackground function exists
        if (typeof window.removeBackground !== "undefined") {
          clearInterval(checkLibrary);
          removeBackgroundFunc = window.removeBackground;
          isLibraryLoaded = true;
          console.log("Background removal library loaded successfully");
          resolve(true);
        } else if (attempts >= maxAttempts) {
          clearInterval(checkLibrary);
          console.error("Background removal library failed to load");
          resolve(false);
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
      const hashBuffer = await crypto.subtle.digest("SHA-256", arrayBuffer);
      const hashArray = Array.from(new Uint8Array(hashBuffer));
      return hashArray.map((b) => b.toString(16).padStart(2, "0")).join("");
    } catch (error) {
      console.error("Hash calculation failed:", error);
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
        type: "POST",
        data: {
          action: "check_processed_image",
          nonce: bgRemoverData.nonce,
          file_hash: fileHash,
        },
        success: function (response) {
          if (response.success) {
            resolve(response.data);
          } else {
            resolve({ exists: false });
          }
        },
        error: function () {
          resolve({ exists: false });
        },
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
        type: "POST",
        data: {
          action: "mark_image_processed",
          nonce: bgRemoverData.nonce,
          attachment_id: attachmentId,
          file_hash: fileHash,
        },
        success: function (response) {
          resolve(response);
        },
        error: function () {
          resolve(false);
        },
      });
    });
  }

  /**
   * Process image - remove background
   */
  async function processImage(file, fileHash) {
    // Check if library is loaded
    if (!isLibraryLoaded) {
      showError("Background removal library not loaded yet");
      return null;
    }

    // Check if already in processing queue
    if (processingQueue.has(fileHash)) {
      console.log("Image already being processed:", file.name);
      return null;
    }

    processingQueue.add(fileHash);

    try {
      console.log("Removing background from:", file.name);

      // Show loading indicator
      showProcessingIndicator(file.name);

      // Remove background using the global removeBackground function
      const blob = await removeBackgroundFunc(file, {
        output: {
          format: "png",
          quality: 0.8,
        },
      });

      // Create new file with -no-bg suffix
      const originalName = file.name.replace(/\.[^/.]+$/, "");
      const newFileName = originalName + "-no-bg.png";
      const processedFile = new File([blob], newFileName, {
        type: "image/png",
      });

      hideProcessingIndicator();

      return processedFile;
    } catch (error) {
      console.error("Background removal failed:", error);
      hideProcessingIndicator();
      showError(
        "Failed to remove background. Image uploaded without processing."
      );
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
    formData.append("async-upload", file);
    formData.append("name", file.name);
    formData.append("action", "upload-attachment");
    formData.append("post_id", postId);

    // Get nonce from various possible locations
    const wpNonce =
      $("#_wpnonce").val() ||
      $('input[name="_wpnonce"]').val() ||
      $("#_ajax_nonce").val();

    if (wpNonce) {
      formData.append("_wpnonce", wpNonce);
    }

    try {
      const response = await fetch(ajaxurl, {
        method: "POST",
        body: formData,
        credentials: "same-origin",
      });

      const html = await response.text();

      // Parse response to get attachment ID
      const idMatch = html.match(/id="media-item-(\d+)"/);

      if (idMatch) {
        return parseInt(idMatch[1]);
      }

      // Try alternative pattern
      const altMatch = html.match(/"id":(\d+)/);
      if (altMatch) {
        return parseInt(altMatch[1]);
      }

      console.log("Could not extract attachment ID from response");
      return null;
    } catch (error) {
      console.error("Upload failed:", error);
      return null;
    }
  }

  /**
   * Handle new image upload to product gallery
   */
  async function handleImageUpload(file, postId) {
    // Only process images
    if (!file.type || !file.type.startsWith("image/")) {
      console.log("Not an image file, skipping:", file.name);
      return;
    }

    try {
      // Calculate hash
      const fileHash = await calculateFileHash(file);

      // Check if already processed
      const existingCheck = await checkIfAlreadyProcessed(fileHash);

      if (existingCheck.exists) {
        console.log("Image already processed, notifying user");
        showNotification(
          "This image was already processed. Using existing version."
        );
        return;
      }

      // Process new image
      const processedFile = await processImage(file, fileHash);

      if (!processedFile) {
        // If processing failed, continue with original upload
        console.log("Processing failed, continuing with original file");
        return;
      }

      // Upload processed file
      const attachmentId = await uploadToWordPress(processedFile, postId);

      if (attachmentId) {
        // Mark as processed
        await markAsProcessed(attachmentId, fileHash);
        showNotification("Background removed successfully!");
      }
    } catch (error) {
      console.error("Error in handleImageUpload:", error);
    }
  }

  /**
   * Hook into WordPress/WooCommerce uploader
   */
  function initializeUploadHook() {
    // Wait for WordPress media library to be ready
    if (typeof wp === "undefined" || !wp.media) {
      console.log("WordPress media not ready, retrying...");
      setTimeout(initializeUploadHook, 500);
      return;
    }

    console.log("Initializing background remover upload hook");

    // Get current post ID
    const postId = $("#post_ID").val() || 0;

    // Hook into the WordPress media uploader
    const originalMediaFrame = wp.media.view.MediaFrame.Post;

    wp.media.view.MediaFrame.Post = originalMediaFrame.extend({
      initialize: function () {
        originalMediaFrame.prototype.initialize.apply(this, arguments);

        const self = this;

        this.on("uploader:ready", function () {
          if (self.uploader && self.uploader.uploader) {
            self.uploader.uploader.bind("FilesAdded", function (up, files) {
              console.log("Files added to uploader:", files.length);

              files.forEach(function (file) {
                const nativeFile = file.getNative ? file.getNative() : file;

                // Process in background
                handleImageUpload(nativeFile, postId).catch(function (error) {
                  console.error("Background removal error:", error);
                });
              });
            });
          }
        });
      },
    });

    console.log("Upload hook initialized successfully");
  }

  /**
   * UI Helper functions
   */
  function showProcessingIndicator(fileName) {
    // Remove any existing indicators
    $(".bg-remover-processing").remove();

    const $indicator = $('<div class="bg-remover-processing">').html(
      "<strong>Processing:</strong> Removing background from " +
        fileName +
        "..."
    );

    $("body").append($indicator);
  }

  function hideProcessingIndicator() {
    $(".bg-remover-processing").fadeOut(300, function () {
      $(this).remove();
    });
  }

  function showNotification(message) {
    const $notif = $('<div class="bg-remover-notification">').html(
      "<strong>✓</strong> " + message
    );

    $("body").append($notif);

    setTimeout(function () {
      $notif.fadeOut(300, function () {
        $(this).remove();
      });
    }, 4000);
  }

  function showError(message) {
    const $error = $('<div class="bg-remover-error">').html(
      "<strong>⚠</strong> " + message
    );

    $("body").append($error);

    setTimeout(function () {
      $error.fadeOut(300, function () {
        $(this).remove();
      });
    }, 6000);
  }

  /**
   * Initialize when document is ready
   */
  $(document).ready(function () {
    console.log("Product BG Remover: Document ready");

    // Wait for library to load
    waitForLibrary().then(function (loaded) {
      if (loaded) {
        console.log("Library loaded, initializing upload hooks");
        initializeUploadHook();
      } else {
        console.error("Failed to load background removal library");
        showError("Background removal feature unavailable");
      }
    });
  });
})(jQuery);
