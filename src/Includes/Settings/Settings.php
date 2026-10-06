<?php
/**
 * Settings class for managing ModPress plugin settings.
 *
 * @package ModPress
 * @subpackage Includes\Settings
 * @since 1.0.0
 */
namespace ModPress\Includes\Settings;

use ModPress\Includes\Functions\Helpers\SanitizationHelper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Settings {
    /**
     * General settings group.
     * 
     * @since 1.0.0
     */
    public const GENERAL = 'general';
    /**
     * Layout settings group.
     * 
     * @since 1.0.0
     */
    public const LAYOUT = 'layout';

    /**
     * Access settings group.
     * 
     * @since 1.0.0
     */
    public const ACCESS = 'access';
    /**
     * Tools settings group.
     * 
     * @since 1.0.0
     */
    public const TOOLS = 'tools';
    /**
     * Settings manager class.
     * 
     * @since 1.0.0
     */
    public static function get( string $key, $default = null ) {
        return SettingsManager::get( $key, $default );
    }
    /**
     * Get a sanitized string setting.
     * 
     * @since 1.0.0
     */
    public static function get_string( string $key, string $default = '' ): string {
        return SanitizationHelper::text( self::get( $key, $default ), $default );
    }

    /**
     * Get a sanitized key setting.
     * 
     * @since 1.0.0
     */
    public static function get_key( string $key, string $default = '' ): string {
        return SanitizationHelper::key( self::get( $key, $default ), $default );
    }

    /**
     * Get a sanitized slug setting.
     * 
     * @since 1.0.0
     */
    public static function get_slug( string $key, string $default = '' ): string {
        return SanitizationHelper::slug( self::get( $key, $default ), $default );
    }

    /**
     * Get a sanitized integer setting.
     * 
     * @since 1.0.0
     */
    public static function get_int( string $key, int $default = 0 ): int {
        return SanitizationHelper::integer( self::get( $key, $default ), $default );
    }

    /**
     * Get a sanitized boolean setting.
     * 
     * @since 1.0.0
     */
    public static function get_bool( string $key, bool $default = false ): bool {
        $value = self::get( $key, $default );
        if ( is_bool( $value ) ) {
            return $value;
        }

        if ( ! is_scalar( $value ) ) {
            return $default;
        }

        $parsed = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
        return null === $parsed ? $default : $parsed;
    }

    /**
     * Set a setting value.
     * 
     * @since 1.0.0
     */
    public static function set( string $key, $value ): bool {
        return SettingsManager::set( $key, $value );
    }

    /**
     * Delete a setting.
     * 
     * @since 1.0.0
     */
    public static function delete( string $key ): bool {
        return SettingsManager::delete( $key );
    }

    /**
     * Check if a setting exists.
     * 
     * @since 1.0.0
     */
    public static function has( string $key ): bool {
        return SettingsManager::has( $key );
    }

    /**
     * Get all settings within a group.
     * 
     * @since 1.0.0
     */
    public static function get_group( string $group, ?array $default = null ): ?array {
        return SettingsManager::get_group( $group ) ?? $default;
    }

    /**
     * Set all settings within a group.
     * 
     * @since 1.0.0
     */
    public static function set_group( string $group, array $settings ): bool {
        return SettingsManager::set_group( $group, $settings );
    }

    /**
     * Register a new settings group with default values.
     * 
     * @since 1.0.0
     */
    public static function register_group( string $group, array $defaults = [] ): bool {
        return SettingsManager::register_group( $group, $defaults );
    }

    /**
     * Register a new setting key within a group with a default value.
     * 
     * @since 1.0.0
     */
    public static function register_key( string $key, string $group, $default = null ): bool {
        return SettingsManager::register_key( $key, $group, $default );
    }

    /**
     * Get all settings.
     * 
     * @since 1.0.0
     */
    public static function get_all(): array {
        return SettingsManager::get_all();
    }
}
