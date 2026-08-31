<?php

namespace ModPress\Includes\Core;

use ModPress\Includes\Settings\Settings;
use ModPress\Includes\Functions\Helpers\PermalinkHelper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PostType {
    public const MOD = 'modpress_mod';
    public const PAGE = 'modpress_page';
    public const RELEASE = 'modpress_release';
    public const CHANGELOG = 'modpress_changelog';
    public const DOCUMENTATION = 'modpress_doc';
    public const MOD_CAPABILITY = 'modpress_mod';
    public const MOD_CAPABILITY_PLURAL = 'modpress_mods';
    public const PAGE_CAPABILITY = 'modpress_page';
    public const PAGE_CAPABILITY_PLURAL = 'modpress_pages';
    public const RELEASE_CAPABILITY = 'modpress_release';
    public const RELEASE_CAPABILITY_PLURAL = 'modpress_releases';
    public const CHANGELOG_CAPABILITY = 'modpress_changelog';
    public const CHANGELOG_CAPABILITY_PLURAL = 'modpress_changelogs';
    public const DOCUMENTATION_CAPABILITY = 'modpress_doc';
    public const DOCUMENTATION_CAPABILITY_PLURAL = 'modpress_docs';

    public function register(): void {
        register_post_type( self::MOD, self::mod_args() );
        register_post_type( self::PAGE, self::page_args() );
        register_post_type( self::RELEASE, self::release_args() );
        register_post_type( self::CHANGELOG, self::changelog_args() );
        register_post_type( self::DOCUMENTATION, self::documentation_args() );
        add_filter( 'post_type_link', [ PermalinkHelper::class, 'filter_page_permalink' ], 10, 2 );
        PermalinkHelper::rewrite_rule();
    }

    public static function get_post_type_name(): string {
        return self::PAGE;
    }

    public static function page_rewrite_slug(): string {
        return self::setting_slug( 'root_slug', 'mod' );
    }

    /**
     * Build the Mod container post type definition.
     *
     * @return array<string, mixed> Registration arguments.
     */
    public static function mod_args(): array {
        return apply_filters( 'modpress_mod_post_type_args', [
            'labels' => [
                'name' => __( 'Mods', 'modpress' ),
                'singular_name' => __( 'Mod', 'modpress' ),
                'add_new_item' => __( 'Add New Mod', 'modpress' ),
                'edit_item' => __( 'Edit Mod', 'modpress' ),
            ],
            'public' => false,
            'show_ui' => false,
            'show_in_rest' => true,
            'supports' => [ 'title', 'editor', 'author', 'thumbnail', 'revisions' ],
            'capability_type' => [ self::MOD_CAPABILITY, self::MOD_CAPABILITY_PLURAL ],
            'map_meta_cap' => true,
        ], self::MOD );
    }

    /**
     * Build the public Mod page post type definition.
     *
     * @return array<string, mixed> Registration arguments.
     */
    public static function page_args(): array {
        return apply_filters( 'modpress_page_post_type_args', [
            'labels' => [
                'name' => __( 'Mod Pages', 'modpress' ),
                'singular_name' => __( 'Mod Page', 'modpress' ),
                'add_new_item' => __( 'Add New Mod Page', 'modpress' ),
                'edit_item' => __( 'Edit Mod Page', 'modpress' ),
            ],
            'public' => true,
            'show_ui' => false,
            'show_in_rest' => true,
            'has_archive' => false,
            'rewrite' => [ 'slug' => self::page_rewrite_slug() ],
            'supports' => [ 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'revisions', 'page-attributes' ],
            'capability_type' => [ self::PAGE_CAPABILITY, self::PAGE_CAPABILITY_PLURAL ],
            'map_meta_cap' => true,
        ], self::PAGE );
    }

    public static function get_post_type_names(): array {
        return [ self::MOD, self::PAGE, self::RELEASE, self::CHANGELOG, self::DOCUMENTATION ];
    }

    public static function release_args(): array {
        return apply_filters( 'modpress_release_post_type_args', [
            'labels' => [ 'name' => __( 'Releases', 'modpress' ), 'singular_name' => __( 'Release', 'modpress' ) ],
            'public' => false,
            'show_ui' => false,
            'show_in_rest' => true,
            'supports' => [ 'title', 'editor', 'author', 'revisions' ],
            'capability_type' => [ self::RELEASE_CAPABILITY, self::RELEASE_CAPABILITY_PLURAL ],
            'map_meta_cap' => true,
        ], self::RELEASE );
    }

    public static function changelog_args(): array {
        return apply_filters( 'modpress_changelog_post_type_args', [
            'labels' => [ 'name' => __( 'Changelogs', 'modpress' ), 'singular_name' => __( 'Changelog', 'modpress' ) ],
            'public' => false,
            'show_ui' => false,
            'show_in_rest' => true,
            'supports' => [ 'title', 'editor', 'author', 'revisions' ],
            'capability_type' => [ self::CHANGELOG_CAPABILITY, self::CHANGELOG_CAPABILITY_PLURAL ],
            'map_meta_cap' => true,
        ], self::CHANGELOG );
    }

    public static function documentation_args(): array {
        return apply_filters( 'modpress_documentation_post_type_args', [
            'labels' => [ 'name' => __( 'Documentation', 'modpress' ), 'singular_name' => __( 'Documentation', 'modpress' ) ],
            'public' => true,
            'show_ui' => false,
            'show_in_rest' => true,
            'has_archive' => false,
            'rewrite' => [ 'slug' => self::setting_slug( 'documentation_slug', 'documentation' ) ],
            'supports' => [ 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'revisions', 'page-attributes' ],
            'capability_type' => [ self::DOCUMENTATION_CAPABILITY, self::DOCUMENTATION_CAPABILITY_PLURAL ],
            'map_meta_cap' => true,
        ], self::DOCUMENTATION );
    }

    private static function setting_slug( string $key, string $fallback ): string {
        $value = sanitize_title( (string) Settings::get( $key, $fallback ) );
        return $value !== '' ? $value : $fallback;
    }
}
