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
	 * @param string $crm_type Type of CRM.
	 * @return object|void
	 */
	function formscrm_get_api_class( $crm_type ) {
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

if ( ! function_exists( 'formscrm_debug_message' ) ) {
	/**
	 * Debug message in log
	 *
	 * @param array<string, mixed>|string $message Message.
	 * @return void
	 */
	function formscrm_debug_message( $message ) {
		if ( true === WP_DEBUG ) {
			if ( is_array( $message ) ) {
				$message = print_r( $message, true ); //phpcs:ignore
			}
			error_log( 'FORMSCRM: ' . esc_html__( 'Message Debug Mode', 'formscrm' ) . ' ' . esc_html( $message ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}

if ( ! function_exists( 'formscrm_get_module' ) ) {
	/**
	 * Gets default module in forms
	 *
	 * @param string               $default_module To avoid.
	 * @param array<string, mixed> $settings Optional settings array.
	 * @return string
	 */
	function formscrm_get_module( $default_module, $settings = array() ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This function is called in GravityForms context where nonce is already verified.
		if ( isset( $_POST['_gform_setting_fc_crm_module'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$module = sanitize_text_field( wp_unslash( $_POST['_gform_setting_fc_crm_module'] ) );
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
			error_log( 'FORMSCRM: API ERROR ' . esc_html( $code ) . ': ' . esc_html( $message ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}

// * Sends an email to administrator when it not creates the lead
if ( ! function_exists( 'formscrm_alert_error' ) ) {
	/**
	 * Sends error to admin
	 *
	 * @param string               $crm CRM.
	 * @param string               $error Error to send.
	 * @param array<mixed>         $data Data of error.
	 * @param string               $url API URL.
	 * @param string               $json JSON request.
	 * @param array<string, mixed> $form_info Form information (form_id, form_name, form_type, entry_id).
	 * @return void
	 */
	function formscrm_alert_error( $crm, $error, $data, $url = '', $json = '', $form_info = array() ) {
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

		// Send to Slack if configured.
		formscrm_send_slack_notification( $crm, $error, $data, $url, $json, $form_info );
	}
}

if ( ! function_exists( 'formscrm_send_slack_notification' ) ) {
	/**
	 * Sends error notification to Slack
	 *
	 * @param string               $crm CRM name.
	 * @param string               $error Error message.
	 * @param array<mixed>         $data Lead data.
	 * @param string               $url API URL.
	 * @param string               $json JSON request.
	 * @param array<string, mixed> $form_info Form information.
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
					/* translators: %d: number of additional fields not shown */
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
						/* translators: %1$s: CRM name, %2$s: error message */
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
			error_log( 'FORMSCRM Slack Error: ' . $response->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			error_log( 'FORMSCRM Slack Error: HTTP ' . $response_code ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
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
		// Test curl.
		if ( ! function_exists( 'curl_version' ) && true === WP_DEBUG ) {
			error_log( 'FORMSCRM: ' . __( 'curl is not Installed in your server. It is needed to work with CRM Libraries.', 'formscrm' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}

if ( ! function_exists( 'formscrm_check_url_crm' ) ) {
	/**
	 * Checks CRM URL to see that is correct
	 *
	 * @param string $url URL to check.
	 * @return string
	 */
	function formscrm_check_url_crm( $url ) {
		return trailingslashit( sanitize_url( $url ) );
	}
}

if ( ! function_exists( 'formscrm_send_webhook' ) ) {
	/**
	 * Sends webhook
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @param array<string, mixed> $response Response from CRM.
	 * @return array<string, mixed>|void
	 */
	function formscrm_send_webhook( $settings, $response ) {
		$webhook_url = isset( $settings['fc_crm_webhook'] ) ? $settings['fc_crm_webhook'] : '';
		if ( ! $webhook_url ) {
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

if ( ! function_exists( 'formscrm_get_svg_icon' ) ) {
	/**
	 * Get SVG icon content.
	 *
	 * @param string $icon_name Icon name without extension.
	 * @param string $class_name Optional CSS class to add to SVG element.
	 * @return string SVG content or empty string if file not found.
	 */
	function formscrm_get_svg_icon( $icon_name, $class_name = '' ) {
		$icon_path = FORMSCRM_PLUGIN_PATH . 'includes/assets/icons/' . $icon_name . '.svg';
		if ( file_exists( $icon_path ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$svg_content = file_get_contents( $icon_path );
			if ( ! empty( $class_name ) ) {
				// Add class to SVG element.
				$svg_content = str_replace( '<svg', '<svg class="' . esc_attr( $class_name ) . '"', $svg_content );
			}
			return $svg_content;
		}
		return '';
	}
}
