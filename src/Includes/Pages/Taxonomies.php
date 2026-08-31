<?php

namespace WikiPress\Includes\Pages;

use WikiPress\Includes\Core\Taxonomy;
use WikiPress\Includes\Functions\Helpers\TaxonomyHelper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Taxonomies {
    public static function get_terms( string $taxonomy, int $limit = 50, string $search = '' ): array {
        $terms = TaxonomyHelper::terms( $taxonomy, 0, max( 1, $limit ), $search );

        return array_map( static fn( $term ) => [
            'id' => absint( $term->term_id ?? 0 ),
            'name' => sanitize_text_field( $term->name ?? '' ),
            'slug' => sanitize_title( $term->slug ?? '' ),
            'count' => absint( $term->count ?? 0 ),
            'description' => sanitize_textarea_field( $term->description ?? '' ),
        ], $terms );
    }

    public static function get_categories( int $limit = 50, string $search = '' ): array { return self::get_terms( Taxonomy::CATEGORY, $limit, $search ); }
    public static function get_tags( int $limit = 50, string $search = '' ): array { return self::get_terms( Taxonomy::TAG, $limit, $search ); }
}
