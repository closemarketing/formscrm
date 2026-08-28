=== FormsCRM - Connect Forms to CRM directly ===
Contributors: closemarketing, davidperez, sacrajaimez, alexbreagarcia, matiasquero
Tags: gravityforms, wpforms, crm, vtiger, odoo
Donate link: https://close.marketing/go/donate/
Requires at least: 5.5
Tested up to: 7.0
Stable tag: 4.4.3
Version: 4.4.3
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connects your CRM, ERP and Email Marketing with your Forms plugin and create new Leads/Entries as the forms are filled automatically. GDPR compliant.

== Description ==
Connects your CRM with the main Form Plugin directly, and send to your CRM when the form is filled automatically.

With this plugin, you don't have to use third party software to send your Leads/data to your CRM. You will have a direct connection between your website and your CRM. It's a connector between Web <> CRM/ERP/Email.

This plugin will connect different Forms plugins to CRM. We support at this time these forms plugins:
- [GravityForms](https://close.marketing/likes/gravityforms/)
- [Elementor Forms](https://elementor.com/pages/form-builder/)
- [Contact Form 7](https://wordpress.org/plugins/contact-form-7/)
- [WooCommerce](https://wordpress.org/plugins/woocommerce/)
- [WPForms PRO](https://close.marketing/likes/wpforms/)
- [JetForms](https://wordpress.org/plugins/jetformbuilder/)
- [Ninja Forms](https://wordpress.org/plugins/ninja-forms/)

If you need to support more Forms plugins, please contact in forum support.

The plugin setup is very easy. Once you have uploaded the plugin, you configure the plugin with the URL, user and password of the user that will create the entries in the CRM.

After that, you'll go to each form feed that you want to connect with the CRM. You will see a mapping fields where you choose for every field, the equivalent for CRM software field.

The plugin connects with the CRM via API webservice, a secure and best way to connect it. It *doesn't use a third party software*. You'll comply GDPR becaouse of not having a third provider.

At this time, FormsCRM supports in free version:
- [Holded](https://close.marketing/likes/holded/)
- [Clientify](https://close.marketing/likes/clientify/)
- [AcumbaMail](https://acumbamail.com/)
- [MailerLite Classic](https://close.marketing/likes/mailerlite/)
- [Brevo](https://brevo.com/)

And you will find, that there are Premium Addons to support:
- [Holded CRM](https://close.technology/wordpress-plugins/formscrm-holded-pro/)
- [Odoo](https://close.technology/en/wordpress-plugins/formscrm-odoo/)
- [vTiger 7](https://close.technology/en/wordpress-plugins/formscrm-vtiger/)
- [PipeDrive](https://close.technology/en/wordpress-plugins/formscrm-pipedrive/)
- [Inmovilla](https://close.technology/en/wordpress-plugins/formscrm-inmovilla/)
- [SuiteCRM](https://close.technology/en/wordpress-plugins/formscrm-suitecrm/)
- [FacturaDirecta](https://close.technology/en/wordpress-plugins/formscrm-facturadirecta/)
- [WHMCS](https://close.technology/en/wordpress-plugins/formscrm-whmcs/)

You can use multiple feed connector in GravityForms, WPForms PRO, Elementor Forms and ContactForm7, and you can use multiple CRM connectors in the same form.

Demo:
[youtube https://www.youtube.com/watch?v=HHG763ikL7o]

** UTM Tracker Addon **

Know exactly where every lead comes from. The [UTM Tracker Addon](https://close.technology/wordpress-plugins/formscrm-utm-tracker/) captures UTM parameters from the URL and automatically attaches them to every form submission sent to your CRM — no hidden fields required.

When a visitor lands on your site with UTM parameters (e.g. `?utm_source=google&utm_medium=cpc&utm_campaign=spring`), the addon:

1. Reads the UTM values from the URL.
2. Stores them in a browser cookie that persists for 90 days across page navigation.
3. Preserves first-touch attribution — if the visitor returns later via a different channel, the original source is kept alongside the latest one.
4. Injects the values directly into the CRM payload when the form is submitted, using merge tags in the field mapping (e.g. `formscrm_utm:utm_source`).

Supported parameters: `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content` — plus `_first` variants for first-touch attribution.

**Dynamic values in GravityForms and WPForms**
We have developed a way to get values from other fields in GravityForms and WPForms. You can use this in the field mapping in the feed. You can use:
{id:N} in order to get the value from field N
{label:N} in order to get the label from field N (only in GravityForms)

**Expert Mode**
You can enable Expert Mode in the form feed. This mode will show all fields of the CRM in the form mapping. This is useful if you want to connect all fields of the CRM to the form. Now currently works for Odoo.

We recommend to use this in the field mapping in the feed and hidden field that gets the value.

== Slack Error Notifications ==

Receive instant error notifications in your Slack workspace! When a form submission fails to send to your CRM, you'll get real-time alerts directly in your Slack channel.

**How to Configure Slack Notifications:**

1. Create an Incoming Webhook in Slack (https://api.slack.com/messaging/webhooks)
2. Go to **Settings > FormsCRM** in WordPress
3. Paste your webhook URL in the "Slack Webhook URL" field
4. Choose the Slack channel where you want to receive notifications
5. Save changes

**What Information is Included:**

When an error occurs, the Slack notification includes:
- **Site Information**: Site name and URL in a single line
- **Form Details**: Form type (Gravity Forms, WPForms, Elementor, etc.), Form ID, Form name, and Entry ID
- **Error Details**: CRM name and complete error message
- **Lead Data Preview**: First 3 fields from the form submission (+ indicator if more fields exist)
- **Technical Details**: API endpoint URL for debugging

**Message Format:**

All Slack notifications use a compact, easy-to-read format with information presented in single lines. Messages are color-coded in red (danger) to stand out in your channel and ensure immediate attention to critical errors.

== Error Notifications ==
**Custom Email for Error Reports**
You can configure a custom email address to receive error notifications when a form submission fails to send to your CRM. This is useful when you want different team members to receive error alerts without using the admin email.

To configure:
1. Go to Settings > FormsCRM
2. Enter one or multiple email addresses (comma-separated) in the "Error Notification Email" field
3. Save changes

**Enhanced Error Email Information**
When an error occurs, you'll receive a detailed email notification that includes:
- **Site Information**: Site name, URL, and timestamp of the error
- **Form Information**: Form type (Gravity Forms, WPForms, Elementor, etc.), Form ID, Form name, and Entry ID
- **Error Details**: CRM name, complete error message, and all form data in a formatted table
- **Technical Details**: API URL and JSON request for debugging purposes

The email is professionally formatted with color-coded sections for easy reading and quick troubleshooting.

== Error Log with Automatic Retry System ==

**Track, Manage, and Automatically Retry Failed Form Submissions**

The Error Log feature provides a comprehensive interface to view, track, and manage all errors that occur when sending form submissions to your CRM. This powerful tool includes an automatic retry system that helps you troubleshoot issues and recover from failed submissions without requiring manual intervention or users to resubmit forms.

**Key Features:**

* **Automatic Retry System**: Failed entries are automatically retried up to 3 times with 1-hour intervals between attempts
* **Smart Retry Management**: Retries stop automatically when an entry is successfully sent or manually deleted
* **Complete Error Tracking**: All errors are automatically saved to the database with complete context including CRM type, error message, form information, lead data, and technical details
* **Advanced Filtering**: Filter errors by status (failed/success) and CRM type to quickly find specific issues
* **Detailed Error Information**: View complete error details including lead data, API URLs, JSON requests, and full error messages
* **One-Click Manual Resend**: Manually resend failed entries directly from the error log with a single click
* **Error Management**: Delete individual entries or clear all logs with confirmation dialogs
* **Pagination**: Navigate through large numbers of error logs with built-in pagination (20 entries per page)
* **Visual Status Tracking**: Status badges show failed and successful entries at a glance
* **Retry Progress Counter**: Shows retry attempts (e.g., "2/3") and displays time until next automatic retry
* **Responsive Design**: Fully responsive interface that works on all devices

**Automatic Retry System:**

When a form submission fails to send to your CRM:

1. The error is logged immediately and the first retry is scheduled for 1 hour later
2. If the retry fails, another retry is scheduled for 1 hour after that
3. This continues for up to 3 total attempts (original submission + 2 retries)
4. If an attempt succeeds, all future retries are automatically cancelled
5. You can manually resend at any time, which counts toward the 3-attempt limit
6. The interface shows the current attempt count (e.g., "1/3", "2/3") and time until next retry

**How to Use:**

1. Go to **WordPress Admin → Settings → FormsCRM → Error Log tab**
2. View all form submission errors in an organized table
3. Filter by status or CRM type to find specific errors
4. Click **Details** to view complete error information including retry schedule
5. Click **Resend** to manually retry sending a failed entry to your CRM
6. Click **Delete** to remove individual log entries and cancel any pending retries
7. Use **Clear All Logs** to remove all entries at once and cancel all pending retries

**What Information is Displayed:**

* Date and time of error
* CRM type (Holded, Clientify, etc.)
* Form information (type, ID, name, entry ID)
* Complete error message
* All lead data from the form submission
* API endpoint URL
* JSON request payload
* Retry attempts count (e.g., "2/3")
* Time until next automatic retry (e.g., "Next: in 45 minutes")
* Last resend date and time

The Error Log with automatic retry system helps you maintain data integrity by ensuring no form submissions are lost due to temporary errors, connectivity issues, or API downtime. The automatic retry mechanism increases the success rate of form submissions without requiring manual intervention.

== Markdown Export for GravityForms Entries ==

**Export your GravityForms entries as portable, human-readable Markdown files**

The Markdown Export feature allows you to export GravityForms entries into clean, well-structured `.md` files. This makes it easy to document, share, version control, or integrate form submissions with knowledge bases, static site generators, or any Markdown-compatible system.

**Key Features:**

* **Single Entry Export**: Export individual entries directly from the entry detail page
* **Bulk Export**: Export multiple selected entries at once as a convenient ZIP file
* **Clean Formatting**: Produces readable, well-structured Markdown with proper headers and field organization
* **Comprehensive Field Support**: Handles all GravityForms field types including text, email, number, textarea, checkboxes, multiselect, name fields, address fields, file uploads, and list fields
* **Smart Content Handling**: Properly formats multi-line content, preserves line breaks, and handles file attachments with Markdown links
* **Metadata Included**: Each export includes form title, entry ID, submission date, and all field labels and values
* **Safe Character Escaping**: Automatically escapes Markdown special characters to ensure valid output

**How to Use:**

**Single Entry Export:**
1. Go to **Forms → Entries** in GravityForms
2. Click on any entry to view its details
3. Find the **Export to Markdown** widget in the right sidebar
4. Click **Download Markdown** to get the `.md` file

**Bulk Export:**
1. Go to **Forms → Entries** in GravityForms
2. Select one or multiple entries using the checkboxes
3. Choose **Export to Markdown** from the bulk actions dropdown
4. Click **Apply** to download a ZIP file containing all selected entries as separate Markdown files

**Exported Markdown Format:**

Each Markdown file includes:
- Form title as the main heading
- Entry ID and submission timestamp
- All filled fields organized in a clean bullet list format
- Field labels in bold with their corresponding values
- Multi-line content properly formatted with preserved line breaks
- File attachments as clickable Markdown links

**Use Cases:**

* Document form submissions for record-keeping
* Share entry data with team members in a readable format
* Version control form submissions using Git or similar tools
* Import entries into knowledge bases or wikis
* Generate reports or documentation from form data
* Backup form entries in a portable, future-proof format
* Integrate with static site generators (Jekyll, Hugo, etc.)

== Settings for Clientify ==
**Important: API v2 Migration**
Since version 4.3.2, FormsCRM uses the Clientify API v2 (api-plus.clientify.com). Your existing API key will continue to work without changes. The migration is fully backward compatible with existing feed configurations.

**Instructions for adding Clientify cookie in the forms**
Clientify cookie adds the ability to merge the contact with the Clientify cookie in the form. You will see if Clientify is added as CRM, a new hidden field in your form. You could check if is already in the form, but if you don't have it you can add it and put as css *clientify_cookie* .

**Add Pipeline name or ID in Opportunities**
You can add a new field that fits with the Pipeline name (pipeline_desc) or Pipeline ID (pipeline_id) in Opportunities in Clientify. You can also specify the Pipeline Stage Name (pipeline_stage_desc). You will need to use the same name or ID as the Pipeline in Clientify.

**Add expected closure date for Deals in Clientify**
You can add a new field that fits with expected closure date for Deals in Clientify. This field is optional, and you need to add a number of days to the expected closure date. The plugin will calculate the date from today and will add it to the Deal in Clientify.

**Marketing Status in Clientify**
You can set the marketing status for contacts using the marketing_status field. Use value 1 for Sales Contact or value 2 for Marketing Contact.

**Autoassignment in Clientify**
Field that applies the autoassignment to the contact. You can add a string with the list of usernames (property emails) separated by comma (,) to apply the autoassignment.

**Webhook in GravityForms**
You can add a new field that fits with the Webhook in GravityForms. This field is optional, and you need to add the webhook url. The plugin will send the form data received from CRM to the webhook url.

== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your
WordPress installation and then activate the Plugin from Plugins page.

== Developers ==
[Official Repository GitHub](https://github.com/closemarketing/formscrm/)

== Changelog ==

= 4.4.3 =
* Added: Native Ninja Forms integration. A "FormsCRM" action becomes available in the Ninja Forms action drawer; the CRM connection is configured once in FormsCRM > Ninja Forms, and each form picks a CRM module and maps its fields using Ninja Forms' own merge tag picker.
* Fixed: Clientify API v1 requests occasionally failing with `HTTP 504 Gateway Timeout` on `api.clientify.net`. All requests (reads, lead/contact creation, updates, deals) now retry once against the `api.clientify.com` fallback before failing; this is a temporary workaround suggested by Clientify support while they investigate the root cause.

= 4.4.2 =
* Added: Holded API v2 support. The API version is now auto-detected from the key's shape (keys prefixed with `pat_` use v2, existing keys keep using v1) — both versions work through the same connector with no new setting.
* Enhanced: "API Connection Status" badge and entry success notes now show the detected Holded API version (e.g. "Connected (Holded v2)") instead of a generic "(Holded)".
* Fixed: `CRMLIB_HOLDED::login()` and `list_modules()` signature mismatch with the `CRMLIB_Abstract` contract, which caused a PHP fatal error on the Holded feed settings page in production.
* Fixed: Holded v2 field names (e.g. `bill_address`, `trade_name`, `is_person`) are now translated from the existing v1 camelCase field IDs, so feeds configured before the v2 migration keep working unmodified.
* Fixed: Clientify API v2 contact merge returning a 409 conflict when matching by `taxpayer_identification_number`; the field is now supported as a search/merge key.
* Fixed: Clientify API v2 now sends `email` inside the `emails` array (type Main) instead of a top-level field, matching the v2 schema.
* Fixed: Clientify API v2 GET requests now always include the required `fields` parameter, even when other query params are already set.
* Tests: Added PHPUnit coverage for both Holded API v1 and v2 using fixtures captured from real sandbox accounts.

= 4.4.1 =
* Fixed: GravityForms `{label:X}` merge tag returning wrong label when select/radio fields have duplicate values.
* Fixed: Merge strategy field not showing correctly.

= 4.4.0 =
* Enhanced: Added support to GravityForms and Clientify to update strategy in the feed. You can choose between update if contact exists or create new contact always.
* Enhanced: Added `CRMLIB_Abstract` base class to unify the CRM library interface (`create_or_update_entry`, `list_fields_search_entry`, `determine_search_by`) across all integrations.
* Enhanced: GravityForms entry notes now include the action taken (created/updated) and the merge strategy field used.
* Fixed: Clientify API version now propagated consistently to deals and products requests instead of using hard-coded strings.
* Fixed: Improved `build_error_message()` to handle `WP_Error` objects and parse standard API error keys (`error`, `detail`, `message`).
* Fixed: Clientify GDPR checkbox value in Contact Form 7 now normalized to '1' (accepted) or '' (not accepted), regardless of the checkbox label language.
* Add a notice to recall to review the plugin FormsCRM.
* Added: Support for JetFormBuilder forms plugin with full field mapping and global settings integration.
* Added: Support to FormsCRM UTM Tracker plugin to send UTM parameters from the cookie to the CRM as merge vars.
* Fixed: API Clientify error not add null values in custom fields.
* Fixed: Error in CF7 value expert.
* Fixed: Login error depending on the CRM, now it shows the error message from the CRM in the settings page.
* Fixed: Elementor not getting correctly the module from settings.
* Fixed: Normalize boolean CRM fields to standardized values for Clientify.
* Fixed: CF7 "Saving..." spinner was always visible because wp_kses strips display:none from inline styles; moved visibility control to CSS class.
* Fixed: Brevo list_modules pagination now returns all lists instead of only 10 by properly sending limit/offset parameters and accumulating paginated results.

= 4.3.3 =
* Added: Smart support for Clientify API v2 and v1 with enhanced login response handling and error messages. 
* Fixed: Elementor Forms field mapping only processed the first occurrence when multiple CRM fields mapped to the same form field; now all mappings are applied correctly.
* Tests: Added unit tests for Elementor and Contact Form 7 merge vars field mapping.
* Fixed: CF7 GDPR checkbox value sent as field name instead of boolean when unchecked, causing gdpr_accept to always be true in Clientify.
* Fixed: Error logs page not working properly.

= 4.3.2 =
*  Migrated: Clientify to API v2 (api-plus.clientify.com/v2): login me/, custom fields object_type filter, deal creation with ID-based refs and inline products (v2 schema).
*  Added: Clientify contact marketing_status, pipeline ID/stage name, and Email Main/Phone Main field types. It defaults to Marketing Contact (2).
*  Fixed: HTTP PUT via wp_remote_request(), consistent wp_remote_retrieve_body() for errors, pipeline_desc mapping.
*  Fixed: GravityForms widget sending leads twice when viewing or editing an entry.
*  Fixed: WPForms > Connections was not working correctly.

= 4.3.1 =
*  Enhanced: Support for GravityPDF merge tags in GravityForms.
*  Fixed: File upload field not sending correctly in GravityForms.
*  Fixed: Brevo duplicate contact error when email already exists. Contacts are now updated instead of returning an error.
*  Fixed: PHP warning when Brevo returns 204 No Content response for updated contacts.

= 4.3.0 =
*  Added: API connection status indicators across all form integrations (GravityForms, WPForms, Elementor, Contact Form 7, WooCommerce).
*  Added: Visual connection status badges with color coding - green (connected), red (error), gray (not configured).
*  Added: Real-time connection validation with detailed error messages when authentication fails.
*  Added: Markdown Export feature for GravityForms entries with single and bulk export capabilities.
*  Added: Export entries as clean, well-structured Markdown files with full field type support.
*  Added: Bulk export creates ZIP file with multiple entry Markdown files for easy sharing.
*  Added: Automatic retry system with up to 3 attempts at 1-hour intervals, visual progress counter, and smart cancellation when entries succeed or are deleted.
*  Added: Error Log feature with comprehensive tracking, filtering by status/CRM, detailed error views, resend capability, and pagination for easy management.
*  Enhanced: Contact Form 7 module selection now auto-saves configuration with visual feedback.
*  Enhanced: Responsive AJAX-based interface with color-coded status badges and synchronized manual/automatic retry system.
*  Enhanced: Feed connection status in Forms list in Gravity Forms.
*  Fixed: Resend button missing in Gravity Forms Entries view.
*  Enhanced: Added feed selector in Resend Entry widget to choose between all feeds or individual feed.
*  Added date conversion in Clientify for birthday field.
*  Hotfix: Error not sending correctly entry id in webhook.

= 4.2.0 =
*  Enhanced: New design for the settings page.
*  Enhanced: Dedicated menu for FormsCRM settings.
*  Improved: Added new tests for more consistent code coverage.
*  Fixed: Fatal error in formscrm_debug_email_lead function.

= 4.1.0 =
*  Enhanced: Complete redesign of the settings page with modern UI and improved UX.
*  Enhanced: New color scheme with cyan-to-purple gradient for better visual appeal.
*  Enhanced: Modern tab navigation system for better organization of settings and license management.
*  Enhanced: Responsive grid layout for forms and CRM integrations display.
*  Enhanced: Improved cards design with hover effects and smooth transitions.
*	 Added: Slack integration for real-time error notifications via Incoming Webhook.
*	 Enhanced: Slack notifications include comprehensive information (site, form, CRM, error, lead preview).
*	 Enhanced: Slack messages use a compact, single-line format for quick scanning.
*	 Enhanced: All form integrations (Gravity Forms, WPForms, Elementor, Contact Form 7, WooCommerce) now include form context in error reports.
*	 Added: 10 comprehensive unit tests for Slack notification functions.
*  Added: Test utility for manually testing Slack notifications (tests/test-slack.php).
*  Added: Custom email option for error notifications - Configure specific emails to receive error reports.
*  Enhanced: Error email notifications now include site information (name, URL, timestamp).
*  Enhanced: Error emails now show detailed form information (type, ID, name, entry ID).
*  Enhanced: Professional HTML email template with color-coded sections for better readability.
*  Enhanced: Complete technical details in error emails (API URL and JSON request) for easier debugging.
*  Improved: All form integrations (Gravity Forms, WPForms, Elementor, Contact Form 7, WooCommerce) now send enhanced error information.

= 4.0.6 =
*  Added: Support Deals tags in Clientify.
*  Fixed: Format of webhook url in GravityForms.
*  Fixed: PHP 7.4 compatibility issues.

= 4.0.5 =
*  Fixed: CF7 custom fields with select fields not sending.
*  Added: Expert Mode.
*  Fixed: Fatal errors in CF7.

= 4.0.4 =
*  Added: Webhook to send form data received from CRM in GravityForms.
*  Added: Automatic tests for robust testing and quality code.

= 4.0.3 =
*  Enhaced: Add Pipeline name to improve Clientify pipeline implementation in Forms.
*  Fixed: Autoassigment in Clientify not added in contacts module.
*  Updated developer dependencies.

= 4.0.2 =
*  Added: Expected closure date for Deals in Clientify.
*  Fixed: Elementor Forms Fields and values not sending.
*  Fixed: Warnings messages in load_plugin_textdomain.

= 4.0.1 =
*  Fixed: Elementor Forms with URL Odoo not working.

= 4.0.0 =
*  Added: New connector for Elementor Forms.
*  Added Brevo Email Marketing.
*  Added field autoassignment_users in Clientify (String with the list of usernames separated by comma (,) to apply the autoassignment).
*  Added Product SKUs in Opportunity in Clientify.
*  Added disclaimer field in Clientify.
*  Added in Clientify different types of Emails: work, personal, other and main. Phones: main, mobile, work, home, fax, other.
*  Added show Login errors from API.

= 3.15.7 =
*  Fixed: Fixed manage contact websites in Clientify.
*  Fixed: Better management of Clientify API errors.

= 3.15.6 =
*  Added: Search contact or lead in Holded by email.

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
