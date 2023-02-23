<?php
/**
 * The WooCommerce API Manager PHP Client Library is designed to be droppped into a WordPress plugin or theme.
 * This version is designed to be used with the WooCommerce API Manager version 2.x.
 *
 * Intellectual Property rights, and copyright, reserved by Todd Lahman, LLC as allowed by law include,
 * but are not limited to, the working concept, function, and behavior of this software,
 * the logical code structure and expression as written.
 *
 * @version       2.8.1
 * @author        Todd Lahman LLC https://www.toddlahman.com/
 * @copyright     Copyright (c) Todd Lahman LLC (support@toddlahman.com)
 * @package       WooCommerce API Manager plugin and theme library
 * @license       Copyright Todd Lahman LLC
 */

defined( 'ABSPATH' ) || exit;

class FormsCRM_Updater {

	/**
	 * Class args
	 *
	 * @var string
	 */
	private $api_url          = '';
	private $data_key         = '';
	private $file             = '';
	private $plugin_name      = '';
	private $plugin_or_theme  = '';
	private $product_id       = '';
	private $slug             = '';
	private $software_title   = '';
	private $software_version = '';
	private $text_domain      = ''; // For language translation.
	private $domain_slug      = '';

	/**
	 * Class properties.
	 *
	 * @var string
	 */
	private $data                              = array();
	private $identifier                        = '';
	private $no_product_id                     = false;
	private $product_id_chosen                 = 0;
	private $wc_am_activated_key               = '';
	private $wc_am_activation_tab_key          = '';
	private $wc_am_api_key_key                 = '';
	private $wc_am_deactivate_checkbox_key     = '';
	private $wc_am_deactivation_tab_key        = '';
	private $wc_am_auto_update_key             = '';
	private $wc_am_domain                      = '';
	private $wc_am_instance_id                 = '';
	private $wc_am_instance_key                = '';
	private $wc_am_menu_tab_activation_title   = '';
	private $wc_am_menu_tab_deactivation_title = '';
	private $wc_am_plugin_name                 = '';
	private $wc_am_product_id                  = '';
	private $wc_am_renew_license_url           = '';
	private $wc_am_settings_menu_title         = '';
	private $wc_am_settings_title              = '';
	private $wc_am_software_version            = '';

	public function __construct( $file, $product_id, $software_version, $plugin_or_theme, $api_url, $software_title = '', $text_domain = '' ) {
		$this->no_product_id   = empty( $product_id );
		$this->plugin_or_theme = esc_attr( strtolower( $plugin_or_theme ) );

		if ( $this->no_product_id ) {
			$this->identifier        = $this->plugin_or_theme == 'plugin' ? dirname( untrailingslashit( plugin_basename( $file ) ) ) : basename( dirname( plugin_basename( $file ) ) );
			$product_id              = strtolower( str_ireplace( array( ' ', '_', '&', '?', '-' ), '_', $this->identifier ) );
			$this->wc_am_product_id  = 'wc_am_product_id_' . $product_id;
			$this->product_id_chosen = get_option( $this->wc_am_product_id );
		} else {
			/**
			 * Preserve the value of $product_id to use for API requests. Pre 2.0 product_id is a string, and >= 2.0 is an integer.
			 */
			if ( is_int( $product_id ) ) {
				$this->product_id = absint( $product_id );
			} else {
				$this->product_id = esc_attr( $product_id );
			}
		}

		// If the product_id was not provided, but was saved by the customer, used the saved product_id.
		if ( empty( $this->product_id ) && ! empty( $this->product_id_chosen ) ) {
			$this->product_id = $this->product_id_chosen;
		}

		$this->file             = $file;
		$this->software_title   = esc_attr( $software_title );
		$this->software_version = esc_attr( $software_version );
		$this->api_url          = esc_url( $api_url );
		$this->domain_slug      = esc_attr( str_replace( '-', '_', $text_domain ) );
		/**
		 * If the product_id is a pre 2.0 string, format it to be used as an option key, otherwise it will be an integer if >= 2.0.
		 */
		$this->data_key            = 'wc_am_client_' . strtolower( str_ireplace( array( ' ', '_', '&', '?', '-' ), '_', $product_id ) );
		$this->wc_am_activated_key = $this->data_key . '_activated';

		if ( is_admin() ) {
			if ( ! empty( $this->plugin_or_theme ) && $this->plugin_or_theme == 'theme' ) {
				add_action( 'admin_init', array( $this, 'activation' ) );
			}

			if ( ! empty( $this->plugin_or_theme ) && $this->plugin_or_theme == 'plugin' ) {
				register_activation_hook( $this->file, array( $this, 'activation' ) );
			}

			add_action( 'admin_init', array( $this, 'load_settings' ) );
			add_action( $this->domain_slug . '_options_license', array( $this, 'settings_license' ) );
			add_action( $this->domain_slug . '_options_license_deactivate', array( $this, 'settings_license_deactivate' ) );
			// Check for external connection blocking
			add_action( 'admin_notices', array( $this, 'check_external_blocking' ) );

			/**
			 * Set all data defaults here
			 */
			$this->wc_am_api_key_key  = $this->data_key . '_api_key';
			$this->wc_am_instance_key = $this->data_key . '_instance';

			/**
			 * Set all admin menu data
			 */
			$this->wc_am_deactivate_checkbox_key     = $this->data_key . '_deactivate_checkbox';
			$this->wc_am_activation_tab_key          = $this->data_key . '_dashboard';
			$this->wc_am_deactivation_tab_key        = $this->data_key . '_deactivation';
			$this->wc_am_auto_update_key             = $this->data_key . '_auto_update';
			$this->wc_am_settings_menu_title         = $this->software_title . esc_html__( ' Activation', 'formscrm' );
			$this->wc_am_settings_title              = $this->software_title . esc_html__( ' License Activation', 'formscrm' );
			$this->wc_am_menu_tab_activation_title   = esc_html__( 'License Activation', 'formscrm' );
			$this->wc_am_menu_tab_deactivation_title = esc_html__( 'License Deactivation', 'formscrm' );

			/**
			 * Set all software update data here
			 */
			$this->data                    = get_option( $this->data_key );
			$this->wc_am_plugin_name       = $this->plugin_or_theme == 'plugin' ? untrailingslashit( plugin_basename( $this->file ) ) : basename( dirname( plugin_basename( $file ) ) ); // same as plugin slug. if a theme use a theme name like 'twentyeleven'
			$this->wc_am_renew_license_url = $this->api_url . 'my-account'; // URL to renew an License. Trailing slash in the upgrade_url is required.
			$this->wc_am_instance_id       = get_option( $this->wc_am_instance_key ); // Instance ID (unique to each blog activation)
			/**
			 * Some web hosts have security policies that block the : (colon) and // (slashes) in http://,
			 * so only the host portion of the URL can be sent. For example the host portion might be
			 * www.example.com or example.com. http://www.example.com includes the scheme http,
			 * and the host www.example.com.
			 * Sending only the host also eliminates issues when a client site changes from http to https,
			 * but their activation still uses the original scheme.
			 * To send only the host, use a line like the one below:
			 *
			 * $this->wc_am_domain = str_ireplace( array( 'http://', 'https://' ), '', home_url() ); // blog domain name
			 */
			$this->wc_am_domain           = str_ireplace( array( 'http://', 'https://' ), '', home_url() ); // blog domain name
			$this->wc_am_software_version = $this->software_version; // The software version

			/**
			 * Check for software updates
			 */
			$this->check_for_update();

			if ( ! empty( $this->wc_am_activated_key ) && get_option( $this->wc_am_activated_key ) != 'Activated' ) {
				add_action( 'admin_notices', array( $this, 'inactive_notice' ) );
			}

			/**
			 * Makes auto updates available if WP >= 5.5.
			 *
			 * @since 2.8
			 */
			$this->try_automatic_updates();

			if ( $this->plugin_or_theme == 'plugin' ) {
				//add_action( 'wp_ajax_update_auto_update_setting', array( $this, 'update_auto_update_setting' ) );
				add_filter( 'plugin_auto_update_setting_html', array( $this, 'auto_update_message' ), 10, 3 );
			}
		}
	}

	/**
	 *  Tries auto updates.
	 *
	 * @since 2.8
	 */
	public function try_automatic_updates() {
		global $wp_version;

		if ( version_compare( $wp_version, '5.5', '>=' ) ) {
			//if ( empty( get_option( $this->wc_am_auto_update_key ) ) ) {
			//	update_option( $this->wc_am_auto_update_key, 'on' );
			//}

			if ( $this->plugin_or_theme == 'plugin' ) {
				add_filter( 'auto_update_plugin', array( $this, 'maybe_auto_update' ), 10, 2 );
			} elseif ( $this->plugin_or_theme == 'theme' ) {
				add_filter( 'auto_update_theme', array( $this, 'maybe_auto_update' ), 10, 2 );
			}
		}
	}

	/**
	 * Tries to set auto updates.
	 *
	 * @since 2.8
	 *
	 * @param bool|null $update
	 * @param object    $item
	 *
	 * @return bool
	 */
	public function maybe_auto_update( $update, $item ) {
		if ( strpos( $this->wc_am_plugin_name, '.php' ) !== 0 ) {
			$slug = dirname( $this->wc_am_plugin_name );
		} else {
			$slug = $this->wc_am_plugin_name;
		}

		if ( isset( $item->slug ) && $item->slug == $slug ) {
			if ( $this->is_auto_update_disabled() ) {
				return false;
			}

			if ( ! $this->get_api_key_status() || ! $this->get_api_key_status( true ) ) {
				return false;
			}

			return true;
		}

		return $update;
	}

	/**
	 * Checks if auto updates are disabled.
	 *
	 * @since 2.8
	 *
	 * @return bool
	 */
	public function is_auto_update_disabled() {
		/*
			* WordPress will not offer to update if background updates are disabled.
			* WordPress background updates are disabled if file changes are not allowed.
			*/
		if ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ) {
			return true;
		}

		if ( defined( 'WP_INSTALLING' ) ) {
			return true;
		}

		$wp_updates_disabled = defined( 'AUTOMATIC_UPDATER_DISABLED' ) && AUTOMATIC_UPDATER_DISABLED;

		/**
		 * Overrides the WordPress AUTOMATIC_UPDATER_DISABLED constant.
		 *
		 * @param bool $wp_updates_disabled true if disables.  false otherwise.
		 */
		$wp_updates_disabled = apply_filters( 'automatic_updater_disabled', $wp_updates_disabled );

		if ( $wp_updates_disabled ) {
			return true;
		}

		// Return true if this plugin or theme background update is disabled.
		// return get_option( $this->wc_am_auto_update_key ) !== 'on';

		return false;
	}

	/**
	 * Filter the auto-update message on the plugins page.
	 *
	 * Plugin updates stored in 'auto_update_plugins' array.
	 *
	 * @see   'wp-admin/includes/class-wp-plugins-list-table.php'
	 *
	 * @since 2.8
	 *
	 * @param string $html        HTML of the auto-update message.
	 * @param string $plugin_file Plugin file.
	 * @param array  $plugin_data Plugin details.
	 *
	 * @return mixed|string
	 */
	public function auto_update_message( $html, $plugin_file, $plugin_data ) {
		if ( $this->wc_am_plugin_name == $plugin_file ) {
			global $status, $page;

			// if ( ! $this->get_api_key_status( true ) || get_option( $this->wc_am_auto_update_key ) !== 'on' ) {
			if ( ! $this->get_api_key_status() || ! $this->get_api_key_status( true ) ) {
				return esc_html__( 'Auto-updates unavailable.', 'formscrm' );
			}

			$auto_updates = (array) get_site_option( 'auto_update_plugins', array() );
			$html         = array();

			if ( ! empty( $plugin_data[ 'auto-update-forced' ] ) ) {
				if ( $plugin_data[ 'auto-update-forced' ] ) {
					// Forced on.
					$text = __( 'Auto-updates enabled' );
				} else {
					$text = __( 'Auto-updates disabled' );
				}

				$action     = 'unavailable';
				$time_class = ' hidden';
			} elseif ( in_array( $plugin_file, $auto_updates, true ) ) {
				$text       = __( 'Disable auto-updates' );
				$action     = 'disable';
				$time_class = '';
			} else {
				$text       = __( 'Enable auto-updates' );
				$action     = 'enable';
				$time_class = ' hidden';
			}

			$query_args = array(
				'action'        => "{$action}-auto-update",
				'plugin'        => $plugin_file,
				'paged'         => $page,
				'plugin_status' => $status,
			);

			$url = add_query_arg( $query_args, 'plugins.php' );

			if ( 'unavailable' === $action ) {
				$html[] = '<span class="label">' . $text . '</span>';
			} else {
				$html[] = sprintf( '<a href="%s" class="toggle-auto-update aria-button-if-js" data-wp-action="%s">', wp_nonce_url( $url, 'updates' ), $action );

				$html[] = '<span class="dashicons dashicons-update spin hidden" aria-hidden="true"></span>';
				$html[] = '<span class="label">' . $text . '</span>';
				$html[] = '</a>';
			}

			if ( ! empty( $plugin_data[ 'update' ] ) ) {
				$html[] = sprintf( '<div class="auto-update-time%s">%s</div>', $time_class, wp_get_auto_update_message() );
			}

			$html = implode( '', $html );
		}

		return $html;
	}

	/**
	 * Generate the default data.
	 */
	public function activation() {
		$instance_exists = get_option( $this->wc_am_instance_key );

		if ( get_option( $this->data_key ) === false || $instance_exists === false ) {
			if ( $instance_exists === false ) {
				update_option( $this->wc_am_instance_key, wp_generate_password( 12, false ) );
			}

			update_option( $this->wc_am_deactivate_checkbox_key, 'on' );
			update_option( $this->wc_am_activated_key, 'Deactivated' );
		}
	}

	/**
	 * Deletes all data if plugin deactivated
	 */
	public function uninstall() {
		/**
		 * @since 2.5.1
		 *
		 * Filter wc_am_client_uninstall_disable
		 * If set to false uninstall() method will be disabled.
		 */
		if ( apply_filters( 'wc_am_client_uninstall_disable', true ) ) {
			global $blog_id;

			$this->license_key_deactivation();

			// Remove options pre API Manager 2.0
			if ( is_multisite() ) {
				switch_to_blog( $blog_id );

				foreach (
					array(
						$this->wc_am_instance_key,
						$this->wc_am_deactivate_checkbox_key,
						$this->wc_am_activated_key,
					) as $option
				) {

					delete_option( $option );
				}

				restore_current_blog();
			} else {
				foreach (
					array(
						$this->wc_am_instance_key,
						$this->wc_am_deactivate_checkbox_key,
						$this->wc_am_activated_key
					) as $option
				) {

					delete_option( $option );
				}
			}
		}
	}

	/**
	 * Deactivates the license on the API server
	 */
	public function license_key_deactivation() {
		$activation_status = get_option( $this->wc_am_activated_key );
		$api_key           = ! empty( $this->data[ $this->wc_am_api_key_key ] ) ? $this->data[ $this->wc_am_api_key_key ] : '';

		$args = array(
			'api_key' => $api_key,
		);

		if ( ! empty( $api_key ) && $activation_status == 'Activated' ) {
			if ( empty( $this->deactivate( $args ) ) ) {
				add_settings_error( 'not_deactivated_text', 'not_deactivated_error', esc_html__( 'The License could not be deactivated. Use the License Deactivation tab to manually deactivate the License before activating a new License. If all else fails, go to Plugins, then deactivate and reactivate this plugin, or if a theme change themes, then change back to this theme, then go to the Settings for this plugin/theme and enter the License information again to activate it. Also check the My Account dashboard to see if the License for this site was still active before the error message was displayed.', 'formscrm' ), 'updated' );
			}
		}
	}

	/**
	 * Displays an inactive notice when the software is inactive.
	 */
	public function inactive_notice() { ?>
		<?php
		/**
		 * @since 2.5.1
		 *
		 * Filter wc_am_client_inactive_notice_override
		 * If set to false inactive_notice() method will be disabled.
		 */ ?>
		<?php if ( apply_filters( 'wc_am_client_inactive_notice_override', true ) ) { ?>
			<?php if ( ! current_user_can( 'manage_options' ) ) {
				return;
			} ?>
			<?php if ( isset( $_GET[ 'page' ] ) && $this->wc_am_activation_tab_key == $_GET[ 'page' ] ) {
				return;
			} ?>
					<div class="notice notice-error">
						<p><?php printf( __( 'The <strong>%s</strong> License has not been activated, so the %s is inactive! %sClick here%s to activate <strong>%s</strong>.', 'formscrm' ), esc_attr( $this->software_title ), esc_attr( $this->plugin_or_theme ), '<a href="' . esc_url( admin_url( 'admin.php?page=formscrm&tab=' . $this->domain_slug . '_license' ) ) . '">', '</a>', esc_attr( $this->software_title ) ); ?></p>
					</div>
		<?php }
	}

	/**
	 * Check for external blocking contstant.
	 */
	public function check_external_blocking() {
		// show notice if external requests are blocked through the WP_HTTP_BLOCK_EXTERNAL constant
		if ( defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && WP_HTTP_BLOCK_EXTERNAL === true ) {
			// check if our API endpoint is in the allowed hosts
			$host = parse_url( $this->api_url, PHP_URL_HOST );

			if ( ! defined( 'WP_ACCESSIBLE_HOSTS' ) || stristr( WP_ACCESSIBLE_HOSTS, $host ) === false ) {
				?>
						<div class="notice notice-error">
							<p><?php printf( __( '<b>Warning!</b> You\'re blocking external requests which means you won\'t be able to get %s updates. Please add %s to %s.', 'formscrm' ), $this->software_title, '<strong>' . $host . '</strong>', '<code>WP_ACCESSIBLE_HOSTS</code>' ); ?></p>
						</div>
				<?php
			}
		}
	}

	public function settings_license() {
		?>
		<form action='options.php' method='post'>
			<div class="main">
				<?php
				settings_fields( $this->data_key );
				do_settings_sections( $this->wc_am_activation_tab_key );
				submit_button( esc_html__( 'Save Changes', 'formscrm' ) );
				?>
			</div>
		</form>
		<?php
	}

	public function settings_license_deactivate() {
		?>
		<form action='options.php' method='post'>
			<div class="main">
				<?php
					settings_fields( $this->wc_am_deactivate_checkbox_key );
					do_settings_sections( $this->wc_am_deactivation_tab_key );
					submit_button( esc_html__( 'Save Changes', 'formscrm' ) );
				?>
			</div>
		</form>
		<?php
	}

	// Register settings
	public function load_settings() {
		global $wp_version;

		register_setting( $this->data_key, $this->data_key, array( $this, 'validate_options' ) );
		// License
		add_settings_section( $this->wc_am_api_key_key, esc_html__( 'License Activation', 'formscrm' ), array(
			$this,
			'wc_am_api_key_text'
		), $this->wc_am_activation_tab_key );
		add_settings_field( $this->wc_am_api_key_key, esc_html__( 'License', 'formscrm' ), array(
			$this,
			'wc_am_api_key_field'
		), $this->wc_am_activation_tab_key, $this->wc_am_api_key_key );

		/**
		 * @since 2.3
		 */
		if ( $this->no_product_id ) {
			add_settings_field( 'product_id', esc_html__( 'Product ID', 'formscrm' ), array(
				$this,
				'wc_am_product_id_field'
			), $this->wc_am_activation_tab_key, $this->wc_am_api_key_key );
		}

		/**
		 * @since 2.8
		 */ //if ( version_compare( $wp_version, '5.5', '>=' ) ) {
		//	add_settings_field( $this->wc_am_auto_update_key, esc_html__( 'Auto Plugin Updates', 'formscrm' ), array(
		//		$this,
		//		'wc_am_auto_update_radio'
		//	), $this->wc_am_activation_tab_key, $this->wc_am_api_key_key );
		//}

		add_settings_field( 'status', esc_html__( 'License Status', 'formscrm' ), array(
			$this,
			'wc_am_api_key_status'
		), $this->wc_am_activation_tab_key, $this->wc_am_api_key_key );
		// Activation settings
		register_setting( $this->wc_am_deactivate_checkbox_key, $this->wc_am_deactivate_checkbox_key, array(
			$this,
			'wc_am_license_key_deactivation'
		) );
		add_settings_section( 'deactivate_button', esc_html__( 'API Deactivation', 'formscrm' ), array(
			$this,
			'wc_am_deactivate_text'
		), $this->wc_am_deactivation_tab_key );
		add_settings_field( 'deactivate_button', esc_html__( 'Deactivate License', 'formscrm' ), array(
			$this,
			'wc_am_deactivate_textarea'
		), $this->wc_am_deactivation_tab_key, 'deactivate_button' );
	}

	// Provides text for api key section
	public function wc_am_api_key_text() { }

	// Returns the License status from the WooCommerce API Manager on the server
	public function wc_am_api_key_status() {
		if ( $this->get_api_key_status( true ) ) {
			$license_status_check = esc_html__( 'Activated', 'formscrm' );
			update_option( $this->wc_am_activated_key, 'Activated' );
			update_option( $this->wc_am_deactivate_checkbox_key, 'off' );
		} else {
			$license_status_check = esc_html__( 'Deactivated', 'formscrm' );
		}

		echo esc_attr( $license_status_check );
	}

	/**
	 * Returns the License status by querying the Status API function from the WooCommerce API Manager on the server.
	 *
	 * @return array|mixed|object
	 */
	public function license_key_status() {
		$status = $this->status();

		return ! empty( $status ) ? json_decode( $this->status(), true ) : $status;
	}

	/**
	 * Returns true if the License status is Activated.
	 *
	 * @since 2.1
	 *
	 * @param bool $live Do not set to true if using to activate software. True is for live status checks after activation.
	 *
	 * @return bool
	 */
	public function get_api_key_status( $live = false ) {
		/**
		 * Real-time result.
		 *
		 * @since 2.5.1
		 */
		if ( $live ) {
			$license_status = $this->license_key_status();

			return ! empty( $license_status ) && ! empty( $license_status[ 'data' ][ 'activated' ] ) && $license_status[ 'data' ][ 'activated' ];
		}

		/**
		 * If $live === false.
		 *
		 * Stored result when first activating software.
		 */
		return get_option( $this->wc_am_activated_key ) == 'Activated';
	}

	// Returns License text field
	public function wc_am_api_key_field() {
		if ( ! empty( $this->data[ $this->wc_am_api_key_key ] ) ) {
			echo "<input id='api_key' name='" . esc_attr( $this->data_key ) . "[" . esc_attr( $this->wc_am_api_key_key ) . "]' size='25' type='text' value='" . esc_attr( $this->data[ $this->wc_am_api_key_key ] ) . "' />";
		} else {
			echo "<input id='api_key' name='" . esc_attr( $this->data_key ) . "[" . esc_attr( $this->wc_am_api_key_key ) . "]' size='25' type='text' value='' />";
		}
	}

	/**
	 * @since 2.3
	 */
	public function wc_am_product_id_field() {
		$product_id = get_option( $this->wc_am_product_id );

		if ( ! empty( $product_id ) ) {
			$this->product_id = $product_id;
		}

		if ( ! empty( $product_id ) ) {
			echo "<input id='product_id' name='" . esc_attr( $this->wc_am_product_id ) . "' size='25' type='text' value='" . absint( $this->product_id ) . "' />";
		} else {
			echo "<input id='product_id' name='" . esc_attr( $this->wc_am_product_id ) . "' size='25' type='text' value='' />";
		}
	}

	/**
	 * Radio buttons to toggle auto-updates on or off.
	 *
	 * @since 2.8
	 */
	//public function wc_am_auto_update_radio() {
	//	echo '<input type="radio" name="' . esc_attr( $this->wc_am_auto_update_key ) . '" value="on"' . checked( get_option( $this->wc_am_auto_update_key ), 'on', false ) . '>' . esc_html__( 'On', 'formscrm' ) . '<br /><br />';
	//	echo '<input type="radio" name="' . esc_attr( $this->wc_am_auto_update_key ) . '" value="off"' . checked( get_option( $this->wc_am_auto_update_key ), 'off', false ) . '>' . esc_html__( 'Off', 'formscrm' );
	//}

	/**
	 * Sanitizes and validates all input and output for Dashboard
	 *
	 * @since 2.0
	 *
	 * @param $input
	 *
	 * @return mixed|string
	 */
	public function validate_options( $input ) {
		// Load existing options, validate, and update with changes from input before returning
		$options                             = $this->data;
		$options[ $this->wc_am_api_key_key ] = trim( $input[ $this->wc_am_api_key_key ] );
		$api_key                             = trim( $input[ $this->wc_am_api_key_key ] );
		$activation_status                   = get_option( $this->wc_am_activated_key );
		$checkbox_status                     = get_option( $this->wc_am_deactivate_checkbox_key );
		$current_api_key                     = ! empty( $this->data[ $this->wc_am_api_key_key ] ) ? $this->data[ $this->wc_am_api_key_key ] : '';

		/**
		 * @since 2.3
		 */
		if ( $this->no_product_id ) {
			$new_product_id = absint( $_REQUEST[ $this->wc_am_product_id ] );

			if ( ! empty( $new_product_id ) ) {
				update_option( $this->wc_am_product_id, $new_product_id );
				$this->product_id = $new_product_id;
			}
		}

		/**
		 * Toggle auto-updates.
		 *
		 * @since 2.8
		 */ //if ( ! empty( $_REQUEST[ $this->wc_am_auto_update_key ] ) && $_REQUEST[ $this->wc_am_auto_update_key ] == 'on' ) {
		//	update_option( $this->wc_am_auto_update_key, 'on' );
		//} else {
		//	update_option( $this->wc_am_auto_update_key, 'off' );
		//}

		// Should match the settings_fields() value
		if ( ! empty( $_REQUEST[ 'option_page' ] ) && $_REQUEST[ 'option_page' ] != $this->wc_am_deactivate_checkbox_key ) {
			if ( $activation_status == 'Deactivated' || $activation_status == '' || $api_key == '' || $checkbox_status == 'on' || $current_api_key != $api_key ) {
				/**
				 * If this is a new key, and an existing key already exists in the database,
				 * try to deactivate the existing key before activating the new key.
				 */
				if ( ! empty( $current_api_key ) && $current_api_key != $api_key ) {
					$this->replace_license_key( $current_api_key );
				}

				$args = array(
					'api_key' => $api_key,
				);

				$activation_result = $this->activate( $args );

				if ( ! empty( $activation_result ) ) {
					$activate_results = json_decode( $activation_result, true );

					if ( $activate_results[ 'success' ] === true && $activate_results[ 'activated' ] === true ) {
						add_settings_error( 'activate_text', 'activate_msg', sprintf( __( '%s activated. ', 'formscrm' ), esc_attr( $this->software_title ) ) . esc_attr( "{$activate_results['message']}." ), 'updated' );
						update_option( $this->wc_am_activated_key, 'Activated' );
						update_option( $this->wc_am_deactivate_checkbox_key, 'off' );
					}

					if ( $activate_results == false && ! empty( $this->data ) && ! empty( $this->wc_am_activated_key ) ) {
						add_settings_error( 'api_key_check_text', 'api_key_check_error', esc_html__( 'Connection failed to the License Key API server. Try again later. There may be a problem on your server preventing outgoing requests, or the store is blocking your request to activate the plugin/theme.', 'formscrm' ), 'error' );
						update_option( $this->wc_am_activated_key, 'Deactivated' );
					}

					if ( isset( $activate_results[ 'data' ][ 'error_code' ] ) && ! empty( $this->data ) && ! empty( $this->wc_am_activated_key ) ) {
						add_settings_error( 'wc_am_client_error_text', 'wc_am_client_error', esc_attr( "{$activate_results['data']['error']}" ), 'error' );
						update_option( $this->wc_am_activated_key, 'Deactivated' );
					}
				} else {
					add_settings_error( 'not_activated_empty_response_text', 'not_activated_empty_response_error', esc_html__( 'The License activation could not be commpleted due to an unknown error possibly on the store server The activation results were empty.', 'formscrm' ), 'updated' );
				}
			} // End Plugin Activation
		}

		return $options;
	}

	// Deactivates the License to allow key to be used on another blog
	public function wc_am_license_key_deactivation( $input ) {
		$activation_status = get_option( $this->wc_am_activated_key );
		$options           = ( $input == 'on' ? 'on' : 'off' );

		$args = array(
			'api_key' => ! empty( $this->data[ $this->wc_am_api_key_key ] ) ? $this->data[ $this->wc_am_api_key_key ] : '',
		);

		if ( ! empty( $this->data[ $this->wc_am_api_key_key ] ) && $options == 'on' && $activation_status == 'Activated' ) {
			// deactivates License key activation
			$deactivation_result = $this->deactivate( $args );

			if ( ! empty( $deactivation_result ) ) {
				$activate_results = json_decode( $deactivation_result, true );

				if ( $activate_results[ 'success' ] === true && $activate_results[ 'deactivated' ] === true ) {
					if ( ! empty( $this->wc_am_activated_key ) ) {
						update_option( $this->wc_am_activated_key, 'Deactivated' );
						add_settings_error( 'wc_am_deactivate_text', 'deactivate_msg', esc_html__( 'License deactivated. ', 'formscrm' ) . esc_attr( "{$activate_results['activations_remaining']}." ), 'updated' );
					}

					return $options;
				}

				if ( isset( $activate_results[ 'data' ][ 'error_code' ] ) && ! empty( $this->data ) && ! empty( $this->wc_am_activated_key ) ) {
					add_settings_error( 'wc_am_client_error_text', 'wc_am_client_error', esc_attr( "{$activate_results['data']['error']}" ), 'error' );
					update_option( $this->wc_am_activated_key, 'Deactivated' );
				}
			} else {
				add_settings_error( 'not_deactivated_empty_response_text', 'not_deactivated_empty_response_error', esc_html__( 'The License activation could not be commpleted due to an unknown error possibly on the store server The activation results were empty.', 'formscrm' ), 'updated' );
			}
		}

		return $options;
	}

	/**
	 * Deactivate the current License before activating the new License
	 *
	 * @param string $current_api_key
	 */
	public function replace_license_key( $current_api_key ) {
		$args = array(
			'api_key' => $current_api_key,
		);

		$this->deactivate( $args );
	}

	public function wc_am_deactivate_text() { }

	public function wc_am_deactivate_textarea() {
		echo '<input type="checkbox" id="' . esc_attr( $this->wc_am_deactivate_checkbox_key ) . '" name="' . esc_attr( $this->wc_am_deactivate_checkbox_key ) . '" value="on"';
		echo checked( get_option( $this->wc_am_deactivate_checkbox_key ), 'on' );
		echo '/>';
		?><span class="description"><?php esc_html_e( 'Deactivates an License so it can be used on another blog.', 'formscrm' ); ?></span>
		<?php
	}

	/**
	 * Builds the URL containing the API query string for activation, deactivation, and status requests.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function create_software_api_url( $args ) {
		return add_query_arg( 'wc-api', 'wc-am-api', $this->api_url ) . '&' . http_build_query( $args );
	}

	/**
	 * Sends the request to activate to the API Manager.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function activate( $args ) {
		if ( empty( $args ) ) {
			add_settings_error( 'not_activated_text', 'not_activated_error', esc_html__( 'The License is missing from the deactivation request.', 'formscrm' ), 'updated' );

			return '';
		}

		$defaults = array(
			'wc_am_action'     => 'activate',
			'product_id'       => $this->product_id,
			'instance'         => $this->wc_am_instance_id,
			'object'           => $this->wc_am_domain,
			'software_version' => $this->wc_am_software_version
		);

		$args       = wp_parse_args( $defaults, $args );
		$target_url = esc_url_raw( $this->create_software_api_url( $args ) );
		$request    = wp_safe_remote_post( $target_url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
			// Request failed
			return '';
		}

		return wp_remote_retrieve_body( $request );
	}

	/**
	 * Sends the request to deactivate to the API Manager.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function deactivate( $args ) {
		if ( empty( $args ) ) {
			add_settings_error( 'not_deactivated_text', 'not_deactivated_error', esc_html__( 'The License is missing from the deactivation request.', 'formscrm' ), 'updated' );

			return '';
		}

		$defaults = array(
			'wc_am_action' => 'deactivate',
			'product_id'   => $this->product_id,
			'instance'     => $this->wc_am_instance_id,
			'object'       => $this->wc_am_domain
		);

		$args       = wp_parse_args( $defaults, $args );
		$target_url = esc_url_raw( $this->create_software_api_url( $args ) );
		$request    = wp_safe_remote_post( $target_url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
			// Request failed
			return '';
		}

		return wp_remote_retrieve_body( $request );
	}

	/**
	 * Sends the status check request to the API Manager.
	 *
	 * @return bool|string
	 */
	public function status() {
		if ( empty( $this->data[ $this->wc_am_api_key_key ] ) ) {
			return '';
		}

		$defaults = array(
			'wc_am_action' => 'status',
			'api_key'      => $this->data[ $this->wc_am_api_key_key ],
			'product_id'   => $this->product_id,
			'instance'     => $this->wc_am_instance_id,
			'object'       => $this->wc_am_domain
		);

		$target_url = esc_url_raw( $this->create_software_api_url( $defaults ) );
		$request    = wp_safe_remote_post( $target_url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
			// Request failed
			return '';
		}

		return wp_remote_retrieve_body( $request );
	}

	/**
	 * Check for software updates.
	 */
	public function check_for_update() {
		$this->plugin_name = $this->wc_am_plugin_name;

		// Slug should be the same as the plugin/theme directory name
		if ( strpos( $this->plugin_name, '.php' ) !== 0 ) {
			$this->slug = dirname( $this->plugin_name );
		} else {
			$this->slug = $this->plugin_name;
		}

		/*********************************************************************
		 * The plugin and theme filters should not be active at the same time
		 *********************************************************************/ /**
			* More info:
			* function set_site_transient moved from wp-includes/functions.php
			* to wp-includes/option.php in WordPress 3.4
			*
			* set_site_transient() contains the pre_set_site_transient_{$transient} filter
			* {$transient} is either update_plugins or update_themes
			*
			* Transient data for plugins and themes exist in the Options table:
			* _site_transient_update_themes
			* _site_transient_update_plugins
			*/

		// uses the flag above to determine if this is a plugin or a theme update request
		if ( $this->plugin_or_theme == 'plugin' ) {
			/**
			 * Plugin Updates
			 */
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update_check' ) );
			// Check For Plugin Information to display on the update details page
			add_filter( 'plugins_api', array( $this, 'information_request' ), 10, 3 );
		} elseif ( $this->plugin_or_theme == 'theme' ) {
			/**
			 * Theme Updates
			 */
			add_filter( 'pre_set_site_transient_update_themes', array( $this, 'update_check' ) );

			// Check For Theme Information to display on the update details page
			//add_filter( 'themes_api', array( $this, 'information_request' ), 10, 3 );

		}
	}

	/**
	 * Sends and receives data to and from the server API
	 *
	 * @since  2.0
	 *
	 * @param array $args
	 *
	 * @return bool|string $response
	 */
	public function send_query( $args ) {
		$target_url = esc_url_raw( add_query_arg( 'wc-api', 'wc-am-api', $this->api_url ) . '&' . http_build_query( $args ) );
		$request    = wp_safe_remote_post( $target_url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
			return false;
		}

		$response = wp_remote_retrieve_body( $request );

		return ! empty( $response ) ? $response : false;
	}

	/**
	 * Check for updates against the remote server.
	 *
	 * @since  2.0
	 *
	 * @param object $transient
	 *
	 * @return object $transient
	 */
	public function update_check( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$args = array(
			'wc_am_action' => 'update',
			'slug'         => $this->slug,
			'plugin_name'  => $this->plugin_name,
			'version'      => $this->wc_am_software_version,
			'product_id'   => $this->product_id,
			'api_key'      => ! empty( $this->data[ $this->wc_am_api_key_key ] ) ? $this->data[ $this->wc_am_api_key_key ] : '',
			'instance'     => $this->wc_am_instance_id,
		);

		// Check for a plugin update
		$response = json_decode( $this->send_query( $args ), true );
		// Displays an admin error message in the WordPress dashboard
		//$this->check_response_for_errors( $response );

		if ( isset( $response[ 'data' ][ 'error_code' ] ) ) {
			add_settings_error( 'wc_am_client_error_text', 'wc_am_client_error', "{$response['data']['error']}", 'error' );
		}

		if ( $response !== false && $response[ 'success' ] === true ) {
			// New plugin version from the API
			$new_ver = (string) $response[ 'data' ][ 'package' ][ 'new_version' ];
			// Current installed plugin version
			$curr_ver = (string) $this->wc_am_software_version;

			$package = array(
				'id'             => $response[ 'data' ][ 'package' ][ 'id' ],
				'slug'           => $response[ 'data' ][ 'package' ][ 'slug' ],
				'plugin'         => $response[ 'data' ][ 'package' ][ 'plugin' ],
				'new_version'    => $response[ 'data' ][ 'package' ][ 'new_version' ],
				'url'            => $response[ 'data' ][ 'package' ][ 'url' ],
				'tested'         => $response[ 'data' ][ 'package' ][ 'tested' ],
				'package'        => $response[ 'data' ][ 'package' ][ 'package' ],
				'upgrade_notice' => $response[ 'data' ][ 'package' ][ 'upgrade_notice' ],
			);

			if ( isset( $new_ver ) && isset( $curr_ver ) ) {
				if ( version_compare( $new_ver, $curr_ver, '>' ) ) {
					if ( $this->plugin_or_theme == 'plugin' ) {
						$transient->response[ $this->plugin_name ] = (object) $package;
						unset( $transient->no_update[ $this->plugin_name ] );
					} elseif ( $this->plugin_or_theme == 'theme' ) {
						$transient->response[ $this->plugin_name ][ 'new_version' ] = $response[ 'data' ][ 'package' ][ 'new_version' ];
						$transient->response[ $this->plugin_name ][ 'url' ]         = $response[ 'data' ][ 'package' ][ 'url' ];
						$transient->response[ $this->plugin_name ][ 'package' ]     = $response[ 'data' ][ 'package' ][ 'package' ];
					}
				}
			}
		}

		return $transient;
	}

	/**
	 * API request for informatin.
	 *
	 * If `$action` is 'query_plugins' or 'plugin_information', an object MUST be passed.
	 * If `$action` is 'hot_tags` or 'hot_categories', an array should be passed.
	 *
	 * @param false|object|array $result The result object or array. Default false.
	 * @param string             $action The type of information being requested from the Plugin Install API.
	 * @param object             $args
	 *
	 * @return object
	 */
	public function information_request( $result, $action, $args ) {
		// Check if this plugins API is about this plugin
		if ( isset( $args->slug ) ) {
			if ( $args->slug != $this->slug ) {
				return $result;
			}
		} else {
			return $result;
		}

		$args = array(
			'wc_am_action' => 'plugininformation',
			'plugin_name'  => $this->plugin_name,
			'version'      => $this->wc_am_software_version,
			'product_id'   => $this->product_id,
			'api_key'      => ! empty( $this->data[ $this->wc_am_api_key_key ] ) ? $this->data[ $this->wc_am_api_key_key ] : '',
			'instance'     => $this->wc_am_instance_id,
			'object'       => $this->wc_am_domain,
		);

		$response = unserialize( $this->send_query( $args ) );

		if ( isset( $response ) && is_object( $response ) && $response !== false ) {
			return $response;
		}

		return $result;
	}

}