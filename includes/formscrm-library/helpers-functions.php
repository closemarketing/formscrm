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

		$message = '';
		$message .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . "\n";
		$message .= '<html xmlns="http://www.w3.org/1999/xhtml" style="box-sizing: border-box; margin: 0;">' . "\n";
		$message .= '<head>' . "\n";
		$message .= '	<meta name="viewport" content="width=device-width">' . "\n";
		$message .= '	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">' . "\n";
		$message .= '	<title>FormsCRM</title>' . "\n";
		$message .= '	<style type="text/css">' . "\n";
		$message .= '	img { max-width: 100%; }' . "\n";
		$message .= '  body { -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: none; width: 100% !important; height: 100%; line-height: 1.2em; }' . "\n";
		$message .= '  body { background-color: #f6f6f6; }' . "\n";
		$message .= '  @media only screen and (max-width: 640px) {' . "\n";
		$message .= '		body { padding: 0 !important; }' . "\n";
		$message .= '		h1 { margin: 20px 0 5px 0 !important; }' . "\n";
		$message .= '		h2 { margin: 20px 0 5px 0 !important; }' . "\n";
		$message .= '		h3 { margin: 20px 0 5px 0 !important; }' . "\n";
		$message .= '		h4 { margin: 20px 0 5px 0 !important; }' . "\n";
		$message .= '		.container { padding: 0 !important; width: 100% !important; }' . "\n";
		$message .= '		.content { padding: 0 !important; }' . "\n";
		$message .= '		.content-wrap { padding: 10px !important; }' . "\n";
		$message .= '		.invoice { width: 100% !important; }' . "\n";
		$message .= '  }' . "\n";
		$message .= '  </style>' . "\n";
		$message .= '</head>' . "\n";
		$message .= '<body itemscope itemtype="http://schema.org/EmailMessage" style="box-sizing: border-box; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: none; width: 100% !important; height: 100%; line-height: 1.6em; background-color: #f6f6f6; margin: 0;" bgcolor="#f6f6f6">' . "\n";
		$message .= '	<table class="body-wrap" style="box-sizing: border-box; width: 100%; background-color: #f6f6f6; margin: 0;" bgcolor="#f6f6f6">' . "\n";
		$message .= '		<tr style="box-sizing: border-box; margin: 0;">' . "\n";
		$message .= '			<td style="box-sizing: border-box; vertical-align: top; margin: 0;" valign="top"></td>' . "\n";
		$message .= '			<td class="container" width="600" style="box-sizing: border-box; vertical-align: top; display: block !important; max-width: 600px !important; clear: both !important; margin: 0 auto;" valign="top">' . "\n";
		$message .= '				<div class="content" style="box-sizing: border-box; max-width: 600px; display: block; margin: 0 auto; padding: 20px;">' . "\n";
		$message .= '					<table class="main" width="100%" cellpadding="0" cellspacing="0" style="box-sizing: border-box; border-radius: 3px; background-color: #fff; margin: 0; border: 1px solid #e9e9e9;" bgcolor="#fff">' . "\n";
		$message .= '						<tr style="box-sizing: border-box; margin: 0;">' . "\n";
		$message .= '							<td class="content-wrap aligncenter" style="box-sizing: border-box; vertical-align: top; text-align: center; margin: 0; padding: 20px;" align="center" valign="top">' . "\n";
		$message .= '								<table width="100%" cellpadding="0" cellspacing="0" style="box-sizing: border-box; margin: 0;">' . "\n";
		$message .= '									<tr style="box-sizing: border-box; margin: 0;">' . "\n";
		$message .= '										<td class="content-block" style="box-sizing: border-box; vertical-align: top; margin: 0; padding: 0 0 20px; text-align: center;" valign="top">' . "\n";
		$message .= '											<img class="aligncenter" src="' . FORMSCRM_PLUGIN_URL . 'assets/logo64.png" width="64" height="64" alt="WPVulnerability">' . "\n";
		$message .= '											<h1 class="aligncenter" style="box-sizing: border-box; color: #000; line-height: 1.2em; text-align: center; margin: 40px 0 0;" align="center">' . $title . '</h1>' . "\n";
		$message .= '											<p class="aligncenter" style="box-sizing: border-box; color: #000; line-height: 1.2em; text-align: center; margin: 5px 0 0;" align="center"><a href="' . site_url() . '" target="_blank" rel="noopener noreferrer">' . site_url() . '</a></p>' . "\n";
		$message .= '										</td>' . "\n";
		$message .= '									</tr>' . "\n";
		$message .= '									<tr style="box-sizing: border-box; margin: 0;">' . "\n";
		$message .= '										<td class="content-block alignlef" style="box-sizing: border-box; vertical-align: top; text-align: left; margin: 0; padding: 0 0 20px;" valign="top">' . "\n";
		$message .= $content;
		$message .= '									</td>' . "\n";
		$message .= '									</tr>' . "\n";
		$message .= '								</table>' . "\n";
		$message .= '								<div class="footer" style="box-sizing: border-box; width: 100%; clear: both; color: #999; margin: 0; padding: 20px;">' . "\n";
		$message .= '									<table width="100%" style="box-sizing: border-box; margin: 0;">' . "\n";
		$message .= '										<tr style="box-sizing: border-box; margin: 0;">' . "\n";
		$message .= '											<td class="aligncenter content-block" style="box-sizing: border-box; vertical-align: top; color: #999; text-align: center; margin: 0; padding: 0 0 20px;" align="center" valign="top">' . "\n";
		$message .= sprintf(
			// translators: %1$s the website of Database, %2$s database site name.
			__( 'Learn more about the WordPress Vulnerability Database API at <a href="%1$s">%2$s</a>', 'wpvulnerability' ),
			'https://vulnerability.wpsysadmin.com/',
			'WPVulnerability'
		);
		$message .= '											</td>' . "\n";
		$message .= '										</tr>' . "\n";
		$message .= '										<tr style="box-sizing: border-box; margin: 0;">' . "\n";
		$message .= '											<td class="aligncenter content-block" style="box-sizing: border-box; vertical-align: top; color: #999; text-align: center; margin: 0; padding: 0 0 20px;" align="center" valign="top"><a href="' . site_url() . '" target="_blank" rel="noopener noreferrer">' . site_url() . '</a></td>' . "\n";
		$message .= '										</tr>' . "\n";
		$message .= '									</table>' . "\n";
		$message .= '								</div>' . "\n";
		$message .= '							</td>' . "\n";
		$message .= '							<td style="box-sizing: border-box; vertical-align: top; margin: 0;" valign="top"></td>' . "\n";
		$message .= '						</tr>' . "\n";
		$message .= '					</table>' . "\n";
		$message .= '				</div>' . "\n";
		$message .= '			</td>' . "\n";
		$message .= '		</tr>' . "\n";
		$message .= '	</table>' . "\n";
		$message .= '</body>' . "\n";
		$message .= '</html>';


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
