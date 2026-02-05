# Error Log Feature - Unit Tests

## Overview

Comprehensive unit tests for the Error Log feature with 30+ test cases covering all functionality.

## Test File Location

`tests/Unit/test-error-log.php`

## Running Tests

### Run All Error Log Tests
```bash
composer test --filter ErrorLogTest
```

### Run with Debugging
```bash
composer test-debug --filter ErrorLogTest
```

### Run Specific Test
```bash
composer test --filter ErrorLogTest::test_insert_log
```

### Run All Plugin Tests
```bash
composer test
```

## Test Coverage

### Database Operations (10 tests)
- ✅ `test_error_log_class_exists` - Verifies Error Log class exists
- ✅ `test_table_creation` - Tests database table creation
- ✅ `test_insert_log` - Tests log insertion returns valid ID
- ✅ `test_get_log` - Tests single log retrieval by ID
- ✅ `test_get_logs_no_filter` - Tests retrieving all logs
- ✅ `test_get_logs_pagination` - Tests pagination (20 per page)
- ✅ `test_get_total_count` - Tests total count without filters
- ✅ `test_update_status_success` - Tests updating status to success
- ✅ `test_update_status_failed` - Tests updating status to failed
- ✅ `test_increment_resend_attempts` - Tests resend counter increment
- ✅ `test_delete_log` - Tests single log deletion
- ✅ `test_clear_all_logs` - Tests clearing all logs

### Filtering & Querying (6 tests)
- ✅ `test_get_logs_status_filter` - Tests filtering by status
- ✅ `test_get_logs_crm_filter` - Tests filtering by CRM type
- ✅ `test_get_total_count_with_filters` - Tests count with filters
- ✅ `test_combined_filters` - Tests multiple filters together
- ✅ `test_logs_ordered_by_date_desc` - Tests descending date order
- ✅ `test_get_logs_pagination` - Tests pagination functionality

### Data Storage & Validation (8 tests)
- ✅ `test_lead_data_json_encoding` - Tests JSON encoding of lead data
- ✅ `test_json_request_storage` - Tests JSON request storage
- ✅ `test_form_information_storage` - Tests form info storage
- ✅ `test_error_date_storage` - Tests error date is set correctly
- ✅ `test_default_status_failed` - Tests default status is "failed"
- ✅ `test_api_url_storage` - Tests API URL storage
- ✅ `test_empty_data_handling` - Tests handling of empty data
- ✅ `test_database_version_option` - Tests version tracking

### Security & Sanitization (2 tests)
- ✅ `test_crm_type_sanitization` - Tests XSS prevention in CRM type
- ✅ `test_error_message_sanitization` - Tests XSS prevention in error message

### Integration Tests (3 tests)
- ✅ `test_alert_error_logs_to_database` - Tests integration with `formscrm_alert_error()`
- ✅ `test_error_log_page_class_exists` - Tests Error Log Page class exists
- ✅ Global `$formscrm_error_log` variable initialization

## Test Structure

Each test follows the standard WordPress unit testing pattern:

```php
public function test_example() {
    // Arrange - Set up test data
    $data = array(...);
    
    // Act - Execute the function being tested
    $result = $this->error_log->insert_log(...);
    
    // Assert - Verify the result
    $this->assertEquals($expected, $result);
}
```

## Test Database

Tests use a separate WordPress test database that is:
- Created automatically during test setup
- Cleaned after each test
- Isolated from production data

## Mocking

HTTP requests are mocked to prevent actual API calls:

```php
add_filter(
    'pre_http_request',
    function( $pre, $r, $url ) {
        return array(
            'body'     => 'ok',
            'response' => array(
                'code'    => 200,
                'message' => 'OK',
            ),
        );
    },
    10,
    3
);
```

## Setup & Teardown

**setUp()**: Runs before each test
- Initializes error log class
- Creates database table
- Clears existing logs
- Sets up HTTP request mocking

**tearDown()**: Runs after each test
- Clears all logs
- Resets test environment

## Test Data Examples

### Basic Log Entry
```php
$log_id = $this->error_log->insert_log(
    'holded',                           // CRM type
    'API connection failed',            // Error message
    array(                              // Lead data
        array('name' => 'Email', 'value' => 'test@example.com')
    ),
    'https://api.holded.com/v1/contacts', // API URL
    '{"test": "data"}',                 // JSON request
    array(                              // Form info
        'form_type' => 'gravityforms',
        'form_id'   => '123'
    )
);
```

## Assertions Used

- `assertEquals()` - Exact value match
- `assertNotEquals()` - Values don't match
- `assertTrue()` - Boolean true check
- `assertFalse()` - Boolean false check
- `assertIsInt()` - Integer type check
- `assertIsArray()` - Array type check
- `assertIsObject()` - Object type check
- `assertCount()` - Array/collection count
- `assertNotNull()` - Not null check
- `assertNull()` - Null check
- `assertGreaterThan()` - Numeric comparison
- `assertStringContainsString()` - String contains
- `assertStringNotContainsString()` - String doesn't contain
- `assertNotFalse()` - Not false check
- `assertNotEmpty()` - Not empty check

## Continuous Integration

Tests are designed to run in CI/CD environments:
- GitHub Actions compatible
- No external dependencies required
- Consistent results across environments

## Test Maintenance

When adding new features:
1. Add corresponding tests
2. Follow existing test naming conventions
3. Include descriptive docblocks
4. Test both success and failure cases
5. Test edge cases (empty data, null values, etc.)

## Troubleshooting

### Database Not Found
```bash
# Reinstall test database
composer test-install
```

### Tests Failing Locally
```bash
# Clear test database and reinstall
rm -rf /tmp/wordpress-tests-lib
composer test-install
```

### Debug Single Test
```bash
# Run with debugging enabled
composer test-debug --filter ErrorLogTest::test_insert_log
```

## Code Coverage

To generate code coverage report:
```bash
# Requires Xdebug
composer test -- --coverage-html coverage/
```

## Performance

All 30+ tests complete in:
- **< 10 seconds** on average
- **< 5 seconds** on fast systems
- Tests are optimized for speed with proper teardown

## Contributing

When adding new tests:
1. Follow existing patterns
2. Add clear docblocks
3. Test both positive and negative cases
4. Clean up test data in tearDown()
5. Use meaningful test names

## Related Documentation

- [Error Log Feature Documentation](error-log-feature.md)
- [Implementation Summary](error-log-implementation-summary.md)
- [Main Plugin Tests](README-TESTS.md)
