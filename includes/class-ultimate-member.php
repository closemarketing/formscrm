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
class Ultimate_Member_Connector {

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
		/*
		add_action( 'wpcf7_after_save', array( $this, 'crm_save_options' ) );
		add_action( 'wpcf7_before_send_mail', array( $this, 'crm_process_entry' ) );*/

		add_action( 'add_meta_boxes', array( $this, 'ultimatemember_add_meta_box' ) );
		add_action( 'save_post', array( $this, 'ultimatemember_save_meta_box' ) );
	}

	/**
	 * # Add meta value for all posts types
	 * ---------------------------------------------------------------------------------------------------- */

	/**
	 * Add meta box
	 *
	 * @return void
	 */
	public function ultimatemember_add_meta_box() {
			add_meta_box(
				'ultimatemember_group_ads',
				__( 'FormsCRM Connector Settings', 'formscrm' ),
				array( $this, 'settings_add_crm' ),
				'um_form',
				'normal',
				'default'
			);

	}

	/**
	 * Render Meta Box content.
	 *
	 * @param WP_Post $post The post object.
	 */
	function ultimatemember_group_ads_callback( $post ) {
		wp_nonce_field( 'ultimatemember_group_ads', 'ultimatemember_group_ads_nonce' );
		$value = get_post_meta( $post->ID, 'ultimatemember_group_ads', true );

		if ( 'on' == $value ) {
			$value = 'checked';
		} else {
			$value = '';
		}
		echo '<input type="checkbox" id="ultimatemember_group_ads" name="ultimatemember_group_ads" ' . esc_attr( $value ) . ' />';
		echo '<label>';
		echo esc_html__( 'Desactivar la publicidad', 'ultimatemembernomos' );
		echo '</label>';
	}

	/**
	 * Save the meta when the post is saved.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	function ultimatemember_save_meta_box( $post_id ) {
		if ( ! isset( $_POST['ultimatemember_group_ads_nonce'] ) ) {
			return $post_id;
		}

		$nonce = $_POST['ultimatemember_group_ads_nonce'];
		if ( ! wp_verify_nonce( $nonce, 'ultimatemember_group_ads' ) ) {
			return $post_id;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( 'um_form' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}
		} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		$mydata = sanitize_text_field( $_POST['formscrm_ultimate_member'] );
		update_post_meta( $post_id, 'formscrm_ultimate_member', $mydata );
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

		$ultimatemember_crm_defaults = array();
		$ultimatemember_crm          = get_post_meta( get_the_ID(), 'formscrm_ultimate_member', false );
		?>
		<div class="metabox-holder">
			<div class="cme-main-fields">
				<p>
					<select name="ultimatemember-crm[fc_crm_type]" class="medium" onchange="jQuery(this).parents('form').submit();" id="fc_crm_type">
						<?php
						foreach ( formscrm_get_choices() as $choice ) {
							echo '<option value="' . esc_html( $choice['value'] ) . '" ';
							if ( isset( $ultimatemember_crm['fc_crm_type'] ) ) {
								selected( $ultimatemember_crm['fc_crm_type'], $choice['value'] );
							}
							echo '>' . esc_html( $choice['label'] ) . '</option>';
						}
						?>
					</select>
				</p>
				<?php if ( isset( $ultimatemember_crm['fc_crm_type'] ) && $ultimatemember_crm['fc_crm_type'] ) { ?>

					<?php if ( false !== array_search( $ultimatemember_crm['fc_crm_type'], formscrm_get_dependency_url(), true ) ) { ?>
					<p>
						<label for="ultimatemember-crm-fc_crm_url"><?php esc_html_e( 'URL:', 'formscrm' ); ?></label><br />
						<input type="text" id="ultimatemember-crm-fc_crm_url" name="ultimatemember-crm[fc_crm_url]" class="wide" size="70" placeholder="[<?php esc_html_e( 'CRM URL', 'formscrm' ); ?>]" value="<?php echo ( isset( $ultimatemember_crm['fc_crm_url'] ) ) ? esc_attr( $ultimatemember_crm['fc_crm_url'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $ultimatemember_crm['fc_crm_type'], formscrm_get_dependency_username(), true ) ) { ?>
					<p>
						<label for="ultimatemember-crm-fc_crm_username"><?php esc_html_e( 'URL:', 'formscrm' ); ?></label><br />
						<input type="text" id="ultimatemember-crm-fc_crm_username" name="ultimatemember-crm[fc_crm_username]" class="wide" size="70" placeholder="[<?php esc_html_e( 'CRM Username', 'formscrm' ); ?>]" value="<?php echo ( isset( $ultimatemember_crm['fc_crm_username'] ) ) ? esc_attr( $ultimatemember_crm['fc_crm_username'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $ultimatemember_crm['fc_crm_type'], formscrm_get_dependency_password(), true ) ) { ?>
					<p>
						<label for="ultimatemember-crm-fc_crm_password"><?php esc_html_e( 'Password:', 'formscrm' ); ?></label><br />
						<input type="password" id="ultimatemember-crm-fc_crm_password" name="ultimatemember-crm[fc_crm_password]" class="wide" size="70" placeholder="[<?php esc_html_e( 'CRM Password', 'formscrm' ); ?>]" value="<?php echo ( isset( $ultimatemember_crm['fc_crm_password'] ) ) ? esc_attr( $ultimatemember_crm['fc_crm_password'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $ultimatemember_crm['fc_crm_type'], formscrm_get_dependency_apipassword(), true ) ) { ?>
					<p>
						<label for="ultimatemember-crm-fc_crm_apipassword"><?php esc_html_e( 'API Password:', 'formscrm' ); ?></label><br />
						<input type="password" id="ultimatemember-crm-fc_crm_apipassword" name="ultimatemember-crm[fc_crm_apipassword]" class="wide" size="70" placeholder="[<?php esc_html_e( 'CRM API Password', 'formscrm' ); ?>]" value="<?php echo ( isset( $ultimatemember_crm['fc_crm_apipassword'] ) ) ? esc_attr( $ultimatemember_crm['fc_crm_apipassword'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $ultimatemember_crm['fc_crm_type'], formscrm_get_dependency_apisales(), true ) ) { ?>
					<p>
						<label for="ultimatemember-crm-fc_crm_apisales"><?php esc_html_e( 'API Sales:', 'formscrm' ); ?></label><br />
						<input type="text" id="ultimatemember-crm-fc_crm_apisales" name="ultimatemember-crm[fc_crm_apisales]" class="wide" size="70" placeholder="[<?php esc_html_e( 'CRM Sales', 'formscrm' ); ?>]" value="<?php echo ( isset( $ultimatemember_crm['fc_crm_apisales'] ) ) ? esc_attr( $ultimatemember_crm['fc_crm_apisales'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $ultimatemember_crm['fc_crm_type'], formscrm_get_dependency_odoodb(), true ) ) { ?>
					<p>
						<label for="ultimatemember-crm-fc_crm_odoodb"><?php esc_html_e( 'API Sales:', 'formscrm' ); ?></label><br />
						<input type="text" id="ultimatemember-crm-fc_crm_odoodb" name="ultimatemember-crm[fc_crm_odoodb]" class="wide" size="70" placeholder="[<?php esc_html_e( 'CRM Sales', 'formscrm' ); ?>]" value="<?php echo ( isset( $ultimatemember_crm['fc_crm_odoodb'] ) ) ? esc_attr( $ultimatemember_crm['fc_crm_odoodb'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<p>
						<?php $this->include_library( $ultimatemember_crm['fc_crm_type'] ); ?>
						<select name="ultimatemember-crm[fc_crm_module]" class="medium" onchange="jQuery(this).parents('form').submit();" id="fc_crm_module">
							<?php
							foreach ( $this->crmlib->list_modules( $ultimatemember_crm ) as $module ) {
								echo '<option value="' . esc_html( $module['name'] ) . '" ';
								if ( isset( $module['name'] ) ) {
									selected( $ultimatemember_crm['fc_crm_module'], $module['name'] );
								}
								echo '>' . esc_html( $module['label'] ) . '</option>';
							}
							?>
						</select>
					</p>

				<?php } ?>
			</div>

		<?php
		if ( isset( $ultimatemember_crm['fc_crm_module'] ) && $ultimatemember_crm['fc_crm_module'] ) {
			$crm_fields = $this->crmlib->list_fields( $ultimatemember_crm );
			?>
			<table class="cf7-map-table" cellspacing="0" cellpadding="0">
				<tbody>
					<tr class="cf7-map-row">
						<th class="cf7-map-column cf7-map-column-heading cf7-map-column-key"><?php esc_html_e( 'Field CRM', 'formscrm' ); ?></th>
						<th class="cf7-map-column cf7-map-column-heading cf7-map-column-value"><?php esc_html_e( 'Form Field', 'formscrm' ); ?></th>
					</tr>
						<?php
						foreach ( $crm_fields as $crm_field ) {
							?>
							<tr class="cf7-map-row">
									<td class="cf7-map-column cf7-map-column-key">
										<label for="ultimatemember-crm-field-<?php echo esc_html( $crm_field['name'] ); ?>"><?php echo esc_html( $crm_field['label'] ); ?><?php if ( $crm_field['required'] ) { echo ' <span class="required">*</span>'; } ?></label>
									</td>
									<td class="cf7-map-column cf7-map-column-value">
										<input type="text" id="ultimatemember-crm-field-<?php echo esc_html( $crm_field['name'] ); ?>" name="ultimatemember-crm[fc_crm_field-<?php echo esc_html( $crm_field['name'] ); ?>]" class="wide" size="70" placeholder="[<?php esc_html_e( 'Name of your field', 'formscrm' ); ?>]" value="<?php echo ( isset( $ultimatemember_crm[ 'fc_crm_field-' . $crm_field['name'] ] ) ) ? esc_attr( $ultimatemember_crm[ 'fc_crm_field-' . $crm_field['name'] ] ) : ''; ?>" <?php if ( $crm_field['required'] ) { echo ' required'; } ?>/>
									</td>
							</tr>
						<?php } ?>
				</tbody>
			</table>
		<?php } ?>
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

		if ( isset( $_POST['ultimatemember-crm'] ) && is_array( $_POST['ultimatemember-crm'] ) ) {
			update_option( 'ultimatemember_crm_' . $args->id, array_filter( $_POST['ultimatemember-crm'] ) );
		}
	}

	/**
	 * Process the entry.
	 *
	 * @param obj $obj CF7 Object.
	 * @return void
	 */
	public function crm_process_entry( $obj ) {

		$ultimatemember_crm    = get_option( 'ultimatemember_crm_' . $obj->id() );
		$submission = WPCF7_Submission::get_instance();

		if ( $ultimatemember_crm ) {
			$this->include_library( $ultimatemember_crm['fc_crm_type'] );
			$merge_vars = $this->get_merge_vars( $ultimatemember_crm, $submission->get_posted_data() );

			$response_result = $this->crmlib->create_entry( $ultimatemember_crm, $merge_vars );

			if ( 'error' === $response_result['status'] ) {
				formscrm_debug_email_lead( $ultimatemember_crm['fc_crm_type'], 'Error ' . $response_result['message'], $merge_vars );
			} else {
				error_log( $response_result['id'] );
			}
		}
	}

	/**
	 * Extract merge variables
	 *
	 * @param array $ultimatemember_crm Array settings from CRM.
	 * @param array $submitted_data Submitted data.
	 * @return array
	 */
	private function get_merge_vars( $ultimatemember_crm, $submitted_data ) {
		$merge_vars = array();
		foreach ( $ultimatemember_crm as $key => $value ) {
			if ( false !== strpos( $key, 'fc_crm_field' ) ) {
				$crm_key      = str_replace( 'fc_crm_field-', '', $key );
				$merge_vars[] = array(
					'name'  => $crm_key,
					'value' => isset( $submitted_data[ $value ] ) ? $submitted_data[ $value ] : '',
				);
			}
		}

		return $merge_vars;
	}
}

new Ultimate_Member_Connector();
