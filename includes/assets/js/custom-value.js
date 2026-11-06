/**
 * Shows the custom value input when "Custom value" is chosen in a FormsCRM
 * field mapping select. Uses event delegation so it also works for selects
 * rendered later via AJAX.
 */
( function () {
	'use strict';

	document.addEventListener( 'change', function ( event ) {
		var select = event.target;

		if ( ! select || ! select.matches || ! select.matches( 'select[data-formscrm-custom]' ) ) {
			return;
		}

		var input = select.parentNode ? select.parentNode.querySelector( '.formscrm-custom-value' ) : null;

		if ( ! input ) {
			return;
		}

		var isCustom = 'formscrm_custom' === select.value;

		input.style.display = isCustom ? '' : 'none';

		if ( isCustom ) {
			input.focus();
		}
	} );
}() );
