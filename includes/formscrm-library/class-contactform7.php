<?php
/**
 * Contact Forms 7 Wrapper
 *
 * @package   WordPress
 * @author    David Perez <david@closemarketing.es>
 * @copyright 2021 Closemarketing
 * @version   3.3
 */

defined( 'ABSPATH' ) || exit;

	/**
	 * Library for Contact Forms Settings
	 *
	 * @package    WordPress
	 * @author     David Perez <david@closemarketing.es>
	 * @copyright  2019 Closemarketing
	 * @version    1.0
	 */
class FORMSCRM_CF7_Settings {

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
		add_filter( 'wpcf7_editor_panels', array( $this, 'show_cm_metabox' ) );
		add_action( 'wpcf7_after_save', array( $this, 'crm_save_options' ) );
		add_action( 'wpcf7_before_send_mail', array( $this, 'crm_process_entry' ) );
	}

	/**
	 * Shows metabox in form
	 *
	 * @param array $panels Panels actived in CF7.
	 * @return array
	 */
	public function show_cm_metabox( $panels ) {
		$new_page = array(
			'cme-Extension' => array(
				'title'    => __( 'FormsCRM', 'formscrm' ),
				'callback' => array( $this, 'settings_add_crm' ),
			),
		);
		$panels = array_merge( $panels, $new_page );
		return $panels;
	}

	/**
	 * Include library connector
	 *
	 * @param string $crmtype Type of CRM.
	 * @return void
	 */
	private function include_library( $crmtype ) {
		if ( isset( $_POST['fc_crm_type'] ) ) {
			$crmtype = sanitize_text_field( $_POST['fc_crm_type'] );
		}

		if ( isset( $crmtype ) ) {
			$crmname      = strtolower( $crmtype );
			$crmclassname = str_replace( ' ', '', $crmname );
			$crmclassname = 'CRMLIB_' . strtoupper( $crmclassname );
			$crmname      = str_replace( ' ', '_', $crmname );

			$array_path = formscrm_get_crmlib_path();
			if ( isset( $array_path[ $crmname ] ) ) {
				include_once $array_path[ $crmname ];
			}

			formscrm_debug_message( $array_path[ $crmname ] );

			if ( class_exists( $crmclassname ) ) {
				$this->crmlib = new $crmclassname();
			}
		}
	}

	/**
	 * Adds CRM options in Contact Form 7
	 *
	 * @param obj $args Arguments.
	 * @return void
	 */
	public function settings_add_crm( $args ) {
		$cf7_crm_defaults = array();
		$cf7_crm          = get_option( 'cf7_crm_' . $args->id(), $cf7_crm_defaults );
		?>
		<div class="metabox-holder">
			<div class="cme-main-fields">
				<p>
					<select name="wpcf7-crm[fc_crm_type]" class="medium" onchange="jQuery(this).parents('form').submit();" id="fc_crm_type">
						<?php
						foreach ( formscrm_get_choices() as $choice ) {
							echo '<option value="' . esc_html( $choice['value'] ) . '" ';
							if ( isset( $cf7_crm['fc_crm_type'] ) ) {
								selected( $cf7_crm['fc_crm_type'], $choice['value'] );
							}
							echo '>' . esc_html( $choice['label'] ) . '</option>';
						}
						?>
					</select>
				</p>
				<?php if ( isset( $cf7_crm['fc_crm_type'] ) && $cf7_crm['fc_crm_type'] ) { ?>

					<?php if ( false !== array_search( $cf7_crm['fc_crm_type'], formscrm_get_dependency_url(), true ) ) { ?>
					<p>
						<label for="wpcf7-crm-fc_crm_url"><?php esc_html_e( 'URL:', 'formscrm' ); ?></label><br />
						<input type="text" id="wpcf7-crm-fc_crm_url" name="wpcf7-crm[fc_crm_url]" class="wide" size="70" placeholder="<?php esc_html_e( 'CRM URL', 'formscrm' ); ?>" value="<?php echo ( isset( $cf7_crm['fc_crm_url'] ) ) ? esc_attr( $cf7_crm['fc_crm_url'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $cf7_crm['fc_crm_type'], formscrm_get_dependency_username(), true ) ) { ?>
					<p>
						<label for="wpcf7-crm-fc_crm_username"><?php esc_html_e( 'Username:', 'formscrm' ); ?></label><br />
						<input type="text" id="wpcf7-crm-fc_crm_username" name="wpcf7-crm[fc_crm_username]" class="wide" size="70" placeholder="<?php esc_html_e( 'Username', 'formscrm' ); ?>" value="<?php echo ( isset( $cf7_crm['fc_crm_username'] ) ) ? esc_attr( $cf7_crm['fc_crm_username'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $cf7_crm['fc_crm_type'], formscrm_get_dependency_password(), true ) ) { ?>
					<p>
						<label for="wpcf7-crm-fc_crm_password"><?php esc_html_e( 'Password:', 'formscrm' ); ?></label><br />
						<input type="password" id="wpcf7-crm-fc_crm_password" name="wpcf7-crm[fc_crm_password]" class="wide" size="70" placeholder="<?php esc_html_e( 'CRM Password', 'formscrm' ); ?>" value="<?php echo ( isset( $cf7_crm['fc_crm_password'] ) ) ? esc_attr( $cf7_crm['fc_crm_password'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $cf7_crm['fc_crm_type'], formscrm_get_dependency_apipassword(), true ) ) { ?>
					<p>
						<label for="wpcf7-crm-fc_crm_apipassword"><?php esc_html_e( 'API Password:', 'formscrm' ); ?></label><br />
						<input type="password" id="wpcf7-crm-fc_crm_apipassword" name="wpcf7-crm[fc_crm_apipassword]" class="wide" size="70" placeholder="<?php esc_html_e( 'CRM API Password', 'formscrm' ); ?>" value="<?php echo ( isset( $cf7_crm['fc_crm_apipassword'] ) ) ? esc_attr( $cf7_crm['fc_crm_apipassword'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $cf7_crm['fc_crm_type'], formscrm_get_dependency_apisales(), true ) ) { ?>
					<p>
						<label for="wpcf7-crm-fc_crm_apisales"><?php esc_html_e( 'API Sales:', 'formscrm' ); ?></label><br />
						<input type="text" id="wpcf7-crm-fc_crm_apisales" name="wpcf7-crm[fc_crm_apisales]" class="wide" size="70" placeholder="<?php esc_html_e( 'CRM Sales', 'formscrm' ); ?>" value="<?php echo ( isset( $cf7_crm['fc_crm_apisales'] ) ) ? esc_attr( $cf7_crm['fc_crm_apisales'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $cf7_crm['fc_crm_type'], formscrm_get_dependency_odoodb(), true ) ) { ?>
					<p>
						<label for="wpcf7-crm-fc_crm_odoodb"><?php esc_html_e( 'Odoo DB:', 'formscrm' ); ?></label><br />
						<input type="text" id="wpcf7-crm-fc_crm_odoodb" name="wpcf7-crm[fc_crm_odoodb]" class="wide" size="70" placeholder="<?php esc_html_e( 'Odoo DB', 'formscrm' ); ?>" value="<?php echo ( isset( $cf7_crm['fc_crm_odoodb'] ) ) ? esc_attr( $cf7_crm['fc_crm_odoodb'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<p>
						<?php $this->include_library( $cf7_crm['fc_crm_type'] ); ?>
						<select name="wpcf7-crm[fc_crm_module]" class="medium" onchange="jQuery(this).parents('form').submit();" id="fc_crm_module">
							<?php
							$settings_module = isset( $cf7_crm['fc_crm_module'] ) ? $cf7_crm['fc_crm_module'] : '';
							foreach ( $this->crmlib->list_modules( $cf7_crm ) as $module ) {
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
							}
							?>
						</select>
					</p>

				<?php } ?>
			</div>

		<?php
		if ( isset( $cf7_crm['fc_crm_module'] ) && $cf7_crm['fc_crm_module'] ) {
			$crm_fields = $this->crmlib->list_fields( $cf7_crm, $cf7_crm['fc_crm_module'] );
			$cf7_form   = WPCF7_ContactForm::get_instance( $args->id() );
			$form_fields = ! empty( $cf7_form ) ? $cf7_form->scan_form_tags() : array();

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
									<label for="wpcf7-crm-field-<?php echo esc_html( $crm_field_name ); ?>">
										<?php
										echo esc_html( $crm_field_label );
										if ( isset( $crm_field_req ) && $crm_field_req ) {
											echo ' <span class="required">*</span>';
										}
										?>
									</label>
								</td>
								<td class="cf7-map-column cf7-map-column-value">
									<select class="wide" name="wpcf7-crm[fc_crm_field-<?php echo esc_html( $crm_field_name ); ?>]" style="min-width:300px; margin-bottom: 10px;">
										<option value=""><?php esc_html_e( 'Select a field', 'formscrm' ); ?></option>
										<?php
										foreach ( $form_fields as $form_field ) {
											echo '<option value="' . esc_html( $form_field['name'] ) . '" ';
											if ( isset( $cf7_crm[ 'fc_crm_field-' . $crm_field_name ] ) ) {
												selected( $cf7_crm[ 'fc_crm_field-' . $crm_field_name ], $form_field['name'] );
											}
											echo '>' . esc_html( $form_field['name'] ) . '</option>';
										}
										?>
									</select>
								</td>
						</tr>
						<?php
						$count_fields++;
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
	 * @param obj $args Arguments CF7.
	 * @return void
	 */
	public function crm_save_options( $args ) {

		if ( isset( $_POST['wpcf7-crm'] ) && is_array( $_POST['wpcf7-crm'] ) ) {
			update_option( 'cf7_crm_' . $args->id(), array_filter( $_POST['wpcf7-crm'] ) );
		}
	}

	/**
	 * Process the entry.
	 *
	 * @param obj $contact_form CF7 Object.
	 * @return void
	 */
	public function crm_process_entry( $contact_form ) {
		$cf7_crm    = get_option( 'cf7_crm_' . $contact_form->id() );
		$submission = WPCF7_Submission::get_instance();
		$crm_type   = ! empty( $cf7_crm['fc_crm_type'] ) ? sanitize_title( $cf7_crm['fc_crm_type'] ) : '';

		// Create contact in CRM.
		$this->include_library( $cf7_crm['fc_crm_type'] );
		$merge_vars      = $this->get_merge_vars( $cf7_crm, $submission->get_posted_data() );
		$response_result = $this->crmlib->create_entry( $cf7_crm, $merge_vars );			

		if ( 'error' === $response_result['status'] ) {
			$url   = isset( $response_result['url'] ) ? $response_result['url'] : '';
			$query = isset( $response_result['query'] ) ? $response_result['query'] : '';

			formscrm_debug_email_lead( $cf7_crm['fc_crm_type'], 'Error ' . $response_result['message'], $merge_vars, $url, $query );
		}
	}

	/**
	 * Extract merge variables
	 *
	 * @param array $cf7_crm Array settings from CRM.
	 * @param array $submitted_data Submitted data.
	 * @return array
	 */
	private function get_merge_vars( $cf7_crm, $submitted_data ) {
		$merge_vars = array();
		foreach ( $cf7_crm as $key => $value ) {
			if ( false !== strpos( $key, 'fc_crm_field' ) ) {
				$crm_key      = str_replace( 'fc_crm_field-', '', $key );

				if ( ! empty( $submitted_data[ $value ] ) ) {
					$value = $submitted_data[ $value ];					
				}

				$merge_vars[] = array(
					'name'  => $crm_key,
					'value' => $value,
				);
			}
		}

		return $merge_vars;
	}
}

new FORMSCRM_CF7_Settings();
