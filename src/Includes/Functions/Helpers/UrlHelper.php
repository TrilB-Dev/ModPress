<?php
/**
 * URL builders for PluginPress and extensions.
 *
 * @package PluginPress
 * @subpackage Includes\Functions\Helpers
 * @since 1.0.0
 */

namespace PluginPress\Includes\Functions\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Build common admin and nonce-protected URLs consistently.
 */
final class UrlHelper {
    /**
     * Constructs an admin page URL with the specified page and query arguments.
     *
     * @param string $page The admin page slug.
     * @param array  $args Optional query arguments to append to the URL.
     * @return string The constructed admin page URL.
     */
    public static function admin_page( string $page, array $args = [] ): string {

        $url = admin_url( 'admin.php' );

        return (string) add_query_arg( array_merge( [ 'page' => SanitizationHelper::key( $page ) ], $args ), $url );

    }
    /**
     * Constructs an admin action URL with the specified action and query arguments.
     *
     * @param string $action The admin action slug.
     * @param array  $args Optional query arguments to append to the URL.
     * @return string The constructed admin action URL.
     */
    public static function admin_action( string $action, array $args = [] ): string {

        return (string) add_query_arg( array_merge( [ 'action' => SanitizationHelper::key( $action ) ], $args ), admin_url( 'admin-post.php' ) );

    }
    /**
     * Generates a nonce-protected URL for the specified action.
     *
     * @param string $url The base URL to protect with a nonce.
     * @param string $action The action name for the nonce.
     * @return string The nonce-protected URL.
     */
    public static function nonce( string $url, string $action ): string {

        return (string) wp_nonce_url( $url, $action );

    }
    /**
     * Generates a nonce-protected admin action URL for the specified action.
     *
     * @param string $action The admin action slug.
     * @param string $nonce_action The action name for the nonce.
     * @param array  $args Optional query arguments to append to the URL.
     * @return string The nonce-protected admin action URL.
     */
    public static function admin_action_nonce( string $action, string $nonce_action, array $args = [] ): string {

        return self::nonce( self::admin_action( $action, $args ), $nonce_action );
        
    }
}
