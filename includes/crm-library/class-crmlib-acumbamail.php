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
	 * @param  array $settings settings from Gravity Forms options.
	 * @return false or id     returns false if cannot login and string if gets token
	 */
	public function login( $settings ) {
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
	 * @param  array $settings settings from Gravity Forms options.
	 * @return array           returns an array of mudules
	 */
	public function list_modules( $settings ) {
		$apikey     = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$get_result = $this->post( $apikey, 'getLists' );

		if ( ! empty( $get_result['data'] ) ) {
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
		$module = formscrm_get_module();

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
						'list_id'      => $list,
						'merge_fields' => $subscriber,
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
		if ( 'ok' === $result['status'] ) {
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
