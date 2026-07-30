<?php
/**
 * Hostinger Reach connect library
 *
 * Has functions to login, list fields and create contact.
 *
 * Documentation: https://developers.hostinger.com/api-reference/reach
 *
 * @author    closemarketing
 * @category  Functions
 * @package   FormsCRM
 * @version   1.0.0
 * @copyright 2026 Closemarketing
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hostinger Reach CRM Library.
 */
class CRMLIB_Reach {
 // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Legacy class name, changing would break compatibility.

	/**
	 * Contact fields accepted by the Hostinger Reach "create contact" endpoint.
	 *
	 * @var array
	 */
	private $contact_fields = array( 'email', 'name', 'surname', 'phone', 'note' );

	/**
	 * Hostinger Reach Connector API.
	 *
	 * @param string $method Method to connect: GET, POST, DELETE.
	 * @param string $module URL endpoint.
	 * @param string $token  Bearer token credential.
	 * @param array  $query  Body data.
	 * @return array
	 */
	private function api( $method, $module, $token, $query = array() ) {
		if ( empty( $token ) ) {
			return;
		}
		$args = array(
			'method'  => $method,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
		);
		if ( ! empty( $query ) ) {
			$args['body'] = wp_json_encode( $query );
		}

		if ( 'GET' !== $method ) {
			return $this->request( $module, $args );
		}

		// GET endpoints are paginated: {"data":[...],"meta":{"current_page","per_page","total"}}.
		$page         = 1;
		$result_data  = array();
		$repeat_query = false;

		do {
			$separator        = false === strpos( $module, '?' ) ? '?' : '&';
			$paginated_module = $module . $separator . 'page=' . $page;
			$result           = $this->request( $paginated_module, $args );

			if ( 'error' === $result['status'] ) {
				return $result;
			}

			if ( isset( $result['data']['data'] ) && is_array( $result['data']['data'] ) ) {
				// Paginated response shape.
				$result_data  = array_merge( $result_data, $result['data']['data'] );
				$total        = isset( $result['data']['meta']['total'] ) ? (int) $result['data']['meta']['total'] : count( $result_data );
				$repeat_query = count( $result_data ) < $total;
				++$page;
			} elseif ( is_array( $result['data'] ) ) {
				// Some endpoints (e.g. segments) return a plain array without pagination meta.
				$result_data  = $result['data'];
				$repeat_query = false;
			} else {
				$repeat_query = false;
			}
		} while ( $repeat_query );

		return array(
			'status' => 'ok',
			'data'   => $result_data,
		);
	}

	/**
	 * Request to Hostinger Reach API.
	 *
	 * @param string $module URL endpoint with parameters.
	 * @param array  $args   Request args.
	 * @return array
	 */
	private function request( $module, $args ) {
		$url         = 'https://developers.hostinger.com/api/reach/v1/' . ltrim( $module, '/' );
		$result      = wp_remote_request( $url, $args );
		$result_code = wp_remote_retrieve_response_code( $result );
		$body        = wp_remote_retrieve_body( $result );
		$api_data    = json_decode( $body, true );
		$result_code = intval( $result_code / 100 );

		if ( is_wp_error( $result ) || 2 !== $result_code ) {
			if ( isset( $api_data['error'] ) ) {
				$message = is_array( $api_data['error'] ) ? wp_json_encode( $api_data['error'] ) : $api_data['error'];
			} elseif ( isset( $api_data['message'] ) ) {
				$message = $api_data['message'];
			} else {
				$message = __( 'Unknown error', 'formscrm' );
			}
			if ( ! empty( $api_data['correlation_id'] ) ) {
				$message .= ' (correlation_id: ' . $api_data['correlation_id'] . ')';
			}
			formscrm_error_admin_message( 'ERROR', $message );
			return array(
				'status' => 'error',
				'data'   => $message,
			);
		}

		return array(
			'status' => 'ok',
			'data'   => $api_data,
		);
	}

	/**
	 * Logins to a CRM.
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return true|array      Returns true on success or an error array on failure.
	 */
	public function login( $settings ) {
		$token = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		try {
			$results = $this->api( 'GET', 'profiles', $token );

			if ( ! empty( $results ) && 'ok' === $results['status'] ) {
				return true;
			}

			$error_msg = isset( $results['data'] ) ? $results['data'] : __( 'Unable to connect to Hostinger Reach API', 'formscrm' );
			return array(
				'status'  => 'error',
				'message' => $error_msg,
			);
		} catch ( \Exception $e ) {

			// Log that authentication test failed.
			error_log( __METHOD__ . '(): API credentials are invalid; ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional logging for API errors.

			return array(
				'status'  => 'error',
				'message' => __( 'Invalid API credentials', 'formscrm' ),
			);
		}
	}

	/**
	 * List modules of a CRM.
	 *
	 * Hostinger Reach scopes contact creation to a sender "profile", so profiles
	 * are exposed here as the module choices, same as lists/groups in other
	 * Email Marketing integrations.
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return array           returns an array of mudules
	 */
	public function list_modules( $settings ) {
		$token = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';

		// If API cannot be initialized, return array.
		$login_check = $this->login( $settings );
		if ( true !== $login_check ) {
			return array();
		}

		// Initialize choices array.
		$choices         = array();
		$result_profiles = $this->api( 'GET', 'profiles', $token );

		// If no profiles were found, return.
		if ( 'error' === $result_profiles['status'] || empty( $result_profiles['data'] ) ) {
			return array();
		}

		// Loop through array.
		foreach ( $result_profiles['data'] as $profile ) {
			if ( empty( $profile['uuid'] ) ) {
				continue;
			}

			// Add profile as choice.
			$choices[] = array(
				'label' => esc_html( isset( $profile['name'] ) ? $profile['name'] : $profile['uuid'] ),
				'value' => esc_attr( $profile['uuid'] ),
			);
		}

		return $choices;
	}

	/**
	 * List fields for given module of a CRM.
	 *
	 * Hostinger Reach does not expose a custom-fields API for contacts, the
	 * field set is fixed regardless of the selected profile.
	 *
	 * @param  array  $settings settings from Gravity Forms options.
	 * @param  string $module   settings from Gravity Forms options.
	 * @return array            returns an array of mudules
	 */
	public function list_fields( $settings, $module ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Required by interface; Reach contact fields are fixed.
		return array(
			array(
				'name'     => 'email',
				'label'    => __( 'Email', 'formscrm' ),
				'required' => true,
			),
			array(
				'name'  => 'name',
				'label' => __( 'Name', 'formscrm' ),
			),
			array(
				'name'  => 'surname',
				'label' => __( 'Surname', 'formscrm' ),
			),
			array(
				'name'    => 'phone',
				'label'   => __( 'Phone', 'formscrm' ),
				'tooltip' => __( 'Phone number in E.164 format (leading "+" then 7-15 digits).', 'formscrm' ),
			),
			array(
				'name'    => 'note',
				'label'   => __( 'Note', 'formscrm' ),
				'tooltip' => __( 'Maximum 75 characters.', 'formscrm' ),
			),
		);
	}

	/**
	 * Creates an entry for given module of a CRM.
	 *
	 * @param  array $settings   settings from Gravity Forms options.
	 * @param  array $merge_vars array of values for the entry.
	 * @return array             status/message of the operation
	 */
	public function create_entry( $settings, $merge_vars ) {
		$token        = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$profile_uuid = isset( $settings['fc_crm_module'] ) ? $settings['fc_crm_module'] : '';

		if ( empty( $profile_uuid ) ) {
			return array(
				'status'  => 'error',
				'message' => __( 'No Hostinger Reach profile selected.', 'formscrm' ),
			);
		}

		$contact = array();
		foreach ( $merge_vars as $element ) {
			if ( in_array( $element['name'], $this->contact_fields, true ) ) {
				$contact[ $element['name'] ] = $element['value'];
			}
		}

		if ( empty( $contact['email'] ) ) {
			return array(
				'status'  => 'error',
				'message' => __( 'Email is required.', 'formscrm' ),
			);
		}

		try {
			// Create contact. Success responses are empty (no contact id returned),
			// e.g. when double opt-in is enabled the contact stays pending.
			$result = $this->api( 'POST', 'profiles/' . $profile_uuid . '/contacts', $token, $contact );

			if ( 'ok' === $result['status'] ) {
				$response_result = array(
					'status'  => 'ok',
					'message' => 'success',
				);
			} else {
				$response_result = array(
					'status'  => 'error',
					'message' => isset( $result['data'] ) ? $result['data'] : '',
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
