// Reads the Analytics PLUS pixel's persistent visitor_uuid from localStorage and
// writes it into the form field(s) mapped to Clientify's visitor_key2, right
// before submission. FormsCRM runs server-side and cannot read localStorage
// itself, so this capture step has to happen in the browser.
( function () {
	'use strict';

	if ( typeof window.formscrmAnalyticsPlus === 'undefined' || ! Array.isArray( window.formscrmAnalyticsPlus.selectors ) || ! window.formscrmAnalyticsPlus.selectors.length ) {
		return;
	}

	var selectors  = window.formscrmAnalyticsPlus.selectors;
	var pkEndpoint = window.formscrmAnalyticsPlus.pkEndpoint || '';

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
		var domain = window.location.origin;
		fetch( pkEndpoint + '?request_type=get_pk_cached&domain=' + encodeURIComponent( domain ) )
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

	/**
	 * Writes the resolved visitor_uuid into every field matching the mapped selectors.
	 *
	 * @param {string|null} visitorUuid
	 */
	function fillFields( visitorUuid ) {
		if ( ! visitorUuid ) {
			return;
		}
		selectors.forEach( function ( selector ) {
			document.querySelectorAll( selector ).forEach( function ( field ) {
				field.value = visitorUuid;
			} );
		} );
	}

	var visitorUuid = readVisitorUuidBySuffix();
	if ( visitorUuid ) {
		fillFields( visitorUuid );
	} else if ( pkEndpoint ) {
		readVisitorUuidByPixelKey( fillFields );
	}
} )();
