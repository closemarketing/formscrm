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
	 * @param string $crm        CRM.
	 * @param string $error      Error to send.
	 * @param array  $data       Data of error.
	 * @param string $url        API URL.
	 * @param string $json       JSON request.
	 * @param array  $form_info  Form information (form_id, form_name, form_type).
	 * @return void
	 */
	function formscrm_debug_email_lead( $crm, $error, $data, $url = '', $json = '', $form_info = array() ) {
		// Get custom email or fallback to admin email.
		$custom_email = get_option( 'formscrm_error_notification_email', '' );
		$to           = ! empty( $custom_email ) ? $custom_email : get_option( 'admin_email' );

		// Subject with site name.
		$site_name = get_bloginfo( 'name' );
		$subject   = sprintf(
			'[%s] FormsCRM - %s',
			$site_name,
			__( 'Error creating the Lead', 'formscrm' )
		);

		// Body with site information.
		$body  = '<html><body style="font-family: Arial, sans-serif; color: #333;">';
		$body .= '<div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">';

		// Header.
		$body .= '<h2 style="color: #d32f2f; margin-top: 0;">' . __( 'FormsCRM Error Report', 'formscrm' ) . '</h2>';

		// Site Information.
		$body .= '<div style="background-color: #f5f5f5; padding: 15px; border-radius: 3px; margin-bottom: 20px;">';
		$body .= '<h3 style="margin-top: 0; color: #666;">' . __( 'Site Information', 'formscrm' ) . '</h3>';
		$body .= '<table style="width: 100%; border-collapse: collapse;">';
		$body .= '<tr><td style="padding: 5px 0;"><strong>' . __( 'Site Name:', 'formscrm' ) . '</strong></td><td style="padding: 5px 0;">' . esc_html( $site_name ) . '</td></tr>';
		$body .= '<tr><td style="padding: 5px 0;"><strong>' . __( 'Site URL:', 'formscrm' ) . '</strong></td><td style="padding: 5px 0;">' . esc_html( get_site_url() ) . '</td></tr>';
		$body .= '<tr><td style="padding: 5px 0;"><strong>' . __( 'Time:', 'formscrm' ) . '</strong></td><td style="padding: 5px 0;">' . esc_html( current_time( 'Y-m-d H:i:s' ) ) . '</td></tr>';
		$body .= '</table>';
		$body .= '</div>';

		// Form Information.
		if ( ! empty( $form_info ) ) {
			$body .= '<div style="background-color: #e3f2fd; padding: 15px; border-radius: 3px; margin-bottom: 20px;">';
			$body .= '<h3 style="margin-top: 0; color: #1976d2;">' . __( 'Form Information', 'formscrm' ) . '</h3>';
			$body .= '<table style="width: 100%; border-collapse: collapse;">';

			if ( isset( $form_info['form_type'] ) ) {
				$body .= '<tr><td style="padding: 5px 0;"><strong>' . __( 'Form Type:', 'formscrm' ) . '</strong></td><td style="padding: 5px 0;">' . esc_html( $form_info['form_type'] ) . '</td></tr>';
			}
			if ( isset( $form_info['form_id'] ) ) {
				$body .= '<tr><td style="padding: 5px 0;"><strong>' . __( 'Form ID:', 'formscrm' ) . '</strong></td><td style="padding: 5px 0;">' . esc_html( $form_info['form_id'] ) . '</td></tr>';
			}
			if ( isset( $form_info['form_name'] ) ) {
				$body .= '<tr><td style="padding: 5px 0;"><strong>' . __( 'Form Name:', 'formscrm' ) . '</strong></td><td style="padding: 5px 0;">' . esc_html( $form_info['form_name'] ) . '</td></tr>';
			}
			if ( isset( $form_info['entry_id'] ) ) {
				$body .= '<tr><td style="padding: 5px 0;"><strong>' . __( 'Entry ID:', 'formscrm' ) . '</strong></td><td style="padding: 5px 0;">' . esc_html( $form_info['entry_id'] ) . '</td></tr>';
			}

			$body .= '</table>';
			$body .= '</div>';
		}

		// Error Information.
		$body .= '<div style="background-color: #ffebee; padding: 15px; border-radius: 3px; margin-bottom: 20px;">';
		$body .= '<h3 style="margin-top: 0; color: #d32f2f;">' . __( 'Error Details', 'formscrm' ) . '</h3>';
		$body .= '<table style="width: 100%; border-collapse: collapse;">';
		$body .= '<tr><td style="padding: 5px 0;"><strong>' . __( 'CRM:', 'formscrm' ) . '</strong></td><td style="padding: 5px 0;">' . esc_html( $crm ) . '</td></tr>';
		$body .= '<tr><td style="padding: 5px 0; vertical-align: top;"><strong>' . __( 'Error:', 'formscrm' ) . '</strong></td><td style="padding: 5px 0;">' . esc_html( $error ) . '</td></tr>';
		$body .= '</table>';
		$body .= '</div>';

		// Lead Data.
		$body .= '<div style="background-color: #fff3e0; padding: 15px; border-radius: 3px; margin-bottom: 20px;">';
		$body .= '<h3 style="margin-top: 0; color: #f57c00;">' . __( 'Lead Data', 'formscrm' ) . '</h3>';
		$body .= '<table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd;">';
		foreach ( $data as $dataitem ) {
			$body .= '<tr style="border-bottom: 1px solid #eee;">';
			$body .= '<td style="padding: 8px; background-color: #fafafa; width: 40%;"><strong>' . esc_html( $dataitem['name'] ) . '</strong></td>';
			$body .= '<td style="padding: 8px;">' . esc_html( $dataitem['value'] ) . '</td>';
			$body .= '</tr>';
		}
		$body .= '</table>';
		$body .= '</div>';

		// Technical Details.
		if ( $url || $json ) {
			$body .= '<div style="background-color: #f5f5f5; padding: 15px; border-radius: 3px; margin-bottom: 20px;">';
			$body .= '<h3 style="margin-top: 0; color: #666;">' . __( 'Technical Details', 'formscrm' ) . '</h3>';

			if ( $url ) {
				$body .= '<p><strong>' . __( 'API URL:', 'formscrm' ) . '</strong><br/>';
				$body .= '<code style="background-color: #fff; padding: 5px; display: block; word-break: break-all;">' . esc_html( $url ) . '</code></p>';
			}

			if ( $json ) {
				$body .= '<p><strong>' . __( 'Request JSON:', 'formscrm' ) . '</strong><br/>';
				$body .= '<code style="background-color: #fff; padding: 10px; display: block; word-break: break-all; font-size: 11px;">' . esc_html( $json ) . '</code></p>';
			}

			$body .= '</div>';
		}

		// Footer.
		$body .= '<div style="text-align: center; padding-top: 20px; border-top: 1px solid #ddd; color: #999; font-size: 12px;">';
		$body .= '<p>FormsCRM - ' . __( 'Connects Forms with CRM, ERP and Email Marketing', 'formscrm' ) . '</p>';
		$body .= '<p><a href="https://close.technology" style="color: #1976d2; text-decoration: none;">close.technology</a></p>';
		$body .= '</div>';

		$body .= '</div></body></html>';

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
