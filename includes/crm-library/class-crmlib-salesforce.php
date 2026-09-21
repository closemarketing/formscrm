<?php
/**
 * Salesforce connect library
 *
 * Has functions to login, list fields and create/update entries via
 * Salesforce's REST API, using OAuth 2.0 (authorization code + refresh token)
 * instead of the URL/username/password or API-key auth other CRMs use here.
 *
 * Documentation: https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/
 *
 * @author   closemarketing
 * @category Functions
 * @package  FormsCRM
 * @version  1.0.0
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-crmlib-salesforce-token.php';

/**
 * Class for Salesforce connection.
 */
class CRMLIB_Salesforce extends CRMLIB_Abstract {
 // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Follows the existing CRMLIB_ naming convention.

	/**
	 * Field names on the Salesforce describe response that are never sent back
	 * as editable fields (system/read-only bookkeeping fields).
	 *
	 * @var string[]
	 */
	private const EXCLUDED_FIELD_NAMES = array(
		'Id',
		'CreatedDate',
		'CreatedById',
		'LastModifiedDate',
		'LastModifiedById',
		'SystemModstamp',
		'IsDeleted',
		'OwnerId',
		'LastActivityDate',
		'LastViewedDate',
		'LastReferencedDate',
	);

	/**
	 * Field types on the Salesforce describe response that are compound
	 * (address/location) and can't be set directly via the REST API.
	 *
	 * @var string[]
	 */
	private const EXCLUDED_FIELD_TYPES = array( 'address', 'location' );

	/**
	 * Token manager instance, lazily created.
	 *
	 * @var CRMLIB_Salesforce_Token|null
	 */
	private $token_manager;

	/**
	 * Returns the token manager, creating it on first use.
	 *
	 * @return CRMLIB_Salesforce_Token
	 */
	public function get_token_manager(): CRMLIB_Salesforce_Token {
		if ( null === $this->token_manager ) {
			$this->token_manager = new CRMLIB_Salesforce_Token();
		}
		return $this->token_manager;
	}

	/**
	 * Logins to a CRM. For Salesforce this checks there is a usable OAuth
	 * access token (refreshing it if needed) rather than validating settings.
	 *
	 * @param  array $settings Settings from Gravity Forms options (unused; Salesforce
	 *                         credentials live in the dedicated OAuth settings).
	 * @return array Login result array.
	 */
	public function login( array $settings ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Required by interface.
		$token = $this->get_token_manager()->get_valid_access_token();

		if ( empty( $token['access_token'] ) ) {
			return array(
				'status'  => 'error',
				'data'    => 0,
				'message' => $this->get_error_message(),
			);
		}

		return array(
			'status'  => 'ok',
			'data'    => 0,
			'message' => __( 'Logged correctly in Salesforce API.', 'formscrm' ),
		);
	}

	/**
	 * List modules of a CRM.
	 *
	 * @param  array $settings Settings from Gravity Forms options.
	 * @return array           Returns an array of modules.
	 */
	public function list_modules( array $settings ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Required by interface.
		return array(
			array(
				'name'  => 'lead',
				'value' => 'Lead',
				'label' => __( 'Lead', 'formscrm' ),
			),
			array(
				'name'  => 'contact',
				'value' => 'Contact',
				'label' => __( 'Contact', 'formscrm' ),
			),
			array(
				'name'  => 'account',
				'value' => 'Account',
				'label' => __( 'Account', 'formscrm' ),
			),
			array(
				'name'  => 'opportunity',
				'value' => 'Opportunity',
				'label' => __( 'Opportunity', 'formscrm' ),
			),
		);
	}

	/**
	 * List fields for given module of a CRM. Cached in a transient since
	 * `sobjects/{module}/describe/` is an expensive call.
	 *
	 * @param  array  $settings Settings from Gravity Forms options.
	 * @param  string $module   Module to get fields from (Lead, Contact, Account, Opportunity).
	 * @return array            Returns an array of fields.
	 */
	public function list_fields( $settings, $module = 'Lead' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Required by interface.
		$module = ! empty( $module ) ? $module : 'Lead';

		$cache_key = 'formscrm_sf_fields_' . sanitize_key( strtolower( $module ) );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$result = $this->request( 'GET', 'sobjects/' . rawurlencode( $module ) . '/describe/' );
		if ( 'error' === $result['status'] || empty( $result['data']['fields'] ) ) {
			return array();
		}

		$fields = array();
		foreach ( $result['data']['fields'] as $field ) {
			if ( ! $this->is_field_editable( $field ) ) {
				continue;
			}

			$fields[] = array(
				'name'     => $field['name'],
				'label'    => ! empty( $field['label'] ) ? $field['label'] : $field['name'],
				'required' => empty( $field['nillable'] ) && empty( $field['defaultedOnCreate'] ) && ! empty( $field['createable'] ),
			);
		}

		set_transient( $cache_key, $fields, apply_filters( 'formscrm_salesforce_fields_cache_ttl', HOUR_IN_SECONDS ) );

		return $fields;
	}

	/**
	 * Whether a field from the describe response should be exposed for mapping:
	 * createable or updateable, not a calculated/formula field, not a system
	 * bookkeeping field, and not a compound (address/location) field.
	 *
	 * @param array $field Single field entry from the describe response.
	 * @return bool
	 */
	private function is_field_editable( array $field ): bool {
		if ( in_array( $field['name'], self::EXCLUDED_FIELD_NAMES, true ) ) {
			return false;
		}

		if ( empty( $field['createable'] ) && empty( $field['updateable'] ) ) {
			return false;
		}

		if ( ! empty( $field['calculated'] ) ) {
			return false;
		}

		if ( isset( $field['type'] ) && in_array( $field['type'], self::EXCLUDED_FIELD_TYPES, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Creates or updates an entry for given module of a CRM.
	 *
	 * Dedupes via a SOQL lookup on the module's unique field (Email for
	 * Lead/Contact, Name for Account/Opportunity): PATCHes the existing record
	 * when found, otherwise POSTs a new one.
	 *
	 * @param  array $settings   Settings from Gravity Forms options.
	 * @param  array $merge_vars Array of values for the entry.
	 * @return array             Status, message and id.
	 */
	public function create_entry( $settings, $merge_vars ) {
		$module = ! empty( $settings['fc_crm_module'] ) ? $settings['fc_crm_module'] : 'Lead';

		$data = array();
		foreach ( $merge_vars as $element ) {
			if ( '' === $element['value'] || null === $element['value'] ) {
				continue;
			}
			$data[ $element['name'] ] = is_array( $element['value'] ) ? implode( ';', $element['value'] ) : $element['value'];
		}

		if ( empty( $data ) ) {
			return array(
				'status'  => 'error',
				'message' => __( 'No data to send to Salesforce.', 'formscrm' ),
			);
		}

		$unique_field = $this->get_unique_field( $module );
		$existing_id  = null;

		if ( ! empty( $data[ $unique_field ] ) ) {
			$existing_id = $this->find_existing_id( $module, $unique_field, $data[ $unique_field ] );
		}

		if ( null !== $existing_id ) {
			$result = $this->request( 'PATCH', 'sobjects/' . rawurlencode( $module ) . '/' . rawurlencode( $existing_id ), $data );
			if ( 'error' === $result['status'] ) {
				return array(
					'status'  => 'error',
					'message' => $result['data'],
				);
			}
			return array(
				'status'  => 'ok',
				'message' => 'success',
				'id'      => $existing_id,
				'module'  => strtolower( $module ),
			);
		}

		$result = $this->request( 'POST', 'sobjects/' . rawurlencode( $module ) . '/', $data );
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
			'module'  => strtolower( $module ),
		);
	}

	/**
	 * Field used to dedupe existing records for a given module.
	 *
	 * @param string $module CRM module (Lead, Contact, Account, Opportunity).
	 * @return string
	 */
	private function get_unique_field( string $module ): string {
		return in_array( $module, array( 'Lead', 'Contact' ), true ) ? 'Email' : 'Name';
	}

	/**
	 * Looks up an existing record's Id via a SOQL query on the unique field.
	 *
	 * @param string $module Module (sObject) name.
	 * @param string $field  Field to search by.
	 * @param string $value  Value to match.
	 * @return string|null Record Id, or null when no match was found (or the query failed).
	 */
	private function find_existing_id( string $module, string $field, string $value ): ?string {
		$soql   = sprintf( "SELECT Id FROM %s WHERE %s = '%s' LIMIT 1", $module, $field, $this->escape_soql_value( $value ) );
		$result = $this->request( 'GET', 'query/?q=' . rawurlencode( $soql ) );

		if ( 'ok' === $result['status'] && ! empty( $result['data']['records'][0]['Id'] ) ) {
			return $result['data']['records'][0]['Id'];
		}

		return null;
	}

	/**
	 * Escapes a value for safe interpolation into a SOQL string literal.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private function escape_soql_value( string $value ): string {
		return str_replace( array( '\\', "'" ), array( '\\\\', "\\'" ), $value );
	}

	/**
	 * List fields for search entry for given module of a CRM.
	 *
	 * @param  string|null $module Module to get fields from.
	 * @return array Array of modules.
	 */
	public function list_fields_search_entry( ?string $module = 'Lead' ): array {
		$module       = ! empty( $module ) ? $module : 'Lead';
		$unique_field = $this->get_unique_field( $module );

		return array(
			array(
				'name'     => strtolower( $unique_field ),
				'value'    => $unique_field,
				'label'    => 'Email' === $unique_field ? __( 'Email', 'formscrm' ) : __( 'Name', 'formscrm' ),
				'required' => false,
			),
		);
	}

	/**
	 * Map a search field ID to the API query param name.
	 *
	 * @internal Salesforce dedupes via SOQL directly in create_entry(); not applicable here.
	 * @param string $search_field Field ID from list_fields_search_entry.
	 * @return string
	 */
	public function determine_search_by( string $search_field ): string {
		return $search_field;
	}

	/**
	 * Check if an entry exists and create or update it.
	 *
	 * @internal create_entry() already performs the find-then-create/update logic for Salesforce.
	 * @param array  $data   Raw merge vars from form.
	 * @param string $module CRM module slug.
	 * @return array
	 */
	public function create_or_update_entry( array $data, string $module ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Required by interface.
		return array();
	}

	/**
	 * Sends an authenticated request to the Salesforce REST API.
	 *
	 * @param string     $method   HTTP method: GET, POST, PATCH.
	 * @param string     $endpoint Path relative to `services/data/{version}/`.
	 * @param array|null $body     Request body for POST/PATCH requests.
	 * @return array 'status' => 'ok'|'error', 'data' => response array or error message.
	 */
	private function request( string $method, string $endpoint, ?array $body = null ): array {
		$token = $this->get_token_manager()->get_valid_access_token();

		if ( empty( $token['access_token'] ) || empty( $token['instance_url'] ) ) {
			return array(
				'status' => 'error',
				'data'   => $this->get_error_message(),
			);
		}

		$url  = trailingslashit( $token['instance_url'] ) . 'services/data/' . $this->get_api_version() . '/' . ltrim( $endpoint, '/' );
		$args = array(
			'method'  => $method,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token['access_token'],
				'Content-Type'  => 'application/json',
			),
			'timeout' => 60,
		);

		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			formscrm_error_admin_message( 'ERROR', $response->get_error_message() );
			return array(
				'status' => 'error',
				'data'   => $response->get_error_message(),
			);
		}

		$code     = (int) wp_remote_retrieve_response_code( $response );
		$body_raw = wp_remote_retrieve_body( $response );
		$decoded  = '' !== $body_raw ? json_decode( $body_raw, true ) : null;

		if ( $code < 200 || $code >= 300 ) {
			$message = $this->build_error_message( $decoded, $code );
			formscrm_error_admin_message( 'ERROR', $message );
			return array(
				'status' => 'error',
				'data'   => $message,
			);
		}

		return array(
			'status' => 'ok',
			'data'   => $decoded,
		);
	}

	/**
	 * Salesforce REST API version to use, filterable per site.
	 *
	 * @return string
	 */
	private function get_api_version(): string {
		return apply_filters( 'formscrm_salesforce_api_version', 'v60.0' );
	}

	/**
	 * Human-readable message for a missing/invalid connection, from the token manager.
	 *
	 * @return string
	 */
	private function get_error_message(): string {
		$error = $this->get_token_manager()->get_last_error();
		return '' !== $error ? $error : __( 'Salesforce is not connected. Go to FormsCRM Settings to connect your account.', 'formscrm' );
	}

	/**
	 * Builds a human-readable error message from a Salesforce error response body.
	 *
	 * @param mixed $decoded Decoded JSON response body.
	 * @param int   $code    HTTP status code.
	 * @return string
	 */
	private function build_error_message( $decoded, int $code ): string {
		if ( is_array( $decoded ) && ! empty( $decoded[0]['message'] ) ) {
			$error_code = ! empty( $decoded[0]['errorCode'] ) ? $decoded[0]['errorCode'] : '';
			return sprintf( 'HTTP %d: %s (%s)', $code, $decoded[0]['message'], $error_code );
		}

		/* translators: %d: HTTP status code. */
		return sprintf( __( 'HTTP %d: Salesforce request failed.', 'formscrm' ), $code );
	}
}
