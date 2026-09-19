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


final class PermissionHelper {
	/**
	 * Check if the current user has a specific capability.
	 *
	 * @param string $capability The capability to check.
	 * @param int $object_id Optional. The object ID for context. Default 0.
	 * @return bool True if the user has the capability, false otherwise.
	 * @since 1.0.0
	 */
	public static function can( string $capability, int $object_id = 0 ): bool {
		return $object_id > 0 ? current_user_can( $capability, $object_id ) : current_user_can( $capability );
	}
	/**
	 * Check if the current user has any of the specified capabilities.
	 *
	 * @param array $capabilities The capabilities to check.
	 * @param int $object_id Optional. The object ID for context. Default 0.
	 * @return bool True if the user has any of the capabilities, false otherwise.
	 * @since 1.0.0
	 */
	public static function can_any( array $capabilities, int $object_id = 0 ): bool {
		foreach ( $capabilities as $capability ) {
			if ( is_string( $capability ) && self::can( $capability, $object_id ) ) {
				return true;
			}
		}

		return false;
	}
	/**
	 * Check if the current user has all of the specified capabilities.
	 *
	 * @param array $capabilities The capabilities to check.
	 * @param int $object_id Optional. The object ID for context. Default 0.
	 * @return bool True if the user has all of the capabilities, false otherwise.
	 * @since 1.0.0
	 */
	public static function can_all( array $capabilities, int $object_id = 0 ): bool {
		foreach ( $capabilities as $capability ) {
			if ( ! is_string( $capability ) || ! self::can( $capability, $object_id ) ) {
				return false;
			}
		}

		return true;
	}
	/**
	 * Check if the current user is logged in.
	 *
	 * @return bool True if the user is logged in, false otherwise.
	 * @since 1.0.0
	 */
	public static function logged_in(): bool {
		return is_user_logged_in();
	}
	/**
	 * Get the current user's ID.
	 *
	 * @return int The current user's ID.
	 * @since 1.0.0
	 */
	public static function user_id(): int {
		return absint( get_current_user_id() );
	}
}





