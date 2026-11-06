<?php
/**
 * Plugin bootstrap.
 *
 * Wires every integration and admin screen once the plugin is loaded.
 *
 * @package    FormsCRM
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2026 Closemarketing
 */

namespace CLOSE\FormsCRM;

defined( 'ABSPATH' ) || exit;

use CLOSE\FormsCRM\Admin\AdminOptions;
use CLOSE\FormsCRM\Admin\ErrorLog;
use CLOSE\FormsCRM\Admin\ErrorLogPage;
use CLOSE\FormsCRM\Admin\ReviewNotice;
use CLOSE\FormsCRM\Integrations\ContactForm7\Settings as ContactForm7Settings;
use CLOSE\FormsCRM\Integrations\Elementor\ActionAfterSubmit;
use CLOSE\FormsCRM\Integrations\GravityForms\Bootstrap as GravityFormsBootstrap;
use CLOSE\FormsCRM\Integrations\WooCommerce\WooCommerce;
use CLOSE\FormsCRM\Integrations\WPForms\Provider as WPFormsProvider;

/**
 * Main plugin class.
 */
final class Plugin {

	/**
	 * Whether the plugin has already been booted.
	 *
	 * @var bool
	 */
	private static $booted = false;

	/**
	 * Boots the plugin: helpers, admin screens and form integrations.
	 *
	 * @return void
	 */
	public static function boot() {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;

		LegacyAliases::register();

		require_once FORMSCRM_PLUGIN_PATH . 'includes/formscrm-library/helpers-functions.php';
		require_once FORMSCRM_PLUGIN_PATH . 'includes/formscrm-library/helpers-library-crm.php';

		// Prevents fatal error is_plugin_active.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		self::load_integrations();
		self::load_admin();
	}

	/**
	 * Loads the integrations for the active form plugins.
	 *
	 * @return void
	 */
	private static function load_integrations() {
		if ( is_plugin_active( 'gravityforms/gravityforms.php' ) || is_plugin_active( 'gravity-forms/gravityforms.php' ) ) {
			require_once FORMSCRM_PLUGIN_PATH . 'includes/formscrm-library/gravityforms.php';
			GravityFormsBootstrap::init();
		}

		if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
			new ContactForm7Settings();
		}

		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			new WooCommerce();
		}

		if ( is_plugin_active( 'jetformbuilder/jet-form-builder.php' ) ) {
			require_once FORMSCRM_PLUGIN_PATH . 'includes/formscrm-library/jetformbuilder.php';
		}

		if ( is_plugin_active( 'wpforms/wpforms.php' ) ) {
			add_action( 'wpforms_loaded', array( __CLASS__, 'load_wpforms' ) );
		}

		if ( is_plugin_active( 'elementor/elementor.php' ) ) {
			require_once FORMSCRM_PLUGIN_PATH . 'includes/formscrm-library/elementor-ajax.php';
			add_action( 'elementor_pro/init', array( __CLASS__, 'load_elementor' ) );
			add_action( 'elementor/editor/after_enqueue_scripts', array( __CLASS__, 'enqueue_elementor_editor' ) );
		}
	}

	/**
	 * Loads the admin screens and the error log.
	 *
	 * @return void
	 */
	private static function load_admin() {
		global $formscrm_error_log;
		$formscrm_error_log = new ErrorLog();

		if ( is_admin() ) {
			new AdminOptions();
			new ErrorLogPage();
		}

		new ReviewNotice();
	}

	/**
	 * Registers the WPForms provider. WPForms Pro is required.
	 *
	 * @return void
	 */
	public static function load_wpforms() {
		if ( ! wpforms()->pro ) {
			return;
		}
		new WPFormsProvider();
	}

	/**
	 * Registers the Elementor Pro form action.
	 *
	 * @return void
	 */
	public static function load_elementor() {
		$action = new ActionAfterSubmit();

		\ElementorPro\Plugin::instance()->modules_manager->get_modules( 'forms' )->add_form_action( $action->get_name(), $action );

		// Injects the tracking hidden field as each Clientify-connected form renders.
		add_filter( 'elementor/widget/render_content', array( ActionAfterSubmit::class, 'inject_analytics_plus_fields' ), 10, 2 );
	}

	/**
	 * Enqueues the Elementor editor assets.
	 *
	 * @return void
	 */
	public static function enqueue_elementor_editor() {
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
}
