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
	function formscrm_debug_message( $message ) {
		if ( WP_DEBUG == true ) {
			if ( is_array( $message ) ) {
				$message = $message['status'] . ' ' . $message['data'];
			}
			// Debug Mode
			echo '  <table class="widefat">
                  <thead>
                  <tr class="form-invalid">
                      <th class="row-title">' . esc_html__( 'Message Debug Mode', 'formscrm' ) . '</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                  <td><pre>';
			echo esc_html( $message );
			echo '</pre></td></tr></table>';
		}
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
		if ( wp_doing_ajax() ) {
			error_log( 'API ERROR ' . esc_html( $code ) . ': ' . esc_html( $message ) );
		} else {
			echo '<div class="error">';
			echo '<p><strong>API ERROR ' . esc_html( $code ) . ': </strong> ' . esc_html( $message ) . '</p>';
			echo '</div>';
		}
	}
}

// * Sends an email to administrator when it not creates the lead
if ( ! function_exists( 'formscrm_debug_email_lead' ) ) {
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
	function formscrm_testserver() {
		// test curl.
		if ( ! function_exists( 'curl_version' ) ) {
			echo '<div id="message" class="error below-h2"><p><strong>' . __( 'curl is not Installed in your server. It is needed to work with CRM Libraries.', 'formscrm' ) . '</strong></p></div>';
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
		if ( substr( $url, -1 ) != '/' ) {
			$url .= '/'; // adds slash to url.
		}

		return $url;
	}
}
