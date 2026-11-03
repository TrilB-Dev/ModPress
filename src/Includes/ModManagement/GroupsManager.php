<?php
/**
 * Core mod grouping service.
 *
 * @package ModPress
 * @subpackage Includes\ModManagement
 * @since 1.0.0
 */
namespace ModPress\Includes\ModManagement;

use ModPress\Includes\Core\Taxonomy;
use ModPress\Includes\Functions\Helpers\DBHelper;
use ModPress\Includes\Functions\Helpers\RequestHelper;
use ModPress\Includes\Core\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GroupsManager {
	/**
	 * Get the taxonomy definitions registered for mods.
	 *
	 * @return array<string, array<string, mixed>>
	 * @see Taxonomy::definitions() for the source of registered taxonomies.
	 * @since 1.0.0
	 */
	public function get_registered(): array {
		$taxonomies = array();

		foreach ( $this->get_group_definitions() as $taxonomy_key => $taxonomy_config ) {
			$taxonomies[ $taxonomy_key ] = $taxonomy_config;
		}

		foreach ( Taxonomy::definitions() as $taxonomy_key => $taxonomy_config ) {
			$taxonomies[ $taxonomy_key ] = $taxonomy_config;
		}

		$filtered = array();
		foreach ( $taxonomies as $taxonomy_key => $taxonomy_config ) {
			if ( $this->is_hidden( $taxonomy_key ) ) {
				continue;
			}

			$filtered[ $taxonomy_key ] = $taxonomy_config;
		}

		return $filtered;
	}
	/**
	 * Build taxonomy definitions from the custom groups table.
	 *
	 * @return array<string, array<string, mixed>>
	 * @since 1.0.0
	 */
	public function get_group_definitions(): array {
		if ( isset( $GLOBALS['modpress_test_db_groups'] ) && is_array( $GLOBALS['modpress_test_db_groups'] ) ) {
			$groups = $GLOBALS['modpress_test_db_groups'];
		} else {
			if ( ! DBHelper::table_exists( 'groups' ) ) {
				return array();
			}

			$groups = DBHelper::get_all( 'groups' );
		}

		$definitions = array();

		foreach ( $groups as $group ) {
			$group_key = trim( (string) ( $group['group_key'] ?? '' ) );
			$group_slug = trim( (string) ( $group['group_slug'] ?? '' ) );
			if ( '' === $group_key && '' === $group_slug ) {
				continue;
			}

			$taxonomy_key = '' !== $group_key ? $group_key : self::get_group_key( $group_slug );
			if ( '' === $taxonomy_key ) {
				continue;
			}

			$definitions[ $taxonomy_key ] = array(
				'label'             => (string) ( $group['group_name'] ?? ucfirst( str_replace( array( '-', '_' ), ' ', $taxonomy_key ) ) ),
				'description'       => (string) ( $group['group_description'] ?? '' ),
				'post_type'         => array( 'modpress_mod' ),
				'hierarchical'      => ! empty( $group['group_hierarchical'] ),
				'public'            => true,
				'show_ui'           => true,
				'show_in_menu'      => false,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'group'             => $taxonomy_key,
				'parent_group'      => $taxonomy_key,
				'reserved'          => false,
			);
		}

		return $definitions;
	}
	/**
	 * Retrieve a group by its ID.
	 *
	 * @param string $group_id Group ID.
	 * @return array<string, mixed>
	 * @since 1.0.0
	 */
	public static function get_group( $group_id_or_key = '' ): array {
		if ( isset( $GLOBALS['modpress_test_db_groups'] ) && is_array( $GLOBALS['modpress_test_db_groups'] ) ) {
			$tests = $GLOBALS['modpress_test_db_groups'];
			if ( is_numeric( $group_id_or_key ) ) {
				$group_id = (int) $group_id_or_key;
				foreach ( $tests as $group ) {
					if ( (int) ( $group['id'] ?? 0 ) === $group_id ) {
						return $group;
					}
				}
			}

			$lookup_value = trim( (string) $group_id_or_key );
			if ( '' !== $lookup_value ) {
				foreach ( $tests as $group ) {
					$group_key = trim( (string) ( $group['group_key'] ?? '' ) );
					$group_slug = trim( (string) ( $group['group_slug'] ?? '' ) );
					if ( '' !== $group_key && $group_key === $lookup_value ) {
						return $group;
					}
					if ( '' !== $group_slug && $group_slug === $lookup_value ) {
						return $group;
					}
				}
			}
		}

		if ( is_numeric( $group_id_or_key ) ) {
			$group_id = (int) $group_id_or_key;
			$group = DBHelper::get_by_id( 'groups', $group_id );
			if ( ! empty( $group ) ) {
				return $group;
			}
		}

		$lookup_value = trim( (string) $group_id_or_key );
		if ( '' === $lookup_value ) {
			return array();
		}

		$normalized_value = strtolower( preg_replace( '/[^a-z0-9_\-]+/', '', str_replace( '-', '_', $lookup_value ) ) );
		$normalized_value = preg_replace( '/_+/', '_', $normalized_value );
		$normalized_value = trim( $normalized_value, '_' );
		if ( '' === $normalized_value ) {
			return array();
		}

		$group = DBHelper::get_row( 'groups', array( 'group_key' => $normalized_value ) );
		if ( empty( $group ) ) {
			$group = DBHelper::get_row( 'groups', array( 'group_slug' => $normalized_value ) );
		}

		if ( empty( $group ) ) {
			$taxonomy_key = self::get_group_key( $normalized_value );
			if ( '' !== $taxonomy_key ) {
				$group = DBHelper::get_row( 'groups', array( 'group_key' => $taxonomy_key ) );
				if ( empty( $group ) ) {
					$group = DBHelper::get_row( 'groups', array( 'group_slug' => str_replace( 'modpress_', '', $taxonomy_key ) ) );
				}
			}
		}

		return is_array( $group ) ? $group : array();
	}

	/**
	 * Normalize a database group slug into a taxonomy key.
	 *
	 * @param string $group_slug Raw group slug.
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_group_key( string $group_slug ): string {
		$slug = trim( (string) $group_slug );
		if ( '' === $slug ) {
			return '';
		}

		$slug = strtolower( preg_replace( '/[^a-z0-9_\-]+/', '', str_replace( '-', '_', $slug ) ) );
		$slug = preg_replace( '/_+/', '_', $slug );
		$slug = trim( $slug, '_' );
		if ( '' === $slug ) {
			return '';
		}

		return 0 === strpos( $slug, 'modpress_' ) ? $slug : 'modpress_' . $slug;
	}

	/**
	 * Load a group record for AJAX requests.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public static function load_group(): void {
		if ( ! current_user_can( 'modpress_manager_groups_view' ) && ! current_user_can( 'modpress_manager_groups_edit' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to view ModPress groups.', 'modpress' ) ), 403 );
		}

		if ( ! empty( $_POST['nonce'] ) ) {
			check_ajax_referer( 'modpress_get_group', 'nonce' );
		} elseif ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 
				array( 
					'message' => __( 'The selected group could not be loaded.', 'modpress' ) 
				), 
				403 
			);
		}

		$group_id  = RequestHelper::integer( $_POST, 'group_id', 0 );
		$group_key = RequestHelper::key( $_POST, 'group_key', '' );
		$group_row = self::get_group( $group_id > 0 ? $group_id : $group_key );

		if ( empty( $group_row ) ) {
			wp_send_json_error( array( 'message' => __( 'The selected group could not be found.', 'modpress' ) ), 404 );
		}

		$serialized_parent_ids = is_string( $group_row['group_parent'] ?? '' ) ? @unserialize( (string) ( $group_row['group_parent'] ?? '' ) ) : null;
		$group_parent_ids      = is_array( $serialized_parent_ids ) ? array_values(
			array_filter(
				array_map( static fn ( $id ) => (int) $id, $serialized_parent_ids ),
				static fn ( $id ) => $id > 0
			)
		) : array();

		$serialized_parent_entries = is_string( $group_row['group_parent_entry'] ?? '' ) ? @unserialize( (string) ( $group_row['group_parent_entry'] ?? '' ) ) : null;
		$group_parent_entries     = is_array( $serialized_parent_entries ) ? array_values(
			array_filter(
				array_map( static fn ( $id ) => (int) $id, $serialized_parent_entries ),
				static fn ( $id ) => $id > 0
			)
		) : array();
		if ( empty( $group_parent_entries ) && isset( $group_row['group_parent_entry'] ) && is_numeric( $group_row['group_parent_entry'] ) ) {
			$parent_entry_id = abs( (int) $group_row['group_parent_entry'] );
			if ( $parent_entry_id > 0 ) {
				$group_parent_entries = array( $parent_entry_id );
			}
		}

		wp_send_json_success(
			array(
				'group_id'            => abs( (int) ( $group_row['id'] ?? 0 ) ),
				'group_key'           => (string) ( $group_row['group_key'] ?? $group_key ),
				'group_name'          => (string) ( $group_row['group_name'] ?? '' ),
				'group_slug'          => (string) ( $group_row['group_slug'] ?? ( $group_key ?: '' ) ),
				'group_singular_name' => (string) ( $group_row['group_singular_name'] ?? ( $group_row['group_name'] ?? '' ) ),
				'group_description'   => (string) ( $group_row['group_description'] ?? '' ),
				'group_hierarchical'  => ! empty( $group_row['group_hierarchical'] ),
				'group_parent'        => $group_parent_ids,
				'group_parent_entry'  => $group_parent_entries,
				'group_fields'        => is_string( $group_row['group_fields'] ) ? json_decode( $group_row['group_fields'], true ) : ( is_array( $group_row['group_fields'] ) ? $group_row['group_fields'] : array() ),
			)
		);
	}

	/**
	 * Retrieve a specific term meta value for a group entry.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $taxonomy_key Taxonomy key.
	 * @param string $field_slug Field slug.
	 * @return mixed
	 * @since 1.0.0
	 */
	public static function get_group_entry( int $term_id, string $taxonomy_key, string $field_slug = '' ) {
		$term_id = (int) $term_id;
		if ( $term_id <= 0 || '' === trim( (string) $taxonomy_key ) ) {
			return '';
		}

		if ( isset( $GLOBALS['modpress_test_term_meta'] ) && is_array( $GLOBALS['modpress_test_term_meta'] ) ) {
			$term_meta = $GLOBALS['modpress_test_term_meta'][ $term_id ] ?? array();
			if ( is_array( $term_meta ) ) {
				if ( '' === trim( (string) $field_slug ) ) {
					return $term_meta;
				}
				if ( isset( $term_meta[ $field_slug ] ) ) {
					$meta_value = $term_meta[ $field_slug ];
					if ( is_array( $meta_value ) ) {
						return $meta_value[0] ?? '';
					}
					return $meta_value;
				}
			}
		}

		$meta_key = '' !== trim( (string) $field_slug ) ? trim( (string) $field_slug ) : '';
		if ( '' === $meta_key ) {
			$term_meta = get_term_meta( $term_id );
			return is_array( $term_meta ) ? $term_meta : array();
		}

		$meta_value = get_term_meta( $term_id, $meta_key, true );
		if ( is_array( $meta_value ) ) {
			return array_values( $meta_value );
		}

		return $meta_value;
	}

	/**
	 * Load a specific group entry for AJAX requests.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public static function load_group_entry(): void {
		if ( ! current_user_can( 'modpress_manager_groups_view' ) && ! current_user_can( 'modpress_manager_groups_edit' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to view ModPress group entries.', 'modpress' ) ), 403 );
		}

		if ( ! empty( $_POST['nonce'] ) ) {
			check_ajax_referer( 'modpress_get_group_entry', 'nonce' );
		} elseif ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'The selected entry could not be loaded.', 'modpress' ) ), 403 );
		}

		$taxonomy = RequestHelper::key( $_POST, 'group_slug', '' );
		$term_id  = RequestHelper::integer( $_POST, 'group_entry_id', 0 );
		if ( '' === $taxonomy || $term_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'The selected entry could not be found.', 'modpress' ) ), 400 );
		}

		$term = get_term( $term_id, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) {
			wp_send_json_error( array( 'message' => __( 'The selected entry could not be found.', 'modpress' ) ), 404 );
		}

		$term_meta = get_term_meta( $term_id );
		$payload   = array(
			'term_id'    => $term_id,
			'name'       => $term->name ?? '',
			'slug'       => $term->slug ?? '',
			'description'=> $term->description ?? '',
			'parent'     => (int) ( $term->parent ?? 0 ),
			'icon'       => is_array( $term_meta['entry_icon'] ?? null ) ? (string) ( $term_meta['entry_icon'][0] ?? '' ) : (string) ( $term_meta['entry_icon'] ?? '' ),
		);

		if ( is_array( $term_meta ) ) {
			foreach ( $term_meta as $meta_key => $meta_value ) {
				if ( is_array( $meta_value ) && 1 === count( $meta_value ) ) {
					$payload[ $meta_key ] = $meta_value[0];
					continue;
				}
				$payload[ $meta_key ] = $meta_value;
			}
		}

		$group_row = self::get_group( $taxonomy );
		$group_fields = array();
		if ( ! empty( $group_row['group_fields'] ) ) {
			$decoded_fields = json_decode( (string) $group_row['group_fields'], true );
			if ( is_array( $decoded_fields ) ) {
				$group_fields = $decoded_fields;
			}
		}

		foreach ( $group_fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$field_slug = trim( (string) ( $field['slug'] ?? $field['name'] ?? '' ) );
			if ( '' === $field_slug ) {
				continue;
			}

			$field_id = trim( (string) ( $field['id'] ?? '' ) );
			if ( '' === $field_id ) {
				$field_id = 'field_' . sanitize_key( $field_slug );
			}

			$meta_value = null;
			foreach ( array( $field_slug, sanitize_key( $field_slug ), $field_id, sanitize_key( $field_id ) ) as $lookup_key ) {
				if ( array_key_exists( $lookup_key, $term_meta ) ) {
					$meta_value = $term_meta[ $lookup_key ];
					break;
				}
			}

			if ( null === $meta_value ) {
				continue;
			}

			if ( is_array( $meta_value ) && 1 === count( $meta_value ) ) {
				$meta_value = $meta_value[0];
			}

			$aliases = array(
				$field_slug,
				sanitize_key( $field_slug ),
				$field_id,
				sanitize_key( $field_id ),
				'entry_custom_field_' . sanitize_key( $field_slug ),
				'entry_custom_field_' . sanitize_key( $field_id ),
			);

			foreach ( $aliases as $alias_key ) {
				if ( '' === trim( (string) $alias_key ) ) {
					continue;
				}
				$payload[ $alias_key ] = $meta_value;
			}
		}

		wp_send_json_success( $payload );
	}
	/**
	 * Get a grouped view of the registered taxonomy structure.
	 *
	 * @return array<int, array<string, mixed>>
	 * @since 1.0.0
	 */
	public function get_group_tree(): array {
		$groups = array();
		$items  = array();

		foreach ( $this->get_registered() as $taxonomy_key => $taxonomy_config ) {
			$taxonomy_object = get_taxonomy( $taxonomy_key );
			$label           = $taxonomy_config['label'] ?? $taxonomy_object->labels->name ?? ucfirst( str_replace( array( '-', '_' ), ' ', $taxonomy_key ) );
			$parent_group    = $taxonomy_config['parent_group'] ?? $taxonomy_config['group'] ?? 'type';
			$group_key       = is_string( $parent_group ) && '' !== $parent_group ? $parent_group : 'type';
			$reserved        = $this->is_reserved( $taxonomy_key, $taxonomy_config );

			$groups[ $group_key ]['items'][] = array(
				'key'         => $taxonomy_key,
				'taxonomy'    => $taxonomy_key,
				'label'       => $label,
				'group'       => $group_key,
				'hierarchical'=> ! empty( $taxonomy_config['hierarchical'] ),
				'count'       => $this->get_group_term_count( $taxonomy_key ),
				'reserved'    => $reserved,
				'deletable'   => ! $reserved,
			);
		}

		foreach ( $groups as $group ) {
			$items[] = $group;
		}

		return $items;
	}

	/**
	 * Return the number of terms attached to a taxonomy.
	 *
	 * @param string $taxonomy_key Taxonomy slug.
	 * @return int
	 * @since 1.0.0
	 */
	private function get_group_term_count( string $taxonomy_key ): int {
		if ( ! taxonomy_exists( $taxonomy_key ) ) {
			return 0;
		}

		$count = wp_count_terms( $taxonomy_key, array( 'hide_empty' => false ) );
		if ( is_array( $count ) ) {
			return count( $count );
		}

		return is_numeric( $count ) ? (int) $count : 0;
	}

	/**
	 * Determine whether a taxonomy should be hidden from the Mod Groups manager.
	 *
	 * @param string $taxonomy_key Taxonomy slug.
	 * @return bool
	 * @since 1.0.0
	 */
	private function is_hidden( string $taxonomy_key ): bool {
		if ( in_array( $taxonomy_key, array( 'modpress_tag' ), true ) ) {
			return false;
		}

		$taxonomy = get_taxonomy( $taxonomy_key );
		if ( ! $taxonomy ) {
			return false;
		}

		$object_types = $taxonomy->object_type ?? array();
		return in_array( 'modpress_mod', (array) $object_types, true );
	}

	/**
	 * Determine whether a taxonomy is a plugin-reserved taxonomy that cannot be deleted.
	 *
	 * @param string $taxonomy_key Taxonomy slug.
	 * @param array<string, mixed> $taxonomy_config Taxonomy config.
	 * @return bool
	 * @since 1.0.0
	 */
	private function is_reserved( string $taxonomy_key, array $taxonomy_config = array() ): bool {
		if ( ! empty( $taxonomy_config['reserved'] ) ) {
			return true;
		}

		return in_array( $taxonomy_key, array( 'modpress_type' ), true );
	}
	/**
	 * Get a single entry for a taxonomy term.
	 *
	 * @param string $taxonomy_key Taxonomy slug.
	 * @param int    $term_id Term ID.
	 * @return array
	 * @since 1.0.0
	 */
	public function get_entry( string $taxonomy_key, int $term_id ): array {
		$term = get_term( $term_id, $taxonomy_key );
		if ( ! $term || is_wp_error( $term ) ) {
			return array();
		}
		$term_meta = get_term_meta( $term_id );
		if ( ! $term_meta || is_wp_error( $term_meta ) ) {
			return array();
		}
		return array(
			'term' => (array) $term,
			'meta' => (array) $term_meta,
		);
		
	}
	/**
	 * Get all entries for a taxonomy.
	 *
	 * @param string $taxonomy_key Taxonomy slug.
	 * @return array
	 * @since 1.0.0
	 */
	public function get_entries( string $taxonomy_key ): array {
		$terms = get_terms( array(
			'taxonomy'   => $taxonomy_key,
			'hide_empty' => false,
		) );

		if ( is_wp_error( $terms ) ) {
			return array();
		}
		$terms_meta = array();
		foreach ( $terms as $term ) {
			$term_id = $term->term_id ?? 0;
			if ( $term_id > 0 ) {
				$term_meta = get_term_meta( $term_id );
				if ( $term_meta && ! is_wp_error( $term_meta ) ) {
					$terms_meta[ $term_id ] = (array) $term_meta;
				}
			}
		}

		return array(
			'terms' => (array) $terms,
			'meta'  => (array) $terms_meta,
		);
	}
	/**
	 * Register a new mod taxonomy from dynamic UI input.
	 *
	 * @param array<string, mixed> $data Dynamic taxonomy payload.
	 * @return bool
	 * @since 1.0.0
	 */
	public function dynamically_create( array $data = array() ): bool {
		$taxonomy      = $data['taxonomy'] ?? $data['slug'] ?? '';
		$group_key     = $data['group_key'] ?? $data['taxonomy_key'] ?? $data['group'] ?? $data['parent_group'] ?? 'type';
		$taxonomy      = '' !== trim( (string) $group_key ) ? trim( (string) $group_key ) : $taxonomy;
		$post_type     = $data['post_type'] ?? $data['post_types'] ?? array( 'modpress_mod' );
		$taxonomy_data = array(
			'post_type'    => $post_type,
			'public'       => true,
			'show_ui'      => true,
			'show_in_rest' => true,
			'group'        => $group_key,
			'parent_group' => $group_key,
		);

		if ( ! empty( $data['label'] ) ) {
			$taxonomy_data['label'] = $data['label'];
		}

		if ( ! empty( $data['description'] ) ) {
			$taxonomy_data['description'] = $data['description'];
		}

		if ( ! empty( $data['hierarchical'] ) ) {
			$taxonomy_data['hierarchical'] = (bool) $data['hierarchical'];
		}

		if ( ! empty( $data['rewrite'] ) ) {
			$taxonomy_data['rewrite'] = $data['rewrite'];
		}

		if ( empty( $taxonomy ) ) {
			return false;
		}

		$created = Taxonomy::dynamically_create( array_merge( $taxonomy_data, array( 'taxonomy' => $taxonomy ) ) );
		if ( ! $created ) {
			return false;
		}

		return true;
	}
	/**
	 * Create the default Mod Type group row if it does not already exist.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function create_default_group(): bool {
		if ( ! DBHelper::table_exists( 'groups' ) ) {
			if ( ! method_exists( '\ModPress\Includes\Core\Schema', 'groups_table' ) ) {
				return false;
			}
			Schema::groups_table();
			DBHelper::create_table( 'groups' );
		}

		if ( ! empty( DBHelper::get_row( 'groups', array( 'group_slug' => 'type' ) ) ) ) {
			return true;
		}

		$existing = DBHelper::get_row( 'groups', array( 'group_key' => 'modpress_type' ) );
		if ( empty( $existing ) ) {
			$existing = DBHelper::get_row( 'groups', array( 'group_slug' => 'type' ) );
		}

		if ( ! empty( $existing ) ) {
			DBHelper::update(
				'groups',
				array( 'group_key' => 'modpress_type', 'updated_at' => current_time( 'mysql' ) ),
				array( 'id' => (int) $existing['id'] )
			);
			return true;
		}

		$inserted = DBHelper::insert(
			'groups',
			array(
				'group_key'           => 'modpress_type',
				'group_name'          => 'Mod Type',
				'group_slug'          => 'type',
				'group_singular_name' => 'Mod Type',
				'group_description'   => 'The Mod Type group is used to categorise your mods by their type.',
				'group_hierarchical'  => false,
				'group_parent'        => null,
				'group_parent_entry'  => null,
				'group_fields'        => null,
				'created_by'          => 1,
				'created_at'          => current_time( 'mysql' ),
				'updated_at'          => current_time( 'mysql' ),
			)
		);

		return false !== $inserted;
	}
}
