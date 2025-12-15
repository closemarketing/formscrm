<?php
/**
 * CRM Library Interface
 *
 * Defines the contract that all CRM library classes must implement.
 * This interface ensures consistent API across different CRM integrations.
 *
 * @package FormsCRM
 * @since 4.0.6
 */

defined( 'ABSPATH' ) || exit;

/**
 * Interface CRMLIB_Interface
 *
 * Standard interface for all CRM library implementations.
 */
interface CRMLIB_Interface {

	/**
	 * Authenticate with the CRM service.
	 *
	 * @param array<string, mixed> $settings Configuration settings including credentials.
	 * @return bool True if login successful, false otherwise.
	 */
	public function login( array $settings ): bool;

	/**
	 * Get list of available modules/entities in the CRM.
	 *
	 * @param array<string, mixed> $settings Configuration settings.
	 * @return array<string, string> Array of module names with labels.
	 */
	public function list_modules( array $settings ): array;

	/**
	 * Get list of fields for a specific module.
	 *
	 * @param array<string, mixed> $settings Configuration settings including module name.
	 * @return array<string, array<string, mixed>> Array of fields with their properties.
	 */
	public function list_fields( array $settings ): array;

	/**
	 * Create a new entry in the CRM.
	 *
	 * @param array<string, mixed> $merge_vars Data to be sent to CRM.
	 * @param array<string, mixed> $settings Configuration settings.
	 * @return array<string, mixed> Response from CRM API.
	 */
	public function create_entry( array $merge_vars, array $settings ): array;
}
