<?php

declare( strict_types=1 );

namespace {
    if ( ! defined( 'ARRAY_A' ) ) {
        define( 'ARRAY_A', 'ARRAY_A' );
    }
    if ( ! defined( 'OBJECT' ) ) {
        define( 'OBJECT', 'OBJECT' );
    }

    if ( ! class_exists( 'WP_Error' ) ) {
        final class WP_Error {
            private string $code;

            public function __construct( string $code ) {
                $this->code = $code;
            }

            public function get_error_code(): string {
                return $this->code;
            }
        }
    }

    $GLOBALS['modpress_test_taxonomies'] = [];
    $GLOBALS['modpress_test_terms'] = [];
    $GLOBALS['modpress_test_object_terms'] = [];
    $GLOBALS['modpress_test_filters'] = [];
    $GLOBALS['modpress_test_next_term_id'] = 1;
    $GLOBALS['wpdb'] = new class {
        public string $prefix = 'wp_';

        public function get_results( string $query, $output = null ): array {
            return [];
        }
    };

    if ( ! function_exists( 'sanitize_title' ) ) {
        function sanitize_title( $title, $fallback_title = '', $context = 'save' ): string {
            $slug = strtolower( trim( preg_replace( '/[^a-z0-9]+/', '-', (string) $title ), '-' ) );
            return $slug !== '' ? $slug : (string) $fallback_title;
        }
    }

    if ( ! function_exists( 'sanitize_key' ) ) {
        function sanitize_key( $key ): string {
            return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );
        }
    }

    if ( ! function_exists( 'sanitize_text_field' ) ) {
        function sanitize_text_field( $text ): string {
            return trim( (string) $text );
        }
    }

    function absint( $maybeint ): int {
        return abs( (int) $maybeint );
    }

    if ( ! function_exists( '__' ) ) {
        function __( $text, $domain = null ): string {
            return (string) $text;
        }
    }

    if ( ! function_exists( 'taxonomy_exists' ) ) {
        function taxonomy_exists( $taxonomy ): bool {
            return isset( $GLOBALS['modpress_test_taxonomies'][ (string) $taxonomy ] );
        }
    }

    if ( ! function_exists( 'post_type_exists' ) ) {
        function post_type_exists( $post_type ): bool {
            return isset( $GLOBALS['modpress_test_post_types'][ (string) $post_type ] );
        }
    }

    if ( ! function_exists( 'register_term_meta' ) ) {
        function register_term_meta( $taxonomy, $meta_key, $args = [] ): void {
            $GLOBALS['modpress_test_term_meta'][ (string) $taxonomy ][ (string) $meta_key ] = $args;
        }
    }

    function maybe_unserialize( $value ) {
        return $value;
    }

    if ( ! function_exists( 'is_wp_error' ) ) {
        function is_wp_error( $thing ): bool {
            return $thing instanceof WP_Error;
        }
    }

    function register_taxonomy( $taxonomy, $object_type, $args = [] ) {
        $GLOBALS['modpress_test_taxonomies'][ $taxonomy ] = [
            'object_type' => $object_type,
            'args' => $args,
        ];
        return (object) [ 'name' => $taxonomy ];
    }

    function add_filter( $hook_name, $callback, $priority = 10, $accepted_args = 1 ): bool {
        $GLOBALS['modpress_test_filters'][ $hook_name ][] = [
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args,
        ];
        return true;
    }

    function apply_filters( $hook_name, $value, ...$args ) {
        foreach ( $GLOBALS['modpress_test_filters'][ $hook_name ] ?? [] as $filter ) {
            $filter_args = array_slice( array_merge( [ $value ], $args ), 0, $filter['accepted_args'] );
            $value = $filter['callback']( ...$filter_args );
        }

        return $value;
    }

    function term_exists( $term, $taxonomy = '', $parent_term = null ) {
        foreach ( $GLOBALS['modpress_test_terms'][ $taxonomy ] ?? [] as $stored_term ) {
            if ( (string) $stored_term->slug === (string) $term || (int) $stored_term->term_id === (int) $term ) {
                if ( null === $parent_term || (int) $stored_term->parent === (int) $parent_term ) {
                    return [ 'term_id' => $stored_term->term_id ];
                }
            }
        }

        return 0;
    }

    function wp_insert_term( $term, $taxonomy, $args = [] ) {
        $slug = $args['slug'] ?? sanitize_title( $term );
        $existing = term_exists( $slug, $taxonomy );
        if ( $existing ) {
            return $existing;
        }

        $term_id = $GLOBALS['modpress_test_next_term_id']++;
        $GLOBALS['modpress_test_terms'][ $taxonomy ][] = (object) [
            'term_id' => $term_id,
            'slug' => $slug,
            'name' => $term,
            'parent' => (int) ( $args['parent'] ?? 0 ),
        ];

        return [ 'term_id' => $term_id ];
    }

    function get_term( $term, $taxonomy = '', $output = OBJECT, $filter = 'raw' ) {
        foreach ( $GLOBALS['modpress_test_terms'][ $taxonomy ] ?? [] as $stored_term ) {
            if ( (int) $stored_term->term_id === (int) $term ) {
                return $stored_term;
            }
        }

        return null;
    }

    function wp_get_object_terms( $object_ids, $taxonomies, $args = [] ) {
        $object_id = (int) $object_ids;
        $taxonomy = (string) $taxonomies;
        $term_ids = $GLOBALS['modpress_test_object_terms'][ $object_id ][ $taxonomy ] ?? [];
        return 'ids' === ( $args['fields'] ?? '' ) ? $term_ids : [];
    }
}

namespace ModPress\Tests\Unit {
    use ModPress\Includes\Core\Taxonomy;
    use PHPUnit\Framework\TestCase;

    final class TaxonomyTest extends TestCase {
        protected function setUp(): void {
            parent::setUp();
            $GLOBALS['modpress_test_taxonomies'] = [];
            $GLOBALS['modpress_test_terms'] = [];
            $GLOBALS['modpress_test_object_terms'] = [];
            $GLOBALS['modpress_test_filters'] = [];
            $GLOBALS['modpress_test_term_meta'] = [];
            $GLOBALS['modpress_test_next_term_id'] = 1;
        }

        public function testCreateRegistersGenericTaxonomyDefinitions(): void {
            $this->assertTrue( Taxonomy::create( 'modpress_mod_group', array( 'object_type' => array( 'modpress_mod' ), 'hierarchical' => true ) ) );
            $this->assertTrue( Taxonomy::create( 'modpress_mod_tag', array( 'object_type' => array( 'modpress_mod' ), 'hierarchical' => false ) ) );

            $this->assertContains( 'modpress_mod_group', Taxonomy::get_taxonomy_names() );
            $this->assertContains( 'modpress_mod_tag', Taxonomy::get_taxonomy_names() );
            $this->assertSame( array( 'modpress_mod' ), $GLOBALS['modpress_test_taxonomies']['modpress_mod_group']['object_type'] );
        }

        public function testDynamicallyCreateNormalizesConfigurationAndSlug(): void {
            $result = Taxonomy::dynamically_create(
                array(
                    'taxonomy' => 'Mod Group',
                    'object_type' => array( 'modpress_mod' ),
                    'hierarchical' => true,
                    'public' => true,
                )
            );

            $this->assertTrue( $result );
            $this->assertArrayHasKey( 'mod_group', $GLOBALS['modpress_test_taxonomies'] );
            $this->assertSame( array( 'modpress_mod' ), $GLOBALS['modpress_test_taxonomies']['mod_group']['object_type'] );
            $this->assertTrue( $GLOBALS['modpress_test_taxonomies']['mod_group']['args']['public'] );
        }

        public function testCreateRejectsEmptyAndDuplicateValues(): void {
            $this->assertFalse( Taxonomy::create( '', array() ) );
            $this->assertTrue( Taxonomy::create( 'modpress_mod_group', array( 'object_type' => array( 'modpress_mod' ) ) ) );
            $this->assertFalse( Taxonomy::create( 'modpress_mod_group', array( 'object_type' => array( 'modpress_mod' ) ) ) );
        }
    }
}
