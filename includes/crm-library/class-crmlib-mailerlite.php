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
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class for MailerLite connection.
 */
class CRMLIB_Mailerlite implements CRMLIB_Interface {
 // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Legacy class name, changing would break compatibility.
	/**
	 * Mailer Lite Connector API
	 *
	 * @param string               $method Method to connect: GET, POST..
	 * @param string               $module URL endpoint.
	 * @param string               $apikey API Key credential.
	 * @param array<string, mixed> $query Body data.
	 * @return array<string, mixed>
	 */
	private function api( $method, $module, $apikey, $query = array() ) {
		if ( empty( $apikey ) ) {
			return array(
				'status' => 'error',
				'data'   => 'Empty API key',
			);
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
			$limit        = 100; // default limit.
			$offset       = 0;
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
	 * @param string               $module URL endpoint with parameters.
	 * @param array<string, mixed> $args Body data.
	 * @return array<string, mixed>
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
	 * @param array<string, mixed> $settings Settings from Gravity Forms options.
	 * @return bool True if login successful, false otherwise.
	 */
	public function login( array $settings ): bool {
		$apikey = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		try {
			$results = $this->api( 'GET', 'groups', $apikey );

			if ( ! empty( $results ) && 'ok' === $results['status'] ) {
				return true;
			}

			return false;
		} catch ( \Exception $e ) {

			// Log that authentication test failed.
			error_log( __METHOD__ . '(): API credentials are invalid; ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

			return false;
		}
	}

	/**
	 * List modules of a CRM
	 *
	 * @param array<string, mixed> $settings Settings from Gravity Forms options.
	 * @return array<int, array<string, mixed>> Array of modules.
	 */
	public function list_modules( array $settings ): array {
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
	 * @param array<string, mixed> $settings Settings from Gravity Forms options.
	 * @return array<int, array<string, mixed>> Array of fields.
	 */
	public function list_fields( array $settings ): array {
		$apikey = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$module = isset( $settings['fc_crm_module'] ) ? $settings['fc_crm_module'] : '';

		// Initialize field map.
		$field_map = array();

		try {
			$custom_fields = $this->api( 'GET', 'fields', $apikey );
		} catch ( \Exception $e ) {

			// Log that we could not retrieve custom fields.
			error_log( __METHOD__ . '(): Unable to retrieve custom fields; ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

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
	 * @param array<string, mixed> $merge_vars Array of values for the entry.
	 * @param array<string, mixed> $settings Settings from Gravity Forms options.
	 * @return array<string, mixed> Response with status and message.
	 */
	public function create_entry( array $merge_vars, array $settings ): array {
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
