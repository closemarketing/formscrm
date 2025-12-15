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
class CRMLIB_AcumbaMail implements CRMLIB_Interface {
	/**
	 * Posts information from AcumbaMail CRM
	 *
	 * @param string $apikey  API Authentication.
	 * @param string $module  URL for module.
	 * @param array<string, mixed> $data Params to send to API.
	 * @return array<string, mixed>
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

		$response = wp_remote_post(
			$url,
			array(
				'method'      => 'POST',
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => array(
					'header' => 'Content-type: application/x-www-form-urlencoded',
				),
				'body'        => $fields,
			)
		);
		error_log( '$fields' . print_r( $fields, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
		error_log( '$response' . print_r( $response, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r

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
	 * Gets module id from AcumbaMail
	 *
	 * @param string $apikey API key for connection.
	 * @param string $module Module name.
	 * @return int
	 */
	private function get_module_id( $apikey, $module ) {
		$module_id        = null;
		$get_result_lists = $this->post( $apikey, 'getLists' );

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
	 * @param array<string, mixed> $settings Settings from Gravity Forms options.
	 * @return bool True if login successful, false otherwise.
	 */
	public function login( array $settings ): bool {
		$apikey     = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$get_result = $this->post( $apikey, 'getLists' );

		if ( $apikey && ! empty( $get_result['data'] ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * List modules of a CRM
	 *
	 * @param array<string, mixed> $settings Settings from Gravity Forms options.
	 * @return array<int, array<string, string>> Array of modules.
	 */
	public function list_modules( array $settings ): array {
		$apikey     = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$get_result = $this->post( $apikey, 'getLists' );
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
	 * @param array<string, mixed> $settings Settings from Gravity Forms options.
	 * @return array<int, array<string, mixed>> Array of fields.
	 */
	public function list_fields( array $settings ): array {
		$apikey = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$module = formscrm_get_module( 'contact', $settings );

		formscrm_debug_message(
			array(
				'message' => __( 'Module active:', 'formscrm' ) . $module,
			)
		);

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
	 * @param array<string, mixed> $merge_vars Array of values for the entry.
	 * @param array<string, mixed> $settings Settings from Gravity Forms options.
	 * @return array<string, mixed> Response with status and message.
	 */
	public function create_entry( array $merge_vars, array $settings ): array {
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
			if ( isset( $merge_var['name'], $merge_var['value'] ) ) {
				$subscriber[ $merge_var['name'] ] = $merge_var['value'];
			}
		}
		if ( isset( $subscriber['list_id'] ) && is_array( $subscriber['list_id'] ) ) {
			$lists_to_subscribe = $subscriber['list_id'];
			unset( $subscriber['list_id'] );
			foreach ( $lists_to_subscribe as $list ) {
				if ( empty( $list ) ) {
					continue;
				}
				error_log( '$subscriber:' . print_r( $list, true ) . ' ' . print_r( $subscriber, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
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
