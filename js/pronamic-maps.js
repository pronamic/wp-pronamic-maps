var gravityforms = document.querySelectorAll( '.gform_wrapper' );

gravityforms.forEach( ( gravityform ) => {
	var input_postcode = gravityform.querySelector( '[autocomplete="postal-code"]' );

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
		var inputs = gravityform.querySelectorAll( '[autocomplete="address-level2"]' );

		inputs.forEach( ( input ) => {
			if ( '' === input.value ) {
				input.value = data.address.city;
			}
		} );
	} );
} );
