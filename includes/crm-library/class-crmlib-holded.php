<?php
/**
 * HOLDED connect library
 *
 * Has functions to login, list fields and create entry. Supports both the
 * legacy v1 API (32-char hex key, `key` header) and v2 (key prefixed with
 * `pat_`, Bearer token) — the API version is detected from the key itself.
 *
 * @author    David Perez <david@closemarketing.es>
 * @category  Functions
 * @package   FormsCRM
 * @version   2.0.0
 * @copyright 2021 Closemarketing
 */

defined( 'ABSPATH' ) || exit;

define( 'FORMSCRM_MAX_LIMIT_HOLDED_API', 500 );

/**
 * Class for Holded connection.
 */
class CRMLIB_HOLDED extends CRMLIB_Abstract {
	/**
	 * Holded API v1 base URL.
	 */
	const API_V1_BASE_URL = 'https://api.holded.com/api/';

	/**
	 * Holded API v2 base URL.
	 */
	const API_V2_BASE_URL = 'https://api.holded.com/api/v2/';

	/**
	 * Detects the Holded API version from the key's shape.
	 *
	 * @param string $apikey API key.
	 * @return string 'v2' or 'v1'.
	 */
	private function detect_api_version( $apikey ) {
		return 0 === strpos( (string) $apikey, 'pat_' ) ? 'v2' : 'v1';
	}

	/**
	 * Gets information from Holded CRM (v1 or v2, detected from the key).
	 *
	 * @param string $url      URL for module.
	 * @param string $apikey   Pass to access.
	 * @param string $function Holded API function type (invoicing, purchases, etc). v1 only.
	 * @return array
	 */
	public function get( $url, $apikey, $function = 'invoicing' ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.functionFound -- Parameter name matches Holded API.
		if ( 'v2' === $this->detect_api_version( $apikey ) ) {
			$args = array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $apikey,
					'Accept'        => 'application/json',
				),
				'timeout' => 120,
			);
			$url  = self::API_V2_BASE_URL . $url;
		} else {
			$args = array(
				'headers' => array(
					'key' => $apikey,
				),
				'timeout' => 120,
			);
			$url  = self::API_V1_BASE_URL . $function . '/v1/' . $url;
		}

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
		}

		$body = wp_remote_retrieve_body( $result );
		return array(
			'status' => 'ok',
			'data'   => json_decode( $body, true ),
		);
	}

	/**
	 * Posts information to Holded CRM (v1 or v2, detected from the key).
	 *
	 * @param string $url      URL for module.
	 * @param array  $bodypost Data to post.
	 * @param string $apikey   Pass to access.
	 * @param string $function Holded API function type (invoicing, purchases, etc). v1 only.
	 * @return array
	 */
	public function post( $url, $bodypost, $apikey, $function = 'invoicing' ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.functionFound -- Parameter name matches Holded API.
		if ( 'v2' === $this->detect_api_version( $apikey ) ) {
			$args = array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $apikey,
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
				),
				'timeout' => 120,
				'body'    => wp_json_encode( $bodypost ),
			);
			$url  = self::API_V2_BASE_URL . $url;
		} else {
			$args = array(
				'headers' => array(
					'key' => $apikey,
				),
				'timeout' => 120,
				'body'    => $bodypost,
			);
			$url  = self::API_V1_BASE_URL . $function . '/v1/' . $url;
		}

		$result = wp_remote_post( $url, $args );
		$code   = isset( $result['response']['code'] ) ? (int) round( $result['response']['code'] / 100, 0 ) : 0;

		if ( 2 !== $code ) {
			$message = implode( ' ', $result['response'] ) . ' ';
			$body    = json_decode( $result['body'], true );
			if ( is_array( $body ) ) {
				foreach ( $body as $key => $value ) {
					$message .= $key . ': ' . $value . ' ';
				}
			}
			formscrm_error_admin_message( 'ERROR', $message );
			return array(
				'status' => 'error',
				'data'   => $message,
			);
		}

		$body = wp_remote_retrieve_body( $result );
		return array(
			'status' => 'ok',
			'data'   => json_decode( $body, true ),
		);
	}

	/**
	 * Search a contact or lead by email. Uses cursor-based pagination on v2,
	 * page-based pagination on v1.
	 *
	 * @param string $module contacts or leads.
	 * @param string $email  email to search.
	 * @param string $apikey Pass to access.
	 * @return string|bool
	 */
	public function search_by_email( $module, $email, $apikey ) {
		if ( 'v2' === $this->detect_api_version( $apikey ) ) {
			$cursor = '';

			while ( true ) {
				$query_url = $module . '?limit=100';
				if ( $cursor ) {
					$query_url .= '&cursor=' . rawurlencode( $cursor );
				}

				$result = $this->get( $query_url, $apikey );
				if ( 'error' === $result['status'] || empty( $result['data'] ) ) {
					return false;
				}

				$items = isset( $result['data']['items'] ) ? $result['data']['items'] : array();
				foreach ( $items as $contact ) {
					if ( isset( $contact['email'] ) && $contact['email'] === $email ) {
						return $contact['id'];
					}
				}

				if ( ! empty( $result['data']['has_more'] ) && ! empty( $result['data']['cursor'] ) ) {
					$cursor = $result['data']['cursor'];
				} else {
					return false;
				}
			}
		}

		$function = 'contacts' === $module ? 'invoicing' : 'crm';
		$next     = true;
		$page     = 1;

		while ( $next ) {
			$contacts = $this->get( $module . '?page=' . $page, $apikey, $function );
			if ( 'error' === $contacts['status'] || empty( $contacts['data'] ) ) {
				return false;
			}

			foreach ( $contacts['data'] as $contact ) {
				if ( isset( $contact['email'] ) && $contact['email'] === $email ) {
					return $contact['id'];
				}
			}

			if ( count( $contacts['data'] ) === FORMSCRM_MAX_LIMIT_HOLDED_API ) {
				++$page;
			} else {
				$next = false;
			}
		}

		return false;
	}

	/**
	 * Logins to a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return array           status ok/error, data and message.
	 */
	public function login( array $settings ): array {
		$apikey = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';

		if ( empty( $apikey ) || ! is_string( $apikey ) ) {
			return array(
				'status'  => 'error',
				'data'    => 0,
				'message' => __( 'Invalid API key.', 'formscrm' ),
			);
		}

		$api_version  = $this->detect_api_version( $apikey );
		$login_result = 'v2' === $api_version ? $this->get( 'contacts?limit=1', $apikey ) : $this->get( 'contacts', $apikey );

		if ( 'error' === $login_result['status'] ) {
			return array(
				'status'  => 'error',
				'data'    => 0,
				'message' => __( 'Failed to login in Holded API.', 'formscrm' ),
			);
		}

		return array(
			'status'  => 'ok',
			'data'    => 0,
			/* translators: %s: API version detected (v1 or v2) */
			'message' => sprintf( __( 'Logged correctly in Holded API %s.', 'formscrm' ), $api_version ),
		);
	}

	/**
	 * List modules of a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return array           returns an array of mudules
	 */
	public function list_modules( array $settings ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Required by interface.
		$modules = array(
			array(
				'name'  => 'contacts',
				'value' => 'contacts',
				'label' => __( 'Contacts', 'formscrm' ),
			),
		);
		return $modules;
	}

	/**
	 * List fields for given module of a CRM
	 *
	 * @param  array  $settings settings from Gravity Forms options.
	 * @param  string $module   The CRM module name.
	 * @return array            returns an array of mudules
	 */
	public function list_fields( $settings, $module ) {
		$module = ! empty( $module ) ? $module : 'contacts';

		// Initialize fields array.
		$fields = array();

		if ( 'contacts' === $module ) {
			// lead fields.
			$fields = array(
				// Contact Info static.
				array(
					'name'     => 'name',
					'label'    => __( 'Name', 'formscrm' ),
					'required' => true,
				),
				array(
					'name'     => 'tradename',
					'label'    => __( 'Fiscal name', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'code',
					'label'    => __( 'VAT No', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'type',
					'label'    => __( 'Type', 'formscrm' ),
					'tooltip'  => __( 'Type of contact. Use: supplier, debtor, creditor, client, lead.', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'          => 'isperson',
					'label'         => __( 'Is person?', 'formscrm' ),
					'default_value' => '0',
					'tooltip'       => __( 'Type of person. Use: 1 = Person, 0 = Company.', 'formscrm' ),
					'required'      => false,
				),
				array(
					'name'     => 'email',
					'label'    => __( 'Email', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'phone',
					'label'    => __( 'Phone', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'mobile',
					'label'    => __( 'Mobile', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'country',
					'label'    => __( 'Country', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'billAddress|address',
					'label'    => __( 'Billing Address', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'billAddress|city',
					'label'    => __( 'Billing City', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'billAddress|postalCode',
					'label'    => __( 'Billing ZIP', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'billAddress|province',
					'label'    => __( 'Billing Province', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'billAddress|country',
					'label'    => __( 'Billing Country', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'note',
					'label'    => __( 'Note', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'tags',
					'label'    => __( 'Tags', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'iban',
					'label'    => __( 'IBAN', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'swift',
					'label'    => __( 'SWIFT', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'sepaRef',
					'label'    => __( 'SEPA Ref', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'sepaDate',
					'label'    => __( 'SEPA Date', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'taxOperation',
					'label'    => __( 'Tax Operation', 'formscrm' ),
					'tooltip'  => __( 'Use: general, intra, nosujeto, receq, exento.', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'clientRecord',
					'label'    => __( 'Client Record', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'supplierRecord',
					'label'    => __( 'Supplier Record', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'socialNetworks|website',
					'label'    => __( 'Social Networks: Website', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'defaults|expensesAccountRecord',
					'label'    => __( 'Expenses Account Record', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'defaults|salesAccountRecord',
					'label'    => __( 'Sales Account Name', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'defaults|salesAccountName',
					'label'    => __( 'Sales Account Name', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'defaults|dueDays',
					'label'    => __( 'Due Days', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'defaults|salesTax',
					'label'    => __( 'Sales Tax', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'defaults|purchasesTax',
					'label'    => __( 'Purchases Tax', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'defaults|discount',
					'label'    => __( 'Discount', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'defaults|currency',
					'label'    => __( 'Expenses Account Name', 'formscrm' ),
					'required' => false,
					'tooltip'  => __( 'Currency ISO code in lowercase (e.g., eur = Euro, usd = U.S. Dollar, etc )', 'formscrm' ),
				),
				array(
					'name'     => 'defaults|language',
					'label'    => __( 'Language', 'formscrm' ),
					'required' => false,
					'tooltip'  => __( 'options (es = spanish, en = english, fr = french, de = german, it = italian, ca = catalan, eu = euskera)', 'formscrm' ),
				),
				array(
					'name'     => 'defaults|showTradeNameOnDocs',
					'label'    => __( 'Show Trade Name on Docs', 'formscrm' ),
					'tooltip'  => __( 'Use: 1 = Yes, 0 = No.', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'defaults|showCountryOnDocs',
					'label'    => __( 'Show Country on Docs', 'formscrm' ),
					'tooltip'  => __( 'Use: 1 = Yes, 0 = No.', 'formscrm' ),
					'required' => false,
				),
			);
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
		$apikey  = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$module  = isset( $settings['fc_crm_module'] ) ? $settings['fc_crm_module'] : 'contacts';
		$contact = array();

		// list_fields() exposes v1 camelCase field IDs (existing feeds map to these).
		// Holded v2 requires snake_case field names, so translate on the way out
		// when the key is a v2 key. v1 keys keep the original field names untouched.
		$v2_field_map = array(
			'tradename'              => 'trade_name',
			'code'                   => 'vat_number',
			'isperson'               => 'is_person',
			'sepaRef'                => 'sepa_ref',
			'clientRecord'           => 'client_record',
			'supplierRecord'         => 'supplier_record',
			'billAddress|address'    => 'bill_address|address',
			'billAddress|city'       => 'bill_address|city',
			'billAddress|postalCode' => 'bill_address|postal_code',
			'billAddress|province'  => 'bill_address|province',
			'billAddress|country'   => 'bill_address|country',
			'socialNetworks|website' => 'website',
			'defaults|dueDays'       => 'defaults|due_days',
		);
		$is_v2 = 'v2' === $this->detect_api_version( $apikey );

		foreach ( $merge_vars as $element ) {
			$field_name = $is_v2 && isset( $v2_field_map[ $element['name'] ] ) ? $v2_field_map[ $element['name'] ] : $element['name'];

			if ( false !== strpos( $field_name, '|' ) ) {
				$data_field = explode( '|', $field_name );
				if ( is_array( $data_field ) ) {
					$contact[ $data_field[0] ][ $data_field[1] ] = (string) $element['value'];
				}
			} elseif ( 'tags' === $field_name ) {
				$contact[ $field_name ] = explode( ',', $element['value'] );
			} else {
				$contact[ $field_name ] = (string) $element['value'];
			}
		}

		$result = $this->post( $module, $contact, $apikey );

		if ( 'error' === $result['status'] ) {
			return array(
				'status'  => 'error',
				'message' => $result['data'],
			);
		}

		return array(
			'status'  => 'ok',
			'message' => 'success',
			'id'      => isset( $result['data']['id'] ) ? $result['data']['id'] : '',
		);
	}

	/**
	 * List fields for search entry for given module of a CRM.
	 *
	 * @internal Not applicable for Holded integration.
	 * @param  string|null $module Module to get fields from.
	 * @return array Empty array.
	 */
	public function list_fields_search_entry( ?string $module = null ): array {
		return array();
	}

	/**
	 * Map a search field ID to the API query param name.
	 *
	 * @internal Not applicable for Holded integration.
	 * @param string $search_field Field ID from list_fields_search_entry.
	 * @return string Query param name to use in the API request.
	 */
	public function determine_search_by( string $search_field ): string {
		return '';
	}

	/**
	 * Check if an entry exists and create or update it.
	 *
	 * @param array  $data   Raw merge vars from form.
	 * @param string $module CRM module slug (contacts, companies etc).
	 * @return array
	 */
	public function create_or_update_entry( array $data, string $module ): array {
		return array();
	}
} //from Class
