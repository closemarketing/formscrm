<?php
/*
Plugin Name: FormsCRM
Plugin URI: https://formscrm.com
Description: Integrates Gravity Forms with CRM allowing form submissions to be automatically sent to your CRM.
Version: 3.0-beta1
Author: closemarketing
Author URI: http://www.closemarketing.es

------------------------------------------------------------------------
Copyright 2018 closemarketing

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

define( 'FORMSCRM_VERSION', '3.0beta1' );

add_action( 'gform_loaded', array( 'GF_CRM_Bootstrap', 'load' ), 5 );

require_once 'includes/debug.php';
require_once 'includes/class-library-crm.php';

// GravityForms.
if ( is_plugin_active( 'gravityforms/gravityforms.php' ) || is_plugin_active( 'gravity-forms/gravityforms.php' ) ) {
	class GF_CRM_Bootstrap {

		public static function load(){

			if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
				return;
			}

			require_once( 'includes/class-gravityforms.php' );

			GFAddOn::register( 'GFCRM' );
		}
	}

	function gf_crm(){
		return GFCRM::get_instance();
	}
}

// ContactForms7.
if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
	require_once 'includes/class-contactform7.php';
}


// WPForms.
if ( is_plugin_active( 'wpforms/wpforms.php' ) || is_plugin_active( 'wpforms-lite/wpforms.php' ) ) {
	/**
	 * Load the provider class.
	 *
	 * @since 1.0.0
	 */
	function wpforms_formscrm() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpforms.php';
	}

	add_action( 'wpforms_loaded', 'wpforms_formscrm' );
}
