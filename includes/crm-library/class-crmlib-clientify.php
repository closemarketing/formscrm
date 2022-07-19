<?php
/**
 * Clientify connect library
 *
 * Has functions to login, list fields and create leadº
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.0.0
 */

/**
 * Class for Holded connection.
 */
class CRMLIB_Clientify {
	/**
	 * Gets information from Holded CRM
	 *
	 * @param string $url URL for module.
	 * @return array
	 */
	private function get( $url, $apikey ) {
		if ( ! $apikey ) {
			return array(
				'status' => 'error',
				'data'   => 'No API Key',
			);
		}
		$args     = array(
			'headers' => array(
				'Authorization' => 'Token ' . $apikey,
			),
			'timeout' => 120,
		);
		// Loop.
		$next          = true;
		$page          = 1;
		$results_value = array();
		$url           = 'https://api.clientify.net/v1/' . $url;

		while ( $next ) {
			$result_api = wp_remote_get( $url, $args );
			$results    = json_decode( wp_remote_retrieve_body( $result_api ), true );
			$code       = isset( $result_api['response']['code'] ) ? (int) round( $result_api['response']['code'] / 100, 0 ) : 0;

			if ( 2 !== $code ) {
				$message = implode( ' ', $result_api['response'] ) . ' ';
				$body    = json_decode( $result_api['body'], true );

				if ( is_array( $body ) ) {
					foreach ( $body as $key => $value ) {
						$message_value = is_array( $value ) ? implode( '.', $value ) : $value;
						$message      .= $key . ': ' . $message_value;
					}
				}
				formscrm_error_admin_message( 'ERROR', $message );
				return array(
					'status' => 'error',
					'data'   => $message,
				);
			} elseif ( isset( $results['results'] ) ) {
				$results_value = array_merge( $results_value, $results['results'] );
			}

			if ( isset( $results['next'] ) && $results['next'] ) {
				$url = $results['next'];
			} else {
				$next = false;
			}
		}

		$results['results'] = $results_value;
		return array(
			'status' => 'ok',
			'data'   => $results,
		);
	}
	/**
	 * Posts information from Holded CRM
	 *
	 * @param string $module   URL for module.
	 * @param string $bodypost Params to send to API.
	 * @param string $apikey   API Authentication.
	 * @return array
	 */
	private function post( $module, $bodypost, $apikey ) {
		$args   = array(
			'headers' => array(
				'Authorization' => 'Token ' . $apikey,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 120,
			'body'    => wp_json_encode( $bodypost ),
		);
		$result = wp_remote_post( 'https://api.clientify.net/v1/' . strtolower( $module ), $args );
		$code   = isset( $result['response']['code'] ) ? (int) round( $result['response']['code'] / 100, 0 ) : 0;

		if ( 2 !== $code ) {
			$message = implode( ' ', $result['response'] ) . ' ';
			$body    = json_decode( $result['body'], true );
			if ( is_array( $body ) ) {
				foreach ( $body as $key => $value ) {
					$message_value = is_array( $value ) ? implode( '.', $value ) : $value;
					$message      .= $key . ': ' . $message_value;
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
		$apikey     = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$get_result = $this->get( 'settings/my-account/', $apikey );

		if ( $apikey && isset( $get_result['data']['count'] ) && $get_result['data']['count'] > 0 ) {
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
			array(
				'name'  => 'companies',
				'label' => 'Companies',
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
		$module = formscrm_get_module( 'Contacts' );

		formscrm_debug_message( __( 'Module active:', 'formscrm' ) . $module );
		$fields = array();
		if ( 'Contacts' === $module ) {
			$fields[] = array( 'name' => 'owner', 'label' => __( 'username of the owner of the contact', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'first_name', 'label' => __( 'contact first name', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'last_name', 'label' => __( 'Contact last name', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'phone', 'label' => __( 'Phone', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'company', 'label' => __( 'Company name', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'email', 'label' => __( 'Email', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'website', 'label' => __( 'Website', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'status', 'label' => __( 'Stores the contact status identifier', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'picture_url', 'label' => __( 'url of the picture for the contact', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'title', 'label' => __( 'Contact title', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'summary', 'label' => __( 'Summary', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'description', 'label' => __( 'Description', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'remarks', 'label' => __( 'Remarks', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'message', 'label' => __( 'Message text to be shown in the contact wall', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'lead_scoring', 'label' => __( 'Lead scoring points', 'formscrm' ), 'required' => false, );

			$fields[] = array( 'name' => 'taxpayer_identification_number', 'label' => __( 'Taxpayer identification nummber', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'tags', 'label' => __( 'Array of strings with the tags of the contact (value separated by comma)', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'gdpr_accept', 'label' => __( 'True if the user accepted the GDPR false if not', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'contact_source', 'label' => __( 'Contact source', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'medium', 'label' => __( 'Contact Medium', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'contact_type', 'label' => __( 'Contact type', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'birthday', 'label' => __( 'Birthday date', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'pinterest_url', 'label' => __( 'URL of the Pinterest site of the contact', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'twitter_url', 'label' => __( 'URL of the twitter site for the contact', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'facebook_url', 'label' => __( 'url of the facebook site for the contact', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'linkedin_url', 'label' => __( 'URL of the Linkedin site for the contact', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'googleplus_url', 'label' => __( 'URL of the Google Plus site for the contact', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'foursquare_url', 'label' => __( 'Foursquare id', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'klout_url', 'label' => __( 'url of the klout picture for the contact', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'skype_username', 'label' => __( 'Skype username for the contact', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'twitter_id', 'label' => __( 'Id of the contact in twitter', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'google_id', 'label' => __( 'Google id', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'facebook_id', 'label' => __( 'Facebook id', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'linkedin_id', 'label' => __( 'Linkedin user id', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'facebook_picture_url', 'label' => __( 'url of the facebook picture for the contact', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'twitter_picture_url', 'label' => __( 'url of the twitter picture for the contact', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'linkedin_picture_url', 'label' => __( 'url of the Linkedin picture for the contact', 'formscrm' ), 'required' => false, );
		} elseif ( 'Companies' === $module ) {
			$fields[] = array( 'name' => 'sector', 'label' => __( 'Sector', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'company_sector', 'label' => __( 'Sector of company', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'business_name', 'label' => __( 'Business Name', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'taxpayer_identification_number', 'label' => __( 'Taxpayer identification nummber', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'fax', 'label' => __( 'Fax', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'number_of_employees', 'label' => __( 'Number of employees', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'owner', 'label' => __( 'username of the owner of the contact', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'email', 'label' => __( 'Email of company', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'phone', 'label' => __( 'Phone of company', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'website', 'label' => __( 'Website', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'rank', 'label' => __( 'Rank', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'rank_manual', 'label' => __( 'Rank Manual', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'picture_url', 'label' => __( 'Picture URL', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'facebook_url', 'label' => __( 'Facebook URL', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'linkedin_url', 'label' => __( 'Linkedin URL', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'twitter_url', 'label' => __( 'Twitter URL', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'facebook_id', 'label' => __( 'Facebook ID', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'twitter_id', 'label' => __( 'Twitter ID', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'founded', 'label' => __( 'Founded', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'approx_employees', 'label' => __( 'Approximate employees', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'description', 'label' => __( 'Description', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'remarks', 'label' => __( 'Remarks', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'summary', 'label' => __( 'Summary', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'tags', 'label' => __( 'Tags', 'formscrm' ), 'required' => false, );
			$fields[] = array( 'name' => 'linkedin_picture_url', 'label' => __( 'Linkedin Picture URL', 'formscrm' ), 'required' => false, );
		}

		// Get Custom Fields.
		$equivalent_module = array(
			'Contacts'  => 'contact',
			'Companies' => 'company',
		);
		$result_api = $this->get( 'custom-fields/', $apikey );
		if ( isset( $result_api['status'] ) && 'ok' === $result_api['status'] && isset( $result_api['data']['results'] ) ) {
			foreach ( $result_api['data']['results'] as $custom_field ) {

				if ( isset( $equivalent_module[ $module ] ) && $equivalent_module[ $module ] === $custom_field['content_type'] ) {
					$fields[] = array(
						'name'     => 'custom_fields|' . $custom_field['name'],
						'label'    => $custom_field['name'],
						'required' => false,
					);
				}
			}
		}
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
		$apikey  = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$module  = isset( $settings['fc_crm_module'] ) ? $settings['fc_crm_module'] : 'Contacts';
		$contact = array();

		foreach ( $merge_vars as $element ) {
			if ( is_array( $element['value'] ) ) {
				$element['value'] = implode( ',', $element['value'] );
			}
			if ( strpos( $element['name'], '|' ) && 0 === strpos( $element['name'], 'custom_fields' ) ) {
				$custom_field = explode( '|', $element['name'] );
				$contact['custom_fields'][] = array(
					'field' => $custom_field[1],
					'value' => $element['value'],
				);
			} elseif ( 'tags' === $element['name'] && false !== strpos( $element['value'], ',' ) ) {
				$contact[ $element['name'] ] = explode( ',', $element['value'] );
			} elseif ( 'tags' === $element['name'] && false === is_array( $element['value'] ) ) {
				$contact[ $element['name'] ] = array( $element['value'] );
			} elseif ( 'gdpr_accept' === $element['name'] ) { 
				$contact[ $element['name'] ] = empty( $element['value'] ) ? false : true;
			} else {
				$contact[ $element['name'] ] = $element['value'];
			}
		}

		// Clean tags blank.
		if ( isset( $contact['tags'] ) ) {
			$contact['tags'] = array_filter( $contact['tags'] );
		}

		$result = $this->post( $module, $contact, $apikey );

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
			);
		}
		return $response_result;
	}

} //from Class
