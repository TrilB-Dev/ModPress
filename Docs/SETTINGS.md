# Settings

Plugin settings are stored through `SettingsManager`. Use logical, plugin-owned group and key names; the storage implementation and prefix are internal details.

## Reading and Writing Values

Use the `Settings` facade rather than querying the database directly:

```php
use ModPress\Includes\Settings\Settings;

$name = Settings::get_string('name', 'ModPress');
$mode = Settings::get_key('mode', 'default');
$enabled = Settings::get_bool('enabled', true);

Settings::set('enabled', false);
Settings::delete('enabled');
```

Typed readers include `get()`, `get_string()`, `get_key()`, `get_slug()`, `get_int()`, and `get_bool()`. Group methods include `get_group()`, `set_group()`, and `get_all()`. `has()` checks whether a key exists.

## Registering Settings

Use `Settings::register_group()` or `Settings::register_key()` for settings and defaults:

```php
final class SettingsProvider {
    public function register(): void {
        Settings::register_group('my_extension', [
            'enabled' => true,
            'mode' => 'safe',
            'api_url' => '',
        ]);
    }
}
```

Register groups before installation or before values are read. The plugin system calls `SettingsProviderInterface::register_settings()` before `init()` for active extensions.

## Settings Pages

An extension can implement `SettingsPageProviderInterface` to provide generated field metadata. Supported field types and layouts must match the current renderer. Dynamic options, conditional rules, validation, and persistence belong to the provider.

```php
public function get_settings_page(): array {
    return [
        'slug' => 'my-extension',
        'label' => __( 'My Extension', 'my-extension' ),
        'title' => __( 'My Extension settings', 'my-extension' ),
        'fields' => [
            [
                'key' => 'enabled',
                'label' => __( 'Enabled', 'my-extension' ),
                'type' => 'checkbox',
                'default' => true,
            ],
        ],
    ];
}
```

Normalize and validate every submitted value with `SanitizationHelper` or a provider-specific sanitizer. Escape values when rendering HTML.

## Persistence and Migration

`SettingsManager::install()` installs or updates the settings store and merges registered defaults with stored values. `set_group()` persists a logical group through the manager. Preserve released keys or provide an explicit migration when changing them.

Do not store secrets unless the feature has an explicit encryption strategy. Sensitive credentials should use the project encryption conventions rather than plain serialized values.

## Checklist

1. Register the group and defaults.
2. Register the provider through the host plugin lifecycle.
3. Read values through typed `Settings` methods.
4. Sanitize and validate submitted values.
5. Persist through `set()` or `set_group()` as appropriate.
6. Escape values at output time.
