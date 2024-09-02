=== FormsCRM ===
Contributors: closemarketing, davidperez, sacrajaimez
Tags: gravityforms, wpforms, crm, vtiger, odoo
Donate link: https://close.marketing/go/donate/
Requires at least: 5.5
Tested up to: 6.6
Stable tag: 3.15.5
Version: 3.15.5
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connects your CRM, ERP and Email Marketing with your Forms plugin and create new Leads/Entries as the forms are filled automatically.

== Description ==
Connects your CRM with the main Form Plugin directly, and send to your CRM when the form is filled automatically.

With this plugin, you don't have to use third party software to send your Leads/data to your CRM. You will have a direct connection between your website and your CRM. It's a connector between Web <> CRM/ERP/Email.

This plugin will connect different Forms plugins to CRM. We support at this time these forms plugins:
- [GravityForms](https://close.marketing/likes/gravityforms/)
- [Contact Form 7](https://wordpress.org/plugins/contact-form-7/)
- [WooCommerce](https://wordpress.org/plugins/woocommerce/)
- [WPForms PRO](https://close.marketing/likes/wpforms/)

If you need to support more Forms plugins, please contact in forum support.

The plugin setup is very easy. Once you have uploaded the plugin, you configure the plugin with the URL, user and password of the user that will create the entries in the CRM.

After that, you'll go to each form feed that you want to connect with the CRM. You will see a mapping fields where you choose for every field, the equivalent for CRM software field.

The plugin connects with the CRM via API webservice, a secure and best way to connect it. It *doesn't use a third party software*. You'll comply GDPR becaouse of not having a third provider.

At this time, FormsCRM supports in free version:
- [Holded](https://close.marketing/likes/holded/)
- [Clientify](https://close.marketing/likes/clientify/)
- [AcumbaMail](https://acumbamail.com/)
- [MailerLite Classic](https://close.marketing/likes/mailerlite/)

And you will find, that there are Premium Addons to support:
- [Holded CRM](https://close.technology/wordpress-plugins/formscrm-holded-pro/)
- [Odoo](https://close.technology/en/wordpress-plugins/formscrm-odoo/)
- [vTiger 7](https://close.technology/en/wordpress-plugins/formscrm-vtiger/)
- [PipeDrive](https://close.technology/en/wordpress-plugins/formscrm-pipedrive/)
- [Inmovilla](https://close.technology/en/wordpress-plugins/formscrm-inmovilla/)
- [SuiteCRM](https://close.technology/en/wordpress-plugins/formscrm-suitecrm/)
- [FacturaDirecta](https://close.technology/en/wordpress-plugins/formscrm-facturadirecta/)
- [WHMCS](https://close.technology/en/wordpress-plugins/formscrm-whmcs/)

You can use multiple feed connector in GravityForms, WPForms PRO and ContactForm7, and you can use multiple CRM connectors in the same form.

Demo:
[youtube https://www.youtube.com/watch?v=HHG763ikL7o]

**Instructions for adding Clientify cookie in the forms**

Clientify cookie adds the ability to merge the contact with the Clientify cookie in the form. You will see if Clientify is added as CRM, a new hidden field in your form. You could check if is already in the form, but if you don't have it you can add it and put as css *clientify_cookie* .

**Dynamic values in GravityForms and WPForms**
We have developed a way to get values from other fields in GravityForms and WPForms. You can use this in the field mapping in the feed. You can use:
{id:N} in order to get the value from field N
{label:N} in order to get the label from field N (only in GravityForms)

We recommend to use this in the field mapping in the feed and hidden field that gets the value.

== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your
WordPress installation and then activate the Plugin from Plugins page.

== Developers ==
[Official Repository GitHub](https://github.com/closemarketing/formscrm/)

== Changelog ==
= 3.15.5 =
*  Added: Dynamic values in WPForms.

= 3.15.4 =
*  Fixed: Connection WPForms field date was not formatting to YYYY-MM-DD.

= 3.15.3 =
*  Added: Logs connection and entry created with WPForms in the form entry always.

= 3.15.2 =
*  Fixed: Sometimes gives Fatal error in WooCommerce Settings page.

= 3.15.1 =
*  Added: Support to multiple feeds in GravityForms for Clientify.
*  Fixed: Sometimes we were getting an error in the feed.
*  Fixed: MailerLite Classic multiple pagination API. When you have more thatn 100 entries, it will get all entries.
*  Fixed: Fatal error not authenticating in WooCommerce library.

= 3.15.0 =
*  Added: New widget in GravityForms Entries to resend the lead to CRM.
*  Added: New field Disclaimer in Clientify.
*  Fixed: Prevents possible errors in admin CF7.

= 3.14.0 =
*  Fixed: Custom fields not send to Opportunities in Clientify.
*  Fixed: Error Clientify not detecting module.
*  Added: Internal testing for Clientify.

= 3.13.3 =
*  Fixed image in settings.

= 3.13.2 =
*  Removed Odoo part not necessary in CF7.

= 3.13.1 =
*  Fix not launching autoupdate.

= 3.13.0 =
*  Added: CF7 now allows you to select the fields defined in the form.
*  Fix: Clientify changed names of custom fields in API. Now it's importing custom fields correctly.

= 3.12.4 =
*  Fix: Prevents error in error message GF send.

= 3.12.3 =
*  Added: CF7 now allows values by default.
*  Fix: CF7 error after sending a lead.

= 3.12.2 =
*  Fix: Holded tags where not importing correctly.
*  Fix: Holded Address fields where not importing correctly.

= 3.12.0 =
*  Fix: Clientify does not allow blanks in tags.
*  Fix: Odoo creation contact.

= 3.11.0 =
*  Module Clientify now supports Deals.
*  Added: Conditional logic for Feed in Gravity Forms.
*  Fix: Woocommerce Mailerlite gets activated.

= 3.10.0 =
*  Added: Support to MailerLite.
*  Custom CRMs connector by feed in Gravity Forms.

= 3.9.2 =
*  Fix: better information in Error debug email.

= 3.9.1 =
*  Fix: checkbox and files urls in dynamic values in GravityForms.

= 3.9.0 =
*  Compose Dynamic values from other fields in GravityForms. Use {id:##} or {label:##}.
*  Minor fixes and translations.

= 3.8.2 =
*  Fix Error fields in CF7.

= 3.8.1 =
*  Fix Error module in CF7.

= 3.8.0 =
*  Added WPForms PRO as new forms provider.
*  Async create lead in GravityForms.
*  Fix: GravityForms not getting Full name.
*  Fix: Multistep APIs.
*  Fix: CF7 deprecated error after submit.
*  Fix: Clientify adds Address fields.
*  Fix: Get Clientify Cookie in WooCommerce.
*  Fix: Added Holded contact fields.
*  Added unit tests: better consistency.

= 3.7.3 =
*  Fix: Error 500 in page ContactForm7.

= 3.7.2 =
*  Fix: Parse error: syntax error, unexpected ‘)’.
*  Reviewed in PHP5.6.

= 3.7.1 =
*  Fix: Parse error: syntax error, unexpected ‘)’.

= 3.7 =
*  New method for clientify visitor key.
*  Fix Clientify pagination Custom fields problem.
*  Better log management (in debug.log).
*  Added link to Odoo premium addon.
*  Added AcumbaMail in free version.
*  Fix: Clientify custom fields not imported.
*  Fix: Fatal error in feed admin if not selected CRM.

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
*	[Closetechnology](https://close.technology/)
*	[All Closemarketing Plugins](https://profiles.wordpress.org/closemarketing/#content-plugins)
