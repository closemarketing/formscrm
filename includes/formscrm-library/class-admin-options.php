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

if ( ! class_exists( 'FORMSCRM_Admin' ) ) {
	class FORMSCRM_Admin {

		/**
		 * Construct of class
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

			add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
			add_action( 'formscrm_settings', array( $this, 'settings_page' ) );
		}

		/**
		 * Enqueue Scripts and styles
		 *
		 * @return void
		 */
		public function enqueue_admin_scripts() {
			wp_enqueue_style(
				'formscrm-admin',
				FORMSCRM_PLUGIN_URL . 'includes/assets/admin.css',
				array(),
				FORMSCRM_VERSION
			);
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
			<ul class="formscrm-list-crm">
				<?php
				$crms_supported = array(
					array( 'label' => 'Holded' ),
					array( 'label' => 'Clientify' ),
					array( 'label' => 'AcumbaMail' ),
					array( 'slug' => 'odoo', 'label' => 'Odoo (Premium)', 'url' => true, 'img' => true ),
					array( 'slug' => 'vtiger', 'label' => 'vTiger (Premium)', 'url' => true, 'img' => true ),
					array( 'slug' => 'inmovilla', 'label' => 'Inmovilla (Premium)', 'url' => true, 'img' => true ),
					array( 'slug' => 'pipedrive', 'label' => 'Pipedrive (Premium)', 'url' => true, 'img' => true ),
					array( 'slug' => 'suitecrm', 'label' => 'SuiteCRM (Premium)', 'url' => true, 'img' => true ),
					array( 'slug' => 'facturadirecta', 'label' => 'FacturaDirecta (Premium)', 'url' => true, 'img' => true ),
				);

				foreach ( $crms_supported as $crm ) {
					echo '<li>';
					if ( isset( $crm['url'] ) && $crm['url'] ) {
						$url = esc_url( $source_shop_url ) . 'wordpress-plugins/formscrm-' . $crm['slug'] . '/' . esc_attr( $utm_source );
						echo ' <a href="' . $url . '" target="_blank">';
					}
					if ( isset( $crm['img'] ) && $crm['img'] ) {
						echo '<img src="' . FORMSCRM_PLUGIN_URL . 'includes/assets/formscrm-' . $crm['slug'] . '.svg" width="250" /><br/>';
					}
					echo $crm['label'];
					if ( isset( $crm['url'] ) && $crm['url'] ) {
						echo '</a> <a class="button button-secondary" href="' . $url . '" target="_blank">' .esc_html__( 'Buy', 'formscrm' ) . '</a>';
					}
					echo '</li>';
				}
				?>
			</ul>
			<br/>
			<a class="button button-primary" href="<?php echo esc_url( $source_shop_url ); ?>formscrm/<?php echo esc_attr( $utm_source ); ?>" target="_blank"><?php esc_html_e( 'View all addons', 'formscrm' ); ?></a>
			<a class="button button-secondary" href="https://wordpress.org/support/plugin/formscrm/" target="_blank"><?php esc_html_e( 'Get Support', 'formscrm' ); ?></a>
			<?php
		}
	}
}
if ( is_admin() ) {
	$formscrm_admin = new FORMSCRM_Admin();
}
