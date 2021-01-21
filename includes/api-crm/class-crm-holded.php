<?php
/**
 * HOLDED connect library
 *
 * Has functions to login, list fields and create leadº
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.0.0
 */

include_once 'debug.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

Class CRMLIB_HOLDED {
	/**
	 * Variables
	 *
	 * @var string
	 */
	private $apikey;

	/**
	 * Construct and intialize
	 */
	public function __construct() {
	}
	/**
	 * Gets information from Holded CRM
	 *
	 * @param string $url URL for module.
	 * @return array
	 */
	private function get( $url ) {
		$args = array(
			'headers' => array(
				'key' => $this->apikey,
			),
			'timeout' => 120,
		);
		$url    = 'https://api.holded.com/api/invoicing/v1/' . $url;
		$response = wp_remote_get( $url, $args );
		if ( is_wp_error( $response ) ) {
			error_admin_message( 'ERROR', $response->errors['http_request_failed'][0] );
			return false;
		} else {
			$body = wp_remote_retrieve_body( $response );

			return json_decode( $body, true);
		}
	}
	/**
	 * Posts information from Holded CRM
	 *
	 * @param string $url URL for module.
	 * @return array
	 */
	private function post( $url, $bodypost) {
		$args = array(
			'headers' => array(
				'key' => $this->apikey,
			),
			'timeout' => 120,
			'body'    => $bodypost,
		);
		$url    = 'https://api.holded.com/api/invoicing/v1/' . $url;
		$response = wp_remote_post( $url, $args );
		if ( is_wp_error( $response ) ) {
			error_admin_message( 'ERROR', $response->errors['http_request_failed'][0] );
			return false;
		} else {
			$body = wp_remote_retrieve_body( $response );

			return json_decode( $body, true );
		}
	}

	/**
	 * Logins to a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return false or id     returns false if cannot login and string if gets token
	 */
	public function login( $settings ) {
		$this->apikey = $settings['gf_crm_apipassword'];
		return $this->get( 'contacts' )[0]['id'];
	}

	/**
	 * List modules of a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return array           returns an array of mudules
	 */
	public function list_modules( $settings ) {
		$password = $settings['gf_crm_apipassword'];

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
		$password = $settings['gf_crm_apipassword'];
		$module = isset( $settings['gf_crm_module'] ) ? $settings['gf_crm_module'] : 'contacts';

		debug_message( __( 'Module active:', 'gravityforms-crm' ) . $module );
		if ( 'contacts' === $module ) {
			$result_contact = $this->get( 'contacts' );
			echo '<pre>result';
			print_r($result_contact);
			echo '</pre>';

			if ( $result_contact ) {
				$fields = array();
				$index = 0;
				foreach ( $result_contact[0] as $key => $value ) {
					if ( '_id' !== $key ) {
						$fields[ $index ]['name']     = $key;
						$fields[ $index ]['label']    = $key;
						$fields[ $index ]['required'] = false;
						$index++;
					}
				}
			} else {
				// lead fields
				$fields = array(
					//Contact Info static.
					array(
						'name'     => 'name',
						'label'    => __( 'Name', 'gravityforms-crm' ),
						'required' => true,
					),
					array('name' => 'tradename', 'label' => __('Fiscal name', 'gravityforms-crm'), 'required' => false),
					array('name' => 'code', 'label' => __('VAT No', 'gravityforms-crm'), 'required' => false),
					array('name' => 'address', 'label' => __('Address', 'gravityforms-crm'), 'required' => false),
					array('name' => 'mobile', 'label' => __('Mobile', 'gravityforms-crm'), 'required' => false),
					array('name' => 'city', 'label' => __('City', 'gravityforms-crm'), 'required' => false),
					array('name' => 'cp', 'label' => __('ZIP', 'gravityforms-crm'), 'required' => false),
					array('name' => 'province', 'label' => __('Province', 'gravityforms-crm'), 'required' => false),
					array('name' => 'country', 'label' => __('Country', 'gravityforms-crm'), 'required' => false),
					array('name' => 'email', 'label' => __('Email', 'gravityforms-crm'), 'required' => false),
					array('name' => 'phone', 'label' => __('Phone', 'gravityforms-crm'), 'required' => false),
					array('name' => 'mobile', 'label' => __('Mobile', 'gravityforms-crm'), 'required' => false),
					array('name' => 'moreinfo', 'label' => __('More Info', 'gravityforms-crm'), 'required' => false),
					array('name' => 'tags', 'label' => __('Tags', 'gravityforms-crm'), 'required' => false),

					//Bank
					array('name' => 'sepaiban', 'label' => __('IBAN', 'gravityforms-crm'), 'required' => false),
					array('name' => 'sepaswift', 'label' => __('SWIFT', 'gravityforms-crm'), 'required' => false),
					array('name' => 'separef', 'label' => __('SEPA Ref', 'gravityforms-crm'), 'required' => false),
					array('name' => 'sepadate', 'label' => __('SEPA Date', 'gravityforms-crm'), 'required' => false),
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
		$password = $settings['gf_crm_apipassword'];
		$module = isset( $settings['gf_crm_module'] ) ? $settings['gf_crm_module'] : 'contacts';

		$contact = array();

		foreach ( $merge_vars as $element ) {
			$contact[ $element['name'] ] = (string) $element['value'];
		}

		$result = $this->post( 'contacts', $contact );

		print_r( $result );

		// debug_email_lead( 'Holded', 'Error ' . $json['error']['message'], $merge_vars );

		return $result['id'];

	}

} //from Class
