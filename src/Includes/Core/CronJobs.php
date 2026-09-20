<?php
/**
 * CronJobs class.
 *
 * Handles the scheduling and execution of ModPress cron jobs.
 *
 * @package ModPress\Includes\Core
 */
namespace ModPress\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

final class CronJobs {
	/**
	 * Register a cron job with a scope-aware namespace.
	 *
	 * @param string        $scope     The owner scope, such as core, plugin, or extension.
	 * @param string        $hook      The cron hook name to schedule.
	 * @param string        $schedule  The WordPress recurrence key or a custom interval.
	 * @param array         $args      Args passed to the callback.
	 * @param callable|null $callback  Optional callback to hook to the cron action.
	 * @param string        $description Optional human-readable description.
	 * @return bool True when scheduled successfully.
	 */
	public static function register( string $scope, string $hook, string $schedule, array $args = array(), $callback = null, string $description = '' ): bool {
		$scope = self::normalize_scope( $scope );
		$hook  = self::normalize_hook( $scope, $hook );
		if ( ! is_string( $hook ) || '' === $hook ) {
			return false;
		}

		if ( null !== $callback && is_callable( $callback ) ) {
			add_action( $hook, $callback, 10, count( $args ) > 0 ? 1 : 0 );
		}

		if ( ! function_exists( 'wp_schedule_event' ) ) {
			return false;
		}

		$timestamp = time() + 60;
		if ( ! wp_schedule_event( $timestamp, $schedule, $hook, $args ) ) {
			return false;
		}

		if ( function_exists( 'add_filter' ) ) {
			add_filter( 'cron_schedules', static function ( $schedules ) use ( $scope, $description, $schedule ) {
				unset( $scope, $description );
				if ( isset( $schedules[ $schedule ] ) ) {
					return $schedules;
				}
				return $schedules;
			} );
		}

		return true;
	}

	/**
	 * Register a core cron job.
	 *
	 * @param string        $hook     Hook name.
	 * @param string        $schedule Recurrence identifier.
	 * @param array         $args     Job args.
	 * @param callable|null $callback Callback.
	 * @return bool True when the schedule was registered.
	 */
	public static function register_core( string $hook, string $schedule, array $args = array(), $callback = null ): bool {
		return self::register( 'core', $hook, $schedule, $args, $callback );
	}

	/**
	 * Clear a scheduled cron job for a scope.
	 *
	 * @param string $scope The owner scope.
	 * @param string $hook  The cron hook name.
	 * @return bool True when cleared.
	 */
	public static function clear( string $scope, string $hook ): bool {
		$scope = self::normalize_scope( $scope );
		$hook  = self::normalize_hook( $scope, $hook );
		if ( '' === $hook || ! function_exists( 'wp_clear_scheduled_hook' ) ) {
			return false;
		}

		return wp_clear_scheduled_hook( $hook );
	}

	/**
	 * Clear a core cron job.
	 *
	 * @param string $hook Hook name.
	 * @return bool True when the schedule was cleared.
	 */
	public static function clear_core( string $hook ): bool {
		return self::clear( 'core', $hook );
	}

	/**
	 * Check whether a cron job exists.
	 *
	 * @param string $scope The owner scope.
	 * @param string $hook  The cron hook name.
	 * @param array  $args  The scheduled arguments.
	 * @return bool True when the job is scheduled.
	 */
	public static function exists( string $scope, string $hook, array $args = array() ): bool {
		$scope = self::normalize_scope( $scope );
		$hook  = self::normalize_hook( $scope, $hook );
		if ( '' === $hook || ! function_exists( 'wp_next_scheduled' ) ) {
			return false;
		}

		return false !== wp_next_scheduled( $hook, $args );
	}

	/**
	 * Build a scope-aware cron hook name.
	 *
	 * @param string $scope Owner scope.
	 * @param string $hook  Raw hook name.
	 * @return string Normalized hook name.
	 */
	public static function normalize_hook( string $scope, string $hook ): string {
		$scope = self::normalize_scope( $scope );
		$hook  = sanitize_key( (string) $hook );
		if ( '' === $hook ) {
			return '';
		}

		return 'modpress_' . $scope . '_' . $hook;
	}

	/**
	 * Normalize a cron owner scope.
	 *
	 * @param string $scope Scope value.
	 * @return string Normalized scope.
	 */
	public static function normalize_scope( string $scope ): string {
		$scope = sanitize_key( trim( (string) $scope ) );
		return '' === $scope ? 'core' : $scope;
	}
}

