<?php
/**
 * Admin functions for native Mod management.
 *
 * @package ModPress
 */

namespace ModPress\Includes\Functions\Admin;

use ModPress\Includes\Core\Schema;
use ModPress\Includes\Functions\Helpers\AlertHelper;
use ModPress\Includes\Functions\Helpers\LoaderHelper;
use ModPress\Includes\Functions\Helpers\LoggerHelper;
use ModPress\Includes\Functions\Helpers\PermalinkHelper;
use ModPress\Includes\Functions\Helpers\SanitizationHelper;
use ModPress\Includes\Functions\Helpers\RequestHelper;
use ModPress\Includes\Functions\Helpers\DBHelper;
use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\Functions\Helpers\WPMediaHelper;
use ModPress\Includes\ModManagement\GroupsManager;
use ModPress\Includes\ModManagement\ModsManager;
use ModPress\Includes\ModManagement\ChangelogManager;
use ModPress\Includes\ModManagement\FileManager;
use ModPress\Includes\Plugins\FontAwesome\Includes\IconPicker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FunctionsMod {

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
					'hook'     => 'admin_post_modpress_save_group',
					'callback' => 'save_group',
				),
				array(
					'type'     => 'action',
					'hook'     => 'admin_post_modpress_save_group_entry',
					'callback' => 'save_group_entry',
				),
				array(
					'type'     => 'action',
					'hook'     => 'admin_post_modpress_save_tags',
					'callback' => 'save_tags',
				),
				array(
					'type'     => 'action',
					'hook'     => 'admin_post_modpress_save_files',
					'callback' => 'save_files',
				),
				array(
					'type'     => 'action',
					'hook'     => 'admin_post_modpress_save_file_type',
					'callback' => 'save_file_type',
				),
				array(
					'type'     => 'action',
					'hook'     => 'admin_post_modpress_save_changelog',
					'callback' => 'save_changelog',
				),
				array(
					'type'     => 'action',
					'hook'     => 'admin_post_modpress_save_changelog_category',
					'callback' => 'save_changelog_category',
				),
				array(
					'type'     => 'action',
					'hook'     => 'admin_post_modpress_save_mod',
					'callback' => 'save_mod',
				),
			)
		)->run();
	}
    /**
     * Save a ModPress form submission.
     *
     * @return void
     * @since 1.0.0
     */
	public function save(): void {
		$action = RequestHelper::key( $_POST, 'action', '' );
		if ( '' === $action ) {
			return;
		}

		if ( in_array( $action, array( 'modpress_save_group' ), true ) ) {
			$this->save_group();
		}
		if ( in_array( $action, array( 'modpress_save_group_entry' ), true ) ) {
			$this->save_group_entry();
		}
		if ( 'modpress_save_tags' === $action ) {
			$this->save_tags();
		}
		if ( 'modpress_save_files' === $action ) {
			$this->save_files();
		}
		if ( 'modpress_save_file_type' === $action ) {
			$this->save_file_type();
		}
		if ( 'modpress_save_changelog' === $action ) {
			$this->save_changelog();
		}
		if ( 'modpress_save_changelog_category' === $action ) {
			$this->save_changelog_category();
		}
		if ( 'modpress_save_mod' === $action ) {
			$this->save_mod();
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
	 * Save a Mod submitted through the native admin form.
	 *
	 * @return string Rendered admin notice, or an empty string when the form was not submitted.
     * @since 1.0.0
	 */
	public function save_mod(): string {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return '';
		}

		$action = isset( $_POST['modpress_action'] )
			? sanitize_key( wp_unslash( $_POST['modpress_action'] ) )
			: '';

		if ( 'modpress_save_mod' !== $action ) {
			return '';
		}

		if ( ! current_user_can( 'modpress_create' ) ) {
			return AlertHelper::get_admin_notice(
				__( 'You are not allowed to create Mods.', 'modpress' ),
				'error'
			);
		}

		$this->validate_save_nonce( 'modpress_save_mod' );
		check_admin_referer( 'modpress_save_mod', 'modpress_mod_nonce' );

		$title = isset( $_POST['post_title'] ) ? SanitizationHelper::text( wp_unslash( $_POST['post_title'] ) ): '';
		$content = isset( $_POST['post_content'] ) ? wp_kses_post( wp_unslash( $_POST['post_content'] ) ): '';

		if ( '' === $title ) {
			return AlertHelper::get_admin_notice(
				__( 'A Mod title is required.', 'modpress' ),
				'error'
			);
		}

		$post_id = wp_insert_post(
			[
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_author'  => get_current_user_id(),
				'post_type'    => 'modpress_mod',
			],
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return AlertHelper::get_admin_notice(
				__( 'The Mod could not be saved.', 'modpress' ),
				'error'
			);
		}

		return AlertHelper::get_admin_notice(
			__( 'Mod saved successfully.', 'modpress' ),
			'success'
		);
	}
	/**
     * Save a custom taxonomy group from the group modal.
     *
     * @return void
     * @since 1.0.0
     */
    public function save_group(): void {
        if ( ! current_user_can( 'modpress_manager_groups_edit' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage ModPress groups.', 'modpress' ) );
        }

        check_admin_referer( 'modpress_save_group', 'modpress_group_nonce' );

        $group_name = RequestHelper::text( $_POST, 'group_name', '' );
        $taxonomy   = RequestHelper::key( $_POST, 'group_slug', SanitizationHelper::slug( $group_name, '' ) );
        $group_key  = GroupsManager::get_group_taxonomy_key( $taxonomy );

        if ( '' === $group_name ) {
            LoggerHelper::write_log( 'ModPress group save rejected: missing group name.' );
            $this->redirect_mod_group_result( false, __( 'A group name is required.', 'modpress' ) );
        }

        if ( '' === $taxonomy ) {
            LoggerHelper::write_log( 'ModPress group save rejected: invalid taxonomy.' );
            $this->redirect_mod_group_result( false, __( 'A valid group slug is required.', 'modpress' ) );
        }

        if ( ! DBHelper::table_exists( 'groups' ) ) {
            Schema::groups_table();
            DBHelper::create_table( 'groups' );
        }

        $group_payload = $this->build_group_payload( $group_name, $taxonomy );
        $group_payload['group_key'] = $group_key;
        $group_id      = $this->persist_group_record( $group_payload );

        if ( false === $group_id ) {
            $failure_reason = $this->get_group_save_failure_reason();
            LoggerHelper::write_log( 'ModPress group save failed while persisting row for taxonomy: ' . $taxonomy . '. Reason: ' . $failure_reason );
            $this->redirect_mod_group_result( false, sprintf( __( 'The group could not be saved to ModPress. %s', 'modpress' ), $failure_reason ) );
        }

        $group_payload['id'] = $group_id;

        $service = new GroupsManager();
        $created = $service->dynamically_create(
            array(
                'taxonomy'      => $group_key,
                'group_key'     => $group_key,
                'label'         => $group_name,
                'name'          => $group_name,
                'singular_name' => $group_payload['group_singular_name'],
                'description'   => $group_payload['group_description'],
                'hierarchical'  => $group_payload['group_hierarchical'],
                'show_ui'       => true,
                'public'        => true,
                'show_in_rest'  => true,
                'post_type'     => array( 'modpress_mod' ),
            )
        );

        if ( ! $created ) {
            LoggerHelper::write_log( 'ModPress group save failed for taxonomy: ' . $taxonomy );
            $this->redirect_mod_group_result( false, __( 'The group could not be created.', 'modpress' ) );
        }

        LoggerHelper::write_log( 'ModPress group saved successfully: ' . $taxonomy . ' (row_id=' . $group_id . ')' );
        $this->redirect_mod_group_result( true, __( 'Group saved successfully.', 'modpress' ) );
    }

    /**
     * Delete a custom ModPress group record.
     *
     * The default Mod Type group is reserved and remains editable, but cannot be removed.
     *
     * @return void
     * @since 1.0.0
     */
    public function delete_group(): void {
        if ( ! current_user_can( 'modpress_manager_groups_edit' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage ModPress groups.', 'modpress' ) );
        }

        check_admin_referer( 'modpress_delete_group', 'modpress_group_nonce' );

        $group_id = RequestHelper::integer( $_POST, 'group_id', 0 );
        $edit_key = RequestHelper::value( $_POST, 'group_edit_key', '' );

        $group_row = array();
        if ( $group_id > 0 ) {
            $group_row = DBHelper::get_by_id( 'groups', $group_id );
        } elseif ( '' !== trim( (string) $edit_key ) ) {
            $lookup_value = sanitize_key( (string) $edit_key );
            if ( '' !== $lookup_value ) {
                $group_row = DBHelper::get_row( 'groups', array( 'group_key' => $lookup_value ) );
                if ( empty( $group_row ) ) {
                    $group_row = DBHelper::get_row( 'groups', array( 'group_slug' => $lookup_value ) );
                }
            }
        }

        if ( empty( $group_row ) ) {
            $this->redirect_mod_group_result( false, __( 'The selected group could not be found.', 'modpress' ) );
        }

        $group_slug = trim( (string) ( $group_row['group_slug'] ?? '' ) );
        $group_key  = trim( (string) ( $group_row['group_key'] ?? GroupsManager::get_group_taxonomy_key( $group_slug ) ) );
        if ( 'modpress_type' === $group_key || 'type' === $group_slug || 'modpress_type' === GroupsManager::get_group_taxonomy_key( $group_slug ) ) {
            $this->redirect_mod_group_result( false, __( 'The Mod Type group cannot be deleted.', 'modpress' ) );
        }

        $deleted = DBHelper::delete( 'groups', array( 'id' => (int) ( $group_row['id'] ?? 0 ) ) );

        if ( ! $deleted ) {
            $this->redirect_mod_group_result( false, __( 'The group could not be deleted.', 'modpress' ) );
        }

        $this->redirect_mod_group_result( true, __( 'Group deleted successfully.', 'modpress' ) );
    }

    /**
     * Build the canonical custom-table payload for a mod group.
     *
     * @param string $group_name Display label.
     * @param string $taxonomy Taxonomy slug.
     * @return array<string, mixed>
     * @since 1.0.0
     */
    private function build_group_payload( string $group_name, string $taxonomy ): array {
        $group_fields = array();
        $field_ids    = RequestHelper::value( $_POST, 'custom_field_id', array() );
        $field_names  = RequestHelper::value( $_POST, 'custom_field_label', RequestHelper::value( $_POST, 'custom_field_name', array() ) );
        $field_slugs  = RequestHelper::value( $_POST, 'custom_field_slug', array() );
        $field_types  = RequestHelper::value( $_POST, 'custom_field_type', array() );
        $field_descs  = RequestHelper::value( $_POST, 'custom_field_description', array() );
        $textarea_rows = RequestHelper::value( $_POST, 'custom_field_type_textarea_rows', array() );
        $radio_options = RequestHelper::value( $_POST, 'custom_field_type_radio', array() );
        $select_options = RequestHelper::value( $_POST, 'custom_field_type_select', array() );
        $image_types = RequestHelper::value( $_POST, 'custom_field_type_image_type', array() );
        $image_type_image = RequestHelper::value( $_POST, 'custom_field_type_image_type_image', array() );
        $image_type_icon = RequestHelper::value( $_POST, 'custom_field_type_image_type_icon', array() );
        $image_type_url = RequestHelper::value( $_POST, 'custom_field_type_image_type_url', array() );
        $show_in_table = RequestHelper::value( $_POST, 'custom_field_show_in_table', array() );

        if ( is_array( $field_names ) ) {
            foreach ( $field_names as $index => $field_name ) {
                $name = trim( (string) $field_name );
                if ( '' === $name ) {
                    continue;
                }

                $field_index = is_numeric( $index ) ? (int) $index : 0;
                $field_id = trim( (string) ( $field_ids[ $field_index ] ?? '' ) );
                if ( '' === $field_id ) {
                    $field_id = 'field_' . ( $field_index + 1 );
                }

                $slug        = trim( (string) ( $field_slugs[ $field_index ] ?? SanitizationHelper::slug( $name, '' ) ) );
                $field_type  = trim( (string) ( $field_types[ $field_index ] ?? 'text' ) );
                $show_in_table_value = $show_in_table[ $field_index ] ?? '';
                $field       = array(
                    'id'          => $field_id,
                    'name'        => $name,
                    'slug'        => $slug,
                    'type'        => $field_type,
                    'description' => trim( (string) ( $field_descs[ $field_index ] ?? '' ) ),
                    'show_in_table' => is_bool( $show_in_table_value )
                        ? $show_in_table_value
                        : in_array( strtolower( trim( (string) $show_in_table_value ) ), array( '1', 'on', 'true', 'yes' ), true ),
                );

                if ( 'textarea' === $field_type ) {
                    $rows = abs( (int) ( $textarea_rows[ $field_index ] ?? 4 ) );
                    if ( $rows > 0 ) {
                        $field['rows'] = $rows;
                    }
                }

                if ( in_array( $field_type, array( 'radio', 'select' ), true ) ) {
                    $option_map = 'radio' === $field_type ? $radio_options : $select_options;
                    $options = array();
                    $option_values = is_array( $option_map ) ? ( $option_map[ $field_index ] ?? array() ) : array();

                    if ( empty( $option_values ) && is_array( $option_map ) ) {
                        $option_values = array_values( $option_map );
                    }

                    foreach ( $option_values as $option ) {
                        if ( is_array( $option ) ) {
                            $option_label = trim( (string) ( $option['label'] ?? $option['name'] ?? '' ) );
                            $option_value = trim( (string) ( $option['option'] ?? $option['value'] ?? $option_label ) );
                            if ( '' === $option_label && '' === $option_value ) {
                                continue;
                            }
                            $options[] = array(
                                'label' => '' !== $option_label ? $option_label : $option_value,
                                'value' => '' !== $option_value ? $option_value : ( '' !== $option_label ? $option_label : '' ),
                            );
                        } elseif ( is_scalar( $option ) ) {
                            $option_value = trim( (string) $option );
                            if ( '' !== $option_value ) {
                                $options[] = array(
                                    'label' => $option_value,
                                    'value' => $option_value,
                                );
                            }
                        }
                    }

                    if ( ! empty( $options ) ) {
                        $field['options'] = $options;
                    }
                }

                if ( 'image' === $field_type ) {
                    $legacy_image_config = is_array( $image_types ) ? ( $image_types[ $field_index ] ?? array() ) : array();
                    $image_config = array();

                    $flatten_selected_values = static function ( $values ) {
                        $flat = array();

                        if ( is_array( $values ) ) {
                            foreach ( $values as $value ) {
                                if ( is_array( $value ) ) {
                                    foreach ( $value as $nested_value ) {
                                        $flat[] = $nested_value;
                                    }
                                    continue;
                                }
                                $flat[] = $value;
                            }
                        } elseif ( null !== $values ) {
                            $flat[] = $values;
                        }

                        return array_values(
                            array_filter(
                                array_map( 'strval', $flat ),
                                static fn ( $item ) => '' !== trim( (string) $item )
                            )
                        );
                    };

                    if ( is_array( $legacy_image_config ) ) {
                        $image_config = array_merge( $image_config, $flatten_selected_values( $legacy_image_config ) );
                    }

                    foreach ( array(
                        'image' => $image_type_image,
                        'icon' => $image_type_icon,
                        'url' => $image_type_url,
                    ) as $type_name => $stored_values ) {
                        $selected_values = $flatten_selected_values( $stored_values[ $field_index ] ?? $stored_values );

                        foreach ( $selected_values as $value ) {
                            $normalized = strtolower( trim( (string) $value ) );
                            if ( '' !== $normalized && in_array( $normalized, array( 'image', 'icon', 'url', '1', 'on', 'true', 'yes' ), true ) ) {
                                $image_config[] = $normalized;
                            }
                        }

                        if ( ! empty( $selected_values ) ) {
                            $image_config[] = $type_name;
                        }
                    }

                    $image_config = array_values( array_unique( array_filter( array_map( 'strval', $image_config ), static fn ( $item ) => '' !== trim( (string) $item ) ) ) );
                    if ( ! empty( $image_config ) ) {
                        $field['image_types'] = array_values(
                            array_filter(
                                array_map(
                                    static fn ( $item ) => strtolower( trim( (string) $item ) ),
                                    $image_config
                                ),
                                static fn ( $item ) => '' !== $item
                            )
                        );
                    }
                }

                $group_fields[] = $field;
            }
        }

        $group_parent_ids = array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn ( $value ) => self::resolve_group_parent_id( $value ),
                        self::normalize_group_parent_values( RequestHelper::value( $_POST, 'group_parent', array() ) )
                    ),
                    static fn ( $value ) => $value > 0
                )
            )
        );
        $group_parent_entry_ids = array_values(
            array_filter(
                array_map(
                    static fn ( $value ) => abs( (int) $value ),
                    self::normalize_group_parent_values( RequestHelper::value( $_POST, 'group_parent_entry', array() ) )
                ),
                static fn ( $value ) => $value > 0
            )
        );
        $resolved_group_key = GroupsManager::get_group_taxonomy_key( $taxonomy );
        $description = SanitizationHelper::textarea( RequestHelper::value( $_POST, 'group_description', '' ), '' );

        $group_payload = array(
            'group_key'           => $resolved_group_key,
            'group_name'          => $group_name,
            'group_slug'          => $taxonomy,
            'group_singular_name' => RequestHelper::text( $_POST, 'group_singular_name', $group_name ),
            'group_description'   => $description,
            'group_hierarchical'  => RequestHelper::boolean( $_POST, 'group_hierarchical', false ),
            'group_parent'        => serialize( $group_parent_ids ),
            'group_parent_entry'  => serialize( $group_parent_entry_ids ),
            'group_fields'        => ! empty( $group_fields ) ? wp_json_encode( $group_fields ) : null,
            'created_by'          => get_current_user_id(),
            'updated_at'          => current_time( 'mysql' ),
        );

        return $group_payload;
    }

    /**
     * Normalize a group parent value into either numeric IDs or taxonomy keys.
     *
     * This is the single place that converts serialized, nested, and mixed parent values
     * into a clean array suitable for persistence or for rendering the modal selection.
     *
     * @param mixed $raw_value Raw DB or request value.
     * @param array<int, array<string, mixed>> $taxonomy_tabs Optional taxonomy map used to resolve IDs to taxonomy keys.
     * @return array<int, string>
     * @since 1.0.0
     */
    public static function normalize_group_parent_values( $raw_value, array $taxonomy_tabs = array() ): array {
        $values = is_array( $raw_value ) ? $raw_value : array( $raw_value );
        $normalized = array();

        foreach ( $values as $value ) {
            if ( is_array( $value ) ) {
                $normalized = array_merge( $normalized, self::normalize_group_parent_values( $value, $taxonomy_tabs ) );
                continue;
            }

            if ( is_string( $value ) && '' !== trim( $value ) ) {
                $unserialized = @unserialize( $value );
                if ( is_array( $unserialized ) ) {
                    $normalized = array_merge( $normalized, self::normalize_group_parent_values( $unserialized, $taxonomy_tabs ) );
                    continue;
                }
            }

            $value = trim( (string) $value );
            if ( '' === $value ) {
                continue;
            }

            if ( is_numeric( $value ) ) {
                $int_value = abs( (int) $value );
                if ( $int_value <= 0 ) {
                    continue;
                }

                if ( ! empty( $taxonomy_tabs ) ) {
                    $group_key_map = array();
                    foreach ( $taxonomy_tabs as $taxonomy ) {
                        $taxonomy_id = abs( (int) ( $taxonomy['id'] ?? 0 ) );
                        $taxonomy_key = trim( (string) ( $taxonomy['taxonomy'] ?? $taxonomy['key'] ?? '' ) );
                        if ( $taxonomy_id > 0 && '' !== $taxonomy_key ) {
                            $group_key_map[ $taxonomy_id ] = $taxonomy_key;
                        }
                    }

                    $taxonomy_key = $group_key_map[ $int_value ] ?? '';
                    if ( '' !== $taxonomy_key ) {
                        $normalized[] = $taxonomy_key;
                        continue;
                    }
                }

                $normalized[] = (string) $int_value;
                continue;
            }

            $normalized[] = sanitize_key( $value );
        }

        return array_values( array_unique( array_filter( array_map( 'strval', $normalized ), static fn ( $item ) => '' !== trim( (string) $item ) ) ) );
    }

    /**
     * Resolve a parent group taxonomy key or numeric ID to the canonical group row ID.
     *
     * @param mixed $value Raw parent group value from the modal or request payload.
     * @return int
     * @since 1.0.0
     */
    public static function resolve_group_parent_id( $value ): int {
        $value = trim( (string) $value );
        if ( '' === $value ) {
            return 0;
        }

        if ( is_numeric( $value ) ) {
            return abs( (int) $value );
        }

        $lookup_key = sanitize_key( $value );
        if ( '' === $lookup_key ) {
            return 0;
        }

        $group_row = DBHelper::get_row( 'groups', array( 'group_key' => $lookup_key ) );
        if ( empty( $group_row ) ) {
            $group_row = DBHelper::get_row( 'groups', array( 'group_slug' => $lookup_key ) );
        }

        if ( empty( $group_row ) ) {
            $group_row = self::find_group_row_by_key_or_slug( $lookup_key );
        }

        if ( empty( $group_row ) || ! isset( $group_row['id'] ) ) {
            return 0;
        }

        return abs( (int) ( $group_row['id'] ?? 0 ) );
    }

    /**
     * Convert a stored parent ID back into the taxonomy key used by the UI.
     *
     * @param mixed $value Raw parent value.
     * @param array<int, array<string, mixed>> $taxonomy_tabs Taxonomy definitions for lookup.
     * @return string
     * @since 1.0.0
     */
    public static function resolve_group_parent_key( $value, array $taxonomy_tabs = array() ): string {
        $value = trim( (string) $value );
        if ( '' === $value ) {
            return '';
        }

        if ( is_numeric( $value ) ) {
            $id = abs( (int) $value );
            if ( $id > 0 ) {
                if ( ! empty( $taxonomy_tabs ) ) {
                    foreach ( $taxonomy_tabs as $taxonomy ) {
                        $taxonomy_id = absint( $taxonomy['id'] ?? 0 );
                        $taxonomy_key = trim( (string) ( $taxonomy['taxonomy'] ?? $taxonomy['key'] ?? '' ) );
                        if ( $taxonomy_id === $id && '' !== $taxonomy_key ) {
                            return $taxonomy_key;
                        }
                    }
                }

                $group_row = DBHelper::get_by_id( 'groups', $id );
                if ( empty( $group_row ) ) {
                    $group_row = self::find_group_row_by_id( $id );
                }

                if ( ! empty( $group_row ) ) {
                    $group_key = trim( (string) ( $group_row['group_key'] ?? $group_row['group_slug'] ?? '' ) );
                    if ( '' !== $group_key ) {
                        return $group_key;
                    }
                }
            }

            return '';
        }

        $lookup_key = sanitize_key( $value );
        if ( '' !== $lookup_key ) {
            $group_row = DBHelper::get_row( 'groups', array( 'group_key' => $lookup_key ) );
            if ( empty( $group_row ) ) {
                $group_row = DBHelper::get_row( 'groups', array( 'group_slug' => $lookup_key ) );
            }

            if ( empty( $group_row ) ) {
                $group_row = self::find_group_row_by_key_or_slug( $lookup_key );
            }

            if ( ! empty( $group_row ) ) {
                $group_key = trim( (string) ( $group_row['group_key'] ?? $group_row['group_slug'] ?? '' ) );
                if ( '' !== $group_key ) {
                    return $group_key;
                }
            }
        }

        return $lookup_key !== '' ? $lookup_key : $value;
    }

    /**
     * Find a group row by key or slug when direct DB lookups do not return a result.
     *
     * @param string $lookup_key Matched group key/slug.
     * @return array<string, mixed>|null
     * @since 1.0.0
     */
    private static function find_group_row_by_key_or_slug( string $lookup_key ) {
        $lookup_key = trim( (string) $lookup_key );
        if ( '' === $lookup_key ) {
            return null;
        }

        if ( isset( $GLOBALS['wpdb'] ) && is_object( $GLOBALS['wpdb'] ) && property_exists( $GLOBALS['wpdb'], 'storage' ) && is_array( $GLOBALS['wpdb']->storage ) ) {
            $table_name = DBHelper::table_name( 'groups' );
            $rows = $GLOBALS['wpdb']->storage[ $table_name ] ?? array();
            foreach ( (array) $rows as $row ) {
                if ( is_array( $row ) ) {
                    $group_key = trim( (string) ( $row['group_key'] ?? '' ) );
                    $group_slug = trim( (string) ( $row['group_slug'] ?? '' ) );
                    if ( $group_key === $lookup_key || $group_slug === $lookup_key ) {
                        return $row;
                    }
                }
            }
        }

        if ( function_exists( 'is_admin' ) && is_admin() ) {
            $rows = DBHelper::get_all( 'groups' );
            foreach ( $rows as $row ) {
                if ( is_array( $row ) ) {
                    $group_key = trim( (string) ( $row['group_key'] ?? '' ) );
                    $group_slug = trim( (string) ( $row['group_slug'] ?? '' ) );
                    if ( $group_key === $lookup_key || $group_slug === $lookup_key ) {
                        return $row;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Find a group row by id when direct DB lookups do not return a result.
     *
     * @param int $group_id Group ID to match.
     * @return array<string, mixed>|null
     * @since 1.0.0
     */
    private static function find_group_row_by_id( int $group_id ) {
        $group_id = abs( (int) $group_id );
        if ( $group_id <= 0 ) {
            return null;
        }

        if ( isset( $GLOBALS['wpdb'] ) && is_object( $GLOBALS['wpdb'] ) && property_exists( $GLOBALS['wpdb'], 'storage' ) && is_array( $GLOBALS['wpdb']->storage ) ) {
            $table_name = DBHelper::table_name( 'groups' );
            $rows = $GLOBALS['wpdb']->storage[ $table_name ] ?? array();
            foreach ( (array) $rows as $row ) {
                if ( is_array( $row ) && abs( (int) ( $row['id'] ?? 0 ) ) === $group_id ) {
                    return $row;
                }
            }
        }

        if ( function_exists( 'is_admin' ) && is_admin() ) {
            $rows = DBHelper::get_all( 'groups' );
            foreach ( $rows as $row ) {
                if ( is_array( $row ) && abs( (int) ( $row['id'] ?? 0 ) ) === $group_id ) {
                    return $row;
                }
            }
        }

        return null;
    }

    /**
     * Persist the custom groups row to the ModPress DB table.
     *
     * @param array<string, mixed> $group_payload Row payload.
     * @return int|false
     * @since 1.0.0
     */
    private function persist_group_record( array $group_payload ) {
        $group_id = $this->resolve_group_record_id(
            RequestHelper::value( $_POST, 'group_edit_key', '' ),
            $group_payload['group_slug'] ?? ''
        );

        if ( $group_id > 0 ) {
            $updated = DBHelper::update(
                'groups',
                array(
                    'group_key'           => $group_payload['group_key'] ?? '',
                    'group_name'          => $group_payload['group_name'] ?? '',
                    'group_slug'          => $group_payload['group_slug'] ?? '',
                    'group_singular_name' => $group_payload['group_singular_name'] ?? '',
                    'group_description'   => $group_payload['group_description'] ?? '',
                    'group_hierarchical'  => $group_payload['group_hierarchical'] ?? false,
                    'group_parent'        => $group_payload['group_parent'] ?? serialize( array() ),
                    'group_parent_entry'  => $group_payload['group_parent_entry'] ?? serialize( array() ),
                    'group_fields'        => $group_payload['group_fields'] ?? null,
                    'updated_at'          => current_time( 'mysql' ),
                ),
                array( 'id' => $group_id )
            );

            return $updated ? $group_id : false;
        }

        $existing_group = DBHelper::get_row( 'groups', array( 'group_key' => $group_payload['group_key'] ?? '' ) );
        if ( empty( $existing_group ) ) {
            $existing_group = DBHelper::get_row( 'groups', array( 'group_slug' => $group_payload['group_slug'] ?? '' ) );
        }

        if ( ! empty( $existing_group ) && isset( $existing_group['id'] ) ) {
            $updated = DBHelper::update(
                'groups',
                array(
                    'group_key'           => $group_payload['group_key'] ?? '',
                    'group_name'          => $group_payload['group_name'] ?? '',
                    'group_singular_name' => $group_payload['group_singular_name'] ?? '',
                    'group_description'   => $group_payload['group_description'] ?? '',
                    'group_hierarchical'  => $group_payload['group_hierarchical'] ?? false,
                    'group_parent'        => $group_payload['group_parent'] ?? serialize( array() ),
                    'group_parent_entry'  => $group_payload['group_parent_entry'] ?? serialize( array() ),
                    'group_fields'        => $group_payload['group_fields'] ?? null,
                    'updated_at'          => current_time( 'mysql' ),
                ),
                array( 'id' => (int) $existing_group['id'] )
            );

            return $updated ? (int) $existing_group['id'] : false;
        }

        $inserted = DBHelper::insert(
            'groups',
            array(
                'group_key'           => $group_payload['group_key'] ?? '',
                'group_name'          => $group_payload['group_name'] ?? '',
                'group_slug'          => $group_payload['group_slug'] ?? '',
                'group_singular_name' => $group_payload['group_singular_name'] ?? '',
                'group_description'   => $group_payload['group_description'] ?? '',
                'group_hierarchical'  => $group_payload['group_hierarchical'] ?? false,
                'group_parent'        => $group_payload['group_parent'] ?? serialize( array() ),
                'group_parent_entry'  => $group_payload['group_parent_entry'] ?? serialize( array() ),
                'group_fields'        => $group_payload['group_fields'] ?? null,
                'created_by'          => $group_payload['created_by'] ?? get_current_user_id(),
                'created_at'          => current_time( 'mysql' ),
                'updated_at'          => current_time( 'mysql' ),
            )
        );

        return $inserted;
    }

    /**
     * Resolve an existing ModPress group row by the canonical key or slug.
     *
     * @param string $edit_key Raw value from the edit form.
     * @param string $fallback_slug Group slug fallback.
     * @return int
     * @since 1.0.0
     */
    private function resolve_group_record_id( string $edit_key, string $fallback_slug = '' ): int {
        $edit_key = trim( (string) $edit_key );
        if ( '' !== $edit_key ) {
            if ( is_numeric( $edit_key ) ) {
                return abs( (int) $edit_key );
            }

            $lookup_key = sanitize_key( $edit_key );
            if ( '' !== $lookup_key ) {
                $group_row = DBHelper::get_row( 'groups', array( 'group_key' => $lookup_key ) );
                if ( ! empty( $group_row ) && isset( $group_row['id'] ) ) {
                    return abs( (int) $group_row['id'] );
                }

                $group_row = DBHelper::get_row( 'groups', array( 'group_slug' => $lookup_key ) );
                if ( ! empty( $group_row ) && isset( $group_row['id'] ) ) {
                    return abs( (int) $group_row['id'] );
                }
            }
        }

        $fallback_slug = sanitize_key( (string) $fallback_slug );
        if ( '' !== $fallback_slug ) {
            $group_row = DBHelper::get_row( 'groups', array( 'group_key' => $fallback_slug ) );
            if ( ! empty( $group_row ) && isset( $group_row['id'] ) ) {
                return abs( (int) $group_row['id'] );
            }

            $group_row = DBHelper::get_row( 'groups', array( 'group_slug' => $fallback_slug ) );
            if ( ! empty( $group_row ) && isset( $group_row['id'] ) ) {
                return abs( (int) $group_row['id'] );
            }
        }

        return 0;
    }

    /**
     * Return a readable database error message when a group save fails.
     *
     * @return string
     * @since 1.0.0
     */
    private function get_group_save_failure_reason(): string {
        global $wpdb;

        if ( isset( $wpdb->last_error ) && '' !== trim( (string) $wpdb->last_error ) ) {
            return trim( (string) $wpdb->last_error );
        }

        return __( 'The database update failed while saving the group record.', 'modpress' );
    }

    /**
     * Save a taxonomy term from the entry modal.
     *
     * @return void
     * @since 1.0.0
     */
    public function get_group(): void {
		if ( ! current_user_can( 'modpress_manager_groups_view' ) && ! current_user_can( 'modpress_manager_groups_edit' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to view ModPress groups.', 'modpress' ) ), 403 );
		}

		if ( ! empty( $_POST['nonce'] ) ) {
			check_ajax_referer( 'modpress_get_group', 'nonce' );
		} elseif ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'The selected group could not be loaded.', 'modpress' ) ), 403 );
		}

		$group_id  = RequestHelper::integer( $_POST, 'group_id', 0 );
		$group_key = RequestHelper::key( $_POST, 'group_key', '' );

		if ( $group_id > 0 ) {
			$group_row = DBHelper::get_by_id( 'groups', $group_id );
		} else {
			if ( '' === $group_key ) {
				wp_send_json_error( array( 'message' => __( 'The selected group could not be loaded.', 'modpress' ) ), 400 );
			}

			$lookup_value = sanitize_key( (string) $group_key );
			if ( '' === $lookup_value ) {
				wp_send_json_error( array( 'message' => __( 'The selected group could not be loaded.', 'modpress' ) ), 400 );
			}

			$group_row = DBHelper::get_row( 'groups', array( 'group_key' => $lookup_value ) );
			if ( empty( $group_row ) ) {
				$group_row = DBHelper::get_row( 'groups', array( 'group_slug' => $lookup_value ) );
			}
		}

		if ( empty( $group_row ) ) {
			wp_send_json_error( array( 'message' => __( 'The selected group could not be found.', 'modpress' ) ), 404 );
		}

		$serialized_parent_ids = is_string( $group_row['group_parent'] ?? '' ) ? @unserialize( (string) ( $group_row['group_parent'] ?? '' ) ) : null;
		$group_parent_ids = is_array( $serialized_parent_ids ) ? array_values(
			array_filter(
				array_map( static fn ( $id ) => (int) $id, $serialized_parent_ids ),
				static fn ( $id ) => $id > 0
			)
		) : array();

		$group_rows = DBHelper::get_all( 'groups' );
		$group_key_map = array();
		foreach ( $group_rows as $row ) {
			$group_id = absint( $row['id'] ?? 0 );
			$group_key = trim( (string) ( $row['group_key'] ?? $row['group_slug'] ?? '' ) );
			if ( $group_id > 0 && '' !== $group_key ) {
				$group_key_map[ $group_id ] = $group_key;
			}
		}
		$serialized_parent_entries = is_string( $group_row['group_parent_entry'] ?? '' ) ? @unserialize( (string) ( $group_row['group_parent_entry'] ?? '' ) ) : null;
		$group_parent_entries = is_array( $serialized_parent_entries ) ? array_values(
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
				'group_key'           => (string) ( $group_row['group_key'] ?? $lookup_value ),
				'group_name'          => (string) ( $group_row['group_name'] ?? '' ),
				'group_slug'          => (string) ( $group_row['group_slug'] ?? $lookup_value ),
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
     * Get a group entry.
     *
     * @return void
     * @since 1.0.0
     */
	public function get_group_entry(): void {
		if ( ! current_user_can( 'modpress_manager_groups_view' ) && ! current_user_can( 'modpress_manager_groups_edit' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to view ModPress taxonomy entries.', 'modpress' ) ), 403 );
		}

		if ( ! empty( $_POST['nonce'] ) ) {
			check_ajax_referer( 'modpress_get_group_entry', 'nonce' );
		} elseif ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'The selected entry could not be loaded.', 'modpress' ) ), 403 );
		}

		$taxonomy = RequestHelper::key( $_POST, 'group_slug', '' );
		$term_id  = RequestHelper::integer( $_POST, 'group_entry_id', 0 );

		if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) || $term_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'The selected entry could not be loaded.', 'modpress' ) ), 400 );
		}

		$term = get_term( $term_id, $taxonomy );
		if ( is_wp_error( $term ) || ! $term ) {
			wp_send_json_error( array( 'message' => __( 'The selected entry could not be found.', 'modpress' ) ), 404 );
		}

		$group_row = DBHelper::get_row( 'groups', array( 'group_key' => sanitize_key( (string) $taxonomy ) ) );
		if ( empty( $group_row ) ) {
			$group_row = DBHelper::get_row( 'groups', array( 'group_slug' => sanitize_key( str_replace( 'modpress_', '', $taxonomy ) ) ) );
		}

		$custom_fields = array();
		if ( ! empty( $group_row['group_fields'] ) ) {
			$decoded_fields = json_decode( (string) $group_row['group_fields'], true );
			if ( is_array( $decoded_fields ) ) {
				foreach ( $decoded_fields as $field ) {
					$field_slug = trim( (string) ( $field['slug'] ?? $field['name'] ?? '' ) );
					if ( '' === $field_slug ) {
						continue;
					}

					$custom_fields[ $field_slug ] = GroupsManager::get_group_entry( (int) $term->term_id, $taxonomy, $field_slug );
				}
			}
		}

		wp_send_json_success(
			array(
				'term_id' => (int) $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
				'description' => $term->description,
				'parent' => (int) $term->parent,
				'icon' => GroupsManager::get_group_entry( (int) $term->term_id, $taxonomy, 'entry_icon' ) ?: '',
				'custom_fields' => $custom_fields,
			)
		);
	}
    /**
     * Save a group entry.
     *
     * @return void
     * @since 1.0.0
     */
	public function save_group_entry(): void {
        if ( ! current_user_can( 'modpress_manager_groups_edit' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage ModPress taxonomy entries.', 'modpress' ) );
        }

        check_admin_referer( 'modpress_save_group_entry', 'modpress_group_entry_nonce' );

        $taxonomy = RequestHelper::key( $_POST, 'group_slug', '' );
        $name     = RequestHelper::text( $_POST, 'entry_name', '' );

        if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
            LoggerHelper::write_log( 'ModPress group entry save rejected: invalid taxonomy ' . $taxonomy );
            $this->redirect_mod_group_result( false, __( 'The selected taxonomy is invalid.', 'modpress' ) );
        }

        if ( '' === $name ) {
            LoggerHelper::write_log( 'ModPress group entry save rejected: missing entry name.' );
            $this->redirect_mod_group_result( false, __( 'An entry name is required.', 'modpress' ) );
        }

        $term_id    = RequestHelper::integer( $_POST, 'group_entry_id', 0 );
        $slug       = RequestHelper::slug( $_POST, 'entry_slug', $name );
        $description = SanitizationHelper::textarea( RequestHelper::value( $_POST, 'entry_description', '' ), '' );
        $parent     = RequestHelper::integer( $_POST, 'entry_parent', 0 );
        $icon       = RequestHelper::text( $_POST, 'entry_icon', '' );
        $args       = array(
            'name'        => $name,
            'description' => $description,
            'slug'        => $slug,
            'parent'      => $parent > 0 ? $parent : 0,
        );

        if ( $term_id > 0 ) {
            $result = wp_update_term( $term_id, $taxonomy, $args );
        } else {
            $result = wp_insert_term( $name, $taxonomy, $args );
        }

        if ( is_wp_error( $result ) ) {
            LoggerHelper::write_log( 'ModPress group entry save failed for taxonomy ' . $taxonomy . ': ' . $result->get_error_message() );
            $this->redirect_mod_group_result( false, $result->get_error_message() );
        }

        $saved_term_id = 0;
        if ( is_array( $result ) ) {
            $saved_term_id = (int) ( $result['term_id'] ?? 0 );
        }

        if ( $saved_term_id > 0 ) {
            $group_row = DBHelper::get_row( 'groups', array( 'group_key' => sanitize_key( (string) $taxonomy ) ) );
            if ( empty( $group_row ) ) {
                $group_row = DBHelper::get_row( 'groups', array( 'group_slug' => sanitize_key( str_replace( 'modpress_', '', $taxonomy ) ) ) );
            }
            $group_fields = array();
            if ( ! empty( $group_row['group_fields'] ) ) {
                $decoded_fields = json_decode( (string) $group_row['group_fields'], true );
                if ( is_array( $decoded_fields ) ) {
                    $group_fields = $decoded_fields;
                }
            }

            foreach ( $group_fields as $field ) {
                $field_slug = trim( (string) ( $field['slug'] ?? $field['name'] ?? '' ) );
                if ( '' === $field_slug ) {
                    continue;
                }

                $field_id = trim( (string) ( $field['id'] ?? '' ) );
                if ( '' === $field_id ) {
                    $field_id = 'field_' . sanitize_key( $field_slug );
                }

                $field_keys = array(
                    'entry_custom_field_' . sanitize_key( $field_id ),
                    'entry_custom_field_' . sanitize_key( $field_slug ),
                    $field_slug,
                    sanitize_key( $field_slug ),
                );

                $field_value = null;
                foreach ( $field_keys as $field_key ) {
                    if ( array_key_exists( $field_key, $_POST ) ) {
                        $field_value = RequestHelper::value( $_POST, $field_key, null );
                        break;
                    }

                    if ( array_key_exists( $field_key . '[]', $_POST ) ) {
                        $field_value = RequestHelper::value( $_POST, $field_key . '[]', null );
                        break;
                    }
                }

                if ( null === $field_value ) {
                    continue;
                }

                if ( is_array( $field_value ) ) {
                    $field_value = array_values(
                        array_map(
                            static function ( $item ) {
                                return is_scalar( $item ) ? sanitize_text_field( wp_unslash( (string) $item ) ) : '';
                            },
                            wp_unslash( $field_value )
                        )
                    );
                } else {
                    $field_value = sanitize_text_field( wp_unslash( (string) $field_value ) );
                }

                if ( is_array( $field_value ) ) {
                    $field_value = array_filter( $field_value, static fn( $value ) => '' !== trim( (string) $value ) );
                    if ( empty( $field_value ) ) {
                        delete_term_meta( $saved_term_id, $field_slug );
                        continue;
                    }
                } elseif ( '' === trim( (string) $field_value ) ) {
                    delete_term_meta( $saved_term_id, $field_slug );
                    continue;
                }

                update_term_meta( $saved_term_id, $field_slug, $field_value );
            }

            if ( '' !== $icon ) {
                update_term_meta( $saved_term_id, 'entry_icon', $icon );
            } else {
                delete_term_meta( $saved_term_id, 'entry_icon' );
            }
        }

        LoggerHelper::write_log( 'ModPress group entry saved successfully for taxonomy ' . $taxonomy );
        $this->redirect_mod_group_result( true, __( 'Entry saved successfully.', 'modpress' ) );
    }

    /**
     * Delete a taxonomy term from the entry modal.
     *
     * @return void
     * @since 1.0.0
     */
    public function delete_group_entry(): void {
        if ( ! current_user_can( 'modpress_manager_groups_edit' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage ModPress taxonomy entries.', 'modpress' ) );
        }

        check_admin_referer( 'modpress_delete_group_entry', 'modpress_group_entry_nonce' );

        $taxonomy = RequestHelper::key( $_POST, 'group_slug', '' );
        if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
            LoggerHelper::write_log( 'ModPress group entry delete rejected: invalid taxonomy.' );
            $this->redirect_mod_group_result( false, __( 'The selected entry could not be deleted.', 'modpress' ) );
        }

        $requested_ids = RequestHelper::value( $_POST, 'group_entry_ids', array() );
        if ( ! is_array( $requested_ids ) ) {
            $requested_ids = array( $requested_ids );
        }

        $term_ids = array();
        foreach ( $requested_ids as $term_id ) {
            $parsed = abs( (int) $term_id );
            if ( $parsed > 0 ) {
                $term_ids[] = $parsed;
            }
        }

        if ( empty( $term_ids ) ) {
            $term_ids[] = RequestHelper::integer( $_POST, 'group_entry_id', 0 );
        }

        $valid_ids = array_values( array_unique( array_filter( $term_ids, static fn( $id ) => $id > 0 ) ) );
        if ( empty( $valid_ids ) ) {
            LoggerHelper::write_log( 'ModPress group entry delete rejected: invalid taxonomy or term id.' );
            $this->redirect_mod_group_result( false, __( 'The selected entry could not be deleted.', 'modpress' ) );
        }

        $failed_ids = array();
        foreach ( $valid_ids as $term_id ) {
            $deleted = wp_delete_term( $term_id, $taxonomy );
            if ( is_wp_error( $deleted ) ) {
                $failed_ids[] = $term_id;
                LoggerHelper::write_log( 'ModPress group entry delete failed for taxonomy ' . $taxonomy . ' and term ' . $term_id . ': ' . $deleted->get_error_message() );
            }
        }

        if ( count( $failed_ids ) === count( $valid_ids ) ) {
            $this->redirect_mod_group_result( false, __( 'The selected entries could not be deleted.', 'modpress' ) );
        }

        LoggerHelper::write_log( 'ModPress group entries deleted successfully for taxonomy ' . $taxonomy . ' (ids=' . implode( ',', $valid_ids ) . ')' );
        $this->redirect_mod_group_result( true, __( 'Entry deleted successfully.', 'modpress' ) );
    }

    /**
     * Fallback for any unimplemented Mod admin action callback.
     *
     * @param string $name Method name.
     * @param array  $arguments Arguments passed to the callback.
     * @return void
     * @since 1.0.0
     */
    public function __call( string $name, array $arguments ): void {
        LoggerHelper::write_log( 'ModPress admin action callback not implemented: ' . $name );

        $tab = RequestHelper::key( $_POST, 'return_tab', 'groups' );
        $this->redirect_mod_action_result( $tab, __( 'This ModPress action is not available yet.', 'modpress' ), false );
    }

    /**
     * Handle a tag save request.
     *
     * @return void
     * @since 1.0.0
     */
    public function save_tags(): void {
        if ( ! current_user_can( 'modpress_manager_tags_edit' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage ModPress tags.', 'modpress' ) );
        }

        check_admin_referer( 'modpress_save_tags' );
        $this->redirect_mod_action_result( 'groups', __( 'Tags saved successfully.', 'modpress' ), true );
    }

    /**
     * Handle a file save request.
     *
     * @return void
     * @since 1.0.0
     */
    public function save_files(): void {
        if ( ! current_user_can( 'modpress_manager_files_edit' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage ModPress files.', 'modpress' ) );
        }

        check_admin_referer( 'modpress_save_files' );
        $this->redirect_mod_action_result( 'files', __( 'Files saved successfully.', 'modpress' ), true );
    }

    /**
     * Handle a file type save request.
     *
     * @return void
     * @since 1.0.0
     */
    public function save_file_type(): void {
        if ( ! current_user_can( 'modpress_manager_files_edit' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage ModPress file types.', 'modpress' ) );
        }

        check_admin_referer( 'modpress_save_file_type' );
        $this->redirect_mod_action_result( 'files', __( 'File type saved successfully.', 'modpress' ), true );
    }

    /**
     * Handle a changelog save request.
     *
     * @return void
     * @since 1.0.0
     */
    public function save_changelog(): void {
        if ( ! current_user_can( 'modpress_manager_changelog_edit' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage ModPress changelogs.', 'modpress' ) );
        }

        check_admin_referer( 'modpress_save_changelog' );
        $this->redirect_mod_action_result( 'changelog', __( 'Changelog saved successfully.', 'modpress' ), true );
    }

    /**
     * Handle a changelog category save request.
     *
     * @return void
     * @since 1.0.0
     */
    public function save_changelog_category(): void {
        if ( ! current_user_can( 'modpress_manager_changelog_edit' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage ModPress changelog categories.', 'modpress' ) );
        }

        check_admin_referer( 'modpress_save_changelog_category' );
        $this->redirect_mod_action_result( 'changelog', __( 'Changelog category saved successfully.', 'modpress' ), true );
    }

    /**
     * Redirect back to the Mod manager screen with a notice.
     *
     * @param string $tab Page tab to return to.
     * @param string $message Notice text.
     * @param bool   $success Whether the save succeeded.
     * @return void
     */
    public static function set_admin_notice_flash( string $message, bool $success = true ): void {
        $key = 'modpress_admin_notice_' . get_current_user_id();
        $notice = array(
            'message' => '' !== trim( $message ) ? trim( $message ) : ( $success ? __( 'The ModPress action was completed successfully.', 'modpress' ) : __( 'The ModPress action could not be completed.', 'modpress' ) ),
            'type' => $success ? 'success' : 'error',
        );

        set_transient( $key, $notice, 60 );
    }
    /**
     * Consume and return the admin notice flash.
     *
     * @return array Notice data with 'message' and 'type'.
     * @since 1.0.0
     */
    public static function consume_admin_notice_flash(): array {
        $key = 'modpress_admin_notice_' . get_current_user_id();
        $notice = get_transient( $key );

        delete_transient( $key );

        if ( ! is_array( $notice ) ) {
            return array(
                'message' => '',
                'type' => 'info',
            );
        }

        $message = isset( $notice['message'] ) ? (string) $notice['message'] : '';
        $type = isset( $notice['type'] ) ? (string) $notice['type'] : 'info';

        if ( ! in_array( $type, array( 'success', 'error', 'warning', 'info' ), true ) ) {
            $type = 'info';
        }

        return array(
            'message' => $message,
            'type' => $type,
        );
    }
    /**
     * Redirect back to the Mod action result with a notice.
     *
     * @param string $tab Page tab to return to.
     * @param string $message Notice text.
     * @param bool   $success Whether the save succeeded.
     * @return void
     */
    private function redirect_mod_action_result( string $tab, string $message = '', bool $success = true ): void {
        $query = array(
            'page' => 'modpress',
            'group' => 'mods',
            'tab' => $tab,
        );

        if ( '' !== trim( $message ) ) {
            self::set_admin_notice_flash( $message, $success );
        }

        wp_safe_redirect( admin_url( 'admin.php?' . build_query( $query ) ) );
        exit;
    }

    /**
     * Redirect back to the Mod Groups screen with a notice.
     *
     * @param bool   $success Whether the save succeeded.
     * @param string $message Notice text.
     * @return void
     */
    private function redirect_mod_group_result( bool $success, string $message = '' ): void {
        $this->redirect_mod_action_result( 'groups', $message, $success );
    }
    /**
     * Render a repeatable option row template.
     *
     * @param string $type The option type.
     * @return string HTML output.
     */
    public static function repeatable_option_row(string $type, array $options = array() ): string {
        ob_start();
        ?>
        <div class="modpress-option-repeater" data-option-type="<?php echo esc_attr( $type ); ?>">
            <div class="d-flex justify-content-end mb-2">
                <?php echo FormFieldHelper::button(
                    esc_html__( 'Add Option', 'modpress' ),
                    array(
                        'type' => 'button',
                        'class' => 'btn btn-outline-secondary btn-sm',
                        'id' => 'add-option-row',
                        'attributes' => array(
                            'data-role' => 'add-option-row',
                            'data-option-type' => $type,
                        )
                    )
                ); ?>
            </div>
            <div class="modpress-option-repeater-items">
                <div class="modpress-option-row-template d-none" data-option-type="<?php echo esc_attr( $type ); ?>">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-5">
                            <?php echo FormFieldHelper::text_input(
                                'custom_field_type_' . $type . '[][label]',
                                '',
                                array(
                                    'class' => 'form-control w-100',
                                    'placeholder' => esc_attr__( 'Label', 'modpress' ),
                                    'attributes' => array(
                                        'data-option-role' => 'label',
                                    ),
                                )
                            ); ?>
                        </div>
                        <div class="col-md-5">
                            <?php echo FormFieldHelper::text_input(
                                'custom_field_type_' . $type . '[][option]',
                                '',
                                array(
                                    'class' => 'form-control w-100',
                                    'placeholder' => esc_attr__( 'Option', 'modpress' ),
                                    'attributes' => array(
                                        'data-option-role' => 'value',
                                    ),
                                )
                            ); ?>
                        </div>
                        <div class="col-md-2">
                            <?php echo FormFieldHelper::button(
                                esc_html__( 'Delete', 'modpress' ),
                                array(
                                    'type' => 'button',
                                    'class' => 'btn btn-outline-danger btn-sm',
                                    'id' => 'remove-option-row',
                                    'attributes' => array(
                                        'data-role' => 'remove-option-row',
                                    ),
                                )
                            ); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }
    /**
     * Render a group custom field.
     *
     * @param array $custom_field The custom field configuration.
     * @param array $value The current value of the custom field.
     * @return string HTML output.
     */
    public static function render_group_custom_field( array $custom_field, $value = array() ) {
        if ( ! is_array( $custom_field ) ) {
            return '';
        }

        $field_slug = trim( (string) ( $custom_field['slug'] ?? $custom_field['name'] ?? '' ) );
        if ( '' === $field_slug ) {
            return '';
        }

        $field_id = trim( (string) ( $custom_field['id'] ?? '' ) );
        if ( '' === $field_id ) {
            $field_id = 'field_' . sanitize_key( $field_slug );
        }

        $field_name = 'entry_custom_field_' . sanitize_key( $field_id );
        $field_type = strtolower( (string) ( $custom_field['type'] ?? 'text' ) );
        $value = is_array( $value ) ? $value : array();

        $normalize_saved_value = static function ( $raw ) use ( &$normalize_saved_value ) {
            if ( is_array( $raw ) ) {
                $flattened = array();
                foreach ( $raw as $item ) {
                    if ( is_array( $item ) ) {
                        $nested = $normalize_saved_value( $item );
                        if ( is_array( $nested ) ) {
                            foreach ( $nested as $nested_item ) {
                                $trimmed = trim( (string) $nested_item );
                                if ( '' !== $trimmed ) {
                                    $flattened[] = $trimmed;
                                }
                            }
                            continue;
                        }

                        $trimmed = trim( (string) $nested );
                        if ( '' !== $trimmed ) {
                            $flattened[] = $trimmed;
                        }
                        continue;
                    }

                    $trimmed = trim( (string) $item );
                    if ( '' !== $trimmed ) {
                        $flattened[] = $trimmed;
                    }
                }

                return array_values( array_unique( $flattened ) );
            }

            if ( null === $raw ) {
                return '';
            }

            return is_scalar( $raw ) ? trim( (string) $raw ) : '';
        };

        $resolve_field_value = static function ( string $field_name_key ) use ( $value, $field_slug, $field_id, $normalize_saved_value ) {
            $keys = array(
                $field_name_key,
                $field_name_key . '[]',
                $field_name_key . '[0]',
                $field_slug,
                $field_slug . '[]',
                sanitize_key( $field_slug ),
                sanitize_key( $field_slug ) . '[]',
                $field_id,
                $field_id . '[]',
                sanitize_key( $field_id ),
                sanitize_key( $field_id ) . '[]',
            );

            foreach ( $keys as $key ) {
                if ( array_key_exists( $key, $value ) ) {
                    return $normalize_saved_value( $value[ $key ] );
                }
            }

            if ( is_array( $value ) ) {
                foreach ( $value as $candidate ) {
                    $normalized = $normalize_saved_value( $candidate );
                    if ( is_array( $normalized ) && ! empty( $normalized ) ) {
                        return $normalized;
                    }
                    if ( '' !== (string) $normalized ) {
                        return $normalized;
                    }
                }
            }

            return '';
        };

        $field_value = $resolve_field_value( $field_name );

        $normalize_options = static function ( $options ) {
            $normalized = array();
            foreach ( (array) $options as $option ) {
                if ( is_array( $option ) ) {
                    $label = (string) ( $option['label'] ?? $option['value'] ?? $option['option'] ?? '' );
                    $option_value = (string) ( $option['option'] ?? $option['value'] ?? $label );
                    if ( '' !== $label || '' !== $option_value ) {
                        $normalized[] = array(
                            'label' => $label,
                            'value' => $option_value,
                        );
                    }
                    continue;
                }

                $option_label = (string) $option;
                $normalized[] = array(
                    'label' => $option_label,
                    'value' => $option_label,
                );
            }

            return $normalized;
        };

        $field_label = FormFieldHelper::label(
            $field_name,
            $custom_field['name'] ?? $field_slug,
            array(
                'class' => 'form-label',
                'description' => $custom_field['description'] ?? '',
            )
        );

        if ( 'text' === $field_type ) {
            return sprintf(
                '<tr><th scope="row" class="align-top text-nowrap pe-4">%1$s</th><td>%2$s</td></tr>',
                $field_label,
                FormFieldHelper::text_input(
                    $field_name,
                    (string) $field_value,
                    array(
                        'class' => 'form-control w-100',
                        'placeholder' => $custom_field['name'] ?? $field_slug,
                    )
                )
            );
        }

        if ( 'textarea' === $field_type ) {
            return sprintf(
                '<tr><th scope="row" class="align-top text-nowrap pe-4">%1$s</th><td>%2$s</td></tr>',
                $field_label,
                FormFieldHelper::textarea(
                    $field_name,
                    (string) $field_value,
                    array(
                        'class' => 'form-control w-100',
                        'rows' => absint( $custom_field['rows'] ?? 4 ),
                        'placeholder' => $custom_field['name'] ?? $field_slug,
                    )
                )
            );
        }

        if ( 'number' === $field_type ) {
            return sprintf(
                '<tr><th scope="row" class="align-top text-nowrap pe-4">%1$s</th><td>%2$s</td></tr>',
                $field_label,
                FormFieldHelper::input(
                    $field_name,
                    (string) $field_value,
                    array(
                        'type' => 'number',
                        'class' => 'form-control w-100',
                        'placeholder' => $custom_field['name'] ?? $field_slug,
                    )
                )
            );
        }

        if ( 'color' === $field_type ) {
            return sprintf(
                '<tr><th scope="row" class="align-top text-nowrap pe-4">%1$s</th><td>%2$s</td></tr>',
                $field_label,
                FormFieldHelper::input(
                    $field_name,
                    (string) $field_value,
                    array(
                        'type' => 'color',
                        'class' => 'form-control form-control-color w-100',
                    )
                )
            );
        }

        if ( 'switch' === $field_type ) {
            return sprintf(
                '<tr><th scope="row" class="align-top text-nowrap pe-4">%1$s</th><td>%2$s</td></tr>',
                $field_label,
                FormFieldHelper::switch(
                    $field_name,
                    '1',
                    '',
                    array(
                        'checked' => ! empty( $field_value ) && ( '1' === (string) $field_value || true === $field_value || 'true' === strtolower( (string) $field_value ) || 'on' === strtolower( (string) $field_value ) || 'yes' === strtolower( (string) $field_value ) ),
                    )
                )
            );
        }

        if ( 'radio' === $field_type ) {
            $option_rows = $normalize_options( $custom_field['options'] ?? array() );
            $selected_values = is_array( $field_value ) ? array_values( array_filter( array_map( 'strval', $field_value ), static fn( $value ) => '' !== trim( (string) $value ) ) ) : array( (string) $field_value );
            return sprintf(
                '<tr><th scope="row" class="align-top text-nowrap pe-4">%1$s</th><td>%2$s</td></tr>',
                $field_label,
                FormFieldHelper::button_group(
                    $field_name,
                    $option_rows,
                    $selected_values,
                    array(
                        'type' => 'radio',
                    )
                )
            );
        }

        if ( 'select' === $field_type ) {
            $option_rows = $normalize_options( $custom_field['options'] ?? array() );
            $selected_value = is_array( $field_value ) ? (string) ( $field_value[0] ?? '' ) : (string) $field_value;
            return sprintf(
                '<tr><th scope="row" class="align-top text-nowrap pe-4">%1$s</th><td>%2$s</td></tr>',
                $field_label,
                FormFieldHelper::bootstrap_select(
                    $field_name,
                    array(
                        'data' => $option_rows,
                        'selected' => $selected_value,
                        'class' => 'w-100',
                    )
                )
            );
        }

        if ( 'image' === $field_type ) {
            $allowed_modes = array_values(
                array_unique(
                    array_filter(
                        array_map(
                            static fn ( $mode ) => strtolower( trim( (string) $mode ) ),
                            (array) ( $custom_field['image_types'] ?? array() )
                        ),
                        static fn ( $mode ) => in_array( $mode, array( 'image', 'icon', 'url' ), true )
                    )
                )
            );

            if ( empty( $allowed_modes ) ) {
                return '';
            }

            $image_value = is_array( $field_value ) ? (string) ( $field_value[0] ?? '' ) : (string) $field_value;
            $selected_mode = $allowed_modes[0];

            if ( '' !== trim( $image_value ) ) {
                if ( filter_var( $image_value, FILTER_VALIDATE_URL ) ) {
                    $selected_mode = in_array( 'url', $allowed_modes, true ) ? 'url' : $selected_mode;
                } elseif ( false !== stripos( $image_value, 'fa-' ) ) {
                    $selected_mode = in_array( 'icon', $allowed_modes, true ) ? 'icon' : $selected_mode;
                } elseif ( in_array( 'image', $allowed_modes, true ) ) {
                    $selected_mode = 'image';
                }
            }

            $mode_html = '';
            if ( count( $allowed_modes ) > 1 ) {
                $mode_choices = array();
                foreach ( $allowed_modes as $mode ) {
                    $mode_choices[] = array(
                        'value' => $mode,
                        'label' => 'image' === $mode ? __( 'Image', 'modpress' ) : ( 'icon' === $mode ? __( 'Icon', 'modpress' ) : __( 'URL', 'modpress' ) ),
                        'input_attributes' => array(
                            'data-input-name' => $field_name,
                        ),
                    );
                }

                $mode_html = FormFieldHelper::button_group(
                    $field_name . '_mode',
                    $mode_choices,
                    $selected_mode,
                    array(
                        'type' => 'radio',
                        'class' => 'modpress-image-picker-mode-group mb-2',
                        'button_class' => 'btn-outline-primary',
                    )
                );
            }

            $image_block = '';
            if ( in_array( 'image', $allowed_modes, true ) ) {
                $image_block .= sprintf(
                    '<div class="modpress-image-entry-mode modpress-image-entry-mode-image" data-image-mode="image"%1$s>%2$s</div>',
                    'image' === $selected_mode ? '' : ' style="display:none;"',
                    WPMediaHelper::create(
                        $image_value,
                        array(
                            'name' => $field_name,
                            'id' => sanitize_key( $field_name . '_image' ),
                            'button_text' => __( 'Select Image', 'modpress' ),
                            'class' => 'btn btn-secondary modpress-image-select',
                            'show_remove' => true,
                            'media_type' => 'image',
                            'return_value' => 'url',
                            'helper_text' => '',
                        ),
                        array(
                            'class' => 'img-fluid rounded',
                            'style' => 'card',
                            'alt' => $custom_field['name'] ?? $field_slug,
                            'show_remove' => true,
                        )
                    )
                );
            }

            if ( in_array( 'icon', $allowed_modes, true ) ) {
                $image_block .= sprintf(
                    '<div class="modpress-image-entry-mode modpress-image-entry-mode-icon" data-image-mode="icon"%1$s>%2$s</div>',
                    'icon' === $selected_mode ? '' : ' style="display:none;"',
                    IconPicker::get_instance()->render_picker(
                        $field_name,
                        $image_value,
                        array(
                            'id' => sanitize_key( $field_name . '-picker' ),
                            'button_text' => __( 'Choose Icon', 'modpress' ),
                        )
                    )
                );
            }

            if ( in_array( 'url', $allowed_modes, true ) ) {
                $url_mode_display = 'url' === $selected_mode ? '' : ' style="display:none;"';
                $url_field = FormFieldHelper::input(
                    $field_name,
                    $image_value,
                    array(
                        'type' => 'url',
                        'class' => 'form-control modpress-image-url-input w-100',
                        'placeholder' => __( 'https://example.com/image.jpg', 'modpress' ),
                    )
                );
                $url_preview = sprintf(
                    '<div class="modpress-image-preview%1$s"><img src="%2$s" alt="%3$s" class="img-fluid rounded" style="max-height:120px;" /></div>',
                    empty( $image_value ) || ! filter_var( $image_value, FILTER_VALIDATE_URL ) ? ' d-none' : '',
                    esc_url( $image_value ),
                    esc_attr( $custom_field['name'] ?? $field_slug )
                );

                $image_block .= sprintf(
                    '<div class="modpress-image-entry-mode modpress-image-entry-mode-url" data-image-mode="url"%1$s>%2$s%3$s</div>',
                    $url_mode_display,
                    $url_field,
                    $url_preview
                );
            }

            $image_field_html = sprintf(
                '<div class="modpress-image-field mb-2">%1$s%2$s</div>',
                $mode_html,
                $image_block
            );

            return sprintf(
                '<tr><th scope="row" class="align-top text-nowrap pe-4">%1$s</th><td>%2$s</td></tr>',
                $field_label,
                $image_field_html
            );
        }

        return '';
    }
}