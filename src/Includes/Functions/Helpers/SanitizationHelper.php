<?php
/**
 * Reusable sanitization helpers for ModPress and extensions.
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
 * Provide defensive wrappers around common WordPress sanitizers.
 */
final class SanitizationHelper {
    /**
     * Sanitize a scalar value as plain text.
     *
     * @param mixed  $value   Value to sanitize.
     * @param string $fallback Value returned for non-scalar values.
     * @return string Sanitized text.
     */
    public static function text( $value, string $fallback = '' ): string {
        return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : $fallback;
    }

    /**
     * Sanitize a scalar value as a textarea value.
     *
     * @param mixed  $value   Value to sanitize.
     * @param string $fallback Value returned for non-scalar values.
     * @return string Sanitized textarea value.
     */
    public static function textarea( $value, string $fallback = '' ): string {
        return is_scalar( $value ) ? sanitize_textarea_field( (string) $value ) : $fallback;
    }

    /**
     * Sanitize a scalar value as a WordPress key.
     *
     * @param mixed  $value   Value to sanitize.
     * @param string $fallback Value returned for non-scalar values or empty output.
     * @return string Sanitized key.
     */
    public static function key( $value, string $fallback = '' ): string {
        if ( ! is_scalar( $value ) ) {
            return $fallback;
        }

        $sanitized = sanitize_key( (string) $value );
        return '' !== $sanitized ? $sanitized : $fallback;
    }

    /**
     * Sanitize a scalar value as a URL slug.
     *
     * @param mixed  $value   Value to sanitize.
     * @param string $fallback Value returned for non-scalar values or empty output.
     * @return string Sanitized slug.
     */
    public static function slug( $value, string $fallback = '' ): string {
        if ( ! is_scalar( $value ) ) {
            return $fallback;
        }

        $sanitized = sanitize_title( (string) $value );
        return '' !== $sanitized ? $sanitized : $fallback;
    }

    /**
     * Convert a value to a non-negative WordPress integer.
     *
     * @param mixed $value   Value to sanitize.
     * @param int   $fallback Fallback value.
     * @return int Sanitized integer.
     */
    public static function integer( $value, int $fallback = 0 ): int {
        return is_scalar( $value ) ? absint( $value ) : $fallback;
    }

    /**
     * Convert a value to an integer within an inclusive range.
     *
     * @param mixed $value    Value to sanitize.
     * @param int   $minimum  Minimum allowed value.
     * @param int   $maximum  Maximum allowed value.
     * @param int   $fallback Fallback value when the input is not scalar.
     * @return int Bounded integer.
     */
    public static function integer_range( $value, int $minimum, int $maximum, int $fallback ): int {
        if ( $minimum > $maximum ) {
            [ $minimum, $maximum ] = [ $maximum, $minimum ];
        }

        if ( ! is_scalar( $value ) || '' === trim( (string) $value ) ) {
            return max( $minimum, min( $maximum, $fallback ) );
        }

        return max( $minimum, min( $maximum, absint( $value ) ) );
    }

    /**
     * Return a value only when it is in an allowed list.
     *
     * @param mixed       $value     Value to check.
     * @param array<int, mixed> $allowed Allowed values.
     * @param mixed       $fallback  Value returned when not allowed.
     * @return mixed Validated value or fallback.
     */
    public static function one_of( $value, array $allowed, $fallback ) {
        return in_array( $value, $allowed, true ) ? $value : $fallback;
    }

    /**
     * Normalize a comma-separated or array-based term list.
     *
     * @param mixed $terms Terms to sanitize.
     * @return array<int, string> Unique, non-empty term names.
     */
    public static function terms( $terms ): array {
        if ( is_string( $terms ) ) {
            $terms = explode( ',', $terms );
        }

        if ( ! is_array( $terms ) ) {
            return [];
        }

        $sanitized = array_map( [ self::class, 'text' ], $terms );
        $sanitized = array_values( array_filter( $sanitized, 'strlen' ) );

        return array_values( array_unique( $sanitized ) );
    }
}
