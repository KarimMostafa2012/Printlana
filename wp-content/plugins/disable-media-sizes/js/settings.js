/* Plugin Settings */

jQuery(document).ready(function($) {
	
	// popup dialog
	
	$('.disable-media-sizes-reset-options').on('click', function(e) {
		e.preventDefault();
		$('.disable-media-sizes-modal-dialog').dialog('destroy');
		var link = this;
		var button_names = {}
		button_names[disable_media_sizes_reset_true]  = function() { window.location = link.href; }
		button_names[disable_media_sizes_reset_false] = function() { $(this).dialog('close'); }
		$('<div class="disable-media-sizes-modal-dialog">'+ disable_media_sizes_reset_message +'</div>').dialog({
			title: disable_media_sizes_reset_title,
			buttons: button_names,
			modal: true,
			width: 350,
			closeText: ''
		});
	});
	
});