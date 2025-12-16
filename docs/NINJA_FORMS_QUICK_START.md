# Ninja Forms + FormsCRM - Quick Start Guide

Get your Ninja Forms connected to your CRM in 5 minutes!

## Prerequisites

✅ WordPress 5.5 or higher
✅ Ninja Forms plugin installed
✅ FormsCRM plugin version 4.1.0+ installed
✅ CRM credentials ready (API key, URL, etc.)

## Step-by-Step Setup

### Step 1: Create Your Form
1. Go to **Ninja Forms** → **Add New**
2. Add your desired fields (Name, Email, Phone, etc.)
3. Click **Publish** to save your form

### Step 2: Add FormsCRM Action
1. In your Ninja Form, click the **Emails & Actions** tab
2. Click **Add New Action**
3. Select **FormsCRM** from the list

### Step 3: Configure CRM Connection
Fill in your CRM details:

#### For Clientify:
```
CRM Type: Clientify
API Password: [Your Clientify API Key]
CRM Module: Contacts
```

#### For Holded:
```
CRM Type: Holded
API Password: [Your Holded API Key]
CRM Module: contacts
```

#### For AcumbaMail:
```
CRM Type: AcumbaMail
API Password: [Your AcumbaMail API Key]
CRM Module: subscribers
```

#### For Brevo (formerly Sendinblue):
```
CRM Type: Brevo
API Password: [Your Brevo API Key]
CRM Module: contacts
```

### Step 4: Map Your Fields
After entering CRM credentials, additional field mapping options will appear:

1. For each CRM field, select the corresponding Ninja Forms field
2. Required fields are marked with *
3. Leave optional fields empty if not needed

**Example Mapping**:
```
CRM Field: email → Form Field: Email
CRM Field: name → Form Field: Name
CRM Field: phone → Form Field: Phone
CRM Field: company → Form Field: Company Name
```

### Step 5: Save & Test
1. Click **Done** to save the action
2. Click **Publish** to save your form
3. Visit your form on the front end
4. Submit a test entry
5. Check your CRM to confirm the entry was created

## Common Field Mappings

### Contact/Lead Fields
| CRM Field | Form Field Type | Example |
|-----------|----------------|---------|
| email | Email | Email Address |
| name | Text | Full Name |
| first_name | Text | First Name |
| last_name | Text | Last Name |
| phone | Phone | Phone Number |
| mobile | Phone | Mobile Phone |
| company | Text | Company Name |
| website | URL | Website |
| address | Address | Street Address |
| city | Text | City |
| state | Select | State/Province |
| postal_code | Text | ZIP/Postal Code |
| country | Select | Country |

### Custom Fields
For custom fields, use the exact field name as it appears in your CRM.

## Troubleshooting

### ❌ "Could not login to CRM"
**Solution**: 
- Double-check your API credentials
- Verify your CRM API is enabled
- Check that there are no typos in the API key

### ❌ Form submits but no entry in CRM
**Solution**:
- Ensure required CRM fields are mapped
- Enable WordPress debug mode to see error messages
- Check your email for error notifications

### ❌ Fields not appearing in mapping
**Solution**:
- Save the connection settings first
- Try enabling "Expert Mode" toggle
- Refresh the form builder page

## Enable Debug Mode

To see detailed error messages:

1. Edit `wp-config.php`
2. Add or update:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```
3. Check `/wp-content/debug.log` for errors

## Advanced Features

### Expert Mode
Enable to access all CRM fields, including custom fields:
1. Toggle **Expert Mode** to ON
2. Additional fields will appear in mapping options

### Multiple Forms
You can add FormsCRM action to multiple forms:
- Each form can connect to a different CRM
- Each form can connect to different modules in the same CRM
- All forms use the same FormsCRM plugin settings

### Field Validation
Add validation in Ninja Forms to ensure data quality:
- Mark fields as required
- Use email validation for email fields
- Use phone validation for phone fields
- Add character limits where appropriate

## CRM-Specific Tips

### Clientify
- Use module "Contacts" for leads
- Use module "Deals" for opportunities
- Cookie tracking is automatic (if Clientify tracking is enabled)

### Holded
- Module names are lowercase: "contacts", "leads"
- Search by email to avoid duplicates
- Tags can be added via custom fields

### AcumbaMail
- Use module "subscribers"
- Lists must exist before mapping
- Double opt-in settings are controlled in AcumbaMail

### Brevo
- API key must have appropriate permissions
- Lists must be created in Brevo first
- Contact attributes must be defined in Brevo

## Testing Checklist

Before going live, test:

- ✅ Form submission works
- ✅ Entry appears in CRM
- ✅ All required fields are filled
- ✅ Email addresses are valid
- ✅ Phone numbers are formatted correctly
- ✅ Custom fields map correctly
- ✅ No error messages in debug.log

## Need Help?

### Documentation
📖 Full documentation: [View ninja-forms-integration.md](ninja-forms-integration.md)

### Support
💬 WordPress.org forum: https://wordpress.org/support/plugin/formscrm/
📧 Email support: https://close.marketing/ayuda/
🌐 Website: https://close.technology/wordpress-plugins/formscrm/

### Video Tutorials
🎥 FormsCRM overview: https://www.youtube.com/watch?v=HHG763ikL7o

## What's Next?

Once your basic integration is working:

1. **Add More Forms**: Connect additional forms to your CRM
2. **Customize Fields**: Map custom fields for richer data
3. **Set Up Notifications**: Configure form success messages
4. **Monitor Submissions**: Regularly check CRM for new entries
5. **Optimize Forms**: Improve conversion rates based on data

## Pro Tips

💡 **Test first**: Always test with sample data before going live
💡 **Map email**: Email is usually required and prevents duplicates
💡 **Use validation**: Ninja Forms validation ensures data quality
💡 **Keep it simple**: Start with basic fields, add complexity later
💡 **Monitor regularly**: Check both WordPress and CRM for issues

---

**Ready to connect more forms?** Install other supported form plugins:
- Gravity Forms
- WPForms PRO  
- Contact Form 7
- Elementor Forms
- WooCommerce

All work with FormsCRM! 🚀
