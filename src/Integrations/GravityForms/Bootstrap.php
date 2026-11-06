<?php
/**
 * Gravity Forms bootstrap.
 *
 * @package    FormsCRM
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2020 Closemarketing
 */

namespace CLOSE\FormsCRM\Integrations\GravityForms;

defined( 'ABSPATH' ) || exit;

use GFAddOn;
use GFForms;

/**
 * Registers the Gravity Forms feed add-on and its companion screens.
 */
class Bootstrap {

	/**
	 * Hooks the integration into Gravity Forms.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'gform_loaded', array( __CLASS__, 'load' ), 5 );

		new Widget();
		new MarkdownExport();
	}

	/**
	 * Loads the Gravity Forms Feed Add-On.
	 *
	 * @return void
	 */
	public static function load() {
		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		GFForms::include_feed_addon_framework();

		GFAddOn::register( AddOn::class );
	}
}
