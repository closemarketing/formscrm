<?php
/**
 * Loader
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2020 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

require_once 'helpers-functions.php';
require_once 'helpers-library-crm.php';
require_once 'class-forms-clientify.php';

// Prevents fatal error is_plugin_active.
if ( ! function_exists( 'is_plugin_active' ) ) {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ( is_plugin_active( 'gravityforms/gravityforms.php' ) || is_plugin_active( 'gravity-forms/gravityforms.php' ) ) && ! class_exists( 'FC_CRM_Bootstrap' ) ) {
	add_action( 'gform_loaded', array( 'FC_CRM_Bootstrap', 'load' ), 5 );
	class FC_CRM_Bootstrap {

		public static function load() {

			if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
				return;
			}

			require_once 'class-gravityforms.php';

			GFAddOn::register( 'GFCRM' );
		}
	}

	function gf_crm() {
		return FCCRM::get_instance();
	}

	require_once 'class-gravityforms-widget.php';
}

// ContactForms7.
if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) && ! class_exists( 'FORMSCRM_CF7_Settings' ) ) {
	require_once 'class-contactform7.php';
}

// WooCommerce.
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) && ! class_exists( 'FormsCRM_WooCommerce' ) ) {
	require_once 'class-woocommerce.php';
}

// WPForms.
if ( is_plugin_active( 'wpforms/wpforms.php' ) && ! class_exists( 'WPForms_FormsCRM' ) ) {
	add_action( 'wpforms_loaded', 'wpforms_formscrm' );
	/**
	 * Load the provider class.
	 *
	 * @since 3.7.2
	 */
	function wpforms_formscrm() {

		// WPForms Pro is required.
		if ( ! wpforms()->pro ) {
			return;
		}
		require_once 'class-wpforms.php';
	}
}

// FluentForms.
if ( is_plugin_active( 'fluentform/fluentform.php' ) && ! class_exists( 'FluentFormsIntegration' ) ) {
	class FluentFormFormsCRM {
  	public function boot() {
        if (!defined('FLUENTFORM')) {
            return $this->injectDependency();
        }

        $this->includeFiles();

        if (function_exists('wpFluentForm')) {
            return $this->registerHooks(wpFluentForm());
        }
    }

    protected function includeFiles() {
			require_once 'class-fluentforms.php';
    }

    protected function registerHooks( $fluentForm ){
      new \FormsCRM\Forms\FluentForms\Bootstrap($fluentForm);
    }

    /**
     * Notify the user about the FluentForm dependency and instructs to install it.
     */
    protected function injectDependency()
    {
        add_action('admin_notices', function () {
            $pluginInfo = $this->getFluentFormInstallationDetails();

            $class = 'notice notice-error';

            $install_url_text = 'Click Here to Install the Plugin';

            if ($pluginInfo->action == 'activate') {
                $install_url_text = 'Click Here to Activate the Plugin';
            }

            $message = 'FluentForm FormsCRM Add-On Requires Fluent Forms Add On Plugin, <b><a href="' . $pluginInfo->url
                . '">' . $install_url_text . '</a></b>';

            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
        });
    }

    protected function getFluentFormInstallationDetails() {
        $activation = (object)[
            'action' => 'install',
            'url' => ''
        ];

        $allPlugins = get_plugins();

        if (isset($allPlugins['fluentform/fluentform.php'])) {
            $url = wp_nonce_url(
                self_admin_url('plugins.php?action=activate&plugin=fluentform/fluentform.php'),
                'activate-plugin_fluentform/fluentform.php'
            );

            $activation->action = 'activate';
        } else {
            $api = (object)[
                'slug' => 'fluentform'
            ];

            $url = wp_nonce_url(
                self_admin_url('update.php?action=install-plugin&plugin=' . $api->slug),
                'install-plugin_' . $api->slug
            );
        }

        $activation->url = $url;

        return $activation;
    }
}

	register_activation_hook(__FILE__, function () {
		$globalModules = get_option('fluentform_global_modules_status');
		if (!$globalModules || !is_array($globalModules)) {
			$globalModules = [];
		}

		$globalModules['ff_formscrm'] = 'yes';
		update_option( 'fluentform_global_modules_status', $globalModules );
	});

	add_action('plugins_loaded', function () {
		(new FluentFormFormsCRM())->boot();
	});
}
