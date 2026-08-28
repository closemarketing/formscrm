# Guidelines for building FormsCRM integrations with form plugins

This document defines **how a new form integration** (Gravity Forms, WPForms,
Ninja Forms, Elementor, CF7, JetFormBuilder, etc.) should be built so it stays
consistent with the rest of the plugin. It is based on the patterns already
used in `includes/formscrm-library/` and on the analysis of a **Ninja Forms**
integration attempt (issue [#122](https://github.com/closemarketing/formscrm/issues/122),
PR [#171](https://github.com/closemarketing/formscrm/pull/171)) that was
merged and later reverted (PR [#173](https://github.com/closemarketing/formscrm/pull/173)).

## The two guiding principles

1. **As adapted as possible to the form and its interface.** The user should
   not notice that FormsCRM is an external plugin: configuration must live
   inside the form plugin's own screens, using its own UI components.
2. **Integrate as deeply as possible with the functions the form plugin
   already offers.** If the plugin already solves a problem (field mapping,
   merge tags, validation, retries, submission logging...), FormsCRM should
   build on that solution instead of reinventing it.

## 1. Use the plugin's native extension mechanism, not a generic hook

Every existing integration registers itself through the **extension system of
its own plugin**, never through a hook that is generic to all of them:

| Plugin | Native mechanism used |
|---|---|
| Gravity Forms | `GFAddOn` / Feed Add-On Framework (`class-gravityforms.php`, loaded on `gform_loaded`) |
| WPForms | `WPForms_Provider` — the same "Marketing Providers" system used by Mailchimp/Constant Contact (`class-wpforms.php`, loaded on `wpforms_loaded`) |
| Elementor Pro | `\ElementorPro\Modules\Forms\Classes\Action_Base`, registered on `elementor_pro/init` |
| JetFormBuilder | `Jet_Form_Builder\Actions\Types\Base`, registered via `jet-form-builder/actions/register` |
| Contact Form 7 | CF7's own hooks: `wpcf7_editor_panels`, `wpcf7_before_send_mail` |

When scoping a new integration, the first question is: **what extension point
does this plugin offer for "providers" or "post-submission actions"?** If the
plugin has an "Action"/"Provider"/"Add-on" concept (like Ninja Forms with
`NF_Abstracts_Action`), FormsCRM must register there — not hook directly into
a generic "form submitted" event and reimplement the action list, settings
storage, etc. on its own.

## 2. Configuration lives inside the plugin's own screens

No standalone settings page is created for each integration. The CRM
connection UI is inserted:

- as a new tab/metabox in the form editor (CF7: `wpcf7_editor_panels`),
- as a new "Action" inside the builder's action list (JetFormBuilder, Ninja Forms),
- or as one more "Provider" in the plugin's existing connections screen (WPForms).

The user configures FormsCRM exactly where they configure any other
integration of that plugin (Mailchimp, Zapier, etc.), with the same look and
feel.

## 3. Field mapping must use the form's own selectors

This is the most important point, and the one most often gotten wrong:

- **WPForms**: mapping is done field by field with a native WPForms `<select>`
  listing the form's real fields (`output_fields()`), the same as for any
  other WPForms provider.
- **JetFormBuilder**: mapping is defined in JFB's own visual editor
  (`fields_map`), using the builder's own JS components.
- **Contact Form 7**: uses CF7's own mail-tags/shortcodes (`[field-name]`)
  that already exist on the form.

❌ **Anti-pattern (found in the Ninja Forms attempt, PR #171):** a free-text
`<textarea>` where the user hand-types lines like
`crm_field = {field:key}`. Even though Ninja Forms supports merge tags and the
textarea allowed them (`use_merge_tags => true`), forcing the user to type the
exact field name from memory instead of offering a selector built from the
form's actual fields is a step backwards compared to how every other
integration works, and it's error-prone.

✅ Correct alternative, now implemented in `class-ninjaforms.php`: one
merge-tag-enabled `textbox` **per CRM field** (not one big multi-line
textarea), generated from the real fields of the selected CRM module. The
admin clicks Ninja Forms' own merge tag button to pick a real submitted
field instead of typing anything — see section 9 for how the module/field
list is built.

## 4. Only show the connection fields that apply to the selected CRM

Each CRM needs different credentials (URL, username/password, API key, Odoo
DB...). Every existing integration hides/shows these fields based on the
selected CRM, using the helpers in `helpers-library-crm.php`:

- `formscrm_get_dependency_url()`
- `formscrm_get_dependency_username()`
- `formscrm_get_dependency_password()`
- `formscrm_get_dependency_apipassword()`
- `formscrm_get_dependency_apisales()`
- `formscrm_get_dependency_odoodb()`

CF7 applies them in PHP (it only renders the matching field), while
WPForms/JetFormBuilder apply them in JS when the CRM `select` changes.
**Never render all connection fields statically at once** (URL, username,
password, API password, API sales, Odoo DB, expert mode...) — this is the
concrete mistake in the reverted Ninja Forms PR: it always showed all 9
options in the "advanced" group regardless of the chosen CRM, cluttering the
settings screen.

## 5. Always reuse the same shared building blocks

Don't duplicate logic that is already solved in `helpers-functions.php` /
`helpers-library-crm.php`:

- `formscrm_get_choices()` for the CRM dropdown.
- `formscrm_get_api_class( $crm_type )` to load the matching `CRMLIB_*` class.
- The standard settings keys: `fc_crm_type`, `fc_crm_module`, `fc_crm_url`,
  `fc_crm_username`, `fc_crm_password`, `fc_crm_apipassword`, `fc_crm_apisales`,
  `fc_crm_odoodb`. Any new integration must produce a `$settings` array with
  these same keys so it works unmodified with any already-supported CRM
  (built-in or external via filters).
- `formscrm_check_url_crm()` to normalize the CRM URL before storing it.

## 6. Never block the form submission or lose the error

"Maximum Reliability" principle (see `AGENTS.md`): a CRM failure must never
prevent the form from submitting, nor be shown to the end user. On top of
that, the error must remain **visible and actionable** for the administrator:

- Wrap the CRM call in `try { } catch ( Exception $e ) { }`.
- On error, call `formscrm_alert_error( $crm_type, $message, $merge_vars, $url, $json, $form_info )`
  (or its alias `formscrm_debug_email_lead()`), always passing `$form_info`
  with `form_type`, `form_type_title`, `form_id`, `form_name`, and, if
  available, `entry_id`. This is what feeds the Error Log table
  (`class-error-log.php`) and allows the lead to be resent manually. The
  Ninja Forms attempt called `formscrm_debug_email_lead()` without
  `$form_info`, so log entries couldn't be traced back to which NF
  form/submission they came from.
- On success, don't interrupt the form plugin's normal flow: just record the
  result (an internal note, a debug log, etc.) using the host plugin's own
  logging system if it has one (`entry_meta` in WPForms, NF notes, etc.).

## 7. Conditional loading, at zero cost when the plugin is inactive

Follow the `loader.php` pattern:

```php
if ( is_plugin_active( 'ninja-forms/ninja-forms.php' ) && ! class_exists( 'FormsCRM_NinjaForms_Action' ) ) {
    add_action( 'plugins_loaded', function () {
        if ( class_exists( 'NF_Abstracts_Action' ) ) {
            require_once 'class-ninjaforms.php';
        }
    }, 20 );
}
```

- Check `is_plugin_active()` first.
- Hook into the plugin's own bootstrap action (`gform_loaded`,
  `wpforms_loaded`, `elementor_pro/init`, `jet-form-builder/actions/register`,
  or `plugins_loaded` at a sufficient priority) instead of loading the class
  directly in `loader.php`.
- Don't read superglobals (`$_POST`, `$_REQUEST`) directly unless the host
  plugin genuinely doesn't hand you that data in the request array; if you
  do, sanitize it (`sanitize_text_field( wp_unslash( ... ) )`) and document
  why it's necessary.

## 8. Checklist before opening a PR for a new integration

- [ ] Does it use the plugin's native extension point (Action/Provider/Add-on),
      not a generic "on submit" hook?
- [ ] Does configuration appear inside the plugin's own screens (form editor,
      action list, connections), rather than a separate settings page?
- [ ] Does field mapping use a selector built from the form's real fields (or
      the plugin's native merge-tag/shortcode system), instead of hand-typed
      free text?
- [ ] Are connection fields (URL, username, password, API key, Odoo DB...)
      shown/hidden based on the selected CRM, using the
      `formscrm_get_dependency_*()` helpers?
- [ ] Does it reuse `formscrm_get_choices()`, `formscrm_get_api_class()`, and
      the standard `fc_crm_*` keys?
- [ ] Does a CRM failure never block form submission or surface to the end user?
- [ ] Does every error go through `formscrm_alert_error()` with a complete
      `$form_info` (`form_type`, `form_id`, `form_name`, `entry_id` if it
      exists)?
- [ ] Is the class/file only loaded when the plugin is active, hooked to its
      own bootstrap action?
- [ ] Are there unit tests for any new helper (e.g. field-mapping parsing)?
- [ ] Do `composer lint` and `composer phpstan` pass with no new warnings?

## 9. When the host plugin resolves settings before it knows which form is involved

Some action frameworks (Ninja Forms among them) build every registered
action's `_settings` once on `init`, before any specific form or action
instance exists. This is different from CF7 (settings render per-form on
demand) or WPForms (settings render per-connection on demand): there is no
"current form" to read credentials from yet, and no admin-triggered request
you can hook to fetch fresh data.

Two building blocks solve this without inventing anything new:

- Store **one global CRM connection** for that integration inside FormsCRM's
  own settings page, using the existing `formscrm_settings_tabs` filter
  (`class-admin-options.php`) to add a new tab — the same place Notifications
  and the Error Log already live. This mirrors what the JetFormBuilder
  integration already does via its own settings tab
  (`class-jetformbuilder-tab-handler.php`), just hosted on FormsCRM's page
  instead of the form plugin's, because the form plugin's settings API isn't
  guaranteed to be reachable from a generic PHP integration in every plugin.
- Fetch CRM modules/fields **eagerly** with that one connection (all modules,
  all their fields, in one pass) and cache the result with a short-lived
  transient (see `docs/performance-optimization-feeds-cache.md` for the same
  pattern applied to Gravity Forms feeds). This is exactly how real,
  widely-used Ninja Forms integrations do it — e.g. Mailchimp for WordPress's
  `MC4WP_Ninja_Forms_Action` builds a `newsletter_list` select whose every
  `option` carries its own `fields` array, paired with a sibling `fieldset`
  setting; Ninja Forms core switches between each option's fields entirely
  client-side, with no extra AJAX endpoint required. FormsCRM's Ninja Forms
  action (`class-ninjaforms.php`) reuses this exact select+fieldset shape for
  `fc_crm_module` / `fc_crm_field_map`.
- Each field-mapping input still uses Ninja Forms' own merge tag picker
  (`use_merge_tags`), so the admin selects a real submitted field instead of
  typing one — Ninja Forms resolves the tag into the actual submitted value
  before `process()` runs, so the action itself does no template parsing at
  all.

## Reference: case study — Ninja Forms (issue #122)

- **Issue**: [#122](https://github.com/closemarketing/formscrm/issues/122)
  requests native Ninja Forms support.
- **PR #123** (draft, by Cursor) targeted a stale base branch
  (`122-support-for-ninja-forms-integration`) and was closed without merging.
- **PR #171** redid the integration against `trunk`, adding
  `FormsCRM_NinjaForms_Action` (correctly extending `NF_Abstracts_Action`, NF's
  native pattern) with conditional loading in `loader.php`. It was merged and
  reverted the following day via **PR #173** (with no recorded comments
  documenting the exact reason).
- Reviewing the merged code, the points that diverge the most from the
  conventions used by the rest of the integrations (and the most plausible
  candidates for the revert) were: free-text field mapping instead of a
  native selector, all connection fields visible at once regardless of the
  CRM, and errors logged without `$form_info`.
- **The integration has been rebuilt** (`class-ninjaforms.php` +
  `class-ninjaforms-settings-tab.php`) applying the fixes above and the
  select+fieldset pattern from section 9: the CRM connection is configured
  once in FormsCRM > Ninja Forms (with `formscrm_get_dependency_*()` hiding
  irrelevant fields, same as every other integration), each form only picks
  a `fc_crm_module` and maps fields through Ninja Forms' native merge tag
  picker, and every failure path calls `formscrm_alert_error()` with full
  `$form_info`. The old `formscrm_parse_field_mapping()` free-text helper was
  dropped entirely rather than reused, since the native picker makes it
  unnecessary; `formscrm_ninjaforms_build_merge_vars()` replaces it with a
  simple, fully unit-tested lookup.
