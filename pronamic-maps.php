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

define( 'PRONAMIC_MAPS_PATH', plugin_dir_path( __FILE__ ) );
define( 'PRONAMIC_MAPS_URL', plugin_dir_url( __FILE__ ) );

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

					$address = pronamic_maps_nationaal_georegister_request( $address );
					$address = pronamic_maps_get_coordinates( $address );
					
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
}

$pronamic_maps_plugin = new PronamicMapsPlugin();

$pronamic_maps_plugin->setup();

/**
 * Autopopulate address
 */
require PRONAMIC_MAPS_PATH . 'includes/autopopulate-address.php';
