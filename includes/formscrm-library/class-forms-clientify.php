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

if ( ! class_exists( 'Forms_Clientify' ) ) {
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
			// Prevents fatal error is_plugin_active.
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			if ( is_plugin_active( 'gravityforms/gravityforms.php' ) && $this->has_gravity_feed_clientify() ) {
				add_action( 'gform_after_save_form', array( $this, 'create_visitor_key_field' ), 10, 2 );
				add_filter( 'gform_pre_render', array( $this, 'clientify_gravityforms_hidden_input' ) );
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			}

			if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
				add_action( 'wpcf7_after_save', array( $this, 'add_custom_field_cf7_clientify' ), 50 );
				add_action( 'wpcf7_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
				add_action( 'wpcf7_contact_form', array( $this, 'contanct_enqueue_scripts' ) );
			}
			if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				add_filter( 'woocommerce_checkout_fields' , array( $this, 'clientify_cookie_checkout_field' ) );
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
				FORMSCRM_PLUGIN_URL . 'includes/formscrm-library/js/clientify-field.js',
				array(),
				FORMSCRM_VERSION,
				true
			);
		}

		/**
		 * Has gravity any clientify?
		 *
		 * @return boolean
		 */
		private function has_gravity_feed_clientify() {
			$is_clientify = get_transient( 'formscrm_query_is_clientify' );
			if ( ! $is_clientify ) {
				$is_clientify       = 'no_clientify';
				$settings           = get_option( 'gravityformsaddon_formscrm_settings' );
				$crm_type_clientify = isset( $settings['fc_crm_type'] ) && 'clientify' === $settings['fc_crm_type'] ? true : false;
				
				if ( $crm_type_clientify ) {
					return true;
				}
				$feeds = GFAPI::get_feeds();
				foreach ( $feeds as $feed ) {
					if ( 'clientify' === $feed['meta']['fc_crm_custom_type'] ) {
						$is_clientify = 'has_clientify';
						break;
					}
				}
				set_transient( 'formscrm_query_is_clientify', $is_clientify, HOUR_IN_SECONDS * 3 );
			}			

			return 'has_clientify' === $is_clientify ? true : false;
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

		/**
		 * Adds field checkout
		 *
		 * @param array $fields
		 * @return array
		 */
		public function clientify_cookie_checkout_field( $fields ) {
			$fields['billing']['clientify_vk'] = array(
				'type'  => 'hidden',
				'class' => array( 'clientify_cookie' ),
			);

			return $fields;
		}
	}
}
new Forms_Clientify();
