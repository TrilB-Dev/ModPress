<?php
/**
 * Capability and authentication helpers for ModPress and extensions.
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
 * Centralize capability checks without forcing callers to know WordPress APIs.
 */
final class PermissionHelper {
    public static function can( string $capability, int $object_id = 0 ): bool {
        return $object_id > 0 ? current_user_can( $capability, $object_id ) : current_user_can( $capability );
    }

    public static function can_any( array $capabilities, int $object_id = 0 ): bool {
        foreach ( $capabilities as $capability ) {
            if ( is_string( $capability ) && self::can( $capability, $object_id ) ) {
                return true;
            }
        }

        return false;
    }

    public static function can_all( array $capabilities, int $object_id = 0 ): bool {
        foreach ( $capabilities as $capability ) {
            if ( ! is_string( $capability ) || ! self::can( $capability, $object_id ) ) {
                return false;
            }
        }

        return true;
    }

    public static function logged_in(): bool {
        return is_user_logged_in();
    }

    public static function user_id(): int {
        return absint( get_current_user_id() );
    }
}
