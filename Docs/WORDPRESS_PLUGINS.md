# WordPress Plugin Integration

A separately installed WordPress plugin can extend ModPress without being placed in the internal extension directory. The bridge is the `modpress_register_plugin` action.

## Register an Extension

```php
<?php
/**
 * ModPress: My ModPress Extension
 */

use ModPress\Includes\Plugins\Plugins;
use ModPress\Includes\Plugins\PluginInterface;

add_action('modpress_register_plugin', static function (Plugins $plugins): void {
    $plugins->register_plugin_instance(new MyPluginExtension());
});

final class MyPluginExtension implements PluginInterface {
    public function get_slug(): string { return 'my-plugin-extension'; }
    public function get_name(): string { return 'My Plugin Extension'; }
    public function get_version(): string { return '1.0.0'; }
    public function get_author(): string { return 'Example MrTrilB'; }
    public function get_author_uri(): string { return 'https://trilb.dev'; }
    public function get_description(): string { return 'Adds a ModPress integration.'; }
    public function get_uri(): string { return 'https://trilb.dev'; }
    public function get_license(): string { return 'GPL-2.0-or-later'; }
    public function is_active(): bool { return true; }
    public function init(): void {}
}
```

Load the extension class before registration. Use the external plugin's Composer autoloader where available and keep its namespace separate from the host. Declare the host plugin as a dependency or check for its classes before registering.

## Optional Providers

The same optional interfaces are available to externally installed extensions: settings, database tables, shortcodes, assets, admin pages and menus, host sidebars when supported, REST routes, frontend behavior, and translations.

```php
final class MyPluginExtension implements PluginInterface, RestRouteProviderInterface {
    public function register_rest_routes(): void {
        register_rest_route('my-extension/v1', '/status', [
            'methods' => 'GET',
            'callback' => [ $this, 'status' ],
            'permission_callback' => '__return_true',
        ]);
    }

    public function status(WP_REST_Request $request): WP_REST_Response {
        return Response::success([ 'ready' => true ]);
    }
}
```

Use `PermissionHelper` for protected routes and import `Response` from `ModPress\API` when using the host response envelope. Shortcode callbacks must return rendered output.

## Admin Integration

Implement `AdminMenuProviderInterface` for extension-owned menu entries. Parent slugs and sidebar sections are host-specific; verify them in the host implementation rather than copying identifiers from another plugin. The extension owns callbacks, rendering, capabilities, and page-specific assets.

## Lifecycle, Settings, and Assets

The host calls the registration action according to its lifecycle. If the host is unavailable, the external plugin should remain inactive rather than causing a fatal error. Use `class_exists()` or an activation dependency check when appropriate.

Implement settings providers for defaults and generated settings pages. Read through `ModPress\Includes\Settings\Settings` and sanitize all submitted values before storing them. External extensions own their translation catalogs and language files.

Register page-specific assets through the shared `Assets` service or filters documented by the host. Keep source assets in the external plugin and load them only on pages that need them.

## Checklist

1. Declare ModPress as a dependency or check for its classes.
2. Load the extension class before the registration hook runs.
3. Register on `modpress_register_plugin`.
4. Use a unique slug and REST namespace.
5. Implement only the provider interfaces needed.
6. Use host helpers for sanitization, permissions, URLs, and responses.
7. Test activation with ModPress active and inactive.
