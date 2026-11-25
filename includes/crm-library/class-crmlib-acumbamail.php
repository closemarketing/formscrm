<?php
/**
 * AcumbaMail connect library
 *
 * API DOCS: https://acumbamail.com/apidoc/
 * Has functions to login, list fields and create leadº
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.0.0
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
 */

/**
 * Class for AcumbaMail connection.
 */
class CRMLIB_AcumbaMail {
	/**
	 * Posts information from AcumbaMail CRM
	 *
	 * @param string $apikey  API Authentication.
	 * @param string $module  URL for module.
	 * @param string $data    Params to send to API.
	 * @return array
	 */
	private function post( $apikey, $module, $data = array() ) {
		$url = 'https://acumbamail.com/api/1/' . $module . '/';

		$fields = array(
			'auth_token'    => $apikey,
			'response_type' => 'json',
		);

		if ( 0 < count( $data ) ) {
			$fields = array_merge( $fields, $data );
		}

		$args = array(
			'method'      => 'POST',
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(
				'header' => 'Content-type: application/x-www-form-urlencoded',
			),
			'body'        => $fields,
		);

		$response = wp_remote_post( $url, $args );

		// The API allows 5 requests per second: wait for the window to reset and retry once.
		if ( ! is_wp_error( $response ) && 429 === (int) wp_remote_retrieve_response_code( $response ) ) {
			formscrm_debug_message( 'AcumbaMail rate limit reached (' . $module . '), retrying once.' );
			sleep( 1 );
			$response = wp_remote_post( $url, $args );
		}

		// Never log the request: it carries the API token.
		if ( is_wp_error( $response ) ) {
			formscrm_debug_message( 'AcumbaMail ' . $module . ' request failed: ' . $response->get_error_message() );
		} else {
			formscrm_debug_message( 'AcumbaMail ' . $module . ' responded HTTP ' . wp_remote_retrieve_response_code( $response ) );
		}

		$code = intval( wp_remote_retrieve_response_code( $response ) / 100 );
		if ( is_wp_error( $response ) ) {
			return array(
				'status' => 'error',
				'data'   => $response,
			);
		} elseif ( 2 !== $code ) {
			return array(
				'status' => 'error',
				'data'   => 'No data.',
			);
		} else {
			$json = json_decode( $response['body'], true );
			if ( is_array( $json ) ) {
				return array(
					'status' => 'ok',
					'data'   => $json,
				);
			} else {
				return array(
					'status' => 'error',
					'data'   => $response,
				);
			}
		}
	}

	/**
	 * Lists cached for the current request, by API key hash.
	 *
	 * @var array
	 */
	private static $lists_cache = array();

	/**
	 * Gets the lists of the account, cached.
	 *
	 * Building a feed screen asks for the lists several times (login, module
	 * dropdown, fields, module id) and the API only allows 5 requests per second,
	 * so extra calls were rejected and the CRM Module dropdown came out empty.
	 * Successful results are kept for the request and for one minute.
	 *
	 * @param string $apikey API key for connection.
	 * @return array Same format as post().
	 */
	private function get_lists( $apikey ) {
		if ( '' === (string) $apikey ) {
			return array(
				'status' => 'error',
				'data'   => 'No API key.',
			);
		}

		$hash = md5( (string) $apikey );

		if ( isset( self::$lists_cache[ $hash ] ) ) {
			return self::$lists_cache[ $hash ];
		}

		$transient_key = 'formscrm_acumba_lists_' . $hash;
		$cached        = get_transient( $transient_key );
		if ( is_array( $cached ) && ! empty( $cached ) ) {
			self::$lists_cache[ $hash ] = array(
				'status' => 'ok',
				'data'   => $cached,
			);
			return self::$lists_cache[ $hash ];
		}

		$result = $this->post( $apikey, 'getLists' );

		if ( 'ok' === $result['status'] && ! empty( $result['data'] ) && is_array( $result['data'] ) ) {
			self::$lists_cache[ $hash ] = $result;
			set_transient( $transient_key, $result['data'], MINUTE_IN_SECONDS );
		}

		return $result;
	}

	/**
	 * Gets module id from AcumbaMail
	 *
	 * @param string $apikey API key for connection.
	 * @param string $module Module name.
	 * @return int
	 */
	private function get_module_id( $apikey, $module ) {
		$module_id        = null;
		$get_result_lists = $this->get_lists( $apikey );

		if ( ! empty( $get_result_lists['data'] ) && is_array( $get_result_lists['data'] ) ) {
			foreach ( $get_result_lists['data'] as $key => $list ) {
				if ( isset( $list['name'] ) && $module === $list['name'] ) {
					$module_id = $key;
				}
			}
		}
		return $module_id;
	}

	/**
	 * Logins to a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return false or id     returns false if cannot login and string if gets token
	 */
	public function login( $settings ) {
		$apikey     = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$get_result = $this->get_lists( $apikey );

		if ( $apikey && ! empty( $get_result['data'] ) ) {
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
		$apikey     = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$get_result = $this->get_lists( $apikey );
		$modules    = array();

		if ( ! empty( $get_result['data'] ) && is_array( $get_result['data'] ) ) {
			$modules[] = array(
				'name'  => 'dinamic',
				'label' => __( 'Dynamic list in field (use admin_label for fields)', 'formscrm' ),
			);

			foreach ( $get_result['data'] as $key => $list ) {
				$modules[] = array(
					'name'  => $key,
					'label' => $list['name'],
				);
			}
		}
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
		$module = formscrm_get_module( 'contact', $settings );

		formscrm_debug_message( __( 'Module active:', 'formscrm' ) . $module );

		$fields     = array();
		$get_result = $this->post(
			$apikey,
			'getMergeFields',
			array(
				'list_id' => $this->get_module_id( $apikey, $module ),
			)
		);
		if ( ! empty( $get_result['data'] ) && is_array( $get_result['data'] ) ) {
			foreach ( $get_result['data'] as $key => $type ) {
				$fields[] = array(
					'name'     => $key,
					'label'    => $key,
					'required' => 'email' === $type ? true : false,
				);
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
		$apikey = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$module = isset( $settings['fc_crm_module'] ) ? $settings['fc_crm_module'] : '';

		$subscriber = array();
		if ( empty( $merge_vars ) ) {
			return array(
				'status'  => 'error',
				'message' => __( 'No data.', 'formscrm' ),
			);
		}
		foreach ( $merge_vars as $merge_var ) {
			$subscriber[ $merge_var['name'] ] = $merge_var['value'];
		}
		if ( isset( $subscriber['list_id'] ) && is_array( $subscriber['list_id'] ) ) {
			$lists_to_subscribe = $subscriber['list_id'];
			unset( $subscriber['list_id'] );
			foreach ( $lists_to_subscribe as $list ) {
				if ( empty( $list ) ) {
					continue;
				}
				$result = $this->post(
					$apikey,
					'addSubscriber',
					array(
						'list_id'           => (int) $list,
						'merge_fields'      => $subscriber,
						'update_subscriber' => 1,
					)
				);
			}
		} else {
			$result = $this->post(
				$apikey,
				'addSubscriber',
				array(
					'list_id'      => $this->get_module_id( $apikey, $module ),
					'merge_fields' => $subscriber,
				)
			);
		}

		// Initialize default error response in case $result is not set.
		$response_result = array(
			'status'  => 'error',
			'message' => 'Unknown error',
		);

		if ( isset( $result ) && 'ok' === $result['status'] ) {
			$response_result = array(
				'status'  => 'ok',
				'message' => 'success',
				'id'      => isset( $result['data']['subscriber_id'] ) ? $result['data']['subscriber_id'] : '',
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
