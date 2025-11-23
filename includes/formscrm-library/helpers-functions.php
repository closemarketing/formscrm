<?php
/**
 * Debug functions
 *
 * Functions to debug library CRM
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.0.0
 */

if ( ! function_exists( 'formscrm_get_api_class' ) ) {
	/**
	 * Include library connector
	 *
	 * @param string $crmtype Type of CRM.
	 * @return object|void
	 */
	function formscrm_get_api_class( $crm_type ) {
		if ( isset( $crm_type ) ) {
			$crmname      = strtolower( $crm_type );
			$crmclassname = str_replace( ' ', '', $crmname );
			$crmclassname = 'CRMLIB_' . strtoupper( $crmclassname );
			$crmname      = str_replace( ' ', '_', $crmname );

			$array_path = formscrm_get_crmlib_path();

			if ( isset( $array_path[ $crmname ] ) ) {
				include_once $array_path[ $crmname ];
				formscrm_debug_message( $array_path[ $crmname ] );
			}

			if ( class_exists( $crmclassname ) ) {
				return new $crmclassname();
			}
		}
	}
}

if ( ! function_exists( 'formscrm_debug_message' ) ) {
	/**
	 * Debug message in log
	 *
	 * @param array $message Message.
	 * @return void
	 */
	function formscrm_debug_message( $message ) {
		if ( true === WP_DEBUG ) {
			if ( is_array( $message ) ) {
				$message = print_r( $message, true ); //phpcs:ignore
			}
			error_log( 'FORMSCRM: ' . esc_html__( 'Message Debug Mode', 'formscrm' ) . ' ' . esc_html( $message ) );
		}
	}
}

if ( ! function_exists( 'formscrm_get_module' ) ) {
	/**
	 * Gets default module in forms
	 *
	 * @param string $default_module To avoid.
	 * @return string
	 */
	function formscrm_get_module( $default_module ) {
		if ( isset( $_POST['_gform_setting_fc_crm_module'] ) ) {
			$module = sanitize_text_field( $_POST['_gform_setting_fc_crm_module'] );
		} elseif ( isset( $settings['fc_crm_module'] ) ) {
			$module = $settings['fc_crm_module'];
		} else {
			$module = $default_module;
		}

		return $module;
	}
}

if ( ! function_exists( 'formscrm_error_admin_message' ) ) {
	/**
	 * Shows in WordPress error message
	 *
	 * @param string $code Code of error.
	 * @param string $message Message.
	 * @return void
	 */
	function formscrm_error_admin_message( $code, $message ) {
		if ( true === WP_DEBUG ) {
			error_log( 'FORMSCRM: API ERROR ' . esc_html( $code ) . ': ' . esc_html( $message ) );
		}
	}
}

// * Sends an email to administrator when it not creates the lead
if ( ! function_exists( 'formscrm_debug_email_lead' ) ) {
	/**
	 * Sends error to admin
	 *
	 * @param string $crm   CRM.
	 * @param string $error Error to send.
	 * @param array  $data  Data of error.
	 * @return void
	 */
	function formscrm_debug_email_lead( $crm, $error, $data, $url = '', $json = '' ) {
		$to      = get_option( 'admin_email' );
		$subject = 'FormsCRM - ' . __( 'Error creating the Lead', 'formscrm' );
		$body    = '<p>' . __( 'There was an error creating the Lead in the CRM', 'formscrm' ) . ' ' . $crm . ':</p><p><strong>' . $error . '</strong></p><p>' . __( 'Lead Data', 'formscrm' ) . ':</p>';
		foreach ( $data as $dataitem ) {
			$body .= '<p><strong>' . $dataitem['name'] . ': </strong>' . $dataitem['value'] . '</p>';
		}
		$body .= '</br/><br/>';
		if ( $url ) {
			$body .= '<p>URL: ' . $url . '</p>';
		}
		if ( $url ) {
			$body .= '<p>JSON: ' . $json . '</p>';
		}
		$body   .= 'FormsCRM';
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		wp_mail( $to, $subject, $body, $headers );
	}
}

if ( ! function_exists( 'formscrm_testserver' ) ) {
	/**
	 * Error message
	 *
	 * @return void
	 */
	function formscrm_testserver() {
		// test curl.
		if ( ! function_exists( 'curl_version' ) && true === WP_DEBUG ) {
			error_log( 'FORMSCRM: ' . __( 'curl is not Installed in your server. It is needed to work with CRM Libraries.', 'formscrm' ) );
		}
	}
}

if ( ! function_exists( 'formscrm_check_url_crm' ) ) {
	/**
	 * Checks CRM URL to see that is correct
	 *
	 * @param string $url URL to check.
	 * @return url
	 */
	function formscrm_check_url_crm( $url ) {

		if ( ! isset( $url ) ) {
			$url = '';
		}
		if ( substr( $url, -1 ) !== '/' ) {
			$url .= '/'; // adds slash to url.
		}

		return $url;
	}
}

if ( ! function_exists( 'formscrm_send_webhook' ) ) {
	/**
	 * Sends webhook
	 *
	 * @param string $settings Settings.
	 * @param array  $response Response from CRM.
	 * @return void
	 */
	function formscrm_send_webhook( $settings, $response ) {
		$webhook_url = isset( $settings['fc_crm_webhook'] ) ? $settings['fc_crm_webhook'] : '';
		if ( empty( $webhook_url ) ) {
			return;
		}
		$module   = isset( $response['module'] ) ? $response['module'] : '';
		$ids      = isset( $response['id'] ) ? $response['id'] : '';
		$ids      = explode( '|', $ids );
		$entry_id = end( $ids );
		$entry_id = str_replace( 'Deal ', '', $entry_id );

		$body     = array(
			'hook' => array(
				'event'  => $module . '.saved',
				'target' => $webhook_url,
			),
			'data' => array(
				'id' => $entry_id,
			),
		);
		$response = wp_remote_post(
			$webhook_url,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);
		return array(
			'response' => $response,
			'request'  => $body,
		);
	}
}

if ( ! function_exists( 'formscrm_parse_field_mapping' ) ) {
	/**
	 * Parses raw field mapping definitions.
	 *
	 * Accepts strings in the format `crm_field = {field:key}` and returns an associative array.
	 *
	 * @param string $raw_mapping Raw mapping string from settings.
	 * @return array
	 */
	function formscrm_parse_field_mapping( $raw_mapping ) {
		if ( empty( $raw_mapping ) || ! is_string( $raw_mapping ) ) {
			return array();
		}

		$lines      = preg_split( '/\r\n|\r|\n/', $raw_mapping );
		$mapping    = array();

		foreach ( $lines as $line ) {
			$line = trim( $line );

			if ( '' === $line || 0 === strpos( $line, '#' ) || false === strpos( $line, '=' ) ) {
				continue;
			}

			list( $crm_field, $template ) = array_map( 'trim', explode( '=', $line, 2 ) );

			if ( '' === $crm_field || '' === $template ) {
				continue;
			}

			$mapping[ $crm_field ] = $template;
		}

		return $mapping;
	}
}
