<?php
/**
 * Deactivator class for ModPress plugin.
 *
 * @package ModPress
 * @since 1.0.0
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
