<?php
/**
 * Gravity forms for Clientify
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2020 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Clientify Visitor Key.
 *
 * Renders the visitor key for Gravity Forms.
 *
 * @since 3.5
 */
class Forms_Clientify {

	/**
	 * Construct of Class
	 */
	public function __construct() {
		$settings     = get_option( 'gravityformsaddon_formscrm_settings' );
		$is_clientify = isset( $settings['fc_crm_type'] ) && 'clientify' === $settings['fc_crm_type'] ? true : false;

		if ( $is_clientify ) {
			add_action( 'gform_after_save_form', array( $this, 'create_visitor_key_field' ), 10, 2 );
			add_filter( 'gform_pre_render', array( $this, 'clientify_gravityforms_hidden_input' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
			add_action( 'wpcf7_after_save', array( $this, 'add_custom_field_cf7_clientify' ), 50 );
			add_action( 'wpcf7_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'wpcf7_contact_form', array( $this, 'contanct_enqueue_scripts' ) );
		}
	}

	/**
	 * Enqueue Scripts
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_register_script(
			'formscrm-clientify-field',
			plugins_url( '/js/clientify-field.js', __FILE__ ),
			array(),
			FORMSCRM_VERSION,
			true
		);
	}

	/**
	 * Create field in editor visitor key
	 *
	 * @param array   $form Gravity form.
	 * @param boolean $is_new Is new?.
	 * @return void
	 */
	public function create_visitor_key_field( $form, $is_new ) {
		if ( ! $is_new ) {
			// Check if field exists.
			foreach ( $form['fields'] as $field ) {
				if ( isset( $field['adminLabel'] ) && 'clientify_visitor_key' === $field['adminLabel'] ) {
					return;
				}
			}
		}
		$new_field_id   = GFFormsModel::get_next_field_id( $form['fields'] );
		$field_property = array(
			'id'         => $new_field_id,
			'cssClass'   => 'clientify_cookie',
			'label'      => __( 'Clientify Visitor Key', 'formscrm' ),
			'type'       => 'hidden',
			'adminLabel' => 'clientify_visitor_key',
		);
		$form['fields'][] = GF_Fields::create( $field_property );
		GFAPI::update_form( $form );
	}

	public function clientify_gravityforms_hidden_input( $form ) {

		foreach ( $form['fields'] as &$field ) {
			if ( isset( $field->adminLabel ) && 'clientify_visitor_key' === $field->adminLabel ) { //phpcs:ignore
				$field->defaultValue = isset( $_COOKIE['vk'] ) ? sanitize_text_field( $_COOKIE['vk'] ) : '';
				wp_enqueue_script( 'formscrm-clientify-field' );
			}
		}
		return $form;
	}

	/**
	 * Adds field for Contact Form 7
	 *
	 * @param object $args Args of action.
	 * @return void
	 */
	public function add_custom_field_cf7_clientify( $args ) {
		$cf7_options = get_option( 'cf7_crm_' . $args->id );
		$crm_type    = isset( $cf7_options['fc_crm_type'] ) ? $cf7_options['fc_crm_type'] : '';
		if ( 'clientify' !== $crm_type ) {
			return;
		}
		$form_content = get_post_meta( $args->id, '_form', true );

		if ( false === strpos( $form_content, 'clientify_cookie' ) ) {
			$pos_submit = strpos( $form_content, '[submit' );
			if ( false !== $pos_submit ) {
				$form_content = str_replace( '[submit', '[hidden clientify_cookie class:clientify_cookie][submit', $form_content );

				update_post_meta( $args->id, '_form', $form_content );
			}
		}
	}

	/**
	 * Enqueue Contact Form 7
	 *
	 * @return void
	 */
	public function contanct_enqueue_scripts() {
		wp_enqueue_script( 'formscrm-clientify-field' );
	}

}

new Forms_Clientify();
