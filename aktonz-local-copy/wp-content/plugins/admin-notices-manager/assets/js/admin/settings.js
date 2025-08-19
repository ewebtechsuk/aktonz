(function ($, window) {

	window.anm_settings = window.anm_settings || {};

	window.anm_settings.select_appropriate_radio = function (e) {
		if (0 === $(e.target).val().length) {
			$(e.target).closest('fieldset').find('input[type="radio"]').first().prop('checked', true);
		} else {
			$(e.target).prevAll('label').first().find('input[type="radio"]').prop('checked', true);
		}
	};

	window.anm_settings.append_select2_events = function (select2obj) {
		select2obj.on('select2:select', window.anm_settings.select_appropriate_radio)
			.on('select2:unselect', window.anm_settings.select_appropriate_radio)
			.on('change', window.anm_settings.select_appropriate_radio);
	};

	jQuery(document).on('click', '#anm-purge-btn', function (e) {
		e.preventDefault();
		var nonce = jQuery(this).attr('data-nonce');

		jQuery.ajax({
			type: 'POST',
			dataType: 'json',
			url: anm_settings.ajaxurl,
			data: {
				action: 'anm_purge_notices',
				nonce: nonce
			},
			complete: function (data) {
				$('#anm-notice-purged-text').not('.visible').addClass('visible');
				setTimeout(function () {
					$('#anm-notice-purged-text.visible').removeClass('visible');
				}, 2000);
			}
		})
	});

}(jQuery, window));
