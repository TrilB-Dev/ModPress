<?php

namespace ModPress\Includes\Plugins\FontAwesome\API;

use ModPress\Includes\Plugins\FontAwesome\Includes\Settings\Settings;

final class FontAwesomeAPI {
    public static function configure(): void {
        // ModPress uses its own Font Awesome asset pipeline and icon picker.
        // The official WordPress FontAwesome plugin is not required.
    }

    public static function register_preferences(): void {
        // No plugin bridge is required.
    }

    public static function is_available(): bool {
        return true;
    }

    public static function instance() {
        return null;
    }

    public static function source(): string {
        return Settings::source();
    }

    public static function kit_id(): string {
        return Settings::kit_id();
    }
}