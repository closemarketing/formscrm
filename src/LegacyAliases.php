<?php
/**
 * Backwards compatibility for the pre-namespace class names.
 *
 * Add-ons and custom code still reference the old global class names
 * (CRMLIB_HOLDED, GFCRM, FORMSCRM_Error_Log…). Each old name is aliased to its
 * namespaced class on demand, the first time it is used.
 *
 * @package    FormsCRM
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2026 Closemarketing
 */

namespace CLOSE\FormsCRM;

defined( 'ABSPATH' ) || exit;

/**
 * Lazily maps the legacy class names to the namespaced ones.
 */
final class LegacyAliases {

	/**
	 * Legacy class name (lowercase) => array( new class, required parent class or '' ).
	 *
	 * The parent is checked before aliasing so that `class_exists( 'GFCRM' )`
	 * keeps returning false (instead of a fatal error) when the form plugin
	 * the class extends is not loaded.
	 *
	 * @var array<string,array{0:string,1:string}>
	 */
	const MAP = array(
		'crmlib_abstract'                        => array( CRM\AbstractCRM::class, '' ),
		'crmlib_acumbamail'                      => array( CRM\AcumbaMail::class, '' ),
		'crmlib_brevo'                           => array( CRM\Brevo::class, '' ),
		'crmlib_clientify'                       => array( CRM\Clientify::class, '' ),
		'crmlib_holded'                          => array( CRM\Holded::class, '' ),
		'crmlib_mailerlite'                      => array( CRM\Mailerlite::class, '' ),
		'formscrm_admin'                         => array( Admin\AdminOptions::class, '' ),
		'formscrm_error_log'                     => array( Admin\ErrorLog::class, '' ),
		'formscrm_error_log_page'                => array( Admin\ErrorLogPage::class, '' ),
		'formscrm_review_notice'                 => array( Admin\ReviewNotice::class, '' ),
		'formscrm_bootstrap'                     => array( Integrations\GravityForms\Bootstrap::class, '' ),
		'gfcrm'                                  => array( Integrations\GravityForms\AddOn::class, 'GFFeedAddOn' ),
		'formscrm_gravityforms_widget'           => array( Integrations\GravityForms\Widget::class, '' ),
		'formscrm_gravityforms_markdown_export'  => array( Integrations\GravityForms\MarkdownExport::class, '' ),
		'formscrm_cf7_settings'                  => array( Integrations\ContactForm7\Settings::class, '' ),
		'formscrm_woocommerce'                   => array( Integrations\WooCommerce\WooCommerce::class, '' ),
		'formscrm_jfb_action'                    => array( Integrations\JetFormBuilder\Action::class, 'Jet_Form_Builder\Actions\Types\Base' ),
		'formscrm_jfb_tab_handler'               => array( Integrations\JetFormBuilder\TabHandler::class, 'Jet_Form_Builder\Admin\Tabs_Handlers\Base_Handler' ),
		'formscrm_wpforms'                       => array( Integrations\WPForms\Provider::class, 'WPForms_Provider' ),
		'formscrm_elementor_action_after_submit' => array( Integrations\Elementor\ActionAfterSubmit::class, 'ElementorPro\Modules\Forms\Classes\Action_Base' ),
	);

	/**
	 * Whether the autoloader is registered.
	 *
	 * @var bool
	 */
	private static $registered = false;

	/**
	 * Registers the legacy autoloader.
	 *
	 * @return void
	 */
	public static function register() {
		if ( self::$registered ) {
			return;
		}
		self::$registered = true;
		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * Aliases a legacy class name to its namespaced class.
	 *
	 * @param string $legacy_class Requested class name.
	 * @return void
	 */
	public static function load( $legacy_class ) {
		$key = strtolower( ltrim( $legacy_class, '\\' ) );
		if ( ! isset( self::MAP[ $key ] ) ) {
			return;
		}

		list( $target, $parent ) = self::MAP[ $key ];
		if ( '' !== $parent && ! class_exists( $parent ) ) {
			return;
		}

		class_alias( $target, $legacy_class );
	}
}
