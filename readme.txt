=== FormsCRM ===
Contributors: closemarketing, davidperez, sacrajaimez
Tags: gravityforms, gravity, form, forms, gravity forms, crm, vtiger, sugarcrm
Donate link: https://close.marketing/go/donate/
Requires at least: 4.0
Tested up to: 5.9
Stable tag: 3.7
Version: 3.7
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connects CRM with your Forms plugin and create new Leads/Entries as the forms are filled automatically.

== Description ==
Connects your CRM with the main Form Plugin directly, and send to your CRM when the form is filled automatically.

With this plugin, you don't have to use third party software to send your Leads/data to your CRM. You will have a direct connection between your website and your CRM. It's a connector between Web <> CRM.

This plugin will connect different Forms plugins to CRM. We support at this time these forms plugins:
- [GravityForms](https://close.marketing/likes/gravityforms/)
- [Contact Form 7](https://wordpress.org/plugins/contact-form-7/)
- [WooCommerce](https://wordpress.org/plugins/woocommerce/)
- WPForms (soon!)
- Ninja Forms (soon!)

If you need to support more Forms plugins, please contact in forum support.

The plugin setup is very easy. Once you have uploaded the plugin, you configure the plugin with the URL, user and password of the user that will create the entries in the CRM.

After that, you'll go to each form feed that you want to connect with the CRM. You will see a mapping fields where you choose for every field, the equivalent for CRM software field.

The plugin connects with the CRM via API webservice, a secure and best way to connect it. It *doesn't use a third party software*. You'll comply GDPR becaouse of not having a third provider.

At this time, FormsCRM supports in free version:
- [Holded](https://close.marketing/likes/holded/)
- [Clientify](https://clientify.com/?utm_source=FormsCRM)

And you will find, that there are Premium Addons to support:
- SugarCRM
- Odoo (soon!)
- [vTiger 7](https://en.close.technology/wordpress-plugins/formscrm-vtiger/)
- [PipeDrive](https://en.close.technology/wordpress-plugins/formscrm-pipedrive/)
- [Inmovilla](https://en.close.technology/wordpress-plugins/formscrm-inmovilla/)
- [SuiteCRM](https://en.close.technology/wordpress-plugins/formscrm-suitecrm/)
- [FacturaDirecta](https://en.close.technology/wordpress-plugins/formscrm-facturadirecta/)

You can only use one type of CRM in the web with this version.

Demo:
[youtube https://www.youtube.com/watch?v=HHG763ikL7o]

== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your
WordPress installation and then activate the Plugin from Plugins page.

== Developers ==
[Official Repository GitHub](https://github.com/closemarketing/formscrm/)

== Changelog ==

= 3.7 =
*  New method for clientify visitor key.
*  Fix Clientify pagination Custom fields problem.
*  Better log management (in debug.log).

= 3.6 =
*  Added link to custom Addons: Inmovilla, PipeDrive, SuiteCRM and FacturaDirecta.
*  Clientify: Added custom fields to select in the form.
*	Removed Freemius engine to sell.
*  Added support to shop in close.technology.
*  Fix acceptance consent in Clientify.
*  Fix visitor key in Clientify.

= 3.5.1 =
*	Hotfix: Clientify connector settings error.

= 3.5 =
*	Clientify: adds visitor key from cookie.
*  Better error management.
*  Holded solved fixes.
*  Holded name mandatory.

= 3.4 =
*	Fix is_plugin_activated.
*  Fix translations.
*  Fix tags loaded.

= 3.3 =
*	Support ContactForm7!.
*  Support to WooCommerce!.
*  Clientify connector in Free version.
*  Better error management.

= 3.2 =
*	Support to Clientify in Premium version.

= 3.1.1 =
*	Fixed fatal error.

= 3.1.0 =
*	Updated Settings Page.
*	Added vTiger.

= 3.0.0 =
*	WPORG version and changed name from GravityForms CRM Plugin. Refactured.

== Links ==

*	[Closemarketing](https://close.marketing/)
*	[All Closemarketing Plugins](https://profiles.wordpress.org/closemarketing/#content-plugins)
