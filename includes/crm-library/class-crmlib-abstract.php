<?php
/**
 * Abstract CRM Library base class
 *
 * @package FormsCRM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abstract base for all CRM library classes.
 */
abstract class CRMLIB_Abstract {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Legacy class name.

	/**
	 * Validate credentials.
	 *
	 * @param array $settings CRM settings.
	 * @return bool
	 */
	abstract public function login( array $settings ): array;

	/**
	 * Return available modules/lists.
	 *
	 * @param array $settings CRM settings.
	 * @return array
	 */
	abstract public function list_modules( array $settings ): array;

	/**
	 * Return fields for a given module.
	 *
	 * @param array  $settings CRM settings.
	 * @param string $module   Module slug.
	 * @return array
	 */
	abstract public function list_fields( $settings, $module );

	/**
	 * Push form data to CRM.
	 *
	 * @param array  $settings CRM settings.
	 * @param string $module   Module slug.
	 * @param array  $data     Form data.
	 * @return array
	 */
	abstract public function create_entry( $settings, $merge_vars );

	/**
	 * List fields for search entry for given module of a CRM
	 *
	 * @param  string $module Module to get fields from.
	 * @return array Array of mudules
	 */
	abstract public function list_fields_search_entry( ?string $module = null );


	/**
	 * Check if an entry exists and create or update it.
	 *
	 * @param array  $data   Raw merge vars from form.
	 * @param string $module CRM module slug (contacts, companies etc).
	 * @return array
	 */
	abstract public function create_or_update_entry( array $data, string $module ): array;

	/**
	 * Map a search field ID to the API query param name.
	 *
	 * @param string $search_field Field ID from list_fields_search_entry.
	 * @return string Query param name to use in the API request.
	 */
	abstract public function determine_search_by( string $search_field ): string;
	
}
