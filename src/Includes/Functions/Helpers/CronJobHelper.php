<?php
/**
 * CronJobHelper functions.
 *
 * Provides helper functions for managing ModPress cron jobs.
 *
 * @package ModPress\Includes\Functions\Helpers
 */
namespace ModPress\Includes\Functions\Helpers;

use ModPress\Includes\Core\CronJobs;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Shared helper for registering cron jobs across the core plugin, internal plugins,
 * and third-party extensions.
 */
final class CronJobHelper {
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
		return CronJobs::register( $scope, $hook, $schedule, $args, $callback, $description );
	}

	/**
	 * Clear a scheduled cron job for a scope.
	 *
	 * @param string $scope The owner scope.
	 * @param string $hook  The cron hook name.
	 * @return bool True when cleared.
	 */
	public static function clear( string $scope, string $hook ): bool {
		return CronJobs::clear( $scope, $hook );
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
		return CronJobs::exists( $scope, $hook, $args );
	}

	/**
	 * Build a scope-aware cron hook name.
	 *
	 * @param string $scope Owner scope.
	 * @param string $hook  Raw hook name.
	 * @return string Normalized hook name.
	 */
	public static function normalize_hook( string $scope, string $hook ): string {
		return CronJobs::normalize_hook( $scope, $hook );
	}

	/**
	 * Normalize a cron owner scope.
	 *
	 * @param string $scope Scope value.
	 * @return string Normalized scope.
	 */
	public static function normalize_scope( string $scope ): string {
		return CronJobs::normalize_scope( $scope );
	}
}

