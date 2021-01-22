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
class FMC_Settings {
	/**
	 * Settings
	 *
	 * @var array
	 */
	private $fmc_settings;

	/**
	 * Label for premium features
	 *
	 * @var string
	 */
	private $label_premium;

	/**
	 * Construct of class
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
		add_action( 'admin_head', array( $this, 'custom_css' ) );
	}

	/**
	 * Adds plugin page.
	 *
	 * @return void
	 */
	public function add_plugin_page() {

		add_submenu_page(
			'wpcf7',
			__( 'FormsCRM', 'formscrm' ),
			__( 'FormsCRM', 'formscrm' ),
			'manage_options',
			'formscrm_settings',
			array( $this, 'create_admin_page' ),
		);
	}

	/**
	 * Create admin page.
	 *
	 * @return void
	 */
	public function create_admin_page() {
		$this->fmc_settings = get_option( 'formscrm_settings' );
		?>

		<div class="wrap">
			<h2><?php esc_html_e( 'Oracle Courses Importing Settings', 'formscrm' ); ?></h2>
			<p></p>
			<?php settings_errors(); ?>
			<form method="post" action="options.php">
				<?php
					settings_fields( 'import_formscrm_settings' );
					do_settings_sections( 'import-formscrm-admin' );
					submit_button(
						__( 'Save settings', 'formscrm' ),
						'primary',
						'submit_settings'
					);
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Init for page
	 *
	 * @return void
	 */
	public function page_init() {

		register_setting(
			'import_formscrm_settings',
			'formscrm_settings',
			array( $this, 'sanitize_fields' )
		);

		add_settings_section(
			'import_formscrm_setting_section',
			__( 'Settings for Importing from Oracle', 'formscrm' ),
			array( $this, 'import_formscrm_section_info' ),
			'import-formscrm-admin'
		);

		add_settings_field(
			'fmc_username',
			__( 'Username', 'formscrm' ),
			array( $this, 'username_callback' ),
			'import-formscrm-admin',
			'import_formscrm_setting_section'
		);

		add_settings_field(
			'fmc_password',
			__( 'Password', 'formscrm' ),
			array( $this, 'password_callback' ),
			'import-formscrm-admin',
			'import_formscrm_setting_section'
		);

		add_settings_field(
			'fmc_connection',
			__( 'Connection String', 'formscrm' ),
			array( $this, 'connection_callback' ),
			'import-formscrm-admin',
			'import_formscrm_setting_section'
		);

		add_settings_field(
			'fmc_charset',
			__( 'Charset', 'formscrm' ),
			array( $this, 'charset_callback' ),
			'import-formscrm-admin',
			'import_formscrm_setting_section'
		);
	}

	/**
	 * Sanitize fiels before saves in DB
	 *
	 * @param array $input Input fields.
	 * @return array
	 */
	public function sanitize_fields( $input ) {
		$sanitary_values = array();
		$fmc_settings    = get_option( 'formscrm_settings' );

		if ( isset( $input[ 'fmc_username' ] ) ) {
			$sanitary_values[ 'fmc_username' ] = sanitize_text_field( $input[ 'fmc_username'] );
		}

		if ( isset( $input[ 'fmc_password'] ) ) {
			$sanitary_values[ 'fmc_password'] = sanitize_text_field( $input[ 'fmc_password'] );
		}

		if ( isset( $input[ 'fmc_connection'] ) ) {
			$sanitary_values[ 'fmc_connection'] = $input[ 'fmc_connection'];
		}

		if ( isset( $input[ 'fmc_charset'] ) ) {
			$sanitary_values[ 'fmc_charset'] = $input[ 'fmc_charset'];
		}

		return $sanitary_values;
	}

	/**
	 * Info for neo automate section.
	 *
	 * @return void
	 */
	public function import_formscrm_section_info() {
		esc_html_e( 'Put the connection API key settings in order to connect and sync courses.', 'formscrm' );
	}

	public function username_callback() {
		global $choices_crm;
		echo '<select name="formscrm_settings[fmc_crm]" id="fmc_crm">';
		$selected = isset( $this->fmc_settings[ 'fmc_crm'] ) ? '' : 'selected';
		echo '<option value="" ' . esc_html( $selected ) . '>---</option>';
		foreach ( $choices_crm as $crm ) {
			$selected = ( isset( $this->fmc_settings[ 'fmc_crm'] ) && $this->fmc_settings[ 'fmc_crm'] === 'yes' ) ? 'selected' : '';
			echo '<option value="' . esc_html( $crm['value'] ) . '" ' . esc_html( $selected ) . '>' . $crm['label'] . '</option>';
		}
		echo '</select>';
	}

	public function password_callback() {
		printf(
			'<input class="regular-text" type="password" name="' . 'formscrm_settings' . '[' . 'fmc_password]" id="' . 'fmc_password" value="%s">',
			isset( $this->fmc_settings[ 'fmc_password' ] ) ? esc_attr( $this->fmc_settings[ 'fmc_password' ] ) : ''
		);
	}

	public function connection_callback() {
		printf(
			'<input class="regular-text" type="text" name="' . 'formscrm_settings' . '[' . 'fmc_connection]" id="' . 'fmc_connection" value="%s">',
			isset( $this->fmc_settings[ 'fmc_connection'] ) ? esc_attr( $this->fmc_settings[ 'fmc_connection'] ) : ''
		);
	}

	public function charset_callback() {
		printf(
			'<input class="regular-text" type="text" name="' . 'formscrm_settings' . '[' . 'fmc_charset]" id="' . 'fmc_charset" value="%s">',
			isset( $this->fmc_settings[ 'fmc_charset'] ) ? esc_attr( $this->fmc_settings[ 'fmc_charset'] ) : ''
		);
	}

	/**
	 * Custom CSS for admin
	 *
	 * @return void
	 */
	public function custom_css() {
		// Free Version.
		echo '
			<style>
			.wp-admin .formscrm-plugin span.wcsen-premium{ 
				color: #b4b9be;
			}
			.wp-admin.formscrm-plugin #fmc_catnp,
			.wp-admin.formscrm-plugin #fmc_crm,
			.wp-admin.formscrm-plugin #fmc_catsep {
				width: 70px;
			}
			.wp-admin.formscrm-plugin #fmc_username,
			.wp-admin.formscrm-plugin #fmc_sync_num {
				width: 50px;
			}
			.wp-admin.formscrm-plugin #fmc_charset {
				width: 150px;
			}
			.wp-admin.formscrm-plugin #fmc_password,
			.wp-admin.formscrm-plugin #fmc_taxinc {
				width: 270px;
			}';
		echo '</style>';
	}

}
if ( is_admin() ) {
	$import_sync = new FMC_Settings();
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
				<label for="wpcf7-crm-email"><?php echo esc_html( __( 'CRM Type:', 'wpcf7' ) ); ?></label><br />
				<select id="wpcf7-type" name="name" value="<?php echo (isset ( $cf7_crm['type'] ) ) ? esc_attr( $cf7_crm['type'] ) : ''; ?>">
					<option value="">--</option>
					<?php 
					foreach ( $choices_crm as $crm ) {
						echo '<option value="' . $crm['value'] . '">' . $crm['label'] . '</option>';
					}
					?>
				</select>
			</p>

			<p>
				<label for="wpcf7-crm-username"><?php echo esc_html( __( 'Username:', 'wpcf7' ) ); ?></label><br />
				<input type="text" id="wpcf7-crm-username" name="wpcf7-crm[username]" class="wide" size="70" placeholder="[CRM Username]" value="<?php echo (isset ( $cf7_crm['username'] ) ) ? esc_attr( $cf7_crm['username'] ) : ''; ?>" />
			</p>

			<p>
				<label for="wpcf7-crm-email"><?php echo esc_html( __( 'Subscriber Email:', 'wpcf7' ) ); ?></label><br />
				<input type="text" id="wpcf7-crm-email" name="wpcf7-crm[email]" class="wide" size="70" placeholder="[your-email]" value="<?php echo (isset ( $cf7_crm['email'] ) ) ? esc_attr( $cf7_crm['email'] ) : ''; ?>" />
			</p>


			<p>
				<label for="wpcf7-crm-name"><?php echo esc_html( __( 'Subscriber Name:', 'wpcf7' ) ); ?></label><br />
				<input type="text" id="wpcf7-crm-name" name="wpcf7-crm[name]" class="wide" size="70" placeholder="[your-name]" value="<?php echo (isset ($cf7_crm['name'] ) ) ? esc_attr( $cf7_crm['name'] ) : '' ; ?>" />
			</p>


			<p>
				<label for="wpcf7-crm-accept"><?php echo esc_html( __( 'Required Acceptance Field:', 'wpcf7' ) ); ?></label><br />
				<input type="text" id="wpcf7-crm-accept" name="wpcf7-crm[accept]" class="wide" size="70" placeholder="[opt-in]" value="<?php echo (isset ($cf7_crm['accept'] ) ) ? esc_attr( $cf7_crm['accept'] ) : '' ; ?>" />
			</p>


			<p>
				<label for="wpcf7-crm-api"><?php echo esc_html( __( 'Client API Key:', 'wpcf7' ) ); ?></label><br />
				<input type="text" id="wpcf7-crm-api" name="wpcf7-crm[api]" class="wide" size="70" placeholder="512a2673a8fc4e588499e82e2d43680d100a824e8ba55394" value="<?php echo (isset($cf7_crm['api']) ) ? esc_attr( $cf7_crm['api'] ) : ''; ?>" />
			</p>


			<p>
				<label for="wpcf7-crm-list"><?php echo esc_html( __( 'API Subscriber List ID:', 'wpcf7' ) ); ?></label><br />
				<input type="text" id="wpcf7-crm-list" name="wpcf7-crm[list]" class="wide" size="70" placeholder="aadc9ca0b08c83fbb714490354463186" value="<?php echo (isset( $cf7_crm['list']) ) ?  esc_attr( $cf7_crm['list']) : '' ; ?>" />
			</p>


			<p>
				<input type="checkbox" id="wpcf7-crm-resubscribeoption" name="wpcf7-crm[resubscribeoption]" value="1"<?php echo ( isset($cf7_crm['resubscribeoption']) ) ? ' checked="checked"' : ''; ?> />
				<label for="wpcf7-crm-resubscribeoption"><?php echo esc_html( __( 'Allow Users to Resubscribe after being Deleted or Unsubscribed? (checked = true)', 'wpcf7' ) ); ?></label>
			</p>


			<p>
				<input type="checkbox" id="wpcf7-crm-cf-active" name="wpcf7-crm[cfactive]" value="1"<?php echo ( isset($cf7_crm['cfactive']) ) ? ' checked="checked"' : ''; ?> />
				<label for="wpcf7-crm-cfactive"><?php echo esc_html( __( 'Use Custom Fields', 'wpcf7' ) ); ?></label>
			</p>
		</div>

	<div class="crm-custom-fields">

		<?php for($i=1;$i<=13;$i++){ ?>

		<div class="col-6">
				<label for="wpcf7-crm-CustomValue<?php echo $i; ?>"><?php echo esc_html( __( 'Contact Form Value '.$i.':', 'wpcf7' ) ); ?></label><br />
				<input type="text" id="wpcf7-crm-CustomValue<?php echo $i; ?>" name="wpcf7-crm[CustomValue<?php echo $i; ?>]" class="wide" size="70" placeholder="[your-example-value]" value="<?php echo (isset( $cf7_crm['CustomValue'.$i]) ) ?  esc_attr( $cf7_crm['CustomValue'.$i]) : '' ;  ?>" />
		</div>


		<div class="col-6">
			<label for="wpcf7-crm-CustomKey<?php echo $i; ?>"><?php echo esc_html( __( 'crm Custom Field Name '.$i.':', 'wpcf7' ) ); ?></label><br />
			<input type="text" id="wpcf7-crm-CustomKey<?php echo $i; ?>" name="wpcf7-crm[CustomKey<?php echo $i; ?>]" class="wide" size="70" placeholder="example-field" value="<?php echo (isset( $cf7_crm['CustomKey'.$i]) ) ?  esc_attr( $cf7_crm['CustomKey'.$i]) : '' ;  ?>" />
		</div>

		<?php } ?>

	</div>



</div>

<?php

}


function wpcf7_crm_save_crm($args) {

	update_option( 'cf7_crm_'.$args->id, $_POST['wpcf7-crm'] );

}
add_action( 'wpcf7_after_save', 'wpcf7_crm_save_crm' );



function show_cm_metabox ( $panels ) {

	$new_page = array(
		'cme-Extension' => array(
			'title' => __( 'CRM', 'contact-form-7' ),
			'callback' => 'wpcf7_crm_add_crm'
		)
	);

	$panels = array_merge($panels, $new_page);

	return $panels;

}
add_filter( 'wpcf7_editor_panels', 'show_cm_metabox' );



function wpcf7_crm_subscribe($obj) {

	$cf7_crm = get_option( 'cf7_crm_'.$obj->id() );
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
add_action( 'wpcf7_before_send_mail', 'wpcf7_crm_subscribe' );



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
