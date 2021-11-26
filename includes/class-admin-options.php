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
		<div class="wrap fs-section fs-full-size-wrapper">
			<h2 class="nav-tab-wrapper"><a href="#" class="nav-tab fs-tab nav-tab-active home"><?php esc_html_e( 'Information', 'formscrm' ); ?></a></h2>
			<p></p>
			<h3><strong><?php esc_html_e( 'Forms supported:', 'formscrm' ); ?></strong></h3>
			<ul>
				<li><?php esc_html_e( 'GravityForms', 'formscrm' ); ?></li>
			</ul>
			<h3><strong><?php esc_html_e( 'CRMs supported:', 'formscrm' ); ?></strong></h3>
			<ul>
				<li>Holded</li>
				<li>Odoo (Premium)</li>
				<li>vTiger (Premium) <a href="https://checkout.freemius.com/mode/dialog/plugin/8767/plan/14673/licenses/1/currency/eur/" target="_blank"><?php esc_html_e( 'Buy', 'formscrm' ); ?></a></li>
				<li>Clientify (Premium) <a href="https://checkout.freemius.com/mode/dialog/plugin/9345/plan/15716/licenses/1/currency/eur/" target="_blank"><?php esc_html_e( 'Buy', 'formscrm' ); ?></a></li>
			</ul>
			<a class="button button-primary" href="/wp-admin/admin.php?page=formscrm-addons"><?php esc_html_e( 'View all addons', 'formscrm' ); ?></a>
		</div>
		<?php
	}

}
if ( is_admin() ) {
	$formscrm_admin = new FORMSCRM_Admin();
}
