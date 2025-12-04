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
	 * @param array  $form_info  Form information (form_id, form_name, form_type, entry_id).
	 * @return void
	 */
	function formscrm_debug_email_lead( $crm, $error, $data, $url = '', $json = '', $form_info = array() ) {
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

		// Send to Slack if configured.
		formscrm_send_slack_notification( $crm, $error, $data, $url, $json, $form_info );
	}
}

if ( ! function_exists( 'formscrm_send_slack_notification' ) ) {
	/**
	 * Sends error notification to Slack
	 *
	 * @param string $crm        CRM name.
	 * @param string $error      Error message.
	 * @param array  $data       Lead data.
	 * @param string $url        API URL.
	 * @param string $json       JSON request.
	 * @param array  $form_info  Form information.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	function formscrm_send_slack_notification( $crm, $error, $data, $url = '', $json = '', $form_info = array() ) {
		$webhook_url = get_option( 'formscrm_slack_webhook_url', '' );

		// If no webhook URL is configured, skip.
		if ( empty( $webhook_url ) ) {
			return false;
		}

		// Build the Slack message.
		$site_name = get_bloginfo( 'name' );
		$site_url  = get_site_url();
		$timestamp = current_time( 'Y-m-d H:i:s' );

		// Build compact message text.
		$message_text = '';

		// Site information - one line.
		$message_text .= '*' . __( 'Site:', 'formscrm' ) . '* ' . $site_name . ' (' . $site_url . ')' . "\n";

		// Form information - one line.
		if ( ! empty( $form_info ) ) {
			$message_text .= '*' . __( 'Form:', 'formscrm' ) . '* ';
			$form_parts    = array();

			if ( isset( $form_info['form_type'] ) ) {
				$form_parts[] = $form_info['form_type'];
			}
			if ( isset( $form_info['form_name'] ) ) {
				$form_parts[] = $form_info['form_name'];
			}
			if ( isset( $form_info['form_id'] ) ) {
				$form_parts[] = 'ID: ' . $form_info['form_id'];
			}
			if ( isset( $form_info['entry_id'] ) ) {
				$form_parts[] = 'Entry: ' . $form_info['entry_id'];
			}

			$message_text .= implode( ' | ', $form_parts ) . "\n";
		}

		// Error information - one line.
		$message_text .= '*' . __( 'CRM:', 'formscrm' ) . '* ' . $crm . "\n";
		$message_text .= '*' . __( 'Error:', 'formscrm' ) . '* ' . $error . "\n";

		// Lead data preview - compact format (first 3 fields).
		if ( ! empty( $data ) && is_array( $data ) ) {
			$lead_preview = array_slice( $data, 0, 3 );
			$lead_parts   = array();

			foreach ( $lead_preview as $item ) {
				if ( isset( $item['name'] ) && isset( $item['value'] ) ) {
					$lead_parts[] = $item['name'] . ': ' . $item['value'];
				}
			}

			if ( ! empty( $lead_parts ) ) {
				$message_text .= '*' . __( 'Lead:', 'formscrm' ) . '* ' . implode( ' | ', $lead_parts );

				if ( count( $data ) > 3 ) {
					$message_text .= sprintf( __( ' ... (+%d more)', 'formscrm' ), count( $data ) - 3 );
				}

				$message_text .= "\n";
			}
		}

		// API URL - one line.
		if ( $url ) {
			$message_text .= '*' . __( 'API:', 'formscrm' ) . '* `' . $url . '`' . "\n";
		}

		// Build the Slack payload.
		$payload = array(
			'username'    => 'FormsCRM',
			'icon_emoji'  => ':warning:',
			'attachments' => array(
				array(
					'fallback'    => sprintf(
						__( 'FormsCRM Error: %1$s - %2$s', 'formscrm' ),
						$crm,
						$error
					),
					'color'       => 'danger',
					'title'       => __( '⚠️ FormsCRM Error Report', 'formscrm' ),
					'text'        => $message_text,
					'footer'      => 'FormsCRM',
					'footer_icon' => 'https://close.technology/wp-content/uploads/2023/12/close-technology-logo.png',
					'ts'          => strtotime( $timestamp ),
					'mrkdwn_in'   => array( 'text' ),
				),
			),
		);

		// Send to Slack.
		$response = wp_remote_post(
			$webhook_url,
			array(
				'body'    => wp_json_encode( $payload ),
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'FORMSCRM Slack Error: ' . $response->get_error_message() );
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			error_log( 'FORMSCRM Slack Error: HTTP ' . $response_code );
			return new WP_Error( 'slack_error', 'Slack returned HTTP ' . $response_code );
		}

		return true;
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
