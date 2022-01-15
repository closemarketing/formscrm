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
		$lang_url = 'es' === substr( get_locale(), 0, 2 ) ? '' : 'en';
		?>
		<div class="wrap fs-section fs-full-size-wrapper">
			<h2 class="nav-tab-wrapper"><a href="#" class="nav-tab fs-tab nav-tab-active home"><?php esc_html_e( 'Information', 'formscrm' ); ?></a></h2>
			<p></p>
			<h3><strong><?php esc_html_e( 'Forms supported:', 'formscrm' ); ?></strong></h3>
			<ul>
				<li>GravityForms</li>
				<li>Contact Form 7</li>
			</ul>
			<h3><strong><?php esc_html_e( 'CRMs supported:', 'formscrm' ); ?></strong></h3>
			<ul>
				<li>Holded</li>
				<li>Clientify</li>
				<li>Odoo (Premium)</li>
				<li>vTiger (Premium) <a href="https://<?php echo esc_html( $lang_url ); ?>close.technology/wordpress-plugins/formscrm-vtiger/?utm_source=WordPress+Settings&utm_medium=plugin&utm_campaign=link" target="_blank"><?php esc_html_e( 'Buy', 'formscrm' ); ?></a></li>
				<li>Inmovilla (Premium) <a href="https://<?php echo esc_html( $lang_url ); ?>close.technology/wordpress-plugins/formscrm-inmovilla/?utm_source=WordPress+Settings&utm_medium=plugin&utm_campaign=link" target="_blank"><?php esc_html_e( 'Buy', 'formscrm' ); ?></a></li>
			</ul>
			<a class="button button-primary" href="/wp-admin/admin.php?page=formscrm-addons"><?php esc_html_e( 'View all addons', 'formscrm' ); ?></a>
		</div>
		<?php
	}

}
if ( is_admin() ) {
	$formscrm_admin = new FORMSCRM_Admin();
}
