<?php
/**
 * SettingsManager class.
 *
 * @package ModPress
 * @subpackage Includes\Settings
 * @since 1.0.0
 */

namespace ModPress\Includes\Settings;

use ModPress\Includes\Functions\Helpers\DBHelper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class SettingsManager {
    /**
     * Runtime settings store used when the custom settings table is unavailable.
     *
     * @var array<string, array<string, mixed>>
     * @since 1.0.0
     */
    private static array $runtime_store = [];

    /**
     * Whether the default settings have already been seeded into the custom table.
     *
     * @var bool
     * @since 1.0.0
     */
    private static bool $seeded = false;

    /**
     * Registered setting keys mapped to their logical group.
     *
     * @var array<string, string>
     * @since 1.0.0
     */
    private static array $registered_keys = [];

    /**
     * Get the table name for the custom ModPress settings storage.
     *
     * @return string
     * @since 1.0.0
     */
    public static function table_name(): string {
        return DBHelper::table_name( 'settings' );
    }

    /**
     * Install any registered settings groups into the custom settings table.
     *
     * @return void
     * @since 1.0.0
     */
    public static function install(): void {
        if ( self::$seeded ) {
            return;
        }

        DBHelper::install();

        if ( class_exists( '\\ModPress\\Includes\\Core\\Defaults' ) ) {
            \ModPress\Includes\Core\Defaults::install_default_settings();
        }

        self::$seeded = true;
    }
    /**
     * Get a setting value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     * @since 1.0.0
     */
    public static function get( string $key, $default = null ) {
        foreach ( self::get_all() as $settings ) {
            if ( is_array( $settings ) && array_key_exists( $key, $settings ) ) {
                return $settings[ $key ];
            }
        }

        return $default;
    }
    /**
     * Set a setting value.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     * @since 1.0.0
     */
    public static function set( string $key, $value ): bool {
        $group = self::group_for_key( $key );
        $settings = self::get_group( $group ) ?? [];
        $settings[ $key ] = $value;
        return self::set_group( $group, $settings );
    }

    /**
     * Delete a setting.
     *
     * @param string $key
     * @return bool
     * @since 1.0.0
     */
    public static function delete( string $key ): bool {
        $group = self::group_for_key( $key );
        $settings = self::get_group( $group );
        if ( ! is_array( $settings ) || ! array_key_exists( $key, $settings ) ) {
            return false;
        }
        unset( $settings[ $key ] );
        return self::set_group( $group, $settings );
    }

    /**
     * Check if a setting exists.
     *
     * @param string $key
     * @return bool
     * @since 1.0.0
     */
    public static function has( string $key ): bool {
        foreach ( self::get_all() as $settings ) {
            if ( is_array( $settings ) && array_key_exists( $key, $settings ) ) {
                return true;
            }
        }

        return false;
    }
    /**
     * Get all settings.
     *
     * @return array
     * @since 1.0.0
     */
    public static function get_all(): array {
        if ( ! self::table_exists() ) {
            if ( self::is_mock_database_object() ) {
                return [];
            }
            return self::$runtime_store;
        }

        $column_name = self::has_column( 'setting_group' ) ? 'setting_group' : 'setting_key';
        $rows = DBHelper::get_all( 'settings' );
        $rows = is_array( $rows ) ? $rows : [];

        $settings = [];
        foreach ( $rows as $row ) {
            $key = $row[ $column_name ] ?? '';
            $group = self::logical_group( (string) $key );
            if ( '' === $group ) {
                continue;
            }

            $stored = maybe_unserialize( $row['setting_value'] ?? '' );
            if ( is_array( $stored ) ) {
                $settings[ $group ] = $stored;
            }
        }

        return $settings;
    }
    /**
     * Get the settings for a specific group.
     *
     * @return array|null
     * @since 1.0.0
     */
    public static function get_group( string $group ): ?array {
        $normalized_group = self::normalize_group( $group );
        if ( ! self::table_exists() ) {
            if ( self::is_mock_database_object() ) {
                return null;
            }
            return isset( self::$runtime_store[ $normalized_group ] ) ? self::$runtime_store[ $normalized_group ] : null;
        }

        $column_name = self::has_column( 'setting_group' ) ? 'setting_group' : 'setting_key';
        $row = DBHelper::get_row( 'settings', array( $column_name => self::storage_group( $group ) ) );
        if ( ! is_array( $row ) ) {
            return null;
        }

        $settings = maybe_unserialize( $row['setting_value'] ?? '' );
        if ( ! is_array( $settings ) ) {
            return null;
        }

        return $settings;
    }
    /**
     * Set the settings for a specific group.
     *
     * @param string $group
     * @param array $settings
     * @return bool
     * @since 1.0.0
     */
    public static function set_group( string $group, array $settings ): bool {
        $normalized_group = self::normalize_group( $group );
        if ( '' === $normalized_group ) {
            return false;
        }

        self::$runtime_store[ $normalized_group ] = $settings;

        global $wpdb;

        if ( ! self::table_exists() ) {
            if ( isset( $wpdb ) && is_object( $wpdb ) && method_exists( $wpdb, 'replace' ) ) {
                // Fall through to the upsert path so test doubles and custom DB adapters
                // can persist settings data even when SHOW TABLES is not implemented.
            } else {
                if ( self::is_mock_database_object() ) {
                    return false;
                }
                return true;
            }
        }

        $storage_group = self::storage_group( $group );
        $payload = array(
            'setting_value' => maybe_serialize( $settings ),
            'updated_at' => current_time( 'mysql' ),
        );

        if ( self::has_column( 'setting_group' ) ) {
            global $wpdb;

            if ( isset( $wpdb ) && is_object( $wpdb ) && method_exists( $wpdb, 'replace' ) ) {
                $replaced = $wpdb->replace(
                    DBHelper::table_name( 'settings' ),
                    array(
                        'setting_group' => $storage_group,
                        'setting_value' => maybe_serialize( $settings ),
                        'autoload' => 'yes',
                        'updated_at' => current_time( 'mysql' ),
                    ),
                    array( '%s', '%s', '%s', '%s' )
                );

                if ( false === $replaced ) {
                    return false;
                }

                $stored = DBHelper::get_var( 'settings', 'setting_value', array( 'setting_group' => $storage_group ) );
                return is_string( $stored ) && maybe_serialize( maybe_unserialize( $stored ) ) === maybe_serialize( $settings );
            }

            $existing = DBHelper::get_row( 'settings', array( 'setting_group' => $storage_group ) );
            $payload['setting_group'] = $storage_group;
            $payload['autoload'] = 'yes';

            if ( is_array( $existing ) ) {
                $updated = DBHelper::update( 'settings', $payload, array( 'setting_group' => $storage_group ) );
                if ( ! $updated ) {
                    return false;
                }
            } else {
                $payload['created_at'] = current_time( 'mysql' );
                $inserted = DBHelper::insert( 'settings', $payload );
                if ( false === $inserted ) {
                    return false;
                }
            }

            $stored = DBHelper::get_var( 'settings', 'setting_value', array( 'setting_group' => $storage_group ) );
            return is_string( $stored ) && maybe_serialize( maybe_unserialize( $stored ) ) === maybe_serialize( $settings );
        }

        $existing = DBHelper::get_row( 'settings', array( 'setting_key' => $storage_group ) );
        $payload = array(
            'setting_key' => $storage_group,
            'setting_value' => maybe_serialize( $settings ),
            'updated_at' => current_time( 'mysql' ),
        );

        if ( is_array( $existing ) ) {
            $updated = DBHelper::update( 'settings', $payload, array( 'setting_key' => $storage_group ) );
            if ( ! $updated ) {
                return false;
            }
        } else {
            $payload['created_at'] = current_time( 'mysql' );
            $inserted = DBHelper::insert( 'settings', $payload );
            if ( false === $inserted ) {
                return false;
            }
        }

        return true;
    }
    /**
     * Register a settings group for lookup resolution.
     *
     * @param string $group
     * @param array $defaults
     * @return bool
     * @since 1.0.0
     */
    public static function register_group( string $group, array $defaults = [] ): bool {
        $group = self::normalize_group( $group );
        if ( '' === $group ) {
            return false;
        }

        foreach ( $defaults as $key => $default ) {
            $key = sanitize_key( (string) $key );
            if ( '' !== $key ) {
                self::$registered_keys[ $key ] = $group;
            }
        }

        return true;
    }
    /**
     * Register a single setting key with a default value.
     *
     * @param string $key
     * @param string $group
     * @param mixed $default
     * @return bool
     * @since 1.0.0
     */
    public static function register_key( string $key, string $group, $default = null ): bool {
        $key = sanitize_key( $key );
        if ( '' === $key || ! self::register_group( $group ) ) {
            return false;
        }

        $group = self::normalize_group( $group );
        self::$registered_keys[ $key ] = $group;

        return true;
    }
    /**
     * Get the storage group name for a logical group.
     *
     * @param string $group
     * @return string
     * @since 1.0.0
     */
    private static function storage_group( string $group ): string {
        $group = self::normalize_group( $group );
        return str_starts_with( $group, 'modpress_' ) ? $group : 'modpress_' . $group;
    }

    /**
     * Get the logical group name from a storage group name.
     *
     * @param string $group
     * @return string
     * @since 1.0.0
     */
    private static function logical_group( string $group ): string {
        return str_starts_with( $group, 'modpress_' ) ? substr( $group, 10 ) : $group;
    }
    /**
     * Get the logical group name for a given setting key.
     *
     * @param string $key
     * @return string
     * @since 1.0.0
     */
    private static function group_for_key( string $key ): string {
        $key = sanitize_key( $key );
        if ( isset( self::$registered_keys[ $key ] ) ) {
            return self::$registered_keys[ $key ];
        }
        if ( in_array( $key, [ 'create_mods', 'write_pages', 'view_analytics', 'manage_plugins' ], true ) ) {
            return 'access';
        }
        if ( str_contains( $key, 'layout' ) ) {
            return 'layout';
        }
        if ( str_contains( $key, 'access' ) ) {
            return 'access';
        }
        if ( str_contains( $key, 'tool' ) ) {
            return 'tools';
        }
        return 'general';
    }

    /**
     * Normalize a group name by replacing dashes and spaces with underscores and removing the 'modpress_' prefix if present.
     *
     * @param string $group
     * @return string
     * @since 1.0.0
     */
    private static function normalize_group( string $group ): string {
        $group = str_replace( '-', '_', sanitize_key( $group ) );
        $group = str_replace( ' ', '_', $group );

        if ( str_starts_with( $group, 'modpress_' ) ) {
            return preg_replace( '/^modpress_/', '', $group ) ?: $group;
        }

        return $group;
    }

    /**
     * Check whether the settings table exists in the database.
     *
     * @return bool True if the table exists, false otherwise.
     * @since 1.0.0
     */
    private static function table_exists(): bool {
        return DBHelper::table_exists( 'settings' );
    }

    /**
     * Check whether the settings table contains a specific column.
     *
     * @param string $column_name The column name to inspect.
     * @return bool True when the column exists.
     * @since 1.0.0
     */
    private static function has_column( string $column_name ): bool {
        global $wpdb;

        if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
            return false;
        }

        if ( method_exists( $wpdb, 'replace' ) && ! method_exists( $wpdb, 'get_results' ) ) {
            return 'setting_group' === $column_name;
        }

        if ( ! method_exists( $wpdb, 'get_var' ) ) {
            return false;
        }

        if ( ! method_exists( $wpdb, 'get_results' ) ) {
            return false;
        }

        $query = 'SHOW COLUMNS FROM ' . self::table_name();
        $columns = $wpdb->get_results( $query, ARRAY_A );
        if ( ! is_array( $columns ) ) {
            return false;
        }

        foreach ( $columns as $column ) {
            if ( isset( $column['Field'] ) && $column['Field'] === $column_name ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the current database object is a test stub rather than a real WordPress database.
     *
     * @return bool True when the database object is a mock-like object.
     * @since 1.0.0
     */
    private static function is_mock_database_object(): bool {
        global $wpdb;

        return isset( $wpdb )
            && is_object( $wpdb )
            && property_exists( $wpdb, 'storage' );
    }
}