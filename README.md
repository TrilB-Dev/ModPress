# ModPress

ModPress is a reusable WordPress plugin template by TrilB.Dev. It provides a starting point for plugins with modular services, settings, REST routes, admin pages, frontend behavior, compiled assets, and optional internal or external extensions. Replace the placeholder names and remove the modules your plugin does not need.

## Requirements

- WordPress with REST API support
- PHP version supported by the current ModPress codebase and its Composer dependencies
- Node.js and npm only when rebuilding frontend assets

Install PHP dependencies with Composer and build the frontend assets with:

```bash
npm install
npm run build
```

The build writes compiled assets to `src/Assets/dist`.

## Included Architecture

- Optional custom post types, taxonomies, admin pages, and frontend features
- A namespaced REST API when the plugin exposes routes
- Database-backed settings grouped by feature
- Shared sanitization, request, permission, query, content, URL, AJAX, and form helpers
- Bootstrap-based admin assets compiled with Webpack and Sass
- Optional Font Awesome integration
- Internal extension discovery and registration
- Integration with separately installed WordPress plugins

## Documentation

- [REST API](Docs/API.md)
- [Helpers](Docs/HELPERS.md)
- [Settings](Docs/SETTINGS.md)
- [Internal plugins](Docs/INTERNAL_PLUGINS.md)
- [WordPress plugin integration](Docs/WORDPRESS_PLUGINS.md)

## Project Structure

```text
src/
|- API/                         REST API services, routes, schemas, responses
|- Admin/                       Admin pages and UI managers
|- Assets/                      Shared source and compiled assets
|- Includes/Functions/          Reusable helper classes and functions
|- Includes/Plugins/            Internal extension system
|- Includes/Settings/           Settings facade and database manager
|- Languages/                   Translation catalogs and compiled language files
`- Plugin.php                   Main bootstrap class
```

## Extension Model

A ModPress extension implements `PluginInterface`, returns a unique slug, and exposes metadata and an `init()` method. Optional capability interfaces add settings, assets, admin pages, REST routes, frontend behavior, database tables, shortcodes, or translations.

Internal extensions are discovered from the configured ModPress plugin directory. A normal WordPress plugin can register an extension by hooking `modpress_register_plugin` and calling `Plugins::register_plugin()`.

See [INTERNAL_PLUGINS.md](Docs/INTERNAL_PLUGINS.md) and [WORDPRESS_PLUGINS.md](Docs/WORDPRESS_PLUGINS.md) for complete examples.

## Development Checks

```bash
npm run build
php -l path/to/changed-file.php
git diff --check
```

Check the package scripts and PHPUnit configuration before relying on a test command. The template may contain placeholder scripts that must be replaced when a plugin adds automated tests.

## License

ModPress is distributed under the license declared by the project and its individual dependencies. Confirm the applicable license before redistributing a packaged build.
