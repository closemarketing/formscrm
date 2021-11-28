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
class FormsCRM_WooCommerce {

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
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
		add_action( 'woocommerce_settings_tabs_formscrm', array( $this, 'settings_tab' ) );
		add_action( 'woocommerce_update_options_formscrm', array( $this, 'update_settings' ) );
		add_action( 'woocommerce_new_order', array( $this, 'crm_process_entry' ), 1, 1 );
	}

	/**
	 * Get Woocommerce Fields.
	 *
	 * @return array
	 */
	private function get_woocommerce_order_fields() {
		// Function name and Label.
		return array(
			''                             => '',
			'customer_note'                => __( 'Customer Note', 'formscrm' ),
			'billing_first_name'           => __( 'Billing First name', 'formscrm' ),
			'billing_last_name'            => __( 'Billing Last name', 'formscrm' ),
			'billing_company'              => __( 'Billing Company', 'formscrm' ),
			'billing_address_1'            => __( 'Billing Address 1', 'formscrm' ),
			'billing_address_2'            => __( 'Billing Address 2', 'formscrm' ),
			'billing_city'                 => __( 'Billing City', 'formscrm' ),
			'billing_state'                => __( 'Billing State', 'formscrm' ),
			'billing_postcode'             => __( 'Billing Postcode', 'formscrm' ),
			'billing_country'              => __( 'Billing Country', 'formscrm' ),
			'billing_email'                => __( 'Billing Email', 'formscrm' ),
			'billing_phone'                => __( 'Billing Phone', 'formscrm' ),
			'shipping_first_name'          => __( 'Shipping First name', 'formscrm' ),
			'shipping_last_name'           => __( 'Shipping Last name', 'formscrm' ),
			'shipping_company'             => __( 'Shipping Company', 'formscrm' ),
			'shipping_address_1'           => __( 'Shipping Address 1', 'formscrm' ),
			'shipping_address_2'           => __( 'Shipping Address 2', 'formscrm' ),
			'shipping_city'                => __( 'Shipping City', 'formscrm' ),
			'shipping_state'               => __( 'Shipping State', 'formscrm' ),
			'shipping_postcode'            => __( 'Shipping Postcode', 'formscrm' ),
			'shipping_country'             => __( 'Shipping Country', 'formscrm' ),
			'formatted_billing_full_name'  => __( 'Formatted Billing Full Name', 'formscrm' ),
			'formatted_shipping_full_name' => __( 'Formatted Shipping Full Name', 'formscrm' ),
			'customer_id'                  => __( 'Customer ID', 'formscrm' ),
			'user_id'                      => __( 'User ID', 'formscrm' ),
		);
	}

	/**
	 * Settings tab in WooCommerce
	 *
	 * @param array $settings_tabs Settings tabs.
	 * @return array
	 */
	public static function add_settings_tab( $settings_tabs ) {
		$settings_tabs['formscrm'] = __( 'FormsCRM', 'formscrm' );
		return $settings_tabs;
	}

	/**
	 * Get fields Woocommerce.
	 *
	 * @return void
	 */
	public function settings_tab() {
			woocommerce_admin_fields( $this->get_settings() );
	}

	/**
	 * Get settings for WooCommerce.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings_crm = array();
		$options_crm  = array();
		$wc_formscrm  = get_option( 'wc_formscrm' );

		foreach ( formscrm_get_choices() as $choice ) {
			$options_crm[ $choice['value'] ] = $choice['label'];
		}

		$settings_crm[] = array(
			'name' => __( 'CRM Settings', 'formscrm' ),
			'type' => 'title',
			'desc' => '',
			'id'   => 'wc_settings_formscrm_section_title',
		);
		$settings_crm[] = array(
			'name'    => __( 'CRM', 'formscrm' ),
			'type'    => 'select',
			'desc'    => __( 'Select your CRM', 'formscrm' ),
			'options' => $options_crm,
			'id'      => 'wc_formscrm[fc_crm_type]',
		);

		if ( isset( $wc_formscrm['fc_crm_type'] ) && $wc_formscrm['fc_crm_type'] ) {
			if ( false !== array_search( $wc_formscrm['fc_crm_type'], formscrm_get_dependency_url(), true ) ) {
				$settings_crm[] = array(
					'name' => __( 'URL', 'formscrm' ),
					'type' => 'text',
					'desc' => __( 'CRM URL', 'formscrm' ),
					'id'   => 'wc_formscrm[fc_crm_url]',
				);
			}
			if ( false !== array_search( $wc_formscrm['fc_crm_type'], formscrm_get_dependency_username(), true ) ) {
				$settings_crm[] = array(
					'name' => __( 'Username', 'formscrm' ),
					'type' => 'text',
					'desc' => __( 'CRM Username', 'formscrm' ),
					'id'   => 'wc_formscrm[fc_crm_username]',
				);
			}
			if ( false !== array_search( $wc_formscrm['fc_crm_type'], formscrm_get_dependency_password(), true ) ) {
				$settings_crm[] = array(
					'name' => __( 'Password', 'formscrm' ),
					'type' => 'password',
					'desc' => __( 'Password of CRM', 'formscrm' ),
					'id'   => 'wc_formscrm[fc_crm_password]',
				);
			}
			if ( false !== array_search( $wc_formscrm['fc_crm_type'], formscrm_get_dependency_apipassword(), true ) ) {
				$settings_crm[] = array(
					'name' => __( 'API Password', 'formscrm' ),
					'type' => 'password',
					'desc' => __( 'API Password of CRM', 'formscrm' ),
					'id'   => 'wc_formscrm[fc_crm_apipassword]',
				);
			}
			if ( false !== array_search( $wc_formscrm['fc_crm_type'], formscrm_get_dependency_apisales(), true ) ) {
				$settings_crm[] = array(
					'name' => __( 'API Sales', 'formscrm' ),
					'type' => 'text',
					'desc' => __( 'API Sales of CRM', 'formscrm' ),
					'id'   => 'wc_formscrm[fc_crm_apisales]',
				);
			}
			if ( false !== array_search( $wc_formscrm['fc_crm_type'], formscrm_get_dependency_odoodb(), true ) ) {
				$settings_crm[] = array(
					'name' => __( 'Odoo Db', 'formscrm' ),
					'type' => 'text',
					'desc' => __( 'Odoo DB name', 'formscrm' ),
					'id'   => 'wc_formscrm[fc_crm_odoodb]',
				);
			}

			// Module.
			$this->include_library( $wc_formscrm['fc_crm_type'] );
			$options_module = array();
			foreach ( $this->crmlib->list_modules( $wc_formscrm ) as $module ) {
				$options_module[ $module['name'] ] = $module['label'];
			}
			$settings_crm[] = array(
				'name'    => __( 'Module', 'formscrm' ),
				'type'    => 'select',
				'desc'    => __( 'Select your Module', 'formscrm' ),
				'options' => $options_module,
				'id'      => 'wc_formscrm[fc_crm_module]',
			);
		}

		$settings_crm[] = array(
			'type' => 'sectionend',
			'id'   => 'wc_settings_formscrm_section_end',
		);

		// Settings Fields.
		if ( isset( $wc_formscrm['fc_crm_module'] ) && $wc_formscrm['fc_crm_module'] ) {
			$crm_fields     = $this->crmlib->list_fields( $wc_formscrm );
			$settings_crm[] = array(
				'name' => __( 'Field Settings', 'formscrm' ),
				'type' => 'title',
				'desc' => '',
				'id'   => 'wc_settings_formscrm_section_field',
			);
			$wc_fields = $this->get_woocommerce_order_fields();
			foreach ( $crm_fields as $crm_field ) {
				$settings_crm[] = array(
					'name'    => esc_html( $crm_field['label'] ),
					'type'    => 'select',
					'options' => $wc_fields,
					'id'      => 'wc_formscrm[fc_crm_field-' . esc_html( $crm_field['name'] ) . ']',
				);
			}
			$settings_crm[] = array(
				'type' => 'sectionend',
				'id'   => 'wc_settings_formscrm_section_field_end',
			);
		}
		return $settings_crm;
	}
	/**
	 * Update settings
	 *
	 * @return void
	 */
	public function update_settings() {
		woocommerce_update_options( $this->get_settings() );
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
	 * Process the entry.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function crm_process_entry( $order_id ) {
		$wc_formscrm = get_option( 'wc_formscrm' );
		$order       = new WC_Order( $order_id );

		if ( $wc_formscrm ) {
			$this->include_library( $wc_formscrm['fc_crm_type'] );
			$merge_vars = $this->get_merge_vars( $wc_formscrm, $order );

			$response_result = $this->crmlib->create_entry( $wc_formscrm, $merge_vars );

			if ( 'error' === $response_result['status'] ) {
				formscrm_debug_email_lead( $wc_formscrm['fc_crm_type'], 'Error ' . $response_result['message'], $merge_vars );
			} else {
				error_log( $response_result['id'] );
			}
		}
	}

	/**
	 * Extract merge variables
	 *
	 * @param array  $wc_formscrm Array settings from CRM.
	 * @param object $order Submitted data.
	 * @return array
	 */
	private function get_merge_vars( $wc_formscrm, $order ) {
		$merge_vars = array();

		foreach ( $wc_formscrm as $key => $value ) {
			if ( false !== strpos( $key, 'fc_crm_field' ) ) {
				$crm_key   = str_replace( 'fc_crm_field-', '', $key );
				$method_wc = 'get_' . $value;
				if ( $method_wc && method_exists( $order, $method_wc ) ) {
					$merge_vars[] = array(
						'name'  => $crm_key,
						'value' => $order->$method_wc(),
					);
				}
			}
		}

		return $merge_vars;
	}
}

new FormsCRM_WooCommerce();
