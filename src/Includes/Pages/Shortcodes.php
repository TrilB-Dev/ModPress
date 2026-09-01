<?php

namespace ModPress\Includes\Pages;

use ModPress\Includes\Core\Shortcodes as ShortcodeCore;
use ModPress\Includes\Functions\Helpers\ShortcodeHelper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Defines and registers the shortcodes built into ModPress.
 */
final class Shortcodes {
    /**
     * Register all built-in ModPress shortcodes.
     */
    public static function register( ShortcodeCore $registry ): void {
        $registry->register_many( self::definitions() );
    }

    /**
     * Return the built-in ModPress shortcode definitions.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function definitions(): array {
        return apply_filters( 'modpress_shortcode_definitions', [] );
    }
}