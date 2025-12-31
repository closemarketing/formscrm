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
			select.addEventListener( 'change', function( event ) {
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
			savingIndicator.style.display = 'inline-flex';
		}

		// Add visual feedback class.
		selectElement.classList.add( 'formscrm-submitting' );

		// Disable browser "unsaved changes" warning.
		disableUnsavedChangesWarning();

		// Add a small delay to ensure user sees the feedback.
		setTimeout( function() {
			// Submit the form.
			form.submit();
		}, 300 );
	}

	/**
	 * Disable "unsaved changes" warning
	 *
	 * Removes beforeunload event listeners to prevent the browser from
	 * showing a confirmation dialog when the form is auto-submitted.
	 *
	 * @return void
	 */
	function disableUnsavedChangesWarning() {
		// Clear the main beforeunload handler.
		window.onbeforeunload = null;

		// Remove jQuery beforeunload handlers (WordPress often uses jQuery).
		if ( typeof jQuery !== 'undefined' ) {
			jQuery( window ).off( 'beforeunload' );
		}
	}
})();

