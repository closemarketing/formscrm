<?php
/**
 * FormsCRM JetFormBuilder Action
 *
 * Registered via jet-form-builder/actions/register. Handles the server-side
 * execution of the action when a JFB form is submitted.
 *
 * @package   WordPress
 * @author    David Perez <david@closemarketing.es>
 * @copyright 2024 Closemarketing
 * @version   2.0
 */

defined( 'ABSPATH' ) || exit;

use Jet_Form_Builder\Actions\Action_Handler;
use Jet_Form_Builder\Actions\Types\Base;
use Jet_Form_Builder\Exceptions\Action_Exception;

/**
 * FormsCRM action type for JetFormBuilder.
 */
class FORMSCRM_JFB_Action extends Base {

	/**
	 * Links this action to the global settings tab saved by FORMSCRM_JFB_Tab_Handler.
	 *
	 * @var string
	 */
	public $option_name = FORMSCRM_JFB_Tab_Handler::SLUG;

	/**
	 * Returns the action type ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'formscrm';
	}

	/**
	 * Returns the human-readable action name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return __( 'FormsCRM', 'formscrm' );
	}

	/**
	 * Labels passed to the JS editor component via wp_localize_script.
	 *
	 * @return array<string,string>
	 */
	public function editor_labels(): array {
		return array(
			'use_global'         => __( 'Use Global Settings', 'formscrm' ),
			'fc_crm_type'        => __( 'CRM Type', 'formscrm' ),
			'fc_crm_url'         => __( 'URL', 'formscrm' ),
			'fc_crm_username'    => __( 'Username', 'formscrm' ),
			'fc_crm_password'    => __( 'Password', 'formscrm' ),
			'fc_crm_apipassword' => __( 'API Key / Password', 'formscrm' ),
			'fc_crm_apisales'    => __( 'API Sales', 'formscrm' ),
			'fc_crm_odoodb'      => __( 'Odoo DB', 'formscrm' ),
			'fc_crm_module'      => __( 'CRM Module', 'formscrm' ),
			'fields_map'         => __( 'Fields Map', 'formscrm' ),
			'fetch_modules'      => __( 'Fetch Modules', 'formscrm' ),
			'fetch_fields'       => __( 'Fetch Fields', 'formscrm' ),
		);
	}

	/**
	 * Execute when a form is submitted.
	 *
	 * @param array          $request  Submitted form field values keyed by field name.
	 * @param Action_Handler $handler  JFB action handler.
	 * @throws Action_Exception On CRM error.
	 */
	public function do_action( array $request, Action_Handler $handler ): void {
		// Merge per-action settings with global defaults.
		$settings = $this->global_settings(
			array(
				'fc_crm_type'        => '',
				'fc_crm_url'         => '',
				'fc_crm_username'    => '',
				'fc_crm_password'    => '',
				'fc_crm_apipassword' => '',
				'fc_crm_apisales'    => '',
				'fc_crm_odoodb'      => '',
			)
		);

		// Per-action overrides take precedence over globals.
		foreach ( $settings as $key => $global_val ) {
			if ( ! empty( $this->settings[ $key ] ) ) {
				$settings[ $key ] = $this->settings[ $key ];
			}
		}

		$crm_type = sanitize_text_field( $settings['fc_crm_type'] );
		if ( ! $crm_type ) {
			return;
		}

		$crmlib = formscrm_get_api_class( $crm_type );
		if ( ! $crmlib ) {
			return;
		}

		$module = sanitize_text_field( $this->settings['fc_crm_module'] ?? '' );
		if ( ! $module ) {
			return;
		}

		$settings['fc_crm_module'] = $module;

		$merge_vars = $this->build_merge_vars( $request );
		$merge_vars = apply_filters( 'formscrm_merge_vars_before_send', $merge_vars, $settings );
		$result     = $crmlib->create_entry( $settings, $merge_vars );

		if ( isset( $result['status'] ) && 'error' === $result['status'] ) {
			$form_id   = $handler->form_id ?? 0;
			$form_info = array(
				'form_type'       => 'jetformbuilder',
				'form_type_title' => 'JetFormBuilder',
				'form_id'         => $form_id,
				'form_name'       => get_the_title( $form_id ),
			);
			formscrm_alert_error(
				$crm_type,
				'Error ' . $result['message'],
				$merge_vars,
				$result['url'] ?? '',
				$result['query'] ?? '',
				$form_info
			);
		} else {
			// CRM classes may report a display name (e.g. "Holded v2") via the create_entry() result.
			$crm_name = ! empty( $result['fc_crm_name'] ) ? $result['fc_crm_name'] : $crm_type;
			error_log( 'FormsCRM: Success creating ' . $crm_name . ' Entry ID: ' . ( $result['id'] ?? '' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional logging for debugging.
		}
	}

	/**
	 * Build the merge-vars array from the fields_map setting and submitted data.
	 *
	 * @param array $request Submitted form data.
	 * @return array
	 */
	private function build_merge_vars( array $request ): array {
		$fields_map = $this->settings['fields_map'] ?? array();
		$merge_vars = array();

		foreach ( $fields_map as $crm_field_name => $form_field_name ) {
			if ( empty( $crm_field_name ) ) {
				continue;
			}
			$value = $request[ $form_field_name ] ?? $form_field_name;
			if ( is_array( $value ) ) {
				$value = implode( ',', $value );
			}
			$value        = $this->fill_dynamic_value( (string) $value, $request );
			$merge_vars[] = array(
				'name'  => $crm_field_name,
				'value' => $value,
			);
		}

		return $merge_vars;
	}

	/**
	 * Replace {id:field_name} placeholders with live submission values.
	 *
	 * @param string $value         Field value possibly containing placeholders.
	 * @param array  $submitted_data All submitted form data.
	 * @return string
	 */
	private function fill_dynamic_value( string $value, array $submitted_data ): string {
		if ( false === strpos( $value, '{id:' ) ) {
			return $value;
		}
		preg_match_all( '/{([^}]*)}/', $value, $matches );
		foreach ( $matches[1] ?? array() as $match ) {
			$parts = explode( ':', $match );
			if ( 'id' !== ( $parts[0] ?? '' ) || empty( $parts[1] ) ) {
				continue;
			}
			$field_val = $submitted_data[ $parts[1] ] ?? '';
			if ( is_array( $field_val ) ) {
				$field_val = implode( ', ', $field_val );
			}
			$value = str_replace( '{' . $match . '}', $field_val, $value );
		}
		return $value;
	}
}
