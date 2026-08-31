<?php
/**
 * Taxonomy query and term normalization helpers for ModPress.
 *
 * @package ModPress
 * @subpackage Includes\Functions\Helpers
 * @since 1.0.0
 */

namespace ModPress\Includes\Functions\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Centralize safe taxonomy lookups used by admin and frontend code.
 */
final class TaxonomyHelper {
    public static function terms( string $taxonomy, int $post_id = 0, int $limit = 0, string $search = '' ): array {
        $args = [
            'taxonomy' => SanitizationHelper::key( $taxonomy ),
            'hide_empty' => false,
        ];

        if ( $post_id > 0 ) {
            $args['object_ids'] = [ $post_id ];
        }
        if ( $limit > 0 ) {
            $args['number'] = $limit;
        }
        if ( '' !== $search ) {
            $args['search'] = SanitizationHelper::text( $search );
        }

        $terms = get_terms( $args );
        return is_wp_error( $terms ) || ! is_array( $terms ) ? [] : $terms;
    }

    public static function ids( $terms ): array {
        if ( ! is_array( $terms ) ) {
            $terms = SanitizationHelper::terms( $terms );
        }

        $ids = [];
        foreach ( $terms as $term ) {
            $id = is_object( $term ) && isset( $term->term_id ) ? $term->term_id : $term;
            if ( is_numeric( $id ) && absint( $id ) > 0 ) {
                $ids[] = absint( $id );
            }
        }

        return array_values( array_unique( $ids ) );
    }

    /**
     * Resolve term IDs and optionally create terms submitted by name.
     *
     * @param mixed  $terms    Term IDs or names.
     * @param string $taxonomy Taxonomy name.
     * @param bool   $create   Whether missing names should be created.
     * @return array<int, int> Resolved term IDs.
     */
    public static function resolve_ids( $terms, string $taxonomy, bool $create = false ): array {
        if ( ! is_array( $terms ) ) {
            $terms = [ $terms ];
        }

        $ids = [];
        foreach ( $terms as $term ) {
            if ( is_numeric( $term ) && absint( $term ) > 0 ) {
                $ids[] = absint( $term );
                continue;
            }

            $name = SanitizationHelper::text( $term );
            if ( '' === $name ) {
                continue;
            }

            $existing = term_exists( $name, $taxonomy );
            if ( is_array( $existing ) && ! empty( $existing['term_id'] ) ) {
                $ids[] = absint( $existing['term_id'] );
                continue;
            }
            if ( is_int( $existing ) && $existing > 0 ) {
                $ids[] = $existing;
                continue;
            }
            if ( $create ) {
                $created = wp_insert_term( $name, $taxonomy );
                if ( ! is_wp_error( $created ) && ! empty( $created['term_id'] ) ) {
                    $ids[] = absint( $created['term_id'] );
                }
            }
        }

        return array_values( array_unique( array_filter( $ids ) ) );
    }

    public static function names( $terms ): array {
        if ( ! is_array( $terms ) ) {
            $terms = SanitizationHelper::terms( $terms );
        }

        $names = [];
        foreach ( $terms as $term ) {
            $name = is_object( $term ) && isset( $term->name ) ? $term->name : $term;
            $name = SanitizationHelper::text( $name );
            if ( '' !== $name ) {
                $names[] = $name;
            }
        }

        return array_values( array_unique( $names ) );
    }
}
