<?php
/**
 * Odoo11 connect library
 *
 * Has functions to login, list fields and create lead
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.1.0
 */

require_once 'debug.php';

/**
 * Class for CRM
 */
class CRMLIB_ODOO11 {


	/**
	 * Logins to a CRM
	 *
	 * @param  array $settings Settings from Gravity Forms options.
	 * @return boolean id returns false if cannot login and string if gets token.
	 */
	public function login( $settings ) {
		$url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];

		return $id;
	}
	/**
	 * List Modules
	 *
	 * @param array $settings Settings from Gravity Forms options.
	 * @return array $custom_modules Array of modules of CRM
	 */
	public function list_modules( $settings ) {
		$url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];

		return $custom_modules;
	}
	/**
	 * List Fields of actual module of CRM
	 *
	 * @param array $settings Settings from Gravity Forms options.
	 * @return array $custom_fields Array of fields
	 */
	public function list_fields( $settings ) {
		$url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];
		$module   = $settings['gf_crm_module'];

		return $custom_fields;
	}

	/**
	 * Create an entry in a module
	 *
	 * @param array $settings Settings from Gravity Forms options.
	 * @param array $merge_vars Values passed in the form.
	 * @return id ID of created entry.
	 */
	public function create_entry( $settings, $merge_vars ) {
		$url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];
		$module   = isset( $settings['gf_crm_module'] ) ? $settings['gf_crm_module'] : $module = 'leads';
		return $recordid;
	}
}
