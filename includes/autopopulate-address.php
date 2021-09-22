<?php

/**
 * Admin menu
 */
\add_action( 'admin_menu', 'pronamic_maps_admin_menu' );

function pronamic_maps_admin_menu() {
    \add_options_page(
        __( 'Pronamic Maps', 'pronamic_maps' ),
        __( 'Pronamic Maps', 'pronamic_maps' ),
        'manage_options', 
        'pronamic_maps_settings', 
        'pronamic_maps_settings_render'
    );
}

/**
 * Initialize
 */
\add_action( 'admin_init',  'pronamic_maps_admin_init' );

function pronamic_maps_admin_init() {

	// Section
	\add_settings_section(
		'pronamic_maps_settings_section_general',
		__( 'Settings', 'pronamic_maps' ),
		'__return_false',
		'pronamic_maps_settings_page_general'
	);

	// Field
	\register_setting( 'pronamic_maps_settings_page_general', 'pronamic_maps_google_geo_api_key' );
	\add_settings_field(
		'pronamic_maps_google_geo_api_key',
		__( 'Google geocoding API key', 'pronamic_maps' ),
		'pronamic_maps_field_input_text',
		'pronamic_maps_settings_page_general',
		'pronamic_maps_settings_section_general',
		array(
			'label_for' => 'pronamic_maps_google_geo_api_key',
		)
	);
}

/**
 * Render
 */
function pronamic_maps_settings_render() {
	?>
	<form action="options.php" method="post">
		<?php

		\settings_fields( 'pronamic_maps_settings_page_general' );

		\do_settings_sections( 'pronamic_maps_settings_page_general' );

		\submit_button();

		?>
	</form>

	<?php
}

/**
 * Field text
 */
function pronamic_maps_field_input_text( $args ) {
	printf(
		'<input name="%s" id="%s" type="text" value="%s" class="%s" />',
		\esc_attr( $args['label_for'] ),
		\esc_attr( $args['label_for'] ),
		\esc_attr( get_option( $args['label_for'] ) ),
		'regular-text'
	);
}

/**
 * Script
 */
\add_action( 'wp_enqueue_scripts', 'pronamic_maps_autopopulate_address_script' );

function pronamic_maps_autopopulate_address_script() {
	\wp_register_script(
		'pronamic-maps-autopopulate-address',
		PRONAMIC_MAPS_URL . 'js/pronamic-maps.min.js',
		array(),
		'1.0.0',
		true
	);

	\wp_enqueue_script( 'pronamic-maps-autopopulate-address' );
}

/**
 * Adding custom REST API endpoint.
 */
\add_action( 'rest_api_init', 'pronamic_maps_autopopulate_address_rest_api_init' );

function pronamic_maps_autopopulate_address_rest_api_init() {
	\register_rest_route(
		'pronamic-maps/v1',
		'/address/(?P<address>[^/]+)',
		array(
			'methods'             => array(
				'GET',
				'POST',
			),
			'callback'            => 'pronamic_maps_rest_api_location_address',
			'permission_callback' => '__return_true',
		)
	);
}

/**
 * REST API location address from Nationaal georegister.
 *
 * @param WP_REST_Request $request Request.
 *
 * @return object $response
 */
function pronamic_maps_rest_api_location_address( WP_REST_Request $request ) {
	$data = (object) array(
		'address' => (object) array(
			'street'        => null,
			'street_number' => null,
			'city'          => null,
			'latitude'      => null,
			'longitude'     => null,
		),
	);

	// Requests to different sources.
	if ( 'nl_NL' == get_locale() ) {
		$data = pronamic_maps_nationaal_georegister_request( $request['address'], $data );
	} else {
		$data = pronamic_maps_google_request( $request['address'], $data );
	}
	
	// REST response object.
	$response = new WP_REST_Response( $data );

	/**
	 * Return.
	 */
	return $response;
}

/**
 * REST API request to Nationaal georegister.
 * 
 * @link https://github.com/PDOK/locatieserver/wiki/API-Locatieserver
 *
 * @param string  $address Address.
 * @param object  $data    Address data.
 *
 * @return object $data Address data.
 */
function pronamic_maps_nationaal_georegister_request( $address ) {
	// Suggest request.
	$url = sprintf(
		'https://geodata.nationaalgeoregister.nl/locatieserver/v3/%s?%s',
		'suggest',
		_http_build_query(
			array(
				'q' => \str_replace( ' ', '', $address->postcode ),
			)
		)
	);

	$response = wp_remote_get( $url );

	if ( \is_wp_error( $response ) ) {
		return $address;
	}

	$data = \json_decode( \wp_remote_retrieve_body( $response ) );

	$documents = $data->response->docs;

	if ( empty( $address->street_name ) || empty( $address->city ) ) {
		$documents_postcode = array_filter( $documents, function( $document ) {
			return ( 'postcode' === $document->type );
		} );

		if ( 1 === \count( $documents_postcode ) ) {
			foreach ( $documents_postcode as $document ) {
				$url = sprintf(
					'https://geodata.nationaalgeoregister.nl/locatieserver/v3/%s?%s',
					'lookup',
					_http_build_query(
						array(
							'id' => $document->id,
						)
					)
				);

				$response = wp_remote_get( $url );

				$data = \json_decode( \wp_remote_retrieve_body( $response ) );

				if ( 1 === \count( $data->response->docs ) ) {
					foreach ( $data->response->docs as $item ) {
						if ( empty( $address->street_name ) ) {
							$address->street_name = $item->straatnaam;
						}

						if ( empty( $address->city ) ) {
							$address->city = $item->woonplaatsnaam;
						}
					}
				}
			}
		}
	}

	return $address;
}

/**
 * REST API request to Google.
 *
 * @param string  $address Address.
 * @param object  $data    Address data.
 *
 * @return object $data
 */
function pronamic_maps_google_request( $address, $data ) {

	// Get coordinates.
	$coordinates = pronamic_maps_get_coordinates( $address );

	if ( ! empty( $coordinates ) ) {
		$data->address->latitude  = $coordinates['lat'];
		$data->address->longitude = $coordinates['lng'];

		// Reverse geocoding.
		$address_data = pronamic_maps_get_address( $coordinates );

		if ( ! empty( $address_data ) ) {
			$data->address->street        = $address_data['street'];
			$data->address->street_number = $address_data['street_number'];
			$data->address->city          = $address_data['city'];
		}
	}

	return $data;
}

/**
 * Get coordinates.
 *
 * @param string $address Address to get coordinates for.
 *
 * @return array Coordinates
 */
function pronamic_maps_get_coordinates( $address = null ) {
	$key = get_option( 'pronamic_maps_google_geo_api_key' );

	$lat = '';
	$lng = '';

	// Request.
	$url = sprintf(
		'https://maps.googleapis.com/maps/api/%s/%s?%s',
		'geocode',
		'json',
		_http_build_query(
			array(
				'address' => $address,
				'sensor'  => false,
				'key'     => $key,
			)
		)
	);

	$response = wp_remote_get( $url );

	if ( ! is_wp_error( $response ) ) {
		$body = $response['body'];

		$response_data = json_decode( $body, true );

		if ( ! empty( $response_data['results'] ) ) {
			$lat = $response_data['results'][0]['geometry']['location']['lat'];
       		$lng = $response_data['results'][0]['geometry']['location']['lng'];
    	}
	}

	$coordinates = array(
		'lat' => $lat,
		'lng' => $lng,
	);

	return $coordinates;
}

/**
 * Get address.
 *
 * @param array $coordinates coordinates to get address for.
 *
 * @return array Address data
 */
function pronamic_maps_get_address( $coordinates = array() ) {
	$key = get_option( 'pronamic_maps_google_geo_api_key' );

	$data = array();

	if (
		empty( $coordinates['lat'] )
			||
		empty( $coordinates['lng'] )
	) {
		return;
	}

	// Request.
	$url = sprintf(
		'https://maps.googleapis.com/maps/api/%s/%s?%s',
		'geocode',
		'json',
		_http_build_query(
			array(
				'latlng' => $coordinates['lat'] . ',' . $coordinates['lng'],
				'key'    => $key,
			)
		)
	);

	$response = wp_remote_get( $url );

	if ( ! is_wp_error( $response ) ) {
		$body = $response['body'];

		$response_data = json_decode( $body, true );

		if ( ! empty( $response_data['results'] ) ) {
			$results = $response_data['results'][0]['address_components'];		

			foreach ( $results as $result ) {
				if ( in_array( 'street_number', $result['types'] ) ) {
					$data['street_number'] = $result['short_name'];
				}

				if ( in_array( 'route', $result['types'] ) ) {
					$data['street'] = $result['short_name'];
				}

				if ( in_array( 'locality', $result['types'] ) ) {
					$data['city'] = $result['short_name'];
				}
			}
		}
	}

	return $data;
}
