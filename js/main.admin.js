(function(window, $) {

	$(function() {

		$('.acfr-reset-link').on('click', function(e) {
			e.preventDefault();

			// Always ask the user first !!!
			if ( !confirm('Are you sure you want to reset the custom fields to default values?') ) {
				return;
			}

			jQuery.post(
				acfr._ajax_url, {
				  'action': 'acfr_reset_options',
				  'nonce': acfr._nonce,
				  'fields': build_fields(),
				  'screen': $('[name="acfr-screen-id"]').val(),
				  'post': $('[name="acfr-post-id"]').val()
				},
				function (data) {
					// Just reload
				  	window.location.reload();
				}
			);
		});

		function build_fields() {

			fields = [];

			$('.acf-field').each(function() {

				$parent 	= $(this).parent().closest('.acf-field');
				$children 	= $(this).find('.acf-field');
				type 		= $(this).data('type');
				fname 		= $(this).data('name');

				/**
				 * Ignore tab|accordion & message fields as they don't hold any value
				 */
				if ( type == 'tab' || type == 'accordion' || type == 'message' ) {
					return;
				}

				if ( $parent.length == 0 ) {
					fields.push(fname);
				}

				$temp = $(this);

				/**
				 *
				 * Handle nested fields like:
				 *
				 * repeater
				 * flexible content
				 * group
				 *
				 */
				$(this).parents('.acf-field').each(function() {

					ptype = $(this).data('type');
					pname = $(this).data('name');

					if ( ptype == 'group' ) {

						fname = `${pname}_${fname}`;

					} else if ( ptype == 'repeater' ) {

						row = $temp.parent().closest('.acf-row').index();
						fname = `${pname}_${row}_${fname}`;

					} else if ( ptype == 'flexible_content' ) {

						row = $temp.parent().closest('.layout').index();
						fname = `${pname}_${row}_${fname}`;

					}

					$temp = $(this);

				});

				fields.push(fname);

			});

			return fields;

		}

	});

})(window, jQuery);
