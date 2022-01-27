var gravityforms = document.querySelectorAll( '.gform_wrapper' );

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
	} );
}

gravityforms.forEach( ( gravityform ) => {
	gravityform.addEventListener( 'change', function( event ) {
		pronamicMapsAutocomplete( gravityform, event.target );
	} );

	pronamicMapsAutocomplete( gravityform );	
} );
