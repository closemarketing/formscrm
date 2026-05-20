( function () {
	'use strict';

	function initModuleSearch( select ) {
		if ( select.dataset.moduleSearchInit ) {
			return;
		}
		select.dataset.moduleSearchInit = '1';

		var wrapper = document.createElement( 'div' );
		wrapper.className = 'gfcrm-module-search-wrapper';

		var input = document.createElement( 'input' );
		input.type = 'text';
		input.className = 'gfcrm-module-search-input';
		input.placeholder = select.options[ select.selectedIndex ]
			? select.options[ select.selectedIndex ].text
			: '';
		input.setAttribute( 'autocomplete', 'off' );

		var dropdown = document.createElement( 'div' );
		dropdown.className = 'gfcrm-module-search-dropdown';
		dropdown.style.display = 'none';

		select.parentNode.insertBefore( wrapper, select );
		wrapper.appendChild( input );
		wrapper.appendChild( dropdown );
		wrapper.appendChild( select );
		select.style.display = 'none';

		function buildOptions( filter ) {
			dropdown.innerHTML = '';
			var options = select.options;
			var count = 0;
			for ( var i = 0; i < options.length; i++ ) {
				var opt = options[ i ];
				if ( filter && opt.text.toLowerCase().indexOf( filter.toLowerCase() ) === -1 ) {
					continue;
				}
				( function ( option ) {
					var item = document.createElement( 'div' );
					item.className = 'gfcrm-module-search-item';
					if ( option.value === select.value ) {
						item.className += ' selected';
					}
					item.textContent = option.text;
					item.addEventListener( 'mousedown', function ( e ) {
						e.preventDefault();
						select.value = option.value;
						input.value = option.text;
						dropdown.style.display = 'none';
						// Trigger GF form submit to reload field map.
						var form = select.closest( 'form' );
						if ( form ) {
							form.submit();
						}
					} );
					dropdown.appendChild( item );
					count++;
				} )( opt );
			}
			if ( 0 === count ) {
				var noResults = document.createElement( 'div' );
				noResults.className = 'gfcrm-module-search-no-results';
				noResults.textContent = 'No results found.';
				dropdown.appendChild( noResults );
			}
		}

		input.addEventListener( 'focus', function () {
			buildOptions( '' );
			dropdown.style.display = 'block';
		} );

		input.addEventListener( 'input', function () {
			buildOptions( input.value );
			dropdown.style.display = 'block';
		} );

		input.addEventListener( 'blur', function () {
			setTimeout( function () {
				dropdown.style.display = 'none';
			}, 150 );
		} );

		// Set initial display value.
		if ( select.selectedIndex >= 0 ) {
			input.value = select.options[ select.selectedIndex ].text;
		}
	}

	function init() {
		var selects = document.querySelectorAll( 'select.gfcrm-module-search-select' );
		for ( var i = 0; i < selects.length; i++ ) {
			initModuleSearch( selects[ i ] );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
