<?php
/**
 * Ajax for admin side
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2020 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Process post data for Elementor forms
 *
 * @param array  $post_data Post data from form.
 * @param string $module CRM module.
 * @return array Processed settings data.
 */
function formscrm_elementor_process_settings( $post_data, $module = '' ) {
	if ( isset( $post_data['fc_crm_url'] ) && is_array( $post_data['fc_crm_url'] ) ) {
		// If the URL is an array, we assume it has a 'url' key.
		$post_data['fc_crm_url'] = isset( $post_data['fc_crm_url']['url'] ) ? sanitize_text_field( $post_data['fc_crm_url']['url'] ) : '';
	} elseif ( isset( $post_data['fc_crm_url'] ) ) {
		// If it's not an array, sanitize it directly.
		$post_data['fc_crm_url'] = sanitize_text_field( $post_data['fc_crm_url'] );
	}
	$post_data['fc_crm_module'] = $module;
	return $post_data;
}

add_action( 'wp_ajax_elementor_formscrm_connect_crm', 'elementor_formscrm_connect_crm' );

/**
 * Ajax function to connect to CRM
 *
 * @return void
 */
function elementor_formscrm_connect_crm() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Legacy function name for Elementor integration.
	// Nonce.
	if ( ! check_ajax_referer( 'formcrm_nonce', 'nonce', false ) ) {
		wp_send_json_error( __( 'Security check failed', 'formscrm' ) );
	}

	// Get options.
	if ( empty( $_POST['crmSettings'] ) ) {
		wp_send_json_error( __( 'No settings found', 'formscrm' ) );
	}

	$hidden_settings = empty( $_POST['hiddenSettings'] ) ? array() : json_decode( stripslashes_deep( $_POST['hiddenSettings'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	ob_start();
	// 1. Check connection to CRM
	$crmtype = isset( $_POST['crmSettings']['fc_crm_type'] ) ? sanitize_text_field( wp_unslash( $_POST['crmSettings']['fc_crm_type'] ) ) : '';

	if ( empty( $crmtype ) ) {
		wp_send_json_error( __( 'No CRM selected', 'formscrm' ) );
	}

	// Load CRM library class using helper function.
	$crmlib = formscrm_get_api_class( $crmtype );

	if ( ! $crmlib ) {
		wp_send_json_error( __( 'Could not load CRM library', 'formscrm' ) );
	}

	$crm_settings_raw = isset( $_POST['crmSettings'] ) ? wp_unslash( $_POST['crmSettings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in formscrm_elementor_process_settings().
	$post_data        = formscrm_elementor_process_settings( $crm_settings_raw );

	// Check connection status.
	$status_data = formscrm_check_connection_status( $post_data );

	// Store connection status HTML separately.
	$status_html = formscrm_get_connection_status_html( $post_data, 'elementor' );

	// If connection failed, return error with status HTML.
	if ( 'connected' !== $status_data['status'] ) {
		$error_msg = __( 'Could not connect to CRM', 'formscrm' );
		if ( ! empty( $status_data['error_message'] ) ) {
			$error_msg .= ': ' . $status_data['error_message'];
		}

		// Return error with status HTML for JavaScript to handle.
		wp_send_json_error(
			array(
				'message'     => $error_msg,
				'status_html' => $status_html,
			)
		);
	}

	// 2. Show modules dropdown
	$modules         = $crmlib->list_modules( $post_data );
	$settings_module = isset( $hidden_settings[ $crmtype ] ) ? $hidden_settings[ $crmtype ] : ''; ?>

	<div class="elementor-control-type-select elementor-label-block elementor-control-separator-before">
		<div class="elementor-control-content">
			<div class="elementor-control-field ">
				<label for="fc_crm_module" class="elementor-control-title"><?php esc_html_e( 'CRM Module', 'formscrm' ); ?></label>
				<div class="elementor-control-input-wrapper elementor-control-unit-5">
					<?php if ( empty( $modules ) ) { ?>
						<p style="color: red;"><?php esc_html_e( 'No modules found. Check your API credentials and try reconnecting.', 'formscrm' ); ?></p>
					<?php } else { ?>
					<select id="fc_crm_module">
						<?php
						foreach ( $modules as $module ) {
							$value = '';
							if ( ! empty( $module['value'] ) ) {
								$value = $module['value'];
							} elseif ( ! empty( $module['name'] ) ) {
								$value = $module['name'];
							}
							if ( empty( $value ) || ! isset( $module['label'] ) ) {
								continue;
							}
							echo '<option value="' . esc_html( $value ) . '" ';

							if ( $value ) {
								selected( $settings_module, $value );
							}

							echo '>' . esc_html( $module['label'] ) . '</option>';
						}
						?>
					</select>
					<?php } ?>
				</div>
			</div>
			<div class="elementor-control-field-description"></div>
		</div>
	</div>
	<?php
	// 3. Show settings for each module
	foreach ( $modules as $module ) {
		$value = '';
		if ( ! empty( $module['value'] ) ) {
			$value = $module['value'];
		} elseif ( ! empty( $module['name'] ) ) {
			$value = $module['name'];
		}
		if ( empty( $value ) || ! isset( $module['label'] ) ) {
			continue;
		}

		$crm_settings_raw = isset( $_POST['crmSettings'] ) ? wp_unslash( $_POST['crmSettings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in formscrm_elementor_process_settings().
		$post_data        = formscrm_elementor_process_settings( $crm_settings_raw );
		$crm_fields       = $crmlib->list_fields( $post_data, $value );

		if ( empty( $crm_fields ) || ! is_array( $crm_fields ) ) {
			continue;
		}
		?>

		<table class="elementor-map-table" cellspacing="0" cellpadding="0" data-module="<?php echo esc_html( $value ); ?>"><tbody>
			<tr class="elementor-map-row">
				<th class="elementor-map-column elementor-map-column-heading elementor-map-column-key"><?php esc_html_e( 'Field CRM', 'formscrm' ); ?></th>
				<th class="elementor-map-column elementor-map-column-heading elementor-map-column-value"><?php esc_html_e( 'Select Form Field', 'formscrm' ); ?></th>
			</tr>
			<?php

			$count_fields = 0;

			foreach ( $crm_fields as $crm_field ) {
				if ( empty( $crm_field['name'] ) ) {
					continue;
				}

				$crm_field_name  = sanitize_text_field( $crm_field['name'] );
				$crm_field_label = isset( $crm_field['label'] ) ? sanitize_text_field( $crm_field['label'] ) : '';
				$crm_field_req   = isset( $crm_field['req'] ) ? (bool) $crm_field['req'] : false;
				?>

				<tr class="elementor-map-row">
					<td class="elementor-map-column elementor-map-column-key">
						<label for="wpelementor-crm-field-<?php echo esc_html( $crm_field_name ); ?>">
						<?php
						echo esc_html( $crm_field_label );

						if ( $crm_field_req ) {
							echo ' <span class="required">*</span>';
						}
						?>
						</label>
					</td>
					<td class="elementor-map-column elementor-map-column-value">
						<select class="wide" name="fc_crm_field-<?php echo esc_html( $crm_field_name ); ?>" >
							<option value=""><?php esc_html_e( 'Select a field', 'formscrm' ); ?></option>
							<?php
							$form_fields     = isset( $_POST['formFields'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['formFields'] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
							$saved_field_val = ! empty( $hidden_settings[ 'fc_crm_field-' . $crm_field_name ] ) ? $hidden_settings[ 'fc_crm_field-' . $crm_field_name ] : '';
							foreach ( $form_fields as $form_name => $form_label ) {
								echo '<option value="' . esc_html( $form_name ) . '" ';
								selected( $saved_field_val, $form_name );
								echo '>' . esc_html( $form_label ) . '</option>';
							}

							/**
							 * Fires after the form field options in the "Select Form Field" dropdown.
							 * Use this to append extra option groups (e.g. UTM variables).
							 *
							 * @param string $saved_field_val Currently saved value for this CRM field.
							 * @param string $crm_field_name  CRM field machine name.
							 */
							do_action( 'formscrm_elementor_field_select_options', $saved_field_val, $crm_field_name );
							?>
						</select>
					</td>
				</tr>
				<?php
				++$count_fields;
			}
			if ( 0 === $count_fields ) {
				echo '<tr><td colspan="2">' . esc_html__( 'No fields found, or the connection has not got the right permissions.', 'formscrm' ) . '</td></tr>';
			}
			?>
		</tbody></table>
		<?php
	}

	$form_html = ob_get_clean();

	// Return success with both form HTML and status HTML.
	wp_send_json_success(
		array(
			'form_html'   => $form_html,
			'status_html' => $status_html,
		)
	);
}
