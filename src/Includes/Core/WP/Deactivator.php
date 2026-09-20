<?php
/**
 * Define the deactivation functionality for the plugin.
 *
 * Handles the registration and execution of deactivation callbacks.
 *
 * @since 1.0.0
 *
 * @package    ModPress
 * @subpackage ModPress/Includes/Core/WP
 */
namespace ModPress\Includes\Core\WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Deactivator {
	/**
	 * Registered deactivation callbacks.
	 *
	 * @var array<int, callable>
	 */
	private static array $callbacks = array();

	/**
	 * Register extension deactivation callbacks.
	 *
	 * @param callable $callback Callback invoked during deactivation.
	 * @return void
	 */
	public static function register( callable $callback ): void {
		self::$callbacks[] = $callback;
	}

	/**
	 * Run extension deactivation tasks and flush rewrite rules.
	 *
	 * @param array<int, callable>|null $callbacks Optional callbacks for this run.
	 * @return void
	 */
	public static function deactivate( ?array $callbacks = null ): void {
		foreach ( $callbacks ?? self::$callbacks as $callback ) {
			call_user_func( $callback );
		}

		flush_rewrite_rules();
	}
}




