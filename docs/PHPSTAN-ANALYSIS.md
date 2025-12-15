# PHPStan Static Analysis Report

## Overview

PHPStan has been configured at **Level 6** to detect potential issues and improve code robustness. This analysis found **154 errors** that should be addressed to improve code quality, type safety, and maintainability.

## Configuration

- **PHPStan Level**: 6 (out of 9)
- **Files Analyzed**: 15
- **Errors Found**: 154
- **Configuration File**: `phpstan.neon.dist`

## Error Categories

### 1. Missing Array Type Hints (Most Common)

**Issue**: Methods and functions use `array` type without specifying what values the array contains.

**Example**:
```php
// Current (Level 6 Error)
public function list_modules( $settings ): array {
    // ...
}

// Fixed
/**
 * @param array<string, mixed> $settings
 * @return array<string, string>
 */
public function list_modules( array $settings ): array {
    // ...
}
```

**Files Affected**:
- `includes/crm-library/class-crmlib-acumbamail.php`
- `includes/crm-library/class-crmlib-brevo.php`
- `includes/crm-library/class-crmlib-clientify.php`
- `includes/crm-library/class-crmlib-holded.php`
- `includes/crm-library/class-crmlib-mailerlite.php`
- `includes/formscrm-library/class-contactform7.php`
- `includes/formscrm-library/class-woocommerce.php`
- `includes/formscrm-library/helpers-functions.php`
- `includes/formscrm-library/helpers-library-crm.php`

### 2. Return Type Mismatches

**Issue**: Methods declare one return type but return a different type.

**Example**:
```php
// Current (Error)
public function login( $settings ): false {
    if ( $valid ) {
        return true; // Error: should return false
    }
}

// Fixed
public function login( array $settings ): bool {
    if ( $valid ) {
        return true;
    }
    return false;
}
```

**Occurrences**: 7
- `CRMLIB_AcumbaMail::login()` (line 114)
- `CRMLIB_Brevo::login()` (line 121)
- `CRMLIB_Clientify::login()` (line 143)
- `CRMLIB_HOLDED::login()` (line 157)
- `CRMLIB_Mailerlite::login()` (line 118)
- `CRMLIB_Brevo::api()` (line 36)
- `CRMLIB_Mailerlite::api()` (line 34)

### 3. Parameter Type Mismatches

**Issue**: Methods receive different types than their parameters expect.

**Example**:
```php
// Current (Error at line 163)
public function post( string $url, string $method, array $data ): array { }

// Called with wrong type:
$this->post( $url, 'POST', array( 'id' => 123 ) ); // $data should be string

// Fixed - Either change method signature or fix calls
public function post( string $url, string $method, string|array $data ): array {
    if ( is_array( $data ) ) {
        $data = json_encode( $data );
    }
    // ...
}
```

**Files Affected**:
- `class-crmlib-acumbamail.php` (lines 36-37, 163, 211, 222)
- `class-crmlib-clientify.php` (lines 881, 913, 933)
- `class-crmlib-holded.php` (line 407)

### 4. Unknown Class Types

**Issue**: Using `obj` as a type, which PHPStan doesn't recognize.

**Example**:
```php
// Current (Error)
/** @var obj */
private $crmlib;

// Fixed - Use actual class name or interface
/** @var CRMLIB_Interface */
private $crmlib;

// Or create an interface:
interface CRMLIB_Interface {
    public function login( array $settings ): bool;
    public function list_modules( array $settings ): array;
    public function list_fields( array $settings ): array;
    public function create_entry( array $merge_vars, array $settings ): array;
}
```

**Files Affected**:
- `class-contactform7.php` (property line 31, multiple method calls)
- `class-woocommerce.php` (property line 30, multiple method calls)

### 5. Unreachable Code

**Issue**: Code that can never be executed.

**Example**:
```php
// Current (Error at line 149)
if ( empty( $result ) ) {
    return array(); // This always returns
}
$processed = process_result( $result ); // Unreachable!

// Fixed
if ( empty( $result ) ) {
    return array();
}

$processed = process_result( $result ); // Now reachable
```

**Occurrences**: 2
- `class-crmlib-brevo.php` (line 149)
- `helpers-functions.php` (line 400)

### 6. Always True/False Conditions

**Issue**: Conditions that always evaluate the same way.

**Example**:
```php
// Current (Error at line 302)
if ( isset( $array['url'] ) && ! empty( $something ) ) {
    // 'url' key always exists, so isset() is always true
}

// Fixed
if ( ! empty( $array['url'] ) && ! empty( $something ) ) {
    // Only check the value, not existence
}
```

**Files Affected**:
- `class-admin-options.php` (lines 302, 308)
- `class-crmlib-brevo.php` (line 144)
- `class-crmlib-holded.php` (line 397)
- `class-crmlib-mailerlite.php` (line 141)
- `class-woocommerce.php` (line 319)

### 7. Invalid Type Definitions

**Issue**: Using non-existent types or incorrect type syntax.

**Example**:
```php
// Current (Error)
/**
 * @return url
 */
function formscrm_check_url_crm() {
    return 'https://example.com'; // Returns string, not 'url'
}

// Fixed
/**
 * @return string URL to the CRM
 */
function formscrm_check_url_crm(): string {
    return 'https://example.com';
}
```

**Occurrences**: 1
- `helpers-functions.php` (line 374, 383)

### 8. Missing Return Types

**Issue**: Methods without declared return types.

**Example**:
```php
// Current (Error)
public function resend_metabox( $args ) {
    echo 'HTML content';
}

// Fixed
public function resend_metabox( array $args ): void {
    echo 'HTML content';
}
```

**Occurrences**: 2
- `class-gravityforms-widget.php` (line 52)
- `loader.php` (line 81)

## Priority Fixes

### High Priority (Breaking Issues)

1. **Fix return type mismatches** in all CRM library login methods (7 occurrences)
2. **Remove unreachable code** (2 occurrences)
3. **Fix parameter type mismatches** in API call methods (10 occurrences)

### Medium Priority (Type Safety)

1. **Add array type hints** to all methods (120+ occurrences)
2. **Replace `obj` type** with proper interface or class names (20+ occurrences)
3. **Add missing return types** (2 occurrences)

### Low Priority (Code Quality)

1. **Remove unnecessary isset() checks** on keys that always exist (5 occurrences)
2. **Simplify always-true conditions** (5 occurrences)

## Recommended Approach

### Phase 1: Create Interfaces
Create a CRM library interface to replace the `obj` type:

```php
// includes/crm-library/interface-crmlib.php
interface CRMLIB_Interface {
    /**
     * @param array<string, mixed> $settings
     */
    public function login( array $settings ): bool;

    /**
     * @param array<string, mixed> $settings
     * @return array<string, string>
     */
    public function list_modules( array $settings ): array;

    /**
     * @param array<string, mixed> $settings
     * @return array<string, array<string, mixed>>
     */
    public function list_fields( array $settings ): array;

    /**
     * @param array<string, mixed> $merge_vars
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function create_entry( array $merge_vars, array $settings ): array;
}
```

### Phase 2: Fix Critical Errors
Start with files that have the most errors:
1. `class-crmlib-acumbamail.php` (22 errors)
2. `class-crmlib-clientify.php` (27 errors)
3. `class-crmlib-brevo.php` (18 errors)
4. `class-crmlib-holded.php` (16 errors)
5. `class-crmlib-mailerlite.php` (16 errors)

### Phase 3: Add PHPDoc Comments
Add proper PHPDoc comments with array type specifications to all remaining methods.

### Phase 4: Progressive Level Increase
- **Current**: Level 6 (154 errors)
- **Target**: Level 7 (after fixing current issues)
- **Ultimate Goal**: Level 8 or 9 for maximum type safety

## Benefits of Fixing These Issues

1. **Better IDE Support**: IDEs will provide accurate autocomplete and type hints
2. **Early Bug Detection**: Catch type-related bugs before runtime
3. **Improved Documentation**: Code becomes self-documenting with proper types
4. **Easier Refactoring**: Type safety makes refactoring safer and easier
5. **Better Team Collaboration**: Clear contracts between functions/methods
6. **Reduced Runtime Errors**: Many potential bugs are caught during development

## Running PHPStan

```bash
# Run analysis
composer phpstan

# Run with specific level
vendor/bin/phpstan analyse --level=6

# Generate baseline (to track progress)
vendor/bin/phpstan analyse --generate-baseline
```

## CI/CD Integration

PHPStan is already integrated into GitHub Actions at `.github/workflows/php-lint.yml`. It runs on:
- Push to trunk/main/master branches
- Pull requests to trunk/main/master/release branches
- Currently set to `continue-on-error: true` (informational only)

## Next Steps

1. ✅ **Configure PHPStan** (DONE)
2. ⏳ **Review this report** and prioritize fixes
3. ⏳ **Create CRM interface** to replace `obj` type
4. ⏳ **Fix critical errors** (return type mismatches, unreachable code)
5. ⏳ **Add array type hints** progressively
6. ⏳ **Increase PHPStan level** to 7 when ready
7. ⏳ **Make CI/CD fail on errors** when confidence is high

## Resources

- [PHPStan Documentation](https://phpstan.org/)
- [Array Type Hints Guide](https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type)
- [PHPStan for WordPress](https://github.com/szepeviktor/phpstan-wordpress)

---

**Generated**: December 15, 2025  
**PHPStan Version**: Latest  
**Analysis Level**: 6 / 9  
**Status**: Initial Analysis Complete ✅
