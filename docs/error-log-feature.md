# Error Log Feature Documentation

## Overview

The Error Log feature has been successfully implemented for the FormsCRM plugin. This feature captures all errors that occur during the "send alert" function and provides administrators with a comprehensive interface to view, manage, and resend failed form submissions.

## Features Implemented

### 1. Database Table for Error Logs

**File:** `includes/admin/class-error-log.php`

- Created a custom database table `wp_formscrm_error_log` to store error information
- Table includes the following fields:
  - `id`: Unique identifier
  - `error_date`: Timestamp of the error
  - `crm_type`: Type of CRM (Holded, Clientify, etc.)
  - `error_message`: Full error message
  - `form_type`: Type of form (Gravity Forms, WPForms, etc.)
  - `form_id`: Form ID
  - `form_name`: Form name
  - `entry_id`: Entry ID from the form
  - `lead_data`: JSON-encoded lead data
  - `api_url`: API endpoint URL
  - `json_request`: JSON request sent to the CRM
  - `status`: Status (failed, success)
  - `resend_attempts`: Number of resend attempts
  - `last_resend_date`: Date of last resend attempt

### 2. Error Logging Integration

**File:** `includes/formscrm-library/helpers-functions.php`

- Modified the `formscrm_alert_error()` function to automatically save errors to the database
- Error logging happens before sending email and Slack notifications
- All error data is captured including form information and technical details

### 3. Error Log Admin Page

**File:** `includes/admin/class-error-log-page.php`

Features include:
- **Filters**: Filter errors by status (failed/success) and CRM type
- **Pagination**: Display 20 entries per page with full pagination support
- **Error Details**: Expandable rows showing complete error information
- **Statistics**: Display total error count

The page shows:
- Date and time of error
- CRM type
- Form information (name, type, ID)
- Truncated error message
- Status badge (color-coded)
- Number of resend attempts
- Action buttons (Resend, Details, Delete)

### 4. Resend Functionality

**Implementation:**
- Resend button for each error log entry
- Uses AJAX to resend the entry without page reload
- Retrieves the CRM API class dynamically
- Attempts to resend with the saved lead data
- Updates status and attempt count in the database
- Provides user feedback on success or failure

### 5. AJAX Handlers

**File:** `includes/admin/class-error-log.php`

Three AJAX handlers implemented:
1. **`formscrm_resend_entry`**: Resends a failed entry to the CRM
2. **`formscrm_delete_log`**: Deletes a single log entry
3. **`formscrm_clear_all_logs`**: Clears all log entries (with confirmation)

### 6. JavaScript Interface

**File:** `includes/admin/js/error-log.js`

Features:
- Toggle details view for each error
- Resend functionality with loading state
- Delete individual logs with confirmation
- Clear all logs with confirmation
- Automatic page reload when necessary
- Visual feedback during operations

### 7. Enhanced Styling

**File:** `includes/assets/formscrm-admin.css`

Added styles for:
- Error log table with responsive design
- Status badges (color-coded for failed/success)
- Action buttons (primary, secondary, danger, small)
- Details expansion rows
- Filters and pagination
- Mobile-responsive layout

### 8. New Admin Tab

**File:** `includes/admin/class-admin-options.php`

- Added "Error Log" tab to the FormsCRM settings page
- Tab integrates seamlessly with existing settings interface
- Follows plugin's design patterns

## File Structure

```
formscrm/
├── formscrm.php (updated - includes new classes)
├── includes/
│   ├── admin/
│   │   ├── class-admin-options.php (updated - added Error Log tab)
│   │   ├── class-error-log.php (new - database operations)
│   │   ├── class-error-log-page.php (new - admin page rendering)
│   │   └── js/
│   │       └── error-log.js (new - AJAX handling)
│   ├── assets/
│   │   └── formscrm-admin.css (updated - error log styles)
│   └── formscrm-library/
│       └── helpers-functions.php (updated - error logging)
└── docs/
    └── error-log-feature.md (this file)
```

## Usage

### For Administrators

1. **Accessing Error Logs:**
   - Navigate to WordPress Admin → FormsCRM
   - Click on the "Error Log" tab

2. **Filtering Errors:**
   - Use the status dropdown to filter by failed or successful entries
   - Use the CRM dropdown to filter by specific CRM type
   - Click "Filter" to apply filters or "Reset" to clear them

3. **Viewing Error Details:**
   - Click the "Details" button to expand full error information
   - View lead data, technical details, and full error message

4. **Resending Failed Entries:**
   - Click the "Resend" button for any failed entry
   - The system will attempt to resend the data to the CRM
   - Status updates automatically on success

5. **Managing Logs:**
   - Click "Delete" to remove individual log entries
   - Click "Clear All Logs" to remove all entries (requires confirmation)

### For Developers

**Accessing the error log programmatically:**

```php
global $formscrm_error_log;

// Get logs with filters
$logs = $formscrm_error_log->get_logs( array(
    'per_page' => 20,
    'page'     => 1,
    'status'   => 'failed',
    'crm_type' => 'holded',
) );

// Insert a new log
$log_id = $formscrm_error_log->insert_log(
    $crm,
    $error,
    $data,
    $url,
    $json,
    $form_info
);

// Update status
$formscrm_error_log->update_status( $log_id, 'success' );
```

## Database Schema

The error log table is automatically created when the plugin loads. The table version is tracked in the `formscrm_error_log_db_version` option.

## Security Features

1. **Nonce verification** for all AJAX requests
2. **Capability checks** (`manage_options`) for admin operations
3. **Data sanitization** for all user inputs
4. **Prepared SQL statements** to prevent SQL injection
5. **Escaped output** to prevent XSS attacks

## Compatibility

- **WordPress**: 5.0+
- **PHP**: 7.4+
- **Mobile**: Fully responsive design
- **Browsers**: Modern browsers (Chrome, Firefox, Safari, Edge)

## Performance Considerations

1. **Pagination**: Limits database queries to 20 records per page
2. **Indexes**: Added indexes on frequently queried columns (crm_type, status, error_date)
3. **AJAX**: Non-blocking operations for resend and delete actions
4. **CSS**: Minimal additional CSS overhead

## Future Enhancements (Optional)

- Export error logs to CSV
- Automatic retry for failed entries
- Email notifications for specific CRM errors
- Error statistics dashboard
- Bulk resend functionality
- Search functionality for error messages

## Testing

### Automated Tests

The Error Log feature includes **30+ comprehensive unit tests**.

📖 **Test Documentation:** [README-ERROR-LOG-TESTS.md](README-ERROR-LOG-TESTS.md)

Run tests:
```bash
composer test --filter ErrorLogTest
```

### Manual Testing Checklist

- [ ] Database table creation
- [ ] Error logging on form submission failure
- [ ] Error Log tab displays correctly
- [ ] Filters work properly (status, CRM type)
- [ ] Pagination works correctly
- [ ] Details expansion shows full information
- [ ] Resend functionality works
- [ ] Delete individual logs
- [ ] Clear all logs
- [ ] Mobile responsive design
- [ ] No JavaScript console errors
- [ ] No PHP errors in debug log

## Documentation

- **Feature Guide**: [error-log-feature.md](error-log-feature.md) (this file)
- **Implementation Summary**: [error-log-implementation-summary.md](error-log-implementation-summary.md)
- **Test Documentation**: [README-ERROR-LOG-TESTS.md](README-ERROR-LOG-TESTS.md)
- **Changelog**: [CHANGELOG-4.3.0.md](CHANGELOG-4.3.0.md)

## Support

For issues or questions about this feature:
- GitHub: [Plugin Repository](https://github.com/closemarketing/formscrm/)
- Email: david@closemarketing.es
- Website: https://close.technology
- WordPress Support: https://wordpress.org/support/plugin/formscrm/
