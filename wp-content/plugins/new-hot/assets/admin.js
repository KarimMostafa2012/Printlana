jQuery(function ($) {
  // Render a preview (image or video) into the preview card for this index
  function setIndexedPreview(index, attachment) {
    const $prev = $('.pl-nh-item[data-index="' + index + '"] .pl-nh-preview');
    if (!attachment) {
      $prev.html('<div class="pl-nh-placeholder">No media ' + index + '</div>');
      return;
    }

    const mime = attachment.mime || attachment.type || '';
    const url =
      (attachment.sizes && attachment.sizes.medium && attachment.sizes.medium.url) ||
      attachment.url;

    if (/^image\//.test(mime) || attachment.type === 'image') {
      $prev.html('<img src="' + url + '" alt="Preview ' + index + '">');
    } else if (/^video\//.test(mime) || attachment.type === 'video') {
      // Add autoplay + muted preview
      const videoHTML = `
        <video 
          src="${attachment.url}" 
          muted 
          playsinline 
          style="max-width:100%;height:auto;display:block;max-height:100%;" 
          autoplay
        ></video>`;
      $prev.html(videoHTML);

      // After inserting, add reversible looping behavior
      const video = $prev.find('video')[0];
      if (video) enableReversibleLoop(video);
    } else {
      $prev.html(
        '<div class="pl-nh-placeholder">Unsupported file type (' + mime + ')</div>'
      );
    }
  }

  // 🌀 Reversible loop logic
  function enableReversibleLoop(video) {
    let reverse = false;
    let reverseTimer = null;

    video.play();

    video.addEventListener('ended', () => {
      if (!reverse) {
        reverse = true;
        video.pause();

        // Simulate reverse playback by stepping backward
        reverseTimer = setInterval(() => {
          if (video.currentTime > 0.03) {
            video.currentTime -= 0.03; // 30 FPS reverse speed
          } else {
            clearInterval(reverseTimer);
            reverse = false;
            video.currentTime = 0;
            video.play(); // restart normal playback
          }
        }, 30);
      }
    });
  }

  // Get 1..4 index from the hidden input id: pl_newandhot_1,2,3,4
  function indexFromInput($input) {
    const m = ($input.attr('id') || '').match(/pl_newandhot_(\d+)/);
    return m ? parseInt(m[1], 10) : null;
  }

  // Open WP media frame for image or video
  function openMediaFrame(onSelect) {
    const frame = wp.media({
      title: 'Select or Upload Media',
      button: { text: 'Use this media' },
      library: { type: ['image', 'video'] },
      multiple: false
    });
    frame.on('select', function () {
      const attachment = frame.state().get('selection').first().toJSON();
      onSelect(attachment);
    });
    frame.open();
  }

  $('.pl-nh-upload').on('click', function (e) {
    e.preventDefault();
    const $input = $($(this).data('target'));
    const idx = indexFromInput($input);
    if (!idx) return;

    openMediaFrame(function (att) {
      $input.val(att.id);
      setIndexedPreview(idx, att);
    });
  });

  $('.pl-nh-remove').on('click', function (e) {
    e.preventDefault();
    const $input = $($(this).data('target'));
    const idx = indexFromInput($input);
    if (!idx) return;
    $input.val('');
    setIndexedPreview(idx, null);
  });
});
