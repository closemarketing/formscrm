<?php
/*
Plugin Name: Gravity Forms CRM Add-On
Plugin URI: http://codecanyon.net/item/gravity-forms-crm-addon/10521695
Description: Integrates Gravity Forms with CRM allowing form submissions to be automatically sent to your CRM.
Version: 2.6.3
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

define( 'GF_CRM_VERSION', '2.6.3' );

add_action( 'gform_loaded', array( 'GF_CRM_Bootstrap', 'load' ), 5 );

// Include the Integration (we're using composer)
require_once 'vendor/autoload.php';

// Set up the Update integration
$client = \Smoolabs\WPU\V4\WPLSController::initClient('https://wp.closemarketing.es/', array(
	'name'      => 'Gravity Forms CRM Add-On',
	'version'   => '2.6.2',
	'path'      => __FILE__,
	'slug'      => 'gravityforms-crm'
));

class GF_CRM_Bootstrap {

	public static function load(){

		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		require_once( 'class-gf-crm.php' );

		GFAddOn::register( 'GFCRM' );
	}
}

function gf_crm(){
	return GFCRM::get_instance();
}
