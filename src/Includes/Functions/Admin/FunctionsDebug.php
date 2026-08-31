<?php
/**
 * FunctionsDebug
 *
 * This file contains the FunctionsDebug class, which provides functionality to retrieve debug information about the WordPress environment.
 *
 * @package ModPress
 * @subpackage Includes\Functions\Admin
 * @since 1.0.0
 */
namespace ModPress\Includes\Functions\Admin;

class FunctionsDebug {
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
}