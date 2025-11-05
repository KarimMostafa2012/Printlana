jQuery(function ($) {
  // Render inside the grid preview by index (1..4)
  function setIndexedPreview(index, attachment) {
    const $prev = $('.pl-nh-item[data-index="' + index + '"] .pl-nh-preview');
    if (!attachment) {
      $prev.html('<div class="pl-nh-placeholder">No media ' + index + "</div>");
      return;
    }
    const mime = attachment.mime || attachment.type || "";
    const url =
      (attachment.sizes &&
        attachment.sizes.medium &&
        attachment.sizes.medium.url) ||
      attachment.url;

    if (/^image\//.test(mime) || attachment.type === "image") {
      $prev.html('<img src="' + url + '" alt="Preview ' + index + '">');
    } else if (/^video\//.test(mime) || attachment.type === "video") {
      const videoHTML = `
        <video 
          src="${attachment.url}" 
          muted 
          playsinline 
          style="max-width:100%;height:auto;display:block;max-height:100%;" 
          autoplay
        ></video>`;
      $prev.html(videoHTML);
      const video = $prev.find("video")[0];
      if (video) enableReversibleLoop(video);
    } else {
      $prev.html(
        '<div class="pl-nh-placeholder">Unsupported file type (' +
          mime +
          ")</div>"
      );
    }
  }

  // Render inside the inline preview inside the controls of the same block
  function setInlinePreview($wrap, url) {
    const $img = $wrap.find(".pl-nh-inline-preview img");
    if ($img.length) {
      $img.attr(
        "src",
        url ||
          "https://printlana.com/wp-content/uploads/2025/09/placeholder-3.png"
      );
    } else {
      $wrap
        .find(".pl-nh-inline-preview")
        .html(
          '<img src="' +
            (url ||
              "https://printlana.com/wp-content/uploads/2025/09/placeholder-3.png") +
            '" alt="Preview" />'
        );
    }
  }

  // 🌀 Reversible loop logic
  function enableReversibleLoop(video) {
    let reverse = false;
    let reverseTimer = null;
    video.play();
    video.addEventListener("ended", () => {
      if (!reverse) {
        reverse = true;
        video.pause();
        reverseTimer = setInterval(() => {
          if (video.currentTime > 0.03) {
            video.currentTime -= 0.03; // ~30 FPS reverse
          } else {
            clearInterval(reverseTimer);
            reverse = false;
            video.currentTime = 0;
            video.play();
          }
        }, 30);
      }
    });
  }

  // Get 1..4 index from the hidden input id: pl_newandhot_1,2,3,4
  function indexFromInput($input) {
    const m = ($input.attr("id") || "").match(/pl_newandhot_(\d+)/);
    return m ? parseInt(m[1], 10) : null;
  }

  // Open WP media frame for image or video
  function openMediaFrame(onSelect) {
    const frame = wp.media({
      title: "Select or Upload Media",
      button: { text: "Use this media" },
      library: { type: ["image", "video"] },
      multiple: false,
    });
    frame.on("select", function () {
      const attachment = frame.state().get("selection").first().toJSON();
      onSelect(attachment);
    });
    frame.open();
  }

  // Upload (use event delegation in case blocks are re-rendered)
  $(document).on("click", ".pl-nh-upload", function (e) {
    e.preventDefault();
    const $btn = $(this);
    const target = $btn.data("target"); // "#pl_newandhot_1"
    const $input = $(target);
    const $wrap = $btn.closest(".pl-nh-controls");
    const idx = indexFromInput($input);
    if (!idx) return;

    openMediaFrame(function (att) {
      // write chosen ID
      $input.val(att.id);

      // preview URL for inline card
      const url =
        att.sizes && att.sizes.medium && att.sizes.medium.url
          ? att.sizes.medium.url
          : att.url;

      // update BOTH previews
      setInlinePreview($wrap, url);
      setIndexedPreview(idx, att);

      // toggle buttons
      $wrap.find(".pl-nh-upload").hide();
      if ($wrap.find(".pl-nh-remove").length) {
        $wrap.find(".pl-nh-remove").show();
      } else {
        $(
          '<button type="button" class="button button-secondary pl-nh-remove" data-target="' +
            target +
            '">Remove</button>'
        ).insertAfter($btn);
      }
    });
  });

  // Remove
  $(document).on("click", ".pl-nh-remove", function (e) {
    e.preventDefault();
    const $btn = $(this);
    const target = $btn.data("target");
    const $input = $(target);
    const $wrap = $btn.closest(".pl-nh-controls");
    const idx = indexFromInput($input);
    if (!idx) return;

    $input.val(""); // clear id

    // revert BOTH previews
    setInlinePreview($wrap, null);
    setIndexedPreview(idx, null);

    // toggle buttons
    $btn.hide();
    $wrap.find(".pl-nh-upload").show();
  });
  $(document).on("click", ".pl-nh-inline-preview", function (e) {
    e.preventDefault();
    const $wrap = $(this).closest(".pl-nh-controls");
    const target = $(this).data("target"); // e.g. "#pl_newandhot_1"
    const $input = $(target);
    const idx = indexFromInput($input);
    if (!idx) return;

    openMediaFrame(function (att) {
      $input.val(att.id);
      const url =
        att.sizes && att.sizes.medium && att.sizes.medium.url
          ? att.sizes.medium.url
          : att.url;

      // update both previews
      setInlinePreview($wrap, url);
      setIndexedPreview(idx, att);

      // toggle buttons
      $wrap.find(".pl-nh-upload").hide();
      if ($wrap.find(".pl-nh-remove").length) {
        $wrap.find(".pl-nh-remove").show();
      } else {
        $(
          '<button type="button" class="button button-secondary pl-nh-remove" data-target="' +
            target +
            '">Remove</button>'
        ).insertAfter($wrap.find(".pl-nh-upload"));
      }
    });
  });

  // Click on GRID preview (image/placeholder) to upload
  $(document).on(
    "click",
    ".pl-nh-item .pl-nh-preview, .pl-nh-item img, .pl-nh-item .pl-nh-placeholder",
    function (e) {
      e.preventDefault();
      const $item = $(this).closest(".pl-nh-item");
      const target = $item.data("target"); // e.g. "#pl_newandhot_1"
      const $input = $(target);
      const idx = indexFromInput($input);
      if (!idx) return;

      // find the matching controls block for inline updates
      const $wrap = $("#pl_newandhot_" + idx).closest(".pl-nh-controls");

      openMediaFrame(function (att) {
        $input.val(att.id);
        const url =
          att.sizes && att.sizes.medium && att.sizes.medium.url
            ? att.sizes.medium.url
            : att.url;

        // update both previews
        setInlinePreview($wrap, url);
        setIndexedPreview(idx, att);

        // toggle buttons
        $wrap.find(".pl-nh-upload").hide();
        if ($wrap.find(".pl-nh-remove").length) {
          $wrap.find(".pl-nh-remove").show();
        } else {
          $(
            '<button type="button" class="button button-secondary pl-nh-remove" data-target="' +
              target +
              '">Remove</button>'
          ).insertAfter($wrap.find(".pl-nh-upload"));
        }
      });
    }
  );
});
