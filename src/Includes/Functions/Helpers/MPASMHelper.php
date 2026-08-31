<?php
/**
 * ModPress Sidebar Admin Menu helper.
 *
 * @package ModPress
 * @subpackage Includes\Functions\Helpers
 * @since 1.0.0
 */
namespace ModPress\Includes\Functions\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class MPASMHelper {
    public const FILTER = 'modpress_admin_sidebar_menus';

    /**
     * Create a ModPress sidebar menu definition.
     *
     * @param string $name Menu label.
     * @param string $slug Menu page slug.
     * @param string $icon Font Awesome icon classes.
     * @param string $parent Existing group slug, or empty for a new group.
     * @return array<string, mixed>
     */
    public static function define( string $name, string $slug, string $icon, string $parent = '', string $capability = '' ): array {
        return [
            'parent' => sanitize_key( $parent ),
            'name'   => $name,
            'slug'   => self::sanitize_slug( $slug ),
            'icon'   => sanitize_text_field( $icon ),
            'capability' => sanitize_key( $capability ),
        ];
    }

    /**
     * Pass sidebar menu definitions through the extension filter.
     *
     * @param array<int, array<string, mixed>> $menus Menu definitions.
     * @return array<int, array<string, mixed>>
     */
    public static function filter( array $menus ): array {
        $filtered = apply_filters( self::FILTER, $menus );
        return is_array( $filtered ) ? array_values( array_filter( $filtered, 'is_array' ) ) : $menus;
    }

    public static function get_url( string $slug ): string {
        return admin_url( 'admin.php?page=' . self::sanitize_slug( $slug ) );
    }

    private static function sanitize_slug( string $slug ): string {
        $parts = explode( '&', $slug, 2 );
        $page = sanitize_key( $parts[0] );
        return $page . ( isset( $parts[1] ) && '' !== $parts[1] ? '&' . sanitize_text_field( $parts[1] ) : '' );
    }
}
