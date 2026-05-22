( function () {
	var UTM_PARAMS = [ 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content' ];
	var COOKIE_DAYS = 30;

	function setCookie( name, value, days ) {
		var expires = '';
		if ( days ) {
			var date = new Date();
			date.setTime( date.getTime() + days * 24 * 60 * 60 * 1000 );
			expires = '; expires=' + date.toUTCString();
		}
		document.cookie = name + '=' + encodeURIComponent( value ) + expires + '; path=/; SameSite=Lax';
	}

	function getCookie( name ) {
		var match = document.cookie.match( new RegExp( '(?:^|; )' + name + '=([^;]*)' ) );
		return match ? decodeURIComponent( match[1] ) : null;
	}

	function captureUtms() {
		var params = new URLSearchParams( window.location.search );
		var found = false;

		UTM_PARAMS.forEach( function ( param ) {
			if ( params.has( param ) ) {
				var value = params.get( param );

				// Always update last touch.
				setCookie( 'fcrm_' + param, value, COOKIE_DAYS );

				// Only set first touch if not already stored.
				if ( ! getCookie( 'fcrm_first_' + param ) ) {
					setCookie( 'fcrm_first_' + param, value, COOKIE_DAYS );
				}

				found = true;
			}
		} );

		return found;
	}

	captureUtms();
}() );
