<?php
/**
 * Settings-related admin functions for ModPress.
 *
 * @package ModPress
 * @subpackage Includes\Functions\Admin
 * @since 1.0.0
 */
namespace ModPress\Includes\Functions\Admin;

use ModPress\Includes\Functions\Admin\FunctionsPlugins;
use ModPress\Includes\Functions\Helpers\PermalinkHelper;
use ModPress\Includes\Settings\Settings;
use ModPress\Includes\Functions\Helpers\LoaderHelper;

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
        // ModPress stores settings in its own custom table rather than the default
        // WordPress options table. The regular settings API is intentionally not used
        // here; form submissions are handled through the custom admin-post flow.
    }

    /**
	 * Register the WordPress admin-post actions used by the settings forms.
	 *
	 * @param LoaderHelper $loader Loader instance used to register hooks.
	 * @return void
	 */
	public function register_admin_post_hooks( LoaderHelper $loader ): void {
		$loader->register_component(
			$this,
			array(
				array(
					'type'     => 'action',
					'hook'     => 'admin_post_modpress_save_general_settings',
					'callback' => 'handle_general_save',
				),
				array(
					'type'     => 'action',
					'hook'     => 'admin_post_modpress_save_access_settings',
					'callback' => 'handle_access_save',
				),
				array(
					'type'     => 'action',
					'hook'     => 'admin_post_modpress_save_layout_settings',
					'callback' => 'handle_layout_save',
				),
				array(
					'type'     => 'action',
					'hook'     => 'admin_post_modpress_save_billing_invoice_settings',
					'callback' => 'handle_billing_invoice_save',
				),
			)
		)->run();
	}
    /**
	 * Save settings using the direct admin POST flow used by the custom table store.
	 *
	 * @return void
	 */
	public function save_settings(): void {
		$action = isset( $_POST['action'] ) ? sanitize_key( wp_unslash( $_POST['action'] ) ) : '';
		if ( '' === $action ) {
			return;
		}

		if ( 'modpress_save_general_settings' === $action ) {
			$this->general_save();
		}

		if ( in_array( $action, array( 'modpress_save_access_settings' ), true ) ) {
			$this->access_save();
		}

		if ( 'modpress_save_layout_settings' === $action ) {
			$this->layout_save();
		}
	}

	/**
	 * Validate a save nonce against the standard WordPress field and the legacy plugin-specific field.
	 *
	 * @param string $action Nonce action.
	 * @return void
     * @since 1.0.0
	 */
	private function validate_save_nonce( string $action ): void {
		if ( isset( $_REQUEST['_wpnonce'] ) ) {
			check_admin_referer( $action, '_wpnonce' );
			return;
		}

		check_admin_referer( $action );
	}
    /**
	 * Save the general settings.
	 *
	 * @return void
     * @since 1.0.0
	 */
	public function general_save(): void {
		if ( ! user_can( get_current_user_id(), 'modpress_settings_general_edit' ) ) {
			wp_die( esc_html__( 'You are not allowed to save ModPress general settings.', 'modpress' ), 403 );
		}

		$this->validate_save_nonce( 'modpress_save_general_settings' );
		$input = isset( $_POST['modpress_general'] ) && is_array( $_POST['modpress_general'] ) ? wp_unslash( $_POST['modpress_general'] ) : array();
		$this->sanitize_general( $input );
		wp_safe_redirect( admin_url( 'admin.php?page=modpress&group=settings&tab=general' ) );
		exit;
	}

	/**
	 * Save the access settings.
	 *
	 * @return void
	 */
	public function access_save(): void {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'modpress_settings_access_edit' ) ) {
			wp_die( esc_html__( 'You are not allowed to save ModPress access settings.', 'modpress' ), 403 );
		}

		$this->validate_save_nonce( 'modpress_save_access_settings' );
		$input = isset( $_POST['modpress_access'] ) && is_array( $_POST['modpress_access'] ) ? wp_unslash( $_POST['modpress_access'] ) : array();
		$sanitized = $this->sanitize_access( $input );
		Settings::set_group( Settings::ACCESS, $sanitized );
		foreach ( $sanitized as $key => $value ) {
			Settings::set( $key, $value );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=modpress&group=settings&tab=access' ) );
		exit;
	}

	/**
	 * Save the layout settings.
	 *
	 * @return void
	 */
	public function layout_save(): void {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'modpress_settings_layout_edit' ) ) {
			wp_die( esc_html__( 'You are not allowed to save ModPress layout settings.', 'modpress' ), 403 );
		}

		$this->validate_save_nonce( 'modpress_save_layout_settings' );
		$input = isset( $_POST['modpress_layout'] ) && is_array( $_POST['modpress_layout'] ) ? wp_unslash( $_POST['modpress_layout'] ) : array();
		$section = sanitize_key( $input['layout_section'] ?? 'general' );
		$sanitized = $this->sanitize_layout( $input );
		Settings::set_group( Settings::LAYOUT, $sanitized );
		foreach ( $sanitized as $key => $value ) {
			Settings::set( $key, $value );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=modpress&group=settings&tab=layout&layout_section=' . rawurlencode( $section ) ) );
		exit;
	}
    /**
	 * Sanitize the general settings input.
	 *
	 * @param array $input The input data to sanitize.
	 * @return array The sanitized general settings.
     * @since 1.0.0
	 */
    public function sanitize_general( $input ): array {
        if ( ! current_user_can( 'modpress_settings_general_edit' ) ) {
            return (array) Settings::get_group( Settings::GENERAL, [] );
        }
        $input = is_array( $input ) ? $input : array();
        $root_name = $input['root_name'] ?? '';
        $root_description = $input['root_description'] ?? '';
        $archive_title = $input['archive_title'] ?? '';
        $archive_description = $input['archive_description'] ?? '';
        $root_slug = $input['root_slug'] ?? '';
        $category_slug = $input['category_slug'] ?? '';
        $tag_slug = $input['tag_slug'] ?? '';
        $permalink = $input['permalink'] ?? '';
        $enable_schema = ! empty( $input['enable_schema'] );
        $rewrite_changed = false;

        if ( $rewrite_changed ) {
            flush_rewrite_rules();
        }

        $general = array(
            'root_name' => sanitize_text_field( $root_name ),
            'root_description' => sanitize_textarea_field( $root_description ),
            'archive_title' => sanitize_text_field( $archive_title ),
            'archive_description' => sanitize_textarea_field( $archive_description ),
            'root_slug' => sanitize_title( $root_slug ),
            'category_slug' => sanitize_title( $category_slug ),
            'tag_slug' => sanitize_title( $tag_slug ),
            'permalink' => PermalinkHelper::sanitize_pattern( $permalink ),
            'enable_schema' => $enable_schema,
        );
        foreach ( $general as $key => $value ) {
			$input[ $key ] = $value;
			Settings::set( $key, $value );
		}

		Settings::set_group( Settings::GENERAL, $general );
		update_option( 'licencepress_general', $general );

		return $general;
    }

    public function sanitize_layout( $input ): array {
        if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'modpress_settings_layout_edit' ) ) {
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
        }
        foreach ( [ 'search_placeholder', 'search_button_text', 'search_no_results_message' ] as $key ) {
            if ( ! in_array( $key, $active_keys, true ) ) {
                continue;
            }
            $input[ $key ] = sanitize_text_field( $input[ $key ] ?? '' );
        }
        if ( in_array( 'search_scope', $active_keys, true ) ) {
            $input['search_scope'] = in_array( $input['search_scope'] ?? '', [ 'all', 'title', 'content' ], true ) ? $input['search_scope'] : 'all';
        }
        if ( in_array( 'sidebar_position', $active_keys, true ) ) {
            $input['sidebar_position'] = in_array( $input['sidebar_position'] ?? '', [ 'left', 'right' ], true ) ? $input['sidebar_position'] : 'left';
        }
        if ( in_array( 'page_toc_position', $active_keys, true ) ) {
            $input['page_toc_position'] = in_array( $input['page_toc_position'] ?? '', [ 'sidebar', 'content' ], true ) ? $input['page_toc_position'] : 'sidebar';
        }
        foreach ( [ 'related_pages_count' => [ 1, 12 ], 'search_results_count' => [ 1, 50 ], 'search_min_chars' => [ 1, 5 ], 'sidebar_width' => [ 180, 480 ], 'toc_min_level' => [ 1, 5 ], 'toc_max_level' => [ 2, 6 ], 'reading_time_wpm' => [ 100, 400 ] ] as $key => [ $minimum, $maximum ] ) {
            if ( ! in_array( $key, $active_keys, true ) ) {
                continue;
            }
            $input[ $key ] = max( $minimum, min( $maximum, absint( $input[ $key ] ?? $minimum ) ) );
        }
        return $input;
    }

    public function sanitize_access( $input ): array {
        if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'modpress_settings_access_edit' ) ) {
            return (array) Settings::get_group( Settings::ACCESS, [] );
        }
        $input = is_array( $input ) ? $input : [];
        $allowed = [ 'manage_options', 'edit_posts', 'publish_posts' ];
        foreach ( [ 'create_mods', 'write_pages', 'view_analytics', 'manage_plugins' ] as $key ) {
            $values = is_array( $input[ $key ] ?? null ) ? $input[ $key ] : [ $input[ $key ] ?? 'manage_options' ];
            $values = array_values( array_unique( array_intersect( $allowed, array_map( 'sanitize_key', $values ) ) ) );
            $input[ $key ] = empty( $values ) ? [ 'manage_options' ] : $values;
        }
        return $input;
    }
    public function sanitize_tools( $input ): array {
        $input = is_array( $input ) ? $input : [];
        foreach ( [ 'debug_logging', 'console_logging' ] as $key ) {
            $input[ $key ] = ! empty( $input[ $key ] );
        }
        return $input;
    }
}