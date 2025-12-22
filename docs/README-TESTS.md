# FormsCRM - Unit Tests

## 📋 Table of Contents

- [Installation](#installation)
- [Running Tests](#running-tests)
- [Notification Tests](#notification-tests)
- [Error Log Tests](#error-log-tests)
- [Test Coverage](#test-coverage)

## 🚀 Installation

### Prerequisites

- PHP 7.4 or higher
- Composer
- MySQL/MariaDB

### Install Dependencies

```bash
composer install
```

### Configure WordPress Test Suite

```bash
composer test-install
```

This command will install the WordPress testing environment.

## ▶️ Running Tests

### All Tests

```bash
composer test
```

### Specific Tests

#### Notification Tests

```bash
composer test -- --filter NotificationsTest
```

#### Helper Function Tests

```bash
composer test -- --filter HelpersFunctionsTest
```

#### Error Log Tests

```bash
composer test -- --filter ErrorLogTest
```

### Debug Mode

To run tests with Xdebug enabled:

```bash
composer test-debug -- --filter NotificationsTest
```

## 📧 Notification Tests

The `tests/Unit/test-notifications.php` file contains complete tests for error notification functions.

### Functions Tested

#### 1. `formscrm_alert_error()`

**Tests included:**

- ✅ Function exists
- ✅ Uses custom email when configured
- ✅ Uses admin email by default
- ✅ Subject includes site name
- ✅ Body contains CRM name
- ✅ Body contains form information
- ✅ Body contains site information
- ✅ Body contains lead data
- ✅ Body contains technical details (URL and JSON)

**Execution example:**

```bash
composer test -- --filter test_email_uses_custom_email_when_configured
```

#### 2. `formscrm_send_slack_notification()`

**Tests included:**

- ✅ Function exists
- ✅ Returns false when no webhook is configured
- ✅ Sends notification when webhook is configured
- ✅ Includes site information
- ✅ Includes form information
- ✅ Includes CRM and error
- ✅ Includes lead data preview (first 3 fields)
- ✅ Includes API URL
- ✅ Uses "danger" color (red) for the message
- ✅ Shows "+N more" counter when there are more than 3 fields

**Execution example:**

```bash
composer test -- --filter test_slack_sends_when_webhook_configured
```

### Integration Test

- ✅ Verifies that both email and Slack are sent when both are configured

```bash
composer test -- --filter test_integration_both_email_and_slack_sent
```

## 🗄️ Error Log Tests

The `tests/Unit/test-error-log.php` file contains **30+ complete tests** for Error Log functionality.

📖 **Complete documentation:** See [README-ERROR-LOG-TESTS.md](README-ERROR-LOG-TESTS.md)

### Functions Tested

#### FORMSCRM_Error_Log Class

**Database Operations (10 tests):**
- ✅ Table creation
- ✅ Log insertion
- ✅ Single log retrieval
- ✅ Multiple logs retrieval
- ✅ Pagination (20 per page)
- ✅ Total count
- ✅ Status update
- ✅ Resend attempts increment
- ✅ Single log deletion
- ✅ Clear all logs

**Filtering & Querying (6 tests):**
- ✅ Filter by status (failed/success)
- ✅ Filter by CRM type
- ✅ Combined filters
- ✅ Pagination with filters
- ✅ Count with filters
- ✅ Order by date descending

**Data Storage & Validation (8 tests):**
- ✅ Lead data JSON encoding
- ✅ JSON request storage
- ✅ Form information storage
- ✅ Error date storage
- ✅ Default "failed" status
- ✅ API URL storage
- ✅ Empty data handling
- ✅ Database version tracking

**Security & Sanitization (2 tests):**
- ✅ CRM type sanitization (XSS prevention)
- ✅ Error message sanitization (XSS prevention)

**Integration Tests (3 tests):**
- ✅ Integration with `formscrm_alert_error()`
- ✅ Class existence verification
- ✅ Global variables initialization

**Execution example:**

```bash
# All Error Log tests
composer test -- --filter ErrorLogTest

# Specific test
composer test -- --filter ErrorLogTest::test_insert_log

# With debug
composer test-debug -- --filter ErrorLogTest
```

## 📊 Test Coverage

### Email Notifications

| Feature | Tested |
|---------|--------|
| Custom email | ✅ |
| Default email | ✅ |
| Subject with site name | ✅ |
| Site information | ✅ |
| Form information | ✅ |
| Lead data | ✅ |
| Technical details | ✅ |
| Formatted HTML | ✅ |

### Slack Notifications

| Feature | Tested |
|---------|--------|
| No webhook configured | ✅ |
| With webhook configured | ✅ |
| Site information | ✅ |
| Form information | ✅ |
| CRM and error | ✅ |
| Lead preview | ✅ |
| API URL | ✅ |
| Message color | ✅ |
| Compact format | ✅ |

## 🔧 Test Structure

```
tests/
├── Unit/
│   ├── test-helpers-functions.php  # Helper function tests
│   ├── test-notifications.php      # Notification tests
│   └── test-error-log.php          # Error Log tests (NEW - 30+ tests)
├── API/
│   └── test-clientify.php          # Clientify API tests
├── Forms/
│   └── test-contactform.php        # Contact Form 7 tests
├── Data/
│   └── *.json                      # Test data
└── bootstrap.php                    # Test bootstrap
```

## 🎯 Test Data

Tests use realistic but fictional data:

### Test Email

```php
$data = array(
    array( 'name' => 'Name', 'value' => 'John Doe' ),
    array( 'name' => 'Email', 'value' => 'john@example.com' ),
    array( 'name' => 'Phone', 'value' => '+34 600 123 456' ),
);
```

### Form Configuration

```php
$form_info = array(
    'form_type' => 'Gravity Forms',
    'form_id'   => '42',
    'form_name' => 'Contact Form',
    'entry_id'  => '12345',
);
```

### Slack Webhook (Mock)

```php
$webhook_url = 'https://hooks.slack.com/services/TEST/WEBHOOK/URL';
```

## 🐛 Debugging

### View Test Output

```bash
composer test -- --filter NotificationsTest --debug
```

### View Sent Email Details

Tests capture emails using `tests_retrieve_phpmailer_instance()`:

```php
$mailer = tests_retrieve_phpmailer_instance();
echo $mailer->get_sent()->body; // View email content
```

### View Slack Request

Tests capture HTTP requests:

```php
add_filter(
    'pre_http_request',
    function( $pre, $r, $url ) use ( &$http_request ) {
        $http_request = $r;
        // Examine $http_request['body']
        return array(...);
    },
    20,
    3
);
```

## 📝 Adding New Tests

### Basic Template

```php
public function test_new_functionality() {
    // Arrange - Prepare data.
    $crm = 'Holded';
    $error = 'Test error';
    $data = array(
        array( 'name' => 'Email', 'value' => 'test@example.com' ),
    );

    // Act - Execute function.
    formscrm_alert_error( $crm, $error, $data );

    // Assert - Verify result.
    $mailer = tests_retrieve_phpmailer_instance();
    $this->assertStringContainsString( 'expected', $mailer->get_sent()->body );
}
```

## 🔍 Verify Tests in CI/CD

Tests run automatically in GitHub Actions when pushing or creating pull requests.

See: `.github/workflows/phpunit.yml`

## 📚 Resources

- [WordPress Testing Handbook](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WP_UnitTestCase API](https://developer.wordpress.org/reference/classes/wp_unittestcase/)

## ✅ Pre-Commit Checklist

- [ ] All tests pass: `composer test`
- [ ] No linting errors: `composer lint`
- [ ] PHPStan is clean: `composer phpstan`
- [ ] New tests documented
- [ ] README updated if necessary

## 🤝 Contributing

To add new tests:

1. Create a new file in `tests/Unit/` or add to an existing one
2. Follow naming convention: `test_clear_description`
3. Use the AAA pattern (Arrange, Act, Assert)
4. Clean up state after each test (`tearDown`)
5. Run tests locally before committing

## 📈 Test Summary

| Test Suite | Number of Tests | Status |
|------------|----------------|--------|
| Helpers Functions | 25+ | ✅ Passing |
| Notifications | 10+ | ✅ Passing |
| Error Log | 30+ | ✅ Passing |
| API Clientify | 5+ | ✅ Passing |
| Contact Form 7 | 3+ | ✅ Passing |
| **TOTAL** | **70+** | ✅ **Passing** |

---

**Last Updated**: January 22, 2025  
**Plugin Version**: 4.3.0
