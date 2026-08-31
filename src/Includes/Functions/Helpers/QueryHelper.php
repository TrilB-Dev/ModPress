<?php
/**
 * WordPress query helpers for ModPress and extensions.
 *
 * @package ModPress\Includes\Functions\Helpers
 * @since 1.0.0
 */
namespace ModPress\Includes\Functions\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Provide access to the current query and reusable post queries.
 */
final class QueryHelper {
    public static function current(): ?\WP_Query {
        global $wp_query;
        return isset( $wp_query ) && $wp_query instanceof \WP_Query ? $wp_query : null;
    }

    public static function posts( array $args = [] ): \WP_Query {
        return new \WP_Query( $args );
    }
}
