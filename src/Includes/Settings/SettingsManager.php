<?php
/**
 * SettingsManager class.
 *
 * @package ModPress
 * @subpackage Includes\Settings
 * @since 1.0.0
 */

namespace ModPress\Includes\Settings;

use ModPress\Includes\Core\WP\Database;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class SettingsManager {
    /**
     * Registered default settings for each group.
     *
     * @var array<string, array<string, mixed>>
     * @since 1.0.0
     */
    private static array $registered_groups = [];

    /**
     * Registered default keys for each group.
     *
     * @var array<string, string>
     * @since 1.0.0
     */
    private static array $registered_keys = [];

    public static function table_name(): string {
        return Database::table_name( 'settings' );
    }
    public static function install(): void {
        Database::install();

        foreach ( self::registered_defaults() as $group => $settings ) {
            $stored_settings = self::get_group( $group ) ?? [];
            self::set_group( $group, array_merge( $settings, $stored_settings ) );
        }
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
        return self::registered_default( $key, $default );
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
        global $wpdb;
        $rows = $wpdb->get_results( 'SELECT setting_group, setting_value FROM ' . self::table_name(), ARRAY_A );
        $settings = self::registered_defaults();

        foreach ( $rows ?: [] as $row ) {
            $group = self::logical_group( $row['setting_group'] );
            $stored = maybe_unserialize( $row['setting_value'] );

            if ( ! is_array( $stored ) ) {
                continue;
            }

            $defaults = self::registered_defaults()[ $group ] ?? [];
            $settings[ $group ] = array_merge( $defaults, $stored );
        }

        return $settings;
    }
    /**
     * Get the default settings.
     *
     * @return array
     * @since 1.0.0
     */
    public static function defaults(): array {
        return [
            'general' => [
                'root_name' => 'ModPress',
                'root_description' => __( 'A searchable catalogue powered by ModPress.', 'modpress' ),
                'archive_title' => __( 'ModPress Catalogue', 'modpress' ),
                'archive_description' => __( 'Browse the ModPress catalogue.', 'modpress' ),
                'root_slug' => 'catalogue',
                'category_slug' => 'catalogue-group',
                'tag_slug' => 'catalogue-tag',
                'permalink' => '%root%/%mod_group%/%mod_tag%/%mod_page%',
                'enable_schema' => true,
            ],
            'layout' => [
                'show_search' => true,
                'show_toc' => true,
                'show_breadcrumbs' => true,
                'show_last_updated' => true,
                'show_author' => false,
                'show_reading_time' => false,
                'show_feedback' => true,
                'show_related_pages' => true,
                'related_pages_count' => 4,
                'search_placeholder' => __( 'Search the catalogue', 'modpress' ),
                'search_button_text' => __( 'Search', 'modpress' ),
                'search_scope' => 'all',
                'search_no_results_message' => __( 'No catalogue entries found.', 'modpress' ),
                'search_results_count' => 10,
                'search_min_chars' => 2,
                'search_live_results' => true,
                'show_sidebar' => true,
                'sidebar_position' => 'left',
                'sidebar_width' => 280,
                'sidebar_sticky' => true,
                'sidebar_show_categories' => true,
                'sidebar_show_category_count' => false,
                'sidebar_expand_categories' => true,
                'sidebar_show_page_count' => false,
                'page_show_title' => true,
                'page_show_toc' => true,
                'page_toc_position' => 'sidebar',
                'toc_min_level' => 2,
                'toc_max_level' => 4,
                'page_show_navigation' => true,
                'reading_time_wpm' => 200,
            ],
            'access' => [],
            'tools' => [ 
                'debug_logging' => false,
                'console_logging' => false 
            ],
        ];
    }
    /**
     * Get the default settings.
     *
     * @return array
     * @since 1.0.0
     */
    public static function get_group( string $group ): ?array {
        global $wpdb;
        $group = self::normalize_group( $group );
        $value = $wpdb->get_var( $wpdb->prepare( 'SELECT setting_value FROM ' . self::table_name() . ' WHERE setting_group = %s', self::storage_group( $group ) ) );
        $settings = $value === null ? null : maybe_unserialize( $value );

        if ( ! is_array( $settings ) ) {
            return self::registered_defaults()[ $group ] ?? null;
        }

        $defaults = self::registered_defaults()[ $group ] ?? [];
        return array_merge( $defaults, $settings );
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
        global $wpdb;
        return false !== $wpdb->replace( self::table_name(), [
            'setting_group' => self::storage_group( $group ),
            'setting_value' => maybe_serialize( $settings ),
            'autoload' => 'yes',
            'updated_at' => current_time( 'mysql' ),
        ], [ '%s', '%s', '%s', '%s' ] );
    }
    /**
     * Register a settings group with default values.
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

        self::$registered_groups[ $group ] = array_merge( self::$registered_groups[ $group ] ?? [], $defaults );
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
        self::$registered_groups[ $group ][ $key ] = $default;
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
     * Return core and extension defaults for activation and fallback reads.
     *
     * @return array<string, array<string, mixed>>
     * @since 1.0.0
     */
    private static function registered_defaults(): array {
        $defaults = self::defaults();
        foreach ( self::$registered_groups as $group => $settings ) {
            $defaults[ $group ] = array_merge( $defaults[ $group ] ?? [], $settings );
        }

        return $defaults;
    }
    /**
     * Get the registered default for a specific setting key.
     *
     * @param string $key
     * @param mixed $fallback
     * @return mixed
     * @since 1.0.0
     */
    private static function registered_default( string $key, $fallback ) {
        $key = sanitize_key( $key );
        foreach ( self::registered_defaults() as $settings ) {
            if ( array_key_exists( $key, $settings ) ) {
                return $settings[ $key ];
            }
        }

        return $fallback;
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
}