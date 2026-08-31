<?php
/**
 * AJAX request and response helpers for ModPress and extensions.
 *
 * @package ModPress
 * @subpackage Includes\Functions\Helpers
 * @since 1.0.0
 */
namespace ModPress\Includes\Functions\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Centralize common WordPress AJAX checks and responses.
 */
final class AjaxHelper {
    /**
     * Checks if the current request is an AJAX request.
     *
     * @return bool True if it's an AJAX request, false otherwise.
     */
    public static function is_ajax_request(): bool {

        return defined( 'DOING_AJAX' ) && DOING_AJAX;

    }

    /**
     * Returns the current HTTP request method.
     *
     * @return string The upper-case request method.
     */
    public static function request_method(): string {

        return strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' );

    }

    /**
     * Checks whether the current request uses the expected HTTP method.
     *
     * @param string $method Expected HTTP method.
     * @return bool True when the method matches.
     */
    public static function is_method( string $method ): bool {

        return self::request_method() === strtoupper( $method );

    }

    /**
     * Checks whether the request contains a valid WordPress nonce.
     *
     * @param string              $action Nonce action.
     * @param string              $field  Request field containing the nonce.
     * @param array<string,mixed>|null $request Request data, defaults to POST data.
     * @return bool True when the nonce is valid.
     */
    public static function has_valid_nonce( string $action, string $field = 'nonce', ?array $request = null ): bool {

        $request = null === $request ? $_POST : $request;
        $nonce   = $request[ $field ] ?? '';

        return is_scalar( $nonce ) && (bool) wp_verify_nonce( (string) wp_unslash( $nonce ), $action );

    }

    /**
     * Checks whether the current user has a capability.
     *
     * @param string $capability Capability to check.
     * @param int    $object_id Optional object ID for meta capabilities.
     * @return bool True when the current user is authorized.
     */
    public static function can( string $capability, int $object_id = 0 ): bool {

        return PermissionHelper::can( $capability, $object_id );

    }

    /**
     * Checks a nonce and capability before an AJAX operation runs.
     *
     * @param string $action     Nonce action.
     * @param string $capability Required capability, or empty to skip the check.
     * @param int    $object_id  Optional object ID for meta capabilities.
     * @param string $field      Request field containing the nonce.
     * @return bool True when all requested checks pass.
     */
    public static function authorized( string $action, string $capability = '', int $object_id = 0, string $field = 'nonce' ): bool {

        if ( ! self::has_valid_nonce( $action, $field ) ) {

            return false;

        }

        return '' === $capability || self::can( $capability, $object_id );

    }

    /**
     * Sends a successful JSON response.
     *
     * @param mixed $data Response data.
     * @param int   $status_code HTTP status code.
     * @return void
     */
    public static function success( $data = null, int $status_code = 200 ): void {

        wp_send_json_success( $data, $status_code );

    }

    /**
     * Sends an error JSON response.
     *
     * @param mixed $data Response error data or message.
     * @param int   $status_code HTTP status code.
     * @return void
     */
    public static function error( $data = null, int $status_code = 400 ): void {

        wp_send_json_error( $data, $status_code );

    }

    /**
     * Sends a standard authorization error for a failed AJAX request.
     *
     * @param string $message Error message.
     * @param int    $status_code HTTP status code.
     * @return void
     */
    public static function unauthorized( string $message = 'You are not authorized to perform this action.', int $status_code = 403 ): void {

        self::error( [ 'message' => $message ], $status_code );
        
    }
}