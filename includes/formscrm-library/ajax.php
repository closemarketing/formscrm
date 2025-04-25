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

add_action( 'wp_ajax_elementor_formscrm_connect_crm', 'elementor_formscrm_connect_crm' );

function elementor_formscrm_connect_crm() {

	// nonce
	if ( ! check_ajax_referer( 'formcrm_nonce', 'nonce', false ) ) {
		wp_send_json_error( __( 'Security check failed', 'formscrm' ) );
	}

	// get options
	if ( empty( $_POST['crmSettings'] ) ) {
		wp_send_json_error( __( 'No settings found', 'formscrm' ) );
	}

	$hidden_settings = empty( $_POST['hiddenSettings'] ) ? array() : json_decode( stripslashes_deep($_POST['hiddenSettings']), true );

	ob_start();
	// 1. Check connection to CRM
	$crmtype = $_POST['crmSettings']['fc_crm_type'];
	$crmlib  = null;

	if ( empty( $crmtype ) ) {
		wp_send_json_error( __( 'No CRM selected', 'formscrm' ) );
	}

	$crmname      = strtolower( $crmtype );
	$crmclassname = str_replace( ' ', '', $crmname );
	$crmclassname = 'CRMLIB_' . strtoupper( $crmclassname );
	$crmname      = str_replace( ' ', '_', $crmname );

	$array_path = formscrm_get_crmlib_path();
	if ( isset( $array_path[ $crmname ] ) ) {
		include_once $array_path[ $crmname ];
	}

	formscrm_debug_message( $array_path[ $crmname ] );

	if ( ! class_exists( $crmclassname ) ) {
		wp_send_json_error( __( 'Class not found', 'formscrm' ) );
	}

	$crmlib = new $crmclassname();

	// TODO maybe check connection to crm with login pass and everything we need

	// 2. Show modules dropdown
	$modules = $crmlib->list_modules( $_POST['crmSettings'] );
	$settings_module = isset( $hidden_settings[$crmtype] ) ? $hidden_settings[$crmtype] : ''; ?>

	<div class="elementor-control-type-select elementor-label-block elementor-control-separator-before">
		<div class="elementor-control-content">
			<div class="elementor-control-field ">
				<label for="fc_crm_module" class="elementor-control-title">CRM Module</label>
				<div class="elementor-control-input-wrapper elementor-control-unit-5">
					<select id="fc_crm_module"><?php
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

						if ( isset( $value ) ) {
							selected( $settings_module, $value );
						}

						echo '>' . esc_html( $module['label'] ) . '</option>';
					} ?>
					</select>
				</div>
			</div>
			<div class="elementor-control-field-description"></div>
		</div>
	</div><?php

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

		$crm_fields = $crmlib->list_fields( $_POST['crmSettings'], $value );

		if ( empty( $crm_fields ) || ! is_array( $crm_fields ) ) {
			continue;
		} ?>

		<table class="elementor-map-table" cellspacing="0" cellpadding="0" data-module="<?php echo $value; ?>"><tbody>
			<tr class="elementor-map-row">
				<th class="elementor-map-column elementor-map-column-heading elementor-map-column-key"><?php esc_html_e( 'Field CRM', 'formscrm' ); ?></th>
				<th class="elementor-map-column elementor-map-column-heading elementor-map-column-value"><?php esc_html_e( 'Select Form Field', 'formscrm' ); ?></th>
			</tr><?php

			$count_fields = 0;

			foreach ( $crm_fields as $crm_field ) {
				if ( empty( $crm_field['name'] ) ) {
					continue;
				}

				$crm_field_name  = sanitize_text_field( $crm_field['name'] );
				$crm_field_label = isset( $crm_field['label'] ) ? sanitize_text_field( $crm_field['label'] ) : '';
				$crm_field_req   = isset( $crm_field['req'] ) ? (bool) $crm_field['req'] : false; ?>

				<tr class="elementor-map-row">
					<td class="elementor-map-column elementor-map-column-key">
						<label for="wpelementor-crm-field-<?php echo esc_html( $crm_field_name ); ?>"><?php

						echo esc_html( $crm_field_label );

						if ( isset( $crm_field_req ) && $crm_field_req ) {
							echo ' <span class="required">*</span>';
						} ?>
						</label>
					</td>
					<td class="elementor-map-column elementor-map-column-value">
						<select class="wide" name="fc_crm_field-<?php esc_html_e( $crm_field_name ); ?>" >
							<option value=""><?php esc_html_e( 'Select a field', 'formscrm' ); ?></option><?php
							foreach ( $_POST['formFields'] as $form_name => $form_label ) {
								echo '<option value="' . esc_html( $form_name ) . '" ';

								if ( !empty( $hidden_settings[ 'fc_crm_field-' . $crm_field_name ] ) ) selected( $hidden_settings[ 'fc_crm_field-' . $crm_field_name ], $form_name );

								echo '>' . esc_html( $form_label ) . '</option>';
							} ?>
						</select>
					</td>
				</tr><?php

				$count_fields++;
			}
			if ( 0 === $count_fields ) {
				echo '<tr><td colspan="2">' . esc_html__( 'No fields found, or the connection has not got the right permissions.', 'formscrm' ) . '</td></tr>';
			} ?>
		</tbody></table><?php
	}

	wp_send_json_success( ob_get_clean() );
}
