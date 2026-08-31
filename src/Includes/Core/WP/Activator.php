<?php

namespace ModPress\Includes\Core\WP;

use ModPress\Includes\Core\Capabilities;
use ModPress\Includes\Plugins\Plugins;
use ModPress\Includes\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Activator {
    /** @var array<int, callable> */
    private static array $callbacks = array();

    /**
     * Register extension activation callbacks.
     *
     * @param callable $callback Callback invoked during activation.
     * @return void
     */
    public static function register( callable $callback ): void {
        self::$callbacks[] = $callback;
    }

    /**
     * Run ModPress and extension activation tasks.
     *
     * @param array<int, callable>|null $callbacks Optional callbacks for this run.
     * @return void
     */
    public static function activate( ?array $callbacks = null ): void {
        Plugins::get_instance()->init();
        Capabilities::install();
        Database::install();
        SettingsManager::install();
        ( new \ModPress\Includes\Core\PostType() )->register();
        ( new \ModPress\Includes\Core\Taxonomy() )->register();

        foreach ( $callbacks ?? self::$callbacks as $callback ) {
            call_user_func( $callback );
        }

        flush_rewrite_rules();
    }
}
