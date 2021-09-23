var gravityforms = document.querySelectorAll( '.gform_wrapper' );

function pronamicMapsAutocomplete( element ) {
	var input_postcode = element.querySelector( '[autocomplete="postal-code"]' );

	var address = {
		'postcode': ( null === input_postcode ) ? null : input_postcode.value
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
		var inputs = element.querySelectorAll( '[autocomplete="address-level2"]' );

		inputs.forEach( ( input ) => {
			if ( '' === input.value ) {
				input.value = data.address.city;
			}
		} );
	} );
}

gravityforms.forEach( ( gravityform ) => {
	gravityform.addEventListener( 'change', function( event ) {
		pronamicMapsAutocomplete( gravityform );
	} );

	pronamicMapsAutocomplete( gravityform );	
} );
