<?php
/**
 * Gravity Forms field map setting with custom (fixed) value support.
 *
 * @package    WordPress
 * @author     David Perez <david@close.technology>
 * @copyright  2026 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

use Gravity_Forms\Gravity_Forms\Settings\Fields\Field_Map;

/**
 * Field map that lets admins send a fixed value instead of a form field.
 *
 * Keeps the legacy "{name}_{key}" storage of Gravity Forms' field_map so
 * existing feeds keep working. When "Add Custom Value" is chosen, the mapping
 * stores "gf_custom" and the text is saved in "{name}Custom_{key}".
 */
class FormsCRM_GF_Field_Map extends Field_Map {

	/**
	 * Settings field type used in feed settings.
	 */
	const TYPE = 'formscrm_field_map';

	/**
	 * Value stored when a custom value is selected.
	 */
	const CUSTOM_VALUE = 'gf_custom';

	/**
	 * Initialize field and enable custom values.
	 *
	 * @param array                                          $props    Field properties.
	 * @param \Gravity_Forms\Gravity_Forms\Settings\Settings $settings Settings instance.
	 */
	public function __construct( $props, $settings ) {
		parent::__construct( $props, $settings );

		// Keep the native field_map UI (fixed rows) and allow custom values.
		$this->type                        = 'field_map';
		$this->value_field['allow_custom'] = true;
	}

	/**
	 * Adds saved custom values to the mapping.
	 *
	 * @return array
	 */
	public function get_value() {
		$value = parent::get_value();

		if ( ! is_array( $value ) ) {
			return $value;
		}

		foreach ( $value as $index => $mapping ) {
			if ( ! is_array( $mapping ) || self::CUSTOM_VALUE !== rgar( $mapping, 'value' ) || '' !== (string) rgar( $mapping, 'custom_value' ) ) {
				continue;
			}

			$value[ $index ]['custom_value'] = (string) $this->settings->get_value( formscrm_gf_custom_value_meta_key( $this->name, $mapping['key'] ) );
		}

		return $value;
	}

	/**
	 * Saves mapped fields and their custom values using legacy keys.
	 *
	 * @param array             $field_values Posted settings values.
	 * @param array|bool|string $field_value  Field map value.
	 * @return array
	 */
	public function save_field( $field_values, $field_value ) {
		$field_values = parent::save_field( $field_values, $field_value );

		if ( is_callable( $this->save_callback ) ) {
			return $field_values;
		}

		$mappings = GFCommon::maybe_decode_json( $field_value );

		if ( ! is_array( $mappings ) ) {
			return $field_values;
		}

		foreach ( $mappings as $mapping ) {
			if ( ! is_array( $mapping ) || empty( $mapping['key'] ) ) {
				continue;
			}

			$meta_key = formscrm_gf_custom_value_meta_key( $this->name, $mapping['key'] );

			if ( self::CUSTOM_VALUE === rgar( $mapping, 'value' ) ) {
				$field_values[ $meta_key ] = sanitize_text_field( (string) rgar( $mapping, 'custom_value' ) );
			} else {
				unset( $field_values[ $meta_key ] );
			}
		}

		return $field_values;
	}
}
