<?php
/**
 * Loader
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2020 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

require_once 'helpers-functions.php';
require_once 'helpers-library-crm.php';

// Prevents fatal error is_plugin_active.
if ( ! function_exists( 'is_plugin_active' ) ) {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ( is_plugin_active( 'gravityforms/gravityforms.php' ) || is_plugin_active( 'gravity-forms/gravityforms.php' ) ) && ! class_exists( 'FORMSCRM_Bootstrap' ) ) {
	add_action( 'gform_loaded', array( 'FORMSCRM_Bootstrap', 'load' ), 5 );
	/**
	 * Bootstrap class for Gravity Forms integration.
	 */
	class FORMSCRM_Bootstrap {

		/**
		 * Loads the Gravity Forms Feed Add-On.
		 *
		 * @return void
		 */
		public static function load() {

			if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
				return;
			}

			require_once 'class-gravityforms.php';

			GFAddOn::register( 'GFCRM' );
		}
	}

	/**
	 * Returns the Gravity Forms CRM instance.
	 *
	 * @return object The CRM instance.
	 */
	function gf_crm() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, Universal.Files.SeparateFunctionsFromOO.Mixed -- Legacy function name for Gravity Forms compatibility.
		return GFCRM::get_instance();
	}

	require_once 'class-gravityforms-widget.php';
	require_once 'class-gravityforms-markdown-export.php';
}

// ContactForms7.
if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) && ! class_exists( 'FORMSCRM_CF7_Settings' ) ) {
	require_once 'class-contactform7.php';
}

// WooCommerce.
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) && ! class_exists( 'FormsCRM_WooCommerce' ) ) {
	require_once 'class-woocommerce.php';
}

// JetFormBuilder.
if ( is_plugin_active( 'jetformbuilder/jet-form-builder.php' ) && ! class_exists( 'FORMSCRM_JetFormBuilder' ) ) {
	require_once 'class-jetformbuilder.php';
}

// WPForms.
if ( is_plugin_active( 'wpforms/wpforms.php' ) && ! class_exists( 'FormsCRM_WPForms' ) ) {
	add_action( 'wpforms_loaded', 'formscrm_wpforms' );
	/**
	 * Load the provider class.
	 *
	 * @since 3.7.2
	 */
	function formscrm_wpforms() { // phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed -- Loading function for WPForms integration.

		// WPForms Pro is required.
		if ( ! wpforms()->pro ) {
			return;
		}
		require_once 'class-wpforms.php';
	}
}

// Elementor.
if ( is_plugin_active( 'elementor/elementor.php' ) ) {
	require_once 'elementor-ajax.php';
	add_action(
		'elementor_pro/init',
		function () {
			// Here its safe to include our action class file.
			include_once FORMSCRM_PLUGIN_PATH . 'includes/formscrm-library/class-elementor.php';

			// Instantiate the action class.
			$formscrm_action = new FormsCRM_Elementor_Action_After_Submit();

			// Register the action with form widget.
			\ElementorPro\Plugin::instance()->modules_manager->get_modules( 'forms' )->add_form_action( $formscrm_action->get_name(), $formscrm_action );
		}
	);

	add_action(
		'elementor/editor/after_enqueue_scripts',
		function () {
			wp_enqueue_script(
				'formcrm-elementor-editor-script',
				FORMSCRM_PLUGIN_URL . 'includes/assets/elementor-editor.js',
				array( 'jquery', 'elementor-editor' ),
				FORMSCRM_VERSION,
				true
			);

			wp_localize_script(
				'formcrm-elementor-editor-script',
				'formcrm_elementor',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'formcrm_nonce' ),
				)
			);

			wp_enqueue_style(
				'formcrm-elementor-editor-style',
				FORMSCRM_PLUGIN_URL . 'includes/assets/elementor.css',
				array(),
				FORMSCRM_VERSION
			);
		}
	);
}
