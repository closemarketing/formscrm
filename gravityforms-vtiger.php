<?php
/*
Plugin Name: Gravity Forms VTiger CRM Add-On
Plugin URI: http://www.gravityforms.com
Description: Integrates Gravity Forms with VTiger CRM allowing form submissions to be automatically sent to your VTiger CRM.
Version: 0.1
Author: closemarketing
Author URI: http://www.closemarketing.es

------------------------------------------------------------------------
Copyright 2014 closemarketing

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

add_action('init',  array('GFVtiger', 'init'));

register_activation_hook( __FILE__, array("GFVtiger", "add_permissions"));

class GFVtiger {

    private static $path = "gravityforms-vtiger/gravityforms-vtiger.php";
    private static $url = "http://www.gravityforms.com";
    private static $slug = "gravityforms-vtiger";
    private static $version = "0.1";
    private static $min_gravityforms_version = "1.3.9";
    private static $supported_fields = array("checkbox", "radio", "select", "text", "website", "textarea", "email", "hidden", "number", "phone", "multiselect", "post_title",
		                            "post_tags", "post_custom_field", "post_content", "post_excerpt");


    //Plugin starting point. Will load appropriate files
    public static function init(){
    	//supports logging
		add_filter("gform_logging_supported", array("GFVtiger", "set_logging_supported"));

        if(basename($_SERVER['PHP_SELF']) == "plugins.php") {
            //loading translations
            load_plugin_textdomain('gravityforms-vtiger', FALSE, '/gravityforms-vtiger/languages' );

            add_action('after_plugin_row_' . self::$path, array('GFVtiger', 'plugin_row') );

            //force new remote request for version info on the plugin page
            //self::flush_version_info();
        }

        if(!self::is_gravityforms_supported()){
           return;
        }

        if(is_admin()){
            //loading translations
            load_plugin_textdomain('gravityforms-vtiger', FALSE, '/gravityforms-vtiger/languages' );

            //add_filter("transient_update_plugins", array('GFVtiger', 'check_update'));
            //add_filter("site_transient_update_plugins", array('GFVtiger', 'check_update'));

            add_action('install_plugins_pre_plugin-information', array('GFVtiger', 'display_changelog'));

            //creates a new Settings page on Gravity Forms' settings screen
            if(self::has_access("gravityforms_vtiger")){
                RGForms::add_settings_page("vtiger", array("GFVtiger", "settings_page"), self::get_base_url() . "/images/vtiger_wordpress_icon_32.png");
            }
        }

        //integrating with Members plugin
        if(function_exists('members_get_capabilities'))
            add_filter('members_get_capabilities', array("GFVtiger", "members_get_capabilities"));

        //creates the subnav left menu
        add_filter("gform_addon_navigation", array('GFVtiger', 'create_menu'));

        if(self::is_vtiger_page()){

            //enqueueing sack for AJAX requests
            wp_enqueue_script(array("sack"));

            //loading data lib
            require_once(self::get_base_path() . "/data.php");

            //loading Gravity Forms tooltips
            require_once(GFCommon::get_base_path() . "/tooltips.php");
            add_filter('gform_tooltips', array('GFVtiger', 'tooltips'));

            //runs the setup when version changes
            self::setup();

         }
         else if(in_array(RG_CURRENT_PAGE, array("admin-ajax.php"))){

            //loading data class
            require_once(self::get_base_path() . "/data.php");

            add_action('wp_ajax_rg_update_feed_active', array('GFVtiger', 'update_feed_active'));
            add_action('wp_ajax_gf_select_vtiger_form', array('GFVtiger', 'select_form'));
            add_action('wp_ajax_gf_select_vtiger_client', array('GFVtiger', 'select_client'));

        }
        else{
             //handling post submission.
            add_action("gform_after_submission", array('GFVtiger', 'export'), 10, 2);

            //handling paypal fulfillment
            add_action("gform_paypal_fulfillment", array("GFVtiger", "paypal_fulfillment"), 10, 4);
        }
    }


    public static function update_feed_active(){
        check_ajax_referer('rg_update_feed_active','rg_update_feed_active');
        $id = $_POST["feed_id"];
        $feed = GFVtigerData::get_feed($id);
        GFVtigerData::update_feed($id, $feed["form_id"], $_POST["is_active"], $feed["meta"]);
    }

/*
    public static function plugin_row(){
        if(!self::is_gravityforms_supported()){
            $message = sprintf(__("Gravity Forms " . self::$min_gravityforms_version . " is required. Activate it now or %spurchase it today!%s"), "<a href='http://www.gravityforms.com'>", "</a>");
            RGvtigerUpgrade::display_plugin_message($message, true);
        }
        else{
            $version_info = RGvtigerUpgrade::get_version_info(self::$slug, self::get_key(), self::$version);

            if(!$version_info["is_valid_key"]){
                $new_version = version_compare(self::$version, $version_info["version"], '<') ? __('There is a new version of Gravity Forms vtiger Add-On available.', 'gravityforms-vtiger') .' <a class="thickbox" title="Gravity Forms vtiger Add-On" href="plugin-install.php?tab=plugin-information&plugin=' . self::$slug . '&TB_iframe=true&width=640&height=808">'. sprintf(__('View version %s Details', 'gravityforms-vtiger'), $version_info["version"]) . '</a>. ' : '';
                $message = $new_version . sprintf(__('%sRegister%s your copy of Gravity Forms to receive access to automatic upgrades and support. Need a license key? %sPurchase one now%s.', 'gravityforms-vtiger'), '<a href="admin.php?page=gf_settings">', '</a>', '<a href="http://www.gravityforms.com">', '</a>') . '</div></td>';
                //RGvtigerUpgrade::display_plugin_message($message);
            }
        }
    }*/


    private static function get_key(){
        if(self::is_gravityforms_supported())
            return GFCommon::get_key();
        else
            return "";
    }
    //---------------------------------------------------------------------------------------

    //Returns true if the current page is an Feed pages. Returns false if not
    private static function is_vtiger_page(){
        $current_page = trim(strtolower(RGForms::get("page")));
        $vtiger_pages = array("gf_vtiger");

        return in_array($current_page, $vtiger_pages);
    }

    //Creates or updates database tables. Will only run when version changes
    private static function setup(){

        if(get_option("gf_vtiger_version") != self::$version)
            GFVtigerData::update_table();

        update_option("gf_vtiger_version", self::$version);
    }

    //Adds feed tooltips to the list of tooltips
    public static function tooltips($tooltips){
        $vtiger_tooltips = array(
            "vtiger_client" => "<h6>" . __("Client", "gravityforms-vtiger") . "</h6>" . __("Select the vtiger client you would like to add your contacts to.", "gravityforms-vtiger"),
            "vtiger_contact_list" => "<h6>" . __("Contact List", "gravityforms-vtiger") . "</h6>" . __("Select the vtiger list you would like to add your contacts to.", "gravityforms-vtiger"),
            "vtiger_gravity_form" => "<h6>" . __("Gravity Form", "gravityforms-vtiger") . "</h6>" . __("Select the Gravity Form you would like to integrate with vtiger. Contacts generated by this form will be automatically added to your vtiger account.", "gravityforms-vtiger"),
            "vtiger_map_fields" => "<h6>" . __("Map Fields", "gravityforms-vtiger") . "</h6>" . __("Associate your vtiger custom fields to the appropriate Gravity Form fields by selecting the appropriate form field from the list.", "gravityforms-vtiger"),
            "vtiger_optin_condition" => "<h6>" . __("Opt-In Condition", "gravityforms-vtiger") . "</h6>" . __("When the opt-in condition is enabled, form submissions will only be exported to vtiger when the condition is met. When disabled all form submissions will be exported.", "gravityforms-vtiger"),
            "vtiger_resubscribe" => "<h6>" . __("Resubscribe", "gravityforms-vtiger") . "</h6>" . __("When this option is enabled, if the subscriber is in an inactive state or has previously been unsubscribed, they will be re-added to the active list. Therefore, this option should be used with caution and only when appropriate.", "gravityforms-vtiger")
        );
        return array_merge($tooltips, $vtiger_tooltips);
    }

    //Creates vtiger left nav menu under Forms
    public static function create_menu($menus){

        // Adding submenu if user has access
        $permission = self::has_access("gravityforms_vtiger");
        if(!empty($permission))
            $menus[] = array("name" => "gf_vtiger", "label" => __("vTiger", "gravityforms-vtiger"), "callback" =>  array("GFVtiger", "vtiger_page"), "permission" => $permission);

        return $menus;
    }

    public static function settings_page(){

        if(isset($_POST["uninstall"])){
            check_admin_referer("uninstall", "gf_vtiger_uninstall");
            self::uninstall();

            ?>
            <div class="updated fade" style="padding:20px;"><?php _e(sprintf("Gravity Forms vtiger Add-On have been successfully uninstalled. It can be re-activated from the %splugins page%s.", "<a href='plugins.php'>","</a>"), "gravityforms-vtiger")?></div>
            <?php
            return;
        }
        else if(isset($_POST["gf_vtiger_submit"])){
            check_admin_referer("update", "gf_vtiger_update");
            $settings = array("url" => $_POST["gf_vtiger_url"], "username" => $_POST["gf_vtiger_username"], "password" => $_POST["gf_vtiger_password"]);
            update_option("gf_vtiger_settings", $settings);
        }
        else{
            $settings = get_option("gf_vtiger_settings");
        }

        self::log_debug("Validating key.");
        $is_valid = self::is_valid_key();

        $message = "";
        if($is_valid)
        {
            $message = "Valid API Key.";
            self::log_debug("Valid key.");
		}
        else if(!empty($settings["api_key"]))
        {
            $message = "Invalid API Key. Please try another.";
            self::log_error("Invalid API Key.");
		}

        ?>
        <style>
            .valid_credentials{color:green;}
            .invalid_credentials{color:red;}
            .size-1{width:400px;}
        </style>

        <form method="post" action="">
            <?php wp_nonce_field("update", "gf_vtiger_update") ?>

            <h3><?php _e("vTiger Account Information", "gravityforms-vtiger") ?></h3>
            <p style="text-align: left;">
                <?php _e(sprintf("vTiger is a CRM software. Use Gravity Forms to collect customer information and automatically add them to your vtiger Leads." , "</a>"), "gravityforms-vtiger") ?>
            </p>

            <table class="form-table">
				<tr>
					<th scope="row"><label for="gf_vtiger_url"><?php _e("CRM URL", "gravityforms-vtiger"); ?></label> </th>
					<td width="88%">
                        <input type="text" class="size-1" id="gf_vtiger_url" name="gf_vtiger_url" value="<?php echo esc_attr($settings["url"]) ?>" />
                        <img src="<?php echo self::get_base_url() ?>/images/<?php echo $is_valid ? "tick.png" : "stop.png" ?>" border="0" alt="<?php $message ?>" title="<?php echo $message ?>" style="display:<?php echo empty($message) ? 'none;' : 'inline;' ?>" />
                    </td>
				</tr>
                <tr>
                    <th scope="row"><label for="gf_vtiger_username"><?php _e("Username", "gravityforms-vtiger"); ?></label> </th>
                    <td width="88%">
                        <input type="text" class="size-1" id="gf_vtiger_username" name="gf_vtiger_username" value="<?php echo esc_attr($settings["username"]) ?>" />
                        <br/>
                        <small><?php _e("Fill with an Administrator Account in vtiger.", "gravityforms-vtiger") ?></small>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gf_vtiger_password"><?php _e("Acccess Key", "gravityforms-vtiger"); ?></label> </th>
                    <td width="88%">
                        <input type="password" class="size-1" id="gf_vtiger_password" name="gf_vtiger_password" value="<?php echo esc_attr($settings["password"]) ?>" />
                        <br/>
                        <small><?php _e("Fill with Access Key in the Account administration.", "gravityforms-vtiger") ?></small>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" ><input type="submit" name="gf_vtiger_submit" class="button-primary" value="<?php _e("Save Settings", "gravityforms-vtiger") ?>" /></td>
                </tr>

            </table>
            <div>

            </div>
        </form>

         <form action="" method="post">
            <?php wp_nonce_field("uninstall", "gf_vtiger_uninstall") ?>
            <?php if(GFCommon::current_user_can_any("gravityforms_vtiger_uninstall")){ ?>
                <div class="hr-divider"></div>

                <h3><?php _e("Uninstall vtiger Add-On", "gravityforms-vtiger") ?></h3>
                <div class="delete-alert"><?php _e("Warning! This operation deletes ALL vtiger Feeds.", "gravityforms-vtiger") ?>
                    <?php
                    $uninstall_button = '<input type="submit" name="uninstall" value="' . __("Uninstall vtiger Add-On", "gravityforms-vtiger") . '" class="button" onclick="return confirm(\'' . __("Warning! ALL vtiger Feeds will be deleted. This cannot be undone. \'OK\' to delete, \'Cancel\' to stop", "gravityforms-vtiger") . '\');"/>';
                    echo apply_filters("gform_vtiger_uninstall_button", $uninstall_button);
                    ?>
                </div>
            <?php } ?>
        </form>

        <?php
    }

    public static function vtiger_page(){
        $view = rgar($_GET, "view");
        if($view == "edit")
            self::edit_page();
        else
            self::list_page();
    }

    //Displays the vtiger feeds list page
    private static function list_page(){
        if(!self::is_gravityforms_supported()){
            die(__(sprintf("vtiger Add-On requires Gravity Forms %s. Upgrade automatically on the %sPlugin page%s.", self::$min_gravityforms_version, "<a href='plugins.php'>", "</a>"), "gravityforms-vtiger"));
        }

        if(rgpost("action") == "delete"){
            check_admin_referer("list_action", "gf_vtiger_list");

            $id = absint($_POST["action_argument"]);
            GFVtigerData::delete_feed($id);
            ?>
            <div class="updated fade" style="padding:6px"><?php _e("Feed deleted.", "gravityforms-vtiger") ?></div>
            <?php
        }
        else if (!empty($_POST["bulk_action"])){
            check_admin_referer("list_action", "gf_vtiger_list");
            $selected_feeds = $_POST["feed"];
            if(is_array($selected_feeds)){
                foreach($selected_feeds as $feed_id)
                    GFVtigerData::delete_feed($feed_id);
            }
            ?>
            <div class="updated fade" style="padding:6px"><?php _e("Feeds deleted.", "gravityforms-vtiger") ?></div>
            <?php
        }

        ?>
        <div class="wrap">
            <img alt="<?php _e("VTiger Feeds", "gravityforms-vtiger") ?>" src="<?php echo self::get_base_url()?>/images/vtiger_wordpress_icon_32.png" style="float:left; margin:15px 7px 0 0;"/>
            <h2><?php _e("vtiger Feeds", "gravityforms-vtiger"); ?>
            <a class="button add-new-h2" href="admin.php?page=gf_vtiger&view=edit&id=0"><?php _e("Add New", "gravityforms-vtiger") ?></a>
            </h2>

            <form id="feed_form" method="post">
                <?php wp_nonce_field('list_action', 'gf_vtiger_list') ?>
                <input type="hidden" id="action" name="action"/>
                <input type="hidden" id="action_argument" name="action_argument"/>

                <div class="tablenav">
                    <div class="alignleft actions" style="padding:8px 0 7px 0">
                        <label class="hidden" for="bulk_action"><?php _e("Bulk action", "gravityforms-vtiger") ?></label>
                        <select name="bulk_action" id="bulk_action">
                            <option value=''> <?php _e("Bulk action", "gravityforms-vtiger") ?> </option>
                            <option value='delete'><?php _e("Delete", "gravityforms-vtiger") ?></option>
                        </select>
                        <?php
                        echo '<input type="submit" class="button" value="' . __("Apply", "gravityforms-vtiger") . '" onclick="if( jQuery(\'#bulk_action\').val() == \'delete\' && !confirm(\'' . __("Delete selected feeds? ", "gravityforms-vtiger") . __("\'Cancel\' to stop, \'OK\' to delete.", "gravityforms-vtiger") .'\')) { return false; } return true;"/>';
                        ?>
                    </div>
                </div>
                <table class="widefat fixed" cellspacing="0">
                    <thead>
                        <tr>
                            <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
                            <th scope="col" id="active" class="manage-column check-column"></th>
                            <th scope="col" class="manage-column"><?php _e("Form", "gravityforms-vtiger") ?></th>
                            <th scope="col" class="manage-column"><?php _e("vtiger Type", "gravityforms-vtiger") ?></th>
                            <th scope="col" class="manage-column"><?php _e("vtiger List", "gravityforms-vtiger") ?></th>
                        </tr>
                    </thead>

                    <tfoot>
                        <tr>
                            <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
                            <th scope="col" id="active" class="manage-column check-column"></th>
                            <th scope="col" class="manage-column"><?php _e("Form", "gravityforms-vtiger") ?></th>
                            <th scope="col" class="manage-column"><?php _e("vtiger Client", "gravityforms-vtiger") ?></th>
                            <th scope="col" class="manage-column"><?php _e("vtiger List", "gravityforms-vtiger") ?></th>
                        </tr>
                    </tfoot>

                    <tbody class="list:user user-list">
                        <?php

                        $settings = GFVtigerData::get_feeds();
                        if(is_array($settings) && sizeof($settings) > 0){
                            foreach($settings as $setting){
                                ?>
                                <tr valign="top">
                                    <th scope="row" class="check-column"><input type="checkbox" name="feed[]" value="<?php echo $setting["id"] ?>"/></th>
                                    <td><img src="<?php echo self::get_base_url() ?>/images/active<?php echo intval($setting["is_active"]) ?>.png" alt="<?php echo $setting["is_active"] ? __("Active", "gravityforms-vtiger") : __("Inactive", "gravityforms-vtiger");?>" title="<?php echo $setting["is_active"] ? __("Active", "gravityforms-vtiger") : __("Inactive", "gravityforms-vtiger");?>" onclick="ToggleActive(this, <?php echo $setting['id'] ?>); " /></td>
                                    <td class="column-title">
                                        <a href="admin.php?page=gf_vtiger&view=edit&id=<?php echo $setting["id"] ?>" title="<?php _e("Edit", "gravityforms-vtiger") ?>"><?php echo $setting["form_title"] ?></a>
                                        <div class="row-actions">
                                            <span class="edit">
                                            <a href="admin.php?page=gf_vtiger&view=edit&id=<?php echo $setting["id"] ?>" title="<?php _e("Edit", "gravityforms-vtiger") ?>"><?php _e("Edit", "gravityforms-vtiger") ?></a>
                                            |
                                            </span>

                                            <span class="trash">
                                            <a title="<?php _e("Delete", "gravityforms-vtiger") ?>" href="javascript: if(confirm('<?php _e("Delete this feed? ", "gravityforms-vtiger") ?> <?php _e("\'Cancel\' to stop, \'OK\' to delete.", "gravityforms-vtiger") ?>')){ DeleteSetting(<?php echo $setting["id"] ?>);}"><?php _e("Delete", "gravityforms-vtiger")?></a>

                                            </span>
                                        </div>
                                    </td>
                                    <td><?php echo $setting["meta"]["client_name"] ?></td>
                                    <td><?php echo $setting["meta"]["contact_list_name"] ?></td>
                                </tr>
                                <?php
                            }
                        }
                        else if(self::is_valid_key()){
                            ?>
                            <tr>
                                <td colspan="5" style="padding:20px;">
                                    <?php printf(__("You don't have any vtiger feeds configured. Let's go %screate one%s!", "gravityforms-vtiger"), '<a href="admin.php?page=gf_vtiger&view=edit&id=0">', "</a>"); ?>
                                </td>
                            </tr>
                            <?php
                        }
                        else{
                            ?>
                            <tr>
                                <td colspan="5" style="padding:20px;">
                                    <?php printf(__("To get started, please configure your %svtiger Settings%s.", "gravityforms-vtiger"), '<a href="admin.php?page=gf_settings&addon=vtiger">', "</a>"); ?>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </form>
        </div>
        <script type="text/javascript">
            function DeleteSetting(id){
                jQuery("#action_argument").val(id);
                jQuery("#action").val("delete");
                jQuery("#feed_form")[0].submit();
            }
            function ToggleActive(img, feed_id){
                var is_active = img.src.indexOf("active1.png") >=0
                if(is_active){
                    img.src = img.src.replace("active1.png", "active0.png");
                    jQuery(img).attr('title','<?php _e("Inactive", "gravityforms-vtiger") ?>').attr('alt', '<?php _e("Inactive", "gravityforms-vtiger") ?>');
                }
                else{
                    img.src = img.src.replace("active0.png", "active1.png");
                    jQuery(img).attr('title','<?php _e("Active", "gravityforms-vtiger") ?>').attr('alt', '<?php _e("Active", "gravityforms-vtiger") ?>');
                }

                var mysack = new sack(ajaxurl);
                mysack.execute = 1;
                mysack.method = 'POST';
                mysack.setVar( "action", "rg_update_feed_active" );
                mysack.setVar( "rg_update_feed_active", "<?php echo wp_create_nonce("rg_update_feed_active") ?>" );
                mysack.setVar( "feed_id", feed_id );
                mysack.setVar( "is_active", is_active ? 0 : 1 );
                mysack.encVar( "cookie", document.cookie, false );
                mysack.onError = function() { alert('<?php _e("Ajax error while updating feed", "gravityforms-vtiger" ) ?>' )};
                mysack.runAJAX();

                return true;
            }
        </script>
        <?php
    }

    public static function edit_page(){
        ?>
        <style>
            .vtiger_col_heading{padding-bottom:2px; border-bottom: 1px solid #ccc; font-weight: bold;}
            .vtiger_field_cell {padding: 6px 17px 0 0; margin-right:15px;}
            .left_header{float:left; width:200px;}
            .margin_vertical_10{margin: 10px 0;}
            #vtiger_resubscribe_warning{padding-left: 5px; padding-bottom:4px; font-size: 10px;}
            .gfield_required{color:red;}
            .feeds_validation_error{ background-color:#FFDFDF;}
            .feeds_validation_error td{ margin-top:4px; margin-bottom:6px; padding-top:6px; padding-bottom:6px; border-top:1px dotted #C89797; border-bottom:1px dotted #C89797}
        </style>
        <script type="text/javascript">
            var form = Array();
        </script>
        <div class="wrap">
            <img alt="<?php _e("vtiger", "gravityforms-vtiger") ?>" style="margin: 15px 7px 0pt 0pt; float: left;" src="<?php echo self::get_base_url() ?>/images/vtiger_wordpress_icon_32.png"/>
            <h2><?php _e("vtiger Feed", "gravityforms-vtiger") ?></h2>

        <?php

        //ensures valid credentials were entered in the settings page

        if(!self::is_valid_key()){
            ?>
            <div class="error" style="padding:15px;"><?php echo sprintf(__("We are unable to login to vtiger with the provided access. Please make sure you have entered a valid URL, username and password in the %sSettings Page%s", "gravityforms-vtiger"), "<a href='?page=gf_settings&subview=vtiger'>", "</a>"); ?></div>
            <?php
            return;
        }

        //getting setting id (0 when creating a new one)
        $id = !empty($_POST["vtiger_setting_id"]) ? $_POST["vtiger_setting_id"] : absint($_GET["id"]);
        $config = empty($id) ? array("is_active" => true) : GFVtigerData::get_feed($id);

        if(!isset($config["meta"]))
            $config["meta"] = array();

        //updating meta information
        if(rgpost("gf_vtiger_submit")){

            list($client_id, $client_name) = explode("|:|", stripslashes($_POST["gf_vtiger_client"]));
            $config["meta"]["client_id"] = $client_id;
            $config["meta"]["client_name"] = $client_name;

            list($list_id, $list_name) = explode("|:|", stripslashes($_POST["gf_vtiger_list"]));
            $config["meta"]["contact_list_id"] = $list_id;
            $config["meta"]["contact_list_name"] = $list_name;
            $config["form_id"] = absint($_POST["gf_vtiger_form"]);

            $merge_vars = self::get_custom_fields($list_id);
            $field_map = array();
            foreach($merge_vars as $var){
                $field_name = "vtiger_map_field_" . self::get_field_key($var);
                $mapped_field = stripslashes($_POST[$field_name]);
                if(!empty($mapped_field))
                    $field_map[self::get_field_key($var)] = $mapped_field;
            }
            $config["meta"]["field_map"] = $field_map;
            $config["meta"]["resubscribe"] = rgpost("vtiger_resubscribe") ? true : false;

            $config["meta"]["optin_enabled"] = rgpost("vtiger_optin_enable") ? true : false;
            if($config["meta"]["optin_enabled"]){
                $config["meta"]["optin_field_id"] = rgpost("vtiger_optin_field_id");
                $config["meta"]["optin_operator"] = rgpost("vtiger_optin_operator");
                $config["meta"]["optin_value"] = rgpost("vtiger_optin_value");
            }

            $is_valid = !empty($field_map["email"]);
            if($is_valid){
                $id = GFVtigerData::update_feed($id, $config["form_id"], $config["is_active"], $config["meta"]);
                ?>
                <div class="updated fade" style="padding:6px"><?php echo sprintf(__("Feed Updated. %sback to list%s", "gravityforms-vtiger"), "<a href='?page=gf_vtiger'>", "</a>") ?></div>
                <input type="hidden" name="vtiger_setting_id" value="<?php echo $id ?>"/>
                <?php
            }
            else{
                ?>
                <div class="error" style="padding:6px"><?php echo __("Feed could not be updated. Please enter all required information below.", "gravityforms-vtiger") ?></div>
                <?php
            }
        }

        if(empty($merge_vars)){
            //getting merge vars from selected list (if one was selected)
            $merge_vars = empty($config["meta"]["contact_list_id"]) ? array() : self::get_custom_fields($config["meta"]["contact_list_id"]);
        }
        ?>

        <form method="post" action="">
            <input type="hidden" name="vtiger_setting_id" value="<?php echo $id ?>"/>


            <div id="vtiger_form_container" valign="top" class="margin_vertical_10" >
                <label for="gf_vtiger_form" class="left_header"><?php _e("Gravity Form", "gravityforms-vtiger"); ?> <?php gform_tooltip("vtiger_gravity_form") ?></label>

                <select id="gf_vtiger_form" name="gf_vtiger_form" onchange="SelectForm(jQuery(this).val());">
                <option value=""><?php _e("Select a Form", "gravityforms-vtiger"); ?></option>
                <?php
                $forms = RGFormsModel::get_forms();
                foreach($forms as $form){
                    $selected = absint($form->id) == rgar($config, "form_id") ? "selected='selected'" : "";
                    ?>
                    <option value="<?php echo absint($form->id) ?>"  <?php echo $selected ?>><?php echo esc_html($form->title) ?></option>
                    <?php
                }
                ?>
                </select>
                &nbsp;&nbsp;
                <img src="<?php echo self::get_base_url() ?>/images/loading.gif" id="vtiger_wait_form" style="display: none;"/>
            </div>

            <div id="vtiger_field_group" valign="top" <?php echo empty($config["form_id"]) ? "style='display:none;'" : "" ?>>

                <div id="vtiger_field_container" valign="top" class="margin_vertical_10" >
                    <label for="vtiger_fields" class="left_header"><?php _e("Map Fields", "gravityforms-vtiger"); ?> <?php gform_tooltip("vtiger_map_fields") ?></label>

                    <div id="vtiger_field_list">
                    <?php
                    if(!empty($config["form_id"])){

                        //getting field map UI
                        echo self::get_field_mapping($config, $config["form_id"], $merge_vars);

                        //getting list of selection fields to be used by the optin
                        $form_meta = RGFormsModel::get_form_meta($config["form_id"]);
                    }
                    ?>
                    </div>
                </div>
<?php /* Optin condition
                <div id="vtiger_optin_container" valign="top" class="margin_vertical_10">
                    <label for="vtiger_optin" class="left_header"><?php _e("Opt-In Condition", "gravityforms-vtiger"); ?> <?php gform_tooltip("vtiger_optin_condition") ?></label>
                    <div id="vtiger_optin">
                        <table>
                            <tr>
                                <td>
                                    <input type="checkbox" id="vtiger_optin_enable" name="vtiger_optin_enable" value="1" onclick="if(this.checked){jQuery('#vtiger_optin_condition_field_container').show('slow');} else{jQuery('#vtiger_optin_condition_field_container').hide('slow');}" <?php echo rgar($config["meta"],"optin_enabled") ? "checked='checked'" : ""?>/>
                                    <label for="vtiger_optin_enable"><?php _e("Enable", "gravityforms-vtiger"); ?></label>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div id="vtiger_optin_condition_field_container" <?php echo !rgar($config["meta"],"optin_enabled") ? "style='display:none'" : ""?>>
                                        <div id="vtiger_optin_condition_fields" style="display:none">
                                            <?php _e("Export to vtiger if ", "gravityforms-vtiger") ?>
                                            <select id="vtiger_optin_field_id" name="vtiger_optin_field_id" class='optin_select' onchange='jQuery("#vtiger_optin_value_container").html(GetFieldValues(jQuery(this).val(), "", 20));'></select>
                                            <select id="vtiger_optin_operator" name="vtiger_optin_operator">
                                                <option value="is" <?php echo rgar($config["meta"],"optin_operator") == "is" ? "selected='selected'" : "" ?>><?php _e("is", "gravityforms-vtiger") ?></option>
                                                <option value="isnot" <?php echo rgar($config["meta"],"optin_operator") == "isnot" ? "selected='selected'" : "" ?>><?php _e("is not", "gravityforms-vtiger") ?></option>
                                                <option value=">" <?php echo rgar($config['meta'], 'optin_operator') == ">" ? "selected='selected'" : "" ?>><?php _e("greater than", "gravityforms-vtiger") ?></option>
                                                <option value="<" <?php echo rgar($config['meta'], 'optin_operator') == "<" ? "selected='selected'" : "" ?>><?php _e("less than", "gravityforms-vtiger") ?></option>
                                                <option value="contains" <?php echo rgar($config['meta'], 'optin_operator') == "contains" ? "selected='selected'" : "" ?>><?php _e("contains", "gravityforms-vtiger") ?></option>
                                                <option value="starts_with" <?php echo rgar($config['meta'], 'optin_operator') == "starts_with" ? "selected='selected'" : "" ?>><?php _e("starts with", "gravityforms-vtiger") ?></option>
                                                <option value="ends_with" <?php echo rgar($config['meta'], 'optin_operator') == "ends_with" ? "selected='selected'" : "" ?>><?php _e("ends with", "gravityforms-vtiger") ?></option>
                                            </select>
                                            <div id="vtiger_optin_value_container" name="vtiger_optin_value_container" style="display:inline;"></div>
                                        </div>
                                        <div id="vtiger_optin_condition_message" style="display:none">
                                            <?php _e("To create an Opt-In condition, your form must have a field supported by conditional logic.", "gravityform") ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div> */?>

                    <script type="text/javascript">
                        <?php
                        if(!empty($config["form_id"])){
                            ?>
                            //creating Javascript form object
                            form = <?php echo GFCommon::json_encode($form_meta)?>;

                            //initializing drop downs
                            jQuery(document).ready(function(){
                                var selectedField = "<?php echo str_replace('"', '\"', rgar($config["meta"], "optin_field_id")) ?>";
                                var selectedValue = "<?php echo str_replace('"', '\"', rgar($config["meta"], "optin_value")) ?>";
                                SetOptin(selectedField, selectedValue);
                            });
                        <?php
                        }
                        ?>
                    </script>
                </div>


                <div id="vtiger_submit_container" class="margin_vertical_10">
                    <input type="submit" name="gf_vtiger_submit" value="<?php echo empty($id) ? __("Save", "gravityforms-vtiger") : __("Update", "gravityforms-vtiger"); ?>" class="button-primary"/>
                    <input type="button" value="<?php _e("Cancel", "gravityforms-vtiger"); ?>" class="button" onclick="javascript:document.location='admin.php?page=gf_vtiger'" />
                </div>
            </div>
        </form>
        </div>
        <script type="text/javascript">

            function SelectForm(formId){
                if(!formId){
                    jQuery("#vtiger_field_group").slideUp();
                    return;
                }

                jQuery("#vtiger_wait_form").show();
                jQuery("#vtiger_field_group").slideUp();

                var mysack = new sack(ajaxurl);
                mysack.execute = 1;
                mysack.method = 'POST';
                mysack.setVar( "action", "gf_select_vtiger_form" );
                mysack.setVar( "gf_select_vtiger_form", "<?php echo wp_create_nonce("gf_select_vtiger_form") ?>" );
                mysack.setVar( "form_id", formId);
                mysack.encVar( "cookie", document.cookie, false );
                mysack.onError = function() {jQuery("#vtiger_wait_form").hide(); alert('<?php _e("Ajax error while selecting a form", "gravityforms-vtiger") ?>' )};
                mysack.runAJAX();

                return true;
            }

            function SetOptin(selectedField, selectedValue){

                //load form fields
                jQuery("#vtiger_optin_field_id").html(GetSelectableFields(selectedField, 20));

                var optinConditionField = jQuery("#vtiger_optin_field_id").val();

                if(optinConditionField){
                    jQuery("#vtiger_optin_condition_message").hide();
                    jQuery("#vtiger_optin_condition_fields").show();

                    jQuery("#vtiger_optin_value_container").html(GetFieldValues(optinConditionField, selectedValue, 20));
					jQuery("#vtiger_optin_value").val(selectedValue);
                }
                else{
                    jQuery("#vtiger_optin_condition_message").show();
                    jQuery("#vtiger_optin_condition_fields").hide();
                }

            }

            function EndSelectForm(fieldList, form_meta){

                //setting global form object
                form = form_meta;

                if(fieldList){

                    SetOptin("","");

                    jQuery("#vtiger_field_list").html(fieldList);
                    jQuery("#vtiger_field_group").slideDown();

                }
                else{
                    jQuery("#vtiger_field_group").slideUp();
                    jQuery("#vtiger_field_list").html("");
                }
                jQuery("#vtiger_wait_form").hide();
            }

            function GetFieldValues(fieldId, selectedValue, labelMaxCharacters){
                if(!fieldId)
                    return "";

                var str = "";
                var field = GetFieldById(fieldId);
                if(!field)
                    return "";

                var isAnySelected = false;

                if(field["type"] == "post_category" && field["displayAllCategories"]){
					str += '<?php $dd = wp_dropdown_categories(array("class"=>"optin_select", "orderby"=> "name", "id"=> "vtiger_optin_value", "name"=> "vtiger_optin_value", "hierarchical"=>true, "hide_empty"=>0, "echo"=>false)); echo str_replace("\n","", str_replace("'","\\'",$dd)); ?>';
				}
				else if(field.choices){
					str += '<select id="vtiger_optin_value" name="vtiger_optin_value" class="optin_select">'
	                for(var i=0; i<field.choices.length; i++){
	                    var fieldValue = field.choices[i].value ? field.choices[i].value : field.choices[i].text;
	                    var isSelected = fieldValue == selectedValue;
	                    var selected = isSelected ? "selected='selected'" : "";
	                    if(isSelected)
	                        isAnySelected = true;

	                    str += "<option value='" + fieldValue.replace(/'/g, "&#039;") + "' " + selected + ">" + TruncateMiddle(field.choices[i].text, labelMaxCharacters) + "</option>";
	                }

	                if(!isAnySelected && selectedValue){
	                    str += "<option value='" + selectedValue.replace("'", "&#039;") + "' selected='selected'>" + TruncateMiddle(selectedValue, labelMaxCharacters) + "</option>";
	                }
	                str += "</select>";
				}
				else
				{
					selectedValue = selectedValue ? selectedValue.replace(/'/g, "&#039;") : "";
					//create a text field for fields that don't have choices (i.e text, textarea, number, email, etc...)
					str += "<input type='text' placeholder='<?php _e("Enter value", "gravityforms"); ?>' id='vtiger_optin_value' name='vtiger_optin_value' value='" + selectedValue.replace(/'/g, "&#039;") + "'>";
				}

                return str;
            }

            function GetFieldById(fieldId){
                for(var i=0; i<form.fields.length; i++){
                    if(form.fields[i].id == fieldId)
                        return form.fields[i];
                }
                return null;
            }

            function TruncateMiddle(text, maxCharacters){
                if(text.length <= maxCharacters)
                    return text;
                var middle = parseInt(maxCharacters / 2);
                return text.substr(0, middle) + "..." + text.substr(text.length - middle, middle);
            }

            function GetSelectableFields(selectedFieldId, labelMaxCharacters){
                var str = "";
                var inputType;
                for(var i=0; i<form.fields.length; i++){
                    fieldLabel = form.fields[i].adminLabel ? form.fields[i].adminLabel : form.fields[i].label;
                    inputType = form.fields[i].inputType ? form.fields[i].inputType : form.fields[i].type;
                    if (IsConditionalLogicField(form.fields[i])) {
                        var selected = form.fields[i].id == selectedFieldId ? "selected='selected'" : "";
                        str += "<option value='" + form.fields[i].id + "' " + selected + ">" + TruncateMiddle(fieldLabel, labelMaxCharacters) + "</option>";
                    }
                }
                return str;
            }

            function IsConditionalLogicField(field){
			    inputType = field.inputType ? field.inputType : field.type;
			    var supported_fields = ["checkbox", "radio", "select", "text", "website", "textarea", "email", "hidden", "number", "phone", "multiselect", "post_title",
			                            "post_tags", "post_custom_field", "post_content", "post_excerpt"];

			    var index = jQuery.inArray(inputType, supported_fields);

			    return index >= 0;
			}

        </script>

        <?php

    }

    private static function get_custom_fields($list_id){
        self::include_api();
        $api = new CS_REST_Lists($list_id, self::get_api_key());

        //getting list of all Campaign Monitor merge variables for the selected contact list
        self::log_debug("Getting custom fields.");
        $response = $api->get_custom_fields();
        if(!$response->was_successful())
        {
        	self::log_error("Unable to retrieve custom fields from Campaign Monitor.");
            return array();
		}

        $custom_field_objects = $response->response;
        self::log_debug("Custom fields retrieved: " . print_r($custom_field_objects,true));

        $custom_fields = array(array("FieldName" => "Email Address", "Key" => "[email]"), array("FieldName" => "Full Name", "Key" => "[fullname]"));

        foreach($custom_field_objects as $custom_field)
            $custom_fields[] = get_object_vars($custom_field);

        return $custom_fields;
    }


    private static function get_field_key($custom_field){
        $key = str_replace("]", "",str_replace("[", "", $custom_field["Key"]));
        return $key;
    }

    public static function select_form(){

        check_ajax_referer("gf_select_vtiger_form", "gf_select_vtiger_form");
        $form_id =  intval(rgpost("form_id"));

        $setting_id =  intval(rgpost("setting_id"));

        if(!self::is_valid_key())
            die("EndSelectForm();");

        $custom_fields = self::get_custom_fields($list_id);

        //getting configuration
        $config = GFVtigerData::get_feed($setting_id);

        //getting field map UI
        $str = self::get_field_mapping($config, $form_id, $custom_fields);


        //fields meta
        $form = RGFormsModel::get_form_meta($form_id);
        //$fields = $form["fields"];
        die("EndSelectForm(\"$str\", " . GFCommon::json_encode($form) . ");");
    }

    private static function get_field_mapping($config, $form_id, $merge_vars){

        //getting list of all fields for the selected form
        $form_fields = self::get_form_fields($form_id);

        $str = "<table cellpadding='0' cellspacing='0'><tr><td class='vtiger_col_heading'>" . __("List Fields", "gravityforms-vtiger") . "</td><td class='vtiger_col_heading'>" . __("Form Fields", "gravityforms-vtiger") . "</td></tr>";
        foreach($merge_vars as $var){
            $meta = rgar($config, "meta");
            if(!is_array($meta))
                $meta = array("field_map"=>"");

            $selected_field = rgar($meta["field_map"], self::get_field_key($var));
            $required = self::get_field_key($var) == "email" ? "<span class='gfield_required'>*</span> " : "";
            $error_class = self::get_field_key($var) == "email" && empty($selected_field) && !rgempty("gf_vtiger_submit") ? " feeds_validation_error" : "";
            $str .= "<tr class='$error_class'><td class='vtiger_field_cell'>" . esc_html($var["FieldName"]) . " $required</td><td class='vtiger_field_cell'>" . self::get_mapped_field_list(self::get_field_key($var), $selected_field, $form_fields) . "</td></tr>";
        }
        $str .= "</table>";

        return $str;
    }

    public static function get_form_fields($form_id){
        $form = RGFormsModel::get_form_meta($form_id);
        $fields = array();

        //Adding default fields
        array_push($form["fields"],array("id" => "date_created" , "label" => __("Entry Date", "gravityforms-vtiger")));
        array_push($form["fields"],array("id" => "ip" , "label" => __("User IP", "gravityforms-vtiger")));
        array_push($form["fields"],array("id" => "source_url" , "label" => __("Source Url", "gravityforms-vtiger")));

        if(is_array($form["fields"])){
            foreach($form["fields"] as $field){
                if(is_array(rgar($field,"inputs"))){

                    //If this is a name or checkbox field, add full name to the list
                    if(RGFormsModel::get_input_type($field) == "name")
                        $fields[] =  array($field["id"], GFCommon::get_label($field) . " (" . __("Full" , "gravityforms-vtiger") . ")");
                    else if(RGFormsModel::get_input_type($field) == "checkbox")
                        $fields[] =  array($field["id"], GFCommon::get_label($field));

                    foreach($field["inputs"] as $input)
                        $fields[] =  array($input["id"], GFCommon::get_label($field, $input["id"]));
                }
                else if(!rgar($field,"displayOnly")){
                    $fields[] =  array($field["id"], GFCommon::get_label($field));
                }
            }
        }
        return $fields;
    }

    public static function get_mapped_field_list($variable_name, $selected_field, $fields){
        $field_name = "vtiger_map_field_" . esc_attr($variable_name);
        $str = "<select name='$field_name' id='$field_name'><option value=''></option>";
        foreach($fields as $field){
            $field_id = $field[0];
            $field_label = esc_html(GFCommon::truncate_middle($field[1], 40));

            $selected = $field_id == $selected_field ? "selected='selected'" : "";
            $str .= "<option value='" . $field_id . "' ". $selected . ">" . $field_label . "</option>";
        }
        $str .= "</select>";
        return $str;
    }


    public static function export($entry, $form, $is_fulfilled = false){
        if(!self::is_valid_key())
            return;

        $paypal_config = self::get_paypal_config($form["id"], $entry);

        //if configured to only subscribe users when payment is received, delay subscription until the payment is received.
        if($paypal_config && rgar($paypal_config["meta"], "delay_vtiger_subscription") && !$is_fulfilled){
            return;
        }

        //Login to vtiger
        self::login_api_vtiger();

        //loading data class
        require_once(self::get_base_path() . "/data.php");

        //getting all active feeds
        $feeds = GFVtigerData::get_feed_by_form($form["id"], true);
        foreach($feeds as $feed){
            //only export if user has opted in
            if(self::is_optin($form, $feed, $entry)){
                self::export_feed($entry, $form, $feed);
                self::log_debug("Marking entry " . $entry["id"] . " as subscribed");
                //updating meta to indicate this entry has already been subscribed to vtiger. This will be used to prevent duplicate subscriptions.
        		gform_update_meta($entry["id"], "vtiger_is_subscribed", true);
			}
			else{
				self::log_debug("Opt-in condition not met; not subscribing entry " . $entry["id"]);
			}
        }
    }

    public static function export_feed($entry, $form, $feed){

        $resubscribe = $feed["meta"]["resubscribe"] ? true : false;
        $email = $entry[$feed["meta"]["field_map"]["email"]];
        $name = "";
        if(!empty($feed["meta"]["field_map"]["fullname"]))
            $name = self::get_name($entry, $feed["meta"]["field_map"]["fullname"]);

        $merge_vars = array();
        foreach($feed["meta"]["field_map"] as $var_key => $field_id){
            $field = RGFormsModel::get_field($form, $field_id);
            if(GFCommon::is_product_field($field["type"]) && rgar($field, "enablePrice")){
                $ary = explode("|", $entry[$field_id]);
                $product_name = count($ary) > 0 ? $ary[0] : "";
                $merge_vars[] = array("Key" => $var_key, "Value" => $product_name);
            }
            else if(RGFormsModel::get_input_type($field) == "checkbox"){
                foreach($field["inputs"] as $input){
                    $index = (string)$input["id"];
                    if(!rgempty($index, $entry)){
                        $merge_vars[] = array("Key" => $var_key, "Value" => $entry[$index]);
                    }
                }
            }
            else if(!in_array($var_key, array('email', 'fullname'))){
                $merge_vars[] = array("Key" => $var_key, "Value" => $entry[$field_id]);
            }

        }

        $subscriber = array (
              'EmailAddress' => $email,
              'Name' => $name,
              'CustomFields' => $merge_vars,
              'Resubscribe' => $resubscribe
        );

        $api = new CS_REST_Subscribers($feed["meta"]["contact_list_id"], self::get_api_key());
        self::log_debug("Adding subscriber.");
        $api->add($subscriber);
        self::log_debug("Subscriber added.");
    }

    private static function get_name($entry, $field_id){

        //If field is simple (one input), simply return full content
        $name = rgar($entry,$field_id);
        if(!empty($name))
            return $name;

        //Complex field (multiple inputs). Join all pieces and create name
        $prefix = trim(rgar($entry,$field_id . ".2"));
        $first = trim(rgar($entry,$field_id . ".3"));
        $last = trim(rgar($entry,$field_id . ".6"));
        $suffix = trim(rgar($entry,$field_id . ".8"));

        $name = $prefix;
        $name .= !empty($name) && !empty($first) ? " $first" : $first;
        $name .= !empty($name) && !empty($last) ? " $last" : $last;
        $name .= !empty($name) && !empty($suffix) ? " $suffix" : $suffix;
        return $name;
    }

    public static function is_optin($form, $settings, $entry){
        $config = $settings["meta"];
        $field = RGFormsModel::get_field($form, rgar($config,"optin_field_id"));

        if(empty($field) || !$config["optin_enabled"])
            return true;

        $operator = isset($config["optin_operator"]) ? $config["optin_operator"] : "";
        $field_value = RGFormsModel::get_field_value($field, array());
        $is_value_match = RGFormsModel::is_value_match($field_value, rgar($config,"optin_value"), $operator);
        $is_visible = !RGFormsModel::is_field_hidden($form, $field, array(), $entry);
        $is_optin = $is_value_match && $is_visible;

        return $is_optin;
    }

    public static function add_permissions(){
        global $wp_roles;
        $wp_roles->add_cap("administrator", "gravityforms_vtiger");
        $wp_roles->add_cap("administrator", "gravityforms_vtiger_uninstall");
    }

    //Target of Member plugin filter. Provides the plugin with Gravity Forms lists of capabilities
    public static function members_get_capabilities( $caps ) {
        return array_merge($caps, array("gravityforms_vtiger", "gravityforms_vtiger_uninstall"));
    }

    public static function uninstall(){

        //loading data lib
        require_once(self::get_base_path() . "/data.php");

        if(!GFVtiger::has_access("gravityforms_vtiger_uninstall"))
            die(__("You don't have adequate permission to uninstall the vtiger Add-On.", "gravityforms-vtiger"));

        //droping all tables
        GFVtigerData::drop_tables();

        //removing options
        delete_option("gf_vtiger_settings");
        delete_option("gf_vtiger_version");

        //Deactivating plugin
        $plugin = "gravityforms-vtiger/vtiger.php";
        deactivate_plugins($plugin);
        update_option('recently_activated', array($plugin => time()) + (array)get_option('recently_activated'));
    }

    private static function is_valid_key(){
        $result_api = self::login_api_vtiger();;
        return $result_api;
    }

    private static function get_url(){
        $settings = get_option("gf_vtiger_settings");
        $url = $settings["url"];
        return $url;
    }

    private static function get_username(){
        $settings = get_option("gf_vtiger_settings");
        $username = $settings["username"];
        return $username;
    }

    private static function get_password(){
        $settings = get_option("gf_vtiger_settings");
        $password = $settings["password"];
        return $password;
    }


    private static function login_api_vtiger(){

	include_once('includes/WSClient.php');

	$client = new Vtiger_WSClient( self::get_url() );

	$login = $client->doLogin(self::get_username(), self::get_password() );

	if(!$login) {  $login_result = false; } else { $login_result = $login; }

    return $login_result;
    }

    private static function is_gravityforms_installed(){
        return class_exists("RGForms");
    }

    private static function is_gravityforms_supported(){
        if(class_exists("GFCommon")){
            $is_correct_version = version_compare(GFCommon::$version, self::$min_gravityforms_version, ">=");
            return $is_correct_version;
        }
        else{
            return false;
        }
    }

    protected static function has_access($required_permission){
        $has_members_plugin = function_exists('members_get_capabilities');
        $has_access = $has_members_plugin ? current_user_can($required_permission) : current_user_can("level_7");
        if($has_access)
            return $has_members_plugin ? $required_permission : "level_7";
        else
            return false;
    }

    //Returns the url of the plugin's root folder
    protected function get_base_url(){
        return plugins_url(null, __FILE__);
    }

    //Returns the physical path of the plugin's root folder
    protected function get_base_path(){
        $folder = basename(dirname(__FILE__));
        return WP_PLUGIN_DIR . "/" . $folder;
    }



    public static function has_vtiger($form_id){
        if(!class_exists("GFVtigerData"))
            require_once(self::get_base_path() . "/data.php");

        //Getting vtiger settings associated with this form
        $config = GFVtigerData::get_feed_by_form($form_id);

        if(!$config)
            return false;

        return true;
    }

    private static function get_paypal_config($form_id, $entry){
        if(!class_exists('GFPayPal'))
            return false;

        if(method_exists("GFPayPal", "get_config_by_entry")){
            return GFPayPal::get_config_by_entry($entry);
        }
        else{
            return GFPayPal::get_config($form_id);
        }
    }

    function set_logging_supported($plugins)
	{
		$plugins[self::$slug] = "vtiger";
		return $plugins;
	}

	private static function log_error($message){
		if(class_exists("GFLogging"))
		{
			GFLogging::include_logger();
			GFLogging::log_message(self::$slug, $message, KLogger::ERROR);
		}
	}

	private static function log_debug($message){
		if(class_exists("GFLogging"))
		{
			GFLogging::include_logger();
			GFLogging::log_message(self::$slug, $message, KLogger::DEBUG);
		}
	}
}

if(!function_exists("rgget")){
function rgget($name, $array=null){
    if(!isset($array))
        $array = $_GET;

    if(isset($array[$name]))
        return $array[$name];

    return "";
}
}

if(!function_exists("rgpost")){
function rgpost($name, $do_stripslashes=true){
    if(isset($_POST[$name]))
        return $do_stripslashes ? stripslashes_deep($_POST[$name]) : $_POST[$name];

    return "";
}
}

if(!function_exists("rgar")){
function rgar($array, $name){
    if(isset($array[$name]))
        return $array[$name];

    return '';
}
}


if(!function_exists("rgempty")){
function rgempty($name, $array = null){
    if(!$array)
        $array = $_POST;

    $val = rgget($name, $array);
    return empty($val);
}
}


if(!function_exists("rgblank")){
function rgblank($text){
    return empty($text) && strval($text) != "0";
}
}


if(!function_exists("rgobj")){
function rgobj($obj, $name){
    if(isset($obj->$name))
        return $obj->$name;

    return '';
}
}

?>
