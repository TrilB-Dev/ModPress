<?php

namespace ModPress\Includes\Tools;

use ModPress\Includes\Core\PostType;
use ModPress\Includes\Core\Taxonomy;
use ModPress\Includes\Functions\Helpers\SanitizationHelper;
use ModPress\Includes\Functions\Helpers\PostHelper;
use ModPress\Includes\Functions\Helpers\QueryHelper;
use ModPress\Includes\Functions\Helpers\TaxonomyHelper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class DataTransfer {
    public const VERSION = 1;

    public static function export(): array {
        $data = [ 'version' => self::VERSION, 'mods' => [], 'pages' => [], 'categories' => [], 'tags' => [] ];
        $query = QueryHelper::posts( [ 'post_type' => [ PostType::MOD, PostType::PAGE ], 'post_status' => 'any', 'posts_per_page' => -1 ] );
        while ( $query->have_posts() ) {
            $query->the_post();
            $post = PostHelper::current();
            if ( ! $post ) {
                continue;
            }

            $item = [ 'id' => $post->ID, 'title' => $post->post_title, 'content' => $post->post_content, 'excerpt' => $post->post_excerpt, 'status' => $post->post_status, 'mod_id' => absint( get_post_meta( $post->ID, '_modpress_mod_id', true ) ), 'categories' => TaxonomyHelper::names( TaxonomyHelper::terms( Taxonomy::CATEGORY, $post->ID ) ), 'tags' => TaxonomyHelper::names( TaxonomyHelper::terms( Taxonomy::TAG, $post->ID ) ) ];
            $data[ $post->post_type === PostType::MOD ? 'mods' : 'pages' ][] = $item;
        }
        wp_reset_postdata();
        foreach ( [ 'categories' => Taxonomy::CATEGORY, 'tags' => Taxonomy::TAG ] as $key => $taxonomy ) {
            $terms = TaxonomyHelper::terms( $taxonomy );
            foreach ( $terms as $term ) {
                $data[ $key ][] = [ 'name' => $term->name, 'slug' => $term->slug, 'description' => $term->description ];
            }
        }
        return $data;
    }

    public static function export_json( int $flags = 0 ): string {
        $json = wp_json_encode( self::export(), $flags );
        return is_string( $json ) ? $json : '';
    }

    public static function validate( $data ): array {
        $errors = [];
        if ( ! is_array( $data ) ) {
            return [ 'valid' => false, 'errors' => [ __( 'The import data must be an object.', 'modpress' ) ] ];
        }
        if ( absint( $data['version'] ?? 0 ) !== self::VERSION ) {
            $errors[] = __( 'This ModPress export version is not supported.', 'modpress' );
        }
        foreach ( [ 'mods', 'pages', 'categories', 'tags' ] as $key ) {
            if ( isset( $data[ $key ] ) && ! is_array( $data[ $key ] ) ) {
                /* translators: %s is the name of the export section. */
                $errors[] = sprintf( esc_html__( 'The %s export section must be an array.', 'modpress' ), $key );
            }
        }

        return [ 'valid' => empty( $errors ), 'errors' => $errors ];
    }

    public static function import( array $data ): array|\WP_Error {
        $validation = self::validate( $data );
        if ( ! $validation['valid'] ) {
            return new \WP_Error( 'invalid_import', implode( ' ', $validation['errors'] ) );
        }
        $mod_map = [];
        $result = [ 'mods' => 0, 'pages' => 0, 'categories' => 0, 'tags' => 0, 'errors' => [] ];
        foreach ( (array) ( $data['mods'] ?? [] ) as $mod ) {
            if ( ! is_array( $mod ) ) {
                $result['errors'][] = __( 'A mod entry was skipped because it was invalid.', 'modpress' );
                continue;
            }
            $id = wp_insert_post( [ 'post_type' => PostType::MOD, 'post_title' => SanitizationHelper::text( $mod['title'] ?? '' ), 'post_content' => self::content( $mod['content'] ?? '' ), 'post_status' => self::status( $mod['status'] ?? 'draft' ) ], true );
            if ( ! is_wp_error( $id ) ) {
                $mod_map[ absint( $mod['id'] ?? 0 ) ] = (int) $id;
                $result['mods']++;
            } else {
                $result['errors'][] = $id->get_error_message();
            }
        }
        foreach ( (array) ( $data['pages'] ?? [] ) as $page ) {
            if ( ! is_array( $page ) ) {
                $result['errors'][] = __( 'A page entry was skipped because it was invalid.', 'modpress' );
                continue;
            }
            $id = wp_insert_post( [ 'post_type' => PostType::PAGE, 'post_title' => SanitizationHelper::text( $page['title'] ?? '' ), 'post_content' => self::content( $page['content'] ?? '' ), 'post_excerpt' => SanitizationHelper::text( $page['excerpt'] ?? '' ), 'post_status' => self::status( $page['status'] ?? 'draft' ) ], true );
            if ( ! is_wp_error( $id ) ) {
                $result['pages']++;
                if ( ! empty( $mod_map[ absint( $page['mod_id'] ?? 0 ) ] ) ) {
                    update_post_meta( (int) $id, '_modpress_mod_id', $mod_map[ absint( $page['mod_id'] ?? 0 ) ] );
                }
                self::set_terms( (int) $id, $page['categories'] ?? [], Taxonomy::CATEGORY );
                self::set_terms( (int) $id, $page['tags'] ?? [], Taxonomy::TAG );
            } else {
                $result['errors'][] = $id->get_error_message();
            }
        }
        $result['categories'] = self::import_terms( (array) ( $data['categories'] ?? [] ), Taxonomy::CATEGORY );
        $result['tags'] = self::import_terms( (array) ( $data['tags'] ?? [] ), Taxonomy::TAG );
        return $result;
    }

    private static function import_terms( array $terms, string $taxonomy ): int {
        $count = 0;
        foreach ( $terms as $term ) {
            if ( ! is_array( $term ) || empty( $term['name'] ) ) {
                continue;
            }
            $inserted = wp_insert_term( SanitizationHelper::text( $term['name'] ), $taxonomy, [ 'slug' => SanitizationHelper::slug( $term['slug'] ?? '' ), 'description' => SanitizationHelper::textarea( $term['description'] ?? '' ) ] );
            if ( ! is_wp_error( $inserted ) ) {
                $count++;
            }
        }
        return $count;
    }

    private static function set_terms( int $post_id, $terms, string $taxonomy ): void {
        wp_set_post_terms( $post_id, TaxonomyHelper::names( $terms ), $taxonomy, false );
    }

    private static function status( $status ): string {
        if ( ! is_scalar( $status ) ) {
            return 'draft';
        }

        $status = sanitize_key( (string) $status );
        return in_array( $status, [ 'publish', 'draft', 'private' ], true ) ? $status : 'draft';
    }

    private static function content( $content ): string {
        return is_scalar( $content ) ? wp_kses_post( (string) $content ) : '';
    }
}
