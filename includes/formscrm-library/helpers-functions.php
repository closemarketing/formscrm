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
	 * @return object|null
	 */
	function formscrm_get_api_class( $crm_type ) {
		// Normalize CRM type.
		$crmname      = strtolower( trim( $crm_type ) );
		$crmclassname = str_replace( ' ', '', $crmname );
		$crmclassname = 'CRMLIB_' . ucfirst( $crmclassname );
		$crmname      = str_replace( ' ', '_', $crmname );

		formscrm_debug_message( 'Attempting to load CRM: ' . $crmname );

		$array_path = formscrm_get_crmlib_path();

		// Log available CRM paths for debugging.
		formscrm_debug_message( 'Available CRM paths: ' . wp_json_encode( array_keys( $array_path ) ) );

		if ( isset( $array_path[ $crmname ] ) ) {
			$file_path = $array_path[ $crmname ];

			// Verify file exists before including.
			if ( ! file_exists( $file_path ) ) {
				formscrm_debug_message( 'ERROR: CRM library file not found: ' . $file_path );
				return null;
			}

			include_once $file_path;
			formscrm_debug_message( 'Included CRM library: ' . $file_path );
		} else {
			formscrm_debug_message( 'ERROR: CRM path not registered for: ' . $crmname );
			return null;
		}

		// Verify class exists after including file.
		if ( class_exists( $crmclassname ) ) {
			formscrm_debug_message( 'Successfully created instance of: ' . $crmclassname );
			return new $crmclassname();
		}

		formscrm_debug_message( 'ERROR: CRM class not found: ' . $crmclassname );
		return null;
	}
}

if ( ! function_exists( 'formscrm_get_crm_settings' ) ) {
	/**
	 * Get CRM settings from WordPress options
	 *
	 * @param string $form_type Type of form (gravity, woocommerce, etc).
	 * @return array Settings array.
	 */
	function formscrm_get_crm_settings( $form_type = '' ) {
		$settings = array();

		// Try to get settings based on form type.
		if ( 'gravity' === $form_type || 'gravityforms' === $form_type ) {
			$settings = get_option( 'gravityformsaddon_formscrm_settings', array() );
		} elseif ( 'woocommerce' === $form_type ) {
			$settings = get_option( 'wc_formscrm', array() );
		} else {
			// Default to Gravity Forms settings as fallback.
			$settings = get_option( 'gravityformsaddon_formscrm_settings', array() );

			// Fallback to WooCommerce settings when Gravity Forms settings are empty.
			if ( empty( $settings ) ) {
				$settings = get_option( 'wc_formscrm', array() );
			}
		}

		formscrm_debug_message( 'Retrieved CRM settings for form type: ' . $form_type );

		return $settings;
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
			error_log( 'FORMSCRM: ' . esc_html__( 'Message Debug Mode', 'formscrm' ) . ' ' . esc_html( $message ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}

if ( ! function_exists( 'formscrm_get_module' ) ) {
	/**
	 * Gets default module in forms
	 *
	 * @param string $default_module To avoid.
	 * @param array  $settings       Optional settings array.
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
	 * @param string $crm        CRM.
	 * @param string $error      Error to send.
	 * @param array  $data       Data of error.
	 * @param string $url        API URL.
	 * @param string $json       JSON request.
	 * @param array  $form_info  Form information (form_id, form_name, form_type, entry_id).
	 * @return void
	 */
	function formscrm_alert_error( $crm, $error, $data, $url = '', $json = '', $form_info = array() ) {
		// Log error to database.
		global $formscrm_error_log;
		if ( isset( $formscrm_error_log ) && method_exists( $formscrm_error_log, 'insert_log' ) ) {
			$formscrm_error_log->insert_log( $crm, $error, $data, $url, $json, $form_info );
		}

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

if ( ! function_exists( 'formscrm_debug_email_lead' ) ) {
	/**
	 * Sends debug email for lead creation errors (backward compatibility wrapper)
	 *
	 * This function is a wrapper for formscrm_alert_error() to maintain backward
	 * compatibility with addon plugins that use the old function name.
	 *
	 * @param string $crm_type  CRM type.
	 * @param string $message   Error message.
	 * @param array  $merge_vars Lead data array.
	 * @return void
	 */
	function formscrm_debug_email_lead( $crm_type, $message, $merge_vars = array() ) {
		formscrm_alert_error( $crm_type, $message, $merge_vars );
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
	 * @return url
	 */
	function formscrm_check_url_crm( $url ) {
		return trailingslashit( sanitize_url( $url ) );
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
		if ( ! $webhook_url ) {
			return;
		}
		$module    = isset( $response['module'] ) ? $response['module'] : '';
		$ids       = isset( $response['id'] ) ? $response['id'] : '';
		$ids       = explode( '|', $ids );
		$entry_raw = end( $ids );
		$entry_id  = preg_replace( '/\D/', '', $entry_raw );

		$body     = array(
			'hook' => array(
				'event'  => $module . '.saved',
				'target' => $webhook_url,
			),
			'data' => array(
				'id' => (int) $entry_id,
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

if ( ! function_exists( 'formscrm_normalize_date_format' ) ) {
	/**
	 * Normalizes date to YYYY-MM-DD format required by APIs like Clientify.
	 *
	 * Supported input formats:
	 * - dd/mm/yyyy (European format)
	 * - dd-mm-yyyy (European format with dashes)
	 * - dd.mm.yyyy (European format with dots)
	 * - yyyy-mm-dd (ISO format - already correct)
	 * - yyyy/mm/dd (ISO format with slashes)
	 * - mm/dd/yyyy (US format)
	 * - mm-dd-yyyy (US format with dashes)
	 * - Unix timestamps
	 *
	 * @param string $date_value The date value to normalize.
	 * @return string|false Normalized date in YYYY-MM-DD format or false if invalid.
	 */
	function formscrm_normalize_date_format( $date_value ) {
		if ( empty( $date_value ) ) {
			return false;
		}

		$date_value = trim( $date_value );

		// Already in correct format YYYY-MM-DD.
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_value ) ) {
			return $date_value;
		}

		// Handle Unix timestamp.
		if ( is_numeric( $date_value ) && strlen( $date_value ) >= 8 ) {
			$timestamp = (int) $date_value;
			$date      = gmdate( 'Y-m-d', $timestamp );
			if ( false !== $date ) {
				return $date;
			}
		}

		// European format: dd/mm/yyyy or dd-mm-yyyy or dd.mm.yyyy.
		if ( preg_match( '/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/', $date_value, $matches ) ) {
			$day   = (int) $matches[1];
			$month = (int) $matches[2];
			$year  = (int) $matches[3];

			// Validate date components.
			if ( $day > 31 || $month > 12 ) {
				// Could be US format mm/dd/yyyy, try swapping.
				if ( $month <= 31 && $day <= 12 ) {
					$temp  = $day;
					$day   = $month;
					$month = $temp;
				}
			}

			// Validate the date.
			if ( checkdate( $month, $day, $year ) ) {
				return sprintf( '%04d-%02d-%02d', $year, $month, $day );
			}
		}

		// ISO format with slashes: yyyy/mm/dd.
		if ( preg_match( '/^(\d{4})[\/](\d{1,2})[\/](\d{1,2})$/', $date_value, $matches ) ) {
			$year  = (int) $matches[1];
			$month = (int) $matches[2];
			$day   = (int) $matches[3];

			if ( checkdate( $month, $day, $year ) ) {
				return sprintf( '%04d-%02d-%02d', $year, $month, $day );
			}
		}

		// Try PHP's strtotime as last resort for other formats.
		$timestamp = strtotime( $date_value );
		if ( false !== $timestamp && -1 !== $timestamp ) {
			return gmdate( 'Y-m-d', $timestamp );
		}

		return false;
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

if ( ! function_exists( 'formscrm_get_connection_status_html' ) ) {
	/**
	 * Get HTML for API connection status indicator.
	 *
	 * @param array  $settings    CRM settings array with fc_crm_type and credentials.
	 * @param string $output_type Output type: 'html', 'notice', 'badge', or 'elementor'.
	 * @param string $help_text   Optional help text to display below status.
	 * @return string HTML output for the connection status.
	 */
	function formscrm_get_connection_status_html( $settings, $output_type = 'html', $help_text = '' ) {
		$status_data = formscrm_check_connection_status( $settings );

		return formscrm_build_status_html( $status_data, $output_type, $help_text );
	}
}

if ( ! function_exists( 'formscrm_check_connection_status' ) ) {
	/**
	 * Check connection status and return status data array.
	 *
	 * @param array $settings CRM settings array with fc_crm_type and credentials.
	 * @return array Status data with keys: status, text, color, icon, error_message, crm_type.
	 */
	function formscrm_check_connection_status( $settings ) {
		$crm_type = isset( $settings['fc_crm_type'] ) ? $settings['fc_crm_type'] : '';
		$data     = array(
			'status'        => 'unknown',
			'text'          => __( 'Not configured', 'formscrm' ),
			'color'         => '#999999',
			'icon'          => '○',
			'error_message' => '',
			'crm_type'      => $crm_type,
		);

		if ( empty( $crm_type ) ) {
			return $data;
		}

		$crmlib = formscrm_get_api_class( $crm_type );

		if ( ! isset( $crmlib ) || ! method_exists( $crmlib, 'login' ) ) {
			return $data;
		}

		$login_result = $crmlib->login( $settings );
		$login_status = isset( $login_result['status'] ) ? $login_result['status'] : '';

		if ( is_array( $login_result ) && 'error' === $login_status ) {
			$data['status']        = 'error';
			$data['text']          = __( 'Error', 'formscrm' );
			$data['color']         = '#dc3232';
			$data['icon']          = '✕';
			$data['error_message'] = isset( $login_result['message'] ) ? $login_result['message'] : '';
		} elseif ( true === $login_result || 'ok' === $login_status ) {
			$data['status'] = 'connected';
			$data['text']   = __( 'Connected', 'formscrm' );
			$data['color']  = '#46b450';
			$data['icon']   = '✓';
		} else {
			$data['status'] = 'disconnected';
			$data['text']   = __( 'Disconnected', 'formscrm' );
			$data['color']  = '#dc3232';
			$data['icon']   = '✕';
		}

		return $data;
	}
}

if ( ! function_exists( 'formscrm_build_status_html' ) ) {
	/**
	 * Build HTML from status data.
	 *
	 * @param array  $status_data Status data from formscrm_check_connection_status().
	 * @param string $output_type Output type: 'html', 'notice', 'badge', or 'elementor'.
	 * @param string $help_text   Optional help text to display below status.
	 * @return string HTML output.
	 */
	function formscrm_build_status_html( $status_data, $output_type = 'html', $help_text = '' ) {
		$status        = $status_data['status'];
		$status_text   = $status_data['text'];
		$status_color  = $status_data['color'];
		$status_icon   = $status_data['icon'];
		$error_message = $status_data['error_message'];
		$crm_type      = $status_data['crm_type'];
		$html          = '';

		switch ( $output_type ) {
			case 'notice':
				$notice_class = 'connected' === $status ? 'notice-success' : ( 'unknown' === $status ? 'notice-warning' : 'notice-error' );
				$html         = '<div class="notice ' . esc_attr( $notice_class ) . '" style="padding: 10px;">';
				$html        .= '<strong>' . esc_html__( 'API Connection Status:', 'formscrm' ) . '</strong> ';
				$html        .= '<span style="color: ' . esc_attr( $status_color ) . '; font-weight: bold;">';
				$html        .= esc_html( $status_icon ) . ' ' . esc_html( $status_text );
				$html        .= '</span>';

				if ( ! empty( $crm_type ) ) {
					$html .= ' <span style="color: #666;">(' . esc_html( ucfirst( $crm_type ) ) . ')</span>';
				}

				if ( ! empty( $error_message ) ) {
					$html .= '<br/><span style="color: #dc3232; font-size: 12px;">' . esc_html( $error_message ) . '</span>';
				}

				$html .= '</div>';
				break;

			case 'elementor':
				$bg_color     = 'connected' === $status ? '#f9f9f9' : ( 'unknown' === $status ? '#f9f9f9' : '#ffebee' );
				$border_color = 'connected' === $status ? '#46b450' : ( 'unknown' === $status ? '#0073aa' : '#dc3232' );
				$badge_color  = 'connected' === $status ? '#46b450' : ( 'unknown' === $status ? '#999' : '#dc3232' );

				$html  = '<div style="padding: 12px; background: ' . esc_attr( $bg_color ) . '; border-left: 4px solid ' . esc_attr( $border_color ) . '; border-radius: 4px; margin-bottom: 15px;">';
				$html .= '<div style="display: flex; align-items: center; justify-content: space-between;">';
				$html .= '<div style="display: flex; align-items: center; gap: 8px;">';
				$html .= '<strong style="color: #23282d;">' . esc_html__( 'API Connection Status:', 'formscrm' ) . '</strong> ';
				$html .= '<span style="display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 3px; background: ' . esc_attr( $badge_color ) . '; color: white; font-size: 12px; font-weight: bold;">';
				$html .= '<span style="margin-right: 5px;">' . esc_html( $status_icon ) . '</span>' . esc_html( $status_text );
				$html .= '</span>';
				$html .= '</div>';

				if ( ! empty( $crm_type ) ) {
					$html .= '<div style="text-align: right;">';
					$html .= '<span style="display: block; font-size: 11px; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">' . esc_html__( 'CRM Type', 'formscrm' ) . '</span>';
					$html .= '<strong style="font-size: 14px; color: #0073aa;">' . esc_html( ucfirst( $crm_type ) ) . '</strong>';
					$html .= '</div>';
				}

				$html .= '</div>';

				if ( ! empty( $error_message ) ) {
					$html .= '<p style="margin: 8px 0 0 0; padding-top: 8px; border-top: 1px solid #ddd; color: #dc3232; font-size: 12px;"><strong>' . esc_html__( 'Error:', 'formscrm' ) . '</strong> ' . esc_html( $error_message ) . '</p>';
				}

				$html .= '</div>';
				break;

			case 'badge':
				$html  = '<div class="formscrm-connection-status" style="display: flex; align-items: center; gap: 10px;">';
				$html .= sprintf(
					'<span class="formscrm-status-badge" style="display: inline-flex; align-items: center; padding: 6px 12px; border-radius: 4px; background-color: %1$s; color: white; font-weight: bold; font-size: 13px;">',
					esc_attr( $status_color )
				);
				$html .= '<span style="margin-right: 6px; font-size: 14px;">' . esc_html( $status_icon ) . '</span>';
				$html .= esc_html( $status_text );
				$html .= '</span>';

				if ( ! empty( $crm_type ) ) {
					$html .= sprintf(
						'<span class="formscrm-crm-name" style="color: #666; font-size: 13px;">(%s)</span>',
						esc_html( ucfirst( $crm_type ) )
					);
				}

				$html .= '</div>';

				if ( ! empty( $error_message ) ) {
					$html .= sprintf(
						'<p class="formscrm-error-message" style="color: #dc3232; margin-top: 8px; font-size: 12px;"><strong>%s:</strong> %s</p>',
						esc_html__( 'Error details', 'formscrm' ),
						esc_html( $error_message )
					);
				}
				break;

			default: // 'html'
				$html  = '<div class="formscrm-connection-status" style="display: flex; align-items: center; gap: 10px; margin: 10px 0;">';
				$html .= '<strong>' . esc_html__( 'API Connection Status:', 'formscrm' ) . '</strong> ';
				$html .= sprintf(
					'<span class="formscrm-status-badge" style="display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 3px; background-color: %1$s; color: white; font-weight: bold; font-size: 12px;">',
					esc_attr( $status_color )
				);
				$html .= '<span style="margin-right: 5px;">' . esc_html( $status_icon ) . '</span>';
				$html .= esc_html( $status_text );
				$html .= '</span>';

				if ( ! empty( $crm_type ) ) {
					$html .= sprintf(
						'<span style="color: #666; font-size: 12px;">(%s)</span>',
						esc_html( ucfirst( $crm_type ) )
					);
				}

				$html .= '</div>';

				if ( ! empty( $error_message ) ) {
					$html .= sprintf(
						'<p style="color: #dc3232; margin: 5px 0; font-size: 12px;"><strong>%s:</strong> %s</p>',
						esc_html__( 'Error', 'formscrm' ),
						esc_html( $error_message )
					);
				}
				break;
		}

		// Add help text if provided.
		if ( ! empty( $help_text ) ) {
			$html .= '<p class="formscrm-status-help" style="color: #666; margin-top: 8px; font-size: 12px;">';
			$html .= esc_html( $help_text );
			$html .= '</p>';
		}

		return $html;
	}
}

if ( ! function_exists( 'formscrm_render_connection_status' ) ) {
	/**
	 * Render API connection status indicator.
	 *
	 * Echoes the HTML for API connection status.
	 *
	 * @param array  $settings    CRM settings array with fc_crm_type and credentials.
	 * @param string $output_type Output type: 'html', 'notice', 'badge', or 'elementor'.
	 * @param string $help_text   Optional help text to display below status.
	 * @return void
	 */
	function formscrm_render_connection_status( $settings, $output_type = 'html', $help_text = '' ) {
		$allowed_html = array(
			'div'    => array(
				'class' => array(),
				'style' => array(),
			),
			'span'   => array(
				'class' => array(),
				'style' => array(),
			),
			'strong' => array(),
			'p'      => array(
				'class' => array(),
				'style' => array(),
			),
			'br'     => array(),
		);

		echo wp_kses( formscrm_get_connection_status_html( $settings, $output_type, $help_text ), $allowed_html );
	}
}

if ( ! function_exists( 'formscrm_get_utm_merge_vars' ) ) {
	/**
	 * Returns last-touch UTM cookie values as merge_vars entries.
	 *
	 * @return array
	 */
	function formscrm_get_utm_merge_vars() {
		return formscrm_read_utm_cookies( '' );
	}
}

if ( ! function_exists( 'formscrm_get_utm_first_values' ) ) {
	/**
	 * Returns first-touch UTM cookie values keyed by param name.
	 *
	 * @return array e.g. ['utm_source' => 'google', ...]
	 */
	function formscrm_get_utm_first_values() {
		$result = array();
		foreach ( formscrm_read_utm_cookies( 'first_' ) as $item ) {
			$result[ $item['name'] ] = $item['value'];
		}
		return $result;
	}
}

if ( ! function_exists( 'formscrm_read_utm_cookies' ) ) {
	/**
	 * Reads UTM cookies with the given prefix and returns merge_vars array.
	 *
	 * @param string $prefix Cookie prefix after 'fcrm_' (e.g. '' or 'first_').
	 * @return array
	 */
	function formscrm_read_utm_cookies( $prefix ) {
		$utm_params = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content' );
		$merge_vars = array();

		foreach ( $utm_params as $param ) {
			$cookie_key = 'fcrm_' . $prefix . $param;
			if ( ! empty( $_COOKIE[ $cookie_key ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$merge_vars[] = array(
					'name'  => $param,
					'value' => sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_key ] ) ),
				);
			}
		}

		return $merge_vars;
	}
}
