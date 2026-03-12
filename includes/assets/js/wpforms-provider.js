( function () {
	'use strict';

	/**
	 * Returns the nearest container for provider account fields.
	 *
	 * @param {HTMLSelectElement} selectElement CRM select element.
	 * @returns {Element|Document}
	 */
	function getProviderContainer( selectElement ) {
		if ( ! selectElement || 'function' !== typeof selectElement.closest ) {
			return document;
		}

		return (
			selectElement.closest( '.wpforms-provider-account-add' ) ||
			selectElement.closest( '.wpforms-provider-connections' ) ||
			document
		);
	}

	/**
	 * Toggles credential fields depending on selected CRM type.
	 *
	 * @param {HTMLSelectElement} selectElement CRM select element.
	 */
	function toggleCredentialFields( selectElement ) {
		if ( ! selectElement || ! window.formscrmWpformsProvider ) {
			return;
		}

		const dependencies = window.formscrmWpformsProvider.dependencies || {};
		const crmType      = selectElement.value || '';
		const container    = getProviderContainer( selectElement );

		Object.keys( dependencies ).forEach( function ( selector ) {
			const field = container.querySelector( selector );
			if ( ! field ) {
				return;
			}

			const crmDependencies = Array.isArray( dependencies[ selector ] ) ? dependencies[ selector ] : [];
			const shouldShow      = -1 !== crmDependencies.indexOf( crmType );

			field.style.display = shouldShow ? '' : 'none';
		} );
	}

	/**
	 * Initialize CRM select dependencies.
	 */
	function initProviderSelectDependencies() {
		const selects = document.querySelectorAll( 'select[name="fc_crm_type"]' );

		selects.forEach( function ( selectElement ) {
			toggleCredentialFields( selectElement );
		} );
	}

	document.addEventListener( 'change', function ( event ) {
		if ( event.target && event.target.matches( 'select[name="fc_crm_type"]' ) ) {
			toggleCredentialFields( event.target );
		}
	} );

	document.addEventListener( 'DOMContentLoaded', initProviderSelectDependencies );
	document.addEventListener( 'wpformsReady', initProviderSelectDependencies );

	if ( 'function' === typeof MutationObserver ) {
		const observer = new MutationObserver( function () {
			initProviderSelectDependencies();
		} );

		observer.observe(
			document.body,
			{
				childList: true,
				subtree: true,
			}
		);
	}
}() );
