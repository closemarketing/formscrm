<?php
/**
 * JetFormBuilder Wrapper
 *
 * @package   WordPress
 * @author    David Perez <david@closemarketing.es>
 * @copyright 2024 Closemarketing
 * @version   1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Library for JetFormBuilder Settings
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2024 Closemarketing
 * @version    1.0
 */
class FORMSCRM_JetFormBuilder {

	/**
	 * CRM LIB external
	 *
	 * @var object
	 */
	private $crmlib;

	/**
	 * Post meta key for storing per-form CRM settings.
	 *
	 * @var string
	 */
	private $meta_key = '_formscrm_jfb_settings';

	/**
	 * Construct of class
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_settings' ), 10, 2 );
		add_action( 'jet_fb_on_success', array( $this, 'crm_process_entry' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the FormsCRM meta box on JetFormBuilder form editor.
	 *
	 * @return void
	 */
	public function add_meta_box() {
		add_meta_box(
			'formscrm-jfb',
			__( 'FormsCRM', 'formscrm' ),
			array( $this, 'render_settings' ),
			'jet-form-builder',
			'normal',
			'high'
		);
	}

	/**
	 * Render per-form CRM settings inside the meta box.
	 *
	 * @param \WP_Post $post Current post object.
	 * @return void
	 */
	public function render_settings( $post ) {
		wp_nonce_field( 'formscrm_jfb_save_' . $post->ID, 'formscrm_jfb_nonce' );

		$settings        = get_post_meta( $post->ID, $this->meta_key, true );
		$settings        = is_array( $settings ) ? $settings : array();
		$settings_module = isset( $settings['fc_crm_module'] ) ? $settings['fc_crm_module'] : '';
		?>
		<div class="metabox-holder">
			<div class="cme-main-fields">
				<p>
					<label for="jfb_fc_crm_type"><?php esc_html_e( 'CRM Type:', 'formscrm' ); ?></label><br />
					<select name="formscrm_jfb[fc_crm_type]" id="jfb_fc_crm_type" class="medium">
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

				<?php if ( ! empty( $settings['fc_crm_type'] ) ) { ?>

					<?php if ( false !== array_search( $settings['fc_crm_type'], formscrm_get_dependency_url(), true ) ) { ?>
					<p>
						<label for="jfb_fc_crm_url"><?php esc_html_e( 'URL:', 'formscrm' ); ?></label><br />
						<input type="text" id="jfb_fc_crm_url" name="formscrm_jfb[fc_crm_url]" class="wide" size="70" placeholder="<?php esc_html_e( 'CRM URL', 'formscrm' ); ?>" value="<?php echo isset( $settings['fc_crm_url'] ) ? esc_attr( $settings['fc_crm_url'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $settings['fc_crm_type'], formscrm_get_dependency_username(), true ) ) { ?>
					<p>
						<label for="jfb_fc_crm_username"><?php esc_html_e( 'Username:', 'formscrm' ); ?></label><br />
						<input type="text" id="jfb_fc_crm_username" name="formscrm_jfb[fc_crm_username]" class="wide" size="70" placeholder="<?php esc_html_e( 'Username', 'formscrm' ); ?>" value="<?php echo isset( $settings['fc_crm_username'] ) ? esc_attr( $settings['fc_crm_username'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $settings['fc_crm_type'], formscrm_get_dependency_password(), true ) ) { ?>
					<p>
						<label for="jfb_fc_crm_password"><?php esc_html_e( 'Password:', 'formscrm' ); ?></label><br />
						<input type="password" id="jfb_fc_crm_password" name="formscrm_jfb[fc_crm_password]" class="wide" size="70" placeholder="<?php esc_html_e( 'CRM Password', 'formscrm' ); ?>" value="<?php echo isset( $settings['fc_crm_password'] ) ? esc_attr( $settings['fc_crm_password'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $settings['fc_crm_type'], formscrm_get_dependency_apipassword(), true ) ) { ?>
					<p>
						<label for="jfb_fc_crm_apipassword"><?php esc_html_e( 'API Password:', 'formscrm' ); ?></label><br />
						<input type="password" id="jfb_fc_crm_apipassword" name="formscrm_jfb[fc_crm_apipassword]" class="wide" size="70" placeholder="<?php esc_html_e( 'CRM API Password', 'formscrm' ); ?>" value="<?php echo isset( $settings['fc_crm_apipassword'] ) ? esc_attr( $settings['fc_crm_apipassword'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $settings['fc_crm_type'], formscrm_get_dependency_apisales(), true ) ) { ?>
					<p>
						<label for="jfb_fc_crm_apisales"><?php esc_html_e( 'API Sales:', 'formscrm' ); ?></label><br />
						<input type="text" id="jfb_fc_crm_apisales" name="formscrm_jfb[fc_crm_apisales]" class="wide" size="70" placeholder="<?php esc_html_e( 'CRM Sales', 'formscrm' ); ?>" value="<?php echo isset( $settings['fc_crm_apisales'] ) ? esc_attr( $settings['fc_crm_apisales'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $settings['fc_crm_type'], formscrm_get_dependency_odoodb(), true ) ) { ?>
					<p>
						<label for="jfb_fc_crm_odoodb"><?php esc_html_e( 'Odoo DB:', 'formscrm' ); ?></label><br />
						<input type="text" id="jfb_fc_crm_odoodb" name="formscrm_jfb[fc_crm_odoodb]" class="wide" size="70" placeholder="<?php esc_html_e( 'Odoo DB', 'formscrm' ); ?>" value="<?php echo isset( $settings['fc_crm_odoodb'] ) ? esc_attr( $settings['fc_crm_odoodb'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php
					$this->crmlib = formscrm_get_api_class( $settings['fc_crm_type'] );
					?>
					<p>
						<label for="jfb_fc_crm_module"><?php esc_html_e( 'CRM Module:', 'formscrm' ); ?></label><br />
						<select name="formscrm_jfb[fc_crm_module]" id="jfb_fc_crm_module" class="medium">
							<?php
							$modules = $this->crmlib->list_modules( $settings );
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
							if ( empty( $settings_module ) ) {
								$settings_module           = ! empty( $modules[0]['value'] ) ? $modules[0]['value'] : '';
								$settings['fc_crm_module'] = $settings_module;
							}
							?>
						</select>
					</p>

					<p>
						<label for="jfb_fc_crm_mode_expert"><?php esc_html_e( 'Expert Mode', 'formscrm' ); ?></label><br />
						<input type="checkbox" id="jfb_fc_crm_mode_expert" name="formscrm_jfb[fc_crm_mode_expert]" class="medium" value="on" <?php checked( isset( $settings['fc_crm_mode_expert'] ) ? $settings['fc_crm_mode_expert'] : '', 'on' ); ?> />
						<?php esc_html_e( 'Enable this option to show all fields of the CRM.', 'formscrm' ); ?>
					</p>

				<?php } ?>
			</div>

			<?php
			if ( ! empty( $settings['fc_crm_type'] ) ) {
				formscrm_render_connection_status( $settings, 'html' );
			}

			if ( ! empty( $this->crmlib ) ) {
				$login_crm = $this->crmlib->login( $settings );
				if ( is_array( $login_crm ) && isset( $login_crm['status'] ) && 'error' === $login_crm['status'] ) {
					echo '<p style="color: red;">' . esc_html( $login_crm['message'] ) . '</p>';
					return;
				}
				if ( false === $login_crm ) {
					return;
				}
			}

			if ( ! empty( $settings_module ) && ! empty( $this->crmlib ) ) {
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
										<label for="jfb-crm-field-<?php echo esc_attr( $crm_field_name ); ?>">
											<?php
											echo esc_html( $crm_field_label );
											if ( $crm_field_req ) {
												echo ' <span class="required">*</span>';
											}
											?>
										</label>
									</td>
									<td class="cf7-map-column cf7-map-column-value">
										<select id="jfb-crm-field-<?php echo esc_attr( $crm_field_name ); ?>" class="wide" name="formscrm_jfb[fc_crm_field-<?php echo esc_attr( $crm_field_name ); ?>]" style="min-width:300px; margin-bottom: 10px;">
											<option value=""><?php esc_html_e( 'Select a field', 'formscrm' ); ?></option>
											<?php
											foreach ( $form_fields as $form_field ) {
												echo '<option value="' . esc_attr( $form_field ) . '" ';
												if ( isset( $settings[ 'fc_crm_field-' . $crm_field_name ] ) ) {
													selected( $settings[ 'fc_crm_field-' . $crm_field_name ], $form_field );
												}
												echo '>' . esc_html( $form_field ) . '</option>';
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
	 * Save FormsCRM settings when the JetFormBuilder form is saved.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function save_settings( $post_id, $post ) {
		if ( 'jet-form-builder' !== $post->post_type ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['formscrm_jfb_nonce'] ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_text_field applied below.
		if ( ! wp_verify_nonce( wp_unslash( $_POST['formscrm_jfb_nonce'] ), 'formscrm_jfb_save_' . $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( isset( $_POST['formscrm_jfb'] ) && is_array( $_POST['formscrm_jfb'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized via array_map below.
			$raw_data = wp_unslash( $_POST['formscrm_jfb'] );
			$crm_data = array_map( 'sanitize_text_field', $raw_data );
			update_post_meta( $post_id, $this->meta_key, array_filter( $crm_data ) );
		}
	}

	/**
	 * Process a successful JetFormBuilder submission and send data to the CRM.
	 *
	 * Supports both the single-arg signature (JFB 3.x: jet_fb_on_success($handler))
	 * and the two-arg signature (JFB 2.x: jet_fb_on_success($response, $handler)).
	 *
	 * @param mixed $handler_or_response Action_Handler object (3.x) or response array (2.x).
	 * @param mixed $handler             Action_Handler object (2.x) or null.
	 * @return void
	 */
	public function crm_process_entry( $handler_or_response, $handler = null ) {
		// Normalise handler across JFB versions.
		if ( is_object( $handler_or_response ) ) {
			$handler = $handler_or_response;
		}
		if ( ! is_object( $handler ) ) {
			return;
		}

		$form_id = isset( $handler->form_id ) ? (int) $handler->form_id : 0;
		if ( ! $form_id ) {
			return;
		}

		$settings = get_post_meta( $form_id, $this->meta_key, true );
		if ( empty( $settings ) || ! is_array( $settings ) ) {
			return;
		}

		$crm_type = isset( $settings['fc_crm_type'] ) ? sanitize_text_field( $settings['fc_crm_type'] ) : '';
		if ( ! $crm_type ) {
			return;
		}

		$this->crmlib = formscrm_get_api_class( $crm_type );
		if ( empty( $this->crmlib ) ) {
			return;
		}

		$submitted_data = $this->get_submitted_data( $handler );
		$merge_vars     = self::get_merge_vars( $settings, $submitted_data );
		$result         = $this->crmlib->create_entry( $settings, $merge_vars );

		if ( isset( $result['status'] ) && 'error' === $result['status'] ) {
			$form_info = array(
				'form_type'       => 'jetformbuilder',
				'form_type_title' => 'JetFormBuilder',
				'form_id'         => $form_id,
				'form_name'       => get_the_title( $form_id ),
			);
			$url       = isset( $result['url'] ) ? $result['url'] : '';
			$query     = isset( $result['query'] ) ? $result['query'] : '';
			formscrm_alert_error( $crm_type, 'Error ' . $result['message'], $merge_vars, $url, $query, $form_info );
		}
	}

	/**
	 * Extract submitted field values from the JFB action handler.
	 *
	 * Handles both array (JFB 2.x) and object (JFB 3.x) request_data.
	 *
	 * @param object $handler JFB Action_Handler.
	 * @return array
	 */
	private function get_submitted_data( $handler ) {
		if ( ! isset( $handler->request_data ) ) {
			return array();
		}
		if ( is_array( $handler->request_data ) ) {
			return $handler->request_data;
		}
		if ( is_object( $handler->request_data ) && method_exists( $handler->request_data, 'get_request' ) ) {
			$data = $handler->request_data->get_request();
			return is_array( $data ) ? $data : array();
		}
		return array();
	}

	/**
	 * Build the merge-vars array from saved field-mapping settings and submitted data.
	 *
	 * @param array $settings       Per-form CRM settings.
	 * @param array $submitted_data Field values submitted with the form.
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

			$value = self::fill_dynamic_value( (string) $value, $submitted_data );

			$merge_vars[] = array(
				'name'  => $crm_key,
				'value' => $value,
			);
		}
		return $merge_vars;
	}

	/**
	 * Replace {id:field_name} shortcodes in a value with live submission data.
	 *
	 * @param string $field_value  The field value that may contain shortcodes.
	 * @param array  $submitted_data All submitted form data.
	 * @return string
	 */
	private static function fill_dynamic_value( $field_value, $submitted_data ) {
		if ( ! str_contains( $field_value, '{id:' ) ) {
			return $field_value;
		}

		$matches = array();
		preg_match_all( '/{([^}]*)}/', $field_value, $matches );
		if ( empty( $matches[1] ) ) {
			return $field_value;
		}

		foreach ( $matches[1] as $match ) {
			$field_options = explode( ':', $match );
			if ( ! isset( $field_options[1] ) || 'id' !== $field_options[0] ) {
				continue;
			}

			$field_name = $field_options[1];
			if ( ! isset( $submitted_data[ $field_name ] ) ) {
				continue;
			}

			$entry_value = $submitted_data[ $field_name ];
			if ( is_array( $entry_value ) ) {
				$entry_value = implode( ', ', $entry_value );
			}

			$field_value = str_replace( '{' . $match . '}', $entry_value, $field_value );
		}

		return $field_value;
	}

	/**
	 * Parse the JetFormBuilder post content to extract all form field names.
	 *
	 * @param int $form_id Post ID of the JetFormBuilder form.
	 * @return string[]
	 */
	private function get_form_fields( $form_id ) {
		$post = get_post( $form_id );
		if ( ! $post ) {
			return array();
		}
		$blocks = parse_blocks( $post->post_content );
		return $this->extract_fields_from_blocks( $blocks );
	}

	/**
	 * Recursively walk Gutenberg blocks and collect jet-forms/* field names.
	 *
	 * @param array $blocks Parsed block array.
	 * @return string[]
	 */
	private function extract_fields_from_blocks( $blocks ) {
		$fields = array();
		foreach ( $blocks as $block ) {
			$block_name = isset( $block['blockName'] ) ? $block['blockName'] : '';
			if ( str_starts_with( (string) $block_name, 'jet-forms/' ) ) {
				$name = '';
				if ( ! empty( $block['attrs']['name'] ) ) {
					$name = $block['attrs']['name'];
				} elseif ( ! empty( $block['attrs']['fieldName'] ) ) {
					$name = $block['attrs']['fieldName'];
				}
				if ( $name ) {
					$fields[] = sanitize_text_field( $name );
				}
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$fields = array_merge( $fields, $this->extract_fields_from_blocks( $block['innerBlocks'] ) );
			}
		}
		return $fields;
	}

	/**
	 * Enqueue admin styles on the JetFormBuilder form edit screen.
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || 'jet-form-builder' !== $screen->post_type ) {
			return;
		}
		wp_enqueue_style(
			'formscrm-admin',
			FORMSCRM_PLUGIN_URL . 'includes/assets/formscrm-admin.css',
			array(),
			FORMSCRM_VERSION,
			'all'
		);
	}
}

new FORMSCRM_JetFormBuilder();
