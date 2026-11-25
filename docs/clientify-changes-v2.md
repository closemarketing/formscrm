visitor_key2 > it gets from localstorage
Localstorage - __*_visitor_uuid

__aRZFRDO0RQdgPE4u_visitor_uuid --> valor sería el visitor_key2
## Merge by taxpayer identification number

The v2 API does not return `taxpayer_identification_number` in any response, so the exact-match check reads it from:

1. A custom field that mirrors it, if the site opts in with the `formscrm_clientify_taxpayer_custom_field` filter (v2 does return custom fields). No custom field is assumed by default, because FormsCRM is used on many sites.
2. Otherwise the v1 contact detail (`GET /v1/contacts/{id}/`), for the first 5 candidates.

```php
// Name of the Clientify custom field that holds the same value as the tax number.
add_filter( 'formscrm_clientify_taxpayer_custom_field', function () {
	return 'NIF';
} );
```

If create still answers 409, the contact is found by its exact email (or the merge search result when it has a single candidate) and updated.
