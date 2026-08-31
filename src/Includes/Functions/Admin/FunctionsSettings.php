<?php
/**
 * Settings-related admin functions for ModPress.
 *
 * @package ModPress
 * @subpackage Includes\Functions\Admin
 * @since 1.0.0
 */
namespace ModPress\Includes\Functions\Admin;

use ModPress\Includes\Functions\Helpers\PermalinkHelper;
use ModPress\Includes\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class FunctionsSettings {
    /**
     * Plugin functions used to collect provider-backed settings pages.
     *
     * @var FunctionsPlugins
     */
    private FunctionsPlugins $plugin_functions;

    public function __construct( FunctionsPlugins $plugin_functions ) {
        $this->plugin_functions = $plugin_functions;
    }

    /**
     * Register ModPress and provider-backed plugin settings.
     *
     * @return void
     */
    public function register_settings(): void {
        register_setting( 'modpress_settings', 'modpress_general', [ 'sanitize_callback' => [ $this, 'sanitize_general' ] ] );
        register_setting( 'modpress_settings', 'modpress_layout', [ 'sanitize_callback' => [ $this, 'sanitize_layout' ] ] );
        register_setting( 'modpress_settings', 'modpress_access', [ 'sanitize_callback' => [ $this, 'sanitize_access' ] ] );
        register_setting( 'modpress_settings', 'modpress_tools', [ 'sanitize_callback' => [ $this, 'sanitize_tools' ] ] );

        foreach ( $this->plugin_functions->plugin_settings_pages() as $page ) {
            register_setting(
                'modpress_settings',
                'modpress_' . $page['slug'],
                [ 'sanitize_callback' => $page['provider']->sanitize_settings( ... ) ]
            );
        }
    }

    public function sanitize_general( $input ): array {
        if ( ! current_user_can( 'modpress_settings_general_edit' ) ) {
            return (array) Settings::get_group( Settings::GENERAL, [] );
        }
        $input = is_array( $input ) ? $input : [];
        $rewrite_changed = false;
        foreach ( [ 'root_name', 'root_description', 'archive_title', 'archive_description', 'root_slug', 'category_slug', 'tag_slug', 'permalink', 'enable_schema' ] as $key ) {
            $value = in_array( $key, [ 'root_slug', 'category_slug', 'tag_slug' ], true ) ? sanitize_title( $input[ $key ] ?? '' ) : ( 'permalink' === $key ? PermalinkHelper::sanitize_pattern( $input[ $key ] ?? '' ) : ( 'enable_schema' === $key ? ! empty( $input[ $key ] ) : sanitize_textarea_field( $input[ $key ] ?? '' ) ) );
            $rewrite_changed = $rewrite_changed || $value !== (string) Settings::get( $key, '' );
            $input[ $key ] = $value;
            Settings::set( $key, $input[ $key ] );
        }
        if ( $rewrite_changed ) {
            flush_rewrite_rules();
        }
        return $input;
    }

    public function sanitize_layout( $input ): array {
        if ( ! current_user_can( 'modpress_settings_layout_edit' ) ) {
            return (array) Settings::get_group( Settings::LAYOUT, [] );
        }
        $input = is_array( $input ) ? $input : [];
        $section = sanitize_key( $input['layout_section'] ?? 'general' );
        unset( $input['layout_section'] );
        $section_keys = [
            'general' => [ 'show_search', 'show_breadcrumbs', 'show_sidebar' ],
            'search' => [ 'show_search', 'search_placeholder', 'search_button_text', 'search_scope', 'search_no_results_message', 'search_results_count', 'search_min_chars', 'search_live_results' ],
            'sidebar' => [ 'show_sidebar', 'sidebar_position', 'sidebar_width', 'sidebar_sticky', 'sidebar_show_categories', 'sidebar_show_category_count', 'sidebar_expand_categories', 'sidebar_show_page_count' ],
            'page' => [ 'page_show_title', 'show_breadcrumbs', 'page_show_toc', 'page_toc_position', 'toc_min_level', 'toc_max_level', 'show_last_updated', 'show_author', 'show_reading_time', 'reading_time_wpm', 'show_feedback', 'page_show_navigation', 'show_related_pages', 'related_pages_count' ],
        ];
        $active_keys = $section_keys[ $section ] ?? array_merge( ...array_values( $section_keys ) );
        foreach ( [ 'show_search', 'show_toc', 'show_breadcrumbs', 'show_last_updated', 'show_author', 'show_reading_time', 'show_feedback', 'show_related_pages', 'search_live_results', 'show_sidebar', 'sidebar_sticky', 'sidebar_show_categories', 'sidebar_show_category_count', 'sidebar_expand_categories', 'sidebar_show_page_count', 'page_show_title', 'page_show_toc', 'page_show_navigation' ] as $key ) {
            if ( ! in_array( $key, $active_keys, true ) ) {
                continue;
            }
            $value = ! empty( $input[ $key ] );
            $input[ $key ] = $value;
            Settings::set( $key, $value );
        }
        foreach ( [ 'search_placeholder', 'search_button_text', 'search_no_results_message' ] as $key ) {
            if ( ! in_array( $key, $active_keys, true ) ) {
                continue;
            }
            $input[ $key ] = sanitize_text_field( $input[ $key ] ?? '' );
            Settings::set( $key, $input[ $key ] );
        }
        if ( in_array( 'search_scope', $active_keys, true ) ) {
            $input['search_scope'] = in_array( $input['search_scope'] ?? '', [ 'all', 'title', 'content' ], true ) ? $input['search_scope'] : 'all';
            Settings::set( 'search_scope', $input['search_scope'] );
        }
        if ( in_array( 'sidebar_position', $active_keys, true ) ) {
            $input['sidebar_position'] = in_array( $input['sidebar_position'] ?? '', [ 'left', 'right' ], true ) ? $input['sidebar_position'] : 'left';
            Settings::set( 'sidebar_position', $input['sidebar_position'] );
        }
        if ( in_array( 'page_toc_position', $active_keys, true ) ) {
            $input['page_toc_position'] = in_array( $input['page_toc_position'] ?? '', [ 'sidebar', 'content' ], true ) ? $input['page_toc_position'] : 'sidebar';
            Settings::set( 'page_toc_position', $input['page_toc_position'] );
        }
        foreach ( [ 'related_pages_count' => [ 1, 12 ], 'search_results_count' => [ 1, 50 ], 'search_min_chars' => [ 1, 5 ], 'sidebar_width' => [ 180, 480 ], 'toc_min_level' => [ 1, 5 ], 'toc_max_level' => [ 2, 6 ], 'reading_time_wpm' => [ 100, 400 ] ] as $key => [ $minimum, $maximum ] ) {
            if ( ! in_array( $key, $active_keys, true ) ) {
                continue;
            }
            $input[ $key ] = max( $minimum, min( $maximum, absint( $input[ $key ] ?? $minimum ) ) );
            Settings::set( $key, $input[ $key ] );
        }
        return $input;
    }

    public function sanitize_access( $input ): array {
        if ( ! current_user_can( 'modpress_settings_access_edit' ) ) {
            return (array) Settings::get_group( Settings::ACCESS, [] );
        }
        $input = is_array( $input ) ? $input : [];
        $allowed = [ 'manage_options', 'edit_posts', 'publish_posts' ];
        foreach ( [ 'create_mods', 'write_pages', 'view_analytics', 'manage_plugins' ] as $key ) {
            $values = is_array( $input[ $key ] ?? null ) ? $input[ $key ] : [ $input[ $key ] ?? 'manage_options' ];
            $values = array_values( array_unique( array_intersect( $allowed, array_map( 'sanitize_key', $values ) ) ) );
            $input[ $key ] = empty( $values ) ? [ 'manage_options' ] : $values;
            Settings::set( $key, $input[ $key ] );
        }
        return $input;
    }

    public function sanitize_tools( $input ): array {
        $input = is_array( $input ) ? $input : [];
        foreach ( [ 'debug_logging', 'console_logging' ] as $key ) {
            $input[ $key ] = ! empty( $input[ $key ] );
            Settings::set( $key, $input[ $key ] );
        }
        return $input;
    }
}