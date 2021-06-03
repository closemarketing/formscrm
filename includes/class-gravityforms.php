<?php
/**
 * Functions for CRM in Gravity Forms
 *
 * All helpers functions for Gravity Forms
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.net>
 * @copyright  2019 Closemarketing
 * @version    1.0
 */

GFForms::include_feed_addon_framework();
global $formscrm_api;

class GFCRM extends GFFeedAddOn {

	protected $_version                  = FORMSCRM_VERSION;
	protected $_min_gravityforms_version = '1.9.0';
	protected $_slug                     = 'formscrm';
	protected $_path                     = 'gravityformscrm/crm.php';
	protected $_full_path                = __FILE__;
	protected $_url                      = 'https://www.gravityforms.com';
	protected $_title                    = 'CRM Add-On';
	protected $_short_title              = 'FormsCRM';

	// Members plugin integration.
	protected $_capabilities = array(
		'gravityforms_crm',
		'gravityforms_crm_uninstall',
	);

	// Permissions.
	protected $_capabilities_settings_page = 'gravityforms_crm';
	protected $_capabilities_form_settings = 'gravityforms_crm';
	protected $_capabilities_uninstall     = 'gravityforms_crm_uninstall';
	protected $_enable_rg_autoupgrade      = true;

	private static $_instance = null;

	private $crmlib;

	public static function get_instance() {
		if (self::$_instance == null) {
			self::$_instance = new GFCRM();
		}

		return self::$_instance;
	}
	/**
	 * Init function of library
	 *
	 * @return void
	 */
	public function init() {

		parent::init();
		load_plugin_textdomain( 'gravityforms-crm', FALSE, '/gravityforms-crm/languages' );

	}

	public function init_admin() {
		parent::init_admin();

		$this->ensure_upgrade();
	}

	/**
	 * Plugin settings
	 *
	 * @return void
	 */
	public function plugin_settings_fields() {

		global $formscrm_api;

		return array(
			array(
				'title'       => __( 'CRM Account Information', 'formscrm' ),
				'description' => __( 'Use this connector with CRM software. Use Gravity Forms to collect customer information and automatically add them to your CRM Leads.', 'formscrm' ),
				'fields'      => array(
					array(
						'name'     => 'fc_crm_type',
						'label'    => __( 'CRM Type', 'formscrm' ),
						'type'     => 'select',
						'class'    => 'medium',
						'onchange' => 'jQuery(this).parents("form").submit();',
						'choices'  => formscrm_get_choices(),
					),
					array(
						'name'          => 'fc_crm_url',
						'label'         => __( 'CRM URL', 'formscrm' ),
						'type'          => 'text',
						'class'         => 'medium',
						'tooltip'       => __( 'Use the URL with http and the ending slash /.', 'formscrm' ),
						'tooltip_class' => 'tooltipclass',
						'dependency'    => array(
							'field'  => 'fc_crm_type',
							'values' => formscrm_get_dependency_url(),
						),
					),
					array(
						'name'              => 'fc_crm_username',
						'label'             => __('Username', 'formscrm'),
						'type'              => 'text',
						'class'             => 'medium',
						'dependency'        => array(
							'field' => 'fc_crm_type',
							'values' => formscrm_get_dependency_username(),
						),
						'feedback_callback' => $this->login_api_crm(),
					),
					array(
						'name'          => 'fc_crm_password',
						'label'         => __('Password', 'formscrm' ),
						'type'          => 'api_key',
						'class'         => 'medium',
						'tooltip'       => __( 'Use the password of the actual user.', 'formscrm' ),
						'tooltip_class' => 'tooltipclass',
						'dependency'    => array(
							'field' => 'fc_crm_type',
							'values' => formscrm_get_dependency_password(),
						),
					),
					array(
						'name'          => 'fc_crm_apipassword',
						'label'         => __('API Password for User', 'formscrm'),
						'type'          => 'api_key',
						'class'         => 'medium',
						//'feedback_callback' => $this->login_api_crm(),
						'tooltip'       => __('Find the API Password in the profile of the user in CRM.', 'formscrm'),
						'tooltip_class' => 'tooltipclass',
						'dependency'    => array(
							'field' => 'fc_crm_type',
							'values' => formscrm_get_dependency_apipassword(),
						),
					),
					array(
						'name'          => 'fc_crm_apisales',
						'label'         => __('Password and Security Key', 'formscrm'),
						'type'          => 'api_key',
						'class'         => 'medium',
						'tooltip'       => __('"Password""SecurityKey" Go to My Settings / Reset my Security Key.', 'formscrm'),
						'tooltip_class' => 'tooltipclass',
						'dependency'    => array(
							'field'  => 'fc_crm_type',
							'values' => formscrm_get_dependency_apisales(),
						),
					),
					array(
						'name'       => 'fc_crm_odoodb',
						'label'      => __( 'Odoo DB Name', 'formscrm' ),
						'type'       => 'text',
						'class'      => 'medium',
						'dependency' => array(
							'field'  => 'fc_crm_type',
							'values' => formscrm_get_dependency_odoodb(),
						),
					),
				),
			),
		);
	}

	public function settings_api_key($field, $echo = true) {

		$field['type'] = 'text';

		$api_key_field = $this->settings_text($field, false);

		//switch type="text" to type="password" so the key is not visible
		$api_key_field = str_replace('type="text"', 'type="password"', $api_key_field);

		$caption = '<small>' . sprintf(__("Find a Password or API key depending of CRM.", 'formscrm')) . '</small>';

		if ( $echo ) {
			echo $api_key_field . '</br>' . $caption;
		}

		return $api_key_field . '</br>' . $caption;
	}

	//-------- Form Settings ---------
	public function feed_edit_page($form, $feed_id) {

		// ensures valid credentials were entered in the settings page
		if ($this->login_api_crm() == false) {
			?>
			<div class="notice notice-error">
			<?php _e('We are unable to login to CRM.', 'formscrm');
			echo ' <a href="' . $this->get_plugin_settings_url() . '">' . __('Use Settings Page', 'formscrm') . '</a>'?>
			</div>
			<?php
			return;
		}

		echo '<script type="text/javascript">var form = ' . GFCommon::json_encode( $form ) . ';</script>';

		parent::feed_edit_page( $form, $feed_id );
	}

	/**
	 * Include library connector
	 *
	 * @param string $crmtype Type of CRM.
	 * @return void
	 */
	private function include_library( $crmtype ) {
		if ( isset( $crmtype ) ) {
			$crmname      = strtolower( $crmtype );
			$crmclassname = str_replace( ' ', '', $crmname );
			$crmclassname = 'CRMLIB_' . strtoupper( $crmclassname );
			$crmname      = str_replace( ' ', '_', $crmname );

			include_once 'class-crm-' . $crmname . '.php';

			debug_message( 'api-crm/class-crm-' . $crmname . '.php' );

			if ( class_exists( $crmclassname ) ) {
				$this->crmlib = new $crmclassname();
			}
		}
	}

	public function feed_settings_fields() {

		$settings = $this->get_plugin_settings();
		$this->include_library($settings['fc_crm_type']);

		return array(
			array(
				'title'       => __('CRM Feed', 'formscrm'),
				'description' => '',
				'fields'      => array(
					array(
						'name'     => 'feedName',
						'label'    => __('Name', 'formscrm'),
						'type'     => 'text',
						'required' => true,
						'class'    => 'medium',
						'tooltip'  => '<h6>' . __('Name', 'formscrm') . '</h6>' . __('Enter a feed name to uniquely identify this setup.', 'formscrm'),
					),
					array(
						'name'     => 'gf_crm_module',
						'label'    => __('CRM Module', 'formscrm'),
						'type'     => 'select',
						'class'    => 'medium',
						'onchange' => 'jQuery(this).parents("form").submit();',
						'choices'  => $this->crmlib->list_modules( $settings ),
					),
					array(
						'name'       => 'listFields',
						'label'      => __('Map Fields', 'formscrm'),
						'type'       => 'field_map',
						'dependency' => 'gf_crm_module',
						'field_map'  => $this->crmlib->list_fields( $settings, $this->get_setting( 'gf_crm_module' ) ),
						'tooltip'    => '<h6>' . __('Map Fields', 'formscrm') . '</h6>' . __('Associate your CRM custom fields to the appropriate Gravity Form fields by selecting the appropriate form field from the list.', 'formscrm'),
					),
				),
			),
		);
	}

	public function ensure_upgrade() {

		if (get_option('gf_crm_upgrade')) {
			return false;
		}

		$feeds = $this->get_feeds();
		if (empty($feeds)) {

			//Force Add-On framework upgrade
			$this->upgrade('2.0');
		}

		update_option('gf_crm_upgrade', 1);
	}

	public function feed_list_columns() {
		return array(
			'feedName' => __('Name', 'formscrm'),
		);
	}

	public function process_feed($feed, $entry, $form) {

		if (!$this->is_valid_key()) {
			return;
		}

		$this->export_feed($entry, $form, $feed);

	}

	public function export_feed( $entry, $form, $feed) {
            $settings = $this->get_plugin_settings();
            $this->include_library($settings['fc_crm_type']);

		if (!empty($feed['meta']['listFields_first_name'])) {
			$name = $this->get_name($entry, $feed['meta']['listFields_first_name']);
		}

		$merge_vars = array();
		$field_maps = $this->get_field_map_fields($feed, 'listFields');

		foreach ($field_maps as $var_key => $field_id) {
			$field = RGFormsModel::get_field($form, $field_id);

			if (GFCommon::is_product_field($field['type']) && rgar($field, 'enablePrice')) {
				$ary          = explode('|', $entry[$field_id]);
				$product_name = count($ary) > 0 ? $ary[0] : '';
				$merge_vars[] = array('name' => $var_key, 'value' => $product_name);
			} else if (RGFormsModel::get_input_type($field) == 'checkbox') {
				$value = '';
				foreach ($field['inputs'] as $input) {
					$index   = (string) $input['id'];
					$value_n = apply_filters('gform_crm_field_value', rgar($entry, $index), $form['id'], $field_id, $entry);
					$value .= $value_n;
					if ($value_n) {
						$value .= '|';
					}

				}
				$value        = substr($value, 0, -1);
				$merge_vars[] = array(
					'name'  => $var_key,
					'value' => $value,
				);
			} else if (RGFormsModel::get_input_type($field) == 'multiselect') {
				$value = apply_filters('gform_crm_field_value', rgar($entry, $field_id), $form['id'], $field_id, $entry);
				$value = str_replace(',', '|', $value);

				$merge_vars[] = array(
					'name'  => $var_key,
					'value' => $value,
				);
			} else if (RGFormsModel::get_input_type($field) == 'textarea') {
				$value        = apply_filters('gform_crm_field_value', rgar($entry, $field_id), $form['id'], $field_id, $entry);
				$value        = str_replace(array("\r", "\n"), ' ', $value);
				$merge_vars[] = array(
					'name'  => $var_key,
					'value' => $value,
				);
			} else {
				$merge_vars[] = array(
					'name'  => $var_key,
					'value' => apply_filters('gform_crm_field_value', rgar($entry, $field_id), $form['id'], $field_id, $entry),
				);
			}
		}

		$override_custom_fields = apply_filters('gform_crm_override_blank_custom_fields', false, $entry, $form, $feed);
		if (!$override_custom_fields) {
			$merge_vars = $this->remove_blank_custom_fields($merge_vars);
		}

		$settings = $this->get_plugin_settings();

		debug_message( $settings );
		debug_message( $merge_vars );

		$id = $this->crmlib->create_entry($settings, $merge_vars);

		debug_message($id);
	}

	private static function remove_blank_custom_fields($merge_vars) {
		$i = 0;

		$count = count($merge_vars);

		for ($i = 0; $i < $count; $i++) {
			if (rgblank($merge_vars[$i]['value'])) {
				unset($merge_vars[$i]);
			}
		}
		//resort the array because items could have been removed, this will give an error from CRM if the keys are not in numeric sequence
		sort($merge_vars);
		return $merge_vars;
	}

	private function get_name($entry, $field_id) {

		//If field is simple (one input), simply return full content
		$name = rgar($entry, $field_id);
		if (!empty($name)) {
			return $name;
		}

		//Complex field (multiple inputs). Join all pieces and create name
		$prefix = trim(rgar($entry, $field_id . '.2'));
		$first  = trim(rgar($entry, $field_id . '.3'));
		$last   = trim(rgar($entry, $field_id . '.6'));
		$suffix = trim(rgar($entry, $field_id . '.8'));

		$name = $prefix;
		$name .= !empty($name) && !empty($first) ? " $first" : $first;
		$name .= !empty($name) && !empty($last) ? " $last" : $last;
		$name .= !empty($name) && !empty($suffix) ? " $suffix" : $suffix;

		return $name;
	}

	private function is_valid_key() {
		$result_api = $this->login_api_crm();

		return $result_api;
	}

	private function login_api_crm() {
		$login_result = false;

		//* Logins to CRM
		$settings = $this->get_plugin_settings();
    
    if(isset($settings['fc_crm_type']))
      $this->include_library($settings['fc_crm_type']);

		if(isset($this->crmlib))
			$login_result = $this->crmlib->login($settings);

		debug_message($login_result);

		testserver();

		if (!isset($login_result)) {
			$login_result = "";
		}

		return $login_result;
	}

} //from main class
