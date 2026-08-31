# PluginPress Plugin Template

Every discovered PluginPress plugin should use this structure:

```text
ModPress/
|- Assets/
|  |- dist/
|  |  |- css/
|  |  `- js/
|  |- js/
|  |- scss/
|  `- Assets.php
|- Includes/
|  |- Core/
|  |  `- Capabilities.php
|  |- Settings/
|  |  `- Settings.php
|  |- I18n.php
|  `- Includes.php
|- Language/
|  `- ModPress.pot
`- ModPress.php
```

`ModPress.php` must implement `PluginInterface`. The loader discovers direct PHP bootstrap files in each plugin directory and validates that contract at runtime; the `Assets`, `Includes`, settings, and language directories are optional. It calls optional capability methods when the plugin implements their corresponding interfaces, then calls `init()`.

A plugin should use composition for its `Assets`, `Includes`, and `Settings` classes. PluginPress core service classes are final and must not be extended.

The required plugin contract is:

- `get_slug()` returns a unique machine-readable identifier.
- `get_name()` returns the display name.
- `get_version()` returns the plugin version.
- `get_author()` returns the author name.
- `get_author_uri()` returns the author's website.
- `get_description()` returns the plugin description.
- `get_uri()` returns the plugin homepage.
- `get_license()` returns the plugin license identifier.
- `is_active()` controls whether the plugin initializes.
- `init()` performs the plugin's main setup.

Optional capabilities are defined in `PluginsInterface.php`:

- `SettingsProviderInterface::register_settings()`
- `DatabaseProviderInterface::register_tables()`
- `AssetsProviderInterface::register_assets()`
- `AdminPageProviderInterface::register_admin_pages()`
- `RestRouteProviderInterface::register_rest_routes()`
- `FrontendProviderInterface::register_frontend()`
- `I18nProviderInterface::load_textdomain()`

Plugins that define their own permissions should place them in
`Includes/Core/Capabilities.php`, extend `PluginPress\Includes\Core\Capabilities`,
and register them from their `Includes` initializer. The core registry merges
these definitions with core capabilities and installs missing capabilities on
the administrator role.

Plugins that provide translations should implement `I18nProviderInterface`, keep
their text-domain loader in `Includes/I18n.php`, and store translation templates
and language files in `Language/`.
