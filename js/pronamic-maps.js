/**
 * Pronamic Maps autocomplete.
 * 
 * @link https://developer.mozilla.org/en-US/docs/Web/CSS/:autofill
 * @link https://developer.mozilla.org/en-US/docs/Web/API/Element/classList
 * @link https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch
 */
function pronamicMapsAutocomplete( element, target ) {
	const map = {
		'postcode': 'postal-code',
		'city': 'address-level2',
		'street_name': 'address-line1',
		'country_name': 'country-name',
		'level_1': 'address-level1',
	}

	var address = {};

	for ( const property in map ) {
		var input = element.querySelector( '[autocomplete="' + map[ property ] + '"]' );	

		if ( null !== input ) {
			address[ property ] = input.value;
		}

		var inputs = element.querySelectorAll( '[autocomplete="' + map[ property ] + '"]' );

		inputs.forEach( ( input ) => {
			if ( input === target ) {
				return;
			}

			if ( '' !== input.value ) {
				return;
			}

			input.classList.add( 'pronamic-maps-autocompleting' );
		} );
	}

	fetch(
		pronamic_maps.rest_url_address_autocomplete,
		{
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify( address ),
		}
	)
	.then( response => response.json() )
	.then( data => {
		for ( const property in map ) {
			var inputs = element.querySelectorAll( '[autocomplete="' + map[ property ] + '"]' );

			inputs.forEach( ( input ) => {
				input.classList.remove( 'pronamic-maps-autocompleting' );

				if ( input === target ) {
					return;
				}

				if ( '' !== input.value ) {
					return;
				}

				input.value = data.address[ property ];

				/**
				 * Dispatch event.
				 * 
				 * @link https://github.com/pronamic/wp-pronamic-maps/issues/6
				 * @link https://html.spec.whatwg.org/multipage/indices.html#event-input
				 * @link https://html.spec.whatwg.org/multipage/indices.html#event-change
				 * @link https://stackoverflow.com/questions/16250464/trigger-change-event-when-the-input-value-changed-programmatically
				 */
				input.dispatchEvent( new Event( 'input' ) );
				input.dispatchEvent( new Event( 'change' ) );
			} );
		}
	} )
	.catch( ( error ) => {
		for ( const property in map ) {
			var inputs = element.querySelectorAll( '[autocomplete="' + map[ property ] + '"]' );

			inputs.forEach( ( input ) => {
				input.classList.remove( 'pronamic-maps-autocompleting' );
			} );
		}
	} );
}

/**
 * Gravity Forms still uses jQuery quite intensively, for example for the `jquery.maskedinput.js` library.
 * The `jquery.maskedinput.js` library also uses the deprecated `jQuery.fn.change()` function.
 * 
 * @link https://github.com/RubtsovAV/jquery.maskedinput/blob/1.4.1/src/jquery.maskedinput.js#L228
 * @link https://stackoverflow.com/questions/25256173/can-i-use-jquery-trigger-with-event-listeners-added-with-addeventlistener
 * @link https://github.com/jquery/jquery/issues/2476
 */
jQuery( '.gform_wrapper' ).on( 'change', function( event ) {
	pronamicMapsAutocomplete( this, event.target );
} );

jQuery( '.gform_wrapper' ).each( function() {
	pronamicMapsAutocomplete( this );
} );
