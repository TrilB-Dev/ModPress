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
use ModPress\Includes\ModManagement\GroupsManager;
use ModPress\Includes\ModManagement\ModsManager;
use ModPress\Includes\ModManagement\ChangelogManager;
use ModPress\Includes\ModManagement\FileManager;

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
     */
    public function save_group(): void {
        if ( ! current_user_can( 'modpress_manager_groups_edit' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage ModPress groups.', 'modpress' ) );
        }

        check_admin_referer( 'modpress_save_mod_group', 'modpress_group_nonce' );

        $group_name = RequestHelper::text( $_POST, 'group_name', '' );
        $taxonomy   = RequestHelper::key( $_POST, 'group_slug', SanitizationHelper::slug( $group_name, '' ) );

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
        $group_id      = $this->persist_group_record( $group_payload );

        if ( false === $group_id ) {
            LoggerHelper::write_log( 'ModPress group save failed while persisting row for taxonomy: ' . $taxonomy );
            $this->redirect_mod_group_result( false, __( 'The group could not be saved to ModPress.', 'modpress' ) );
        }

        $group_payload['id'] = $group_id;

        $service = new GroupsManager();
        $created = $service->dynamically_create(
            array(
                'taxonomy'      => $taxonomy,
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
     * Build the canonical custom-table payload for a mod group.
     *
     * @param string $group_name Display label.
     * @param string $taxonomy Taxonomy slug.
     * @return array<string, mixed>
     */
    private function build_group_payload( string $group_name, string $taxonomy ): array {
        $group_fields = array();
        $field_names  = RequestHelper::value( 
			$_POST, 
			'custom_field_name', 
			array() 
		);
        $field_slugs  = RequestHelper::value( $_POST, 'custom_field_slug', array() );
        $field_types  = RequestHelper::value( $_POST, 'custom_field_type', array() );
        $field_descs  = RequestHelper::value( $_POST, 'custom_field_description', array() );

        if ( is_array( $field_names ) ) {
            foreach ( $field_names as $index => $field_name ) {
                $name = trim( (string) $field_name );
                if ( '' === $name ) {
                    continue;
                }

                $group_fields[] = array(
                    'name'        => $name,
                    'slug'        => trim( (string) ( $field_slugs[ $index ] ?? SanitizationHelper::slug( $name, '' ) ) ),
                    'type'        => trim( (string) ( $field_types[ $index ] ?? 'text' ) ),
                    'description' => trim( (string) ( $field_descs[ $index ] ?? '' ) ),
                );
            }
        }

        $parent_value = RequestHelper::value( $_POST, 'group_parent', '' );
        $parent_id    = 0;

        if ( is_numeric( $parent_value ) ) {
            $parent_id = absint( $parent_value );
        } elseif ( '' !== (string) $parent_value ) {
            $parent_group = DBHelper::get_row( 'groups', array( 'group_slug' => sanitize_key( (string) $parent_value ) ) );
            if ( ! empty( $parent_group ) && isset( $parent_group['id'] ) ) {
                $parent_id = absint( $parent_group['id'] );
            }
        }

        $description = SanitizationHelper::textarea( RequestHelper::value( $_POST, 'group_description', '' ), '' );

        $group_payload = array(
            'group_name'          => $group_name,
            'group_slug'          => $taxonomy,
            'group_singular_name' => RequestHelper::text( $_POST, 'group_singular_name', $group_name ),
            'group_description'   => $description,
            'group_hierarchical'  => RequestHelper::boolean( $_POST, 'group_hierarchical', false ) ? 1 : 0,
            'group_parent_id'     => $parent_id > 0 ? $parent_id : null,
            'group_fields'        => ! empty( $group_fields ) ? wp_json_encode( $group_fields ) : null,
            'created_by'          => get_current_user_id(),
            'updated_at'          => current_time( 'mysql' ),
        );

        if ( empty( $group_payload['group_singular_name'] ) ) {
            $group_payload['group_singular_name'] = $group_name;
        }

        return $group_payload;
    }

    /**
     * Persist the custom groups row to the ModPress DB table.
     *
     * @param array<string, mixed> $group_payload Row payload.
     * @return int|false
     */
    private function persist_group_record( array $group_payload ) {
        $group_id = 0;
        $edit_key = RequestHelper::value( $_POST, 'group_edit_key', '' );

        if ( '' !== trim( (string) $edit_key ) ) {
            if ( is_numeric( $edit_key ) ) {
                $group_id = absint( $edit_key );
            } else {
                $existing_group = DBHelper::get_row( 'groups', array( 'group_slug' => sanitize_key( (string) $edit_key ) ) );
                if ( ! empty( $existing_group ) && isset( $existing_group['id'] ) ) {
                    $group_id = absint( $existing_group['id'] );
                }
            }
        }

        if ( $group_id > 0 ) {
            $updated = DBHelper::update(
                'groups',
                array(
                    'group_name'          => $group_payload['group_name'],
                    'group_slug'          => $group_payload['group_slug'],
                    'group_singular_name' => $group_payload['group_singular_name'],
                    'group_description'   => $group_payload['group_description'],
                    'group_hierarchical'  => $group_payload['group_hierarchical'],
                    'group_parent_id'     => $group_payload['group_parent_id'],
                    'group_fields'        => $group_payload['group_fields'],
                    'updated_at'          => current_time( 'mysql' ),
                ),
                array( 'id' => $group_id )
            );

            return $updated ? $group_id : false;
        }

        $existing_group = DBHelper::get_row( 'groups', array( 'group_slug' => $group_payload['group_slug'] ) );
        if ( ! empty( $existing_group ) && isset( $existing_group['id'] ) ) {
            $updated = DBHelper::update(
                'groups',
                array(
                    'group_name'          => $group_payload['group_name'],
                    'group_singular_name' => $group_payload['group_singular_name'],
                    'group_description'   => $group_payload['group_description'],
                    'group_hierarchical'  => $group_payload['group_hierarchical'],
                    'group_parent_id'     => $group_payload['group_parent_id'],
                    'group_fields'        => $group_payload['group_fields'],
                    'updated_at'          => current_time( 'mysql' ),
                ),
                array( 'id' => (int) $existing_group['id'] )
            );

            return $updated ? (int) $existing_group['id'] : false;
        }

        $inserted = DBHelper::insert(
            'groups',
            array(
                'group_name'          => $group_payload['group_name'],
                'group_slug'          => $group_payload['group_slug'],
                'group_singular_name' => $group_payload['group_singular_name'],
                'group_description'   => $group_payload['group_description'],
                'group_hierarchical'  => $group_payload['group_hierarchical'],
                'group_parent_id'     => $group_payload['group_parent_id'],
                'group_fields'        => $group_payload['group_fields'],
                'created_by'          => $group_payload['created_by'],
                'created_at'          => current_time( 'mysql' ),
                'updated_at'          => current_time( 'mysql' ),
            )
        );

        return false !== $inserted ? (int) $inserted : false;
    }

    /**
     * Save a taxonomy term from the entry modal.
     *
     * @return void
     */
    public function save_group_entry(): void {
        if ( ! current_user_can( 'modpress_manager_groups_edit' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage ModPress taxonomy entries.', 'modpress' ) );
        }

        check_admin_referer( 'modpress_save_mod_group_entry', 'modpress_group_entry_nonce' );

        $taxonomy = RequestHelper::key( $_POST, 'entry_group_taxonomy', '' );
        $name     = RequestHelper::text( $_POST, 'entry_name', '' );

        if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
            LoggerHelper::write_log( 'ModPress group entry save rejected: invalid taxonomy ' . $taxonomy );
            $this->redirect_mod_group_result( false, __( 'The selected taxonomy is invalid.', 'modpress' ) );
        }

        if ( '' === $name ) {
            LoggerHelper::write_log( 'ModPress group entry save rejected: missing entry name.' );
            $this->redirect_mod_group_result( false, __( 'An entry name is required.', 'modpress' ) );
        }

        $slug        = RequestHelper::slug( $_POST, 'entry_slug', $name );
        $description = SanitizationHelper::textarea( RequestHelper::value( $_POST, 'entry_description', '' ), '' );
        $parent      = RequestHelper::integer( $_POST, 'entry_parent', 0 );
        $args        = array(
            'description' => $description,
            'slug'        => $slug,
            'parent'      => $parent > 0 ? $parent : 0,
        );

        $existing = term_exists( $name, $taxonomy );
        $result   = $existing ? wp_update_term( (int) ( is_array( $existing ) ? $existing['term_id'] : $existing ), $taxonomy, array_merge( array( 'name' => $name ), $args ) ) : wp_insert_term( $name, $taxonomy, $args );

        if ( is_wp_error( $result ) ) {
            LoggerHelper::write_log( 'ModPress group entry save failed for taxonomy ' . $taxonomy . ': ' . $result->get_error_message() );
            $this->redirect_mod_group_result( false, $result->get_error_message() );
        }

        LoggerHelper::write_log( 'ModPress group entry saved successfully for taxonomy ' . $taxonomy );
        $this->redirect_mod_group_result( true, __( 'Entry saved successfully.', 'modpress' ) );
    }

    /**
     * Fallback for any unimplemented Mod admin action callback.
     *
     * @param string $name Method name.
     * @param array  $arguments Arguments passed to the callback.
     * @return void
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
    private function redirect_mod_action_result( string $tab, string $message = '', bool $success = true ): void {
        $query = array(
            'page' => 'modpress',
            'group' => 'mods',
            'tab' => $tab,
            $success ? 'mod_group_saved' : 'mod_group_failed' => '1',
        );

        if ( '' !== $message ) {
            $query['mod_group_message'] = rawurlencode( $message );
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
}