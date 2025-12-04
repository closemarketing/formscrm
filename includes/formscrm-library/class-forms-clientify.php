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
				add_action( 'gform_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10, 2 );
				add_action( 'gform_enqueue_scripts', array( $this, 'contact_enqueue_scripts' ), 15, 2 );
			}

			if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
				add_action( 'wpcf7_after_save', array( $this, 'add_custom_field_cf7_clientify' ), 50 );
				add_action( 'wpcf7_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
				add_action( 'wpcf7_contact_form', array( $this, 'contact_enqueue_scripts' ) );
			}
			if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				add_filter( 'woocommerce_checkout_fields', array( $this, 'clientify_cookie_checkout_field' ) );
			}

			// elementor
			if ( is_plugin_active( 'elementor/elementor.php' ) ) {

				// filter form fields before render
				add_filter(
					'elementor/widget/render_content',
					function ( $widget_content, $form ) {

						// check if form is type of ElementorPro\Modules\Forms\Widgets\Form
						if ( ! $form instanceof \ElementorPro\Modules\Forms\Widgets\Form ) {
							return $widget_content;
						}

						$settings = $form->get_settings_for_display();
						if ( empty( $settings['fc_crm_type'] ) ) {
							return $widget_content;
						}
						$crm_type = $settings['fc_crm_type'];
						if ( 'clientify' !== $crm_type ) {
							return $widget_content;
						}
						// check if visitor_key field exists in content
						if ( false === strpos( $widget_content, 'visitor_key' ) ) {
							// add visitor_key field before <button only once
							$pos_button = strpos( $widget_content, '<button' );
							if ( false !== $pos_button ) {
								global $wp_session;
								$visitor_key = isset( $wp_session['clientify_visitor_key'] ) ? $wp_session['clientify_visitor_key'] : '';

								$widget_content = preg_replace( '/<button/', '<input type="hidden" name="visitor_key" class="visitor_key" value="' . $visitor_key . '" /><button', $widget_content, 1 );
							}
						}

						return $widget_content;
					},
					10,
					2
				);
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
			global $wpdb;
			$is_clientify = get_transient( 'formscrm_query_is_clientify' );
			if ( ! $is_clientify ) {
				$is_clientify       = 'no_clientify';
				$settings           = get_option( 'gravityformsaddon_formscrm_settings' );
				$crm_type_clientify = isset( $settings['fc_crm_type'] ) && 'clientify' === $settings['fc_crm_type'] ? true : false;

				if ( $crm_type_clientify ) {
					return true;
				}

				$table = $wpdb->prefix . 'gf_addon_feed';
				$sql   = "SELECT COUNT(*) as count FROM $table WHERE `meta` LIKE '%clientify%';";
				$count = (int) $wpdb->get_var( $sql );
				if ( $count > 0 ) {
					$is_clientify = 'has_clientify';
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
			$new_field_id     = GFFormsModel::get_next_field_id( $form['fields'] );
			$field_property   = array(
				'id'         => $new_field_id,
				'cssClass'   => 'clientify_cookie',
				'label'      => __( 'Clientify Visitor Key', 'formscrm' ),
				'type'       => 'hidden',
				'adminLabel' => 'clientify_visitor_key',
			);
			$form['fields'][] = GF_Fields::create( $field_property );
			GFAPI::update_form( $form );
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
		public function contact_enqueue_scripts() {
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
