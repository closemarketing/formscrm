<?php
/**
 * PHPStan Bootstrap File
 * 
 * This file defines constants and functions that PHPStan needs to understand
 * but are not available during static analysis.
 */

// Define PHPStan constant for stubs detection.
if ( ! defined( 'PHPSTAN' ) ) {
	define( 'PHPSTAN', true );
}

// Define plugin constants that are used throughout the codebase.
if ( ! defined( 'FORMSCRM_VERSION' ) ) {
	define( 'FORMSCRM_VERSION', '4.0.7' );
}

if ( ! defined( 'FORMSCRM_PLUGIN' ) ) {
	define( 'FORMSCRM_PLUGIN', __FILE__ );
}

if ( ! defined( 'FORMSCRM_PLUGIN_URL' ) ) {
	define( 'FORMSCRM_PLUGIN_URL', 'http://localhost/wp-content/plugins/formscrm/' );
}

if ( ! defined( 'FORMSCRM_PLUGIN_PATH' ) ) {
	define( 'FORMSCRM_PLUGIN_PATH', '/path/to/formscrm/' );
}

if ( ! defined( 'FORMSCRM_CRED_VARIABLES' ) ) {
	define( 'FORMSCRM_CRED_VARIABLES', array( 'url', 'username', 'password', 'apipassword', 'odoodb', 'apisales' ) );
}

// Define WordPress constants that might be missing.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/path/to/wordpress/' );
}

if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

if ( ! defined( 'DOING_AJAX' ) ) {
	define( 'DOING_AJAX', false );
}

// Mock WordPress functions that PHPStan can't find.
if ( ! function_exists( 'wp_doing_ajax' ) ) {
	function wp_doing_ajax() {
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}
}

// Mock common WordPress functions for PHPStan.
if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ) {
		return 'http://localhost/wp-content/plugins/formscrm/';
	}
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return '/path/to/formscrm/';
	}
}

if ( ! function_exists( 'load_plugin_textdomain' ) ) {
	function load_plugin_textdomain( $domain, $deprecated = false, $plugin_rel_path = false ) {
		return true;
	}
}

/**
 * External Plugin Stubs
 * ============================================================================
 * Type hints for external plugin classes and functions that are not available
 * during static analysis.
 */

/**
 * Gravity Forms Stubs
 * ============================================================================
 */

/**
 * Gravity Forms Model class
 */
class GFFormsModel {
	/**
	 * Get form
	 *
	 * @param int $form_id Form ID.
	 * @return array|null
	 */
	public static function get_form( $form_id ) {}

	/**
	 * Get form meta
	 *
	 * @param int $form_id Form ID.
	 * @return array|null
	 */
	public static function get_form_meta( $form_id ) {}

	/**
	 * Get lead
	 *
	 * @param int $lead_id Lead ID.
	 * @return array|null
	 */
	public static function get_lead( $lead_id ) {}

	/**
	 * Get lead field value
	 *
	 * @param array  $lead Lead data.
	 * @param object $field Field object.
	 * @return mixed
	 */
	public static function get_lead_field_value( $lead, $field ) {}

	/**
	 * Get next field ID
	 *
	 * @param array $form Form data.
	 * @return int
	 */
	public static function get_next_field_id( $form ) {}
}

/**
 * Gravity Forms RGFormsModel class (legacy)
 */
class RGFormsModel extends GFFormsModel {
	/**
	 * Get input type
	 *
	 * @param object $field Field object.
	 * @return string
	 */
	public static function get_input_type( $field ) {}

	/**
	 * Get field
	 *
	 * @param array $form Form data.
	 * @param int   $field_id Field ID.
	 * @return object|null
	 */
	public static function get_field( $form, $field_id ) {}
}

/**
 * Gravity Forms Fields class
 */
class GF_Fields {
	/**
	 * Get field
	 *
	 * @param mixed $field Field data.
	 * @return object|null
	 */
	public static function get( $field ) {}

	/**
	 * Create field
	 *
	 * @param array $field Field data.
	 * @return object|null
	 */
	public static function create( $field ) {}
}

/**
 * Gravity Forms API class
 */
class GFAPI {
	/**
	 * Get form
	 *
	 * @param int $form_id Form ID.
	 * @return array|null
	 */
	public static function get_form( $form_id ) {}

	/**
	 * Get entry
	 *
	 * @param int $entry_id Entry ID.
	 * @return array|null
	 */
	public static function get_entry( $entry_id ) {}

	/**
	 * Add entry
	 *
	 * @param array $entry Entry data.
	 * @return int|WP_Error
	 */
	public static function add_entry( $entry ) {}

	/**
	 * Update entry
	 *
	 * @param array $entry Entry data.
	 * @return bool|WP_Error
	 */
	public static function update_entry( $entry ) {}

	/**
	 * Delete entry
	 *
	 * @param int $entry_id Entry ID.
	 * @return bool|WP_Error
	 */
	public static function delete_entry( $entry_id ) {}

	/**
	 * Update form
	 *
	 * @param array $form Form data.
	 * @param int   $form_id Form ID.
	 * @return int|WP_Error
	 */
	public static function update_form( $form, $form_id = 0 ) {}
}

/**
 * Gravity Forms Common class
 */
class GFCommon {
	/**
	 * Get base URL
	 *
	 * @return string
	 */
	public static function get_base_url() {}

	/**
	 * Get base path
	 *
	 * @return string
	 */
	public static function get_base_path() {}

	/**
	 * Format number
	 *
	 * @param mixed  $number Number to format.
	 * @param string $format Format string.
	 * @return string
	 */
	public static function format_number( $number, $format = '' ) {}

	/**
	 * Get product fields
	 *
	 * @param array $form Form data.
	 * @param array $lead Lead data.
	 * @return array
	 */
	public static function get_product_fields( $form, $lead ) {}

	/**
	 * JSON encode
	 *
	 * @param mixed $data Data to encode.
	 * @return string
	 */
	public static function json_encode( $data ) {}

	/**
	 * Check if field is product field
	 *
	 * @param object $field Field object.
	 * @return bool
	 */
	public static function is_product_field( $field ) {}
}

/**
 * Gravity Forms main class
 */
class GFForms {
	/**
	 * Get instance
	 *
	 * @return GFForms
	 */
	public static function get_instance() {}

	/**
	 * Include feed addon framework
	 *
	 * @return void
	 */
	public static function include_feed_addon_framework() {}
}

/**
 * Gravity Forms Add-On Framework class
 */
class GFAddOn {
	/**
	 * Get form settings
	 *
	 * @param array $form Form data.
	 * @return array
	 */
	public function get_form_settings( $form ) {}

	/**
	 * Get plugin settings
	 *
	 * @return array
	 */
	public function get_plugin_settings() {}

	/**
	 * Register addon
	 *
	 * @param string $class_name Class name.
	 * @return void
	 */
	public static function register( $class_name ) {}
}

/**
 * Gravity Forms Feed Add-On class
 */
class GFFeedAddOn extends GFAddOn {
	/**
	 * Init admin
	 *
	 * @return void
	 */
	public function init_admin() {}

	/**
	 * Settings text
	 *
	 * @param array $field Field data.
	 * @param bool  $echo Echo or return.
	 * @return string
	 */
	public function settings_text( $field, $echo = true ) {}

	/**
	 * Feed edit page
	 *
	 * @param array $form Form data.
	 * @param int   $feed_id Feed ID.
	 * @return void
	 */
	public function feed_edit_page( $form, $feed_id ) {}

	/**
	 * Get current feed
	 *
	 * @return array|null
	 */
	public function get_current_feed() {}

	/**
	 * Get feeds
	 *
	 * @param int $form_id Form ID.
	 * @return array
	 */
	public function get_feeds( $form_id = 0 ) {}

	/**
	 * Upgrade
	 *
	 * @param string $previous_version Previous version.
	 * @return void
	 */
	public function upgrade( $previous_version ) {}

	/**
	 * Get field map fields
	 *
	 * @param array $form Form data.
	 * @param array $field_type Field type.
	 * @return array
	 */
	public function get_field_map_fields( $form, $field_type ) {}

	/**
	 * Add note
	 *
	 * @param int    $entry_id Entry ID.
	 * @param string $note Note text.
	 * @param string $note_type Note type.
	 * @return int
	 */
	public function add_note( $entry_id, $note, $note_type = 'note' ) {}
}

/**
 * Gravity Forms CRM class
 */
class GFCRM {}

/**
 * Get array value by key
 *
 * @param array  $array Array to search.
 * @param string $key Key to find.
 * @return mixed
 */
function rgar( $array, $key ) {}

/**
 * Check if value is blank
 *
 * @param mixed $value Value to check.
 * @return bool
 */
function rgblank( $value ) {}

/**
 * Get POST value
 *
 * @param string $key Key to get.
 * @return mixed
 */
function rgpost( $key ) {}

/**
 * Add form meta
 *
 * @param int    $form_id Form ID.
 * @param string $meta_key Meta key.
 * @param mixed  $meta_value Meta value.
 * @param int    $entry_id Entry ID (optional).
 * @return bool
 */
function gform_add_meta( $form_id, $meta_key, $meta_value, $entry_id = 0 ) {}

/**
 * Action Scheduler Stubs
 * ============================================================================
 */

if ( ! function_exists( 'as_schedule_single_action' ) ) {
	/**
	 * Schedule a single action.
	 *
	 * @param int    $timestamp Unix timestamp.
	 * @param string $hook      Action hook.
	 * @param array  $args      Action arguments.
	 * @param string $group     Action group.
	 * @return int
	 */
	function as_schedule_single_action( $timestamp, $hook, $args = array(), $group = '' ) {
		return 0;
	}
}

if ( ! function_exists( 'as_has_scheduled_action' ) ) {
	/**
	 * Check if an action is scheduled.
	 *
	 * @param string $hook  Action hook.
	 * @param array  $args  Action arguments.
	 * @param string $group Action group.
	 * @return bool
	 */
	function as_has_scheduled_action( $hook, $args = array(), $group = '' ) {
		return false;
	}
}

if ( ! function_exists( 'as_unschedule_all_actions' ) ) {
	/**
	 * Unschedule all pending actions for a hook.
	 *
	 * @param string $hook  Action hook.
	 * @param array  $args  Action arguments.
	 * @param string $group Action group.
	 * @return void
	 */
	function as_unschedule_all_actions( $hook, $args = array(), $group = '' ) {}
}

/**
 * Action Scheduler action object (minimal stub for type-checking).
 */
if ( ! class_exists( 'ActionScheduler_Action' ) ) {
	class ActionScheduler_Action {
		/**
		 * Get the hook name.
		 *
		 * @return string
		 */
		public function get_hook() {
			return '';
		}
	}
}

/**
 * WooCommerce Stubs
 * ============================================================================
 */

/**
 * WooCommerce Order class
 */
class WC_Order {
	/**
	 * Constructor
	 *
	 * @param int|WC_Order $order Order ID or object.
	 */
	public function __construct( $order = 0 ) {}

	/**
	 * Get ID
	 *
	 * @return int
	 */
	public function get_id() {}

	/**
	 * Get billing first name
	 *
	 * @return string
	 */
	public function get_billing_first_name() {}

	/**
	 * Get billing last name
	 *
	 * @return string
	 */
	public function get_billing_last_name() {}

	/**
	 * Get billing email
	 *
	 * @return string
	 */
	public function get_billing_email() {}

	/**
	 * Get billing phone
	 *
	 * @return string
	 */
	public function get_billing_phone() {}

	/**
	 * Get total
	 *
	 * @return float
	 */
	public function get_total() {}

	/**
	 * Get items
	 *
	 * @return array
	 */
	public function get_items() {}

	/**
	 * Get meta
	 *
	 * @param string $key Meta key.
	 * @return mixed
	 */
	public function get_meta( $key ) {}
}

/**
 * WooCommerce admin fields
 *
 * @param array $options Options array.
 * @return void
 */
function woocommerce_admin_fields( $options ) {}

/**
 * WooCommerce update options
 *
 * @param array $options Options array.
 * @return void
 */
function woocommerce_update_options( $options ) {}

/**
 * WPForms Stubs
 * ============================================================================
 */

/**
 * WPForms Provider base class
 */
class WPForms_Provider {
	/**
	 * Provider version
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * Provider name
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * Provider slug
	 *
	 * @var string
	 */
	public $slug = '';

	/**
	 * Provider priority
	 *
	 * @var int
	 */
	public $priority = 10;

	/**
	 * Provider icon
	 *
	 * @var string
	 */
	public $icon = '';

	/**
	 * Error method
	 *
	 * @param string $message Error message.
	 * @return array Error details array.
	 */
	public function error( $message ) {
		return array( 'error' => $message );
	}
}

/**
 * Get WPForms instance
 *
 * @return object|null
 */
function wpforms() {}

/**
 * WPForms log function
 *
 * @param string $title Log title.
 * @param mixed  $message Log message.
 * @param array  $args Additional args.
 * @return void
 */
function wpforms_log( $title = '', $message = '', $args = array() ) {}

/**
 * Contact Form 7 Stubs
 * ============================================================================
 */

/**
 * Contact Form 7 Submission class
 */
class WPCF7_Submission {
	/**
	 * Get instance
	 *
	 * @return WPCF7_Submission|null
	 */
	public static function get_instance() {}

	/**
	 * Get contact form
	 *
	 * @return WPCF7_ContactForm|null
	 */
	public function get_contact_form() {}

	/**
	 * Get posted data
	 *
	 * @param string $name Field name.
	 * @return mixed
	 */
	public function get_posted_data( $name = '' ) {}
}

/**
 * Contact Form 7 Contact Form class
 */
class WPCF7_ContactForm {
	/**
	 * Get instance
	 *
	 * @param int $post_id Post ID.
	 * @return WPCF7_ContactForm|null
	 */
	public static function get_instance( $post_id = 0 ) {}

	/**
	 * Get ID
	 *
	 * @return int
	 */
	public function id() {}

	/**
	 * Get title
	 *
	 * @return string
	 */
	public function title() {}

	/**
	 * Get properties
	 *
	 * @param string $name Property name.
	 * @return mixed
	 */
	public function prop( $name ) {}
}

/**
 * Elementor Stubs
 * ============================================================================
 * Using if (!class_exists()) to avoid namespace issues
 */

// Elementor namespace classes.
if ( ! class_exists( 'Elementor\Controls_Manager' ) ) {
	class Elementor_Controls_Manager {
		const SELECT   = 'select';
		const URL      = 'url';
		const TEXT     = 'text';
		const SWITCHER = 'switcher';
		const BUTTON   = 'button';
		const RAW_HTML = 'raw_html';
		const HIDDEN   = 'hidden';
	}
	class_alias( 'Elementor_Controls_Manager', 'Elementor\Controls_Manager' );
}

// ElementorPro namespace classes.
if ( ! class_exists( 'ElementorPro\Modules\Forms\Classes\Action_Base' ) ) {
	class ElementorPro_Action_Base {
		public function get_name() {}
		public function get_label() {}
		public function register_settings_section( $widget ) {}
		public function on_export( $element ) {}
	}
	class_alias( 'ElementorPro_Action_Base', 'ElementorPro\Modules\Forms\Classes\Action_Base' );
}

if ( ! class_exists( 'ElementorPro\Modules\Forms\Widgets\Form' ) ) {
	class ElementorPro_Form_Widget {
		public function get_name() {}
	}
	class_alias( 'ElementorPro_Form_Widget', 'ElementorPro\Modules\Forms\Widgets\Form' );
}

if ( ! class_exists( 'ElementorPro\Plugin' ) ) {
	class ElementorPro_Plugin {
		public static function instance() {}
	}
	class_alias( 'ElementorPro_Plugin', 'ElementorPro\Plugin' );
}
