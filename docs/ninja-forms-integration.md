# Ninja Forms Integration for FormsCRM

## Overview

FormsCRM now supports Ninja Forms integration, allowing you to seamlessly connect your Ninja Forms submissions directly to your CRM, ERP, or Email Marketing platforms without requiring third-party services.

## Requirements

- WordPress 5.5 or higher
- Ninja Forms plugin (free or premium version)
- FormsCRM plugin version 4.1.0 or higher
- An active CRM connection (Holded, Clientify, AcumbaMail, Brevo, MailerLite, or premium addons)

## How to Setup

### Step 1: Install Required Plugins

1. Install and activate **Ninja Forms** plugin
2. Install and activate **FormsCRM** plugin
3. Verify both plugins are active in WordPress Admin → Plugins

### Step 2: Create or Edit a Ninja Form

1. Navigate to **Ninja Forms** → **Add New** (or edit an existing form)
2. Build your form with the required fields
3. Save your form

### Step 3: Add FormsCRM Action

1. While editing your Ninja Form, click on the **Emails & Actions** tab
2. Click **Add New Action**
3. Select **FormsCRM** from the actions list
4. Configure the FormsCRM action settings:

#### Connection Settings

- **CRM Type**: Select your CRM from the dropdown (Holded, Clientify, AcumbaMail, Brevo, MailerLite, or premium options)
- **CRM URL**: Enter your CRM URL (if required by your CRM)
- **Username**: Enter your CRM username (if required)
- **Password**: Enter your CRM password (if required)
- **API Password**: Enter your CRM API password/token (if required)
- **API Sales**: Enter API Sales identifier (if required by your CRM)
- **Odoo DB**: Enter Odoo database name (if using Odoo)
- **CRM Module**: Enter the module name where data should be sent (e.g., "Leads", "Contacts", "Deals")

#### Field Mapping

After configuring the connection settings:

1. The plugin will automatically connect to your CRM
2. Additional field mapping options will appear
3. Map your Ninja Forms fields to corresponding CRM fields
4. Required fields in the CRM are marked with an asterisk (*)

### Step 4: Enable Expert Mode (Optional)

Enable **Expert Mode** to:
- View all available CRM fields
- Access advanced field mappings
- Connect to custom fields in your CRM

### Step 5: Test Your Form

1. Save your Ninja Form
2. Preview or visit the page with your form
3. Fill out and submit a test entry
4. Check your CRM to verify the lead/contact was created successfully

## Supported CRM Systems

### Free Version
- **Holded**: Contact and lead management
- **Clientify**: Contacts, deals, and opportunities
- **AcumbaMail**: Email marketing contacts
- **MailerLite Classic**: Email marketing subscribers
- **Brevo**: Email marketing and CRM

### Premium Addons
- **Holded Pro**: Advanced features
- **Odoo**: Full ERP integration
- **vTiger 7**: Advanced CRM features
- **PipeDrive**: Sales pipeline management
- **Inmovilla**: Real estate CRM
- **SuiteCRM**: Enterprise CRM
- **FacturaDirecta**: Invoicing system
- **WHMCS**: Web hosting billing

## Field Mapping Tips

### Standard Fields
- Map **Email** fields to ensure proper contact identification
- Map **Name** fields (first name, last name, or full name)
- Map **Phone** fields for contact information
- Map **Company** fields if applicable

### Custom Fields
- Enable **Expert Mode** to access custom fields
- Use exact field names as they appear in your CRM
- Some CRMs support custom field prefixes

### Dynamic Values
FormsCRM supports dynamic field values in certain scenarios. Check the main FormsCRM documentation for advanced field mapping techniques.

## Troubleshooting

### Connection Issues

**Problem**: "Could not login to CRM" error

**Solutions**:
- Verify your CRM credentials are correct
- Check that your CRM API is enabled
- Ensure your website can reach the CRM URL (firewall/network issues)
- Enable WordPress debug mode to see detailed error messages

### Field Mapping Issues

**Problem**: Fields not appearing in mapping

**Solutions**:
- Ensure you've saved the connection settings
- Try enabling **Expert Mode**
- Check that your CRM user has permission to access the module
- Refresh the form editor page

### Submission Issues

**Problem**: Form submits but entry not created in CRM

**Solutions**:
- Check WordPress `debug.log` for error messages
- Verify required CRM fields are mapped
- Check your email for error notifications (sent to admin email)
- Test CRM connection credentials

## Best Practices

1. **Test First**: Always test with sample data before going live
2. **Map Required Fields**: Ensure all required CRM fields are mapped
3. **Use Validation**: Add field validation in Ninja Forms for data quality
4. **Monitor Submissions**: Regularly check CRM to ensure entries are being created
5. **Keep Credentials Safe**: Use environment variables or secure storage for sensitive API keys

## GDPR Compliance

FormsCRM connects directly to your CRM without third-party services:
- Data flows directly from your website to your CRM
- No external data processors involved
- You maintain full control over your data
- Compliant with GDPR requirements for direct data processing

## Support

For issues or questions:
- Visit [FormsCRM Support](https://close.technology/wordpress-plugins/formscrm/)
- Check the [WordPress.org support forum](https://wordpress.org/support/plugin/formscrm/)
- Contact [Close Technology](https://close.marketing/ayuda/)

## Developer Resources

- [Official GitHub Repository](https://github.com/closemarketing/formscrm/)
- [FormsCRM Documentation](https://close.technology/wordpress-plugins/formscrm/)

## Changelog

### Version 4.1.0
- Added full Ninja Forms integration support
- Compatible with Ninja Forms actions system
- Supports all FormsCRM CRM connections
- Field mapping interface integrated with Ninja Forms builder

---

**Note**: This integration is included in FormsCRM at no additional cost. Premium CRM connectors require separate addon purchases.
