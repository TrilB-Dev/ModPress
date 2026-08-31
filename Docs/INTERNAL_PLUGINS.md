# Internal Plugins

Internal plugins are modular extensions stored in the host plugin's configured extension directory. The directory constant, setting name, and discovery rules are host implementation details and should be documented by the host.

## Required Structure

```text
MyPlugin/
|- Assets/
|  |- dist/css/
|  |- dist/js/
|  |- Assets.php
|  |- js/
|  `- scss/
|- Includes/
|  |- Includes.php
|  |- I18n.php
|  `- Settings/Settings.php
|- Language/
`- MyPlugin.php
```

The main file should be named after the directory, declare a namespace, define a matching class, and implement `PluginInterface`.

## Contract and Providers

```php
namespace ModPress\Includes\Plugins\MyPlugin;

use ModPress\Includes\Plugins\PluginInterface;

final class MyPlugin implements PluginInterface {
    public function get_slug(): string { return 'my-plugin'; }
    public function get_name(): string { return 'My Plugin'; }
    public function get_version(): string { return '1.0.0'; }
    public function get_author(): string { return 'Example MrTrilB'; }
    public function get_author_uri(): string { return 'https://trilb.dev'; }
    public function get_description(): string { return 'An example ModPress extension.'; }
    public function get_uri(): string { return 'https://trilb.dev/my-plugin'; }
    public function get_license(): string { return 'GPL-2.0-or-later'; }
    public function is_active(): bool { return true; }
    public function init(): void {}
}
```

Implement only the optional interfaces needed by the extension: settings, database, shortcodes, assets, admin pages, admin menus, admin sidebars, REST routes, frontend behavior, and translations. The loader invokes provider methods before `init()` according to the host lifecycle.

Admin menu parent slugs, sidebar sections, activation settings, and discovery timing are host-specific. Use parent values exposed by the host, or create an extension-owned top-level menu where supported.

## Composition, Assets, and Localization

Core service classes may be final. Compose them from the extension rather than extending them. Use `Includes/Includes.php` to load feature classes and `LoaderHelper` to register hooks.

Register page-specific assets through the shared `Assets` service. Keep source JavaScript and Sass under `Assets/js` and `Assets/scss`; compiled output belongs under `Assets/dist/js` and `Assets/dist/css`.

```bash
npm run build
```

Run `npm run i18n:pot` and `npm run i18n:mo` from the host root when those scripts cover internal extensions. Keep extension strings in the owning text domain and language directory.

## Discovery and Registry

`Plugins::get_instance()` returns the manager singleton. `get_loaded_plugins()` returns discovered class names and `get_registered_plugins()` returns registered instances. Use `register_plugin_instance()` inside the host registration callback, or `Plugins::register_plugin()` as a convenience method. Duplicate slugs should be ignored.

Use `LoggerHelper` for diagnostics. The loader should log invalid plugin classes and initialization failures without exposing sensitive data.
