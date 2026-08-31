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

    function sanitize_title( $title, $fallback_title = '', $context = 'save' ): string {
        $slug = strtolower( trim( preg_replace( '/[^a-z0-9]+/', '-', (string) $title ), '-' ) );
        return $slug !== '' ? $slug : (string) $fallback_title;
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
            $GLOBALS['modpress_test_next_term_id'] = 1;
        }

        public function testRegisterAddsTaxonomiesFilterAndRootGroups(): void {
            ( new Taxonomy() )->register();

            $this->assertSame( Taxonomy::get_taxonomy_names(), array_keys( $GLOBALS['modpress_test_taxonomies'] ) );
            $this->assertCount( 1, $GLOBALS['modpress_test_filters']['pre_set_object_terms'] );
            $this->assertSame( 5, $GLOBALS['modpress_test_filters']['pre_set_object_terms'][0]['accepted_args'] );
            $this->assertCount( 4, $GLOBALS['modpress_test_terms'][ Taxonomy::GROUP ] );
            $this->assertSame( 4, count( array_unique( array_column( $GLOBALS['modpress_test_terms'][ Taxonomy::GROUP ], 'slug' ) ) ) );
        }

        public function testRootGroupSeedingIsIdempotent(): void {
            ( new Taxonomy() )->register();
            ( new Taxonomy() )->register();

            $this->assertCount( 4, $GLOBALS['modpress_test_terms'][ Taxonomy::GROUP ] );
        }

        public function testValidFeatureOwnershipAndGameAssignment(): void {
            ( new Taxonomy() )->register();
            $game_mods = term_exists( 'game-mods', Taxonomy::GROUP );
            $GLOBALS['modpress_test_object_terms'][42][ Taxonomy::GROUP ] = [ $game_mods['term_id'] ];

            $this->assertTrue( Taxonomy::has_valid_feature_groups() );
            $this->assertSame( 'game-mods', Taxonomy::group_for( Taxonomy::GAMES ) );
            $this->assertSame( [ 'game-term' ], Taxonomy::restrict_game_terms( [ 'game-term' ], 42, Taxonomy::GAMES, false, [] ) );
        }

        public function testInvalidOwnershipConfigurationsFailClosed(): void {
            ( new Taxonomy() )->register();

            add_filter( 'modpress_feature_taxonomy_groups', static function (): array {
                return [ Taxonomy::GAMES => 'missing-owner' ];
            } );
            $this->assertFalse( Taxonomy::has_valid_feature_groups() );
            $this->assertInstanceOf( \WP_Error::class, Taxonomy::restrict_game_terms( [ 1 ], 42, Taxonomy::GAMES, false, [] ) );

            $GLOBALS['modpress_test_filters'] = [];
            add_filter( 'modpress_feature_taxonomy_groups', static function (): array {
                return [ Taxonomy::GAMES => 'Game Mods' ];
            } );
            $this->assertFalse( Taxonomy::has_valid_feature_groups() );

            $GLOBALS['modpress_test_filters'] = [];
            add_filter( 'modpress_feature_taxonomy_definitions', static function (): array {
                return [];
            } );
            $this->assertFalse( Taxonomy::has_valid_feature_groups() );
        }

        public function testGameAssignmentIsRejectedOutsideGameMods(): void {
            ( new Taxonomy() )->register();
            $assets = term_exists( 'assets', Taxonomy::GROUP );
            $GLOBALS['modpress_test_object_terms'][42][ Taxonomy::GROUP ] = [ $assets['term_id'] ];

            $result = Taxonomy::restrict_game_terms( [ 'game-term' ], 42, Taxonomy::GAMES, false, [] );

            $this->assertInstanceOf( \WP_Error::class, $result );
            $this->assertSame( 'modpress_game_group_required', $result->get_error_code() );
        }

        public function testNonRootOwnerIsInvalid(): void {
            ( new Taxonomy() )->register();
            $parent = term_exists( 'assets', Taxonomy::GROUP );
            foreach ( $GLOBALS['modpress_test_terms'][ Taxonomy::GROUP ] as $term ) {
                if ( 'game-mods' === $term->slug ) {
                    $term->parent = $parent['term_id'];
                }
            }

            $this->assertFalse( Taxonomy::has_valid_feature_groups() );
        }
    }
}
