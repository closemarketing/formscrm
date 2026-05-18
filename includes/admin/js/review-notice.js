/**
 * FormsCRM Review Notice
 *
 * Handles dismissal of the review admin notice.
 */
(function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var notice   = document.getElementById( 'formscrm-review-notice' );
		var noThanks = document.getElementById( 'formscrm-dismiss-review' );

		if ( ! notice || ! noThanks ) {
			return;
		}

		function sendDismiss() {
			var data = new FormData();
			data.append( 'action', 'formscrm_dismiss_review_notice' );
			data.append( 'nonce', formscrmReviewNotice.nonce );

			fetch( formscrmReviewNotice.ajaxurl, {
				method: 'POST',
				body:   data,
			} );
		}

		// "No thanks" button.
		noThanks.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			sendDismiss();
			notice.style.display = 'none';
		} );

		// WordPress built-in dismiss button (.notice-dismiss).
		notice.addEventListener( 'click', function ( e ) {
			if ( e.target && e.target.classList.contains( 'notice-dismiss' ) ) {
				sendDismiss();
			}
		} );
	} );
}());
