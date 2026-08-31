# ModPress Architecture Instructions

ModPress is a modular WordPress platform. Internal plugins and separately installed WordPress plugins are the primary way to add features and integrations. Preserve this architecture in every change.

## Repository Layout

The repository is organized into a core application and provider-based internal plugins:

- `src/Plugin.php` is the main application entry point that wires the core services and plugin lifecycle.
- `src/Admin/` contains core admin UI and managers.
- `src/API/` contains shared API and response contracts.
- `src/Assets/` is the central asset pipeline. `src/Assets/Assets.php` owns registration and enqueueing of styles and scripts.
- `src/Includes/Core/` contains WordPress infrastructure and shared registries, including capabilities, editor, post types, taxonomies, shortcodes, activation, and deactivation.
- `src/Includes/Functions/` contains reusable functions and helpers.
- `src/Includes/Settings/` contains core settings storage and management services.
- `src/Includes/Plugins/` contains internal plugins, provider interfaces, and the plugin discovery/initialization service.
- `src/languages/` contains ModPress core translation catalogs and compiled language files.
- `src/Public/` contains core frontend behavior.
- `Test/` contains automated tests and test fixtures.
- `Docs/` contains the public architecture, API, helper, settings, and integration documentation.

Use the repository's existing casing: `src/Includes/` is authoritative, not `src/includes/`. Do not create duplicate parallel directories for the same concern.

## Core Boundary

- Keep feature-specific behavior out of ModPress core. Core must not contain conditions, selectors, settings keys, labels, capabilities, or business rules for a particular plugin.
- Do not add plugin-specific branches to shared classes such as admin renderers, settings managers, asset managers, API services, or shared helpers.
- If a plugin needs behavior that core cannot currently express, first implement it inside the plugin using its own `Admin/`, `Assets/`, `Includes/`, templates, helpers, services, and hooks.
- Decide whether to change core using this decision gate:
  1. Can the behavior live entirely in the plugin? If yes, do that.
  2. If no, does an identical need exist in two or more existing plugins? If no, stop — do not change core.
  3. If yes, add a generic extension point; never reference a plugin slug or class in core.
- When adapting core, expose a stable extension point and let the plugin provide the data or behavior. Never make core identify a plugin by slug, class name, or feature-specific setting.
- Prefer composition over inheritance. Core service classes are generally final and should be composed by plugins.
- Preserve existing public interfaces and hook contracts. Extend them backward-compatibly when possible.

## Internal Plugins

- Internal plugins live under `src/Includes/Plugins/<ModPress>/` and are initialized by `Plugins.php` through the contracts in `PluginsInterface.php`.
- Separately installed plugins register through `modpress_register_plugin` and follow the same ownership rules. They must not require core changes that reference their slug or class.
- Do not bypass the plugin loader with direct feature requires from `ModPress.php`, `Plugin.php`, or core bootstrap code.
- Treat each internal plugin as a self-contained extension with its own bootstrap, assets, includes, settings, translations, capabilities, and frontend/admin behavior where needed. A plugin may use this structure:

```text
ModPress/
|- Admin/                         (optional)
|- Assets/
|  |- dist/
|  |- js/
|  |- scss/
|  `- Assets.php                  (optional)
|- Includes/
|  |- Core/                       (optional)
|  |- Functions/                  (optional)
|  |- Settings/                   (optional)
|  `- Includes.php                (optional)
|- Language/ or Languages/
`- ModPress.php
```

- A plugin owns its settings defaults, settings sanitization, business rules, templates, JavaScript, Sass, translations, REST routes, admin pages, capabilities, and feature-specific helpers.
- Implement only the provider interfaces required by the feature: settings, settings page, database, assets, admin pages, admin menus/sidebar, REST routes, frontend behavior, shortcodes, and translations.
- Keep plugin bootstrap classes thin. Delegate business logic to plugin-owned services and use the plugin lifecycle and `LoaderHelper` for hooks.
- Use unique plugin slugs, namespaces, option names, AJAX actions, REST namespaces, script handles, and CSS selectors.
- Do not place plugin settings in core settings groups unless the setting is genuinely a core concern.
- Plugin settings pages should provide declarative field metadata to the generic renderer. Put dynamic option generation, validation, conditional behavior, and persistence in the plugin.
- Plugin JavaScript must discover and control plugin-owned markup through plugin data attributes or generic metadata. Do not require core to add a marker for one plugin.

## Assets

All ModPress and internal-plugin assets must flow through `src/Assets/Assets.php`.

- Plugin asset classes may define asset lists and page conditions, but must register them through the central `ModPress\Assets\Assets` service.
- Use the central page registry and enqueue pipeline; do not call `wp_enqueue_style()` or `wp_enqueue_script()` directly from plugin code when the central service can express the requirement.
- Keep source JavaScript and SCSS in the owning plugin's `Assets/js` and `Assets/scss` directories, and generated files in its `Assets/dist` directory. Core source assets remain in `src/Assets/js` and `src/Assets/scss`.
- Preserve page-specific loading conditions so plugin assets do not load on unrelated admin or frontend pages.
- Update webpack configuration only for the owning asset pipeline. Do not commit ad hoc generated bundles in a source directory.
- Run the asset build after changing JavaScript, SCSS, webpack entries, or asset registration, and inspect generated changes for stale bundles.

## Reusable Core APIs

- Before adding local logic, check the existing APIs and helpers in `src/API`, `src/Includes/Functions`, `src/Includes/Settings`, `src/Includes/Core`, and the provider interfaces in `src/Includes/Plugins/PluginsInterface.php`.
- Prefer `Settings` and `SettingsManager` for settings access, project sanitization/request helpers for input normalization, permission helpers for authorization, form-field helpers for controls, AJAX helpers for AJAX responses, alert helpers for admin notices, and `Response`/`Validators` for REST responses and validation.
- Do not query the settings database directly, duplicate shared sanitization or permission logic, or hand-build shared form/alert markup when a project helper exists.
- Use hooks, filters, provider interfaces, and generic metadata to deepen integration without coupling extensions to core internals.

## WordPress Security and Quality

- Check capabilities before privileged admin, AJAX, REST, settings, import, export, or file operations.
- Verify nonces for state-changing admin and AJAX requests, validate REST permissions, and sanitize all input with the appropriate WordPress/project helper.
- Escape output for its context and validate URLs, IDs, file paths, and uploaded file types before use.
- Never hard-code credentials, tokens, salts, or environment-specific paths. Do not log secrets or sensitive request data.
- Keep admin-only code out of frontend execution paths and avoid loading feature assets globally.
- Use WordPress APIs and project abstractions instead of direct database or filesystem access unless the owning abstraction explicitly requires it.

## Localization

- ModPress and its plugins use the repository language scripts to manage translation catalogs and compiled language files.
- Run `npm run i18n:pot` after adding or changing translatable strings. The script scans core and plugin source files and writes the appropriate POT catalog under `src/languages` or the plugin's `Language/` directory.
- Run `npm run i18n:mo` after updating a POT or PO catalog to compile the corresponding MO files used by WordPress.
- Keep plugin translation catalogs and language files inside the owning plugin's `Language/` or `Languages/` directory. Do not move plugin strings into the core catalog or hard-code translated text in shared core code.
- Use the correct text domain for the owning component and preserve existing translation file naming conventions.

## Change Workflow

1. Read the relevant README and documentation before changing architecture. Start with [README.md](../README.md), then consult [API.md](../Docs/API.md), [HELPERS.md](../Docs/HELPERS.md), [SETTINGS.md](../Docs/SETTINGS.md), [INTERNAL_PLUGINS.md](../Docs/INTERNAL_PLUGINS.md), and [WORDPRESS_PLUGINS.md](../Docs/WORDPRESS_PLUGINS.md) as applicable.
2. Identify the owning plugin or extension boundary before editing. State whether the requested behavior belongs in a plugin or requires a generic core capability.
3. Inspect the nearest implementation, provider interface, and test or call site before changing behavior. Prefer the smallest plugin-owned implementation.
4. If core must change, document the generic contract and keep the implementation independent of any plugin identity. Update every affected provider implementation when an interface changes.
5. Preserve existing public APIs, hooks, settings names, and data formats unless a backward-compatible migration is included.
6. Validate changed PHP with `php -l`, changed JavaScript with `node --check`, and run focused tests before broader checks.
7. Rebuild frontend assets with `npm run build` when source assets change.
8. Regenerate language catalogs with `npm run i18n:pot` and `npm run i18n:mo` when translatable strings change.
9. Run `git diff --check` and review generated files, deleted files, and unrelated worktree changes before finishing.

## Documentation References

- [REST API](../Docs/API.md)
- [Helpers](../Docs/HELPERS.md)
- [Settings](../Docs/SETTINGS.md)
- [Internal plugins](../Docs/INTERNAL_PLUGINS.md)
- [WordPress plugin integration](../Docs/WORDPRESS_PLUGINS.md)
- [Plugin interfaces](../src/Includes/Plugins/PluginsInterface.php)
