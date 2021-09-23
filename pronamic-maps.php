<?php
/**
 * Plugin Name: Pronamic Maps
 * Plugin URI: https://www.pronamic.eu/plugins/pronamic-maps/
 * Description: 
 *
 * Version: 1.0.0
 * Requires at least: 4.7
 *
 * Author: Pronamic
 * Author URI: https://www.pronamic.eu/
 *
 * Text Domain: pronamic-maps
 * Domain Path: /languages/
 *
 * License: GPL-3.0-or-later
 *
 * GitHub URI: https://github.com/pronamic/wp-pronamic-maps
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2021 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Maps
 */

class PronamicMapsPlugin {
	public function setup() {
		/**
		 * Adding custom REST API endpoint.
		 *
		 * @link https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
		 * @link https://developer.wordpress.org/reference/functions/register_rest_route/
		 */
		\add_action( 'rest_api_init', function () {
			\register_rest_route( 'pronamic-maps/v1', '/location/self', array(
				'methods'             => array(
					'GET',
					'POST',
				),
				'callback'            => array( $this, 'rest_api_location_self' ),
				'permission_callback' => array( $this, 'permission_callback_same_origin' ),
				'args'                => array(
					/**
					 * A cache-buster is a unique string which is appended to a URL 
					 * in the form of a query string.
					 *
					 * Google Tag Managers uses the `gtmcb` parameter as default.
					 *
					 * @link https://curtistimson.co.uk/post/front-end-dev/what-is-cache-busting/
					 * @link https://support.google.com/tagmanager/answer/6107167?hl=en
					 */
					'cb' => array(
						'description' => __( 'Cache Busting Query Parameter.', 'pronamic-maps' ),
						'type'        => 'string',
					),
					't'  => array(
						'description' => __( 'Cache Busting Query Parameter.', 'pronamic-maps' ),
						'type'        => 'string',
					),
				),
			) );

			\register_rest_route( 'pronamic-maps/v1', '/address/autocomplete', array(
				'methods'             => array(
					'GET',
					'POST',
				),
				'callback'            => function( WP_REST_Request $request ) {
					$postcode     = $request->get_param( 'postcode' );
					$country_code = $request->get_param( 'country_code' );
					$city         = $request->get_param( 'city' );

					$address = (object) array(
						'country_code' => $country_code,
						'postcode'     => $postcode,
						'city'         => $city,
						'street_name'  => null,
						'house_number' => null,
						'latitude'     => null,
						'longitude'    => null,
					);

					$address = $this->complete_address_via_dutch_pdok( $address );
					$address = $this->complete_address_via_google( $address );
					
					return (object) array(
						'address' => $address,
					);
				},
				'permission_callback' => array( $this, 'permission_callback_same_origin' ),
				'args'                => array(
					'postcode'     => array(
						'description' => __( 'Postcode.', 'pronamic-maps' ),
						'type'        => 'string',
					),
					'country_code' => array(
						'description' => __( 'Country Code.', 'pronamic-maps' ),
						'type'        => 'string',
					),
					'city'         => array(
						'description' => __( 'City.', 'pronamic-maps' ),
						'type'        => 'string',
					),
				),
			) );
		} );

		\add_action( 'admin_init', array( $this, 'admin_init' ) );
		\add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		\add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Admin init.
	 */
	public function admin_init() {
		\add_settings_section(
			'pronamic_maps_settings_section_general',
			\__( 'Settings', 'pronamic-maps' ),
			'__return_false',
			'pronamic_maps_settings_page_general'
		);

		\register_setting( 'pronamic_maps_settings_page_general', 'pronamic_maps_google_geo_api_key' );

		\add_settings_field(
			'pronamic_maps_google_geo_api_key',
			\__( 'Google geocoding API key', 'pronamic-maps' ),
			array( $this, 'field_input_text' ),
			'pronamic_maps_settings_page_general',
			'pronamic_maps_settings_section_general',
			array(
				'label_for' => 'pronamic_maps_google_geo_api_key',
			)
		);
	}

	/**
	 * Admin menu.
	 */
	public function admin_menu() {
		\add_options_page(
			__( 'Pronamic Maps', 'pronamic-maps' ),
			__( 'Pronamic Maps', 'pronamic-maps' ),
			'manage_options', 
			'pronamic_maps_settings', 
			array( $this, 'page_settings' )
		);
	}

	/**
	 * Page settings.
	 */
	public function page_settings() {
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
	public function field_input_text( $args ) {
		printf(
			'<input name="%s" id="%s" type="text" value="%s" class="%s" />',
			\esc_attr( $args['label_for'] ),
			\esc_attr( $args['label_for'] ),
			\esc_attr( get_option( $args['label_for'] ) ),
			'regular-text'
		);
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {
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
	 * Only allow from same origin.
	 *
	 * @return bool
	 */
	public function permission_callback_same_origin() {
		$referer = \wp_get_raw_referer();

		if ( false === $referer ) {
			return true;
		}

		$url = \wp_validate_redirect( $referer );

		if ( '' === $url ) {
			return false;
		}

		return true;
	}

	/**
	 * REST API location self.
	 *
	 * @link https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
	 * @param WP_REST_Request $request Request.
	 * @return object
	 */
	public function rest_api_location_self( WP_REST_Request $request ) {
		$data = (object) array(
			'country' => (object) array(
				'iso_code' => null,
			),
		);

		$response = new WP_REST_Response( $data );

		/**
		 * The response of the location self endpoint is for each visitor different.
		 *
		 * @link https://wordpress.stackexchange.com/questions/295511/how-to-stop-wp-api-endpoint-from-caching
		 * @link https://github.com/WordPress/WordPress/blob/5.7/wp-includes/rest-api/class-wp-rest-server.php#L319-L335
		 */
		foreach ( \wp_get_nocache_headers() as $key => $value ) {
			$response->header( $key, $value );
		}

		/**
		 * Cloudflare IP Geolocation.
		 *
		 * @link https://support.cloudflare.com/hc/en-us/articles/200168236-Configuring-Cloudflare-IP-Geolocation
		 */
		$value = $request->get_header( 'CF-IPCountry' );

		if ( null !== $value ) {
			$data->country->iso_code = $value;
		}

		/**
		 * Return.
		 */
		return $response;
	}

	/**
	 * Complete address via Dutch PDOK.
	 * 
	 * @link https://geodata.nationaalgeoregister.nl/
	 * @param object $address Address to complete.
	 * @return object
	 */
	public function complete_address_via_dutch_pdok( $address ) {
		if ( 'NL' !== $address->country_code ) {
			return $address;
		}

		// Suggest request.
		$url = 'https://geodata.nationaalgeoregister.nl/locatieserver/v3/free';

		$url = \add_query_arg(
			array(
				'q'  => \str_replace( ' ', '', $address->postcode ),
				'fq' => 'type:postcode',
			),
			$url
		);

		$response = \wp_remote_get( $url );

		if ( \is_wp_error( $response ) ) {
			return $address;
		}

		$data = \json_decode( \wp_remote_retrieve_body( $response ) );

		$documents = $data->response->docs;

		if ( empty( $address->street_name ) || empty( $address->city ) ) {
			if ( 1 === \count( $documents ) ) {
				foreach ( $documents as $document ) {
					if ( empty( $address->street_name ) ) {
						$address->street_name = $document->straatnaam;
					}

					if ( empty( $address->city ) ) {
						$address->city = $document->woonplaatsnaam;
					}
				}
			}
		}

		return $address;
	}

	/**
	 * Complete address via Google.
	 * 
	 * @param object $address Address to complete.
	 * @return object
	 */
	public function complete_address_via_google( $address ) {
		$key = \get_option( 'pronamic_maps_google_geo_api_key' );

		if ( empty( $key ) ) {
			return $address;
		}

		// Request.
		$url = 'https://maps.googleapis.com/maps/api/geocode/json';

		$url = \add_query_arg(
			array(
				'components' => \implode(
					'|',
					array(
						'postal_code:' . $address->postcode,
						'country:' . $address->country_code,
					)
				),
				'sensor'     => 'false',
				'key'        => $key,
			),
			$url
		);

		$response = \wp_remote_get( $url );

		if ( \is_wp_error( $response ) ) {
			return $address;
		}

		$data = \json_decode( \wp_remote_retrieve_body( $response ) );

		if ( 1 === \count( $data->results ) ) {
			foreach ( $data->results as $item ) {
				foreach ( $item->address_components as $component ) {
					/**
					 * Component `locality` indicates an incorporated city or town political entity.
					 * 
					 * @link https://developers.google.com/maps/documentation/geocoding/overview
					 */
					if ( \in_array( 'locality', $component->types ) ) {
						if ( empty( $address->city ) ) {
							$address->city = $component->long_name;
						}
					}
				}

				$address->latitude  = $item->geometry->location->lat;
				$address->longitude = $item->geometry->location->lng;
			}
		}

		return $address;
	}
}

$pronamic_maps_plugin = new PronamicMapsPlugin();

$pronamic_maps_plugin->setup();
