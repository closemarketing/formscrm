<?php
/**
 * Brevo connect library
 *
 * Has functions to login, list fields and create contact
 *
 * Documentation: https://developers.brevo.com/reference/
 *
 * @author    David Perez <david@closemarketing.es>
 * @category  Functions
 * @package   FormsCRM
 * @version   4.0.0
 * @copyright 2021 Closemarketing
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
 */

defined( 'ABSPATH' ) || exit;

/**
 * Brevo CRM Library
 */
class CRMLIB_Brevo {
 // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Legacy class name, changing would break compatibility.
	/**
	 * Brevo Connector API
	 *
	 * @param string $method Method to connect: GET, POST..
	 * @param string $module URL endpoint.
	 * @param string $apikey API Key credential.
	 * @param array  $query  Body data.
	 * @return array
	 */
	private function api( $method, $module, $apikey, $query = array() ) {
		if ( empty( $apikey ) ) {
			return;
		}
		$args = array(
			'method'  => $method,
			'headers' => array(
				'api-key'      => $apikey,
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
			),
		);
		if ( ! empty( $query ) ) {
			$args['body'] = wp_json_encode( $query );
		}

		if ( 'GET' === $method ) {
			$limit        = 100; // default limit.
			$offset       = 0;
			$result_data  = array();
			$repeat_query = false;

			// . '?limit=' . $limit . '&offset=' . $offset
			do {
				$result = $this->request( $module, $args );

				if ( 'ok' === $result['status'] && ! empty( $result['data'] ) && is_array( $result['data'] ) ) {
					$offset      += count( $result['data'] );
					$result_data  = array_merge( $result_data, $result['data'] );
					$repeat_query = count( $result['data'] ) === $limit ? true : false;
				} else {
					return $result;
				}
			} while ( $repeat_query );
			return array(
				'status' => 'ok',
				'data'   => $result_data,
			);
		} else {
			return $this->request( $module, $args );
		}
	}

	/**
	 * Request to Brevo API
	 *
	 * @param string $module URL endpoint with parameters.
	 * @param array  $args  Body data.
	 * @return array
	 */
	private function request( $module, $args ) {
		$url         = 'https://api.brevo.com/v3/' . $module;
		$result      = wp_remote_request( $url, $args );
		$result_code = wp_remote_retrieve_response_code( $result );
		$body        = wp_remote_retrieve_body( $result );
		$api_data    = json_decode( $body, true );
		$result_code = intval( $result_code / 100 );

		if ( is_wp_error( $result ) || 2 !== $result_code ) {
			$message = 'Error: ';
			foreach ( $api_data as $key => $value ) {
				$message .= $key . ': ' . $value . ' ';
			}
			formscrm_error_admin_message( 'ERROR', $message );
			return array(
				'status' => 'error',
				'data'   => $message,
			);
		} else {
			return array(
				'status' => 'ok',
				'data'   => $api_data,
			);
		}
	}
	/**
	 * Logins to a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return false or id     returns false if cannot login and string if gets token
	 */
	public function login( $settings ) {
		$apikey = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		
		if ( empty( $apikey ) ) {
			return array(
				'status' => 'error',
				'data'   => __( 'API Key is required', 'formscrm' ),
			);
		}

		try {
			$results = $this->api( 'GET', 'contacts/lists', $apikey );

			if ( 'ok' === $results['status'] ) {
				return array(
					'status' => 'ok',
					'data'   => __( 'Successfully connected to Brevo', 'formscrm' ),
				);
			}

			return array(
				'status' => 'error',
				'data'   => __( 'Could not authenticate with Brevo. Please check your API key.', 'formscrm' ),
			);
		} catch ( \Exception $e ) {

			// Log that authentication test failed.
			error_log( __METHOD__ . '(): API credentials are invalid; ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional logging for API errors.

			return array(
				'status' => 'error',
				'data'   => $e->getMessage(),
			);
		}
	}

	/**
	 * List modules of a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return array           returns an array of mudules
	 */
	public function list_modules( $settings ) {
		$apikey = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';

		// If API cannot be initialized, return array.
		if ( ! $this->login( $settings ) ) {
			return array();
		}

		// Initialize choices array.
		$choices      = array();
		$result_lists = $this->api( 'GET', 'contacts/lists', $apikey );

		// If no lists were found, return.
		if ( 'error' === $result_lists['status'] || empty( $result_lists['data']['lists'] ) ) {
			return array();
		}

		// Loop through array.
		foreach ( $result_lists['data']['lists'] as $list ) {

			// Add list as choice.
			$choices[] = array(
				'label' => esc_html( $list['name'] ),
				'value' => (int) $list['id'],
			);
		}

		return $choices;
	}

	/**
	 * List fields for given module of a CRM
	 *
	 * @param  array  $settings settings from Gravity Forms options.
	 * @param  string $module settings from Gravity Forms options.
	 * @return array           returns an array of mudules
	 */
	public function list_fields( $settings, $module ) {
		$apikey = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$module = ! empty( $module ) ? $module : '';

		// Initialize field map.
		$field_map = array();

		try {
			$custom_fields = $this->api( 'GET', 'contacts/attributes', $apikey );
		} catch ( \Exception $e ) {

			// Log that we could not retrieve custom fields.
			error_log( __METHOD__ . '(): Unable to retrieve custom fields; ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional logging for API errors.

			return $field_map;
		}

		$field_map[] = array(
			'label' => 'Email',
			'name'  => 'email',
		);

		$field_map[] = array(
			'label' => 'EXT_ID',
			'name'  => 'ext_id',
		);

		if ( 'error' === $custom_fields['status'] || empty( $custom_fields['data']['attributes'] ) ) {
			return $field_map;
		}

		// Loop through custom fields.
		foreach ( $custom_fields['data']['attributes'] as $custom_field ) {
			if ( isset( $custom_field['category'] ) && 'global' === $custom_field['category'] ) {
				continue;
			}

			if ( 'EXT_ID' === $custom_field['name'] ) {
				continue;
			}

			$field_name = isset( $custom_field['name'] ) ? $custom_field['name'] : '';

			// Add custom field to field map.
			$field_map[] = array(
				'label' => $field_name,
				'name'  => $field_name,
			);
		}
		return $field_map;
	}

	/**
	 * Creates an entry for given module of a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @param  array $merge_vars array of values for the entry.
	 * @return array           id or false
	 */
	public function create_entry( $settings, $merge_vars ) {
		$apikey  = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$list_id = isset( $settings['fc_crm_module'] ) ? (int) $settings['fc_crm_module'] : '';

		$subscriber            = array();
		$subscriber['listIds'] = array( $list_id );
		foreach ( $merge_vars as $element ) {
			if ( false === strpos( $element['name'], '|' ) ) {
				$subscriber[ $element['name'] ] = $element['value'];
			} else {
				$key                              = str_replace( 'attributes|', '', $element['name'] );
				$subscriber['attributes'][ $key ] = $element['value'];
			}
		}

		try {
			// Subscribe user.
			$result = $this->api( 'POST', 'contacts', $apikey, $subscriber );

			if ( 'ok' === $result['status'] ) {
				$response_result = array(
					'status'  => 'ok',
					'message' => 'success',
					'id'      => $result['data']['id'],
				);
			} else {
				$message         = isset( $result['data'] ) ? $result['data'] : '';
				$response_result = array(
					'status'  => 'error',
					'message' => $message,
					'url'     => isset( $result['url'] ) ? $result['url'] : '',
					'query'   => isset( $result['query'] ) ? $result['query'] : '',
				);
			}
		} catch ( \Exception $e ) {
			$response_result = array(
				'status'  => 'error',
				'message' => $e->getMessage(),
				'url'     => '',
				'query'   => '',
			);
		}

		return $response_result;
	}
} //from Class
