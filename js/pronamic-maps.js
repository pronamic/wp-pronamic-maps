/**
 * Pronamic Maps autocomplete.
 * 
 * @link https://developer.mozilla.org/en-US/docs/Web/CSS/:autofill
 * @link https://developer.mozilla.org/en-US/docs/Web/API/Element/classList
 * @link https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch
 */

const map = {
	'postal-code': 'postcode',
	'address-level2': 'level_2',
	'address-line1': 'street_name',
	'street-address': 'street_name',
	'country-name': 'country_name',
	'address-level1': 'level_1',
}

function pronamicMapsAutocomplete( element, target ) {
	var address = {};

	for ( const value in map ) {
		var property = map[ value ];

		var input = element.querySelector( '[autocomplete="' + value + '"]' );	

		if ( null !== input && ! input.readOnly ) {
			address[ property ] = input.value;
		}

		var inputs = element.querySelectorAll( '[autocomplete="' + value + '"]' );

		inputs.forEach( ( input ) => {
			if ( input === target ) {
				return;
			}

			if ( '' !== input.value && ! input.readOnly ) {
				return;
			}

			input.dispatchEvent( new Event( 'autocompleting.pronamic-maps' ) );

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
		for ( const value in map ) {
			var property = map[ value ];

			var inputs = element.querySelectorAll( '[autocomplete="' + value + '"]' );

			inputs.forEach( ( input ) => {
				input.classList.remove( 'pronamic-maps-autocompleting' );

				if ( input === target ) {
					return;
				}

				if ( '' !== input.value && ! input.readOnly ) {
					return;
				}

				input.value = data.address[ property ];

				input.dispatchEvent( new Event( 'autocompleted.pronamic-maps' ) );

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
		for ( const value in map ) {
			var inputs = element.querySelectorAll( '[autocomplete="' + value + '"]' );

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
	if ( ! Object.hasOwn( map, event.target.getAttribute( 'autocomplete' ) ) ) {
		return;
	}

	pronamicMapsAutocomplete( this, event.target );
} );

jQuery( '.gform_wrapper' ).each( function() {
	pronamicMapsAutocomplete( this );
} );
