# Testing Guide: API Connection Validation

## Manual Testing Steps

### Prerequisites
- WordPress installed and running
- FormsCRM plugin activated
- Access to admin dashboard
- Valid API credentials for at least one CRM

---

## Test Case 1: Successful Connection (Clientify)

### Steps:
1. Navigate to **WordPress Admin > Forms > Settings > FormsCRM**
2. In the "CRM Account Information" section:
   - Select CRM type: **Clientify**
   - Enter a valid **API Password** (get from your Clientify account)
3. Click **Save Settings** button
4. **Expected Result**: 
   - Green success message appears: "Settings saved successfully! API connection test passed."
   - Settings are saved

### Screenshot Location:
Take a screenshot showing the success message

---

## Test Case 2: Invalid API Credentials (Clientify)

### Steps:
1. Navigate to **WordPress Admin > Forms > Settings > FormsCRM**
2. In the "CRM Account Information" section:
   - Select CRM type: **Clientify**
   - Enter an invalid API Password: `invalid-key-12345`
3. Click **Save Settings** button
4. **Expected Result**: 
   - Red error message appears: "Settings saved, but API connection test failed. Error: Could not authenticate with Clientify. Please check your API key."
   - Settings are still saved (so user can fix them)

---

## Test Case 3: Empty API Key (Clientify)

### Steps:
1. Navigate to **WordPress Admin > Forms > Settings > FormsCRM**
2. In the "CRM Account Information" section:
   - Select CRM type: **Clientify**
   - Leave **API Password** field empty
3. Click **Save Settings** button
4. **Expected Result**: 
   - Red error message appears: "Settings saved, but API connection test failed. Error: API Key is required"
   - Settings are saved

---

## Test Case 4: Successful Connection (Holded)

### Steps:
1. Navigate to **WordPress Admin > Forms > Settings > FormsCRM**
2. In the "CRM Account Information" section:
   - Select CRM type: **Holded**
   - Enter a valid **API Password**
3. Click **Save Settings** button
4. **Expected Result**: 
   - Green success message: "Settings saved successfully! API connection test passed."

---

## Test Case 5: Invalid API Credentials (Holded)

### Steps:
1. Navigate to **WordPress Admin > Forms > Settings > FormsCRM**
2. Select CRM type: **Holded**
3. Enter an invalid API Password
4. Click **Save Settings**
5. **Expected Result**: 
   - Error message with Holded-specific error details

---

## Test Case 6: MailerLite Connection

### Steps:
1. Select CRM type: **MailerLite**
2. Enter valid/invalid API key
3. Click **Save Settings**
4. **Expected Result**: 
   - Appropriate success or error message
   - For MailerLite, should test connection to their groups API

---

## Test Case 7: Brevo (Sendinblue) Connection

### Steps:
1. Select CRM type: **Brevo**
2. Enter valid/invalid API key
3. Click **Save Settings**
4. **Expected Result**: 
   - Appropriate success or error message
   - Tests connection to contacts/lists endpoint

---

## Test Case 8: AcumbaMail Connection

### Steps:
1. Select CRM type: **AcumbaMail**
2. Enter valid/invalid API key
3. Click **Save Settings**
4. **Expected Result**: 
   - Appropriate success or error message
   - Tests getLists API call

---

## Test Case 9: Network Error Simulation

### Steps:
1. **Temporarily disable internet connection** OR
2. Add this code to test network errors:
   ```php
   add_filter('pre_http_request', function() {
       return new WP_Error('http_request_failed', 'Network connection failed');
   }, 10, 3);
   ```
3. Try to save settings
4. **Expected Result**: 
   - Error message indicating connection problem

---

## Test Case 10: Backward Compatibility

### Purpose: Ensure old code still works

### Steps:
1. Navigate to **Forms > Forms**
2. Edit an existing form
3. Go to **Settings > FormsCRM**
4. Create a new feed with CRM settings
5. **Expected Result**: 
   - Feed creation works normally
   - Connection status shows in feed editor
   - No PHP errors in logs

---

## Verification Checklist

After running tests, verify:

- [ ] Success messages appear in green/positive style
- [ ] Error messages appear in red/error style
- [ ] Error messages are specific and helpful
- [ ] Settings are always saved (even on connection failure)
- [ ] No PHP warnings or errors in WordPress debug log
- [ ] No JavaScript console errors
- [ ] Works with all 5 CRM types (Clientify, Holded, MailerLite, Brevo, AcumbaMail)
- [ ] Form feeds still work correctly
- [ ] Existing integrations continue to function

---

## Debugging Tips

### Enable WordPress Debug Mode
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Check Debug Log
View: `wp-content/debug.log`

### Check Browser Console
Open Developer Tools (F12) and check Console tab for JavaScript errors

### Check Network Tab
In Developer Tools > Network, check:
- POST request to `admin-ajax.php` or `options.php`
- Response codes (200 = success)
- Response content

---

## Expected Error Messages Reference

| Scenario | Expected Message |
|----------|-----------------|
| Empty API key | "Settings saved, but API connection test failed. Error: API Key is required" |
| Invalid Clientify key | "Settings saved, but API connection test failed. Error: Could not authenticate with Clientify. Please check your API key." |
| Invalid Holded key | "Settings saved, but API connection test failed. Error: Could not authenticate with Holded. Please check your API key." |
| Invalid MailerLite key | "Settings saved, but API connection test failed. Error: Could not authenticate with MailerLite. Please check your API key." |
| Invalid Brevo key | "Settings saved, but API connection test failed. Error: Could not authenticate with Brevo. Please check your API key." |
| Invalid AcumbaMail key | "Settings saved, but API connection test failed. Error: Could not authenticate with AcumbaMail. Please check your API key." |
| Valid credentials | "Settings saved successfully! API connection test passed." |
| Network error | "Settings saved, but API connection test failed. Error: [network error details]" |

---

## Test Data

### Test API Keys (For Testing Only - DO NOT USE IN PRODUCTION)

You'll need to use your own valid API keys for each CRM:

**Clientify:**
- Get from: https://app.clientify.com/settings/integrations
- Format: Token `your-api-key-here`

**Holded:**
- Get from: https://app.holded.com/settings/integrations
- Format: API Key string

**MailerLite:**
- Get from: https://app.mailerlite.com/integrations/api
- Format: API Key string

**Brevo (Sendinblue):**
- Get from: https://app.brevo.com/settings/keys/api
- Format: API Key string

**AcumbaMail:**
- Get from: https://acumbamail.com/api/
- Format: API Key string

---

## Automated Testing

For automated tests, see: `tests/Unit/test-api-connection-validation.php`

Run tests with:
```bash
composer test
```

Or specifically:
```bash
phpunit tests/Unit/test-api-connection-validation.php
```

---

## Reporting Issues

If you find any issues during testing, please note:
1. CRM type being tested
2. API key status (valid/invalid/empty)
3. Actual error message received
4. Expected behavior
5. WordPress version
6. FormsCRM version
7. Debug log entries
8. Screenshots if applicable

