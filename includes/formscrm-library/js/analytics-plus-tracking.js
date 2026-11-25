// Captures Clientify's visitor tracking identifier in the browser and writes
// it into the hidden field FormsCRM auto-injects into every Clientify-
// connected form, right before submission. Both possible sources live
// client-side only:
// - Analytics PLUS visitor_uuid (preferred): written to localStorage by the
//   pixel, never sent to the server at all — PHP cannot read it under any
//   circumstance.
// - `vk`: Clientify's legacy tracking cookie (set/refreshed by the pixel
//   after the page has already been served, so only the browser's own copy
//   at submit time is reliable), used as a fallback when there's no
//   Analytics PLUS visitor_uuid.
//
// Both are sent to Clientify as the same field, `visitor_key` — Clientify's
// API doesn't process `visitor_key2` in production yet, so Analytics PLUS
// attribution rides on `visitor_key` too, same as the legacy cookie.
( function () {
	'use strict';

	var pkEndpoint = 'https://analyticsplusdev.clientify.net/analytics_plus/apiclientify';

	/**
	 * Writes a value into the hidden input carrying the given class — whether
	 * the class is on the input itself (Contact Form 7, Elementor) or on a
	 * wrapping container around it (Gravity Forms renders the class on the
	 * field's wrapping <div>, not the <input>).
	 *
	 * @param {string}      className Class name, without the leading dot.
	 * @param {string|null} value
	 */
	function fillFields( className, value ) {
		if ( ! value ) {
			return;
		}
		document.querySelectorAll( '.' + className ).forEach( function ( field ) {
			var input = 'INPUT' === field.tagName ? field : field.querySelector( 'input' );
			if ( input ) {
				input.value = value;
			}
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

	var visitorUuid = readVisitorUuidBySuffix();
	if ( visitorUuid ) {
		fillFields( 'formscrm-vk', visitorUuid );
	} else {
		readVisitorUuidByPixelKey( function ( uuid ) {
			fillFields( 'formscrm-vk', uuid || readVkCookie() );
		} );
	}
} )();
