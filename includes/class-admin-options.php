<?php
/**
 * Library for admin settings
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2019 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Library for WooCommerce Settings
 *
 * Settings in order to sync products
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2019 Closemarketing
 * @version    0.1
 */
class FORMSCRM_Admin {

	/**
	 * Construct of class
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
	}


	/**
	 * Adds plugin page.
	 *
	 * @return void
	 */
	public function add_plugin_page() {

		add_menu_page(
			__( 'FormsCRM', 'formscrm' ),
			__( 'FormsCRM', 'formscrm' ),
			'manage_options',
			'formscrm',
			array( $this, 'create_admin_page' ),
			'dashicons-table-col-after',
			99
		);
	}

	/**
	 * Create admin page.
	 *
	 * @return void
	 */
	public function create_admin_page() {
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Page information for FormsCRM', 'formscrm' ); ?></h2>
			<p></p>
			<p><strong><?php esc_html_e( 'Forms supported:', 'formscrm' ); ?></strong></p>
			<ul>
				<li><?php esc_html_e( 'GravityForms', 'formscrm' ); ?></li>
			</ul>
			<p><strong><?php esc_html_e( 'CRMs supported:', 'formscrm' ); ?></strong></p>
			<ul>
				<li>Holded</li>
				<li>Odoo (premium)</li>
			</ul>
		</div>
		<?php
	}

}
if ( is_admin() ) {
	$formscrm_admin = new FORMSCRM_Admin();
}
