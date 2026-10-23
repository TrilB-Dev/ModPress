<?php
/**
 * Import-related admin functions for ModPress.
 *
 * @package ModPress
 * @subpackage Includes\Functions\Admin
 * @since 1.0.0
 */
namespace ModPress\Includes\Functions\Admin;

use ModPress\Includes\Core\Defaults;
use ModPress\Includes\Tools\DataTransfer;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class FunctionsImport {

    /**
     * Import ModPress data from an uploaded JSON file.
     *
     * @return void
     */
    public function import_data(): void {
        if ( ! \current_user_can( 'modpress_tools_import' ) ) {
            \wp_die( \esc_html__( 'You are not allowed to import ModPress data.', 'modpress' ), 403 );
        }
        \check_admin_referer( 'modpress_import' );
        $file = $_FILES['modpress_import_file'] ?? array();
        if ( empty( $file['tmp_name'] ) || ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) !== UPLOAD_ERR_OK ) {
            \wp_die( \esc_html__( 'Please upload a valid ModPress JSON export.', 'modpress' ), 400 );
        }
        $data = json_decode( file_get_contents( $file['tmp_name'] ), true );
        if ( ! is_array( $data ) ) {
            \wp_die( \esc_html__( 'The uploaded file is not valid JSON.', 'modpress' ), 400 );
        }
        $result = DataTransfer::import( $data );
        if ( is_wp_error( $result ) ) {
            \wp_die( \esc_html( $result->get_error_message() ), 400 );
        }
        \wp_safe_redirect( \admin_url( 'admin.php?page=modpress&group=tools&tab=import&imported=1' ) );
        exit;
    }

    public function import_group_defaults(): void {
        if ( ! \current_user_can( 'modpress_tools_import' ) ) {
            \wp_die( \esc_html__( 'You are not allowed to import the default ModPress groups.', 'modpress' ), 403 );
        }

        \check_admin_referer( 'modpress_import_group_defaults', 'modpress_import_group_defaults_nonce' );

        if ( empty( $_POST['confirm_default_reset'] ) || '1' !== (string) $_POST['confirm_default_reset'] ) {
            \wp_safe_redirect( \admin_url( 'admin.php?page=modpress&group=tools&tab=import&default_groups_import_failed=1' ) );
            exit;
        }

        $success = Defaults::reset_group_defaults();
        \wp_safe_redirect( \admin_url( 'admin.php?page=modpress&group=tools&tab=import&default_groups_imported=' . ( $success ? '1' : '0' ) ) );
        exit;
    }
}