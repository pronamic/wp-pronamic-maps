/**
 * Init
 *
 * @param {string} sourceElement  Source dom element.
 * @param {object} targetElements Target dom elements.
 */
function PronamicMapsFields( sourceElement, targetElements = {} ) {
	if ( sourceElement === null ) {
		return;
	}

	fetch( '/wp-json/pronamic-maps/v1/address/' + sourceElement.value )
		.then( response => response.json() )
		.then( data => PronamicMapsAutopopulateFields( data, targetElements ) );
}

/**
 * Autopopulate fields
 *
 * @param {object} data           Address data.
 * @param {object} targetElements Target dom elements.
 */
function PronamicMapsAutopopulateFields( data, targetElements ) {
	// Address
	if ( targetElements.address !== undefined && targetElements.street_number !== undefined ) {
		targetElements.address.value = data.address.street + ' ' + data.address.street_number;
	}

	// Street
	if ( targetElements.address !== undefined ) {
		targetElements.address.value = data.address.street;
	}

	// City
	if ( targetElements.city !== undefined ) {
		targetElements.city.value = data.address.city;
	}
}

/**
 * Demo
 */
sourceElement = document.getElementById( 'input_3_3' );

targetElements = {
	'address': document.getElementById( 'input_3_2' ),
	'city': document.getElementById( 'input_3_4' )
};

PronamicMapsFields( sourceElement, targetElements );
