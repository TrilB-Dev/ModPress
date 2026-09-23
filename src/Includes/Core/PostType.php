<?php
/**
 * Core post type registry for ModPress.
 *
 * This is the single entry point for creating or registering custom post types.
 * It is intentionally generic and should not contain type-specific data.
 *
 * @package ModPress\Includes\Core
 */
namespace ModPress\Includes\Core;

use ModPress\Includes\Functions\Helpers\PermalinkHelper;
use ModPress\Includes\Functions\Helpers\SanitizationHelper;
use ModPress\Includes\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PostType {
	/**
	 * Legacy compatibility aliases for earlier ModPress code paths and copied plugin implementations.
	 *
	 * The core registry is slug-based, so these constants keep older references working while the
	 * runtime continues to use the dynamically registered slugs defined in ModManagement.
	 */
	public const MOD = 'modpress_mod';
	public const PAGE = 'modpress_page';
	public const MODPRESS = 'modpress_page';
	public const WIKI = 'modpress_wiki';

	/**
	 * Registered post type definitions.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static array $registered = array();

	/**
	 * Register a post type using the core API.
	 *
	 * PHP treats method names as case-insensitive, so Create() and create() are the same API.
	 *
	 * @param string $slug Post type slug.
	 * @param array<string, mixed> $config Registration config.
	 * @return bool
	 */
	public static function create( string $slug, array $config = array() ): bool {
		$slug = self::normalize_slug( $slug );
		if ( '' === $slug || post_type_exists( $slug ) ) {
			return false;
		}

		$definition = self::normalize_definition( $slug, $config );
		self::$registered[ $slug ] = $definition;
		self::register_single( $slug, $definition );

		return true;
	}

	/**
	 * Register multiple post types from a definition map.
	 *
	 * @param array<string, array<string, mixed>> $post_types Post type definitions.
	 * @return void
	 */
	public static function register_post_types( array $post_types = array() ): void {
		foreach ( $post_types as $slug => $config ) {
			self::create( (string) $slug, (array) $config );
		}
	}

	/**
	 * Create a post type from UI input.
	 *
	 * @param array<string, mixed> $data UI payload.
	 * @return bool
	 */
	public static function dynamically_create( array $data = array() ): bool {
		$slug = self::normalize_slug( $data['slug'] ?? $data['post_type'] ?? '' );
		if ( '' === $slug ) {
			return false;
		}

		$labels = array(
			'name'                  => $data['name'] ?? self::humanize_slug( $slug ),
			'singular_name'         => $data['singular_name'] ?? self::humanize_slug( $slug, true ),
			'menu_name'             => $data['menu_name'] ?? self::humanize_slug( $slug ),
			'name_admin_bar'        => $data['name_admin_bar'] ?? self::humanize_slug( $slug, true ),
			'archives'              => $data['archives'] ?? sprintf( __( 'Item Archives', 'modpress' ) ),
			'attributes'            => $data['attributes'] ?? sprintf( __( 'Item Attributes', 'modpress' ) ),
			'parent_item_colon'     => $data['parent_item_colon'] ?? sprintf( __( 'Parent Item:', 'modpress' ) ),
			'all_items'             => $data['all_items'] ?? sprintf( __( 'All %s', 'modpress' ), self::humanize_slug( $slug ) ),
			'add_new_item'          => $data['add_new_item'] ?? sprintf( __( 'Add New %s', 'modpress' ), self::humanize_slug( $slug, true ) ),
			'add_new'               => $data['add_new'] ?? __( 'Add New', 'modpress' ),
			'new_item'              => $data['new_item'] ?? sprintf( __( 'New %s', 'modpress' ), self::humanize_slug( $slug, true ) ),
			'edit_item'             => $data['edit_item'] ?? sprintf( __( 'Edit %s', 'modpress' ), self::humanize_slug( $slug, true ) ),
			'update_item'           => $data['update_item'] ?? sprintf( __( 'Update %s', 'modpress' ), self::humanize_slug( $slug, true ) ),
			'view_item'             => $data['view_item'] ?? sprintf( __( 'View %s', 'modpress' ), self::humanize_slug( $slug, true ) ),
			'view_items'            => $data['view_items'] ?? sprintf( __( 'View %s', 'modpress' ), self::humanize_slug( $slug ) ),
			'search_items'          => $data['search_items'] ?? sprintf( __( 'Search %s', 'modpress' ), self::humanize_slug( $slug ) ),
			'not_found'             => $data['not_found'] ?? __( 'Not found', 'modpress' ),
			'not_found_in_trash'    => $data['not_found_in_trash'] ?? __( 'Not found in Trash', 'modpress' ),
			'featured_image'        => $data['featured_image'] ?? __( 'Featured Image', 'modpress' ),
			'set_featured_image'    => $data['set_featured_image'] ?? __( 'Set featured image', 'modpress' ),
			'remove_featured_image' => $data['remove_featured_image'] ?? __( 'Remove featured image', 'modpress' ),
			'use_featured_image'    => $data['use_featured_image'] ?? __( 'Use as featured image', 'modpress' ),
			'insert_into_item'      => $data['insert_into_item'] ?? __( 'Insert into item', 'modpress' ),
			'uploaded_to_this_item' => $data['uploaded_to_this_item'] ?? __( 'Uploaded to this item', 'modpress' ),
			'items_list'            => $data['items_list'] ?? sprintf( __( '%s list', 'modpress' ), self::humanize_slug( $slug ) ),
			'items_list_navigation' => $data['items_list_navigation'] ?? sprintf( __( '%s list navigation', 'modpress' ), self::humanize_slug( $slug ) ),
			'filter_items_list'     => $data['filter_items_list'] ?? sprintf( __( 'Filter %s list', 'modpress' ), self::humanize_slug( $slug ) ),
		);

		$config = array(
			'label'                 => $data['label'] ?? self::humanize_slug( $slug ),
			'description'           => $data['description'] ?? '',
			'labels'                => array_merge( $labels, (array) ( $data['labels'] ?? array() ) ),
			'supports'              => self::normalize_supports( (array) ( $data['supports'] ?? array( 'title', 'editor' ) ) ),
			'taxonomies'            => self::normalize_taxonomies( $data['taxonomies'] ?? array() ),
			'hierarchical'          => self::normalize_bool( $data['hierarchical'] ?? false ),
			'public'                => self::normalize_bool( $data['public'] ?? true ),
			'show_ui'               => self::normalize_bool( $data['show_ui'] ?? true ),
			'show_in_menu'          => self::normalize_bool( $data['show_in_menu'] ?? true ),
			'menu_position'         => isset( $data['menu_position'] ) ? SanitizationHelper::integer( $data['menu_position'], 5 ) : 5,
			'menu_icon'             => isset( $data['menu_icon'] ) ? SanitizationHelper::text( $data['menu_icon'], 'dashicons-admin-post' ) : 'dashicons-admin-post',
			'show_in_admin_bar'     => self::normalize_bool( $data['show_in_admin_bar'] ?? true ),
			'show_in_nav_menus'     => self::normalize_bool( $data['show_in_nav_menus'] ?? true ),
			'can_export'            => self::normalize_bool( $data['can_export'] ?? true ),
			'has_archive'           => self::normalize_has_archive( $data['has_archive'] ?? false ),
			'exclude_from_search'   => self::normalize_bool( $data['exclude_from_search'] ?? false ),
			'publicly_queryable'    => self::normalize_bool( $data['publicly_queryable'] ?? true ),
			'query_var'             => isset( $data['query_var'] ) ? $data['query_var'] : true,
			'rewrite'               => self::normalize_rewrite( $data['rewrite'] ?? array(), $slug ),
			'capabilities'          => self::normalize_capabilities( $data['capabilities'] ?? array() ),
			'map_meta_cap'          => self::normalize_bool( $data['map_meta_cap'] ?? true ),
			'capability_type'       => $data['capability_type'] ?? 'post',
			'show_in_rest'          => self::normalize_bool( $data['show_in_rest'] ?? true ),
			'rest_base'             => isset( $data['rest_base'] ) ? SanitizationHelper::key( $data['rest_base'], $slug ) : $slug,
			'rest_controller_class' => isset( $data['rest_controller_class'] ) ? SanitizationHelper::text( $data['rest_controller_class'], 'WP_REST_Posts_Controller' ) : 'WP_REST_Posts_Controller',
		);

		foreach ( array( 'show_in_menu', 'show_in_admin_bar', 'show_in_nav_menus', 'can_export', 'public', 'show_ui', 'hierarchical', 'exclude_from_search', 'publicly_queryable', 'show_in_rest', 'map_meta_cap' ) as $bool_key ) {
			if ( array_key_exists( $bool_key, $data ) ) {
				$config[ $bool_key ] = self::normalize_bool( $data[ $bool_key ] );
			}
		}

		if ( isset( $data['menu_icon'] ) ) {
			$config['menu_icon'] = SanitizationHelper::text( $data['menu_icon'], 'dashicons-admin-post' );
		}

		if ( isset( $data['menu_position'] ) ) {
			$config['menu_position'] = SanitizationHelper::integer( $data['menu_position'], 5 );
		}

		if ( isset( $data['meta'] ) ) {
			$config['meta'] = self::normalize_meta( $data['meta'] );
		}

		if ( isset( $data['custom_meta'] ) ) {
			$config['custom_meta'] = self::normalize_meta( $data['custom_meta'] );
		}

		if ( isset( $data['supports'] ) && is_string( $data['supports'] ) ) {
			$config['supports'] = self::normalize_supports( preg_split( '/\s*,\s*/', trim( $data['supports'] ) ) );
		}

		$config = array_merge_recursive( $config, $data );
		unset( $config['slug'], $config['post_type'] );

		return self::create( $slug, $config );
	}

	/**
	 * Register the configured post types.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		foreach ( self::$registered as $slug => $config ) {
			self::register_single( $slug, $config );
		}

		add_filter( 'post_type_link', array( PermalinkHelper::class, 'filter_page_permalink' ), 10, 2 );
		PermalinkHelper::rewrite_rule();
	}

	/**
	 * Get all registered post type definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		return apply_filters( 'modpress_post_type_definitions', self::$registered );
	}

	/**
	 * Get all registered post type names.
	 *
	 * @return array<int, string>
	 */
	public static function get_post_type_names(): array {
		return array_keys( self::definitions() );
	}

	/**
	 * Build the rewrite slug for a post type.
	 *
	 * @return string
	 */
	public static function page_rewrite_slug(): string {
		return self::setting_slug( 'root_slug', 'modpress' );
	}

	/**
	 * Normalize a post type slug.
	 *
	 * @param string $slug Raw slug.
	 * @param bool $allow_hyphen Whether to allow hyphen separators.
	 * @return string
	 */
	private static function normalize_slug( string $slug, bool $allow_hyphen = false ): string {
		$slug = trim( (string) $slug );
		if ( '' === $slug ) {
			return '';
		}

		$slug = SanitizationHelper::key( str_replace( array( ' ', '/' ), array( '-', '-' ), $slug ) );
		if ( ! $allow_hyphen ) {
			$slug = str_replace( '-', '_', $slug );
		}

		return $slug;
	}

	/**
	 * Normalize post type metadata definitions.
	 *
	 * @param array<string, mixed> $meta Meta definitions.
	 * @return array<string, array<string, mixed>>
	 */
	private static function normalize_meta( array $meta ): array {
		$normalized = array();
		foreach ( $meta as $key => $value ) {
			if ( is_array( $value ) ) {
				$normalized[ (string) $key ] = $value;
				continue;
			}

			$normalized[ (string) $key ] = array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
			);
		}

		return $normalized;
	}

	/**
	 * Normalize a boolean-style value.
	 *
	 * @param mixed $value Value to normalize.
	 * @return bool
	 */
	private static function normalize_bool( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_numeric( $value ) ) {
			return (bool) $value;
		}

		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Normalize a rewrite array.
	 *
	 * @param array<string, mixed>|string $rewrite Rewrite config.
	 * @param string $slug Post type slug.
	 * @return array<string, mixed>
	 */
	private static function normalize_rewrite( $rewrite, string $slug ): array {
		$rewrite_defaults = array(
			'slug'       => self::normalize_slug( $slug, true ),
			'with_front' => true,
			'pages'      => true,
			'feeds'      => true,
		);

		if ( is_string( $rewrite ) ) {
			$rewrite = array( 'slug' => self::normalize_slug( $rewrite, true ) );
		}

		if ( ! is_array( $rewrite ) ) {
			$rewrite = array();
		}

		$rewrite = array_replace_recursive( $rewrite_defaults, $rewrite );
		if ( isset( $rewrite['slug'] ) ) {
			$rewrite['slug'] = self::normalize_slug( (string) $rewrite['slug'], true );
		}

		return $rewrite;
	}

	/**
	 * Normalize a taxonomies definition.
	 *
	 * @param mixed $taxonomies Taxonomies payload.
	 * @return array<int, string>
	 */
	private static function normalize_taxonomies( $taxonomies ): array {
		if ( is_string( $taxonomies ) ) {
			$taxonomies = array( $taxonomies );
		}

		if ( ! is_array( $taxonomies ) ) {
			return array();
		}

		$normalized = array();
		foreach ( $taxonomies as $taxonomy ) {
			if ( is_string( $taxonomy ) ) {
				$normalized[] = SanitizationHelper::key( $taxonomy );
			}
		}

		return array_values( array_unique( array_filter( $normalized ) ) );
	}

	/**
	 * Normalize the capabilities array.
	 *
	 * @param array<string, mixed> $capabilities Raw capabilities.
	 * @return array<string, string>
	 */
	private static function normalize_capabilities( array $capabilities ): array {
		$normalized = array();
		foreach ( $capabilities as $key => $capability ) {
			$normalized[ SanitizationHelper::key( $key ) ] = SanitizationHelper::key( $capability );
		}

		return $normalized;
	}

	/**
	 * Normalize has_archive values.
	 *
	 * @param mixed $value Raw value.
	 * @return bool|string
	 */
	private static function normalize_has_archive( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			return SanitizationHelper::slug( $value );
		}

		if ( is_numeric( $value ) ) {
			return (bool) $value;
		}

		return false;
	}

	/**
	 * Normalize and validate supports list.
	 *
	 * @param array<int, string> $supports Supports list.
	 * @return array<int, string>
	 */
	private static function normalize_supports( array $supports ): array {
		$valid = array( 
			'title', 
			'editor', 
			'author', 
			'thumbnail', 
			'excerpt', 
			'revisions', 
			'page-attributes', 
			'custom-fields' 
		);
		$normalized = array();

		foreach ( $supports as $support ) {
			$support = SanitizationHelper::key( $support );
			if ( in_array( $support, $valid, true ) ) {
				$normalized[] = $support;
			}
		}

		if ( empty( $normalized ) ) {
			$normalized = array( 'title', 'editor' );
		}

		return $normalized;
	}

	/**
	 * Normalize config before registration.
	 *
	 * @param string $slug Post type slug.
	 * @param array<string, mixed> $config Raw config.
	 * @return array<string, mixed>
	 */
	private static function normalize_definition( string $slug, array $config ): array {
		$definition = array(
			'label'                 => self::humanize_slug( $slug ),
			'description'           => '',
			'labels'                => array(
				'name'                  => self::humanize_slug( $slug, false ),
				'singular_name'         => self::humanize_slug( $slug, true ),
				'menu_name'             => self::humanize_slug( $slug, false ),
				'name_admin_bar'        => self::humanize_slug( $slug, true ),
				'all_items'             => sprintf( __( 'All %s', 'modpress' ), self::humanize_slug( $slug ) ),
				'add_new_item'          => sprintf( __( 'Add New %s', 'modpress' ), self::humanize_slug( $slug, true ) ),
				'edit_item'             => sprintf( __( 'Edit %s', 'modpress' ), self::humanize_slug( $slug, true ) ),
				'view_item'             => sprintf( __( 'View %s', 'modpress' ), self::humanize_slug( $slug, true ) ),
				'search_items'          => sprintf( __( 'Search %s', 'modpress' ), self::humanize_slug( $slug ) ),
				'not_found'             => __( 'Not found', 'modpress' ),
				'not_found_in_trash'    => __( 'Not found in Trash', 'modpress' ),
			),
			'supports'              => array( 'title', 'editor' ),
			'taxonomies'            => array(),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 5,
			'menu_icon'             => 'dashicons-admin-post',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			'query_var'             => true,
			'rewrite'               => array(
				'slug'       => self::normalize_slug( $slug, true ),
				'with_front' => true,
				'pages'      => true,
				'feeds'      => true,
			),
			'capabilities'          => array(),
			'map_meta_cap'          => true,
			'capability_type'       => 'post',
			'show_in_rest'          => true,
			'rest_base'             => $slug,
			'rest_controller_class' => 'WP_REST_Posts_Controller',
		);

		$definition = array_replace_recursive( $definition, $config );

		if ( isset( $definition['labels'] ) && is_array( $definition['labels'] ) ) {
			$definition['labels'] = array_merge( $definition['labels'], (array) $config['labels'] ?? array() );
		}

		if ( isset( $definition['rewrite'] ) ) {
			$definition['rewrite'] = self::normalize_rewrite( $definition['rewrite'], $slug );
		}

		if ( isset( $definition['supports'] ) ) {
			$definition['supports'] = self::normalize_supports( (array) $definition['supports'] );
		}

		if ( isset( $definition['taxonomies'] ) ) {
			$definition['taxonomies'] = self::normalize_taxonomies( $definition['taxonomies'] );
		}

		if ( isset( $definition['capabilities'] ) ) {
			$definition['capabilities'] = self::normalize_capabilities( (array) $definition['capabilities'] );
		}

		if ( isset( $definition['menu_icon'] ) ) {
			$definition['menu_icon'] = SanitizationHelper::text( $definition['menu_icon'], 'dashicons-admin-post' );
		}

		if ( isset( $definition['menu_position'] ) ) {
			$definition['menu_position'] = SanitizationHelper::integer( $definition['menu_position'], 5 );
		}

		if ( isset( $definition['has_archive'] ) ) {
			$definition['has_archive'] = self::normalize_has_archive( $definition['has_archive'] );
		}

		if ( isset( $definition['public'] ) ) {
			$definition['public'] = self::normalize_bool( $definition['public'] );
		}

		if ( isset( $definition['show_ui'] ) ) {
			$definition['show_ui'] = self::normalize_bool( $definition['show_ui'] );
		}

		if ( isset( $definition['show_in_rest'] ) ) {
			$definition['show_in_rest'] = self::normalize_bool( $definition['show_in_rest'] );
		}

		if ( isset( $definition['hierarchical'] ) ) {
			$definition['hierarchical'] = self::normalize_bool( $definition['hierarchical'] );
		}

		if ( isset( $definition['exclude_from_search'] ) ) {
			$definition['exclude_from_search'] = self::normalize_bool( $definition['exclude_from_search'] );
		}

		if ( isset( $definition['publicly_queryable'] ) ) {
			$definition['publicly_queryable'] = self::normalize_bool( $definition['publicly_queryable'] );
		}

		if ( isset( $definition['show_in_menu'] ) ) {
			$definition['show_in_menu'] = self::normalize_bool( $definition['show_in_menu'] );
		}

		if ( isset( $definition['show_in_nav_menus'] ) ) {
			$definition['show_in_nav_menus'] = self::normalize_bool( $definition['show_in_nav_menus'] );
		}

		if ( isset( $definition['show_in_admin_bar'] ) ) {
			$definition['show_in_admin_bar'] = self::normalize_bool( $definition['show_in_admin_bar'] );
		}

		if ( isset( $definition['can_export'] ) ) {
			$definition['can_export'] = self::normalize_bool( $definition['can_export'] );
		}

		if ( isset( $definition['map_meta_cap'] ) ) {
			$definition['map_meta_cap'] = self::normalize_bool( $definition['map_meta_cap'] );
		}

		if ( isset( $definition['rest_base'] ) ) {
			$definition['rest_base'] = SanitizationHelper::key( $definition['rest_base'], $slug );
		}

		if ( isset( $definition['rest_controller_class'] ) ) {
			$definition['rest_controller_class'] = SanitizationHelper::text( $definition['rest_controller_class'], 'WP_REST_Posts_Controller' );
		}

		return $definition;
	}

	/**
	 * Convert a slug into a display label.
	 *
	 * @param string $slug Raw slug.
	 * @param bool $singular Whether to render singular form.
	 * @return string
	 */
	private static function humanize_slug( string $slug, bool $singular = false ): string {
		$label = str_replace( array( '-', '_' ), ' ', $slug );
		$label = ucwords( $label );
		if ( $singular ) {
			return trim( $label );
		}
		return trim( $label );
	}

	/**
	 * Resolve the setting-driven slug for a given key.
	 *
	 * @param string $key The setting key.
	 * @param string $fallback The fallback slug.
	 * @return string
	 */
	private static function setting_slug( string $key, string $fallback ): string {
		$value = SanitizationHelper::slug( Settings::get( $key, $fallback ), $fallback );
		return '' !== $value ? $value : $fallback;
	}

	/**
	 * Register a single post type and any custom metadata fields.
	 *
	 * @param string $slug Post type slug.
	 * @param array<string, mixed> $config Definition payload.
	 * @return void
	 */
	private static function register_single( string $slug, array $config ): void {
		if ( post_type_exists( $slug ) ) {
			return;
		}

		$args = $config;
		$meta = array();

		if ( array_key_exists( 'meta', $args ) ) {
			$meta = (array) $args['meta'];
			unset( $args['meta'] );
		}

		if ( array_key_exists( 'custom_meta', $args ) ) {
			$meta = array_merge( $meta, (array) $args['custom_meta'] );
			unset( $args['custom_meta'] );
		}

		register_post_type( $slug, $args );

		if ( ! empty( $meta ) ) {
			foreach ( $meta as $meta_key => $meta_config ) {
				$meta_args = array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => static function ( $value ) {
						return SanitizationHelper::text( $value );
					},
				);

				register_post_meta( $slug, (string) $meta_key, $meta_args );
			}
		}
	}
}






