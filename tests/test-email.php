<?php
/**
 * Test Error Email Notification
 * 
 * Este archivo es TEMPORAL - ELIMINAR después de las pruebas
 * 
 * Para usar: Accede a https://tu-sitio.local/wp-content/plugins/formscrm/test-email.php
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

// Cargar la función de email si no está cargada.
if ( ! function_exists( 'formscrm_alert_error' ) ) {
	require_once __DIR__ . '/includes/formscrm-library/helpers-functions.php';
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
	<title>Test Email FormsCRM</title>
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
			border-bottom: 3px solid #2271b1;
			padding-bottom: 10px;
		}
		.info-box {
			background: #e3f2fd;
			padding: 15px;
			border-left: 4px solid #2271b1;
			margin: 20px 0;
		}
		.success {
			background: #d4edda;
			border-left-color: #28a745;
			color: #155724;
		}
		.warning {
			background: #fff3cd;
			border-left-color: #ffc107;
			color: #856404;
		}
		.button {
			display: inline-block;
			padding: 12px 24px;
			background: #2271b1;
			color: white;
			text-decoration: none;
			border-radius: 4px;
			border: none;
			cursor: pointer;
			font-size: 16px;
			margin: 10px 10px 10px 0;
		}
		.button:hover {
			background: #135e96;
		}
		.button-secondary {
			background: #dcdcde;
			color: #1d2327;
		}
		.button-secondary:hover {
			background: #c3c4c7;
		}
		.test-data {
			background: #f8f9fa;
			padding: 15px;
			border-radius: 4px;
			margin: 20px 0;
			font-family: monospace;
			font-size: 13px;
		}
		.test-data h3 {
			margin-top: 0;
			color: #2271b1;
		}
		select, input[type="text"] {
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
	</style>
</head>
<body>
	<div class="container">
		<h1>🧪 Test de Email de Errores - FormsCRM</h1>
		
		<?php
		// Obtener el email configurado.
		$configured_email = get_option( 'formscrm_error_notification_email', '' );
		$default_email    = get_option( 'admin_email' );
		$target_email     = ! empty( $configured_email ) ? $configured_email : $default_email;

		// Procesar el formulario si se envió.
		if ( isset( $_POST['send_test'] ) && check_admin_referer( 'formscrm_test_email' ) ) {
			$crm_type   = sanitize_text_field( $_POST['crm_type'] );
			$form_type  = sanitize_text_field( $_POST['form_type'] );
			$error_type = sanitize_text_field( $_POST['error_type'] );

			// Definir errores según el tipo.
			$errors = array(
				'api_key'     => 'Invalid API Key - Authentication failed',
				'connection'  => 'Connection timeout - Could not reach CRM server',
				'validation'  => 'Validation error - Email field is required',
				'duplicate'   => 'Duplicate entry - Contact already exists',
				'permission'  => 'Permission denied - Insufficient privileges',
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
				array( 'name' => 'Mensaje', 'value' => 'Estoy interesado en sus servicios de integración CRM. Me gustaría recibir más información sobre las funcionalidades disponibles.' ),
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

			// Enviar el email de prueba.
			formscrm_alert_error( $crm_type, $error_message, $data, $url, $json, $form_info );

			echo '<div class="info-box success">';
			echo '<h2 style="margin-top: 0;">✅ Email de Prueba Enviado Correctamente</h2>';
			echo '<p><strong>Destinatario(s):</strong> ' . esc_html( $target_email ) . '</p>';
			echo '<p><strong>CRM:</strong> ' . esc_html( $crm_type ) . '</p>';
			echo '<p><strong>Tipo de Formulario:</strong> ' . esc_html( $form_type ) . '</p>';
			echo '<p><strong>Error Simulado:</strong> ' . esc_html( $error_message ) . '</p>';
			echo '<p>Revisa tu bandeja de entrada (y la carpeta de SPAM si no lo ves).</p>';
			echo '</div>';
		}
		?>

		<div class="info-box">
			<p><strong>📧 Email configurado para notificaciones:</strong></p>
			<p style="font-size: 16px; font-weight: bold; color: #2271b1;">
				<?php echo esc_html( $target_email ); ?>
			</p>
			<?php if ( empty( $configured_email ) ) : ?>
				<p style="color: #856404;">⚠️ Usando email por defecto. Configura uno personalizado en Ajustes > FormsCRM</p>
			<?php endif; ?>
		</div>

		<form method="post" action="">
			<?php wp_nonce_field( 'formscrm_test_email' ); ?>

			<label for="crm_type">Tipo de CRM:</label>
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

			<label for="form_type">Tipo de Formulario:</label>
			<select name="form_type" id="form_type" required>
				<option value="Gravity Forms">Gravity Forms</option>
				<option value="WPForms">WPForms</option>
				<option value="Elementor">Elementor Forms</option>
				<option value="Contact Form 7">Contact Form 7</option>
				<option value="WooCommerce">WooCommerce</option>
			</select>

			<label for="error_type">Tipo de Error:</label>
			<select name="error_type" id="error_type" required>
				<option value="api_key">Error de API Key</option>
				<option value="connection">Error de Conexión</option>
				<option value="validation">Error de Validación</option>
				<option value="duplicate">Entrada Duplicada</option>
				<option value="permission">Sin Permisos</option>
			</select>

			<div style="margin-top: 30px;">
				<button type="submit" name="send_test" class="button">📤 Enviar Email de Prueba</button>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=formscrm' ) ); ?>" class="button button-secondary">⚙️ Configurar Email</a>
			</div>
		</form>

		<div class="info-box warning" style="margin-top: 30px;">
			<h3 style="margin-top: 0;">⚠️ IMPORTANTE</h3>
			<p><strong>Este archivo es SOLO para pruebas.</strong></p>
			<p>Una vez verificado que los emails funcionan correctamente, <strong>ELIMINA este archivo</strong> por seguridad:</p>
			<code style="background: white; padding: 5px; display: block; margin-top: 10px;">
				/wp-content/plugins/formscrm/test-email.php
			</code>
		</div>

		<div class="test-data">
			<h3>📋 Datos que se enviarán en el email de prueba:</h3>
			<ul>
				<li><strong>Nombre:</strong> Juan Pérez García</li>
				<li><strong>Email:</strong> juan.perez@ejemplo.com</li>
				<li><strong>Teléfono:</strong> +34 600 123 456</li>
				<li><strong>Empresa:</strong> Tecnología Ejemplo SL</li>
				<li><strong>Cargo:</strong> Director de Marketing</li>
				<li><strong>Ciudad:</strong> Madrid</li>
				<li><strong>País:</strong> España</li>
				<li><strong>Mensaje:</strong> Interesado en servicios de integración CRM</li>
			</ul>
		</div>
	</div>
</body>
</html>

