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
		var match = document.cookie.match( new RegExp( '(?:^|; )' + name.replace( /([.$?*|{}()[\]\\/+^])/g, '\\$1' ) + '=([^;]*)' ) );
		return match ? decodeURIComponent( match[ 1 ] ) : '';
	}

	function captureUtms() {
		var params = new URLSearchParams( window.location.search );

		UTM_PARAMS.forEach( function ( param ) {
			if ( params.has( param ) ) {
				var value = params.get( param );
				setCookie( 'fcrm_' + param, value, COOKIE_DAYS );
				if ( ! getCookie( 'fcrm_first_' + param ) ) {
					setCookie( 'fcrm_first_' + param, value, COOKIE_DAYS );
				}
			}
		} );
	}

	captureUtms();
}() );
