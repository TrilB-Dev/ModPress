<?php
/**
 * Validation helpers for ModPress import and tool payloads.
 *
 * @package ModPress
 * @subpackage Includes\Tools
 * @since 1.0.0
 */

namespace ModPress\Includes\Tools;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ImportValidator {
    public static function from_json( string $json ): array|\WP_Error {
        if ( '' === trim( $json ) ) {
            return new \WP_Error( 'empty_import', __( 'The import file is empty.', 'modpress' ) );
        }

        $data = json_decode( $json, true );
        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
            return new \WP_Error( 'invalid_json', __( 'The import file contains invalid JSON.', 'modpress' ) );
        }

        $validation = DataTransfer::validate( $data );
        return $validation['valid'] ? $data : new \WP_Error( 'invalid_import', implode( ' ', $validation['errors'] ) );
    }

    public static function file( array $file ): array|\WP_Error {
        if ( ! empty( $file['error'] ) ) {
            return new \WP_Error( 'upload_error', __( 'The import file could not be uploaded.', 'modpress' ) );
        }
        if ( empty( $file['tmp_name'] ) || ! is_readable( $file['tmp_name'] ) ) {
            return new \WP_Error( 'missing_file', __( 'The import file could not be read.', 'modpress' ) );
        }

        $json = file_get_contents( $file['tmp_name'] );
        return false === $json ? new \WP_Error( 'read_error', __( 'The import file could not be read.', 'modpress' ) ) : self::from_json( $json );
    }
}