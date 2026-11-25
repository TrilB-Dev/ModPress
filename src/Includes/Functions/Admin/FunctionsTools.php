<?php
/**
 * Admin Tools Functions
 *
 * @package ModPress
 * @subpackage Includes\Functions\Admin
 * @since 1.0.0
 */
namespace ModPress\Includes\Functions\Admin;

use ModPress\Includes\Tools\DataTransfer;
use ModPress\Includes\Core\Defaults;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

final class FunctionsTools {
    /**
     * Get debug information about the WordPress environment.
     *
     * @return array Debug information.
     * @since 1.0.0
     */
    public static function debug_info(): array {
        return [
            'php_version' => phpversion(),
            'wordpress_version' => get_bloginfo( 'version' ),
            'active_plugins' => get_option( 'active_plugins', [] ),
            'theme' => wp_get_theme()->get( 'Name' ),
            'memory_limit' => ini_get( 'memory_limit' ),
            'max_execution_time' => ini_get( 'max_execution_time' ),
            'upload_max_filesize' => ini_get( 'upload_max_filesize' ),
            'post_max_size' => ini_get( 'post_max_size' ),
        ];
    }
    /**
     * Run basic HTTP diagnostics for the Tools Debug page.
     *
     * @return array<string,mixed>
     * @since 1.0.0
     */
    public static function run_diagnostics(): array {
        $curl_available = function_exists( 'curl_init' ) || extension_loaded( 'curl' );
        $curl = array(
            'success' => $curl_available,
            'message' => $curl_available ? __( 'cURL support is available.', 'modpress' ) : __( 'cURL support is unavailable.', 'modpress' ),
            'trace' => array(
                'curl_init=' . ( function_exists( 'curl_init' ) ? 'available' : 'unavailable' ),
                'curl_extension=' . ( extension_loaded( 'curl' ) ? 'loaded' : 'not-loaded' ),
            ),
        );

        $response = wp_remote_get( 'https://graph.microsoft.com/v1.0/', array( 'timeout' => 10 ) );
        $http_graph = array( 'success' => false, 'message' => __( 'Microsoft Graph was not reachable.', 'modpress' ), 'trace' => array() );

        if ( is_wp_error( $response ) ) {
            $http_graph['trace'][] = $response->get_error_message();
        } else {
            $status = absint( wp_remote_retrieve_response_code( $response ) );
            $http_graph['success'] = $status >= 200 && $status < 500;
            $http_graph['message'] = 401 === $status
                ? __( 'Microsoft Graph is reachable; authentication was rejected as expected.', 'modpress' )
                : sprintf( __( 'Microsoft Graph returned HTTP status %d.', 'modpress' ), $status );
            $http_graph['trace'][] = 'HTTP ' . $status;
        }

        return array(
            'success' => $curl['success'] && $http_graph['success'],
            'message' => __( 'Diagnostics completed.', 'modpress' ),
            'curl' => $curl,
            'http_graph' => $http_graph,
            'diagnostics' => self::debug_info(),
        );
    }
    /**
     * Export ModPress data as a JSON file.
     *
     * @return void
     * @since 1.0.0
     */
    public function export_data(): void {
        if ( ! current_user_can( 'modpress_tools_export' ) ) {
            wp_die( esc_html__( 'You are not allowed to export ModPress data.', 'modpress' ), 403 );
        }
        check_admin_referer( 'modpress_export' );
        nocache_headers();
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=modpress-export-' . gmdate( 'Y-m-d' ) . '.json' );
        echo wp_json_encode( DataTransfer::export(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
        exit;
    }
    /**
     * Import ModPress data from an uploaded JSON file.
     *
     * @return void
     * @since 1.0.0
     */
    public function import_data(): void {
        if ( ! current_user_can( 'modpress_tools_import' ) ) {
            wp_die( esc_html__( 'You are not allowed to import ModPress data.', 'modpress' ), 403 );
        }
        check_admin_referer( 'modpress_import' );
        $file = $_FILES['modpress_import_file'] ?? array();
        if ( empty( $file['tmp_name'] ) || ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) !== UPLOAD_ERR_OK ) {
            wp_die( esc_html__( 'Please upload a valid ModPress JSON export.', 'modpress' ), 400 );
        }
        $data = json_decode( file_get_contents( $file['tmp_name'] ), true );
        if ( ! is_array( $data ) ) {
            wp_die( esc_html__( 'The uploaded file is not valid JSON.', 'modpress' ), 400 );
        }
        $result = DataTransfer::import( $data );
        if ( is_wp_error( $result ) ) {
            wp_die( esc_html( $result->get_error_message() ), 400 );
        }
        wp_safe_redirect( admin_url( 'admin.php?page=modpress&group=tools&tab=import&imported=1' ) );
        exit;
    }
    /**
     * Import default ModPress group settings.
     *
     * @return void
     * @since 1.0.0
     */
    public function import_group_defaults(): void {
        if ( ! current_user_can( 'modpress_tools_import' ) ) {
            wp_die( esc_html__( 'You are not allowed to import the default ModPress groups.', 'modpress' ), 403 );
        }

        check_admin_referer( 'modpress_import_group_defaults', 'modpress_import_group_defaults_nonce' );

        $import_target = isset( $_POST['import_target'] ) ? sanitize_key( wp_unslash( $_POST['import_target'] ) ) : '';

        if ( 'modpress_file_type' === $import_target ) {
            Defaults::default_file_type();
            wp_safe_redirect( admin_url( 'admin.php?page=modpress&group=tools&tab=import&default_file_types_imported=1' ) );
            exit;
        }

        if ( 'modpress_mod_status' === $import_target ) {
            Defaults::default_mod_status();
            wp_safe_redirect( admin_url( 'admin.php?page=modpress&group=tools&tab=import&default_mod_statuses_imported=1' ) );
            exit;
        }

        if ( empty( $_POST['confirm_default_reset'] ) || '1' !== (string) $_POST['confirm_default_reset'] ) {
            wp_safe_redirect( admin_url( 'admin.php?page=modpress&group=tools&tab=import&default_groups_import_failed=1' ) );
            exit;
        }

        $success = Defaults::reset_group_defaults();
        wp_safe_redirect( admin_url( 'admin.php?page=modpress&group=tools&tab=import&default_groups_imported=' . ( $success ? '1' : '0' ) ) );
        exit;
    }
}