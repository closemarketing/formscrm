<?php
/**
 * Stubs for Elementor Pro classes used in tests.
 *
 * Allows class-elementor.php to be loaded without Elementor Pro installed.
 *
 * @package Formscrm
 */

namespace ElementorPro\Modules\Forms\Classes;

if ( ! class_exists( 'ElementorPro\Modules\Forms\Classes\Action_Base' ) ) {
	/**
	 * Stub for Action_Base.
	 */
	abstract class Action_Base {
		/**
		 * Get name.
		 *
		 * @return string
		 */
		abstract public function get_name();

		/**
		 * Get label.
		 *
		 * @return string
		 */
		abstract public function get_label();

		/**
		 * Register settings section.
		 *
		 * @param mixed $widget Widget object.
		 */
		abstract public function register_settings_section( $widget );

		/**
		 * On export.
		 *
		 * @param mixed $element Element data.
		 */
		abstract public function on_export( $element );
	}
}
