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
		add_action( 'formscrm_settings', array( $this, 'settings_page' ) );
	}


	/**
	 * Adds plugin page.
	 *
	 * @return void
	 */
	public function add_plugin_page() {
		add_submenu_page(
			'options-general.php',
			__( 'FormsCRM', 'formscrm' ),
			__( 'FormsCRM', 'formscrm' ),
			'manage_options',
			'formscrm',
			array( $this, 'create_admin_page' ),
		);
	}

	/**
	 * Create admin page.
	 *
	 * @return void
	 */
	public function create_admin_page() {
		$lang_url = 'es' === substr( get_locale(), 0, 2 ) ? '' : 'en.';
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'FormsCRM', 'import-holded-products-woocommerce' ); ?></h2>
			<p></p>
			<?php
			settings_errors();
			$active_tab = isset( $_GET['tab'] ) ? strval( $_GET['tab'] ) : 'settings';

			$formscrm_tabs = apply_filters(
				'formscrm_settings_tabs',
				array(
					array(
						'tab'    => 'settings',
						'label'  => esc_html__( 'Settings', 'import-holded-products-woocommerce' ),
						'action' => 'formscrm_settings',
					),
				)
			);
			echo '<h2 class="nav-tab-wrapper">';
			foreach ( $formscrm_tabs as $tab ) {
				echo '<a href="?page=formscrm&tab=' . esc_html( $tab['tab'] ) . '" class="nav-tab ';
				echo $tab['tab'] === $active_tab ? 'nav-tab-active' : '';
				echo '">' . esc_html( $tab['label'] ) . '</a>';
			}
			echo '</h2>';
			foreach ( $formscrm_tabs as $tab ) {
				if ( $tab['tab'] === $active_tab ) {
					do_action( $tab['action'] );
				}
			}
			?>
		</div>
		<?php
	}

	public function settings_page() {
		$source_shop_url = 'es' === strtok( get_locale(), '_' ) ? 'https://close.technology/' : 'https://en.close.technology/';
		$utm_source      = '?utm_source=WordPress+Settings&utm_medium=plugin&utm_campaign=link';
		?>
		<h3><strong><?php esc_html_e( 'Forms supported:', 'formscrm' ); ?></strong></h3>
		<ul>
			<li>GravityForms</li>
			<li>Contact Form 7</li>
			<li>WooCommerce</li>
		</ul>
		<h3><strong><?php esc_html_e( 'CRMs supported:', 'formscrm' ); ?></strong></h3>
		<ul>
			<li>Holded</li>
			<li>Clientify</li>
			<li>AcumbaMail</li>
			<li>Odoo (Premium)</li>
			<li>vTiger (Premium) <a href="<?php echo esc_url( $source_shop_url ); ?>wordpress-plugins/formscrm-vtiger/<?php echo esc_attr( $utm_source ); ?>" target="_blank"><?php esc_html_e( 'Buy', 'formscrm' ); ?></a></li>
			<li>Inmovilla (Premium) <a href="<?php echo esc_url( $source_shop_url ); ?>wordpress-plugins/formscrm-inmovilla/<?php echo esc_attr( $utm_source ); ?>" target="_blank"><?php esc_html_e( 'Buy', 'formscrm' ); ?></a></li>
			<li>PipeDrive (Premium) <a href="<?php echo esc_url( $source_shop_url ); ?>wordpress-plugins/formscrm-pipedrive/<?php echo esc_attr( $utm_source ); ?>" target="_blank"><?php esc_html_e( 'Buy', 'formscrm' ); ?></a></li>
			<li>SuiteCRM (Premium) <a href="<?php echo esc_url( $source_shop_url ); ?>wordpress-plugins/formscrm-suitecrm/<?php echo esc_attr( $utm_source ); ?>" target="_blank"><?php esc_html_e( 'Buy', 'formscrm' ); ?></a></li>
			<li>FacturaDirecta (Premium) <a href="<?php echo esc_url( $source_shop_url ); ?>wordpress-plugins/formscrm-facturadirecta/<?php echo esc_attr( $utm_source ); ?>" target="_blank"><?php esc_html_e( 'Buy', 'formscrm' ); ?></a></li>
		</ul>
		<br/>
		<a class="button button-primary" href="<?php echo esc_url( $source_shop_url ); ?>formscrm/<?php echo esc_attr( $utm_source ); ?>" target="_blank"><?php esc_html_e( 'View all addons', 'formscrm' ); ?></a>
		<a class="button button-secondary" href="https://wordpress.org/support/plugin/formscrm/"><?php esc_html_e( 'Get Support', 'formscrm' ); ?></a>
		<?php
	}

}
if ( is_admin() ) {
	$formscrm_admin = new FORMSCRM_Admin();
}
