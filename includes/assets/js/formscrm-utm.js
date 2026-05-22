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

	function captureUtms() {
		var params = new URLSearchParams( window.location.search );
		var found = false;

		UTM_PARAMS.forEach( function ( param ) {
			if ( params.has( param ) ) {
				setCookie( 'fcrm_' + param, params.get( param ), COOKIE_DAYS );
				found = true;
			}
		} );

		return found;
	}

	captureUtms();
}() );
