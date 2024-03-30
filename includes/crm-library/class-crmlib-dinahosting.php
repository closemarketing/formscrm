<?php
/**
 * Dinahosting connect library
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
 * Class for Dinahosting connection.
 */
class CRMLIB_Dinahosting {
	/**
	 * Dinahosting Connector API
	 *
	 * @param string $method Method to connect: GET, POST..
	 * @param string $module URL endpoint.
	 * @param string $apikey API Key credential.
	 * @param array  $data   Body data.
	 * @return array
	 */
	private function request( $method, $module, $credentials, $data = array() ) {
		if ( empty( $credentials['fc_crm_username'] ) || empty( $credentials['fc_crm_password'] ) ) {
			return array(
				'status'  => 'ok',
				'message' => 'No credentials',
				'data'    => array()
			);
		}

		$args = array(
			'method'  => $method,
			'headers' => array(),
		);

		$url    = 'https://dinahosting.com/special/api.php';
		$url   .= '?AUTH_USER=' . $credentials['fc_crm_username'];
		$url   .= '&AUTH_PWD=' . $credentials['fc_crm_password'];
		$url   .= '&responseType=Json';
		$url   .= '&command=' . $module;

		if ( ! empty( $data ) ) {
			$data_login = array(
				'loginData'    => array(
					'login'    => $credentials['fc_crm_username'],
					'password' => $credentials['fc_crm_password'],
				),
			);
			$url .= '&' . http_build_query( array_merge( $data_login, $data ) );
			error_log( 'URL: ' . $url );
		}

		$result    = wp_remote_request( $url, $args );
		$body      = wp_remote_retrieve_body( $result );
		$body_data = json_decode( $body, true );

		if ( ! empty( $body_data['errors'] ) ) {
			if ( is_array( $body_data['errors'] ) ) {
				$messages = array_column( $body_data['errors'], 'message' );
				$message  = implode( '; ', $messages );
			} else {
				$message = $body_data['errors'];
			}

			return array(
				'status' => 'error',
				'data'   => $message,
			);
		}

		return array(
			'status' => 'ok',
			'data'   => $body_data,
		);
	}


	/**
	 * Logins to a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return false or id     returns false if cannot login and string if gets token
	 */
	public function login( $settings ) {
		try {
			$results = $this->request( 'GET', 'User_GetInfo', $settings );

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
		$modules = array(
			array(
				'name'  => 'users',
				'value' => 'users',
				'label' => __( 'Users', 'formscrm' ),
			),
		);
		return $modules;
	}

	/**
	 * List fields for given module of a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @param  string $module settings from Gravity Forms options.
	 * @return array           returns an array of mudules
	 */
	public function list_fields( $settings, $module ) {
		$module = ! empty( $module ) ? $module : '';

		// Initialize field map.
		$field_map = array(
			array(
				'name'  => 'personalData|firstname',
				'label' => __( 'First Name', 'formscrm' ),
			),
			array(
				'name'  => 'personalData|lastname',
				'label' => __( 'Last Name', 'formscrm' ),
			),
			array(
				'name'  => 'personalData|NIF',
				'label' => __( 'NIF', 'formscrm' ),
			),
			array(
				'name'  => 'companyData|company',
				'label' => __( 'Company', 'formscrm' ),
			),
			array(
				'name'  => 'companyData|legal_form',
				'label' => __( 'Legal Form', 'formscrm' ),
			),
			array(
				'name'  => 'companyData|CIF',
				'label' => __( 'CIF', 'formscrm' ),
			),
			array(
				'name'  => 'companyData|company_phone',
				'label' => __( 'Company Phone', 'formscrm' ),
			),
			array(
				'name'  => 'companyData|company_fax',
				'label' => __( 'Company Fax', 'formscrm' ),
			),
			array(
				'name'  => 'contactData|country_code',
				'label' => __( 'Country Code', 'formscrm' ),
			),
			array(
				'name'  => 'contactData|state',
				'label' => __( 'State', 'formscrm' ),
			),
			array(
				'name'  => 'contactData|city',
				'label' => __( 'City', 'formscrm' ),
			),
			array(
				'name'  => 'contactData|postal_code',
				'label' => __( 'Postal Code', 'formscrm' ),
			),
			array(
				'name'  => 'contactData|address',
				'label' => __( 'Address', 'formscrm' ),
			),
			array(
				'name'  => 'contactData|phone',
				'label' => __( 'Phone', 'formscrm' ),
			),
			array(
				'name'  => 'contactData|fax',
				'label' => __( 'Fax', 'formscrm' ),
			),
			array(
				'name'  => 'contactData|email_address',
				'label' => __( 'Email Address', 'formscrm' ),
			),
		);

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
		$data = array();
		foreach ( $merge_vars as $element ) {
			$key = explode( '|', $element['name'] );
			if ( 2 === count( $key ) ) {
				$data[ $key[0] ][ $key[1] ] = $element['value'];
			} else {
				$data[ $element['name'] ] = $element['value'];
			}
		}

		try {
			// Create user.
			$result = $this->request( 'GET', 'User_Secondary_Create', $settings, $data );

			if ( 'ok' === $result['status'] ) {
				$response_result = array(
					'status'  => 'ok',
					'message' => 'success',
					'id'      => $result['data']['id'],
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
				'message' => isset( $result['data'] ) ? $result['data'] . $e->getMessage() : '',
				'url'     => isset( $result['url'] ) ? $result['url'] : '',
				'query'   => isset( $result['query'] ) ? $result['query'] : '',
			);
		}

		return $response_result;
	}

} //from Class
