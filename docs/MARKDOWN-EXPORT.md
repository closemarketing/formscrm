# Markdown Export for GravityForms Entries

## Overview

The Markdown Export feature allows users to export GravityForms entries to clean, readable Markdown (.md) files. This enables easier documentation, sharing, versioning, and integration with knowledge bases or static site generators.

## Features

✅ Export single or multiple GravityForms entries  
✅ Clean, well-structured Markdown output  
✅ Handles all GravityForms field types correctly  
✅ Includes entry metadata (ID, submission date, user info)  
✅ Support for special fields (checkboxes, file uploads, textareas)  
✅ Three export modes: All entries, Recent 50, or Selected entries

## Usage

### Accessing the Export Page

1. Navigate to **WordPress Admin > FormsCRM**
2. Click on the **"Export Entries"** tab
3. You'll see the Markdown Export interface

### Exporting Entries

#### 1. Select a Form

Choose the GravityForms form you want to export from the dropdown menu.

#### 2. Choose Export Type

Select one of three export options:

- **All Entries**: Exports all entries from the selected form
- **Recent 50 Entries**: Exports the 50 most recent entries
- **Selected Entries**: Allows you to manually select specific entries to export

#### 3. Select Entries (if applicable)

If you chose "Selected Entries":
1. Wait for the entries list to load
2. Check the boxes next to the entries you want to export
3. Use "Select All" to quickly select all visible entries

#### 4. Export

Click the **"Export to Markdown"** button. Your browser will download a `.md` file with the exported data.

## Output Format

### Single Entry Example

```markdown
# Contact Form

**Entry ID:** 123  
**Submitted at:** 2024-01-15 14:32:00  
**User IP:** 192.168.1.1  

## Fields

- **Name:** John Doe
- **Email:** john@example.com
- **Message:**

  ```
  Hello, this is a test message.
  This message spans multiple lines.
  ```

- **Interests:**
  - Technology
  - Design
  - Marketing
```

### Multiple Entries Example

```markdown
# Contact Form

**Total Entries:** 3  
**Export Date:** 2024-01-15 15:30:00

---

## Entry #123

**Entry ID:** 123  
**Submitted at:** 2024-01-15 14:32:00  
**User IP:** 192.168.1.1  

## Fields

- **Name:** John Doe
- **Email:** john@example.com

---

## Entry #124

**Entry ID:** 124  
**Submitted at:** 2024-01-15 15:10:00  
**User IP:** 192.168.1.2  

## Fields

- **Name:** Jane Smith
- **Email:** jane@example.com

---
```

## Supported Field Types

The Markdown Export feature handles all GravityForms field types:

### Basic Fields
- Text
- Textarea
- Number
- Email
- Website
- Phone
- Date
- Time

### Advanced Fields
- **Checkboxes**: Exported as bulleted lists
- **Radio Buttons**: Shows selected value
- **Select (Dropdown)**: Shows selected value
- **Multi-Select**: Shows all selected values
- **File Upload**: Shows download links to uploaded files
- **Name**: Combines first and last name
- **Address**: Formats full address
- **Section**: Creates markdown heading

### Special Handling

- **Textarea fields**: Wrapped in code blocks for better formatting
- **Long text**: Automatically formatted with proper indentation
- **Empty fields**: Skipped in output
- **HTML/Special characters**: Properly escaped

## Technical Details

### Files Created

- **Class**: `includes/admin/class-markdown-export.php`
- **JavaScript**: `includes/admin/js/markdown-export.js`
- **Styles**: Added to `includes/assets/formscrm-admin.css`

### Hooks & Filters

#### Action Hooks

- `formscrm_markdown_export_content` - Renders the export page
- `wp_ajax_formscrm_get_form_entries` - AJAX handler for loading entries

#### Filter Hooks

- `formscrm_settings_tabs` - Adds the "Export Entries" tab

### Functions

#### Main Class: FORMSCRM_Markdown_Export

**Methods:**

- `render_export_page()` - Renders the export UI
- `handle_export()` - Processes export requests
- `get_entries_for_export()` - Retrieves entries based on criteria
- `generate_markdown()` - Generates markdown content
- `generate_entry_markdown()` - Generates markdown for single entry
- `process_field()` - Processes individual field
- `get_field_value()` - Extracts field value from entry
- `send_markdown_download()` - Sends file for download

### Security

- **Nonce verification**: All requests are verified with WordPress nonces
- **Capability checks**: Only users with `manage_options` can export
- **Data sanitization**: All input data is properly sanitized
- **Output escaping**: Markdown content is properly escaped

## Use Cases

### Documentation
Export form responses to create documentation or reports in Markdown format.

### Version Control
Store exported entries in Git repositories for version tracking.

### Knowledge Bases
Import entries into knowledge base systems that support Markdown.

### Static Site Generators
Use with Jekyll, Hugo, or other static site generators.

### Backup & Archive
Create human-readable backups of form submissions.

### Data Sharing
Share form data in a portable, readable format with team members.

## Limitations

- Export only (no import functionality)
- Maximum 100 entries shown in selection interface (but all can be exported)
- Requires GravityForms plugin to be active
- File uploads show as links (files not embedded in Markdown)

## Future Enhancements

Potential future improvements:

- Bulk ZIP export for multiple forms
- Custom Markdown templates
- Filter by date range
- Export to other formats (JSON, CSV)
- Email export results
- Scheduled automatic exports
- Import from Markdown

## Support

For issues or feature requests, please contact FormsCRM support or visit:
- Website: https://close.technology
- Plugin: https://close.technology/wordpress-plugins/formscrm/

## Changelog

### Version 1.0.0
- Initial release
- Export all entries, recent 50, or selected entries
- Support for all GravityForms field types
- Clean, readable Markdown output
- AJAX-powered entry selection
