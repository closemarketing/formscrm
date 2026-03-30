# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**FormsCRM** is a WordPress plugin that connects WordPress form plugins to CRM/ERP and Email Marketing systems. It acts as a hub that loads integrations conditionally based on which form plugins and CRMs are active.

- Form integrations: Gravity Forms, Contact Form 7, WPForms, Elementor Pro, WooCommerce
- Built-in CRMs: Clientify, Holded, Brevo, AcumbaMail, MailerLite Classic
- External CRMs (separate plugins): Zoho, Salesforce, Odoo, and others

## Development Commands

All commands run from this directory (`wp-content/plugins/formscrm/`):

| Command | Purpose |
|---|---|
| `composer lint` | Run PHP_CodeSniffer (WordPress coding standards) |
| `composer format` | Auto-fix coding standard issues |
| `composer phpstan` | Run PHPStan static analysis (level 1, `includes/` only) |
| `composer test` | Run PHPUnit integration tests (requires MySQL with `wordpress_test` DB) |
| `composer test-debug` | Run PHPUnit with Xdebug enabled |
| `composer test-install` | Install WordPress test suite and create test DB |
| `npm run wp` | Start WordPress Playground at http://127.0.0.1 (no MySQL needed) |

**Run a single test file:**
```bash
vendor/bin/phpunit tests/Unit/test-helpers-functions.php
```

**PHPUnit test setup** (requires MySQL):
```bash
sudo mysqld_safe &
composer test-install
composer test
```
If tests hang during teardown, use: `timeout 120 vendor/bin/phpunit`

## Plugin Architecture

### Extension Model

The plugin uses WordPress filters as extension points. Built-in CRMs register themselves in `formscrm.php`. External CRM plugins hook into the same filters:

- `formscrm_choices` — adds CRM label/value pairs to the settings dropdown
- `formscrm_crmlib_path` — maps CRM slug to the path of its class file
- `formscrm_dependency_apipassword` — CRMs using API key auth (vs. URL+username+password)
- `formscrm_dependency_url` — CRMs that require a URL field in settings

### CRM Class Interface

Every CRM class (e.g. `CRMLIB_Clientify`) must implement:
- `login( $settings )` — validate credentials, return bool
- `list_modules( $settings )` — return array of CRM modules/lists
- `list_fields( $settings, $module )` — return fields for a given module
- `create_entry( $settings, $module, $data )` — push form data to the CRM

CRM class name convention: `CRMLIB_` + ucfirst(slug), e.g. `CRMLIB_Holded`. The `formscrm_get_api_class( $crm_type )` helper in `helpers-functions.php` handles dynamic loading.

### Form Integration Loading

`includes/formscrm-library/loader.php` checks `is_plugin_active()` for each supported form plugin and conditionally requires the matching class. This means form integration classes are only loaded when their dependency is active.

### Error Log System

`includes/admin/class-error-log.php` — custom DB table (`{prefix}_formscrm_error_log`) storing failed CRM submissions. Provides AJAX handlers for resend, delete, and clear-all. UI rendered by `class-error-log-page.php`. CRM failures must never surface to form submitters — all errors are caught, logged, and can be retried.

## Coding Standards

Enforced by `.phpcs.xml.dist` (WordPress Coding Standards):

- **Tabs** for indentation (never spaces)
- **Yoda conditions** always (`if ( 'value' === $var )`)
- PHP inline comments start with capital letter, end with period
- Global prefixes: `formscrm_`, `FormsCRM_`, `CRMLIB_`, `GFCRM`, `fcrm_`, `FCRM_`
- Text domain: `formscrm`
- Align consecutive `=` assignments vertically with spaces
- **JavaScript**: Vanilla JS only — no jQuery
- PHPStan runs at level 1 against `includes/` using bootstrap at `tests/phpstan-bootstrap.php`

## Tests

- `tests/Unit/` — PHPUnit unit tests (helpers, markdown export, notifications, error log)
- `tests/API/` — PHPUnit API integration tests (Clientify)
- `tests/Forms/` — Form integration tests (CF7, Clientify)
- `tests/Data/` — Mock JSON responses used by tests
- `tests/bootstrap.php` — Test bootstrap (loads WP test suite)

## Key Files

- `formscrm.php` — Plugin entry point; registers built-in CRMs via filters, requires admin and loader
- `includes/formscrm-library/loader.php` — Conditionally loads form integrations
- `includes/formscrm-library/helpers-functions.php` — `formscrm_get_api_class()`, settings helpers, debug logging
- `includes/formscrm-library/helpers-library-crm.php` — Filter wrappers (`formscrm_get_choices()`, `formscrm_get_crmlib_path()`, etc.)
- `includes/admin/class-admin-options.php` — Settings page UI
- `includes/admin/class-error-log.php` — Error log DB operations and AJAX handlers

## CI/CD

- `.github/workflows/php-lint.yml` — PHPCS on PRs
- `.github/workflows/php-test.yml` — PHPUnit on PHP 7.4, 8.1, 8.2, 8.3
- `.github/workflows/deploy.yml` — Deploy to WordPress.org SVN
- Update `readme.txt` changelog for notable changes
- Documentation goes in `/docs/` and must be listed in `.distignore`
