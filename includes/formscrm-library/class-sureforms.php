<?php
/**
 * SureForms Wrapper
 *
 * @package   WordPress
 * @author    David Perez <david@closemarketing.es>
 * @copyright 2026 Closemarketing
 * @version   1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Library for SureForms Settings
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2026 Closemarketing
 * @version    1.0
 */
class FORMSCRM_SureForms {

	/**
	 * SureForms form post type slug.
	 *
	 * @var string
	 */
	const POST_TYPE = 'sureforms_form';

	/**
	 * Post meta key storing the CRM settings for a form.
	 *
	 * @var string
	 */
	const META_KEY = '_formscrm_sureforms_settings';

	/**
	 * CRM LIB external
	 *
	 * @var obj
	 */
	private $crmlib;

	/**
	 * Construct of class
	 */
	public function __construct() {
		add_action( 'add_meta_boxes_' . self::POST_TYPE, array( $this, 'add_metabox' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'crm_save_options' ) );
		add_action( 'srfm_form_submit', array( $this, 'crm_process_entry' ) );
	}

	/**
	 * Registers the FormsCRM metabox in the SureForms form editor.
	 *
	 * @return void
	 */
	public function add_metabox() {
		add_meta_box(
			'formscrm-sureforms',
			__( 'FormsCRM', 'formscrm' ),
			array( $this, 'settings_add_crm' ),
			self::POST_TYPE,
			'normal',
			'default'
		);
	}

	/**
	 * Renders the CRM settings metabox.
	 *
	 * @param WP_Post $post Current SureForms form post.
	 * @return void
	 */
	public function settings_add_crm( $post ) {
		$settings        = get_post_meta( $post->ID, self::META_KEY, true );
		$settings        = is_array( $settings ) ? $settings : array();
		$settings_module = isset( $settings['fc_crm_module'] ) ? $settings['fc_crm_module'] : '';

		wp_nonce_field( 'formscrm_sureforms_save', 'formscrm_sureforms_nonce' );
		?>
		<div class="metabox-holder">
			<div class="cme-main-fields">
				<p>
					<label for="fc_crm_type"><?php esc_html_e( 'CRM Type:', 'formscrm' ); ?></label><br />
					<select name="formscrm_sureforms[fc_crm_type]" class="medium" id="fc_crm_type">
						<option value=""><?php esc_html_e( 'Select a CRM', 'formscrm' ); ?></option>
						<?php
						foreach ( formscrm_get_choices() as $choice ) {
							echo '<option value="' . esc_attr( $choice['value'] ) . '" ';
							if ( isset( $settings['fc_crm_type'] ) ) {
								selected( $settings['fc_crm_type'], $choice['value'] );
							}
							echo '>' . esc_html( $choice['label'] ) . '</option>';
						}
						?>
					</select>
				</p>
				<?php if ( ! empty( $settings['fc_crm_type'] ) ) : ?>

					<?php if ( in_array( $settings['fc_crm_type'], formscrm_get_dependency_url(), true ) ) : ?>
					<p>
						<label for="formscrm-sureforms-fc_crm_url"><?php esc_html_e( 'URL:', 'formscrm' ); ?></label><br />
						<input type="text" id="formscrm-sureforms-fc_crm_url" name="formscrm_sureforms[fc_crm_url]" class="wide" size="70" placeholder="<?php esc_attr_e( 'CRM URL', 'formscrm' ); ?>" value="<?php echo isset( $settings['fc_crm_url'] ) ? esc_attr( $settings['fc_crm_url'] ) : ''; ?>" />
					</p>
					<?php endif; ?>

					<?php if ( in_array( $settings['fc_crm_type'], formscrm_get_dependency_username(), true ) ) : ?>
					<p>
						<label for="formscrm-sureforms-fc_crm_username"><?php esc_html_e( 'Username:', 'formscrm' ); ?></label><br />
						<input type="text" id="formscrm-sureforms-fc_crm_username" name="formscrm_sureforms[fc_crm_username]" class="wide" size="70" placeholder="<?php esc_attr_e( 'Username', 'formscrm' ); ?>" value="<?php echo isset( $settings['fc_crm_username'] ) ? esc_attr( $settings['fc_crm_username'] ) : ''; ?>" />
					</p>
					<?php endif; ?>

					<?php if ( in_array( $settings['fc_crm_type'], formscrm_get_dependency_password(), true ) ) : ?>
					<p>
						<label for="formscrm-sureforms-fc_crm_password"><?php esc_html_e( 'Password:', 'formscrm' ); ?></label><br />
						<input type="password" id="formscrm-sureforms-fc_crm_password" name="formscrm_sureforms[fc_crm_password]" class="wide" size="70" placeholder="<?php esc_attr_e( 'CRM Password', 'formscrm' ); ?>" value="<?php echo isset( $settings['fc_crm_password'] ) ? esc_attr( $settings['fc_crm_password'] ) : ''; ?>" />
					</p>
					<?php endif; ?>

					<?php if ( in_array( $settings['fc_crm_type'], formscrm_get_dependency_apipassword(), true ) ) : ?>
					<p>
						<label for="formscrm-sureforms-fc_crm_apipassword"><?php esc_html_e( 'API Password:', 'formscrm' ); ?></label><br />
						<input type="password" id="formscrm-sureforms-fc_crm_apipassword" name="formscrm_sureforms[fc_crm_apipassword]" class="wide" size="70" placeholder="<?php esc_attr_e( 'CRM API Password', 'formscrm' ); ?>" value="<?php echo isset( $settings['fc_crm_apipassword'] ) ? esc_attr( $settings['fc_crm_apipassword'] ) : ''; ?>" />
					</p>
					<?php endif; ?>

					<?php if ( in_array( $settings['fc_crm_type'], formscrm_get_dependency_apisales(), true ) ) : ?>
					<p>
						<label for="formscrm-sureforms-fc_crm_apisales"><?php esc_html_e( 'API Sales:', 'formscrm' ); ?></label><br />
						<input type="text" id="formscrm-sureforms-fc_crm_apisales" name="formscrm_sureforms[fc_crm_apisales]" class="wide" size="70" placeholder="<?php esc_attr_e( 'CRM Sales', 'formscrm' ); ?>" value="<?php echo isset( $settings['fc_crm_apisales'] ) ? esc_attr( $settings['fc_crm_apisales'] ) : ''; ?>" />
					</p>
					<?php endif; ?>

					<?php if ( in_array( $settings['fc_crm_type'], formscrm_get_dependency_odoodb(), true ) ) : ?>
					<p>
						<label for="formscrm-sureforms-fc_crm_odoodb"><?php esc_html_e( 'Odoo DB:', 'formscrm' ); ?></label><br />
						<input type="text" id="formscrm-sureforms-fc_crm_odoodb" name="formscrm_sureforms[fc_crm_odoodb]" class="wide" size="70" placeholder="<?php esc_attr_e( 'Odoo DB', 'formscrm' ); ?>" value="<?php echo isset( $settings['fc_crm_odoodb'] ) ? esc_attr( $settings['fc_crm_odoodb'] ) : ''; ?>" />
					</p>
					<?php endif; ?>

					<?php
					$this->crmlib = formscrm_get_api_class( $settings['fc_crm_type'] );
					$modules      = ! empty( $this->crmlib ) ? $this->crmlib->list_modules( $settings ) : array();
					?>
					<p>
						<label for="fc_crm_module"><?php esc_html_e( 'CRM Module:', 'formscrm' ); ?></label><br />
						<select name="formscrm_sureforms[fc_crm_module]" class="medium" id="fc_crm_module">
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
								echo '<option value="' . esc_attr( $value ) . '" ';
								selected( $settings_module, $value );
								echo '>' . esc_html( $module['label'] ) . '</option>';
							}
							if ( empty( $settings_module ) || ! in_array( $settings_module, array_column( $modules, 'value' ), true ) ) {
								$settings_module = ! empty( $modules[0]['value'] ) ? $modules[0]['value'] : '';
							}
							?>
						</select>
					</p>
				<?php endif; ?>
			</div>
			<?php
			if ( ! empty( $settings['fc_crm_type'] ) ) {
				formscrm_render_connection_status( $settings, 'html' );
			}

			$login_ok = true;
			if ( ! empty( $this->crmlib ) ) {
				$login_crm = $this->crmlib->login( $settings );
				if ( ! $login_crm || ( is_array( $login_crm ) && isset( $login_crm['status'] ) && 'error' === $login_crm['status'] ) ) {
					echo '<p style="color: red;">' . esc_html( $login_crm['message'] ) . '</p>';
					$login_ok = false;
				}
			}

			if ( $login_ok && $settings_module && ! empty( $this->crmlib ) ) {
				$crm_fields  = $this->crmlib->list_fields( $settings, $settings_module );
				$form_fields = $this->get_form_fields( $post->ID );

				if ( ! empty( $crm_fields ) && is_array( $crm_fields ) ) {
					?>
				<table class="cf7-map-table" cellspacing="0" cellpadding="0">
					<tbody>
						<tr class="cf7-map-row">
							<th class="cf7-map-column cf7-map-column-heading cf7-map-column-key"><?php esc_html_e( 'Field CRM', 'formscrm' ); ?></th>
							<th class="cf7-map-column cf7-map-column-heading cf7-map-column-value"><?php esc_html_e( 'Select Form Field', 'formscrm' ); ?></th>
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
							<tr class="cf7-map-row">
									<td class="cf7-map-column cf7-map-column-key">
										<label for="formscrm-sureforms-field-<?php echo esc_attr( $crm_field_name ); ?>">
											<?php
											echo esc_html( $crm_field_label );
											if ( $crm_field_req ) {
												echo ' <span class="required">*</span>';
											}
											?>
										</label>
									</td>
									<td class="cf7-map-column cf7-map-column-value">
										<select class="wide" id="formscrm-sureforms-field-<?php echo esc_attr( $crm_field_name ); ?>" name="formscrm_sureforms[fc_crm_field-<?php echo esc_attr( $crm_field_name ); ?>]" style="min-width:300px; margin-bottom: 10px;">
											<option value=""><?php esc_html_e( 'Select a field', 'formscrm' ); ?></option>
											<?php
											foreach ( $form_fields as $form_field_label ) {
												echo '<option value="' . esc_attr( $form_field_label ) . '" ';
												if ( isset( $settings[ 'fc_crm_field-' . $crm_field_name ] ) ) {
													selected( $settings[ 'fc_crm_field-' . $crm_field_name ], $form_field_label );
												}
												echo '>' . esc_html( $form_field_label ) . '</option>';
											}
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
					</tbody>
				</table>
					<?php
				} else {
					echo '<p>' . esc_html__( 'No fields found. Reconnect your CRM.', 'formscrm' ) . '</p>';
				}
			}
			?>
		</div>
		<?php
	}

	/**
	 * Save options CRM.
	 *
	 * @param int $post_id Post ID of the SureForms form.
	 * @return void
	 */
	public function crm_save_options( $post_id ) {
		if ( ! isset( $_POST['formscrm_sureforms_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['formscrm_sureforms_nonce'] ) ), 'formscrm_sureforms_save' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- Nonce verified above.
		if ( isset( $_POST['formscrm_sureforms'] ) && is_array( $_POST['formscrm_sureforms'] ) ) {
			$crm_data = array_map( 'sanitize_text_field', wp_unslash( $_POST['formscrm_sureforms'] ) );
			update_post_meta( $post_id, self::META_KEY, array_filter( $crm_data ) );
		}
	}

	/**
	 * Process the entry after a successful SureForms submission.
	 *
	 * @param array $form_submit_response Response data built by SureForms after submission.
	 * @return void
	 */
	public function crm_process_entry( $form_submit_response ) {
		$form_id = isset( $form_submit_response['form_id'] ) ? (int) $form_submit_response['form_id'] : 0;

		if ( ! $form_id ) {
			return;
		}

		$settings = get_post_meta( $form_id, self::META_KEY, true );
		$settings = is_array( $settings ) ? $settings : array();
		$crm_type = ! empty( $settings['fc_crm_type'] ) ? sanitize_text_field( $settings['fc_crm_type'] ) : '';

		if ( ! $crm_type ) {
			return;
		}

		$form_info = array(
			'form_type'       => 'sureforms',
			'form_type_title' => 'SureForms',
			'form_id'         => $form_id,
			'form_name'       => ! empty( $form_submit_response['form_name'] ) ? $form_submit_response['form_name'] : get_the_title( $form_id ),
			'entry_id'        => isset( $form_submit_response['entry_id'] ) ? $form_submit_response['entry_id'] : '',
		);

		// Create contact in CRM.
		$this->crmlib = formscrm_get_api_class( $crm_type );
		if ( empty( $this->crmlib ) ) {
			/* translators: %s: CRM type slug */
			formscrm_alert_error( $crm_type, sprintf( __( 'CRM class not found for type: %s', 'formscrm' ), $crm_type ), array(), '', '', $form_info );
			return;
		}

		$submitted_data  = isset( $form_submit_response['data'] ) && is_array( $form_submit_response['data'] ) ? $form_submit_response['data'] : array();
		$merge_vars      = self::get_merge_vars( $settings, $submitted_data );
		$merge_vars      = apply_filters( 'formscrm_merge_vars_before_send', $merge_vars, $settings, array() );
		$response_result = $this->crmlib->create_entry( $settings, $merge_vars );

		if ( isset( $response_result['status'] ) && 'error' === $response_result['status'] ) {
			$url   = isset( $response_result['url'] ) ? $response_result['url'] : '';
			$query = isset( $response_result['query'] ) ? $response_result['query'] : '';

			formscrm_alert_error( $crm_type, 'Error ' . $response_result['message'], $merge_vars, $url, $query, $form_info );
		} else {
			// CRM classes may report a display name (e.g. "Holded v2") via the create_entry() result.
			$crm_name = formscrm_get_crm_display_name( $response_result, $crm_type );
			formscrm_debug_message( 'Success creating ' . $crm_name . ' Entry ID: ' . ( isset( $response_result['id'] ) ? $response_result['id'] : '' ) );
		}
	}

	/**
	 * Extract merge variables from settings and submitted data.
	 *
	 * SureForms passes submitted data to `srfm_form_submit` keyed by the
	 * field's visible label (see `Form_Submit::prepare_submission_data()`),
	 * so the field mapping stores that same label as the value.
	 *
	 * @param array $settings       CRM settings, including the field mapping.
	 * @param array $submitted_data Submitted data keyed by field label.
	 * @return array
	 */
	public static function get_merge_vars( $settings, $submitted_data ) {
		if ( empty( $settings ) || ! is_array( $settings ) ) {
			return array();
		}

		$merge_vars = array();
		foreach ( $settings as $key => $value ) {
			if ( false === strpos( $key, 'fc_crm_field' ) ) {
				continue;
			}
			$crm_key = str_replace( 'fc_crm_field-', '', $key );

			if ( array_key_exists( $value, $submitted_data ) ) {
				$value = $submitted_data[ $value ];
			}

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

	/**
	 * Returns the list of field labels available in a SureForms form.
	 *
	 * Uses the same "label" extraction SureForms core uses internally to
	 * build the confirmation message (see `Form_Submit::handle_form_entry()`),
	 * so the mapped labels always match the keys of the data submitted to
	 * `srfm_form_submit`.
	 *
	 * @param int $form_id SureForms form post ID.
	 * @return array List of unique field labels.
	 */
	private function get_form_fields( $form_id ) {
		$form_markup = get_the_content( null, false, $form_id );
		preg_match_all( '/"label":"(.*?)"/', $form_markup, $matches );

		$labels = ! empty( $matches[1] ) ? array_values( array_unique( array_filter( $matches[1] ) ) ) : array();

		return apply_filters( 'formscrm_sureforms_form_fields', $labels, $form_id );
	}
}

new FORMSCRM_SureForms();
