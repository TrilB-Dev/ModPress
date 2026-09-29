<?php
/**
 * CronJobs class for registering plugin cron jobs.
 *
 * @package ModPress
 * @subpackage Includes\Pages
 * @since 1.0.0
 */
namespace ModPress\Includes\Pages;

use ModPress\Includes\Core\CronJobs as CronJobCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CronJobs {
	/**
	 * Register all page-layer cron jobs for the plugin.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public static function register(): void {
		self::modpress_cleanup();
	}

	/**
	 * Register the built-in ModPress cleanup cron job.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function modpress_cleanup(): bool {
		return CronJobCore::create(
			'core',
			'modpress_cleanup',
			'daily',
			array(),
			array( self::class, 'cleanup' ),
			__( 'Daily ModPress cleanup task.', 'modpress' )
		);
	}

	/**
	 * Example built-in cron callback.
	 *
	 * @return void
	 */
	public static function cleanup(): void {
		do_action( 'modpress_daily_cleanup' );
	}
}