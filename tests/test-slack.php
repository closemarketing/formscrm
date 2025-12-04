<?php
/**
 * Test Slack Notifications
 * 
 * Este archivo es TEMPORAL - ELIMINAR después de las pruebas
 * 
 * Para usar: Accede a https://tu-sitio.local/wp-content/plugins/formscrm/tests/test-slack.php
 */

// Cargar WordPress - Buscar wp-load.php automáticamente.
$wp_load_path = __DIR__;
$found        = false;

for ( $i = 0; $i < 10; $i++ ) {
	if ( file_exists( $wp_load_path . '/wp-load.php' ) ) {
		require_once $wp_load_path . '/wp-load.php';
		$found = true;
		break;
	}
	$wp_load_path = dirname( $wp_load_path );
}

if ( ! $found ) {
	die( 'Error: No se pudo encontrar wp-load.php. Por favor, verifica la instalación de WordPress.' );
}

// Cargar las funciones si no están cargadas.
if ( ! function_exists( 'formscrm_send_slack_notification' ) ) {
	require_once dirname( __DIR__ ) . '/includes/formscrm-library/helpers-functions.php';
}

// Verificar que estamos en un entorno seguro.
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'No tienes permisos para acceder a esta página.' );
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Test Slack - FormsCRM</title>
	<style>
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			max-width: 800px;
			margin: 50px auto;
			padding: 20px;
			background: #f0f0f1;
		}
		.container {
			background: white;
			padding: 30px;
			border-radius: 8px;
			box-shadow: 0 2px 4px rgba(0,0,0,0.1);
		}
		h1 {
			color: #1d2327;
			border-bottom: 3px solid #611f69;
			padding-bottom: 10px;
			display: flex;
			align-items: center;
			gap: 15px;
		}
		.slack-logo {
			font-size: 36px;
		}
		.info-box {
			padding: 15px;
			border-left: 4px solid #611f69;
			margin: 20px 0;
			border-radius: 4px;
		}
		.info-box.config {
			background: #f4ede5;
			border-left-color: #611f69;
		}
		.info-box.success {
			background: #d4edda;
			border-left-color: #28a745;
			color: #155724;
		}
		.info-box.warning {
			background: #fff3cd;
			border-left-color: #ffc107;
			color: #856404;
		}
		.info-box.error {
			background: #f8d7da;
			border-left-color: #dc3545;
			color: #721c24;
		}
		.button {
			display: inline-block;
			padding: 12px 24px;
			background: #611f69;
			color: white;
			text-decoration: none;
			border-radius: 4px;
			border: none;
			cursor: pointer;
			font-size: 16px;
			margin: 10px 10px 10px 0;
		}
		.button:hover {
			background: #4a154b;
		}
		.button:disabled {
			background: #ccc;
			cursor: not-allowed;
		}
		.button-secondary {
			background: #dcdcde;
			color: #1d2327;
		}
		.button-secondary:hover {
			background: #c3c4c7;
		}
		select {
			padding: 8px 12px;
			border: 1px solid #8c8f94;
			border-radius: 4px;
			width: 100%;
			max-width: 400px;
			margin: 10px 0;
		}
		label {
			display: block;
			margin-top: 15px;
			font-weight: 600;
			color: #1d2327;
		}
		code {
			background: #f5f5f5;
			padding: 3px 8px;
			border-radius: 3px;
			font-family: monospace;
			font-size: 13px;
		}
		.webhook-display {
			background: #f9f9f9;
			padding: 12px;
			border-radius: 4px;
			border: 1px solid #ddd;
			word-break: break-all;
			font-family: monospace;
			font-size: 12px;
			margin: 10px 0;
		}
		.status-badge {
			display: inline-block;
			padding: 4px 12px;
			border-radius: 12px;
			font-size: 12px;
			font-weight: bold;
			margin-left: 10px;
		}
		.status-enabled {
			background: #28a745;
			color: white;
		}
		.status-disabled {
			background: #dc3545;
			color: white;
		}
		.setup-steps {
			background: #f8f9fa;
			padding: 20px;
			border-radius: 6px;
			margin: 20px 0;
		}
		.setup-steps ol {
			margin: 10px 0;
			padding-left: 20px;
		}
		.setup-steps li {
			margin: 10px 0;
			line-height: 1.6;
		}
		.slack-preview {
			background: #f9f9f9;
			border: 1px solid #ddd;
			border-radius: 8px;
			padding: 15px;
			margin: 20px 0;
		}
		.slack-preview h4 {
			margin-top: 0;
			color: #611f69;
		}
		.message-example {
			background: white;
			border-left: 4px solid #e01e5a;
			padding: 12px;
			margin: 10px 0;
			border-radius: 4px;
		}
		.message-example strong {
			display: block;
			margin-bottom: 5px;
		}
	</style>
</head>
<body>
	<div class="container">
		<h1>
			<span class="slack-logo">💬</span>
			Test de Notificaciones Slack - FormsCRM
		</h1>
		
		<?php
		// Obtener configuración de Slack.
		$slack_webhook = get_option( 'formscrm_slack_webhook_url', '' );
		$is_configured = ! empty( $slack_webhook );

		// Procesar el formulario si se envió.
		if ( isset( $_POST['send_test'] ) && check_admin_referer( 'formscrm_test_slack' ) ) {
			if ( ! $is_configured ) {
				echo '<div class="info-box error">';
				echo '<h3 style="margin-top: 0;">❌ Error</h3>';
				echo '<p>No hay ningún Webhook de Slack configurado. Por favor, configura uno primero.</p>';
				echo '</div>';
			} else {
				$crm_type   = sanitize_text_field( $_POST['crm_type'] );
				$form_type  = sanitize_text_field( $_POST['form_type'] );
				$error_type = sanitize_text_field( $_POST['error_type'] );

				// Definir errores según el tipo.
				$errors = array(
					'api_key'     => 'Invalid API Key - Authentication failed',
					'connection'  => 'Connection timeout - Could not reach CRM server',
					'validation'  => 'Validation error - Required field is missing',
					'duplicate'   => 'Duplicate entry - Contact already exists in CRM',
					'permission'  => 'Permission denied - Insufficient privileges to create entry',
					'rate_limit'  => 'Rate limit exceeded - Too many requests to API',
				);

				$error_message = isset( $errors[ $error_type ] ) ? $errors[ $error_type ] : 'Unknown error occurred';

				// Datos de prueba del lead.
				$data = array(
					array( 'name' => 'Nombre', 'value' => 'Juan Pérez García' ),
					array( 'name' => 'Email', 'value' => 'juan.perez@ejemplo.com' ),
					array( 'name' => 'Teléfono', 'value' => '+34 600 123 456' ),
					array( 'name' => 'Empresa', 'value' => 'Tecnología Ejemplo SL' ),
					array( 'name' => 'Cargo', 'value' => 'Director de Marketing' ),
					array( 'name' => 'Ciudad', 'value' => 'Madrid' ),
					array( 'name' => 'País', 'value' => 'España' ),
				);

				// URL y JSON de prueba.
				$url  = 'https://api.' . strtolower( $crm_type ) . '.com/api/v1/contacts';
				$json = wp_json_encode(
					array(
						'name'    => 'Juan Pérez García',
						'email'   => 'juan.perez@ejemplo.com',
						'phone'   => '+34 600 123 456',
						'company' => 'Tecnología Ejemplo SL',
					),
					JSON_PRETTY_PRINT
				);

				// Información del formulario.
				$form_info = array(
					'form_type' => $form_type,
					'form_id'   => '42',
					'form_name' => 'Formulario de Contacto Principal',
					'entry_id'  => '12345',
				);

				// Enviar la notificación a Slack.
				$result = formscrm_send_slack_notification( $crm_type, $error_message, $data, $url, $json, $form_info );

				if ( true === $result ) {
					echo '<div class="info-box success">';
					echo '<h2 style="margin-top: 0;">✅ Notificación Enviada a Slack</h2>';
					echo '<p><strong>Estado:</strong> Enviado correctamente</p>';
					echo '<p><strong>CRM:</strong> ' . esc_html( $crm_type ) . '</p>';
					echo '<p><strong>Formulario:</strong> ' . esc_html( $form_type ) . '</p>';
					echo '<p><strong>Error Simulado:</strong> ' . esc_html( $error_message ) . '</p>';
					echo '<p style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">';
					echo '🎯 <strong>Revisa tu canal de Slack ahora.</strong> Deberías ver una notificación con formato bonito y toda la información del error.';
					echo '</p>';
					echo '</div>';
				} elseif ( is_wp_error( $result ) ) {
					echo '<div class="info-box error">';
					echo '<h3 style="margin-top: 0;">❌ Error al Enviar</h3>';
					echo '<p><strong>Mensaje:</strong> ' . esc_html( $result->get_error_message() ) . '</p>';
					echo '<p>Verifica que la URL del Webhook sea correcta y que el webhook esté activo en Slack.</p>';
					echo '</div>';
				} else {
					echo '<div class="info-box warning">';
					echo '<h3 style="margin-top: 0;">⚠️ No se pudo enviar</h3>';
					echo '<p>La función no devolvió un resultado exitoso. Revisa los logs de WordPress para más detalles.</p>';
					echo '</div>';
				}
			}
		}
		?>

		<!-- Estado de Configuración -->
		<div class="info-box config">
			<h3 style="margin-top: 0; display: flex; align-items: center; justify-content: space-between;">
				<span>📋 Configuración de Slack</span>
				<?php if ( $is_configured ) : ?>
					<span class="status-badge status-enabled">✓ Configurado</span>
				<?php else : ?>
					<span class="status-badge status-disabled">✗ No configurado</span>
				<?php endif; ?>
			</h3>
			
			<?php if ( $is_configured ) : ?>
				<p><strong>Webhook URL configurado:</strong></p>
				<div class="webhook-display">
					<?php echo esc_html( substr( $slack_webhook, 0, 70 ) ) . ( strlen( $slack_webhook ) > 70 ? '...' : '' ); ?>
				</div>
				<p style="color: #155724; font-weight: bold;">✅ Listo para enviar notificaciones</p>
			<?php else : ?>
				<p style="color: #721c24; font-weight: bold;">⚠️ No hay webhook configurado</p>
				<p>Para recibir notificaciones en Slack, necesitas configurar un Webhook URL primero.</p>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=formscrm' ) ); ?>" class="button">
					⚙️ Ir a Configuración
				</a>
			<?php endif; ?>
		</div>

		<?php if ( $is_configured ) : ?>
			<!-- Formulario de Prueba -->
			<form method="post" action="">
				<?php wp_nonce_field( 'formscrm_test_slack' ); ?>

				<label for="crm_type">🎯 Tipo de CRM:</label>
				<select name="crm_type" id="crm_type" required>
					<option value="Holded">Holded</option>
					<option value="Clientify">Clientify</option>
					<option value="AcumbaMail">AcumbaMail</option>
					<option value="Brevo">Brevo</option>
					<option value="Odoo">Odoo</option>
					<option value="WHMCS">WHMCS</option>
					<option value="Pipedrive">Pipedrive</option>
					<option value="Vtiger">vTiger</option>
					<option value="SuiteCRM">SuiteCRM</option>
				</select>

				<label for="form_type">📝 Tipo de Formulario:</label>
				<select name="form_type" id="form_type" required>
					<option value="Gravity Forms">Gravity Forms</option>
					<option value="WPForms">WPForms</option>
					<option value="Elementor">Elementor Forms</option>
					<option value="Contact Form 7">Contact Form 7</option>
					<option value="WooCommerce">WooCommerce</option>
				</select>

				<label for="error_type">❌ Tipo de Error:</label>
				<select name="error_type" id="error_type" required>
					<option value="api_key">Error de API Key</option>
					<option value="connection">Error de Conexión</option>
					<option value="validation">Error de Validación</option>
					<option value="duplicate">Entrada Duplicada</option>
					<option value="permission">Sin Permisos</option>
					<option value="rate_limit">Límite de Peticiones</option>
				</select>

				<div style="margin-top: 30px;">
					<button type="submit" name="send_test" class="button">
						🚀 Enviar Notificación de Prueba a Slack
					</button>
					<a href="<?php echo esc_url( admin_url( 'options-general.php?page=formscrm' ) ); ?>" class="button button-secondary">
						⚙️ Configuración
					</a>
				</div>
			</form>

			<!-- Vista Previa del Mensaje -->
			<div class="slack-preview">
				<h4>📱 Vista Previa del Mensaje en Slack:</h4>
				<div class="message-example">
					<strong>⚠️ FormsCRM Error Report</strong>
					<div style="margin-top: 10px; font-size: 13px; line-height: 1.8;">
						<p style="margin: 3px 0;"><strong>Site:</strong> Tu Sitio Web (https://tu-sitio.com)</p>
						<p style="margin: 3px 0;"><strong>Form:</strong> Gravity Forms | Formulario de Contacto | ID: 42 | Entry: 12345</p>
						<p style="margin: 3px 0;"><strong>CRM:</strong> Holded</p>
						<p style="margin: 3px 0;"><strong>Error:</strong> Invalid API Key - Authentication failed</p>
						<p style="margin: 3px 0;"><strong>Lead:</strong> Nombre: Juan Pérez | Email: juan@ejemplo.com | Teléfono: +34 600... (+4 more)</p>
						<p style="margin: 3px 0;"><strong>API:</strong> <code>https://api.holded.com/api/v1/contacts</code></p>
					</div>
				</div>
				<p style="font-size: 12px; color: #666; margin-top: 10px;">
					✨ Formato compacto y fácil de leer. El mensaje real incluirá colores de Slack.
				</p>
			</div>
		<?php endif; ?>

		<!-- Instrucciones de Configuración -->
		<div class="setup-steps">
			<h3 style="margin-top: 0; color: #611f69;">💡 Cómo Configurar un Webhook de Slack</h3>
			<ol>
				<li>
					<strong>Crea un Webhook en Slack:</strong><br>
					Ve a <a href="https://api.slack.com/messaging/webhooks" target="_blank">https://api.slack.com/messaging/webhooks</a>
				</li>
				<li>
					<strong>Selecciona tu Workspace:</strong><br>
					Haz clic en "Create New App" o usa una app existente
				</li>
				<li>
					<strong>Activa Incoming Webhooks:</strong><br>
					En la configuración de tu app, activa "Incoming Webhooks"
				</li>
				<li>
					<strong>Añade un Webhook a tu Workspace:</strong><br>
					Haz clic en "Add New Webhook to Workspace"
				</li>
				<li>
					<strong>Elige el Canal:</strong><br>
					Selecciona el canal donde quieres recibir notificaciones (ej: <code>#errors</code>, <code>#formscrm</code>, <code>#dev-alerts</code>)
				</li>
				<li>
					<strong>Copia la URL del Webhook:</strong><br>
					Copia la URL completa (empieza con <code>https://hooks.slack.com/services/...</code>)
				</li>
				<li>
					<strong>Configura en WordPress:</strong><br>
					Ve a <strong>Ajustes > FormsCRM</strong> y pega la URL en el campo "Slack Webhook URL"
				</li>
				<li>
					<strong>¡Prueba!</strong><br>
					Vuelve a esta página y envía una notificación de prueba
				</li>
			</ol>
		</div>

		<!-- Nota Importante -->
		<div class="info-box warning" style="margin-top: 30px;">
			<h3 style="margin-top: 0;">⚠️ IMPORTANTE</h3>
			<p><strong>Este archivo es SOLO para pruebas.</strong></p>
			<p>Una vez verificado que Slack funciona correctamente, <strong>ELIMINA este archivo</strong> por seguridad:</p>
			<code style="background: white; padding: 5px; display: block; margin-top: 10px;">
				rm wp-content/plugins/formscrm/tests/test-slack.php
			</code>
		</div>
	</div>
</body>
</html>

