# ModPress Migration Handoff

This document records the ModPress extraction work completed while ModPress was part of the multi-root workspace. Continue from this file after moving ModPress into its own Git repository.

## Objective

Extract ModPress from the TrilB.Dev plugin architecture and rebuild it using the modular architecture already used by WikiPress and MSPress.

The migration is intentionally incremental. The first completed slice is the native synchronous Mod creation flow. The current native form is authoritative; historical AJAX payloads and filesystem-backed Mod forms were not recreated without an explicit replacement contract.

## Repository Context

- Project: ModPress
- Current source path during this work: `C:\Users\Anthony\OneDrive\Modding\Wordpress Plugins\ModPress`
- Composer PSR-4 mapping: `ModPress\\` -> `src/`
- Native Mod post type: `PostType::MOD` (`modpress_mod`)
- PHP: `8.4.22`
- PHPUnit: `9.6.36`
- WordPress stubs: `^6.9`
- This directory was not recognized as a Git repository during the work. Re-run Git checks after placing it in its own repository.

## Completed Native Mod Creation Slice

The native Mod creation flow now has an explicit, testable contract:

- Form action: `modpress_action=save_mod`
- Capability: `modpress_create`
- Nonce action: `modpress_save_mod`
- Nonce field: `modpress_mod_nonce`
- New Mod status: `publish`
- Title sanitization: `SanitizationHelper::text()`
- Content sanitization: `wp_kses_post()`
- Success and error notices: `AlertHelper::get_admin_notice()`
- Save service returns a notice string; it does not echo or redirect.

### Production files involved

- `src/Admin/Admin.php`
  - Composes the Mod save service and injects it into `ModManager`.
  - Registers the native Mod admin callbacks.

- `src/Admin/Manager/Mods/ModManager.php`
  - Calls `FunctionsMod::save_mod()`.
  - Renders the returned notice through `wp_kses_post()`.

- `src/Admin/Manager/Mods/ModForms.php`
  - Native creation form includes:
    ```php
    <input type="hidden" name="modpress_action" value="save_mod">
    ```
  - A historical filesystem-backed reference remains and needs an explicit replacement decision:
    `MODPRESS_PATH . "mods/{$mod_name}/form.php"`

- `src/Includes/Functions/Admin/FunctionsMod.php`
  - Owns Mod-specific save behavior.
  - Gates on POST and the `save_mod` action.
  - Checks `modpress_create`.
  - Verifies `check_admin_referer( 'modpress_save_mod', 'modpress_mod_nonce' )`.
  - Uses `wp_unslash()` and `sanitize_key()` for request handling.
  - Sanitizes title and content, rejects an empty title, inserts a published `PostType::MOD` post, and handles `WP_Error`.
  - Uses the shared alert helper for notices.

No plugin-specific branches were added to shared core. Mod behavior remains inside the Mod-owned admin service and manager path.

## Test Changes

- `Test/Unit/FunctionsModTest.php`
  - Added focused tests for the save service.
  - Added guarded WordPress function/class shims needed by the isolated test environment.
  - The `WP_Error` shim includes a string code, constructor, and `get_error_code()`.
  - The title expectation matches WordPress text sanitization: `My Mod` rather than HTML-marked input.

- `Test/Unit/TaxonomyTest.php`
  - Guarded duplicate global test shims with `class_exists()` and `function_exists()` checks.
  - This prevents process-wide PHPUnit redeclaration failures when test files load together.

## Validation Results

Focused validation passed:

```text
PHPUnit 9.6.36
OK (6 tests, 12 assertions)
```

PHP syntax validation passed for:

```text
src/Admin/Manager/Mods/ModManager.php
src/Admin/Manager/Mods/ModForms.php
src/Includes/Functions/Admin/FunctionsMod.php
Test/Unit/FunctionsModTest.php
Test/Unit/TaxonomyTest.php
```

The full suite loads and executes all 20 tests. Current result:

```text
Tests: 20, Assertions: 28, Errors: 8.
```

All 8 remaining errors are from `EmailCatalogTest`, which requires missing pre-existing files under:

```text
src/Includes/Plugins/Exchange/Templates/WP/
```

Missing files:

```text
AdminEmail.php
CommentsEmail.php
MultisiteEmail.php
UserEmail.php
```

These failures are unrelated to the native Mod creation slice. Reconfirm them after moving the repository, but do not alter the email subsystem unless that becomes part of the migration scope.

Other validation notes:

- `git diff --check` could not run because the original ModPress path was not a Git worktree.
- PHPCS/WPCS was unavailable because the configured `WordPress-Extra` sniff is missing.
- No commit or branch was created.

## Deferred Migration Surface

Define an explicit replacement contract before implementing each area below:

- Taxonomy assignment during Mod creation
- Mod metadata
- Media and featured images
- Links
- Dependencies
- Files and downloads
- Changelogs
- Groups
- REST and AJAX operations
- Replacement for filesystem-backed Mod forms
- Admin and frontend asset registration
- Loader and importer behavior
- Remaining legacy terminology or WikiPress-derived code
- Translation POT and MO regeneration

Taxonomy assignment was intentionally deferred from the first save slice. Inspect the existing `DataTransfer.php`, taxonomy helpers, REST/AJAX handlers, and nearby tests before deciding where that behavior belongs.

## Architecture Rules to Preserve

- Mod-specific behavior belongs in Mod-owned services, managers, templates, assets, and hooks.
- Do not add Mod-specific conditions, settings, labels, capabilities, or business rules to shared core classes.
- Load modules through the existing plugin loader and provider interfaces; do not bypass the loader with direct bootstrap requires.
- Use shared settings, sanitization, permission, request, form, AJAX, alert, and REST helpers where applicable.
- Keep admin-only code out of frontend paths and load assets only on relevant pages.
- Check capabilities and nonces for privileged operations.
- Sanitize input and escape output for its context.
- Keep plugin settings, translations, assets, and business rules inside the owning extension boundary.
- Preserve public APIs and hook contracts unless a backward-compatible migration is defined.

## Recommended Next Session Commands

After placing ModPress in its own Git repository:

```powershell
Set-Location '<new ModPress repository path>'
composer install
npm install
vendor\bin\phpunit.bat -c phpunit.xml.dist
php -l src\Includes\Functions\Admin\FunctionsMod.php
git diff --check
```

Then inspect the standalone repository status and continue with the deferred contracts above. Regenerate translations only after translatable strings are finalized:

```powershell
npm run i18n:pot
npm run i18n:mo
```

## Handoff Summary

The first native Mod creation slice is implemented, connected, and covered by focused tests. The production files lint cleanly. The full suite is blocked only by the existing missing Exchange email template files. The next meaningful migration step is to define and implement taxonomy assignment, followed by the other Mod fields and legacy replacement contracts one surface at a time.
