<?php
/**
 * Safe request-value helpers for ModPress and extensions.
 *
 * @package ModPress
 * @subpackage Includes\Functions\Helpers
 * @since 1.0.0
 */

namespace ModPress\Includes\Functions\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Read and sanitize values from request-like arrays.
 */
final class RequestHelper {
    public static function get( string $key, $fallback = null ) {
        return self::value( $_GET, $key, $fallback );
    }

    public static function get_text( string $key, string $fallback = '' ): string {
        return self::text( $_GET, $key, $fallback );
    }

    public static function get_key( string $key, string $fallback = '' ): string {
        return self::key( $_GET, $key, $fallback );
    }

    public static function get_integer( string $key, int $fallback = 0 ): int {
        return self::integer( $_GET, $key, $fallback );
    }

    public static function value( array $source, string $key, $fallback = null ) {
        return array_key_exists( $key, $source ) ? $source[ $key ] : $fallback;
    }

    public static function text( array $source, string $key, string $fallback = '' ): string {
        return SanitizationHelper::text( self::unslash( self::value( $source, $key, $fallback ) ), $fallback );
    }

    public static function key( array $source, string $key, string $fallback = '' ): string {
        return SanitizationHelper::key( self::unslash( self::value( $source, $key, $fallback ) ), $fallback );
    }

    public static function slug( array $source, string $key, string $fallback = '' ): string {
        return SanitizationHelper::slug( self::unslash( self::value( $source, $key, $fallback ) ), $fallback );
    }

    public static function integer( array $source, string $key, int $fallback = 0 ): int {
        return SanitizationHelper::integer( self::unslash( self::value( $source, $key, $fallback ) ), $fallback );
    }

    public static function integer_range( $value, int $minimum, int $maximum, int $fallback ): int {
        return SanitizationHelper::integer_range( self::unslash( $value ), $minimum, $maximum, $fallback );
    }

    public static function array( array $source, string $key, array $fallback = [] ): array {
        $value = self::value( $source, $key, $fallback );
        return is_array( $value ) ? wp_unslash( $value ) : $fallback;
    }

    public static function boolean( array $source, string $key, bool $fallback = false ): bool {
        $value = self::value( $source, $key, null );
        if ( null === $value ) {
            return $fallback;
        }

        if ( is_bool( $value ) ) {
            return $value;
        }

        $parsed = filter_var( self::unslash( $value ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
        return null === $parsed ? $fallback : $parsed;
    }

    private static function unslash( $value ) {
        return function_exists( 'wp_unslash' ) ? wp_unslash( $value ) : $value;
    }
}
