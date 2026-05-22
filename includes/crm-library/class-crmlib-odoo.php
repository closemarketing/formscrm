<?php
/**
 * Odoo CRM connector
 *
 * @package    WordPress
 * @author     CloseTechnology
 * @copyright 2026 CloseTechnology
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

class CRMLIB_Odoo {
	/**
	 * Odoo endpoint URL.
	 *
	 * @var string
	 */
	private $url;

	/**
	 * Odoo database name.
	 *
	 * @var string
	 */
	private $db;

	/**
	 * Odoo username.
	 *
	 * @var string
	 */
	private $username;

	/**
	 * Odoo password / API token.
	 *
	 * @var string
	 */
	private $password;

	/**
	 * Authenticated user id.
	 *
	 * @var int
	 */
	private $uid;

	/**
	 * Returns available Odoo modules for mapping.
	 *
	 * @param array $settings CRM settings.
	 * @return array
	 */
	public function list_modules( $settings ) {
		return array(
			array(
				'value' => 'res.partner',
				'label' => __( 'Odoo Contact', 'formscrm' ),
			),
			array(
				'value' => 'crm.lead',
				'label' => __( 'Odoo Lead', 'formscrm' ),
			),
			array(
				'value' => 'sale.order',
				'label' => __( 'Odoo Sale Order', 'formscrm' ),
			),
		);
	}

	/**
	 * Logins to Odoo.
	 *
	 * @param array $settings CRM settings.
	 * @return bool
	 */
	public function login( $settings ) {
		if ( ! $this->prepare_connection( $settings ) ) {
			return false;
		}

		$login = $this->json_rpc(
			'common',
			'login',
			array(
				$this->db,
				$this->username,
				$this->password,
			)
		);

		if ( empty( $login ) || ! isset( $login['result'] ) || empty( $login['result'] ) ) {
			return false;
		}

		$this->uid = intval( $login['result'] );
		return true;
	}

	/**
	 * List available fields for a given module.
	 *
	 * @param array  $settings CRM settings.
	 * @param string $module   Module name.
	 * @return array
	 */
	public function list_fields( $settings, $module ) {
		$module = ! empty( $module ) ? $module : 'res.partner';
		$fields = array();

		switch ( $module ) {
			case 'crm.lead':
				$fields = array(
					array( 'name' => 'name', 'label' => __( 'Lead Name', 'formscrm' ), 'required' => true ),
					array( 'name' => 'contact_name', 'label' => __( 'Contact Name', 'formscrm' ), 'required' => false ),
					array( 'name' => 'email_from', 'label' => __( 'Email', 'formscrm' ), 'required' => false ),
					array( 'name' => 'phone', 'label' => __( 'Phone', 'formscrm' ), 'required' => false ),
					array( 'name' => 'description', 'label' => __( 'Description', 'formscrm' ), 'required' => false ),
				);
				break;
			case 'sale.order':
				$fields = array(
					array( 'name' => 'partner_id|id', 'label' => __( 'Customer ID', 'formscrm' ), 'required' => false ),
					array( 'name' => 'partner_id|name', 'label' => __( 'Customer Name', 'formscrm' ), 'required' => false ),
					array( 'name' => 'partner_invoice_id|name', 'label' => __( 'Billing Address', 'formscrm' ), 'required' => false ),
					array( 'name' => 'amount_total', 'label' => __( 'Total Amount', 'formscrm' ), 'required' => false ),
				);
				break;
			case 'res.partner':
			default:
				$fields = array(
					array( 'name' => 'name', 'label' => __( 'Name', 'formscrm' ), 'required' => true ),
					array( 'name' => 'email', 'label' => __( 'Email', 'formscrm' ), 'required' => false ),
					array( 'name' => 'phone', 'label' => __( 'Phone', 'formscrm' ), 'required' => false ),
					array( 'name' => 'street', 'label' => __( 'Street', 'formscrm' ), 'required' => false ),
					array( 'name' => 'city', 'label' => __( 'City', 'formscrm' ), 'required' => false ),
					array( 'name' => 'zip', 'label' => __( 'ZIP', 'formscrm' ), 'required' => false ),
				);
				break;
		}

		return $fields;
	}

	/**
	 * Creates an entry in Odoo.
	 *
	 * @param array $settings CRM settings.
	 * @param array $merge_vars Form field mapping values.
	 * @return array
	 */
	public function create_entry( $settings, $merge_vars ) {
		if ( ! $this->prepare_connection( $settings ) ) {
			formscrm_debug_message( 'Odoo create_entry failed: missing connection settings.' );
			return array(
				'status'  => 'error',
				'message' => __( 'Missing Odoo connection settings.', 'formscrm' ),
			);
		}

		$module = isset( $settings['fc_crm_module'] ) ? sanitize_text_field( $settings['fc_crm_module'] ) : '';
		formscrm_debug_message( 'Odoo create_entry module: ' . $module );
		if ( empty( $module ) ) {
			return array(
				'status'  => 'error',
				'message' => __( 'Odoo module is not configured.', 'formscrm' ),
			);
		}

		$login = $this->json_rpc(
			'common',
			'login',
			array(
				$this->db,
				$this->username,
				$this->password,
			)
		);

		if ( empty( $login ) || ! isset( $login['result'] ) || empty( $login['result'] ) ) {
			formscrm_debug_message( 'Odoo authentication failed for user: ' . $this->username );
			return array(
				'status'  => 'error',
				'message' => __( 'Odoo authentication failed.', 'formscrm' ),
			);
		}

		$this->uid = intval( $login['result'] );

		// UTM fields in crm.lead are Many2one relations in Odoo.
		$utm_map = array(
			'utm_source'   => array( 'field' => 'source_id',   'model' => 'utm.source' ),
			'utm_medium'   => array( 'field' => 'medium_id',   'model' => 'utm.medium' ),
			'utm_campaign' => array( 'field' => 'campaign_id', 'model' => 'utm.campaign' ),
		);

		$fields = array();
		foreach ( $merge_vars as $element ) {
			if ( empty( $element['name'] ) ) {
				continue;
			}

			$value = isset( $element['value'] ) ? $element['value'] : '';
			if ( is_array( $value ) ) {
				$value = implode( ',', $value );
			}

			if ( 'crm.lead' === $module && isset( $utm_map[ $element['name'] ] ) && ! empty( $value ) ) {
				$utm_id = $this->resolve_utm_id( $utm_map[ $element['name'] ]['model'], (string) $value );
				if ( $utm_id ) {
					$fields[ $utm_map[ $element['name'] ]['field'] ] = $utm_id;
				}
				continue;
			}

			if ( false !== strpos( $element['name'], '|' ) ) {
				$parts = explode( '|', $element['name'] );
				if ( count( $parts ) === 2 ) {
					$fields[ $parts[0] ][ $parts[1] ] = (string) $value;
					continue;
				}
			}

			$fields[ $element['name'] ] = (string) $value;
		}

		$result = $this->json_rpc(
			'object',
			'execute_kw',
			array(
				$this->db,
				$this->uid,
				$this->password,
				$module,
				'create',
				array( $fields ),
				new stdClass(),
			)
		);

		if ( isset( $result['error'] ) ) {
			formscrm_debug_message( array( 'odoo_error' => $result['error'], 'fields' => $fields ) );
			return array(
				'status'  => 'error',
				'message' => isset( $result['error']['message'] ) ? $result['error']['message'] : __( 'Odoo error creating record.', 'formscrm' ),
			);
		}

		if ( isset( $result['result'] ) && is_int( $result['result'] ) ) {
			formscrm_debug_message( 'Odoo record created: ' . $result['result'] );
			return array(
				'status'  => 'ok',
				'message' => __( 'success', 'formscrm' ),
				'id'      => $result['result'],
			);
		}

		return array(
			'status'  => 'error',
			'message' => __( 'Unexpected Odoo response.', 'formscrm' ),
		);
	}

	/**
	 * Prepares connection settings.
	 *
	 * @param array $settings CRM settings.
	 * @return bool
	 */
	private function prepare_connection( $settings ) {
		$this->url      = isset( $settings['fc_crm_url'] ) ? untrailingslashit( trim( $settings['fc_crm_url'] ) ) : '';
		$this->db       = isset( $settings['fc_crm_odoodb'] ) ? sanitize_text_field( $settings['fc_crm_odoodb'] ) : '';
		$this->username = isset( $settings['fc_crm_username'] ) ? sanitize_text_field( $settings['fc_crm_username'] ) : '';
		$this->password = '';

		if ( ! empty( $settings['fc_crm_apipassword'] ) ) {
			$this->password = sanitize_text_field( $settings['fc_crm_apipassword'] );
			$this->log_debug( 'Odoo connection using fc_crm_apipassword.' );
		} elseif ( ! empty( $settings['fc_crm_password'] ) ) {
			$this->password = sanitize_text_field( $settings['fc_crm_password'] );
			$this->log_debug( 'Odoo connection using fc_crm_password fallback.' );
		}

		return ! empty( $this->url ) && ! empty( $this->db ) && ! empty( $this->username ) && ! empty( $this->password );
	}

	/**
	 * Sends a JSON-RPC request to Odoo.
	 *
	 * @param string $service Odoo service name.
	 * @param string $method  Odoo method.
	 * @param array  $params  Method parameters.
	 * @return array|null
	 */
	private function json_rpc( $service, $method, $params ) {
		$endpoint = $this->url . '/jsonrpc';
		$payload  = array(
			'jsonrpc' => '2.0',
			'method'  => 'call',
			'params'  => array(
				'service' => $service,
				'method'  => $method,
				'args'    => $params,
			),
			'id'      => time(),
		);

		$args = wp_json_encode( $payload );
		if ( false === $args ) {
			return null;
		}

		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $endpoint );
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $args );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $curl, CURLOPT_TIMEOUT, 30 );

		$response = curl_exec( $curl );
		$errno    = curl_errno( $curl );
		$error    = curl_error( $curl );
		curl_close( $curl );

		if ( $errno ) {
			$this->log_debug( 'Odoo curl error: ' . $error );
			$this->log_debug( 'Odoo request payload: ' . $args );
			return null;
		}

		$this->log_debug( 'Odoo request payload: ' . $args );
		$this->log_debug( 'Odoo response: ' . $response );

		$result = json_decode( $response, true );
		if ( null === $result ) {
			$this->log_debug( 'Odoo invalid json response: ' . $response );
		}

		return $result;
	}

	/**
	 * Resolves a UTM name to its Odoo record ID, creating it if it does not exist.
	 *
	 * @param string $model Odoo UTM model (e.g. 'utm.source').
	 * @param string $name  UTM value name (e.g. 'google').
	 * @return int|false Record ID or false on failure.
	 */
	private function resolve_utm_id( $model, $name ) {
		$search = $this->json_rpc(
			'object',
			'execute_kw',
			array(
				$this->db,
				$this->uid,
				$this->password,
				$model,
				'search_read',
				array( array( array( 'name', '=', $name ) ) ),
				array( 'fields' => array( 'id' ), 'limit' => 1 ),
			)
		);

		if ( ! empty( $search['result'][0]['id'] ) ) {
			return intval( $search['result'][0]['id'] );
		}

		$create = $this->json_rpc(
			'object',
			'execute_kw',
			array(
				$this->db,
				$this->uid,
				$this->password,
				$model,
				'create',
				array( array( 'name' => $name ) ),
				new stdClass(),
			)
		);

		if ( ! empty( $create['result'] ) && is_int( $create['result'] ) ) {
			return intval( $create['result'] );
		}

		return false;
	}

	/**
	 * Logs debug messages when WP_DEBUG is active.
	 *
	 * @param string $message Message to log.
	 * @return void
	 */
	private function log_debug( $message ) {
		if ( function_exists( 'formscrm_debug_message' ) ) {
			formscrm_debug_message( $message );
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'FORMSCRM ODOO: ' . $message );
		}
	}
}
