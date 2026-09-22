<?php
/**
 * Formidable Forms Wrapper
 *
 * Formidable Forms does not expose a public filter to inject a panel directly
 * into its native per-form "Build"/"Settings" tabs (unlike Contact Form 7's
 * `wpcf7_editor_panels`), so FormsCRM adds its own settings page, listed as a
 * submenu under the Formidable Forms admin menu, where a form is picked from
 * a dropdown and mapped to a CRM the same way Contact Form 7 is.
 *
 * @package   WordPress
 * @author    David Perez <david@closemarketing.es>
 * @copyright 2025 Closemarketing
 * @version   1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Library for Formidable Forms Settings
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2025 Closemarketing
 * @version    1.0
 */
class FORMSCRM_FormidableForms {
	/**
	 * CRM LIB external
	 *
	 * @var obj
	 */
	private $crmlib;

	/**
	 * Formidable field types that never carry submitted entry data.
	 *
	 * @var array
	 */
	private static $skip_field_types = array(
		'divider',
		'end_divider',
		'html',
		'break',
		'captcha',
		'heading',
		'spacer',
	);

	/**
	 * Construct of class
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'crm_save_options' ) );
		add_action( 'frm_after_create_entry', array( $this, 'crm_process_entry' ), 30, 3 );
	}

	/**
	 * Adds the FormsCRM settings page under the Formidable Forms admin menu.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		add_submenu_page(
			'formidable',
			__( 'FormsCRM', 'formscrm' ),
			__( 'FormsCRM', 'formscrm' ),
			'manage_options',
			'formscrm-formidable',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Saves the per-form CRM settings.
	 *
	 * @return void
	 */
	public function crm_save_options() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing check, nonce verified below.
		if ( ! isset( $_GET['page'] ) || 'formscrm-formidable' !== $_GET['page'] ) {
			return;
		}

		if ( ! isset( $_POST['formscrm_formidable_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['formscrm_formidable_nonce'] ) ), 'formscrm_formidable_save' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		if ( ! $form_id ) {
			return;
		}

		$crm_data = array();
		if ( isset( $_POST['formscrm_formidable'] ) && is_array( $_POST['formscrm_formidable'] ) ) {
			$crm_data = array_map( 'sanitize_text_field', wp_unslash( $_POST['formscrm_formidable'] ) );
		}

		update_option( 'frm_crm_' . $form_id, array_filter( $crm_data ) );

		wp_safe_redirect( admin_url( 'admin.php?page=formscrm-formidable&form_id=' . $form_id . '&updated=1' ) );
		exit;
	}

	/**
	 * Renders the FormsCRM settings page for Formidable Forms.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$forms = array();
		if ( class_exists( 'FrmForm' ) ) {
			$forms = FrmForm::getAll( array( 'is_template' => 0 ), 'name' );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only form selector, no state change.
		$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
		if ( ! $form_id && ! empty( $forms ) ) {
			$first_form = reset( $forms );
			$form_id    = ! empty( $first_form->id ) ? absint( $first_form->id ) : 0;
		}

		$frm_crm = $form_id ? get_option( 'frm_crm_' . $form_id, array() ) : array();
		$module  = isset( $frm_crm['fc_crm_module'] ) ? $frm_crm['fc_crm_module'] : '';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'FormsCRM - Formidable Forms', 'formscrm' ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice flag, no state change. ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'formscrm' ); ?></p></div>
			<?php endif; ?>

			<form method="get">
				<input type="hidden" name="page" value="formscrm-formidable" />
				<p>
					<label for="formscrm-frm-form-select"><?php esc_html_e( 'Formidable Form:', 'formscrm' ); ?></label><br />
					<select name="form_id" id="formscrm-frm-form-select" onchange="this.form.submit()">
						<?php if ( empty( $forms ) ) : ?>
							<option value=""><?php esc_html_e( 'No Formidable forms found.', 'formscrm' ); ?></option>
						<?php else : ?>
							<?php foreach ( $forms as $form ) : ?>
								<option value="<?php echo esc_attr( $form->id ); ?>" <?php selected( $form_id, $form->id ); ?>>
									<?php echo esc_html( ! empty( $form->name ) ? $form->name : ( 'Form #' . $form->id ) ); ?>
								</option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				</p>
			</form>

			<?php if ( $form_id ) : ?>
			<form method="post">
				<?php wp_nonce_field( 'formscrm_formidable_save', 'formscrm_formidable_nonce' ); ?>
				<input type="hidden" name="form_id" value="<?php echo esc_attr( $form_id ); ?>" />

				<p>
					<label for="fc_crm_type"><?php esc_html_e( 'CRM Type:', 'formscrm' ); ?></label><br />
					<select name="formscrm_formidable[fc_crm_type]" id="fc_crm_type">
						<option value=""><?php esc_html_e( 'Select a CRM', 'formscrm' ); ?></option>
						<?php
						foreach ( formscrm_get_choices() as $choice ) {
							echo '<option value="' . esc_attr( $choice['value'] ) . '" ';
							if ( isset( $frm_crm['fc_crm_type'] ) ) {
								selected( $frm_crm['fc_crm_type'], $choice['value'] );
							}
							echo '>' . esc_html( $choice['label'] ) . '</option>';
						}
						?>
					</select>
				</p>

				<?php if ( ! empty( $frm_crm['fc_crm_type'] ) ) : ?>

					<?php
					foreach ( formscrm_get_crm_field_definitions() as $def ) {
						$dependency = call_user_func( $def['dependency'] );
						if ( ! in_array( $frm_crm['fc_crm_type'], $dependency, true ) ) {
							continue;
						}
						$field_name = 'fc_crm_' . $def['name'];
						$field_id   = 'formscrm-frm-' . $field_name;
						$value      = isset( $frm_crm[ $field_name ] ) ? $frm_crm[ $field_name ] : '';
						?>
					<p>
						<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $def['label'] ); ?>:</label><br />
						<?php
						if ( 'select' === $def['type'] && ! empty( $def['choices'] ) ) {
							?>
						<select id="<?php echo esc_attr( $field_id ); ?>" name="formscrm_formidable[<?php echo esc_attr( $field_name ); ?>]" class="wide" size="1">
							<?php
							foreach ( $def['choices'] as $choice ) {
								echo '<option value="' . esc_attr( $choice['value'] ) . '" ' . selected( $value, $choice['value'], false ) . '>' . esc_html( $choice['label'] ) . '</option>';
							}
							?>
						</select>
							<?php
						} else {
							$input_type = 'api_key' === $def['type'] ? 'password' : 'text';
							?>
						<input type="<?php echo esc_attr( $input_type ); ?>" id="<?php echo esc_attr( $field_id ); ?>" name="formscrm_formidable[<?php echo esc_attr( $field_name ); ?>]" class="regular-text" placeholder="<?php echo esc_attr( $def['label'] ); ?>" value="<?php echo esc_attr( $value ); ?>" />
							<?php
						}
						?>
					</p>
					<?php } ?>

					<?php
					$this->crmlib = formscrm_get_api_class( $frm_crm['fc_crm_type'] );
					?>

					<?php if ( ! empty( $frm_crm['fc_crm_type'] ) ) : ?>
						<?php formscrm_render_connection_status( $frm_crm, 'notice' ); ?>
					<?php endif; ?>

					<?php if ( ! empty( $this->crmlib ) ) : ?>
					<p>
						<label for="fc_crm_module"><?php esc_html_e( 'CRM Module:', 'formscrm' ); ?></label><br />
						<select name="formscrm_formidable[fc_crm_module]" id="fc_crm_module">
							<?php
							$modules = $this->crmlib->list_modules( $frm_crm );
							foreach ( $modules as $crm_module ) {
								$module_value = '';
								if ( ! empty( $crm_module['value'] ) ) {
									$module_value = $crm_module['value'];
								} elseif ( ! empty( $crm_module['name'] ) ) {
									$module_value = $crm_module['name'];
								}
								if ( empty( $module_value ) || ! isset( $crm_module['label'] ) ) {
									continue;
								}
								echo '<option value="' . esc_attr( $module_value ) . '" ';
								selected( $module, $module_value );
								echo '>' . esc_html( $crm_module['label'] ) . '</option>';
							}
							?>
						</select>
					</p>

						<?php if ( $module && class_exists( 'FrmField' ) ) : ?>
							<?php
							$crm_fields  = $this->crmlib->list_fields( $frm_crm, $module );
							$form_fields = FrmField::getAll( array( 'form_id' => $form_id ) );
							?>
							<?php if ( ! empty( $crm_fields ) && is_array( $crm_fields ) ) : ?>
						<table class="widefat" cellspacing="0" cellpadding="0">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Field CRM', 'formscrm' ); ?></th>
									<th><?php esc_html_e( 'Select Form Field', 'formscrm' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ( $crm_fields as $crm_field ) {
									if ( empty( $crm_field['name'] ) ) {
										continue;
									}
									$crm_field_name  = sanitize_text_field( $crm_field['name'] );
									$crm_field_label = isset( $crm_field['label'] ) ? sanitize_text_field( $crm_field['label'] ) : '';
									$crm_field_req   = isset( $crm_field['req'] ) ? (bool) $crm_field['req'] : false;
									?>
									<tr>
										<td>
											<label for="formscrm-frm-crm-field-<?php echo esc_attr( $crm_field_name ); ?>">
												<?php
												echo esc_html( $crm_field_label );
												if ( $crm_field_req ) {
													echo ' <span class="required">*</span>';
												}
												?>
											</label>
										</td>
										<td>
											<select id="formscrm-frm-crm-field-<?php echo esc_attr( $crm_field_name ); ?>" name="formscrm_formidable[fc_crm_field-<?php echo esc_attr( $crm_field_name ); ?>]" style="min-width:300px;">
												<option value=""><?php esc_html_e( 'Select a field', 'formscrm' ); ?></option>
												<?php
												foreach ( (array) $form_fields as $form_field ) {
													if ( empty( $form_field->type ) || in_array( $form_field->type, self::$skip_field_types, true ) ) {
														continue;
													}
													$field_value = ! empty( $form_field->field_key ) ? $form_field->field_key : $form_field->id;
													$field_label = ! empty( $form_field->name ) ? $form_field->name : $field_value;
													echo '<option value="' . esc_attr( $field_value ) . '" ';
													if ( isset( $frm_crm[ 'fc_crm_field-' . $crm_field_name ] ) ) {
														selected( $frm_crm[ 'fc_crm_field-' . $crm_field_name ], $field_value );
													}
													echo '>' . esc_html( $field_label ) . '</option>';
												}
												?>
											</select>
										</td>
									</tr>
									<?php
								}
								?>
							</tbody>
						</table>
						<?php else : ?>
							<p><?php esc_html_e( 'No fields found. Reconnect your CRM.', 'formscrm' ); ?></p>
						<?php endif; ?>
					<?php endif; ?>
					<?php endif; ?>
				<?php endif; ?>

				<p><?php submit_button( __( 'Save Settings', 'formscrm' ) ); ?></p>
			</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Processes a new Formidable Forms entry after it is created.
	 *
	 * @param int   $entry_id Formidable entry ID.
	 * @param int   $form_id  Formidable form ID.
	 * @param array $args     Extra arguments; 'is_child' is truthy for repeater/embedded child entries.
	 * @return void
	 */
	public function crm_process_entry( $entry_id, $form_id, $args = array() ) {
		// Repeater/embedded child entries are not top-level submissions.
		if ( ! empty( $args['is_child'] ) ) {
			return;
		}

		$form_id = absint( $form_id );
		if ( ! $form_id ) {
			return;
		}

		$frm_crm  = get_option( 'frm_crm_' . $form_id, array() );
		$crm_type = ! empty( $frm_crm['fc_crm_type'] ) ? sanitize_text_field( $frm_crm['fc_crm_type'] ) : '';

		// No CRM configured for this form, nothing to do.
		if ( '' === $crm_type ) {
			return;
		}

		$form_name = '';
		if ( class_exists( 'FrmForm' ) ) {
			$form      = FrmForm::getOne( $form_id );
			$form_name = ! empty( $form->name ) ? $form->name : '';
		}

		$form_info = array(
			'form_type'       => 'formidableforms',
			'form_type_title' => 'Formidable Forms',
			'form_id'         => $form_id,
			'form_name'       => $form_name,
			'entry_id'        => $entry_id,
		);

		$this->crmlib = formscrm_get_api_class( $crm_type );
		if ( empty( $this->crmlib ) ) {
			/* translators: %s: CRM type slug */
			formscrm_alert_error( $crm_type, sprintf( __( 'CRM class not found for type: %s', 'formscrm' ), $crm_type ), array(), '', '', $form_info );
			return;
		}

		if ( ! class_exists( 'FrmEntry' ) || ! class_exists( 'FrmField' ) ) {
			formscrm_alert_error( $crm_type, __( 'Formidable Forms entry/field classes are not available.', 'formscrm' ), array(), '', '', $form_info );
			return;
		}

		$entry = FrmEntry::getOne( $entry_id, true );
		if ( empty( $entry ) ) {
			formscrm_alert_error( $crm_type, __( 'Could not load the Formidable Forms entry.', 'formscrm' ), array(), '', '', $form_info );
			return;
		}

		$fields       = FrmField::getAll( array( 'form_id' => $form_id ) );
		$field_values = self::get_field_values( $entry, $fields );
		$merge_vars   = self::get_merge_vars( $frm_crm, $field_values );
		$merge_vars   = apply_filters( 'formscrm_merge_vars_before_send', $merge_vars, $frm_crm, array() );

		$frm_crm['formscrm_form_type'] = 'formidableforms';

		// The CRM call is wrapped so a failure never blocks or breaks the Formidable submission.
		try {
			$response_result = $this->crmlib->create_entry( $frm_crm, $merge_vars );
		} catch ( Exception $e ) {
			formscrm_alert_error( $crm_type, __( 'Error sending information to CRM: ', 'formscrm' ) . $e->getMessage(), $merge_vars, '', '', $form_info );
			return;
		}

		if ( ! is_array( $response_result ) ) {
			formscrm_alert_error( $crm_type, __( 'The CRM did not return a valid response.', 'formscrm' ), $merge_vars, '', '', $form_info );
			return;
		}

		if ( isset( $response_result['status'] ) && 'error' === $response_result['status'] ) {
			$url   = isset( $response_result['url'] ) ? $response_result['url'] : '';
			$query = isset( $response_result['query'] ) ? $response_result['query'] : '';

			formscrm_alert_error( $crm_type, 'Error ' . $response_result['message'], $merge_vars, $url, $query, $form_info );
		} else {
			// CRM classes may report a display name (e.g. "Holded v2") via the create_entry() result.
			$crm_name = formscrm_get_crm_display_name( $response_result, $crm_type );
			formscrm_debug_message( 'Success creating ' . $crm_name . ' Entry ID: ' . ( $response_result['id'] ?? '' ) );
		}
	}

	/**
	 * Builds a flat field-key/id => value map from a Formidable entry and its fields.
	 *
	 * Non-data field types are skipped, file fields resolve to attachment URLs,
	 * and multi-value fields (checkboxes, multi-select) are flattened to a
	 * comma-joined string. Each value is stored under both the field's key and
	 * its numeric ID so mappings saved either way keep working.
	 *
	 * @param object $entry  Formidable entry object as returned by FrmEntry::getOne( $id, true ).
	 * @param array  $fields Formidable fields as returned by FrmField::getAll().
	 * @return array
	 */
	public static function get_field_values( $entry, $fields ) {
		$field_values = array();

		if ( empty( $fields ) || ! is_array( $fields ) ) {
			return $field_values;
		}

		$metas = ( is_object( $entry ) && ! empty( $entry->metas ) && is_array( $entry->metas ) ) ? $entry->metas : array();

		foreach ( $fields as $field ) {
			$field_type = isset( $field->type ) ? $field->type : '';

			if ( '' === $field_type || in_array( $field_type, self::$skip_field_types, true ) ) {
				continue;
			}

			$field_id  = isset( $field->id ) ? $field->id : 0;
			$field_key = ! empty( $field->field_key ) ? $field->field_key : $field_id;

			$value = array_key_exists( $field_id, $metas ) ? $metas[ $field_id ] : '';
			$value = maybe_unserialize( $value );

			if ( 'file' === $field_type ) {
				$value = self::resolve_file_field_value( $value );
			} elseif ( is_array( $value ) ) {
				$value = implode( ',', array_map( 'strval', $value ) );
			}

			$field_values[ $field_id ]  = $value;
			$field_values[ $field_key ] = $value;
		}

		return $field_values;
	}

	/**
	 * Resolves a file field's stored attachment ID(s) to their URL(s).
	 *
	 * @param mixed $value Raw entry meta value for a `file` field.
	 * @return string Comma-joined attachment URL(s), or an empty string.
	 */
	private static function resolve_file_field_value( $value ) {
		$attachment_ids = is_array( $value ) ? $value : array( $value );
		$urls           = array();

		foreach ( $attachment_ids as $attachment_id ) {
			$attachment_id = absint( $attachment_id );
			if ( ! $attachment_id ) {
				continue;
			}

			$url = wp_get_attachment_url( $attachment_id );
			if ( $url ) {
				$urls[] = $url;
			}
		}

		return implode( ',', $urls );
	}

	/**
	 * Extract merge variables from the saved settings and the built field-value map.
	 *
	 * @param array $frm_crm      Array settings from CRM, including `fc_crm_field-*` mappings.
	 * @param array $field_values Field-key/id => value map, as returned by get_field_values().
	 * @return array
	 */
	public static function get_merge_vars( $frm_crm, $field_values ) {
		if ( empty( $frm_crm ) || ! is_array( $frm_crm ) ) {
			return array();
		}

		$merge_vars = array();
		foreach ( $frm_crm as $key => $mapped_field ) {
			if ( false === strpos( $key, 'fc_crm_field' ) ) {
				continue;
			}
			$crm_key = str_replace( 'fc_crm_field-', '', $key );
			$value   = array_key_exists( $mapped_field, $field_values ) ? $field_values[ $mapped_field ] : '';

			if ( is_array( $value ) ) {
				$value = implode( ',', $value );
			}

			$merge_vars[] = array(
				'name'  => $crm_key,
				'value' => $value,
			);
		}

		return $merge_vars;
	}
}

new FORMSCRM_FormidableForms();
