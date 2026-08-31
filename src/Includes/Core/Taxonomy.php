<?php

namespace ModPress\Includes\Core;

use ModPress\Includes\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Taxonomy {
    public const GROUP = 'modpress_group';
    public const CATEGORY = 'modpress_category';
    public const TAG = 'modpress_tag';
    public const GAMES = 'modpress_game';

    /**
     * Root groups created when the taxonomy is first registered.
     *
     * @var array<string, string>
     */
    private const ROOT_GROUPS = [
        'assets' => 'Assets',
        'game-mods' => 'Game Mods',
        'software-extensions' => 'Software Extensions',
        'web-extensions' => 'Web Extensions',
    ];

    /**
     * Feature taxonomy ownership. Each taxonomy has exactly one group.
     *
     * @var array<string, string>
     */
    private const FEATURE_GROUPS = [
        self::GAMES => 'game-mods',
    ];

    public function register(): void {
        register_taxonomy( self::GROUP, [ PostType::MOD, PostType::PAGE ], self::group_args() );
        register_taxonomy( self::CATEGORY, [ PostType::MOD, PostType::PAGE ], self::category_args() );
        register_taxonomy( self::TAG, [ PostType::MOD, PostType::PAGE ], self::tag_args() );
        register_taxonomy( self::GAMES, [ PostType::MOD, PostType::PAGE ], self::games_args() );
        add_filter( 'pre_set_object_terms', [ self::class, 'restrict_game_terms' ], 10, 5 );
        self::seed_root_groups();
    }

    /**
     * Return the group assigned to a feature taxonomy.
     *
     * @param string $taxonomy Feature taxonomy name.
     * @return string Group slug.
     */
    public static function group_for( string $taxonomy ): string {
        $groups = self::feature_groups();
        $group = $groups[ $taxonomy ] ?? '';

        if ( ! is_string( $group ) || sanitize_title( $group ) !== $group || $group === '' ) {
            return '';
        }

        return $group;
    }

    /**
     * Validate that every registered feature taxonomy has one group owner.
     *
     * @return bool True when the registry is valid.
     */
    public static function has_valid_feature_groups(): bool {
        $definitions = self::feature_definitions();
        $registered = [ self::GAMES ];

        if ( count( $definitions ) !== count( $registered ) || array_diff( $registered, array_keys( $definitions ) ) ) {
            return false;
        }

        foreach ( $registered as $taxonomy ) {
            $definition = $definitions[ $taxonomy ] ?? null;
            $group = is_array( $definition ) ? ( $definition['group'] ?? '' ) : '';
            $post_types = is_array( $definition ) ? ( $definition['post_types'] ?? [] ) : [];

            if ( ! is_string( $group ) || $group === '' || sanitize_title( $group ) !== $group || ! is_array( $post_types ) || array_diff( [ PostType::MOD, PostType::PAGE ], $post_types ) ) {
                return false;
            }

            $owner = term_exists( $group, self::GROUP );
            if ( ! $owner ) {
                return false;
            }

            $owner_id = is_array( $owner ) ? absint( $owner['term_id'] ?? 0 ) : absint( $owner );
            $owner_term = $owner_id ? get_term( $owner_id, self::GROUP ) : null;
            if ( ! $owner_term || is_wp_error( $owner_term ) || (int) $owner_term->parent !== 0 ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return the feature taxonomy definitions used by ModPress consumers.
     *
     * @return array<string, array{group: string, post_types: array<int, string>}>
     */
    public static function feature_definitions(): array {
        $definitions = [];

        foreach ( self::feature_groups() as $taxonomy => $group ) {
            $definitions[ $taxonomy ] = [
                'group' => $group,
                'post_types' => [ PostType::MOD, PostType::PAGE ],
            ];
        }

        return apply_filters( 'modpress_feature_taxonomy_definitions', $definitions );
    }

    private static function feature_groups(): array {
        return apply_filters( 'modpress_feature_taxonomy_groups', self::FEATURE_GROUPS );
    }

    public static function restrict_game_terms( $terms, int $object_id, string $taxonomy, bool $append, array $old_term_taxonomy_ids ) {
        if ( self::GAMES !== $taxonomy ) {
            return $terms;
        }

        if ( ! self::has_valid_feature_groups() ) {
            return new \WP_Error( 'modpress_invalid_feature_groups', __( 'Games cannot be assigned until their content group configuration is valid.', 'modpress' ) );
        }

        $owner = term_exists( self::group_for( self::GAMES ), self::GROUP );
        $owner_id = is_array( $owner ) ? absint( $owner['term_id'] ?? 0 ) : absint( $owner );
        $assigned_groups = wp_get_object_terms( $object_id, self::GROUP, [ 'fields' => 'ids' ] );

        if ( is_wp_error( $assigned_groups ) || ! in_array( $owner_id, array_map( 'absint', (array) $assigned_groups ), true ) ) {
            return new \WP_Error( 'modpress_game_group_required', __( 'Games can only be assigned to content in the Game Mods group.', 'modpress' ) );
        }

        return $terms;
    }

    public static function group_args(): array {
        return apply_filters( 'modpress_group_taxonomy_args', [
            'labels' => [ 'name' => __( 'Groups', 'modpress' ), 'singular_name' => __( 'Group', 'modpress' ) ],
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_in_rest' => true,
            'rewrite' => [ 'slug' => self::setting_slug( 'group_slug', 'group' ) ],
        ], self::GROUP );
    }

    public static function games_args(): array {
        return apply_filters( 'modpress_games_taxonomy_args', [
            'labels' => [ 'name' => __( 'Games', 'modpress' ), 'singular_name' => __( 'Game', 'modpress' ) ],
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_in_rest' => true,
            'rewrite' => [ 'slug' => self::setting_slug( 'games_slug', 'game' ) ],
        ], self::GAMES );
    }

    private static function seed_root_groups(): void {
        $groups = apply_filters( 'modpress_root_groups', self::ROOT_GROUPS );

        foreach ( $groups as $slug => $name ) {
            $slug = sanitize_title( (string) $slug );
            $name = sanitize_text_field( (string) $name );

            if ( $slug === '' || $name === '' || term_exists( $slug, self::GROUP ) ) {
                continue;
            }

            wp_insert_term( $name, self::GROUP, [ 'slug' => $slug, 'parent' => 0 ] );
        }
    }

    /**
    * Build the hierarchical Mod category taxonomy definition.
     *
     * @return array<string, mixed> Registration arguments.
     */
    public static function category_args(): array {
        return apply_filters( 'modpress_category_taxonomy_args', [
            'labels' => [ 'name' => __( 'Mod Categories', 'modpress' ), 'singular_name' => __( 'Mod Category', 'modpress' ) ],
            'hierarchical' => true,
            'public' => true,
            'show_ui' => false,
            'show_in_rest' => true,
            'rewrite' => [ 'slug' => self::setting_slug( 'category_slug', 'mod-category' ) ],
        ], self::CATEGORY );
    }

    /**
    * Build the non-hierarchical Mod tag taxonomy definition.
     *
     * @return array<string, mixed> Registration arguments.
     */
    public static function tag_args(): array {
        return apply_filters( 'modpress_tag_taxonomy_args', [
            'labels' => [ 'name' => __( 'Mod Tags', 'modpress' ), 'singular_name' => __( 'Mod Tag', 'modpress' ) ],
            'hierarchical' => false,
            'public' => true,
            'show_ui' => false,
            'show_in_rest' => true,
            'rewrite' => [ 'slug' => self::setting_slug( 'tag_slug', 'mod-tag' ) ],
        ], self::TAG );
    }

    public static function get_taxonomy_names(): array {
        return [ self::GROUP, self::CATEGORY, self::TAG, self::GAMES ];
    }

    private static function setting_slug( string $key, string $fallback ): string {
        $value = sanitize_title( (string) Settings::get( $key, $fallback ) );
        return $value !== '' ? $value : $fallback;
    }
}
