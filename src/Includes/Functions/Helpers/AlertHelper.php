<?php
/**
 * This file contains the alert helper functions for the plugin.
 * 
 * @since 1.0.0
 * 
 */

namespace ModPress\Includes\Functions\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlertHelper {

    /**
     * Renders an admin error notice.
     *
     * @param string $message The message to display.
     * @return void
     */
    public static function admin_error( string $message ): void {
        self::render_admin_notice( $message, 'error' );
    }
    /**
     * Renders an admin success notice.
     *
     * @param string $message The message to display.
     * @return void
     */
    public static function admin_success( string $message ): void {
        self::render_admin_notice( $message, 'success' );
    }
    /**
     * Renders an admin warning notice.
     *
     * @param string $message The message to display.
     * @return void
     */
    public static function admin_warning( string $message ): void {
        self::render_admin_notice( $message, 'warning' );
    }
    /**
     * Renders an admin info notice.
     *
     * @param string $message The message to display.
     * @return void
     */
    public static function admin_info( string $message ): void {
        self::render_admin_notice( $message, 'info' );
    }
    /**
     * Renders an admin notice with the specified type.
     *
     * @param string $message The message to display.
     * @param string $type    The type of notice ('info', 'success', 'warning', 'error').
     * @return void
     */
    public static function render_admin_notice( string $message, string $type = 'info' ): void {
        echo self::get_admin_notice( $message, $type );
    }

    /**
     * Returns an admin notice rendered with the shared alert markup.
     *
     * @param string $message The message to display.
     * @param string $type    The type of notice ('info', 'success', 'warning', 'error').
     * @return string The rendered admin notice.
     */
    public static function get_admin_notice( string $message, string $type = 'info' ): string {
        $allowed_types = [ 'info', 'success', 'warning', 'error' ];

        if ( ! in_array( $type, $allowed_types, true ) ) {

            $type = 'info';

        }
        
        switch ( $type ) {
            case 'success':
                $alert = 'success';
                $icon = 'check-circle';
                break;
            case 'warning':
                $alert = 'warning';
                $icon = 'exclamation-triangle';
                break;
            case 'error':
                $alert = 'danger';
                $icon = 'times-circle';
                break;
            default:
                $alert = 'info';
                $icon = 'info-circle';
        }

        ob_start();
        ?>

        <div class="alert alert-<?php echo esc_attr( $alert ); ?> d-flex align-items-center alert-dismissible fade show" role="alert" data-modpress-alert>

            <i class="flex-shrink-0 me-2 fa-solid fa-<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></i> <?php echo esc_html( $message ); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

        </div>

        <?php
        return (string) ob_get_clean();
    }
}