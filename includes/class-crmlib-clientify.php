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
		$args     = array(
			'headers' => array(
				'Authorization' => 'Token ' . $apikey,
			),
			'timeout' => 120,
		);
		$url      = 'https://api.clientify.net/v1/' . $url;
		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			formscrm_error_admin_message( 'ERROR', $response->errors['http_request_failed'][0] );
			return false;
		} else {
			$body = wp_remote_retrieve_body( $response );

			return json_decode( $body, true );
		}
	}
	/**
	 * Posts information from Holded CRM
	 *
	 * @param string $url URL for module.
	 * @return array
	 */
	private function post( $url, $bodypost, $apikey ) {
		$args     = array(
			'headers' => array(
				'Authorization' => 'Token ' . $apikey,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 120,
			'body'    => wp_json_encode( $bodypost ),
		);
		$response = wp_remote_post( 'https://api.clientify.net/v1/' . $url, $args );
		if ( is_wp_error( $response ) ) {
			formscrm_error_admin_message( 'ERROR', $response->errors['http_request_failed'][0] );
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
		$apikey = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$get_result = $this->get( 'settings/my-account/', $apikey );

		if ( $apikey && isset( $get_result['count'] ) && $get_result['count'] > 0 ) {
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
				'label' => __( 'Contacts', 'formscrm' ),
			),
			array(
				'name'  => 'companies',
				'label' => __( 'Companies', 'formscrm' ),
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

		formscrm_debug_message( __( 'Module active:', 'formscrm' ) . $module );
		$fields = array();
		if ( 'contacts' === $module ) {
			$fields[] = array( 'name' => 'owner', 'label' => __( 'username of the owner of the contact', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'first_name', 'label' => __( 'contact first name', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'last_name', 'label' => __( 'Contact last name', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'phone', 'label' => __( 'Phone', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'company', 'label' => __( 'Company name', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'email', 'label' => __( 'Email', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'website', 'label' => __( 'Website', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'status', 'label' => __( 'Stores the contact status identifier', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'picture_url', 'label' => __( 'url of the picture for the contact', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'title', 'label' => __( 'Contact title', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'summary', 'label' => __( 'Summary', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'description', 'label' => __( 'Description', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'remarks', 'label' => __( 'Remarks', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'message', 'label' => __( 'Message text to be shown in the contact wall', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'lead_scoring', 'label' => __( 'Lead scoring points', 'formscrm' ), 'required' => false , );

			$fields[] = array( 'name' => 'taxpayer_identification_number', 'label' => __( 'Taxpayer identification nummber', 'formscrm' ), 'required' => false , );

			$fields[] = array( 'name' => 'tags', 'label' => __( 'Array of strings with the tags of the contact', 'formscrm' ), 'required' => false , );

			$fields[] = array( 'name' => 'gdpr_accept', 'label' => __( 'True if the user accepted the GDPR false if not', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'contact_source', 'label' => __( 'Contact source', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'medium', 'label' => __( 'Contact Medium', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'contact_type', 'label' => __( 'Contact type', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'birthday', 'label' => __( 'Birthday date', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'visitor_key', 'label' => __( 'Visitor key regtrieved from the vk generated by the tracking code', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'pinterest_url', 'label' => __( 'URL of the Pinterest site of the contact', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'twitter_url', 'label' => __( 'URL of the twitter site for the contact', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'facebook_url', 'label' => __( 'url of the facebook site for the contact', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'linkedin_url', 'label' => __( 'URL of the Linkedin site for the contact', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'googleplus_url', 'label' => __( 'URL of the Google Plus site for the contact', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'foursquare_url', 'label' => __( 'Foursquare id', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'klout_url', 'label' => __( 'url of the klout picture for the contact', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'skype_username', 'label' => __( 'Skype username for the contact', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'twitter_id', 'label' => __( 'Id of the contact in twitter', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'google_id', 'label' => __( 'Google id', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'facebook_id', 'label' => __( 'Facebook id', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'linkedin_id', 'label' => __( 'Linkedin user id', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'facebook_picture_url', 'label' => __( 'url of the facebook picture for the contact', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'twitter_picture_url', 'label' => __( 'url of the twitter picture for the contact', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'linkedin_picture_url', 'label' => __( 'url of the Linkedin picture for the contact', 'formscrm' ), 'required' => false , );
		} elseif ( 'companies' === $module ) {
			$fields[] = array( 'name' => 'sector', 'label' => __( 'Sector', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'company_sector', 'label' => __( 'Sector of company', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'business_name', 'label' => __( 'Business Name', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'taxpayer_identification_number', 'label' => __( 'Taxpayer identification nummber', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'fax', 'label' => __( 'Fax', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'number_of_employees', 'label' => __( 'Number of employees', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'owner', 'label' => __( 'username of the owner of the contact', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'email', 'label' => __( 'Email of company', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'phone', 'label' => __( 'Phone of company', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'website', 'label' => __( 'Website', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'rank', 'label' => __( 'Rank', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'rank_manual', 'label' => __( 'Rank Manual', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'picture_url', 'label' => __( 'Picture URL', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'facebook_url', 'label' => __( 'Facebook URL', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'linkedin_url', 'label' => __( 'Linkedin URL', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'twitter_url', 'label' => __( 'Twitter URL', 'formscrm' ), 'required' =>  false, );
			$fields[] = array( 'name' => 'facebook_id', 'label' => __( 'Facebook ID', 'formscrm' ), 'required' =>  false, );
			$fields[] = array( 'name' => 'twitter_id', 'label' => __( 'Twitter ID', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'founded', 'label' => __( 'Founded', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'approx_employees', 'label' => __( 'Approximate employees', 'formscrm' ), 'required' =>  false, );
			$fields[] = array( 'name' => 'description', 'label' => __( 'Description', 'formscrm' ), 'required' =>  false, );
			$fields[] = array( 'name' => 'remarks', 'label' => __( 'Remarks', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'summary', 'label' => __( 'Summary', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'tags', 'label' => __( 'Tags', 'formscrm' ), 'required' => false , );
			$fields[] = array( 'name' => 'linkedin_picture_url', 'label' => __( 'Linkedin Picture URL', 'formscrm' ), 'required' => false , );
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
		$apikey = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$module = isset( $settings['fc_crm_module'] ) ? $settings['fc_crm_module'] : 'contacts';
		$contact = array();

		foreach ( $merge_vars as $element ) {
			if ( strpos( $element['name'], '|' ) && 0 === strpos( $element['name'], 'custom_fields' ) ) {
				$custom_field = explode( '|', $element['name'] );
				$contact[ $custom_field[0] ][ $custom_field[1] ] = $element['value'];
			} else {
				$contact[ $element['name'] ] = $element['value'];
			}
		}
		$result = $this->post( $module, $contact, $apikey );

		if ( isset( $result['id'] ) ) {
			$response_result = array(
				'status'  => 'ok',
				'message' => 'success',
				'id'      => $result['id'],
			);
		} else {
			$message = isset( $result['detail'] ) ? $result['detail'] : '';
			$response_result = array(
				'status'  => 'error',
				'message' => $message,
			);
		}
		return $response_result;
	}

} //from Class
