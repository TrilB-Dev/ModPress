<?php

namespace WikiPress\Includes\Pages;

use WikiPress\Includes\Core\Shortcodes as ShortcodeCore;
use WikiPress\Includes\Functions\Helpers\ShortcodeHelper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Defines and registers the shortcodes built into WikiPress.
 */
final class Shortcodes {
    /**
     * Register all built-in WikiPress shortcodes.
     */
    public static function register( ShortcodeCore $registry ): void {
        $registry->register_many( self::definitions() );
    }

    /**
     * Return the built-in WikiPress shortcode definitions.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function definitions(): array {
        return apply_filters( 'wikipress_shortcode_definitions', [] );
    }
}