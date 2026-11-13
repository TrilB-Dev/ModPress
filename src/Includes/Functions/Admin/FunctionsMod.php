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
use ModPress\Includes\Plugins\FontAwesome\Includes\Functions\Helpers\FAHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FunctionsMod {

	/**
	 * Register the WordPress admin-post actions used by the settings forms.
	 *
	 * @param LoaderHelper $loader Loader instance used to register hooks.
	 * @return void
     * @since 1.0.0
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
	 * Validate a save nonce for the current request.
	 *
	 * @param string $action Nonce action.
	 * @return void
     * @since 1.0.0
	 */
	private function validate_save_nonce( string $action, ?string $query_arg = null ): void {
		$nonce_name = $query_arg ?? ( $action . '_nonce' );
		check_admin_referer( $action, $nonce_name );
	}

	/**
	 * Normalize and sanitize the dependent entry payload for a mod.
	 *
	 * @param array<int, mixed> $dependents Raw dependent values from the form.
	 * @return array<int, array<string, scalar>>
	 */
	private function normalize_mod_dependents( array $dependents ): array {
		if ( empty( $dependents ) ) {
			return array();
		}

		$normalized = array();
		foreach ( $dependents as $dependent ) {
			if ( ! is_array( $dependent ) ) {
				continue;
			}

			$type = sanitize_key( (string) ( $dependent['type'] ?? 'internal' ) );
			if ( ! in_array( $type, array( 'internal', 'external' ), true ) ) {
				$type = 'internal';
			}

			$entry = array(
				'type' => $type,
			);

			if ( 'internal' === $type ) {
				$mod_id = absint( $dependent['mod_id'] ?? 0 );
				if ( $mod_id > 0 ) {
					$entry['mod_id'] = $mod_id;
				}
			} else {
				$external_url = isset( $dependent['external_url'] ) ? wp_unslash( $dependent['external_url'] ) : '';
				if ( is_string( $external_url ) ) {
					$external_url = trim( $external_url );
					if ( function_exists( 'esc_url_raw' ) ) {
						$external_url = esc_url_raw( $external_url );
					} elseif ( function_exists( 'sanitize_url' ) ) {
						$external_url = sanitize_url( $external_url );
					}
				}
				if ( '' !== $external_url ) {
					$entry['external_url'] = $external_url;
				}
			}

			$instructions = isset( $dependent['instructions'] ) ? SanitizationHelper::textarea( wp_unslash( $dependent['instructions'] ) ) : '';
			if ( '' !== $instructions ) {
				$entry['instructions'] = $instructions;
			}

			$has_valid_value = ( 'internal' === $type )
				? ( ! empty( $entry['mod_id'] ) || ! empty( $entry['instructions'] ) )
				: ( ! empty( $entry['external_url'] ) || ! empty( $entry['instructions'] ) );

			if ( ! $has_valid_value ) {
				continue;
			}

			$normalized[] = $entry;
		}

		return array_values( $normalized );
	}

	/**
	 * Normalize and sanitize the file payload for a mod.
	 *
	 * @param array<int, mixed> $files Raw file values from the form.
	 * @return array<int, array<string, scalar>>
	 */
	private function normalize_mod_files( array $files ): array {
		if ( empty( $files ) ) {
			return array();
		}

		$normalized = array();
		foreach ( $files as $file ) {
			if ( ! is_array( $file ) ) {
				continue;
			}

			$type = sanitize_key( (string) ( $file['type'] ?? 'internal' ) );
			if ( ! in_array( $type, array( 'internal', 'external' ), true ) ) {
				$type = 'internal';
			}

			$entry = array(
				'type' => $type,
			);

			if ( 'internal' === $type ) {
				$attachment_id = absint( $file['attachment_id'] ?? ( $file['file_id'] ?? 0 ) );
				$name = SanitizationHelper::text( wp_unslash( (string) ( $file['name'] ?? '' ) ), '' );
				$version = SanitizationHelper::text( wp_unslash( (string) ( $file['version'] ?? '' ) ), '' );
				$description = isset( $file['description'] ) ? SanitizationHelper::textarea( wp_unslash( (string) $file['description'] ) ) : '';
				if ( $attachment_id > 0 ) {
					$entry['attachment_id'] = $attachment_id;
				}
				if ( '' !== $name ) {
					$entry['name'] = $name;
				}
				if ( '' !== $version ) {
					$entry['version'] = $version;
				}
				if ( '' !== $description ) {
					$entry['description'] = $description;
				}
				if ( $attachment_id > 0 ) {
					$entry['download'] = (string) $attachment_id;
				}
			} else {
				$host_id = absint( $file['host_id'] ?? 0 );
				$host_url = isset( $file['host_url'] ) ? trim( (string) wp_unslash( $file['host_url'] ) ) : '';
				if ( '' === $host_url && $host_id > 0 && function_exists( 'get_term_meta' ) ) {
					$host_url = (string) get_term_meta( $host_id, 'url', true );
				}
				if ( is_string( $host_url ) ) {
					$host_url = trim( $host_url );
					if ( function_exists( 'esc_url_raw' ) ) {
						$host_url = esc_url_raw( $host_url );
					} elseif ( function_exists( 'sanitize_url' ) ) {
						$host_url = sanitize_url( $host_url );
					}
				}
				$url_suffix = isset( $file['url_suffix'] ) ? trim( (string) wp_unslash( $file['url_suffix'] ) ) : '';
				if ( '' !== $url_suffix ) {
					$url_suffix = ltrim( $url_suffix, '/' );
				}
				$description = isset( $file['description'] ) ? SanitizationHelper::textarea( wp_unslash( (string) $file['description'] ) ) : '';
				if ( $host_id > 0 ) {
					$entry['host_id'] = $host_id;
				}
				if ( '' !== $host_url ) {
					$entry['host_url'] = $host_url;
				}
				if ( '' !== $url_suffix ) {
					$entry['url_suffix'] = $url_suffix;
				}
				if ( '' !== $description ) {
					$entry['description'] = $description;
				}
				$download = '';
				if ( '' !== $host_url ) {
					$download = rtrim( $host_url, '/' );
					if ( '' !== $url_suffix ) {
						$download .= '/' . ltrim( $url_suffix, '/' );
					}
				} elseif ( '' !== $url_suffix ) {
					$download = $url_suffix;
				}
				if ( '' !== $download ) {
					$entry['download'] = $download;
				}
			}

			$has_valid_value = ( 'internal' === $type )
				? ( ! empty( $entry['attachment_id'] ) || ! empty( $entry['name'] ) || ! empty( $entry['version'] ) || ! empty( $entry['description'] ) )
				: ( ! empty( $entry['host_id'] ) || ! empty( $entry['host_url'] ) || ! empty( $entry['url_suffix'] ) || ! empty( $entry['description'] ) );

			if ( ! $has_valid_value ) {
				continue;
			}

			$normalized[] = $entry;
		}

		return array_values( $normalized );
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
			: ( isset( $_POST['action'] ) ? sanitize_key( wp_unslash( $_POST['action'] ) ) : '' );

		if ( ! in_array( $action, array( 'save_mod', 'modpress_save_mod' ), true ) ) {
			return '';
		}

		$should_redirect = false;
		if ( isset( $_POST['action'] ) ) {
			$requested_action = sanitize_key( wp_unslash( $_POST['action'] ) );
			$should_redirect = 'modpress_save_mod' === $requested_action;
		}

		if ( ! current_user_can( 'modpress_create' ) && ! current_user_can( 'manage_options' ) ) {
			$notice = __( 'You are not allowed to create Mods.', 'modpress' );
			if ( $should_redirect ) {
				$this->redirect_mod_action_result( 'mods', $notice, false );
			}
			return AlertHelper::get_admin_notice( $notice, 'error' );
		}

		$this->validate_save_nonce( 'modpress_save_mod', 'modpress_mod_nonce' );

		$title = isset( $_POST['post_title'] ) ? SanitizationHelper::text( wp_unslash( $_POST['post_title'] ) ) : '';
		$content = isset( $_POST['post_content'] ) ? wp_kses_post( wp_unslash( $_POST['post_content'] ) ) : '';
		$excerpt = isset( $_POST['post_excerpt'] ) ? SanitizationHelper::textarea( wp_unslash( $_POST['post_excerpt'] ) ) : '';
		$slug = isset( $_POST['post_name'] ) ? SanitizationHelper::slug( wp_unslash( $_POST['post_name'] ), SanitizationHelper::slug( $title, '' ) ) : SanitizationHelper::slug( $title, '' );
		$author = isset( $_POST['post_author'] ) ? absint( wp_unslash( $_POST['post_author'] ) ) : get_current_user_id();
		$status = isset( $_POST['post_status'] ) ? sanitize_key( wp_unslash( $_POST['post_status'] ) ) : 'publish';
		$status = '' !== $status ? $status : 'publish';
		$featured_image = isset( $_POST['featured_image'] ) ? absint( wp_unslash( $_POST['featured_image'] ) ) : 0;
		$mod_logo = isset( $_POST['mod_logo'] ) ? absint( wp_unslash( $_POST['mod_logo'] ) ) : 0;
		$mod_gallery_raw = isset( $_POST['mod_gallery'] ) ? wp_unslash( $_POST['mod_gallery'] ) : '';
		$mod_gallery = array();
		if ( is_array( $mod_gallery_raw ) ) {
			$mod_gallery = array_values( array_unique( array_filter( array_map( 'absint', $mod_gallery_raw ) ) ) );
		} elseif ( is_string( $mod_gallery_raw ) ) {
			$decoded_gallery = json_decode( $mod_gallery_raw, true );
			if ( is_array( $decoded_gallery ) ) {
				$mod_gallery = array_values( array_unique( array_filter( array_map( 'absint', $decoded_gallery ) ) ) );
			} else {
				$mod_gallery = array_values( array_unique( array_filter( array_map( 'absint', preg_split( '/[\s,]+/', trim( $mod_gallery_raw ) ) ) ) ) );
			}
		}
		$mod_id = isset( $_POST['mod_id'] ) ? absint( wp_unslash( $_POST['mod_id'] ) ) : 0;

		if ( '' === $title ) {
			$notice = __( 'A Mod title is required.', 'modpress' );
			if ( $should_redirect ) {
				$this->redirect_mod_action_result( 'mods', $notice, false );
			}
			return AlertHelper::get_admin_notice( $notice, 'error' );
		}

		$post_args = array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => $status,
			'post_author'  => $author,
			'post_type'    => 'modpress_mod',
		);
		if ( $mod_id > 0 ) {
			$post_args['ID'] = $mod_id;
		}
		if ( '' !== $slug ) {
			$post_args['post_name'] = $slug;
		}
		if ( '' !== $excerpt ) {
			$post_args['post_excerpt'] = $excerpt;
		}

		$post_id = wp_insert_post( $post_args, true );

		if ( is_wp_error( $post_id ) ) {
			$notice = __( 'The Mod could not be saved.', 'modpress' );
			if ( $should_redirect ) {
				$this->redirect_mod_action_result( 'mods', $notice, false );
			}
			return AlertHelper::get_admin_notice( $notice, 'error' );
		}

		if ( $featured_image > 0 ) {
			set_post_thumbnail( $post_id, $featured_image );
		}

		update_post_meta( $post_id, 'mod_logo', $mod_logo );
		update_post_meta( $post_id, 'mod_gallery', $mod_gallery );

		$mod_type_value = isset( $_POST['mod_type'] ) ? absint( wp_unslash( $_POST['mod_type'] ) ) : 0;
		if ( $mod_type_value > 0 && function_exists( 'wp_set_post_terms' ) ) {
			wp_set_post_terms( $post_id, array( $mod_type_value ), 'modpress_type', false );
		}

		$mod_status_value = isset( $_POST['mod_status'] ) ? absint( wp_unslash( $_POST['mod_status'] ) ) : 0;
		if ( $mod_status_value > 0 && function_exists( 'wp_set_post_terms' ) ) {
			wp_set_post_terms( $post_id, array( $mod_status_value ), 'modpress_mod_status', false );
		}

		$group_taxonomies = ( new GroupsManager() )->get_group_definitions();
		if ( ! empty( $group_taxonomies ) ) {
			foreach ( $group_taxonomies as $taxonomy_key => $group_definition ) {
				if ( 'modpress_type' === $taxonomy_key ) {
					continue;
				}

				$raw_value = isset( $_POST[ $taxonomy_key ] ) ? wp_unslash( $_POST[ $taxonomy_key ] ) : '';
				$term_values = array();
				if ( is_array( $raw_value ) ) {
					$term_values = array_map( 'absint', $raw_value );
				} else {
					$term_value = absint( $raw_value );
					if ( $term_value > 0 ) {
						$term_values[] = $term_value;
					}
				}

				if ( function_exists( 'wp_set_post_terms' ) ) {
					wp_set_post_terms( $post_id, $term_values, $taxonomy_key, false );
				}
			}
		}

		$dependents = isset( $_POST['dependents'] ) && is_array( $_POST['dependents'] ) ? array_map( 'wp_unslash', $_POST['dependents'] ) : array();
		$normalized_dependents = $this->normalize_mod_dependents( $dependents );
		if ( ! empty( $normalized_dependents ) || $mod_id > 0 ) {
			update_post_meta( $post_id, '_modpress_dependents', $normalized_dependents );
		}

		$files = isset( $_POST['files'] ) && is_array( $_POST['files'] ) ? array_map( 'wp_unslash', $_POST['files'] ) : array();
		$normalized_files = $this->normalize_mod_files( $files );
		if ( ! empty( $normalized_files ) || $mod_id > 0 ) {
			update_post_meta( $post_id, '_modpress_files', $normalized_files );
		}

		$notice = __( 'Mod saved successfully.', 'modpress' );
		if ( $should_redirect ) {
			$this->redirect_mod_action_result( 'mods', $notice, true );
		}
		return AlertHelper::get_admin_notice( $notice, 'success' );
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
        $group_key  = GroupsManager::get_group_key( $taxonomy );

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
        $group_key  = trim( (string) ( $group_row['group_key'] ?? GroupsManager::get_group_key( $group_slug ) ) );
        if ( 'modpress_type' === $group_key || 'type' === $group_slug || 'modpress_type' === GroupsManager::get_group_key( $group_slug ) ) {
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
        $switch_values = RequestHelper::value( $_POST, 'custom_field_type_switch', array() );
        $switch_custom_values = RequestHelper::value( $_POST, 'custom_field_type_switch_custom', array() );
        $color_use_values = RequestHelper::value( $_POST, 'custom_field_type_color', false );
        $radio_options = RequestHelper::value( $_POST, 'custom_field_type_radio', array() );
        $select_options = RequestHelper::value( $_POST, 'custom_field_type_select', array() );
        $image_types = RequestHelper::value( $_POST, 'custom_field_type_image_type', array() );
        $image_type_image = RequestHelper::value( $_POST, 'custom_field_type_image_type_image', array() );
        $image_type_icon = RequestHelper::value( $_POST, 'custom_field_type_image_type_icon', array() );
        $image_type_url = RequestHelper::value( $_POST, 'custom_field_type_image_type_url', array() );
        $show_in_table = RequestHelper::value( $_POST, 'custom_field_show_in_table', array() );

        if ( is_array( $field_names ) ) {
            $resolve_field_value = static function ( $source, $field_index, $field_key, $field_id, $fallback = null ) {
                if ( ! is_array( $source ) ) {
                    return $fallback;
                }

                $candidates = array();
                if ( is_numeric( $field_index ) ) {
                    $candidates[] = (int) $field_index;
                }
                if ( '' !== (string) $field_key ) {
                    $candidates[] = (string) $field_key;
                }
                if ( '' !== (string) $field_id ) {
                    $candidates[] = (string) $field_id;
                }

                foreach ( $candidates as $candidate ) {
                    if ( array_key_exists( $candidate, $source ) ) {
                        return $source[ $candidate ];
                    }
                }

                if ( is_numeric( $field_index ) ) {
                    $numeric_key = (string) (int) $field_index;
                    if ( array_key_exists( $numeric_key, $source ) ) {
                        return $source[ $numeric_key ];
                    }
                }

                return $fallback;
            };

            foreach ( $field_names as $index => $field_name ) {
                $name = trim( (string) $field_name );
                if ( '' === $name ) {
                    continue;
                }

                $field_index = is_numeric( $index ) ? (int) $index : 0;
                $field_key   = is_string( $index ) ? (string) $index : (string) $field_index;
                $field_id    = trim( (string) ( $field_ids[ $field_index ] ?? $field_ids[ $field_key ] ?? $field_ids[ $index ] ?? '' ) );
                if ( '' === $field_id ) {
                    $field_id = 'field_' . ( $field_index + 1 );
                }

                $slug        = trim( (string) ( $resolve_field_value( $field_slugs, $field_index, $field_key, $field_id, SanitizationHelper::slug( $name, '' ) ) ?? SanitizationHelper::slug( $name, '' ) ) );
                $field_type  = trim( (string) ( $resolve_field_value( $field_types, $field_index, $field_key, $field_id, 'text' ) ?? 'text' ) );
                $show_in_table_value = $resolve_field_value( $show_in_table, $field_index, $field_key, $field_id, '' );
                $field       = array(
                    'id'          => $field_id,
                    'name'        => $name,
                    'slug'        => $slug,
                    'type'        => $field_type,
                    'description' => trim( (string) ( $resolve_field_value( $field_descs, $field_index, $field_key, $field_id, '' ) ?? '' ) ),
                    'show_in_table' => is_bool( $show_in_table_value )
                        ? $show_in_table_value
                        : in_array( strtolower( trim( (string) $show_in_table_value ) ), array( '1', 'on', 'true', 'yes' ), true ),
                );

                if ( 'textarea' === $field_type ) {
                    $rows = abs( (int) ( $resolve_field_value( $textarea_rows, $field_index, $field_key, $field_id, 4 ) ?? 4 ) );
                    if ( $rows > 0 ) {
                        $field['rows'] = $rows;
                    }
                }

                if ( 'switch' === $field_type ) {
                    $switch_value = $resolve_field_value( $switch_values, $field_index, $field_key, $field_id, true );
                    $custom_switch_values = $resolve_field_value( $switch_custom_values, $field_index, $field_key, $field_id, '' );

                    $normalized_switch_value = is_bool( $switch_value )
                        ? ( $switch_value ? 'true' : 'false' )
                        : strtolower( trim( (string) $switch_value ) );

                    if ( '' === $normalized_switch_value ) {
                        $normalized_switch_value = 'true';
                    }

                    if ( in_array( $normalized_switch_value, array( '1', 'on', 'true', 'yes', 'checked' ), true ) ) {
                        $normalized_switch_value = 'true';
                    } elseif ( in_array( $normalized_switch_value, array( '0', 'off', 'false', 'no', 'unchecked' ), true ) ) {
                        $normalized_switch_value = 'false';
                    }

                    $field['switch'] = $normalized_switch_value;

                    if ( is_array( $custom_switch_values ) ) {
                        $field['switch_custom'] = array_values(
                            array_filter(
                                array_map(
                                    static fn ( $value ) => trim( (string) $value ),
                                    $custom_switch_values
                                ),
                                static fn ( $value ) => '' !== $value
                            )
                        );
                    } elseif ( is_scalar( $custom_switch_values ) ) {
                        $parsed = array_map(
                            static fn ( $part ) => trim( (string) $part ),
                            preg_split( '/\s*,\s*/', (string) $custom_switch_values )
                        );
                        $field['switch_custom'] = array_values(
                            array_filter( $parsed, static fn ( $value ) => '' !== $value )
                        );
                    }
                }

                if ( 'color' === $field_type ) {
                    $raw_color_value = $resolve_field_value( $color_use_values, $field_index, $field_key, $field_id, false );
                    $field['custom_color_use'] = is_bool( $raw_color_value )
                        ? $raw_color_value
                        : in_array( strtolower( trim( (string) $raw_color_value ) ), array( '1', 'on', 'true', 'yes', 'checked' ), true );
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
        $resolved_group_key = GroupsManager::get_group_key( $taxonomy );
        $description = SanitizationHelper::textarea( RequestHelper::value( $_POST, 'group_description', '' ), '' );

        $group_payload = array(
            'group_key'           => $resolved_group_key,
            'group_name'          => $group_name,
            'group_slug'          => $taxonomy,
            'group_singular_name' => RequestHelper::text( $_POST, 'group_singular_name', $group_name ),
            'group_description'   => $description,
            'group_hierarchical'  => RequestHelper::boolean( $_POST, 'group_hierarchical', false ),
            'group_multiselect'   => RequestHelper::boolean( $_POST, 'group_multiselect', false ),
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
                    'group_multiselect'   => $group_payload['group_multiselect'] ?? false,
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
                    'group_multiselect'   => $group_payload['group_multiselect'] ?? false,
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
                'group_multiselect'   => $group_payload['group_multiselect'] ?? false,
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

                $field_type = strtolower( trim( (string) ( $field['type'] ?? 'text' ) ) );
                if ( 'image' === $field_type ) {
                    delete_term_meta( $saved_term_id, $field_slug );

                    $image_meta_keys = array(
                        'image' => $field_slug . '_image',
                        'icon'  => $field_slug . '_icon',
                        'url'   => $field_slug . '_url',
                    );

                    foreach ( $image_meta_keys as $meta_key ) {
                        $meta_value = RequestHelper::value( $_POST, $meta_key, '' );
                        if ( is_array( $meta_value ) ) {
                            $meta_value = array_values(
                                array_map(
                                    static function ( $item ) {
                                        return is_scalar( $item ) ? sanitize_text_field( wp_unslash( (string) $item ) ) : '';
                                    },
                                    wp_unslash( $meta_value )
                                )
                            );
                            $meta_value = $meta_value[0] ?? '';
                        } else {
                            $meta_value = sanitize_text_field( wp_unslash( (string) $meta_value ) );
                        }

                        if ( '' === trim( (string) $meta_value ) ) {
                            delete_term_meta( $saved_term_id, $meta_key );
                            continue;
                        }

                        update_term_meta( $saved_term_id, $meta_key, $meta_value );
                    }

                    continue;
                }

                $field_value = null;
                if ( array_key_exists( $field_slug, $_POST ) ) {
                    $field_value = RequestHelper::value( $_POST, $field_slug, null );
                } elseif ( array_key_exists( $field_slug . '[]', $_POST ) ) {
                    $field_value = RequestHelper::value( $_POST, $field_slug . '[]', null );
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
                    $field_value = array_values(
                        array_filter(
                            $field_value,
                            static function ( $value ) {
                                return '' !== trim( (string) $value );
                            }
                        )
                    );
                    if ( empty( $field_value ) ) {
                        delete_term_meta( $saved_term_id, $field_slug );
                        continue;
                    }
                } elseif ( '' === trim( (string) $field_value ) ) {
                    delete_term_meta( $saved_term_id, $field_slug );
                    continue;
                }

                delete_term_meta( $saved_term_id, $field_slug );
                update_term_meta( $saved_term_id, $field_slug, $field_value );
            }
        }

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
     * Render a group custom field.
     *
     * @param array $custom_field The custom field configuration.
     * @param array $value The current value of the custom field.
     * @return string HTML output.
     * @since 1.0.0
     */
    public static function render_group_custom_field( array $custom_field, $value = array(), array $group_fields = array() ) {
        if ( ! is_array( $custom_field ) ) {
            return '';
        }

        $field_slug = trim( (string) ( $custom_field['slug'] ?? $custom_field['name'] ?? '' ) );
        if ( '' === $field_slug ) {
            return '';
        }

        if ( ! empty( $group_fields ) ) {
            foreach ( $group_fields as $candidate_field ) {
                if ( ! is_array( $candidate_field ) ) {
                    continue;
                }

                $candidate_slug = trim( (string) ( $candidate_field['slug'] ?? $candidate_field['name'] ?? '' ) );
                $candidate_id = trim( (string) ( $candidate_field['id'] ?? '' ) );
                $current_id = trim( (string) ( $custom_field['id'] ?? '' ) );

                if ( '' !== $candidate_slug && $candidate_slug === $field_slug ) {
                    $custom_field = array_merge( $custom_field, $candidate_field );
                    break;
                }

                if ( '' !== $current_id && '' !== $candidate_id && $candidate_id === $current_id ) {
                    $custom_field = array_merge( $custom_field, $candidate_field );
                    break;
                }
            }
        }

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

        $resolve_field_value = static function ( string $field_name_key ) use ( $value, $field_slug, $normalize_saved_value ) {
            $keys = array(
                $field_name_key,
                $field_name_key . '[]',
                $field_name_key . '[0]',
                $field_slug,
                $field_slug . '[]',
                $field_slug . '[0]',
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

        $field_value = $resolve_field_value( $field_slug );
        $unwrap_single_meta_value = static function ( $raw_value ) {
            if ( ! is_array( $raw_value ) ) {
                return $raw_value;
            }

            $flattened = array();
            foreach ( $raw_value as $item ) {
                if ( is_array( $item ) ) {
                    foreach ( (array) $item as $nested_item ) {
                        $trimmed = trim( (string) $nested_item );
                        if ( '' !== $trimmed ) {
                            $flattened[] = $trimmed;
                        }
                    }
                    continue;
                }

                $trimmed = trim( (string) $item );
                if ( '' !== $trimmed ) {
                    $flattened[] = $trimmed;
                }
            }

            if ( 1 === count( $flattened ) ) {
                return $flattened[0];
            }

            return array_values( array_unique( $flattened ) );
        };
        $field_value = $unwrap_single_meta_value( $field_value );

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
            $field_slug,
            $custom_field['name'] ?? $field_slug,
            array(
                'class' => 'form-label',
                'description' => $custom_field['description'] ?? '',
            )
        );

        $scalar_field_value = is_array( $field_value ) ? (string) ( $field_value[0] ?? '' ) : (string) $field_value;

        if ( 'text' === $field_type ) {
            return sprintf(
                '<tr><th scope="row" class="align-top text-nowrap pe-4">%1$s</th><td>%2$s</td></tr>',
                $field_label,
                FormFieldHelper::text_input(
                    $field_slug,
                    $scalar_field_value,
                    array(
                        'class' => 'form-control w-100',
                        'placeholder' => $custom_field['name'] ?? $field_slug,
                        'attributes' => array(
                            'data-modpress-field-key' => $field_slug,
                        ),
                    )
                )
            );
        }

        if ( 'textarea' === $field_type ) {
            return sprintf(
                '<tr><th scope="row" class="align-top text-nowrap pe-4">%1$s</th><td>%2$s</td></tr>',
                $field_label,
                FormFieldHelper::textarea(
                    $field_slug,
                    $scalar_field_value,
                    array(
                        'class' => 'form-control w-100',
                        'rows' => absint( $custom_field['rows'] ?? 4 ),
                        'placeholder' => $custom_field['name'] ?? $field_slug,
                        'attributes' => array(
                            'data-modpress-field-key' => $field_slug,
                        ),
                    )
                )
            );
        }

        if ( 'number' === $field_type ) {
            return sprintf(
                '<tr><th scope="row" class="align-top text-nowrap pe-4">%1$s</th><td>%2$s</td></tr>',
                $field_label,
                FormFieldHelper::input(
                    $field_slug,
                    $scalar_field_value,
                    array(
                        'type' => 'number',
                        'class' => 'form-control w-100',
                        'placeholder' => $custom_field['name'] ?? $field_slug,
                        'attributes' => array(
                            'data-modpress-field-key' => $field_slug,
                        ),
                    )
                )
            );
        }

        if ( 'color' === $field_type ) {
            return sprintf(
                '<tr><th scope="row" class="align-top text-nowrap pe-4">%1$s</th><td>%2$s</td></tr>',
                $field_label,
                FormFieldHelper::input(
                    $field_slug,
                    $scalar_field_value,
                    array(
                        'type' => 'color',
                        'class' => 'form-control form-control-color w-100',
                        'attributes' => array(
                            'data-modpress-field-key' => $field_slug,
                        ),
                    )
                )
            );
        }

        if ( 'switch' === $field_type ) {
            $switch_value = is_array( $field_value ) ? ( $field_value[0] ?? '' ) : $field_value;
            return sprintf(
                '<tr><th scope="row" class="align-top text-nowrap pe-4">%1$s</th><td>%2$s</td></tr>',
                $field_label,
                FormFieldHelper::switch(
                    $field_slug,
                    true,
                    '',
                    array(
                        'checked' => ! empty( $switch_value ) && ( true === (string) $switch_value || true === $switch_value || 'true' === strtolower( (string) $switch_value ) || 'on' === strtolower( (string) $switch_value ) || 'yes' === strtolower( (string) $switch_value ) ),
                        'attributes' => array(
                            'data-modpress-field-key' => $field_slug,
                        ),
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
                    $field_slug,
                    $option_rows,
                    $selected_values,
                    array(
                        'type' => 'radio',
                        'attributes' => array(
                            'data-modpress-field-key' => $field_slug,
                        ),
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
                    $field_slug,
                    array(
                        'data' => $option_rows,
                        'selected' => $selected_value,
                        'class' => 'w-100',
                        'attributes' => array(
                            'data-modpress-field-key' => $field_slug,
                        ),
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

            $image_values = array(
                'image' => $resolve_field_value( $field_slug . '_image' ),
                'icon'  => $resolve_field_value( $field_slug . '_icon' ),
                'url'   => $resolve_field_value( $field_slug . '_url' ),
            );

            $image_value_string = static function ( $value ) {
                if ( is_array( $value ) ) {
                    $flattened = array();
                    foreach ( $value as $item ) {
                        $normalized = is_array( $item ) ? $item : array( $item );
                        foreach ( $normalized as $nested_item ) {
                            $trimmed = trim( (string) $nested_item );
                            if ( '' !== $trimmed ) {
                                $flattened[] = $trimmed;
                            }
                        }
                    }

                    return $flattened[0] ?? '';
                }

                return trim( (string) $value );
            };

            $selected_mode = $allowed_modes[0];
            foreach ( $allowed_modes as $mode ) {
                $variant_value = $image_value_string( $image_values[ $mode ] ?? '' );
                if ( '' !== $variant_value ) {
                    $selected_mode = $mode;
                    break;
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
                            'data-input-name' => $field_slug,
                        ),
                    );
                }

                $mode_html = FormFieldHelper::button_group(
                    $field_slug . '_mode',
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
                $image_value = is_array( $image_values['image'] ) ? (string) ( $image_values['image'][0] ?? '' ) : (string) ( $image_values['image'] ?? '' );
                $image_block .= sprintf(
                    '<div class="modpress-image-entry-mode modpress-image-entry-mode-image" data-image-mode="image"%1$s>%2$s</div>',
                    'image' === $selected_mode ? '' : ' style="display:none;"',
                    WPMediaHelper::create(
                        $image_value,
                        array(
                            'name' => sanitize_key( $field_slug . '_image' ),
                            'id' => sanitize_key( $field_slug . '_image' ),
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
                $icon_value = is_array( $image_values['icon'] ) ? (string) ( $image_values['icon'][0] ?? '' ) : (string) ( $image_values['icon'] ?? '' );
                $image_block .= sprintf(
                    '<div class="modpress-image-entry-mode modpress-image-entry-mode-icon" data-image-mode="icon"%1$s>%2$s</div>',
                    'icon' === $selected_mode ? '' : ' style="display:none;"',
                    IconPicker::get_instance()->render_picker(
                        sanitize_key( $field_slug . '_icon' ),
                        $icon_value,
                        array(
                            'id' => sanitize_key( $field_slug . '-picker' ),
                            'button_text' => __( 'Choose Icon', 'modpress' ),
                        )
                    )
                );
            }

            if ( in_array( 'url', $allowed_modes, true ) ) {
                $url_value = is_array( $image_values['url'] ) ? (string) ( $image_values['url'][0] ?? '' ) : (string) ( $image_values['url'] ?? '' );
                $url_mode_display = 'url' === $selected_mode ? '' : ' style="display:none;"';
                $url_field = FormFieldHelper::input(
                    sanitize_key( $field_slug . '_url' ),
                    $url_value,
                    array(
                        'type' => 'url',
                        'class' => 'form-control modpress-image-url-input w-100',
                        'placeholder' => __( 'https://example.com/image.jpg', 'modpress' ),
                    )
                );
                $url_preview = sprintf(
                    '<div class="modpress-image-preview%1$s"><img src="%2$s" alt="%3$s" class="img-fluid rounded" style="max-height:120px;" /></div>',
                    empty( $url_value ) || ! filter_var( $url_value, FILTER_VALIDATE_URL ) ? ' d-none' : '',
                    esc_url( $url_value ),
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
    /**
     * Redirect back to the Mod Groups screen with a notice.
     *
     * @param bool   $success Whether the save succeeded.
     * @param string $message Notice text.
     * @return void
     * @since 1.0.0
     */
    private function redirect_mod_group_result( bool $success, string $message = '' ): void {
        $this->redirect_mod_action_result( 'groups', $message, $success );
    }
    /**
     * Render a repeatable option row template.
     *
     * @param string $type The option type.
     * @return string HTML output.
     * @since 1.0.0
     */
    public static function repeatable_option_row(string $type): string {
        ob_start();
        ?>
        <div class="modpress-option-repeater" data-option-type="<?php echo esc_attr( $type ); ?>">
            <div class="d-flex justify-content-end mb-2">
                <?php echo FormFieldHelper::button(
                    FAHelper::faicon( 'plus', array( 'class' => 'fa-svg-symbol' ) ).' '.esc_html__( 'Add Option', 'modpress' ),
                    array(
                        'type' => 'button',
                        'class' => 'btn btn-outline-secondary btn-sm',
                        'id' => 'add-option-row',
                        'raw' => true,
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
                                FAHelper::faicon( 'trash', array( 'class' => 'fa-svg-symbol' ) ),
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
     * Save or update a taxonomy term and its structured metadata.
     *
     * @param string $taxonomy Taxonomy slug.
     * @param array  $payload Term payload.
     * @return int Number of the saved term.
     * @since 1.0.0
     */
    private function persist_taxonomy_term( string $taxonomy, array $payload ): int {
        $taxonomy = sanitize_key( $taxonomy );
        if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
            return 0;
        }

        $name = trim( (string) ( $payload['name'] ?? '' ) );
        if ( '' === $name ) {
            return 0;
        }

        $slug = trim( (string) ( $payload['slug'] ?? '' ) );
        if ( '' === $slug ) {
            $slug = SanitizationHelper::slug( $name, '' );
        }

        $description = isset( $payload['description'] ) ? SanitizationHelper::textarea( (string) $payload['description'], '' ) : '';
        $term_id = absint( $payload['term_id'] ?? 0 );
        $parent = absint( $payload['parent'] ?? 0 );

        $result = $term_id > 0
            ? wp_update_term( $term_id, $taxonomy, array(
                'name'        => $name,
                'slug'        => $slug,
                'description' => $description,
                'parent'      => $parent,
            ) )
            : wp_insert_term( $name, $taxonomy, array(
                'slug'        => $slug,
                'description' => $description,
                'parent'      => $parent,
            ) );

        if ( is_wp_error( $result ) || ! is_array( $result ) ) {
            return 0;
        }

        $saved_term_id = absint( $result['term_id'] ?? 0 );
        if ( $saved_term_id <= 0 ) {
            return 0;
        }

        $sanitize_taxonomy_meta_value = static function ( $raw_value, string $meta_key ) {
            if ( ! is_scalar( $raw_value ) ) {
                return '';
            }

            $clean_value = function_exists( 'wp_unslash' ) ? wp_unslash( (string) $raw_value ) : (string) $raw_value;

            if ( 'url' === $meta_key || 'image' === $meta_key ) {
                if ( function_exists( '\\esc_url_raw' ) ) {
                    return \esc_url_raw( $clean_value );
                }

                $fallback_url = filter_var( $clean_value, FILTER_SANITIZE_URL );
                return is_string( $fallback_url ) ? $fallback_url : '';
            }

            if ( function_exists( '\\sanitize_text_field' ) ) {
                return \sanitize_text_field( $clean_value );
            }

            return trim( strip_tags( $clean_value ) );
        };

        foreach ( array( 'image', 'icon', 'url', 'category_color' ) as $meta_key ) {
            $raw_value = isset( $payload[ $meta_key ] ) ? $payload[ $meta_key ] : '';

            if ( is_array( $raw_value ) ) {
                $raw_value = array_values(
                    array_filter(
                        array_map(
                            static function ( $value ) {
                                if ( is_scalar( $value ) ) {
                                    return function_exists( 'wp_unslash' ) ? wp_unslash( (string) $value ) : (string) $value;
                                }
                                return '';
                            },
                            $raw_value
                        ),
                        static function ( $value ) {
                            return '' !== trim( (string) $value );
                        }
                    )
                );
                $raw_value = $raw_value[0] ?? '';
            }

            if ( 'category_color' === $meta_key ) {
                $clean_value = function_exists( 'wp_unslash' ) ? wp_unslash( (string) $raw_value ) : (string) $raw_value;
                $clean_value = preg_replace( '/[^#a-fA-F0-9]/', '', $clean_value );
                $value = '' !== trim( $clean_value ) ? '#' . ltrim( $clean_value, '#' ) : '';
                if ( ! preg_match( '/^#[0-9a-fA-F]{3,8}$/', $value ) ) {
                    $value = '';
                }
            } else {
                $value = $sanitize_taxonomy_meta_value( $raw_value, $meta_key );
            }

            if ( '' === trim( (string) $value ) ) {
                delete_term_meta( $saved_term_id, $meta_key );
                continue;
            }

            delete_term_meta( $saved_term_id, $meta_key );
            update_term_meta( $saved_term_id, $meta_key, $value );
        }

        return $saved_term_id;
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
    public function save_file(): void {
        if ( ! current_user_can( 'modpress_manager_files_edit' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage ModPress files.', 'modpress' ) );
        }

        check_admin_referer( 'modpress_save_files' );
        $this->redirect_mod_action_result( 'files', __( 'Files saved successfully.', 'modpress' ), true );
    }
    /**
     * Handle a file delete request.
     *
     * @return void
     * @since 1.0.0
     */
    public function delete_file(): void {
        if ( ! current_user_can( 'modpress_manager_files_edit' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage ModPress files.', 'modpress' ) );
        }

        check_admin_referer( 'modpress_delete_file' );
        $this->redirect_mod_action_result( 'files', __( 'File deleted successfully.', 'modpress' ), true );
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

        check_admin_referer( 'modpress_save_file_type', 'modpress_file_type_nonce' );

        $taxonomy = RequestHelper::key( $_POST, 'file_type_slug', 'modpress_file_type' );
        if ( '' === $taxonomy ) {
            $taxonomy = 'modpress_file_type';
        }

        $payload = array(
            'term_id'     => absint( $_POST['modpress-file-types-term-id'] ?? 0 ),
            'name'        => RequestHelper::text( $_POST, 'modpress-file-types-name', '' ),
            'slug'        => RequestHelper::slug( $_POST, 'modpress-file-types-slug', '' ),
            'description' => RequestHelper::text( $_POST, 'modpress-file-types-description', '' ),
            'image'       => RequestHelper::text( $_POST, 'modpress-file-types-image', '' ),
        );

        $saved_term_id = $this->persist_taxonomy_term( $taxonomy, $payload );
        if ( $saved_term_id <= 0 ) {
            $this->redirect_mod_action_result( 'files', __( 'Unable to save the selected file type.', 'modpress' ), false );
        }

        $this->redirect_mod_action_result( 'files', __( 'File type saved successfully.', 'modpress' ), true );
    }
    /**
     * Handle a file type delete request.
     *
     * @return void
     * @since 1.0.0
     */
    public function delete_file_type(): void {
        if ( ! current_user_can( 'modpress_manager_files_edit' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage ModPress file types.', 'modpress' ) );
        }

        check_admin_referer( 'modpress_delete_file_type', 'modpress_file_type_nonce' );

        $taxonomy = RequestHelper::key( $_POST, 'file_type_slug', 'modpress_file_type' );
        $requested_ids = RequestHelper::value( $_POST, 'file_type_ids', array() );
        if ( ! is_array( $requested_ids ) ) {
            $requested_ids = array( $requested_ids );
        }

        $term_ids = array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn ( $value ) => absint( $value ),
                        $requested_ids
                    ),
                    static fn ( $value ) => $value > 0
                )
            )
        );

        if ( empty( $term_ids ) ) {
            $this->redirect_mod_action_result( 'files', __( 'No file type was selected to delete.', 'modpress' ), false );
        }

        foreach ( $term_ids as $term_id ) {
            wp_delete_term( $term_id, $taxonomy );
        }

        $this->redirect_mod_action_result( 'files', __( 'File type deleted successfully.', 'modpress' ), true );
    }
    /**
     * Handle a file host save request.
     *
     * @return void
     * @since 1.0.0
     */
    public function save_file_host(): void {
        if ( ! current_user_can( 'modpress_manager_files_edit' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage ModPress file hosts.', 'modpress' ) );
        }

        check_admin_referer( 'modpress_save_file_host', 'modpress_file_host_nonce' );

        $taxonomy = RequestHelper::key( $_POST, 'file_host_slug', 'modpress_file_hosts' );
        if ( '' === $taxonomy ) {
            $taxonomy = 'modpress_file_hosts';
        }

        $payload = array(
            'term_id'     => absint( $_POST['modpress-file-hosts-term-id'] ?? 0 ),
            'name'        => RequestHelper::text( $_POST, 'modpress-file-hosts-name', '' ),
            'slug'        => RequestHelper::slug( $_POST, 'modpress-file-hosts-slug', '' ),
            'description' => RequestHelper::text( $_POST, 'modpress-file-hosts-description', '' ),
            'image'       => RequestHelper::text( $_POST, 'modpress-file-hosts-logo', '' ),
            'url'         => RequestHelper::text( $_POST, 'modpress-file-hosts-url', '' ),
        );

        $saved_term_id = $this->persist_taxonomy_term( $taxonomy, $payload );
        if ( $saved_term_id <= 0 ) {
            $this->redirect_mod_action_result( 'files', __( 'Unable to save the selected file host.', 'modpress' ), false );
        }

        $this->redirect_mod_action_result( 'files', __( 'File host saved successfully.', 'modpress' ), true );
    }
    /**
     * Handle a file host delete request.
     *
     * @return void
     * @since 1.0.0
     */
    public function delete_file_host(): void {
        if ( ! current_user_can( 'modpress_manager_files_edit' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage ModPress file hosts.', 'modpress' ) );
        }

        check_admin_referer( 'modpress_delete_file_host', 'modpress_file_host_nonce' );

        $taxonomy = RequestHelper::key( $_POST, 'file_host_slug', 'modpress_file_hosts' );
        $requested_ids = RequestHelper::value( $_POST, 'file_host_ids', array() );
        if ( ! is_array( $requested_ids ) ) {
            $requested_ids = array( $requested_ids );
        }

        $term_ids = array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn ( $value ) => absint( $value ),
                        $requested_ids
                    ),
                    static fn ( $value ) => $value > 0
                )
            )
        );

        if ( empty( $term_ids ) ) {
            $this->redirect_mod_action_result( 'files', __( 'No file host was selected to delete.', 'modpress' ), false );
        }

        foreach ( $term_ids as $term_id ) {
            wp_delete_term( $term_id, $taxonomy );
        }

        $this->redirect_mod_action_result( 'files', __( 'File host deleted successfully.', 'modpress' ), true );
    }
    /**
     * Handle a licence save request.
     *
     * @return void
     * @since 1.0.0
     */
    public function save_licence(): void {
        if ( ! current_user_can( 'modpress_manager_licences_edit' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage ModPress licences.', 'modpress' ) );
        }

        check_admin_referer( 'modpress_save_licence' );
        $this->redirect_mod_action_result( 'licences', __( 'Licence saved successfully.', 'modpress' ), true );
    }
    /**
     * Handle a licence delete request.
     *
     * @return void
     * @since 1.0.0
     */
    public function delete_licence(): void {
        if ( ! current_user_can( 'modpress_manager_licences_edit' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage ModPress licences.', 'modpress' ) );
        }

        check_admin_referer( 'modpress_delete_licence' );
        $this->redirect_mod_action_result( 'licences', __( 'Licence deleted successfully.', 'modpress' ), true );
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

        check_admin_referer( 'modpress_save_changelog', 'modpress_changelog_nonce' );

        $post_id = absint( RequestHelper::value( $_POST, 'modpress-changelog-id', 0 ) );
        $version  = RequestHelper::text( $_POST, 'modpress-changelog-version', '' );
        if ( '' === $version ) {
            $version = __( 'Untitled changelog', 'modpress' );
        }

        $date = RequestHelper::text( $_POST, 'modpress-changelog-date', '' );
        $notes = RequestHelper::text( $_POST, 'modpress-changelog-notes', '' );

        $post_data = array(
            'post_type'    => 'modpress_changelog',
            'post_status'  => 'publish',
            'post_title'   => $version,
            'post_content' => $notes,
        );

        if ( $post_id > 0 ) {
            $post_data['ID'] = $post_id;
        }

        if ( '' !== $date ) {
            $post_data['post_date'] = $date . ' 00:00:00';
            $post_data['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', strtotime( $date . ' 00:00:00' ) );
        }

        if ( $post_id > 0 && function_exists( 'wp_update_post' ) ) {
            $saved_post_id = wp_update_post( $post_data, true );
        } elseif ( function_exists( 'wp_insert_post' ) ) {
            $saved_post_id = wp_insert_post( $post_data, true );
        } else {
            $saved_post_id = $post_id > 0 ? $post_id : 1;
        }

        if ( is_wp_error( $saved_post_id ) || 0 === absint( $saved_post_id ) ) {
            $this->redirect_mod_action_result( 'changelog', __( 'Unable to save the selected changelog entry.', 'modpress' ), false );
        }

        $category_ids = RequestHelper::value( $_POST, 'modpress-changelog-category', array() );
        if ( ! is_array( $category_ids ) ) {
            $category_ids = array( $category_ids );
        }

        $category_ids = array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn ( $value ) => absint( $value ),
                        $category_ids
                    ),
                    static fn ( $value ) => $value > 0
                )
            )
        );

        if ( taxonomy_exists( 'modpress_changelog_category' ) && function_exists( 'wp_set_object_terms' ) ) {
            wp_set_object_terms( (int) $saved_post_id, $category_ids, 'modpress_changelog_category', false );
        }

        $this->redirect_mod_action_result( 'changelog', __( 'Changelog saved successfully.', 'modpress' ), true );
    }

    /**
     * Handle a changelog delete request.
     *
     * @return void
     * @since 1.0.0
     */
    public function delete_changelog(): void {
        if ( ! current_user_can( 'modpress_manager_changelog_edit' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage ModPress changelogs.', 'modpress' ) );
        }

        check_admin_referer( 'modpress_delete_changelog', 'modpress_changelog_nonce' );

        $requested_ids = RequestHelper::value( $_POST, 'changelog_ids', array() );
        if ( ! is_array( $requested_ids ) ) {
            $requested_ids = array( $requested_ids );
        }

        $post_ids = array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn ( $value ) => absint( $value ),
                        $requested_ids
                    ),
                    static fn ( $value ) => $value > 0
                )
            )
        );

        if ( empty( $post_ids ) ) {
            $this->redirect_mod_action_result( 'changelog', __( 'No changelog entry was selected to delete.', 'modpress' ), false );
        }

        foreach ( $post_ids as $post_id ) {
            if ( function_exists( 'wp_delete_post' ) ) {
                wp_delete_post( $post_id, true );
            }
        }

        $this->redirect_mod_action_result( 'changelog', __( 'Changelog deleted successfully.', 'modpress' ), true );
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

        check_admin_referer( 'modpress_save_changelog_category', 'modpress_changelog_category_nonce' );

        $taxonomy = RequestHelper::key( $_POST, 'changelog_category_slug', 'modpress_changelog_category' );
        if ( '' === $taxonomy ) {
            $taxonomy = 'modpress_changelog_category';
        }

        $payload = array(
            'term_id'        => absint( RequestHelper::value( $_POST, 'modpress-changelog-category-term-id', 0 ) ),
            'name'           => RequestHelper::text( $_POST, 'modpress-changelog-category-name', '' ),
            'slug'           => RequestHelper::slug( $_POST, 'modpress-changelog-category-slug', '' ),
            'description'    => RequestHelper::text( $_POST, 'modpress-changelog-category-description', '' ),
            'category_color' => RequestHelper::text( $_POST, 'modpress-changelog-category-color', '#ffffff' ),
        );

        $saved_term_id = $this->persist_taxonomy_term( $taxonomy, $payload );
        if ( $saved_term_id <= 0 ) {
            $this->redirect_mod_action_result( 'changelog', __( 'Unable to save the selected changelog category.', 'modpress' ), false );
        }

        $this->redirect_mod_action_result( 'changelog', __( 'Changelog category saved successfully.', 'modpress' ), true );
    }

    /**
     * Handle a changelog category delete request.
     *
     * @return void
     * @since 1.0.0
     */
    public function delete_changelog_category(): void {
        if ( ! current_user_can( 'modpress_manager_changelog_edit' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage ModPress changelog categories.', 'modpress' ) );
        }

        check_admin_referer( 'modpress_delete_changelog_category', 'modpress_changelog_category_nonce' );

        $taxonomy = RequestHelper::key( $_POST, 'changelog_category_slug', 'modpress_changelog_category' );
        $requested_ids = RequestHelper::value( $_POST, 'changelog_category_ids', array() );
        if ( ! is_array( $requested_ids ) ) {
            $requested_ids = array( $requested_ids );
        }

        $term_ids = array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn ( $value ) => absint( $value ),
                        $requested_ids
                    ),
                    static fn ( $value ) => $value > 0
                )
            )
        );

        if ( empty( $term_ids ) ) {
            $this->redirect_mod_action_result( 'changelog', __( 'No changelog category was selected to delete.', 'modpress' ), false );
        }

        foreach ( $term_ids as $term_id ) {
            wp_delete_term( $term_id, $taxonomy );
        }

        $this->redirect_mod_action_result( 'changelog', __( 'Changelog category deleted successfully.', 'modpress' ), true );
    }

    /**
     * Redirect back to the Mod manager screen with a notice.
     *
     * @param string $tab Page tab to return to.
     * @param string $message Notice text.
     * @param bool   $success Whether the save succeeded.
     * @return void
     * @since 1.0.0
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
     * @since 1.0.0
     */
    private function redirect_mod_action_result( string $tab, string $message = '', bool $success = true ): void {
        $query = array(
            'page' => 'modpress',
            'group' => 'mod-manager',
            'tab' => $tab,
        );

        if ( '' !== trim( $message ) ) {
            self::set_admin_notice_flash( $message, $success );
        }

        wp_safe_redirect( admin_url( 'admin.php?' . build_query( $query ) ) );
        exit;
    }
}