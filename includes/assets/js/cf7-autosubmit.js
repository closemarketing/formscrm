/**
 * Contact Form 7 Auto-Submit Script
 *
 * @package FormsCRM
 * @version 4.2.2
 */

(function() {
	'use strict';

	// Wait for DOM to be ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initAutoSubmit );
	} else {
		initAutoSubmit();
	}

	/**
	 * Initialize auto-submit functionality
	 *
	 * @return void
	 */
	function initAutoSubmit() {
		// Get all select elements with auto-submit attribute.
		var autoSubmitSelects = document.querySelectorAll( 'select[data-formscrm-autosubmit="true"]' );

		if ( ! autoSubmitSelects.length ) {
			return;
		}

		// Add change event listener to each select.
		autoSubmitSelects.forEach( function( select ) {
			// Store the server-rendered value so browser form restoration doesn't trigger a save.
			var serverValue = select.value;

			select.addEventListener( 'change', function( event ) {
				// Ignore if the value hasn't actually changed from what the server rendered.
				if ( event.target.value === serverValue ) {
					return;
				}
				handleAutoSubmit( event.target );
			});
		});
	}

	/**
	 * Handle auto-submit when select changes
	 *
	 * @param {HTMLElement} selectElement The select element that changed
	 * @return void
	 */
	function handleAutoSubmit( selectElement ) {
		// Prevent multiple submissions.
		if ( selectElement.dataset.submitting === 'true' ) {
			return;
		}

		// Find the closest form.
		var form = selectElement.closest( 'form' );

		if ( ! form ) {
			console.warn( 'FormsCRM: Could not find parent form for auto-submit' );
			return;
		}

		// Mark as submitting to prevent duplicate submissions.
		selectElement.dataset.submitting = 'true';

		// Show saving indicator.
		var savingIndicator = selectElement.parentElement.querySelector( '.formscrm-saving-indicator' );
		if ( savingIndicator ) {
			savingIndicator.classList.add( 'is-active' );
		}

		// Add visual feedback class.
		selectElement.classList.add( 'formscrm-submitting' );

		// Add a small delay to ensure user sees the feedback.
		setTimeout( function() {
			// Click the CF7 save button so CF7 clears its own beforeunload listener.
			var saveButton = form.querySelector( '[name="wpcf7-save"]' );
			if ( saveButton ) {
				saveButton.click();
			} else {
				form.submit();
			}
		}, 300 );
	}
})();
