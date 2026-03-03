<?php
/**
 * Clientify connect library
 *
 * Has functions to login, list fields and create lead.
 * Uses Clientify API v2: https://newapi.clientify.com/
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  2.0.0
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
 */

/**
 * Class for Clientify connection.
 */
class CRMLIB_Clientify {
 // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Legacy class name, changing would break compatibility.

	/**
	 * Clientify API v2 base URL.
	 *
	 * @var string
	 */
	private $api_url = 'https://api-plus.clientify.com/v2/';

	/**
	 * Gets information from Clientify CRM
	 *
	 * @param string $url    URL for module.
	 * @param string $apikey API Authentication.
	 *
	 * @return array
	 */
	private function get( $url, $apikey ) {
		if ( ! $apikey ) {
			return array(
				'status' => 'error',
				'data'   => 'No API Key',
			);
		}
		$args = array(
			'headers' => array(
				'Authorization' => 'Token ' . $apikey,
			),
			'timeout' => 120,
		);

		$next          = true;
		$results_value = array();
		$url           = $this->api_url . $url;

		while ( $next ) {
			$result_api  = wp_remote_get( $url, $args );
			$body_raw    = wp_remote_retrieve_body( $result_api );
			$results     = json_decode( $body_raw, true );
			$code_status = (int) wp_remote_retrieve_response_code( $result_api );
			$code        = (int) round( $code_status / 100, 0 );

			if ( 2 !== $code ) {
				$message = $code_status . ' ';
				$body    = json_decode( $body_raw, true );

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
	 * Sends a request to Clientify API.
	 *
	 * @param string $module   URL for module.
	 * @param array  $bodypost Params to send to API.
	 * @param string $apikey   API Authentication.
	 * @param string $method   HTTP method to use.
	 * @return array
	 */
	private function request( $module, $bodypost, $apikey, $method = 'POST' ) {
		$url  = $this->api_url . strtolower( $module );
		$args = array(
			'headers' => array(
				'Authorization' => 'Token ' . $apikey,
				'Content-Type'  => 'application/json',
			),
			'method'  => $method,
			'timeout' => 120,
			'body'    => wp_json_encode( $bodypost ),
		);

		$result   = wp_remote_request( $url, $args );
		$body_raw = wp_remote_retrieve_body( $result );
		$code     = (int) round( (int) wp_remote_retrieve_response_code( $result ) / 100, 0 );

		if ( 2 !== $code ) {
			$message = wp_remote_retrieve_response_code( $result ) . ' ';
			$body    = json_decode( $body_raw, true );
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
				'url'    => $url,
				'query'  => wp_json_encode( $bodypost ),
			);
		} else {
			return array(
				'status' => 'ok',
				'data'   => json_decode( $body_raw, true ),
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
		if ( ! $apikey ) {
			return false;
		}

		$args = array(
			'headers' => array(
				'Authorization' => 'Token ' . $apikey,
			),
			'timeout' => 120,
		);

		$result      = wp_remote_get( $this->api_url . 'me/', $args );
		$body        = json_decode( wp_remote_retrieve_body( $result ), true );
		$code_status = (int) wp_remote_retrieve_response_code( $result );

		if ( 200 === $code_status && ! empty( $body['id'] ) ) {
			return true;
		}

		return false;
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
				'value' => 'Contacts',
				'label' => __( 'Contacts', 'formscrm' ),
			),
			array(
				'name'  => 'contacts-deals',
				'value' => 'Contacts-Deals',
				'label' => __( 'Contacts & Deals', 'formscrm' ),
			),
			array(
				'name'  => 'companies',
				'value' => 'Companies',
				'label' => __( 'Companies', 'formscrm' ),
			),
			array(
				'name'  => 'companies-deals',
				'value' => 'Companies-Deals',
				'label' => __( 'Companies & Deals', 'formscrm' ),
			),
		);
		return $modules;
	}

	/**
	 * Sends Fields addresses
	 *
	 * @return array
	 */
	private function get_fields_addresses() {
		$fields   = array();
		$fields[] = array(
			'name'     => 'addresses|street',
			'label'    => __( 'Address Street', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'addresses|city',
			'label'    => __( 'Address City', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'addresses|state',
			'label'    => __( 'Address State', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'addresses|country',
			'label'    => __( 'Address Country', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'addresses|postal_code',
			'label'    => __( 'Address Postal Code', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'addresses|type',
			'label'    => __( 'Address Type', 'formscrm' ),
			'tooltip'  => __( 'Type of address. Use: 1=Work,2=Home,3=Billing,4=Other,5=Main.', 'formscrm' ),
			'required' => false,
		);

		return $fields;
	}

	/**
	 * Sends Fields addresses
	 *
	 * @return array
	 */
	private function get_fields_social() {
		$fields = array();

		$fields[] = array(
			'name'     => 'picture_url',
			'label'    => __( 'Picture URL', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'pinterest_url',
			'label'    => __( 'URL of the Pinterest', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'twitter_url',
			'label'    => __( 'URL of the twitter', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'facebook_url',
			'label'    => __( 'url of the facebook', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'linkedin_url',
			'label'    => __( 'URL of the Linkedin', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'googleplus_url',
			'label'    => __( 'URL of the Google Plus', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'foursquare_url',
			'label'    => __( 'Foursquare id', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'klout_url',
			'label'    => __( 'url of the klout picture', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'skype_username',
			'label'    => __( 'Skype username', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'twitter_id',
			'label'    => __( 'Id of the contact in twitter', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'google_id',
			'label'    => __( 'Google id', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'facebook_id',
			'label'    => __( 'Facebook id', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'linkedin_id',
			'label'    => __( 'Linkedin user id', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'facebook_picture_url',
			'label'    => __( 'url of the facebook picture', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'twitter_picture_url',
			'label'    => __( 'url of the twitter picture', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'linkedin_picture_url',
			'label'    => __( 'url of the Linkedin picture', 'formscrm' ),
			'required' => false,
		);

		return $fields;
	}

	/**
	 * Sends Fields addresses
	 *
	 * @return array
	 */
	private function get_fields_websites() {
		$fields = array();

		$fields[] = array(
			'name'     => 'websites|corporate',
			'label'    => __( 'Corporate Website', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'websites|personal',
			'label'    => __( 'Personal Website', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'websites|blog',
			'label'    => __( 'Blog Website', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'websites|other',
			'label'    => __( 'Other Website', 'formscrm' ),
			'required' => false,
		);

		$fields[] = array(
			'name'     => 'websites|main',
			'label'    => __( 'Main Website', 'formscrm' ),
			'required' => false,
		);

		return $fields;
	}

	/**
	 * Sends Fields Phones and Emails
	 *
	 * @return array
	 */
	private function get_fields_email_phones() {
		$fields = array();

		// Email types: 1=Work, 2=Personal, 3=Other, 4=Main.
		$email_types = array(
			1 => __( 'Work', 'formscrm' ),
			2 => __( 'Personal', 'formscrm' ),
			3 => __( 'Other', 'formscrm' ),
			4 => __( 'Main', 'formscrm' ),
		);

		array_walk(
			$email_types,
			function ( $type, $key ) use ( &$fields ) {
				$fields[] = array(
					'name'     => 'emails|' . $key,
					'label'    => __( 'Email', 'formscrm' ) . ' ' . $type,
					'required' => false,
				);
			}
		);

		// Phone types from Clientify API: Main, Mobile, Work, Home, Fax, Other.
		$phone_types = array(
			1 => __( 'Main', 'formscrm' ),
			2 => __( 'Mobile', 'formscrm' ),
			3 => __( 'Work', 'formscrm' ),
			4 => __( 'Home', 'formscrm' ),
			5 => __( 'Fax', 'formscrm' ),
			6 => __( 'Other', 'formscrm' ),
		);

		array_walk(
			$phone_types,
			function ( $type, $key ) use ( &$fields ) {
				$fields[] = array(
					'name'     => 'phones|' . $key,
					'label'    => __( 'Phone', 'formscrm' ) . ' ' . $type,
					'required' => false,
				);
			}
		);

		return $fields;
	}

	/**
	 * List fields for given module of a CRM
	 *
	 * @param  array  $settings settings from Gravity Forms options.
	 * @param  string $module   module to get fields from.
	 *
	 * @return array           returns an array of mudules
	 */
	public function list_fields( $settings, $module = 'Contacts' ) {
		$apikey      = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$module      = ! empty( $module ) ? $module : 'Contacts';
		$module_slug = sanitize_title( $module );

		$fields = array();
		if ( 'contacts' === $module_slug || 'contacts-deals' === $module_slug ) {
			$fields[] = array(
				'name'     => 'owner',
				'label'    => __( 'username of the owner of the contact', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'first_name',
				'label'    => __( 'contact first name', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'last_name',
				'label'    => __( 'Contact last name', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'phone',
				'label'    => __( 'Phone Main', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'company',
				'label'    => __( 'Company name', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'email',
				'label'    => __( 'Email Main', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'country',
				'label'    => __( 'Country', 'formscrm' ),
				'required' => false,
			);

			// Phones and Emails.
			$fields = array_merge( $fields, $this->get_fields_email_phones() );

			// Website.
			$fields = array_merge( $fields, $this->get_fields_websites() );

			$fields[] = array(
				'name'     => 'status',
				'label'    => __( 'Stores the contact status identifier', 'formscrm' ),
				'tooltip'  => __( 'Status of contact: other,not-qualified-lead,visitor,cold-lead,warm-lead,hot-lead,in-deal,lost-lead,client,lost-client', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'picture_url',
				'label'    => __( 'url of the picture for the contact', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'title',
				'label'    => __( 'Contact title', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'summary',
				'label'    => __( 'Summary', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'description',
				'label'    => __( 'Description', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'remarks',
				'label'    => __( 'Remarks', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'disclaimer',
				'label'    => __( 'Disclaimer', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'message',
				'label'    => __( 'Message text to be shown in the contact wall', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'lead_scoring',
				'label'    => __( 'Lead scoring points', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'taxpayer_identification_number',
				'label'    => __( 'Taxpayer identification nummber', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'tags',
				'label'    => __( 'String with the list of tags of the contact separated by comma (,)', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'autoassignment_users',
				'label'    => __( 'String with the list of usernames separated by comma (,) to apply the autoassignment', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'gdpr_accept',
				'label'    => __( 'True if the user accepted the GDPR false if not', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'contact_source',
				'label'    => __( 'Contact source', 'formscrm' ),
				'required' => false,
			);

			// Address.
			$fields = array_merge( $fields, $this->get_fields_addresses() );

			$fields[] = array(
				'name'     => 'medium',
				'label'    => __( 'Contact Medium', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'contact_type',
				'label'    => __( 'Contact type', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'marketing_status',
				'label'    => __( 'Marketing Status', 'formscrm' ),
				'tooltip'  => __( '1=Sales Contact, 2=Marketing Contact.', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'birthday',
				'label'    => __( 'Birthday date', 'formscrm' ),
				'required' => false,
			);
			// Social.
			$fields = array_merge( $fields, $this->get_fields_social() );
		} elseif ( 'companies' === $module_slug || 'companies-deals' === $module_slug ) {
			$fields[] = array(
				'name'     => 'sector',
				'label'    => __( 'Sector', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'company_sector',
				'label'    => __( 'Sector of company', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'business_name',
				'label'    => __( 'Business Name', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'taxpayer_identification_number',
				'label'    => __( 'Taxpayer identification nummber', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'fax',
				'label'    => __( 'Fax', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'number_of_employees',
				'label'    => __( 'Number of employees', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'owner',
				'label'    => __( 'username of the owner of the contact', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'email',
				'label'    => __( 'Email of company', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'phone',
				'label'    => __( 'Phone of company', 'formscrm' ),
				'required' => false,
			);

			// Website.
			$fields = array_merge( $fields, $this->get_fields_websites() );

			// Address.
			$fields = array_merge( $fields, $this->get_fields_addresses() );

			$fields[] = array(
				'name'     => 'rank',
				'label'    => __( 'Rank', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'rank_manual',
				'label'    => __( 'Rank Manual', 'formscrm' ),
				'required' => false,
			);

			// Social.
			$fields = array_merge( $fields, $this->get_fields_social() );

			$fields[] = array(
				'name'     => 'founded',
				'label'    => __( 'Founded', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'approx_employees',
				'label'    => __( 'Approximate employees', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'description',
				'label'    => __( 'Description', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'remarks',
				'label'    => __( 'Remarks', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'summary',
				'label'    => __( 'Summary', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'tags',
				'label'    => __( 'Tags', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'autoassignment_users',
				'label'    => __( 'String with the list of usernames separated by comma (,) to apply the autoassignment', 'formscrm' ),
				'required' => false,
			);
		}

		if ( 'contacts-deals' === $module_slug ) {
			$fields[] = array(
				'name'     => 'deal|name',
				'label'    => __( 'Deal Name', 'formscrm' ),
				'required' => true,
			);

			$fields[] = array(
				'name'     => 'deal|amount',
				'label'    => __( 'Deal Amount', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'deal|pipeline_desc',
				'label'    => __( 'Pipeline Name', 'formscrm' ),
				'tooltip'  => __( 'Name of the pipeline (pipeline_desc).', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'deal|pipeline_id',
				'label'    => __( 'Pipeline ID', 'formscrm' ),
				'tooltip'  => __( 'Numeric ID of the pipeline.', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'deal|pipeline_stage_desc',
				'label'    => __( 'Pipeline Stage Name', 'formscrm' ),
				'tooltip'  => __( 'Name of the pipeline stage.', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'deal|product_skus',
				'label'    => __( 'Product SKUs in Opportunity (separated by comma)', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'deal|tags',
				'label'    => __( 'Deal tags (separated by comma)', 'formscrm' ),
				'required' => false,
			);

			$fields[] = array(
				'name'     => 'deal|expected_closed_date_days',
				'label'    => __( 'Expected Closure Date in Days', 'formscrm' ),
				'required' => false,
			);
		}

		// Fetch custom fields by object_type.
		$object_types_map = array(
			'contacts'        => array( 'contacts' ),
			'companies'       => array( 'companies' ),
			'contacts-deals'  => array( 'contacts', 'deals' ),
			'companies-deals' => array( 'companies', 'deals' ),
		);
		$label_map        = array(
			'contacts'  => __( 'Contact', 'formscrm' ),
			'companies' => __( 'Company', 'formscrm' ),
			'deals'     => __( 'Deal', 'formscrm' ),
		);

		if ( isset( $object_types_map[ $module_slug ] ) ) {
			foreach ( $object_types_map[ $module_slug ] as $object_type ) {
				$result_api = $this->get( 'custom-fields/?object_type=' . $object_type, $apikey );
				if ( isset( $result_api['status'] ) && 'ok' === $result_api['status'] && isset( $result_api['data']['results'] ) ) {
					foreach ( $result_api['data']['results'] as $custom_field ) {
						$key  = 'deals' === $object_type ? 'deal|' : '';
						$key .= 'custom_fields|' . $custom_field['name'];

						$label = isset( $label_map[ $object_type ] ) ? $label_map[ $object_type ] . ': ' : '';

						$fields[] = array(
							'name'     => $key,
							'label'    => $label . $custom_field['name'],
							'required' => false,
						);
					}
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
		$apikey            = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$module            = isset( $settings['fc_crm_module'] ) ? $settings['fc_crm_module'] : 'Contacts';
		$contact           = array();
		$deal              = array();
		$deal_product_skus = '';
		$deal_tags         = '';
		$last_module       = 'contact';

		$module = sanitize_title( $module );
		$module = str_replace( '-deals', '', $module );

		foreach ( $merge_vars as $element ) {
			if ( is_array( $element['value'] ) ) {
				$element['value'] = implode( ',', $element['value'] );
			}
			if ( strpos( $element['name'], '|' ) && 0 === strpos( $element['name'], 'deal|custom_fields' ) ) {
				$custom_field            = explode( '|', $element['name'] );
				$deal['custom_fields'][] = array(
					'field' => $custom_field[2],
					'value' => $element['value'],
				);
			} elseif ( strpos( $element['name'], '|' ) && 0 === strpos( $element['name'], 'deal' ) ) {
				if ( 'deal|product_skus' === $element['name'] ) {
					$deal_product_skus = $element['value'];
				} elseif ( 'deal|tags' === $element['name'] ) {
					$deal_tags = $element['value'];
				} elseif ( 'deal|expected_closed_date_days' === $element['name'] ) {
					$deal['expected_closed_date'] = gmdate( 'Y-m-d', strtotime( '+' . (int) $element['value'] . ' days' ) );
				} elseif ( 'deal|pipeline_id' === $element['name'] ) {
					$deal['pipeline_id'] = (int) $element['value'];
				} elseif ( 'deal|pipeline_desc' === $element['name'] ) {
					$deal['pipeline_desc'] = $element['value'];
				} elseif ( 'deal|pipeline_stage_desc' === $element['name'] ) {
					$deal['pipeline_stage_desc'] = $element['value'];
				} else {
					$deal_field             = explode( '|', $element['name'] );
					$deal[ $deal_field[1] ] = $element['value'];
				}
			} elseif ( strpos( $element['name'], '|' ) && 0 === strpos( $element['name'], 'custom_fields' ) ) {
				$custom_field               = explode( '|', $element['name'] );
				$contact['custom_fields'][] = array(
					'field' => $custom_field[1],
					'value' => $element['value'],
				);
			} elseif ( strpos( $element['name'], '|' ) && 0 === strpos( $element['name'], 'emails' ) ) {
				$email               = explode( '|', $element['name'] );
				$contact['emails'][] = array(
					'type'  => (int) $email[1],
					'email' => $element['value'],
				);
			} elseif ( strpos( $element['name'], '|' ) && 0 === strpos( $element['name'], 'phones' ) ) {
				$phone               = explode( '|', $element['name'] );
				$contact['phones'][] = array(
					'type'  => (int) $phone[1],
					'phone' => $element['value'],
				);
			} elseif ( strpos( $element['name'], '|' ) && 0 === strpos( $element['name'], 'addresses' ) ) {
				$address_field                                = explode( '|', $element['name'] );
				$contact['addresses'][0][ $address_field[1] ] = $element['value'];
			} elseif ( strpos( $element['name'], '|' ) && 0 === strpos( $element['name'], 'websites' ) ) {
				$website_field = explode( '|', $element['name'] );
				$website_type  = 5;
				if ( 'corporate' === $website_field[1] ) {
					$website_type = 1;
				} elseif ( 'personal' === $website_field[1] ) {
					$website_type = 2;
				} elseif ( 'blog' === $website_field[1] ) {
					$website_type = 3;
				} elseif ( 'other' === $website_field[1] ) {
					$website_type = 4;
				}
				$contact['websites'][] = array(
					'type'    => $website_type,
					'website' => $element['value'],
				);
			} elseif ( 'tags' === $element['name'] && false !== strpos( $element['value'], ',' ) ) {
				$contact[ $element['name'] ] = explode( ',', $element['value'] );
			} elseif ( 'tags' === $element['name'] && false === is_array( $element['value'] ) ) {
				$contact[ $element['name'] ] = array( $element['value'] );
			} elseif ( 'gdpr_accept' === $element['name'] || 'disclaimer' === $element['name'] ) {
				$contact[ $element['name'] ] = empty( $element['value'] ) ? false : true;
			} elseif ( 'marketing_status' === $element['name'] ) {
				$contact['marketing_status'] = (int) $element['value'];
			} elseif ( 'birthday' === $element['name'] ) {
				// Normalize birthday date format to YYYY-MM-DD.
				$normalized_date = formscrm_normalize_date_format( $element['value'] );
				if ( false !== $normalized_date ) {
					$contact[ $element['name'] ] = $normalized_date;
				}
			} else {
				$contact[ $element['name'] ] = $element['value'];
			}
		}

		// Clean tags blank.
		if ( ! empty( $contact['tags'] ) && is_array( $contact['tags'] ) ) {
			$contact_tags    = array_map( 'trim', $contact['tags'] );
			$contact['tags'] = array_values( array_filter( $contact_tags ) );
		}

		$result = $this->request( $module . '/', $contact, $apikey );
		if ( 'ok' === $result['status'] ) {
			$contact_id      = isset( $result['data']['id'] ) ? $result['data']['id'] : '';
			$response_result = array(
				'status'  => 'ok',
				'message' => 'success',
				'id'      => $contact_id,
			);

			// Create deal linked to the contact/company.
			if ( ! empty( $deal ) ) {
				if ( ! empty( $deal_product_skus ) ) {
					$res_products = $this->extract_deal_products( $deal_product_skus, $apikey );
					if ( ! empty( $res_products['data'] ) ) {
						$deal['products'] = $res_products['data'];
						$deal['amount']   = ! empty( $res_products['total'] ) ? $res_products['total'] : 0;
					}
				}

				// V2 uses ID-based references instead of URL-based.
				if ( 'contacts' === $module ) {
					$deal['contact_id'] = (int) $contact_id;
				} elseif ( 'companies' === $module ) {
					$deal['company_id'] = (int) $contact_id;
				}
				$deal['amount'] = isset( $deal['amount'] ) ? $deal['amount'] : 0;
				$result         = $this->request( 'deals/', $deal, $apikey );
				if ( 'ok' === $result['status'] ) {
					$response_result['id'] = sprintf(
						/* translators: %1$s: Contact ID, %2$s: Deal ID */
						__( 'Contact %1$s | Deal %2$s', 'formscrm' ),
						$contact_id,
						$result['data']['id']
					);
				}

				// Add tags to deal.
				if ( ! empty( $deal_tags ) && isset( $result['data']['id'] ) ) {
					$deal_tags_raw = explode( ',', $deal_tags );
					$deal_id       = $result['data']['id'];

					foreach ( $deal_tags_raw as $deal_tag ) {
						$deal_tags_api = array(
							'name' => sanitize_text_field( $deal_tag ),
						);

						$result_tag = $this->request( 'deals/' . $deal_id . '/tags/', $deal_tags_api, $apikey );

						if ( 'ok' !== $result_tag['status'] ) {
							$result_deal_tag = sprintf(
								/* translators: %s: Tag name */
								__( 'Tag %s not added to deal', 'formscrm' ),
								$deal_tag,
							);
						} else {
							$result_deal_tag = sprintf(
								/* translators: %s: Tag name */
								__( 'Tag %s added to deal', 'formscrm' ),
								$deal_tag,
							);
						}
						$response_result['message'] .= ' ' . $result_deal_tag;
					}
				}
				$last_module = 'deal';
			}
		} else {
			$message         = isset( $result['data'] ) ? $result['data'] : '';
			$response_result = array(
				'status'  => 'error',
				'message' => $message,
				'url'     => isset( $result['url'] ) ? $result['url'] : '',
				'query'   => isset( $result['query'] ) ? $result['query'] : '',
			);
		}

		$response_result['module'] = $last_module;
		return $response_result;
	}

	/**
	 * Extracts deal products from a string of SKUs and get Clientify schema.
	 *
	 * @param string $deal_product_skus The string of SKUs separated by commas.
	 * @param string $apikey            The API key.
	 * @return array The array of deal products in v2 format.
	 */
	private function extract_deal_products( $deal_product_skus, $apikey ) {
		$skus          = explode( ',', $deal_product_skus );
		$deal_products = array();
		$deal_total    = 0;
		foreach ( $skus as $sku ) {
			$sku         = trim( $sku );
			$res_product = $this->get( 'products/?sku=' . $sku, $apikey );
			if ( 'ok' === $res_product['status'] && isset( $res_product['data']['results'][0]['id'] ) ) {
				$product       = $res_product['data']['results'][0];
				$product_price = ! empty( $product['price'] ) ? (float) $product['price'] : 0;

				$deal_products[] = array(
					'product_id' => $product['id'],
					'price'      => $product_price,
					'quantity'   => 1,
				);
				$deal_total     += $product_price;
			}
		}
		return array(
			'status' => 'ok',
			'data'   => $deal_products,
			'total'  => $deal_total,
		);
	}
} //from Class
