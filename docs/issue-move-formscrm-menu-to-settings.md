# Issue: Move FormsCRM Admin Menu Under "Settings"

## Summary
FormsCRM currently appears as a top-level admin menu item. To keep the WordPress admin cleaner and follow common plugin UX conventions, FormsCRM should be moved under the core **Settings** menu.

## Problem
- The plugin adds a dedicated top-level menu entry for configuration.
- This increases top-level menu clutter for administrators.
- The current placement is less consistent with plugins that only expose settings screens.

## Expected Behavior
- FormsCRM should be accessible at **Settings -> FormsCRM**.
- Existing page slug (`page=formscrm`) must remain valid to avoid breaking direct links.
- Admin styles/scripts must still load correctly on the FormsCRM settings screen.

## Proposed Technical Approach
1. Replace `add_menu_page()` with `add_options_page()` in the admin options class.
2. Update the admin page hook check in `admin_enqueue_scripts` to target the new settings-page hook.
3. Keep the existing page slug and callback intact.

## Acceptance Criteria
- [ ] FormsCRM no longer appears as a top-level menu item.
- [ ] FormsCRM appears under **Settings**.
- [ ] Opening **Settings -> FormsCRM** renders the same tabs/content as before.
- [ ] Admin CSS still loads on the FormsCRM page.
- [ ] Existing direct URL `wp-admin/options-general.php?page=formscrm` works as expected.

## QA Notes
- Verify both "Settings" and "Error Log" tabs render correctly.
- Save settings and confirm success notice behavior is unchanged.
- Confirm no PHP warnings/notices are introduced.
