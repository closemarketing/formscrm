<?php
/**
 * Contact Forms 7 Wrapper
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2021 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Library for Contact Forms Settings
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2019 Closemarketing
 * @version    0.1
 */
class CF7_Settings {

	private $crmlib;

	/**
	 * Construct of class
	 */
	public function __construct() {
		add_filter( 'wpcf7_editor_panels', array( $this, 'show_cm_metabox' ) );
		add_action( 'wpcf7_after_save', array( $this, 'wpcf7_crm_save_crm' ) );
		add_action( 'wpcf7_before_send_mail', array( $this, 'wpcf7_crm_subscribe' ) );
	}

	public function show_cm_metabox ( $panels ) {
	
		$new_page = array(
			'cme-Extension' => array(
				'title'    => __( 'FormsCRM', 'formscrm' ),
				'callback' => array( $this, 'wpcf7_crm_add_crm' ),
			)
		);
	
		$panels = array_merge($panels, $new_page);
	
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

			formscrm_debug_message( $array_path[ $crmname ]);

			if ( class_exists( $crmclassname ) ) {
				$this->crmlib = new $crmclassname();
			}
		}
	}

	function wpcf7_crm_add_crm( $args ) {
		global $choices_crm;
	
		$cf7_crm_defaults = array();
		$cf7_crm          = get_option( 'cf7_crm_' . $args->id(), $cf7_crm_defaults );
	
		echo '<pre>cf7_crm:';
		print_r($cf7_crm);
		echo '</pre>';
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
							echo '>' . $choice['label'] . '</option>';
						}
						?>
					</select>
				</p>
				<?php if ( isset( $cf7_crm['fc_crm_type'] ) && $cf7_crm['fc_crm_type'] ) { ?>
			
					<?php if ( false !== array_search( $cf7_crm['fc_crm_type'], formscrm_get_dependency_url(), true ) ) { ?>
					<p>
						<label for="wpcf7-crm-fc_crm_url"><?php esc_html_e( 'URL:', 'formscrm' ); ?></label><br />
						<input type="text" id="wpcf7-crm-fc_crm_url" name="wpcf7-crm[fc_crm_url]" class="wide" size="70" placeholder="[<?php esc_html_e( 'CRM URL', 'formscrm' ); ?>]" value="<?php echo (isset ( $cf7_crm['fc_crm_url'] ) ) ? esc_attr( $cf7_crm['fc_crm_url'] ) : ''; ?>" />
					</p>
					<?php } ?>
			
					<?php if ( false !== array_search( $cf7_crm['fc_crm_type'], formscrm_get_dependency_username(), true ) ) { ?>
					<p>
						<label for="wpcf7-crm-fc_crm_username"><?php esc_html_e( 'URL:', 'formscrm' ); ?></label><br />
						<input type="text" id="wpcf7-crm-fc_crm_username" name="wpcf7-crm[fc_crm_username]" class="wide" size="70" placeholder="[<?php esc_html_e( 'CRM Username', 'formscrm' ); ?>]" value="<?php echo (isset ( $cf7_crm['fc_crm_username'] ) ) ? esc_attr( $cf7_crm['fc_crm_username'] ) : ''; ?>" />
					</p>
					<?php } ?>
			
					<?php if ( false !== array_search( $cf7_crm['fc_crm_type'], formscrm_get_dependency_password(), true ) ) { ?>
					<p>
						<label for="wpcf7-crm-fc_crm_password"><?php esc_html_e( 'Password:', 'formscrm' ); ?></label><br />
						<input type="text" id="wpcf7-crm-fc_crm_password" name="wpcf7-crm[fc_crm_password]" class="wide" size="70" placeholder="[<?php esc_html_e( 'CRM Password', 'formscrm' ); ?>]" value="<?php echo (isset ( $cf7_crm['fc_crm_password'] ) ) ? esc_attr( $cf7_crm['fc_crm_password'] ) : ''; ?>" />
					</p>
					<?php } ?>
			
					<?php if ( false !== array_search( $cf7_crm['fc_crm_type'], formscrm_get_dependency_apipassword(), true ) ) { ?>
					<p>
						<label for="wpcf7-crm-fc_crm_apipassword"><?php esc_html_e( 'API Password:', 'formscrm' ); ?></label><br />
						<input type="text" id="wpcf7-crm-fc_crm_apipassword" name="wpcf7-crm[fc_crm_apipassword]" class="wide" size="70" placeholder="[<?php esc_html_e( 'CRM API Password', 'formscrm' ); ?>]" value="<?php echo (isset ( $cf7_crm['fc_crm_apipassword'] ) ) ? esc_attr( $cf7_crm['fc_crm_apipassword'] ) : ''; ?>" />
					</p>
					<?php } ?>
			
					<?php if ( false !== array_search( $cf7_crm['fc_crm_type'], formscrm_get_dependency_apisales(), true ) ) { ?>
					<p>
						<label for="wpcf7-crm-fc_crm_apisales"><?php esc_html_e( 'API Sales:', 'formscrm' ); ?></label><br />
						<input type="text" id="wpcf7-crm-fc_crm_apisales" name="wpcf7-crm[fc_crm_apisales]" class="wide" size="70" placeholder="[<?php esc_html_e( 'CRM Sales', 'formscrm' ); ?>]" value="<?php echo (isset ( $cf7_crm['fc_crm_apisales'] ) ) ? esc_attr( $cf7_crm['fc_crm_apisales'] ) : ''; ?>" />
					</p>
					<?php } ?>
			
					<?php if ( false !== array_search( $cf7_crm['fc_crm_type'], formscrm_get_dependency_odoodb(), true ) ) { ?>
					<p>
						<label for="wpcf7-crm-fc_crm_odoodb"><?php esc_html_e( 'API Sales:', 'formscrm' ); ?></label><br />
						<input type="text" id="wpcf7-crm-fc_crm_odoodb" name="wpcf7-crm[fc_crm_odoodb]" class="wide" size="70" placeholder="[<?php esc_html_e( 'CRM Sales', 'formscrm' ); ?>]" value="<?php echo (isset ( $cf7_crm['fc_crm_odoodb'] ) ) ? esc_attr( $cf7_crm['fc_crm_odoodb'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<p>
						<?php $this->include_library( $cf7_crm['fc_crm_type'] ); ?>
						<select name="wpcf7-crm[fc_crm_module]" class="medium" onchange="jQuery(this).parents('form').submit();" id="fc_crm_module">
							<?php 
							foreach ( $this->crmlib->list_modules( $cf7_crm ) as $module ) {
								print_r( $module );
								echo '<option value="' . esc_html( $module['name'] ) . '" ';
								if ( isset( $module['name'] ) ) {
									selected( $cf7_crm['fc_crm_module'], $module['name'] );
								}
								echo '>' . $module['label'] . '</option>';
							}
							?>
						</select>
					</p>

				<?php } ?>
			</div>
		
		<?php if ( isset( $cf7_crm['fc_crm_module'] ) && $cf7_crm['fc_crm_module'] ) {
		
			$crm_fields = $this->crmlib->list_fields( $cf7_crm );
		?>
		<table class="cf7-map-table" cellspacing="0" cellpadding="0">
			<tbody>
				<tr class="cf7-map-row">
					<th class="cf7-map-column cf7-map-column-heading cf7-map-column-key">Campo</th>
					<th class="cf7-map-column cf7-map-column-heading cf7-map-column-value">Campo formulario</th>
				</tr>
					<?php
					foreach ( $crm_fields as $crm_field ) { ?>

				<tr class="cf7-map-row">
						<td class="cf7-map-column cf7-map-column-key">
							<label for="wpcf7-crm-field-<?php echo $crm_field['name']; ?>"><?php echo esc_html( $crm_field['label'] ); ?><?php if ( $crm_field['required'] ) { echo ' <span class="required">*</span>'; } ?></label>
						</td>
						<td class="cf7-map-column cf7-map-column-value">
							<input type="text" id="wpcf7-crm-field-<?php echo $crm_field['name']; ?>" name="wpcf7-crm[fc_crm_field-<?php echo $crm_field['name']; ?>]" class="wide" size="70" placeholder="[<?php esc_html_e( 'Name of your field', 'formscrm' ); ?>]" value="<?php echo ( isset( $cf7_crm['fc_crm_field-' . $crm_field['name'] ] ) ) ?  esc_attr( $cf7_crm['fc_crm_field-' . $crm_field['name'] ] ) : '' ;  ?>" <?php if ( $crm_field['required'] ) { echo ' required'; } ?>/>
						</td>
				</tr>
					<?php } ?>
			</tbody>
		</table>
		<?php } ?>
	</div>
	<?php
	}
	
	function wpcf7_crm_save_crm($args) {
	
		update_option( 'cf7_crm_'.$args->id, $_POST['wpcf7-crm'] );
	
	}

	
	function wpcf7_crm_subscribe($obj) {
	
		$cf7_crm = get_option( 'cf7_crm_' . $obj->id() );
		$submission = WPCF7_Submission::get_instance();
	
		if( $cf7_crm )
		{
			$subscribe = false;
	
			$regex = '/\[\s*([a-zA-Z_][0-9a-zA-Z:._-]*)\s*\]/';
			$callback = array( &$obj, 'cf7_crm_callback' );
	
			$email = cf7_crm_tag_replace( $regex, $cf7_crm['email'], $submission->get_posted_data() );
			$name = cf7_crm_tag_replace( $regex, $cf7_crm['name'], $submission->get_posted_data() );
	
			$lists = cf7_crm_tag_replace( $regex, $cf7_crm['list'], $submission->get_posted_data() );
			$listarr = explode(',',$lists);
	
			if( isset($cf7_crm['accept']) && strlen($cf7_crm['accept']) != 0 )
			{
				$accept = cf7_crm_tag_replace( $regex, $cf7_crm['accept'], $submission->get_posted_data() );
				if($accept != $cf7_crm['accept'])
				{
					if(strlen($accept) > 0)
						$subscribe = true;
				}
			}
			else
			{
				$subscribe = true;
			}
	
			for($i=1;$i<=20;$i++){
	
				if( isset($cf7_crm['CustomKey'.$i]) && isset($cf7_crm['CustomValue'.$i]) && strlen(trim($cf7_crm['CustomValue'.$i])) != 0 )
				{
					$CustomFields[] = array('Key'=>trim($cf7_crm['CustomKey'.$i]), 'Value'=>cf7_crm_tag_replace( $regex, trim($cf7_crm['CustomValue'.$i]), $submission->get_posted_data() ) );
				}
	
			}
	
			if( isset($cf7_crm['resubscribeoption']) && strlen($cf7_crm['resubscribeoption']) != 0 )
			{
				$ResubscribeOption = true;
			}
				else
			{
				$ResubscribeOption = false;
			}
	
			if($subscribe && $email != $cf7_crm['email'])
			{
	
				require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'../api/csrest_subscribers.php');
	
				$wrap = new SPARTAN_CS_REST_Subscribers( trim($listarr[0]), $cf7_crm['api'] );
				foreach($listarr as $listid)
				{
					$wrap->set_list_id(trim($listid));
					$wrap->add(array(
						'EmailAddress' => $email,
						'Name' => $name,
						'CustomFields' => $CustomFields,
						'Resubscribe' => $ResubscribeOption
					));
				}
	
			}
	
		}
	}
	
	function cf7_crm_tag_replace( $pattern, $subject, $posted_data, $html = false ) {
		if( preg_match($pattern,$subject,$matches) > 0)
		{
	
			if ( isset( $posted_data[$matches[1]] ) ) {
				$submitted = $posted_data[$matches[1]];
	
				if ( is_array( $submitted ) )
					$replaced = join( ', ', $submitted );
				else
					$replaced = $submitted;
	
				if ( $html ) {
					$replaced = strip_tags( $replaced );
					$replaced = wptexturize( $replaced );
				}
	
				$replaced = apply_filters( 'wpcf7_mail_tag_replaced', $replaced, $submitted );
	
				return stripslashes( $replaced );
			}
	
			if ( $special = apply_filters( 'wpcf7_special_mail_tags', '', $matches[1] ) )
				return $special;
	
			return $matches[0];
		}
		return $subject;
	}

}

new CF7_Settings();
