<?php
/**
 * Plugin Name: Pronamic Maps
 * Plugin URI: https://www.pronamic.eu/plugins/pronamic-maps/
 * Description: 
 *
 * Version: 1.0.1
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
 * Update URI: https://www.pronamic.eu/plugins/pronamic-maps/
 * GitHub URI: https://github.com/pronamic/wp-pronamic-maps
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2021 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Maps
 */

/**
 * Pronamic Maps Plugin class.
 */
class PronamicMapsPlugin {
	/**
	 * Setup.
	 */
	public function setup() {
		/**
		 * Adding custom REST API endpoint.
		 *
		 * @link https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
		 * @link https://developer.wordpress.org/reference/functions/register_rest_route/
		 */
		\add_action(
			'rest_api_init',
			function () {
				\register_rest_route(
					'pronamic-maps/v1',
					'/location/self',
					array(
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
					) 
				);

				\register_rest_route(
					'pronamic-maps/v1',
					'/address/autocomplete',
					array(
						'methods'             => array(
							'GET',
							'POST',
						),
						'callback'            => function( WP_REST_Request $request ) {
							$postcode     = $request->get_param( 'postcode' );
							$country_code = $request->get_param( 'country_code' );
							$country_name = $request->get_param( 'country_name' );
							$city         = $request->get_param( 'city' );

							$address = (object) array(
								'country_code' => $country_code,
								'country_name' => $country_name,
								'postcode'     => $postcode,
								'city'         => $city,
								'street_name'  => null,
								'house_number' => null,
								'level_1'      => null,
								'latitude'     => null,
								'longitude'    => null,
							);

							$address = $this->complete_address_via_locale( $address );
							$address = $this->complete_address_via_gravityforms( $address );
							$address = $this->complete_address_via_dutch_pdok( $address );
							$address = $this->complete_address_via_belgium_local( $address );
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
							'country_name' => array(
								'description' => __( 'Country Name.', 'pronamic-maps' ),
								'type'        => 'string',
							),
							'city'         => array(
								'description' => __( 'City.', 'pronamic-maps' ),
								'type'        => 'string',
							),
						),
					) 
				);
			} 
		);

		\add_action( 'admin_init', array( $this, 'admin_init' ) );
		\add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		\add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		\add_action( 'gform_field_advanced_settings', array( $this, 'gform_field_advanced_settings' ), 10, 2 );
		\add_action( 'gform_editor_js', array( $this, 'gform_editor_js' ), 10 );

		\add_filter( 'gform_field_container', array( $this, 'gform_field_container' ), 10, 2 );

		\add_filter( 'gform_field_content', array( $this, 'gform_field_content' ), 10, 2 );
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

		\register_setting( 'pronamic_maps_settings_page_general', 'pronamic_maps_google_api_key' );

		\add_settings_field(
			'pronamic_maps_google_api_key',
			\__( 'Google API key', 'pronamic-maps' ),
			array( $this, 'field_input_text' ),
			'pronamic_maps_settings_page_general',
			'pronamic_maps_settings_section_general',
			array(
				'label_for' => 'pronamic_maps_google_api_key',
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
	 * Field text.
	 * 
	 * @param array $args Arguments.
	 */
	public function field_input_text( $args ) {
		\printf(
			'<input name="%s" id="%s" type="text" value="%s" class="%s" />',
			\esc_attr( $args['label_for'] ),
			\esc_attr( $args['label_for'] ),
			\esc_attr( \get_option( $args['label_for'] ) ),
			'regular-text'
		);
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {
		$suffix = SCRIPT_DEBUG ? '' : '.min';

		\wp_register_script(
			'pronamic-maps-address-autocomplete',
			\plugins_url( 'js/pronamic-maps' . $suffix . '.js', __FILE__ ),
			array(),
			'1.0.0',
			true
		);

		\wp_localize_script(
			'pronamic-maps-address-autocomplete',
			'pronamic_maps',
			array(
				'rest_url_address_autocomplete' => \rest_url( 'pronamic-maps/v1/address/autocomplete' ),
			)
		);

		\wp_enqueue_script( 'pronamic-maps-address-autocomplete' );

		\wp_register_style(
			'pronamic-maps-address-autocomplete',
			\plugins_url( 'css/pronamic-maps.css', __FILE__ ),
			array(),
			'1.0.0'
		);

		\wp_enqueue_style( 'pronamic-maps-address-autocomplete' );
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
	 * Complete address via locale.
	 * 
	 * @param object $address Address to complete.
	 * @return object
	 */
	public function complete_address_via_locale( $address ) {
		if ( ! empty( $address->country_code ) ) {
			return $address;
		}

		$locale = \get_locale();

		$address->country_code = \substr( $locale, 3, 2 );

		return $address;
	}

	/**
	 * Complete address via Gravity Forms.
	 * 
	 * @param object $address Address to complete.
	 * @return object
	 */
	public function complete_address_via_gravityforms( $address ) {
		if ( ! \method_exists( 'GFCommon', 'get_country_code' ) ) {
			return $address;
		}

		if ( ! empty( $address->country_code ) ) {
			return $address;
		}

		if ( empty( $address->country_name ) ) {
			return $address;
		}

		$address->country_code = \GFCommon::get_country_code( $address->country_name );

		return $address;
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

					if ( empty( $address->level_1 ) ) {
						$address->level_1 = $document->provincienaam;
					}
				}
			}
		}

		return $address;
	}

	/**
	 * Complete address via Belgium local postal codes data.
	 * 
	 * @link https://www.bpost2.be/zipcodes/files/zipcodes_num_nl_new.html
	 * @param object $address Address to complete.
	 * @return object
	 */
	public function complete_address_via_belgium_local( $address ) {
		if ( 'BE' !== $address->country_code ) {
			return $address;
		}

		if ( ! empty( $address->city ) ) {
			return $address;
		}

		if ( empty( $address->postcode ) ) {
			return $address;
		}

		$data = include __DIR__ . '/resources/belgium-postal-codes-municipality.php';

		if ( \array_key_exists( $address->postcode, $data ) ) {
			$address->city = $data[ $address->postcode ];
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

	/**
	 * Gravity Forms field advanced settings.
	 *
	 * @link https://docs.gravityforms.com/gform_field_standard_settings/
	 * @link https://github.com/wp-premium/gravityforms/blob/2.4.12/form_detail.php#L1364-L1366
	 * @param int    $position Position.
	 * @param string $form_id  Form ID.
	 */
	public function gform_field_advanced_settings( $position, $form_id ) {
		if ( 175 !== $position ) {
			return;
		}

		?>
		<li class="pronamic_maps_autocomplete_setting field_setting">
			<input type="checkbox" id="field_pronamic_maps_autocomplete" />

			<label for="field_pronamic_maps_autocomplete" class="inline"><?php esc_html_e( 'Enable Pronamic Maps Autocomplete', 'pronamic-maps' ); ?></label>
		</li>

		<li class="pronamic_readonly_setting field_setting">
			<input type="checkbox" id="field_pronamic_readonly" />

			<label for="field_pronamic_readonly" class="inline"><?php esc_html_e( 'Readonly', 'pronamic-maps' ); ?></label>
		</li>	
		<?php
	}

	/**
	 * Gravity Forms editor JavaScript.
	 *
	 * @link https://docs.gravityforms.com/gform_field_standard_settings/
	 * @link https://www.samanthaming.com/tidbits/19-2-ways-to-convert-to-boolean/
	 * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/readonly
	 */
	public function gform_editor_js() {
		?>
		<script type="text/javascript">
			fieldSettings.address  += ', .pronamic_maps_autocomplete_setting';
			fieldSettings.text     += ', .pronamic_maps_autocomplete_setting';
			fieldSettings.text     += ', .pronamic_readonly_setting';

			const map = {
				'pronamic_maps_autocomplete': '#field_pronamic_maps_autocomplete',
				'pronamic_readonly': '#field_pronamic_readonly',
			}

			jQuery( document ).on( 'gform_load_field_settings', function( event, field, form ) {
				for ( const property in map ) {
					jQuery( map[ property ] ).prop( 'checked', !! field[ property ] );	
				}
			} );

			jQuery( document ).ready( function() {
				for ( const property in map ) {
					jQuery( map[ property ] ).on( 'change input propertychange', function() {
						SetFieldProperty( property, this.checked );
					} );
				}
			} );
		</script>
		<?php
	}

	/**
	 * Gravity Forms field container.
	 * 
	 * @link https://docs.gravityforms.com/gform_field_container/
	 * @param string $field_container Field container.
	 * @param object $field           The field currently being processed.
	 */
	public function gform_field_container( $field_container, $field ) {
		if ( ! \property_exists( $field, 'pronamic_maps_autocomplete' ) ) {
			return $field_container;
		}

		if ( true !== $field->pronamic_maps_autocomplete ) {
			return $field_container;
		}

		$field_container = \str_replace(
			'{FIELD_CONTENT}',
			'<div data-pronamic-maps-address-autocomplete="true">{FIELD_CONTENT}</div>',
			$field_container
		);

		return $field_container;
	}

	/**
	 * Gravity Forms field content.
	 * 
	 * @link https://github.com/wp-premium/gravityforms/blob/a9c8f2de051e016e096069210ecd5fb8f5a19801/form_display.php#L3397
	 * @param string    $field_content Field content.
	 * @param \GF_Field $field         Field.
	 * @return string
	 */
	public function gform_field_content( $field_content, $field ) {
		if ( \GFCommon::is_form_editor() ) {
			return $field_content;
		}

		if ( \GFCommon::is_entry_detail() ) {
			return $field_content;
		}

		if ( ! \property_exists( $field, 'pronamic_readonly' ) ) {
			return $field_content;
		}

		if ( ! $field->pronamic_readonly ) {
			return $field_content;
		}

		$document = new DOMDocument();

		$result = $document->loadHTML( $field_content );

		if ( false === $result ) {
			return $field_content;
		}

		$input = $document->getElementsByTagName( 'input' )->item( 0 );

		if ( null === $input ) {
			return $field_content;
		}

		$input->setAttribute( 'readonly', 'readonly' );

		/**
		 * Set `tabindex` to `-1`, no need to make element reachable
		 * via sequential keyboard navigation.
		 * 
		 * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/tabindex
		 */
		$input->setAttribute( 'tabindex', '-1' );

		$html = $document->saveHtml( $document->documentElement );

		if ( false !== $html ) {
			return $html;
		}

		return $field_content;
	}
}

$pronamic_maps_plugin = new PronamicMapsPlugin();

$pronamic_maps_plugin->setup();
