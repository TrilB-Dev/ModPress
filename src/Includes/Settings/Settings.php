<?php

namespace ModPress\Includes\Settings;

use ModPress\Includes\Functions\Helpers\SanitizationHelper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Settings {
    public const GENERAL = 'general';
    public const LAYOUT = 'layout';
    public const ACCESS = 'access';
    public const TOOLS = 'tools';

    public static function get( string $key, $default = null ) {
        return SettingsManager::get( $key, $default );
    }

    public static function get_string( string $key, string $default = '' ): string {
        return SanitizationHelper::text( self::get( $key, $default ), $default );
    }

    public static function get_key( string $key, string $default = '' ): string {
        return SanitizationHelper::key( self::get( $key, $default ), $default );
    }

    public static function get_slug( string $key, string $default = '' ): string {
        return SanitizationHelper::slug( self::get( $key, $default ), $default );
    }

    public static function get_int( string $key, int $default = 0 ): int {
        return SanitizationHelper::integer( self::get( $key, $default ), $default );
    }

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

    public static function set( string $key, $value ): bool {
        return SettingsManager::set( $key, $value );
    }

    public static function delete( string $key ): bool {
        return SettingsManager::delete( $key );
    }

    public static function has( string $key ): bool {
        return SettingsManager::has( $key );
    }

    public static function get_group( string $group, ?array $default = null ): ?array {
        return SettingsManager::get_group( $group ) ?? $default;
    }

    public static function set_group( string $group, array $settings ): bool {
        return SettingsManager::set_group( $group, $settings );
    }

    public static function register_group( string $group, array $defaults = [] ): bool {
        return SettingsManager::register_group( $group, $defaults );
    }

    public static function register_key( string $key, string $group, $default = null ): bool {
        return SettingsManager::register_key( $key, $group, $default );
    }

    public static function get_all(): array {
        return SettingsManager::get_all();
    }
}
