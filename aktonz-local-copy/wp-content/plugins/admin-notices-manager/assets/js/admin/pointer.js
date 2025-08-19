jQuery(
	function() {
		var { __ } = wp.i18n;

		if ( ! anm_pointer_i18n.is_dismissed ) {
			jQuery('#' + anm_pointer_i18n.first_element_id ).pointer( 
				{
					content:
						"<h3>" + anm_pointer_i18n.content_title  + "<\/h4>" +
						"<p>" + anm_pointer_i18n.content_text + "</p>",


					position:
						{
							edge:  'top',
							align: 'center'
						},

					pointerClass:
						'wp-pointer anm-pointer',

					//pointerWidth: 20,
					
					close: function() {
						jQuery.post(
							ajaxurl,
							{
								pointer: anm_pointer_i18n.menu_name,
								action: 'dismiss-wp-pointer',
							}
						);

						second.pointer('open');
					},

				}
			).pointer('open');
		}

		var second = jQuery('#'+ anm_pointer_i18n.second_element_id ).pointer( 
			{
				content:
					"<h3>" + anm_pointer_i18n.second_content_title + "<\/h3>" +
					"<p>" + anm_pointer_i18n.second_content_title + "</p>",


				position:
					{
						edge:  'left',
						align: 'center'
					},

				// pointerClass:
				// 	'wp-pointer anm-pointer',

				//pointerWidth: 20,
				
				close: function() {
					jQuery.post(
						ajaxurl,
						{
							pointer: anm_pointer_i18n.settings_menu_name,
							action: 'dismiss-wp-pointer',
						}
					);
				},

			}
		);

		if ( anm_pointer_i18n.is_dismissed && ! anm_pointer_i18n.settings_is_dismissed ) {
			second.pointer('open');
		}
	}
);