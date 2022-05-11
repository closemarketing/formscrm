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
	function formscrm_get_module( $default_module = '' ) {
		global $settings;
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
	function formscrm_debug_email_lead( $crm, $error, $data ) {
		$to      = get_option( 'admin_email' );
		$subject = 'FormsCRM - ' . __( 'Error creating the Lead', 'formscrm' );
		$body    = '<p>' . __( 'There was an error creating the Lead in the CRM', 'formscrm' ) . ' ' . $crm . ':</p><p><strong>' . $error . '</strong></p><p>' . __( 'Lead Data', 'formscrm' ) . ':</p>';
		foreach ( $data as $dataitem ) {
			$body .= '<p><strong>' . $dataitem['name'] . ': </strong>' . $dataitem['value'] . '</p>';
		}
		$body   .= '</br/><br/>FormsCRM';
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
