# Migrate Clientify Integration from API v1 to API v2

## Summary

Migrate the existing Clientify CRM integration from the legacy API v1 (`https://api.clientify.net/v1/`) to the new API v2 documented at https://newapi.clientify.com/#c4871c63-5032-4ad8-94e5-3b0f14106d4d

## Motivation

Clientify has released a new version of their public API (v2). The current integration relies entirely on v1 endpoints which may be deprecated in the future. Migrating ensures continued compatibility, access to new features, and long-term support.

## Current v1 Implementation

### Base URL
```
https://api.clientify.net/v1/
```

### Authentication
- Header: `Authorization: Token {apikey}`
- Credential stored in: `fc_crm_apipassword`

### Endpoints Currently Used

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `settings/my-account/` | GET | Login / credential validation |
| `custom-fields/` | GET | Fetch custom fields (paginated) |
| `contacts` | POST | Create contact |
| `companies` | POST | Create company |
| `deals` | POST | Create deal |
| `deals/{id}/tags/` | POST | Add tags to a deal |
| `deals/{id}/products/` | PUT | Add products to a deal |
| `products/?sku={sku}` | GET | Lookup product by SKU |

### Files Affected

| File | Description |
|------|-------------|
| `includes/crm-library/class-crmlib-clientify.php` | Core API library (all HTTP calls, authentication, CRUD) |
| `includes/formscrm-library/class-forms-clientify.php` | Clientify-specific form integrations (visitor key, cookie fields) |
| `includes/formscrm-library/helpers-library-crm.php` | Visitor key session handling |
| `includes/formscrm-library/js/clientify-field.js` | Frontend JS for visitor key cookie |
| `formscrm.php` | Clientify registration (CRM choices, credential dependencies) |
| `tests/API/test-clientify.php` | PHPUnit tests |
| `tests/Data/clientify-login.json` | Mock login response |
| `tests/Data/clientify-custom-fields.json` | Mock custom fields response |
| `tests/clientify.php` | Manual API test script |

### Current Data Structures

**Contact payload:**
- Standard fields: `first_name`, `last_name`, `email`, `phone`, `company`, `status`, `tags`, etc.
- `custom_fields`: `[{"field": "field_name", "value": "..."}]`
- `emails`: `[{"type": 1|2|3, "email": "..."}]` (1=Work, 2=Personal, 3=Other)
- `phones`: `[{"type": 2|3|4|5|6, "phone": "..."}]`
- `websites`: `[{"type": 1-5, "website": "..."}]`
- `addresses`: `[{"street", "city", "state", "country", "postal_code", "type"}]`
- Boolean fields: `gdpr_accept`, `disclaimer`
- Date fields: `birthday` (normalized to `YYYY-MM-DD`)

**Deal payload:**
- `contact` or `company`: URL reference (e.g. `https://api.clientify.net/v1/contacts/{id}/`)
- `name`, `amount`, `expected_closed_date`, `pipeline`, `custom_fields`
- Tags via separate `POST deals/{id}/tags/`
- Products via separate `PUT deals/{id}/products/`

## Migration Tasks

### 1. Review API v2 Documentation
- [ ] Thoroughly review the new API v2 documentation at https://newapi.clientify.com/#c4871c63-5032-4ad8-94e5-3b0f14106d4d
- [ ] Document all endpoint changes (URLs, methods, request/response formats)
- [ ] Identify authentication changes (Token vs Bearer, new headers, OAuth, etc.)
- [ ] Identify new/removed/renamed fields
- [ ] Identify pagination changes (current v1 uses `next`/`previous` with `results` array)
- [ ] Check rate limiting changes

### 2. Update Base URL and Authentication (`class-crmlib-clientify.php`)
- [ ] Update base URL from `https://api.clientify.net/v1/` to the v2 base URL
- [ ] Update authentication headers if the format has changed
- [ ] Update the `get()` method with new pagination format if changed
- [ ] Update the `request()` method — **fix existing bug**: currently uses `wp_remote_post()` for all methods including PUT; should use `wp_remote_request()` with proper `method` parameter

### 3. Update Login / Validation (`login()`)
- [ ] Update the login endpoint (currently `settings/my-account/`)
- [ ] Update response validation logic (currently checks `$get_result['data']['count'] > 0`)

### 4. Update Custom Fields Retrieval (`list_fields()`)
- [ ] Update custom fields endpoint (currently `custom-fields/`)
- [ ] Adapt to new response format if `content_type` field naming has changed
- [ ] Verify field types mapping still works (`field_type`, `field_type_display`)

### 5. Update Contact Creation (`create_entry()`)
- [ ] Update contacts endpoint (currently `POST contacts`)
- [ ] Adapt contact payload structure to v2 format
- [ ] Verify `emails`, `phones`, `websites`, `addresses` array structures
- [ ] Verify `custom_fields` format
- [ ] Verify `tags` handling
- [ ] Verify boolean fields (`gdpr_accept`, `disclaimer`)
- [ ] Verify date field format (`birthday`)
- [ ] Check if `visitor_key` handling has changed

### 6. Update Company Creation
- [ ] Update companies endpoint (currently `POST companies`)
- [ ] Adapt company payload structure to v2 format

### 7. Update Deal Creation and Related Operations
- [ ] Update deals endpoint (currently `POST deals`)
- [ ] Update deal reference format — currently uses full URL (`https://api.clientify.net/v1/contacts/{id}/`), verify if v2 uses IDs directly
- [ ] Update deal tags endpoint (currently `POST deals/{id}/tags/`)
- [ ] Update deal products endpoint (currently `PUT deals/{id}/products/`)
- [ ] Update product lookup by SKU (currently `GET products/?sku={sku}`)
- [ ] Fix `deal|pipeline_desc` vs `deal|pipeline_name` mismatch in field mapping

### 8. Update Modules List (`list_modules()`)
- [ ] Verify available modules in v2 (Contacts, Companies, Deals)
- [ ] Check if new modules have been added

### 9. Fix Existing Known Bugs During Migration
- [ ] **PUT method bug**: `request()` uses `wp_remote_post()` for all HTTP methods. Change to `wp_remote_request()` for proper PUT/PATCH support
- [ ] **Error body access**: Line ~53-54 in `get()` accesses `$result_api['body']` directly instead of using `wp_remote_retrieve_body($result_api)`
- [ ] **Pipeline field mismatch**: `list_fields()` exposes `deal|pipeline_desc` but `create_entry()` only handles `deal|pipeline_name`

### 10. Update Tests
- [ ] Update mock response files (`tests/Data/clientify-login.json`, `tests/Data/clientify-custom-fields.json`) to match v2 response format
- [ ] Update test assertions if response structure changes
- [ ] Add new tests for any new v2-specific functionality
- [ ] Update manual test script (`tests/clientify.php`)
- [ ] Add tests for the fixed bugs (PUT method, error handling)

### 11. Update Visitor Key / Cookie Handling
- [ ] Verify if Clientify's visitor tracking (`vk` cookie) mechanism has changed in v2
- [ ] Update `helpers-library-crm.php` if session variable handling changes
- [ ] Update `js/clientify-field.js` if cookie name or format changes

### 12. Backwards Compatibility
- [ ] Consider if existing users need a migration path or if v2 is a drop-in replacement
- [ ] Ensure existing feed configurations (`fc_crm_apipassword`, `fc_crm_module`) remain compatible
- [ ] If API key format changes, provide clear admin notice and migration instructions
- [ ] Test with all form integrations (Gravity Forms, CF7, WPForms, Elementor, WooCommerce)

## Acceptance Criteria

- [ ] All current Clientify functionality works with the new API v2
- [ ] Login/authentication validates correctly against v2
- [ ] Custom fields are fetched and displayed correctly in field mapping UI
- [ ] Contact creation works with all field types (standard, custom, emails, phones, addresses, websites)
- [ ] Company creation works correctly
- [ ] Deal creation works (with contact/company association, tags, and products)
- [ ] Visitor key tracking continues to work
- [ ] All PHPUnit tests pass with updated mock data
- [ ] No regressions in existing form plugin integrations (GF, CF7, WPForms, Elementor, WooCommerce)
- [ ] Error handling and logging work correctly
- [ ] Retry mechanism (`formscrm_retry_failed_entry`) works with new endpoints
- [ ] Existing bugs (PUT method, error body access, pipeline mismatch) are fixed
- [ ] `composer lint` and `composer phpstan` pass

## API v2 Documentation Reference

https://newapi.clientify.com/#c4871c63-5032-4ad8-94e5-3b0f14106d4d

## Labels

`enhancement`, `api-migration`, `clientify`
