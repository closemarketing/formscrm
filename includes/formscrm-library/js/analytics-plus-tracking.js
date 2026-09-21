// Captures Clientify's two tracking identifiers in the browser and writes them
// into the hidden fields FormsCRM auto-injects into every Clientify-connected
// form, right before submission. Both identifiers live client-side only:
// - `vk`: Clientify's legacy tracking cookie (set/refreshed by the pixel after
//   the page has already been served, so only the browser's own copy at
//   submit time is reliable).
// - Analytics PLUS visitor_uuid: written to localStorage by the pixel, never
//   sent to the server at all — PHP cannot read it under any circumstance.
( function () {
	'use strict';

	var pkEndpoint = 'https://analyticsplusdev.clientify.net/analytics_plus/apiclientify';

	/**
	 * Writes a value into every field matching the given selector.
	 *
	 * @param {string}      selector
	 * @param {string|null} value
	 */
	function fillFields( selector, value ) {
		if ( ! value ) {
			return;
		}
		document.querySelectorAll( selector ).forEach( function ( field ) {
			field.value = value;
		} );
	}

	/**
	 * Reads Clientify's legacy `vk` tracking cookie.
	 *
	 * @return {string|null}
	 */
	function readVkCookie() {
		var match = document.cookie.match( /(?:^|; )vk=([^;]*)/ );
		return match ? decodeURIComponent( match[ 1 ] ) : null;
	}

	/**
	 * Finds the visitor_uuid localStorage key without needing the pixel_key,
	 * since the key is always suffixed "_visitor_uuid" (never "_visitor_session_uuid").
	 *
	 * @return {string|null}
	 */
	function readVisitorUuidBySuffix() {
		try {
			for ( var i = 0; i < window.localStorage.length; i++ ) {
				var key = window.localStorage.key( i );
				if ( key && key.indexOf( '_visitor_uuid' ) === key.length - '_visitor_uuid'.length ) {
					return window.localStorage.getItem( key );
				}
			}
		} catch ( e ) {
			// localStorage unavailable (privacy mode, etc.) — no attribution, non-fatal.
		}
		return null;
	}

	/**
	 * Resolves the pixel_key from Analytics PLUS's public, CORS-enabled endpoint,
	 * for sites with more than one pixel where the "_visitor_uuid" suffix match
	 * would be ambiguous.
	 *
	 * @param {Function} onDone Callback receiving the visitor_uuid or null.
	 */
	function readVisitorUuidByPixelKey( onDone ) {
		fetch( pkEndpoint + '?request_type=get_pk_cached&domain=' + encodeURIComponent( window.location.origin ) )
			.then( function ( response ) {
				return response.text();
			} )
			.then( function ( pixelKey ) {
				if ( ! pixelKey || pixelKey.indexOf( 'Error' ) === 0 ) {
					onDone( null );
					return;
				}
				try {
					onDone( window.localStorage.getItem( '__' + pixelKey + '_visitor_uuid' ) );
				} catch ( e ) {
					onDone( null );
				}
			} )
			.catch( function () {
				onDone( null );
			} );
	}

	fillFields( '.formscrm-vk', readVkCookie() );

	var visitorUuid = readVisitorUuidBySuffix();
	if ( visitorUuid ) {
		fillFields( '.formscrm-vk2', visitorUuid );
	} else {
		readVisitorUuidByPixelKey( function ( uuid ) {
			fillFields( '.formscrm-vk2', uuid );
		} );
	}
} )();
