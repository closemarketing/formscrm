<?php
/**
 * HOLDED connect library
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
 * Class for Holded connection.
 */
class CRMLIB_HOLDED {
	/**
	 * Gets information from Holded CRM
	 *
	 * @param string $url URL for module.
	 * @param string $apikey Pass to access.
	 * @return array
	 */
	private function get( $url, $apikey ) {
		$args     = array(
			'headers' => array(
				'key' => $apikey,
			),
			'timeout' => 120,
		);
		$url    = 'https://api.holded.com/api/invoicing/v1/' . $url;
		$result = wp_remote_get( $url, $args );
		$code   = isset( $result['response']['code'] ) ? (int) round( $result['response']['code'] / 100, 0 ) : 0;

		if ( 2 !== $code ) {
			$message = implode( ' ', $result['response'] ) . ' ';
			$body    = json_decode( $result['body'], true );
			if ( is_array( $body ) ) {
				foreach ( $body as $key => $value ) {
					$message .= $key . ': ' . $value;
				}
			}
			formscrm_error_admin_message( 'ERROR', $message );
			return array(
				'status' => 'error',
				'data'   => $message,
			);
		} else {
			$body = wp_remote_retrieve_body( $result );

			return array(
				'status' => 'ok',
				'data'   => json_decode( $body, true ),
			);
		}
	}
	/**
	 * Posts information from Holded CRM
	 *
	 * @param string $url URL for module.
	 * @param string $bodypost JSON to pass.
	 * @param string $apikey Pass to access.
	 * @return array
	 */
	private function post( $url, $bodypost, $apikey ) {
		$args   = array(
			'headers' => array(
				'key' => $apikey,
			),
			'timeout' => 120,
			'body'    => $bodypost,
		);
		$url    = 'https://api.holded.com/api/invoicing/v1/' . $url;
		$result = wp_remote_post( $url, $args );
		$code   = isset( $result['response']['code'] ) ? (int) round( $result['response']['code'] / 100, 0 ) : 0;

		if ( 2 !== $code ) {
			$message = implode( ' ', $result['response'] ) . ' ';
			$body    = json_decode( $result['body'], true );
			if ( is_array( $body ) ) {
				foreach ( $body as $key => $value ) {
					$message .= $key . ': ' . $value;
				}
			}
			formscrm_error_admin_message( 'ERROR', $message );
			return array(
				'status' => 'error',
				'data'   => $message,
			);
		} else {
			$body = wp_remote_retrieve_body( $result );

			return array(
				'status' => 'ok',
				'data'   => json_decode( $body, true ),
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
		$login_result = $this->get( 'contacts', $apikey );

		if ( $apikey && 'error' !== $login_result['status'] ) {
			return true;

		} else {
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
				'name'  => 'contacts',
				'label' => 'Contacts',
			),
		);
		return $modules;
	}

	/**
	 * List fields for given module of a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return array           returns an array of mudules
	 */
	public function list_fields( $settings ) {
		$apikey = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$module = isset( $settings['fc_crm_module'] ) ? $settings['fc_crm_module'] : 'contacts';

		formscrm_debug_message( __( 'Module active:', 'gravityforms-crm' ) . $module );
		if ( 'contacts' === $module ) {
			$result_contact = $this->get( $module, $apikey );

			if ( $result_contact['data'] ) {
				$fields = array();
				$index  = 0;
				foreach ( $result_contact['data'][0] as $key => $value ) {
					if ( '_id' !== $key && 'id' !== $key && 'customId' !== $key ) {
						$fields[ $index ]['name']     = $key;
						$fields[ $index ]['label']    = $key;
						$fields[ $index ]['required'] = 'name' === $key ? true : false;
						$index++;
					}
				}
			} else {
				// lead fields.
				$fields = array(
					// Contact Info static.
					array(
						'name'     => 'name',
						'label'    => __( 'Name', 'gravityforms-crm' ),
						'required' => true,
					),
					array(
						'name'     => 'tradename',
						'label'    => __( 'Fiscal name', 'gravityforms-crm' ),
						'required' => false,
					),
					array(
						'name'     => 'code',
						'label'    => __( 'VAT No', 'gravityforms-crm' ),
						'required' => false,
					),
					array(
						'name'     => 'address',
						'label'    => __( 'Address', 'gravityforms-crm' ),
						'required' => false,
					),
					array(
						'name'     => 'mobile',
						'label'    => __( 'Mobile', 'gravityforms-crm' ),
						'required' => false,
					),
					array(
						'name'     => 'city',
						'label'    => __( 'City', 'gravityforms-crm' ),
						'required' => false,
					),
					array(
						'name'     => 'cp',
						'label'    => __( 'ZIP', 'gravityforms-crm' ),
						'required' => false,
					),
					array(
						'name'     => 'province',
						'label'    => __( 'Province', 'gravityforms-crm' ),
						'required' => false,
					),
					array(
						'name'     => 'country',
						'label'    => __( 'Country', 'gravityforms-crm' ),
						'required' => false,
					),
					array(
						'name'     => 'email',
						'label'    => __( 'Email', 'gravityforms-crm' ),
						'required' => false,
					),
					array(
						'name'     => 'phone',
						'label'    => __( 'Phone', 'gravityforms-crm' ),
						'required' => false,
					),
					array(
						'name'     => 'mobile',
						'label'    => __( 'Mobile', 'gravityforms-crm' ),
						'required' => false,
					),
					array(
						'name'     => 'moreinfo',
						'label'    => __( 'More Info', 'gravityforms-crm' ),
						'required' => false,
					),
					array(
						'name'     => 'tags',
						'label'    => __( 'Tags', 'gravityforms-crm' ),
						'required' => false,
					),

					// Bank.
					array(
						'name'     => 'sepaiban',
						'label'    => __( 'IBAN', 'gravityforms-crm' ),
						'required' => false,
					),
					array(
						'name'     => 'sepaswift',
						'label'    => __( 'SWIFT', 'gravityforms-crm' ),
						'required' => false,
					),
					array(
						'name'     => 'separef',
						'label'    => __( 'SEPA Ref', 'gravityforms-crm' ),
						'required' => false,
					),
					array(
						'name'     => 'sepadate',
						'label'    => __( 'SEPA Date', 'gravityforms-crm' ),
						'required' => false,
					),
				);
			}
		} // module contacts

		return $fields;
	}

	/**
	 * Creates an entry for given module of a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @param  array $merge_vars array of values for the entry.
	 * @return array           id or false
	 */
	public function create_entry( $settings, $merge_vars ) {
		$apikey = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$module = isset( $settings['fc_crm_module'] ) ? $settings['fc_crm_module'] : 'contacts';

		$contact = array();

		foreach ( $merge_vars as $element ) {
			$contact[ $element['name'] ] = (string) $element['value'];
		}

		$result = $this->post( $module, $contact, $apikey );

		if ( 'error' === $result['status'] ) {
			$response_result = array(
				'status'  => 'error',
				'message' => $result['data'],
			);
		} else {
			$response_result = array(
				'status'  => 'ok',
				'message' => 'success',
				'id'      => $result['data']['id'],
			);
		}

		return $response_result;
	}

} //from Class
