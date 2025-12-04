<?php
/**
 * Manual Test Script for API Connection Validation
 *
 * USAGE: http://your-site.local/wp-content/plugins/formscrm/tests/manual-test-connection.php
 *
 * @package    WordPress
 * @subpackage FormsCRM
 */

// Load WordPress.
define( 'WP_USE_THEMES', false );
require '../../../../wp-load.php';

// Security check.
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'You do not have permission to access this page.' );
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>FormsCRM API Connection Test</title>
	<style>
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			max-width: 1200px;
			margin: 40px auto;
			padding: 20px;
			background: #f0f0f1;
		}
		.container {
			background: white;
			padding: 30px;
			border-radius: 8px;
			box-shadow: 0 1px 3px rgba(0,0,0,0.1);
		}
		h1 {
			color: #1d2327;
			border-bottom: 3px solid #2271b1;
			padding-bottom: 10px;
		}
		.test-section {
			margin: 30px 0;
			padding: 20px;
			background: #f6f7f7;
			border-left: 4px solid #2271b1;
		}
		.success {
			background: #d7f0dd;
			border-left-color: #00a32a;
			padding: 15px;
			margin: 10px 0;
			border-radius: 4px;
		}
		.error {
			background: #f8d7da;
			border-left-color: #d63638;
			padding: 15px;
			margin: 10px 0;
			border-radius: 4px;
		}
		.info {
			background: #e5f5fa;
			border-left-color: #2271b1;
			padding: 15px;
			margin: 10px 0;
			border-radius: 4px;
		}
		form {
			margin: 20px 0;
		}
		label {
			display: block;
			margin: 15px 0 5px;
			font-weight: 600;
		}
		input[type="text"],
		input[type="password"],
		select {
			width: 100%;
			max-width: 500px;
			padding: 10px;
			border: 1px solid #8c8f94;
			border-radius: 4px;
			font-size: 14px;
		}
		button {
			background: #2271b1;
			color: white;
			border: none;
			padding: 12px 24px;
			border-radius: 4px;
			cursor: pointer;
			font-size: 14px;
			margin-top: 15px;
		}
		button:hover {
			background: #135e96;
		}
		.test-result {
			margin-top: 20px;
			padding: 15px;
			border-radius: 4px;
		}
		code {
			background: #f0f0f1;
			padding: 2px 6px;
			border-radius: 3px;
			font-family: Consolas, Monaco, monospace;
		}
		pre {
			background: #1d2327;
			color: #f0f0f1;
			padding: 15px;
			border-radius: 4px;
			overflow-x: auto;
		}
	</style>
</head>
<body>
	<div class="container">
		<h1>🔌 FormsCRM API Connection Test</h1>
		
		<div class="info">
			<strong>📋 Purpose:</strong> Test API connection validation for different CRM systems
		</div>

		<?php
		// Process form submission.
		if ( isset( $_POST['test_connection'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'test_connection' ) ) {
			$crm_type = sanitize_text_field( $_POST['crm_type'] );
			$api_key  = sanitize_text_field( $_POST['api_key'] );

			echo '<div class="test-section">';
			echo '<h2>🧪 Test Results</h2>';

			// Load CRM library.
			$crm_file = FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-' . strtolower( $crm_type ) . '.php';
			
			if ( file_exists( $crm_file ) ) {
				require_once $crm_file;
				$class_name = 'CRMLIB_' . ucfirst( $crm_type );

				if ( class_exists( $class_name ) ) {
					$crm = new $class_name();

					$settings = array(
						'fc_crm_type'        => strtolower( $crm_type ),
						'fc_crm_apipassword' => $api_key,
					);

					echo '<p><strong>Testing:</strong> ' . esc_html( $crm_type ) . '</p>';
					echo '<p><strong>API Key:</strong> ' . ( empty( $api_key ) ? '<em>Empty</em>' : '<code>' . esc_html( substr( $api_key, 0, 10 ) ) . '...</code>' ) . '</p>';

					$start_time = microtime( true );
					$result     = $crm->login( $settings );
					$end_time   = microtime( true );
					$duration   = round( ( $end_time - $start_time ) * 1000, 2 );

					if ( is_array( $result ) ) {
						if ( 'ok' === $result['status'] ) {
							echo '<div class="success">';
							echo '<strong>✅ Success!</strong><br>';
							echo esc_html( $result['data'] );
							echo '</div>';
						} else {
							echo '<div class="error">';
							echo '<strong>❌ Error:</strong><br>';
							echo esc_html( $result['data'] );
							echo '</div>';
						}

						echo '<p><strong>Response Time:</strong> ' . esc_html( $duration ) . 'ms</p>';

						echo '<details>';
						echo '<summary>View Full Response</summary>';
						echo '<pre>' . esc_html( print_r( $result, true ) ) . '</pre>';
						echo '</details>';
					} else {
						echo '<div class="info">';
						echo '<strong>ℹ️ Legacy Response Format:</strong><br>';
						echo 'Result: ' . ( $result ? 'TRUE' : 'FALSE' );
						echo '</div>';
					}
				} else {
					echo '<div class="error">Class ' . esc_html( $class_name ) . ' not found</div>';
				}
			} else {
				echo '<div class="error">CRM library file not found: ' . esc_html( $crm_file ) . '</div>';
			}

			echo '</div>';
		}
		?>

		<div class="test-section">
			<h2>🔐 Test Form</h2>
			<form method="post">
				<?php wp_nonce_field( 'test_connection' ); ?>
				
				<label for="crm_type">CRM Type:</label>
				<select name="crm_type" id="crm_type" required>
					<option value="">-- Select CRM --</option>
					<option value="clientify">Clientify</option>
					<option value="holded">Holded</option>
					<option value="mailerlite">MailerLite</option>
					<option value="brevo">Brevo</option>
					<option value="acumbamail">AcumbaMail</option>
				</select>

				<label for="api_key">API Key / Password:</label>
				<input type="password" name="api_key" id="api_key" placeholder="Enter your API key">
				<small>Leave empty to test empty key validation</small>

				<button type="submit" name="test_connection">🧪 Test Connection</button>
			</form>
		</div>

		<div class="test-section">
			<h2>📝 Test Scenarios</h2>
			<ol>
				<li><strong>Empty API Key:</strong> Leave API key field empty and submit</li>
				<li><strong>Invalid API Key:</strong> Enter <code>invalid-key-12345</code></li>
				<li><strong>Valid API Key:</strong> Enter your real API key from the CRM</li>
			</ol>
		</div>

		<div class="test-section">
			<h2>🔗 Quick Links</h2>
			<ul>
				<li><a href="<?php echo admin_url( 'admin.php?page=gf_settings&subview=formscrm' ); ?>">FormsCRM Settings</a></li>
				<li><a href="<?php echo admin_url( 'admin.php?page=gf_edit_forms' ); ?>">Gravity Forms</a></li>
				<li><a href="<?php echo admin_url( 'admin.php?page=gf_entries' ); ?>">Form Entries</a></li>
			</ul>
		</div>

		<div class="info">
			<strong>💡 Pro Tip:</strong> Check the browser console (F12) and WordPress debug log for additional information.
		</div>
	</div>
</body>
</html>

