<?php
/**
 * MailerLite connect library
 *
 * Has functions to login, list fields and create leadº
 *
 * @author    David Perez <david@closemarketing.es>
 * @category  Functions
 * @package   FormsCRM
 * @version   1.0.0
 * @copyright 2021 Closemarketing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class for MailerLite connection.
 */
class CRMLIB_Mailerlite {
	/**
	 * Mailer Lite Connector API
	 *
	 * @param string $method Method to connect: GET, POST..
	 * @param string $module URL endpoint.
	 * @param string $apikey API Key credential.
	 * @param array  $data   Body data.
	 * @return array
	 */
	private function api( $method, $module, $apikey, $query = array() ) {
		if ( empty( $apikey ) ) {
			return;
		}
		$args = array(
			'method'  => $method,
			'headers' => array(
				'X-MailerLite-ApiKey' => $apikey,
				'Content-Type'        => 'application/json',
			),
		);
		if ( ! empty( $query ) ) {
			$args['body'] = wp_json_encode( $query );
		}

		if ( 'GET' === $method ) {
			$limit  = 100; // default limit.
			$offset = 0;
			$result_data  = array();
			$repeat_query = false;
			do {
				$result = $this->request( $module . '?limit=' . $limit . '&offset=' . $offset, $args );

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
			$result = $this->request( $module, $args );
			return $result;
		}

	}

	/**
	 * Request to MailerLite API
	 *
	 * @param string $module URL endpoint with parameters.
	 * @param array  $args  Body data.
	 * @return array
	 */
	private function request( $module, $args ) {
		$url         = 'https://api.mailerlite.com/api/v2/' . $module;
		$result      = wp_remote_request( $url, $args );
		$result_code = wp_remote_retrieve_response_code( $result );
		$body        = wp_remote_retrieve_body( $result );
		$api_data    = json_decode( $body, true );

		if ( is_wp_error( $result ) || 200 !== $result_code ) {
			$message = 'Error: ' . $result->get_error_message() . ' ';
			if ( ! empty( $api_data['error'] ) && is_array( $api_data['error'] ) ) {
				foreach ( $api_data['error'] as $key => $value ) {
					$message .= $key . ': ' . $value . ' ';
				}
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
		try {
			$results = $this->api( 'GET', 'groups', $apikey );

			if ( 'ok' === $results['status'] ) {
				return true;
			}

			return false;

		} catch ( \Exception $e ) {

			// Log that authentication test failed.
			error_log( __METHOD__ . '(): API credentials are invalid; ' . $e->getMessage() );

			return false;

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
		$choices = array();

		$result_groups = $this->api( 'GET', 'groups', $apikey );

		// If no lists were found, return.
		if ( 'error' === $result_groups['status'] || empty( $result_groups['data'] ) ) {
			return array();
		}

		// Loop through array.
		foreach ( $result_groups['data'] as $group ) {

			// Add list as choice.
			$choices[] = array(
				'label' => esc_html( $group['name'] ),
				'value' => esc_attr( $group['id'] ),
			);

		}

		return $choices;
	}

	/**
	 * List fields for given module of a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @param  string $module settings from Gravity Forms options.
	 * @return array           returns an array of mudules
	 */
	public function list_fields( $settings, $module ) {
		$apikey = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$module = ! empty( $module ) ? $module : '';

		// Initialize field map.
		$field_map = array();

		try {
			$custom_fields = $this->api( 'GET', 'fields', $apikey );

		} catch ( \Exception $e ) {

			// Log that we could not retrieve custom fields.
			error_log( __METHOD__ . '(): Unable to retrieve custom fields; ' . $e->getMessage() );

			return $field_map;
		}

		// Loop through custom fields.
		foreach ( $custom_fields['data'] as $custom_field ) {

			// Add custom field to field map.
			$field_map[] = array(
				'name'  => $custom_field['key'],
				'label' => $custom_field['title'],
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
		$list_id = isset( $settings['fc_crm_module'] ) ? $settings['fc_crm_module'] : '';

		$subscriber = array();

		foreach ( $merge_vars as $element ) {
			if ( 'email' === $element['name'] ) {
				$subscriber[ $element['name'] ] = $element['value'];
			} else {
				$subscriber['fields'][ $element['name'] ] = $element['value'];
			}
		}

		try {
			// Subscribe user.
			$result = $this->api( 'POST', 'groups/' . $list_id . '/subscribers', $apikey, $subscriber );

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
			$message         = isset( $result['data'] ) ? $result['data'] : '';
			$response_result = array(
				'status'  => 'error',
				'message' => $message,
				'url'     => isset( $result['url'] ) ? $result['url'] : '',
				'query'   => isset( $result['query'] ) ? $result['query'] : '',
			);
		}

		return $response_result;
	}

} //from Class
