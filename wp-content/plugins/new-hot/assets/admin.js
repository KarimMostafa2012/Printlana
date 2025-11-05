jQuery(function ($) {
  console.log("[PL-NewHot] admin.js initialized ✅");

  // Render inside the grid preview by index (1..4)
  function setIndexedPreview(index, attachment) {
    console.log("[setIndexedPreview]", { index, attachment });
    const $prev = $('.pl-nh-item[data-index="' + index + '"] .pl-nh-preview');
    if (!$prev.length)
      console.warn("[setIndexedPreview] No preview element for index:", index);

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
      console.log("[setIndexedPreview] Rendering image", url);
      $prev.html('<img src="' + url + '" alt="Preview ' + index + '">');
    } else if (/^video\//.test(mime) || attachment.type === "video") {
      console.log("[setIndexedPreview] Rendering video", attachment.url);
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
      console.warn("[setIndexedPreview] Unsupported type", mime);
      $prev.html(
        '<div class="pl-nh-placeholder">Unsupported file type (' +
          mime +
          ")</div>"
      );
    }
  }

  // Render inside the inline preview inside the controls of the same block
  function setInlinePreview($wrap, url) {
    console.log("[setInlinePreview]", { wrap: $wrap.attr("class"), url });
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

  // Get 1..4 index from the hidden input id: pl_newandhot_1,2,3,4
  function indexFromInput($input) {
    const m = ($input.attr("id") || "").match(/pl_newandhot_(\d+)/);
    const idx = m ? parseInt(m[1], 10) : null;
    console.log("[indexFromInput]", { id: $input.attr("id"), idx });
    return idx;
  }

  // Open WP media frame for image or video
  function openMediaFrame(onSelect) {
    console.log("[openMediaFrame] Opening media frame");
    const frame = wp.media({
      title: "Select or Upload Media",
      button: { text: "Use this media" },
      library: { type: ["image", "video"] },
      multiple: false,
    });
    frame.on("select", function () {
      const attachment = frame.state().get("selection").first().toJSON();
      console.log("[openMediaFrame] Selected attachment", attachment);
      onSelect(attachment);
    });
    frame.open();
  }

  // Upload (use event delegation in case blocks are re-rendered)
  $(document).on("click", ".pl-nh-upload", function (e) {
    e.preventDefault();
    console.log("[Event] .pl-nh-upload clicked", this);
    const $btn = $(this);
    const target = $btn.data("target");
    const $input = $(target);
    const $wrap = $btn.closest(".pl-nh-controls");
    const idx = indexFromInput($input);
    if (!idx) return console.warn("[Upload] Invalid index or input", target);

    openMediaFrame(function (att) {
      console.log("[Upload] Media selected", att);
      $input.val(att.id);
      const url =
        att.sizes && att.sizes.medium && att.sizes.medium.url
          ? att.sizes.medium.url
          : att.url;

      setInlinePreview($wrap, url);
      setIndexedPreview(idx, att);

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
      console.log("[Upload] Completed update for index", idx);
    });
  });

  // Remove
  $(document).on("click", ".pl-nh-remove", function (e) {
    e.preventDefault();
    console.log("[Event] .pl-nh-remove clicked", this);
    const $btn = $(this);
    const target = $btn.data("target");
    const $input = $(target);
    const $wrap = $btn.closest(".pl-nh-controls");
    const idx = indexFromInput($input);
    if (!idx) return console.warn("[Remove] Invalid index", target);

    $input.val("");
    setInlinePreview($wrap, null);
    setIndexedPreview(idx, null);

    $btn.hide();
    $wrap.find(".pl-nh-upload").show();
    console.log("[Remove] Cleared media for index", idx);
  });

  // Click inline preview
  $(document).on("click", ".pl-nh-inline-preview", function (e) {
    e.preventDefault();
    console.log("[Event] .pl-nh-inline-preview clicked", this);
    const $wrap = $(this).closest(".pl-nh-controls");
    const target = $(this).data("target");
    const $input = $(target);
    const idx = indexFromInput($input);
    if (!idx) return console.warn("[Inline Preview] Invalid index", target);

    openMediaFrame(function (att) {
      console.log("[Inline Preview] Media selected", att);
      $input.val(att.id);
      const url =
        att.sizes && att.sizes.medium && att.sizes.medium.url
          ? att.sizes.medium.url
          : att.url;

      setInlinePreview($wrap, url);
      setIndexedPreview(idx, att);

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
      console.log("[Inline Preview] Updated index", idx);
    });
  });

  // 🌀 Reversible loop logic
  function enableReversibleLoop(video) {
    console.log("[enableReversibleLoop] Start for", video.src);
    let reverse = false;
    let reverseTimer = null;
    video.play();
    video.addEventListener("ended", () => {
      if (!reverse) {
        console.log("[enableReversibleLoop] Reversing...");
        reverse = true;
        video.pause();
        reverseTimer = setInterval(() => {
          if (video.currentTime > 0.03) {
            video.currentTime -= 0.03;
          } else {
            clearInterval(reverseTimer);
            reverse = false;
            video.currentTime = 0;
            video.play();
            console.log("[enableReversibleLoop] Loop complete");
          }
        }, 30);
      }
    });
  }

  // Click on GRID preview
  $(document).on(
    "click",
    ".pl-nh-item .pl-nh-preview, .pl-nh-item img, .pl-nh-item .pl-nh-placeholder",
    function (e) {
      e.preventDefault();
      console.log("[Event] GRID preview clicked", this);
      const $item = $(this).closest(".pl-nh-item");
      const target = $item.data("target");
      const $input = $(target);
      const idx = indexFromInput($input);
      if (!idx) return console.warn("[Grid Preview] Invalid index", target);

      const $wrap = $("#pl_newandhot_" + idx).closest(".pl-nh-controls");
      console.log("[Grid Preview] Matching controls", $wrap);

      openMediaFrame(function (att) {
        console.log("[Grid Preview] Media selected", att);
        $input.val(att.id);
        const url =
          att.sizes && att.sizes.medium && att.sizes.medium.url
            ? att.sizes.medium.url
            : att.url;

        setInlinePreview($wrap, url);
        setIndexedPreview(idx, att);

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
        console.log("[Grid Preview] Updated index", idx);
      });
    }
  );

  console.log("[PL-NewHot] Event bindings completed ⚙️");
});
