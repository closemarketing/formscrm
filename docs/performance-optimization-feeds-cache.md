# Performance Optimization: Feeds Cache Implementation

## Problem Description

The entry detail page in Gravity Forms was experiencing timeout issues when loading entries. The main cause was identified as repeated database queries to fetch feeds on every page load.

## Root Cause

In the `FormsCRM_GravityForms_Widget` class, the method `resend_metabox()` was calling:

```php
$feeds = GFCRM::get_instance()->get_feeds( null, $form_id, 'formscrm', true );
```

This query was executed on **every page load** of the entry detail page, causing:
- Repeated database queries
- Slow page load times
- Potential timeouts with multiple feeds or slow database connections

## Solution Implemented

### 1. Feeds Caching System

A new private method `get_feeds_cached()` was implemented to cache the feeds query results using WordPress Transients API:

```php
private function get_feeds_cached( $form_id ) {
    $cache_key = 'formscrm_feeds_' . $form_id;
    $feeds     = get_transient( $cache_key );

    if ( false === $feeds ) {
        $feeds = GFCRM::get_instance()->get_feeds( null, $form_id, 'formscrm', true );
        set_transient( $cache_key, $feeds, 5 * MINUTE_IN_SECONDS );
    }

    return $feeds;
}
```

**Benefits:**
- Feeds are cached for 5 minutes per form
- Database queries are reduced drastically
- Page load time is significantly improved
- Cache is stored per form ID for optimal performance

### 2. Cache Invalidation

A new method `clear_feeds_cache()` was implemented to automatically clear the cache when feeds are modified:

```php
public function clear_feeds_cache( $feed_id, $form_id ) {
    if ( ! empty( $form_id ) ) {
        $cache_key = 'formscrm_feeds_' . $form_id;
        delete_transient( $cache_key );
    }
}
```

This method is hooked into Gravity Forms actions to ensure cache consistency:
- `gform_post_add_feed` - When a new feed is created
- `gform_post_update_feed` - When a feed is updated
- `gform_post_delete_feed` - When a feed is deleted

### 3. Modified Code

The direct call to `get_feeds()` was replaced with the cached version:

**Before:**
```php
$feeds = GFCRM::get_instance()->get_feeds( null, $form_id, 'formscrm', true );
```

**After:**
```php
$feeds = $this->get_feeds_cached( $form_id );
```

## Performance Impact

### Before Optimization
- Database query on every page load
- Loading time: Variable (depending on number of feeds and DB performance)
- Risk of timeouts with slow connections or many feeds

### After Optimization
- Database query only once every 5 minutes per form
- Loading time: Near-instant for cached requests
- Timeout risk eliminated for normal usage

## Cache Behavior

1. **First Request**: Queries database and stores result in cache for 5 minutes
2. **Subsequent Requests**: Retrieves data from cache (no database query)
3. **After 5 Minutes**: Cache expires, next request queries database again
4. **On Feed Changes**: Cache is immediately cleared, ensuring fresh data

## Manual Cache Clearing

If needed, you can manually clear the cache for a specific form using:

```php
delete_transient( 'formscrm_feeds_' . $form_id );
```

Or clear all FormsCRM feed caches:

```php
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_formscrm_feeds_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_formscrm_feeds_%'" );
```

## Future Considerations

### Potential Improvements
1. Make cache duration configurable via settings
2. Add cache statistics to admin dashboard
3. Implement object caching support for multi-server environments
4. Add WP-CLI command for cache management

### Monitoring
Monitor the following metrics to ensure optimal performance:
- Page load time for entry detail pages
- Database query count
- Cache hit/miss ratio (can be logged if needed)

## Technical Details

**File Modified:** `includes/formscrm-library/class-gravityforms-widget.php`

**Methods Added:**
- `get_feeds_cached( $form_id )` - Retrieves feeds with caching
- `clear_feeds_cache( $feed_id, $form_id )` - Clears cache on feed changes

**Hooks Added:**
- `gform_post_add_feed` → `clear_feeds_cache()`
- `gform_post_update_feed` → `clear_feeds_cache()`
- `gform_post_delete_feed` → `clear_feeds_cache()`

## Testing Recommendations

1. Test entry detail page loading before and after optimization
2. Verify cache is cleared when feeds are added/updated/deleted
3. Test with multiple forms to ensure cache isolation
4. Monitor for 5 minutes to verify cache expiration works correctly
5. Test with different numbers of feeds (1, 10, 50+)

## Date of Implementation

February 6, 2026

## Version

FormsCRM 1.0+

---

**Note:** This optimization should significantly reduce database load and eliminate timeout issues on the entry detail pages.
