<?php
/**
 * JetFormBuilder Action Integration
 *
 * Registers FormsCRM as a native JetFormBuilder action type so it appears
 * alongside GetResponse, MailChimp, etc. in the "Add new action" panel.
 *
 * @package   WordPress
 * @author    David Perez <david@closemarketing.es>
 * @copyright 2024 Closemarketing
 * @version   2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bootstraps the JetFormBuilder integration once JFB is loaded.
 */
function formscrm_jfb_init() {
	if ( ! function_exists( 'jet_form_builder' ) ) {
		return;
	}

	require_once __DIR__ . '/class-jetformbuilder-tab-handler.php';
	require_once __DIR__ . '/class-jetformbuilder-action.php';

	// Register the action type so it appears in "Add new action" modal.
	add_action(
		'jet-form-builder/actions/register',
		function ( $manager ) {
			$manager->register_action_type( new FORMSCRM_JFB_Action() );
		}
	);

	// Install the global settings tab handler.
	// jet-form-builder/after-init fires right after JFB calls rep_install() on all
	// modules (including Active_Campaign which also calls install() at this point),
	// so the manager singleton already exists and install() is safe to call.
	add_action(
		'jet-form-builder/after-init',
		function () {
			\Jet_Form_Builder\Admin\Tabs_Handlers\Tab_Handler_Manager::instance()->install(
				new FORMSCRM_JFB_Tab_Handler()
			);
		}
	);

	// Register the REST setting so the page config endpoint exposes it.
	add_action(
		'rest_api_init',
		function () {
			if ( ! class_exists( '\Jet_Form_Builder\Admin\Tabs_Handlers\Base_Handler' ) ) {
				return;
			}
			$prefix = \Jet_Form_Builder\Admin\Tabs_Handlers\Base_Handler::PREFIX;
			register_setting(
				trim( $prefix, '_' ),
				$prefix . FORMSCRM_JFB_Tab_Handler::SLUG,
				array(
					'type'         => 'string',
					'show_in_rest' => true,
					'default'      => '{}',
				)
			);

			// REST: fetch CRM modules (used by the block editor action panel).
			register_rest_route(
				'formscrm/v1',
				'/jfb/modules',
				array(
					'methods'             => 'POST',
					'callback'            => 'formscrm_jfb_rest_get_modules',
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);

			// REST: fetch CRM fields for a selected module.
			register_rest_route(
				'formscrm/v1',
				'/jfb/fields',
				array(
					'methods'             => 'POST',
					'callback'            => 'formscrm_jfb_rest_get_fields',
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	);

	// Enqueue the compiled block-editor action panel JS.
	add_action( 'jet-form-builder/editor-assets/after', 'formscrm_jfb_editor_assets' );

	// Enqueue the Vue settings-page tab JS for the JFB Settings page.
	add_action( 'jet-fb/admin-pages/before-assets/jfb-settings', 'formscrm_jfb_settings_assets' );
}
add_action( 'plugins_loaded', 'formscrm_jfb_init', 20 );

/**
 * Enqueue the compiled block-editor JS (React action panel).
 */
function formscrm_jfb_editor_assets() {
	$asset_file = FORMSCRM_PLUGIN_PATH . 'includes/formscrm-library/assets/build/jfb-editor.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}
	$asset = require $asset_file;

	array_push(
		$asset['dependencies'],
		'jet-fb-components',
		'jet-fb-data',
		'jet-fb-actions-v2',
		'jet-fb-blocks-v2-to-actions-v2'
	);

	wp_enqueue_script(
		'formscrm-jfb-editor',
		FORMSCRM_PLUGIN_URL . 'includes/formscrm-library/assets/build/jfb-editor.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	wp_localize_script(
		'formscrm-jfb-editor',
		'formsCrmJfb',
		array(
			'restUrl' => esc_url_raw( rest_url( 'formscrm/v1/jfb' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'choices' => formscrm_get_choices(),
		)
	);
}

/**
 * Enqueue the Vue settings-page tab JS for JetForm > Settings.
 */
function formscrm_jfb_settings_assets() {
	$asset_file = FORMSCRM_PLUGIN_PATH . 'includes/formscrm-library/assets/build/jfb-settings.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}
	$asset = require $asset_file;

	wp_enqueue_script(
		'formscrm-jfb-settings',
		FORMSCRM_PLUGIN_URL . 'includes/formscrm-library/assets/build/jfb-settings.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	wp_localize_script(
		'formscrm-jfb-settings',
		'formsCrmJfb',
		array(
			'choices' => formscrm_get_choices(),
		)
	);
}

/**
 * Merge global saved credentials into $settings when use_global is set.
 *
 * @param array $settings Request settings, may include use_global flag.
 * @return array Settings with global credentials merged in when applicable.
 */
function formscrm_jfb_resolve_settings( array $settings ): array {
	if ( ! empty( $settings['use_global'] ) ) {
		$option_name = \Jet_Form_Builder\Admin\Tabs_Handlers\Base_Handler::PREFIX . FORMSCRM_JFB_Tab_Handler::SLUG;
		$raw_global  = get_option( $option_name, '{}' );
		$global      = json_decode( $raw_global, true );
		if ( ! is_array( $global ) ) {
			$global = array();
		}
		// Global credentials take precedence; per-form values (module, fields_map) are kept.
		$settings = array_merge( $settings, $global );
	}
	return $settings;
}

/**
 * REST: return available modules for the selected CRM type + credentials.
 *
 * @param WP_REST_Request $request The REST request containing CRM settings as JSON body.
 * @return WP_REST_Response|WP_Error
 */
function formscrm_jfb_rest_get_modules( $request ) {
	$settings = array_map( 'sanitize_text_field', (array) $request->get_json_params() );
	$settings = formscrm_jfb_resolve_settings( $settings );
	$crm_type = $settings['fc_crm_type'] ?? '';

	if ( ! $crm_type ) {
		return new WP_Error( 'no_crm_type', __( 'CRM type is required.', 'formscrm' ), array( 'status' => 400 ) );
	}

	$crmlib = formscrm_get_api_class( $crm_type );
	if ( ! $crmlib ) {
		return new WP_Error( 'unknown_crm', __( 'Unknown CRM type.', 'formscrm' ), array( 'status' => 400 ) );
	}

	$modules = $crmlib->list_modules( $settings );
	$result  = array();
	foreach ( (array) $modules as $module ) {
		$value = $module['value'] ?? $module['name'] ?? '';
		$label = $module['label'] ?? $value;
		if ( $value && $label ) {
			$result[] = array(
				'value' => $value,
				'label' => $label,
			);
		}
	}

	return rest_ensure_response( $result );
}

/**
 * REST: return available CRM fields for a module.
 *
 * @param WP_REST_Request $request The REST request containing CRM settings and module as JSON body.
 * @return WP_REST_Response|WP_Error
 */
function formscrm_jfb_rest_get_fields( $request ) {
	$settings = array_map( 'sanitize_text_field', (array) $request->get_json_params() );
	$settings = formscrm_jfb_resolve_settings( $settings );
	$crm_type = $settings['fc_crm_type'] ?? '';
	$module   = $settings['fc_crm_module'] ?? '';

	if ( ! $crm_type || ! $module ) {
		return new WP_Error( 'missing_params', __( 'CRM type and module are required.', 'formscrm' ), array( 'status' => 400 ) );
	}

	$crmlib = formscrm_get_api_class( $crm_type );
	if ( ! $crmlib ) {
		return new WP_Error( 'unknown_crm', __( 'Unknown CRM type.', 'formscrm' ), array( 'status' => 400 ) );
	}

	$login = $crmlib->login( $settings );
	if ( ! $login || ( is_array( $login ) && isset( $login['status'] ) && 'error' === $login['status'] ) ) {
		return new WP_Error( 'login_failed', $login['message'], array( 'status' => 400 ) );
	}

	$fields = $crmlib->list_fields( $settings, $module );
	$result = array();
	foreach ( (array) $fields as $field ) {
		$name  = $field['name'] ?? '';
		$label = $field['label'] ?? $name;
		if ( ! $name ) {
			continue;
		}
		$result[] = array(
			'name'     => sanitize_text_field( $name ),
			'label'    => sanitize_text_field( $label ),
			'required' => ! empty( $field['req'] ),
		);
	}

	return rest_ensure_response( $result );
}
