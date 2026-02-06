# Markdown Export for GravityForms Entries

## Overview

This feature allows users to export GravityForms entries as clean, well-structured Markdown (`.md`) files. The implementation provides both single entry export and bulk export capabilities, making it easy to document, share, and version control form submissions.

## Architecture

### Main Class

`FormsCRM_GravityForms_Markdown_Export` - Located in `includes/formscrm-library/class-gravityforms-markdown-export.php`

This class handles all export functionality and integrates with GravityForms through WordPress hooks and filters.

### Integration Points

The feature integrates with GravityForms in three ways:

1. **Bulk Actions**: Adds "Export to Markdown" option to the entry list bulk actions dropdown
2. **Entry Detail Meta Box**: Adds export widget to individual entry detail pages
3. **Query Parameter Handler**: Processes export requests via `admin_init` hook

## Key Features

### 1. Single Entry Export

Users can export individual entries from the entry detail page:
- Widget appears in the right sidebar
- One-click download as `.md` file
- Filename format: `{form-title}-entry-{entry-id}.md`

### 2. Bulk Export

Users can export multiple entries at once:
- Select entries using checkboxes in the entry list
- Choose "Export to Markdown" from bulk actions
- Downloads as ZIP file containing all selected entries
- Filename format: `{form-title}-entries-{timestamp}.zip`

### 3. Field Type Support

The export handles all GravityForms field types:

- **Text Fields**: email, text, number, phone, website, etc.
- **Checkbox**: Combines all selected values with commas
- **Multiselect**: Formats multiple selections
- **Name**: Combines first and last name
- **Address**: Combines street, city, state, ZIP
- **File Upload**: Creates Markdown links to files
- **List**: Formats as bulleted list
- **Textarea**: Preserves line breaks and formatting
- **Post Content**: Preserves line breaks

### 4. Markdown Structure

Each exported file includes:

```markdown
# {Form Title}

**Entry ID:** {entry_id}  
**Submitted at:** {date_time}

## Fields

- **Field Label:** Field Value
- **Another Field:** Value
```

### 5. Character Escaping

The implementation properly escapes Markdown special characters:
- Backslashes, asterisks, underscores, brackets, parentheses
- Hash symbols, plus signs, minus signs, dots, exclamation marks
- File links are preserved and not escaped

## Code Structure

### Public Methods

- `add_bulk_action( $actions, $form_id )` - Adds bulk action to entry list
- `process_bulk_export( $action, $entries, $form_id )` - Processes bulk export
- `add_export_metabox( $meta_boxes, $entry, $form )` - Adds meta box to entry detail
- `render_export_metabox( $args )` - Renders meta box content
- `handle_single_export()` - Handles single entry export request

### Private Methods

- `generate_markdown( $entry, $form )` - Generates Markdown content for an entry
- `get_field_value( $entry, $field, $form )` - Extracts field value based on field type
- `format_field_markdown( $label, $value, $type )` - Formats field as Markdown
- `escape_markdown( $text )` - Escapes Markdown special characters
- `generate_filename( $entry, $form )` - Creates sanitized filename
- `force_download_markdown( $content, $filename )` - Forces Markdown file download
- `create_zip_export( $entry_ids, $form )` - Creates ZIP file for bulk export
- `download_file( $filepath )` - Forces file download and cleanup

## Security

The implementation includes proper security measures:

1. **Nonce Verification**: All export requests are verified with nonces
2. **Capability Check**: Verifies user has `gravityforms_view_entries` capability
3. **Input Sanitization**: All user inputs are properly sanitized
4. **Output Escaping**: Markdown content is properly escaped

## File Storage

Temporary files for bulk export are stored in:
`wp-content/uploads/formscrm-exports/`

Files are automatically deleted after download.

## Testing

Unit tests are located in `tests/Unit/test-markdown-export.php`

The test suite covers:
- Filename generation
- Character escaping
- Field value extraction for all field types
- Markdown formatting
- Filter integration

Run tests with:
```bash
composer test -- --filter=MarkdownExportTest
```

## Usage Examples

### Single Entry Export

1. Navigate to Forms → Entries
2. Click on any entry
3. Find "Export to Markdown" widget in sidebar
4. Click "Download Markdown"

### Bulk Export

1. Navigate to Forms → Entries
2. Select one or more entries
3. Choose "Export to Markdown" from bulk actions
4. Click "Apply"
5. Download ZIP file

## Future Enhancements

Potential improvements for future versions:

1. **Custom Templates**: Allow users to define custom Markdown templates
2. **Field Filtering**: Option to exclude certain fields from export
3. **Markdown Styles**: Support for different Markdown flavors (GitHub, CommonMark)
4. **Scheduled Exports**: Automatic periodic exports
5. **Cloud Storage Integration**: Direct upload to Dropbox, Google Drive, etc.
6. **Entry Filtering**: Export based on date range, status, or custom criteria

## Changelog

### Version 4.3.0
- Initial release of Markdown Export feature
- Support for all GravityForms field types
- Single and bulk export capabilities
- ZIP file generation for bulk exports
- Comprehensive unit test coverage
