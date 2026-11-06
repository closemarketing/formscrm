<?php
/**
 * PHPStan stubs for the Gravity Forms 2.5+ Settings API.
 *
 * @package Formscrm
 */

namespace Gravity_Forms\Gravity_Forms\Settings;

/**
 * Settings fields registry.
 */
class Fields {
	/**
	 * Whether a field type is registered.
	 *
	 * @param string $type Field type.
	 * @return bool
	 */
	public static function exists( $type ) {}

	/**
	 * Register a field type.
	 *
	 * @param string $type       Field type.
	 * @param string $class_name Class name.
	 * @return bool|\WP_Error
	 */
	public static function register( $type, $class_name ) {}
}

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

/**
 * Field map setting.
 */
class Field_Map {
	/**
	 * Field type.
	 *
	 * @var string
	 */
	public $type = 'field_map';

	/**
	 * Field name.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Save callback.
	 *
	 * @var callable|null
	 */
	public $save_callback;

	/**
	 * Settings instance.
	 *
	 * @var object
	 */
	protected $settings;

	/**
	 * Value field properties.
	 *
	 * @var array
	 */
	protected $value_field = array();

	/**
	 * Constructor.
	 *
	 * @param array  $props    Field properties.
	 * @param object $settings Settings instance.
	 */
	public function __construct( $props, $settings ) {}

	/**
	 * Get field value.
	 *
	 * @return array
	 */
	public function get_value() {}

	/**
	 * Save field.
	 *
	 * @param array             $field_values Field values.
	 * @param array|bool|string $field_value  Field value.
	 * @return array
	 */
	public function save_field( $field_values, $field_value ) {}
}
