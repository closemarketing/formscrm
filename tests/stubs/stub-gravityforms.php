<?php
/**
 * Stubs for Gravity Forms classes used in tests.
 *
 * Allows class-gravityforms.php (GFCRM) to be loaded without Gravity Forms installed.
 * Only the API surface GFCRM touches in tests is implemented.
 *
 * @package Formscrm
 */

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound, Generic.CodeAnalysis.UnusedFunctionParameter.Found

if ( ! class_exists( 'GFForms' ) ) {
	/**
	 * Stub for GFForms.
	 */
	class GFForms {
		/**
		 * Loads the feed add-on framework (no-op in tests).
		 *
		 * @return void
		 */
		public static function include_feed_addon_framework() {}
	}
}

if ( ! class_exists( 'GFAddOn' ) ) {
	/**
	 * Stub for GFAddOn.
	 */
	class GFAddOn {
		/**
		 * Add-on slug.
		 *
		 * @var string
		 */
		protected $_slug = ''; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

		/**
		 * Plugin settings returned by get_plugin_settings().
		 *
		 * @var array
		 */
		public $test_plugin_settings = array();

		/**
		 * Init (no-op).
		 *
		 * @return void
		 */
		public function init() {}

		/**
		 * Init admin (no-op).
		 *
		 * @return void
		 */
		public function init_admin() {}

		/**
		 * Returns the add-on slug.
		 *
		 * @return string
		 */
		public function get_slug() {
			return $this->_slug;
		}

		/**
		 * Returns plugin settings.
		 *
		 * @return array
		 */
		public function get_plugin_settings() {
			return $this->test_plugin_settings;
		}
	}
}

if ( ! class_exists( 'GFFeedAddOn' ) ) {
	/**
	 * Stub for GFFeedAddOn.
	 */
	class GFFeedAddOn extends GFAddOn {
		/**
		 * Returns the current feed.
		 *
		 * @return array
		 */
		public function get_current_feed() {
			return array();
		}

		/**
		 * Returns mapped fields for a field map setting.
		 *
		 * @param array  $feed       Feed.
		 * @param string $field_name Field map name.
		 * @return array
		 */
		public function get_field_map_fields( $feed, $field_name ) {
			return array();
		}
	}
}

if ( ! class_exists( 'GFFormsModel' ) ) {
	/**
	 * Stub for GFFormsModel.
	 */
	class GFFormsModel {
		/**
		 * Notes added during the test run.
		 *
		 * @var array
		 */
		public static $notes = array();

		/**
		 * Records a note instead of writing it.
		 *
		 * @param int    $entry_id  Entry ID.
		 * @param int    $user_id   User ID.
		 * @param string $user_name User name.
		 * @param string $note      Note.
		 * @param string $type      Note type.
		 * @param string $sub_type  Note sub type.
		 * @return void
		 */
		public static function add_note( $entry_id, $user_id, $user_name, $note, $type = '', $sub_type = null ) {
			self::$notes[] = compact( 'entry_id', 'note', 'sub_type' );
		}

		/**
		 * Returns the input type of a field.
		 *
		 * @param object $field Field.
		 * @return string
		 */
		public static function get_input_type( $field ) {
			return isset( $field->type ) ? $field->type : '';
		}
	}
}

if ( ! class_exists( 'RGFormsModel' ) ) {
	/**
	 * Stub for RGFormsModel (legacy alias).
	 */
	class RGFormsModel extends GFFormsModel {}
}

if ( ! function_exists( 'rgar' ) ) {
	/**
	 * Gets a value from an array by key.
	 *
	 * @param array  $arr     Array.
	 * @param string $name    Key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	function rgar( $arr, $name, $default = '' ) {
		return is_array( $arr ) && isset( $arr[ $name ] ) ? $arr[ $name ] : $default;
	}
}

if ( ! function_exists( 'gform_add_meta' ) ) {
	/**
	 * Adds entry meta (no-op in tests).
	 *
	 * @param int    $entry_id   Entry ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @param int    $form_id    Form ID.
	 * @return void
	 */
	function gform_add_meta( $entry_id, $meta_key, $meta_value, $form_id = null ) {}
}
