=== Gravity Forms CRM Addon ===
Contributors: closemarketing
Tags: gravityforms, gravity, form, forms, gravity forms, crm, vtiger, sugarcrm
Requires at least: 4.0
Tested up to: 4.6.2
Stable tag: 2.6.3
Version: 2.6.3

Gravity Forms CRM Addon allows you to connect different CRM and create new Leads as the forms are filled automatically.

== Description ==
Gravity Forms is the best way to use forms in your web, becouse it has much power, flexibilty and functionality than CRM HTML Forms. But with this plugin you can connect every entry that is filled directly to the CRM.

The plugin setup is very easy. Once you have uploaded the plugin, you configure the plugin with the URL, user and password of the user that will create the entries in the CRM.

After that, you go to each form that you want to connect with the CRM. You will see a mapping fields where you choose for every field, the equivalent for CRM software field.

The plugin connects with the CRM via webservice, a secure and best way to connect it.

At this time, CRM Software that are supported are: vTiger SugarCRM

You can only use one type of CRM in the web with this version.

We will support more CRM, you can comment which CRM you would like to use, and we will consider in next versions.

Translations: Spanish.

== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your
WordPress installation and then activate the Plugin from Plugins page.

== Changelog ==
= 3.0 =
*   Rewritten libraries for support modules (not all crm)
*   Added support to OFIWEB, Holded,

= 2.6.3 =
*   Fix rare chars.

= 2.6.2 =
*   Fix MsDynamics leadid
*   Automatic updater implemented.
*   Solved CRM bug with &.

= 2.6.1 =
*   vTiger now sends error creating lead.
*   New method to connect for MS Dynamics.

= 2.6 =
*   Added support to Hubspot.

= 2.5 =
*   Emails to administrator when it cannot create the lead in CRM with error message.

= 2.4.2 =
*   Fixed problems with MSDynamics.

= 2.4.1 =
*   Better debug messages Bitrix.

= 2.4 =
*   Fixed error with facturadirecta.
*   Better support for SuiteCRM (two libraries connection).

= 2.3 =
*   Better manage multiselect and checkbox fields.
*   Internal better management of Libraries.
*   Fixed bug textarea with line breaks does not send to CRM.
*   Fixed bug Bitrix URL connection.

= 2.2 =
*   Server check system.
*   Support to FacturaDirecta.
*   Support to Odoo 9.
*   Solved problems with vTiger description.

= 2.1 =
*	Added connection with Solve360 CRM.

= 2.0 =
*	Added connection with Bitrix24 CRM.
*	Minor fixes with SugarCRM7 not mandatory Teams.

= 1.9 =
*	Added MS Dynamics on Premise. Use it if MS Dynamics Online does not work for you.
*	Solved fixes connection to SugarCRM.
*	Solved fixes with Odoo map function.

= 1.8.1 =
*	Finally solved connection with MS Dynamics. Problems from MS Dynamics API library.

= 1.8 =
*	Added connection with Salesforce.

= 1.7 =
*	Added connection with Zoho CRM.
*	New debug mode to show vars and errors when Wordpress Debug mode is activated.
* Added POT File for translations.

= 1.6.1 =
*	Fixed connection with MS Dynamics.

= 1.6.0 =
*	Added connection with ESPO CRM.

= 1.5.0 =
*	Added connection with SugarCRM 7.

= 1.4.0 =
*	Added connection with VTE CRM.
*	Cleaned methods for better support to CRMs.
*	Login issue with SugarCRM.

= 1.3.1 =
*	Password issue with SuiteCRM.

= 1.3.0 =
*	Added Microsoft Dynamics CRM 2015.

= 1.2.0 =
*	Added SuiteCRM.

= 1.1.3 =
*	Solving conflicts with trailing slash.

= 1.1.2 =
*	Solving problems with SugarCRM.

= 1.1.1 =
*	Bugfixes (warnings).

= 1.1 =
*	Support to Odoo 8.
*	Updated translation in Spanish.
*	Change Settings input depending of CRM.

= 1.0.2 =
* Handle vTiger errors
*	Manage vTiger API errors in Feed Settings page.

= 1.0.1 =
* Some fixes after submit form
* WP Updates automatically

= 1.0.0 =
*	First released, Publish Version with vTiger and SugarCRM.


== Links ==

*	[Closemarketing](https://www.closemarketing.es/)


== Closemarketing plugins ==

*	[Send SMS to WordPress Users via Arsys](http://wordpress.org/plugins/send-sms-arsys/)
*	[Clean HTML Code in the Editor](http://wordpress.org/plugins/clean-html/)
