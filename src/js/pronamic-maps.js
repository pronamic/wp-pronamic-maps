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
