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
		'callback'            => 'pronamic_maps_rest_api_location_self',
		'permission_callback' => '__return_true',
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
} );

/**
 * REST API location self.
 *
 * @link https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
 * @param WP_REST_Request $request Request.
 * @return object
 */
function pronamic_maps_rest_api_location_self( WP_REST_Request $request ) {
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
 * Autopopulate address
 */
require PRONAMIC_MAPS_PATH . 'includes/autopopulate-address.php';
