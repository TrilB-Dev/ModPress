<?php

namespace ModPress\Includes\Plugins\FontAwesome\API;

use ModPress\Includes\Plugins\FontAwesome\Includes\Settings\Settings;

final class FontAwesomeAPI {
    public static function configure(): void {
        if ( ! class_exists( '\\FortAwesome\\FontAwesome' ) ) {
            return;
        }

        add_action( 'font_awesome_preferences', [ self::class, 'register_preferences' ] );
    }

    public static function register_preferences(): void {
        if ( ! self::is_available() ) {
            return;
        }

        self::instance()->register( [
            'technology'     => 'svg',
            'compat'         => true,
            'pseudoElements' => false,
            'name'           => 'ModPress',
        ] );
    }

    public static function is_available(): bool {
        return function_exists( 'FortAwesome\\fa' );
    }

    public static function instance() {
        return self::is_available() ? \FortAwesome\fa() : null;
    }

    public static function source(): string {
        return Settings::source();
    }

    public static function kit_id(): string {
        return Settings::kit_id();
    }
}