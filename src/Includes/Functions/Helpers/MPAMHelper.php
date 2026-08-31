<?php
/**
 * Wordpress Admin Menu Helper class for ModPress plugin.
 * 
 * @package ModPress
 * @subpackage Includes\Functions\Helpers
 * @since 1.0.0
 */

namespace ModPress\Includes\Functions\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MPAMHelper {
    public const FILTER = 'modpress_admin_menus';

    /**
     * Create a WordPress admin menu definition.
     *
     * @param string $name Menu label.
     * @param string $slug Menu slug.
     * @param string $icon Dashicon or icon URL.
     * @param string $parent Parent menu slug, or empty for a top-level menu.
     * @return array<string, mixed>
     */
    public static function define( string $name, string $slug, string $icon = 'dashicons-admin-generic', string $parent = '' ): array {
        return [
            'parent' => sanitize_key( $parent ),
            'name'   => $name,
            'slug'   => sanitize_key( $slug ),
            'icon'   => sanitize_text_field( $icon ),
        ];
    }

    /**
     * Pass WordPress admin menu definitions through the extension filter.
     *
     * @param array<int, array<string, mixed>> $menus Menu definitions.
     * @return array<int, array<string, mixed>>
     */
    public static function filter( array $menus ): array {
        $filtered = apply_filters( self::FILTER, $menus );
        return is_array( $filtered ) ? array_values( array_filter( $filtered, 'is_array' ) ) : $menus;
    }

    /**
     * Get the admin menu page URL for a given slug.
     *
     * @param string $slug The slug of the admin menu page.
     * @return string The URL of the admin menu page.
     */
    public static function get_admin_menu_page_url( string $slug ): string {
        return admin_url( 'admin.php?page=' . $slug );
    }
}