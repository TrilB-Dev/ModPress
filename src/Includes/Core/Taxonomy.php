<?php
/**
 * Core taxonomy registry for ModPress.
 *
 * Supports multiple taxonomies and dynamic metadata registration.
 *
 * @package ModPress
 * @subpackage Includes\Core
 * @since 1.0.0
 */
namespace ModPress\Includes\Core;

use ModPress\Includes\Functions\Helpers\SanitizationHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Taxonomy {
	/**
	 * Registered taxonomy definitions.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static array $registered = array();

	/**
	 * Register the configured taxonomies.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		foreach ( self::definitions() as $taxonomy => $config ) {
			self::register_single( $taxonomy, $config );
		}
	}

	/**
	 * Create a taxonomy from a generic definition.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param array<string, mixed> $config Registration config.
	 * @return bool
	 */
	public static function create( string $taxonomy, array $config = array() ): bool {
		$taxonomy = self::normalize_taxonomy( $taxonomy );
		if ( '' === $taxonomy || taxonomy_exists( $taxonomy ) ) {
			return false;
		}

		$definition = self::normalize_definition( $taxonomy, $config );
		self::$registered[ $taxonomy ] = $definition;
		self::register_single( $taxonomy, $definition );

		return true;
	}

	/**
	 * Create a taxonomy from UI input.
	 *
	 * @param array<string, mixed> $data UI payload.
	 * @return bool
	 */
	public static function dynamically_create( array $data = array() ): bool {
		$taxonomy = self::normalize_taxonomy( $data['taxonomy'] ?? $data['slug'] ?? '' );
		if ( '' === $taxonomy ) {
			return false;
		}

		$labels = array(
			'name'          => $data['name'] ?? self::humanize_slug( $taxonomy ),
			'singular_name' => $data['singular_name'] ?? self::humanize_slug( $taxonomy, true ),
			'menu_name'     => $data['menu_name'] ?? self::humanize_slug( $taxonomy ),
			'all_items'     => $data['all_items'] ?? sprintf( __( 'All %s', 'modpress' ), self::humanize_slug( $taxonomy ) ),
			'edit_item'     => $data['edit_item'] ?? sprintf( __( 'Edit %s', 'modpress' ), self::humanize_slug( $taxonomy, true ) ),
			'view_item'     => $data['view_item'] ?? sprintf( __( 'View %s', 'modpress' ), self::humanize_slug( $taxonomy, true ) ),
			'update_item'   => $data['update_item'] ?? sprintf( __( 'Update %s', 'modpress' ), self::humanize_slug( $taxonomy, true ) ),
			'add_new_item'  => $data['add_new_item'] ?? sprintf( __( 'Add New %s', 'modpress' ), self::humanize_slug( $taxonomy, true ) ),
			'new_item_name' => $data['new_item_name'] ?? sprintf( __( 'New %s Name', 'modpress' ), self::humanize_slug( $taxonomy, true ) ),
			'parent_item'   => $data['parent_item'] ?? sprintf( __( 'Parent %s', 'modpress' ), self::humanize_slug( $taxonomy, true ) ),
			'search_items'  => $data['search_items'] ?? sprintf( __( 'Search %s', 'modpress' ), self::humanize_slug( $taxonomy ) ),
			'popular_items' => $data['popular_items'] ?? sprintf( __( 'Popular %s', 'modpress' ), self::humanize_slug( $taxonomy ) ),
		);

		$config = array(
			'object_type'          	=> self::normalize_object_types( $data['object_type'] ?? $data['object_types'] ?? array() ),
			'label'                	=> $data['label'] ?? self::humanize_slug( $taxonomy ),
			'description'          	=> $data['description'] ?? '',
			'labels'               	=> array_merge( $labels, (array) ( $data['labels'] ?? array() ) ),
			'hierarchical'         	=> self::normalize_bool( $data['hierarchical'] ?? false ),
			'public'               	=> self::normalize_bool( $data['public'] ?? true ),
			'show_ui'              	=> self::normalize_bool( $data['show_ui'] ?? true ),
			'show_in_menu'         	=> self::normalize_bool( $data['show_in_menu'] ?? true ),
			'show_tagcloud'        	=> self::normalize_bool( $data['show_tagcloud'] ?? true ),
			'show_in_quick_edit'   	=> self::normalize_bool( $data['show_in_quick_edit'] ?? true ),
			'show_admin_column'    	=> self::normalize_bool( $data['show_admin_column'] ?? false ),
			'meta_box_cb'          	=> $data['meta_box_cb'] ?? null,
			'sort'                 	=> self::normalize_bool( $data['sort'] ?? false ),
			'update_count_callback' => $data['update_count_callback'] ?? null,
			'query_var'            	=> isset( $data['query_var'] ) ? $data['query_var'] : true,
			'rewrite'              	=> self::normalize_rewrite( $data['rewrite'] ?? array(), $taxonomy ),
			'capabilities'         	=> self::normalize_capabilities( (array) ( $data['capabilities'] ?? array() ) ),
			'default_term'         	=> isset( $data['default_term'] ) ? $data['default_term'] : null,
			'show_in_rest'         	=> self::normalize_bool( $data['show_in_rest'] ?? true ),
			'rest_base'            	=> isset( $data['rest_base'] ) ? SanitizationHelper::key( $data['rest_base'], $taxonomy ) : $taxonomy,
			'rest_namespace'       	=> isset( $data['rest_namespace'] ) ? SanitizationHelper::key( $data['rest_namespace'], 'wp/v2' ) : 'wp/v2',
			'rest_controller_class' => isset( $data['rest_controller_class'] ) ? SanitizationHelper::text( $data['rest_controller_class'], 'WP_REST_Terms_Controller' ) : 'WP_REST_Terms_Controller',
		);

		if ( isset( $data['meta'] ) ) {
			$config['meta'] = self::normalize_meta( $data['meta'] );
		}

		if ( isset( $data['custom_meta'] ) ) {
			$config['custom_meta'] = self::normalize_meta( $data['custom_meta'] );
		}

		return self::create( $taxonomy, $config );
	}

	/**
	 * Register multiple taxonomies using a definition map.
	 *
	 * @param array<string, array<string, mixed>> $taxonomies Taxonomy definitions.
	 * @return void
	 */
	public static function register_taxonomies( array $taxonomies = array() ): void {
		foreach ( $taxonomies as $taxonomy => $config ) {
			self::create( (string) $taxonomy, (array) $config );
		}
	}

	/**
	 * Get all taxonomy definitions for the plugin.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		return apply_filters( 'modpress_taxonomy_definitions', self::$registered );
	}

	/**
	 * Register a single taxonomy and any custom term metadata.
	 *
	 * @param string $taxonomy Taxonomy key.
	 * @param array<string, mixed> $config Definition payload.
	 * @return void
	 */
	private static function register_single( string $taxonomy, array $config ): void {
		if ( taxonomy_exists( $taxonomy ) ) {
			return;
		}

		$args = $config;
		$meta = array();

		if ( array_key_exists( 'object_type', $args ) ) {
			$object_types = $args['object_type'];
			unset( $args['object_type'] );
		} elseif ( array_key_exists( 'object_types', $args ) ) {
			$object_types = $args['object_types'];
			unset( $args['object_types'] );
		} else {
			$object_types = array();
		}

		if ( array_key_exists( 'meta', $args ) ) {
			$meta = (array) $args['meta'];
			unset( $args['meta'] );
		}

		if ( array_key_exists( 'custom_meta', $args ) ) {
			$meta = array_merge( $meta, (array) $args['custom_meta'] );
			unset( $args['custom_meta'] );
		}

		register_taxonomy( $taxonomy, self::normalize_object_types( $object_types ), $args );

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

				if ( is_array( $meta_config ) ) {
					$meta_args = array_merge( $meta_args, $meta_config );
				}

				register_term_meta( $taxonomy, (string) $meta_key, $meta_args );
			}
		}
	}

	/**
	 * Get all taxonomy identifiers for the plugin.
	 *
	 * @return array<int, string>
	 */
	public static function get_taxonomy_names(): array {
		return array_keys( self::definitions() );
	}

	/**
	 * Normalize a taxonomy slug.
	 *
	 * @param string $taxonomy Raw taxonomy slug.
	 * @return string
	 */
	private static function normalize_taxonomy( string $taxonomy ): string {
		$taxonomy = trim( (string) $taxonomy );
		if ( '' === $taxonomy ) {
			return '';
		}

		return SanitizationHelper::key( str_replace( array( ' ', '/' ), array( '-', '-' ), $taxonomy ) );
	}

	/**
	 * Normalize a taxonomy object type list.
	 *
	 * @param mixed $object_type Object type payload.
	 * @return array<int, string>
	 */
	private static function normalize_object_types( $object_type ): array {
		if ( is_string( $object_type ) ) {
			$object_type = array( $object_type );
		}

		if ( ! is_array( $object_type ) ) {
			return array();
		}

		$normalized = array();
		foreach ( $object_type as $type ) {
			if ( is_string( $type ) ) {
				$normalized[] = SanitizationHelper::key( $type );
			}
		}

		return array_values( array_filter( array_unique( $normalized ) ) );
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
	 * Normalize a rewrite array for a taxonomy.
	 *
	 * @param array<string, mixed>|string $rewrite Rewrite config.
	 * @param string $taxonomy Taxonomy slug.
	 * @return array<string, mixed>
	 */
	private static function normalize_rewrite( $rewrite, string $taxonomy ): array {
		$defaults = array(
			'slug'       => self::normalize_taxonomy( $taxonomy ),
			'with_front' => true,
			'pages'      => true,
			'feeds'      => true,
		);

		if ( is_string( $rewrite ) ) {
			$rewrite = array( 'slug' => $rewrite );
		}

		if ( ! is_array( $rewrite ) ) {
			$rewrite = array();
		}

		$rewrite = array_replace_recursive( $defaults, $rewrite );
		if ( isset( $rewrite['slug'] ) ) {
			$rewrite['slug'] = self::normalize_taxonomy( (string) $rewrite['slug'] );
		}

		return $rewrite;
	}
	/**
	 * Normalize the capabilities array.
	 *
	 * @param array<string, mixed> $capabilities Raw capabilities.
	 * @return array<string, string>
	 */
	private static function normalize_capabilities( array $capabilities ): array {
		$normalized = array();
		foreach ( $capabilities as $key => $value ) {
			$normalized[ SanitizationHelper::key( $key ) ] = SanitizationHelper::key( $value );
		}

		return $normalized;
	}
	/**
	 * Normalize term meta definitions.
	 *
	 * @param array<string, mixed> $meta Term meta definitions.
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
				'sanitize_callback' => static function ( $value ) {
					return SanitizationHelper::text( $value );
				},
			);
		}

		return $normalized;
	}
	/**
	 * Normalize taxonomy definition before registration.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param array<string, mixed> $config Raw config.
	 * @return array<string, mixed>
	 */
	private static function normalize_definition( string $taxonomy, array $config ): array {
		$definition = array(
			'label'                => self::humanize_slug( $taxonomy ),
			'description'          => '',
			'labels'               => array(
				'name'          => self::humanize_slug( $taxonomy ),
				'singular_name' => self::humanize_slug( $taxonomy, true ),
				'menu_name'     => self::humanize_slug( $taxonomy ),
				'all_items'     => sprintf( __( 'All %s', 'modpress' ), self::humanize_slug( $taxonomy ) ),
				'edit_item'     => sprintf( __( 'Edit %s', 'modpress' ), self::humanize_slug( $taxonomy, true ) ),
				'view_item'     => sprintf( __( 'View %s', 'modpress' ), self::humanize_slug( $taxonomy, true ) ),
				'update_item'   => sprintf( __( 'Update %s', 'modpress' ), self::humanize_slug( $taxonomy, true ) ),
				'add_new_item'  => sprintf( __( 'Add New %s', 'modpress' ), self::humanize_slug( $taxonomy, true ) ),
				'new_item_name' => sprintf( __( 'New %s Name', 'modpress' ), self::humanize_slug( $taxonomy, true ) ),
				'parent_item'   => sprintf( __( 'Parent %s', 'modpress' ), self::humanize_slug( $taxonomy, true ) ),
				'search_items'  => sprintf( __( 'Search %s', 'modpress' ), self::humanize_slug( $taxonomy ) ),
			),
			'object_type'          => array(),
			'hierarchical'         => false,
			'public'               => true,
			'show_ui'              => true,
			'show_in_menu'         => true,
			'show_tagcloud'        => true,
			'show_in_quick_edit'   => true,
			'show_admin_column'    => false,
			'sort'                 => false,
			'query_var'            => true,
			'rewrite'              => array(
				'slug'       => self::normalize_taxonomy( $taxonomy ),
				'with_front' => true,
				'pages'      => true,
				'feeds'      => true,
			),
			'capabilities'         => array(),
			'default_term'         => null,
			'show_in_rest'         => true,
			'rest_base'            => $taxonomy,
			'rest_namespace'       => 'wp/v2',
			'rest_controller_class' => 'WP_REST_Terms_Controller',
			'meta'                 => array(),
		);

		$definition = array_replace_recursive( $definition, $config );

		if ( isset( $definition['object_type'] ) ) {
			$definition['object_type'] = self::normalize_object_types( $definition['object_type'] );
		}

		if ( isset( $definition['object_types'] ) ) {
			$definition['object_type'] = self::normalize_object_types( $definition['object_types'] );
			unset( $definition['object_types'] );
		}

		if ( isset( $definition['labels'] ) && is_array( $definition['labels'] ) ) {
			$definition['labels'] = array_merge( $definition['labels'], (array) ( $config['labels'] ?? array() ) );
		}

		if ( isset( $definition['rewrite'] ) ) {
			$definition['rewrite'] = self::normalize_rewrite( $definition['rewrite'], $taxonomy );
		}

		if ( isset( $definition['capabilities'] ) ) {
			$definition['capabilities'] = self::normalize_capabilities( (array) $definition['capabilities'] );
		}

		if ( isset( $definition['meta'] ) ) {
			$definition['meta'] = self::normalize_meta( (array) $definition['meta'] );
		}

		if ( isset( $definition['custom_meta'] ) ) {
			$definition['meta'] = array_merge( $definition['meta'], self::normalize_meta( (array) $definition['custom_meta'] ) );
			unset( $definition['custom_meta'] );
		}

		foreach ( array( 'public', 'show_ui', 'show_in_menu', 'show_tagcloud', 'show_in_quick_edit', 'show_admin_column', 'sort', 'show_in_rest', 'hierarchical' ) as $key ) {
			if ( isset( $definition[ $key ] ) ) {
				$definition[ $key ] = self::normalize_bool( $definition[ $key ] );
			}
		}

		if ( isset( $definition['rest_base'] ) ) {
			$definition['rest_base'] = SanitizationHelper::key( $definition['rest_base'], $taxonomy );
		}

		if ( isset( $definition['rest_namespace'] ) ) {
			$definition['rest_namespace'] = SanitizationHelper::key( $definition['rest_namespace'], 'wp/v2' );
		}

		if ( isset( $definition['query_var'] ) && false !== $definition['query_var'] ) {
			$definition['query_var'] = ! empty( $definition['query_var'] ) ? $definition['query_var'] : true;
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
		return $singular ? trim( $label ) : trim( $label );
	}
}