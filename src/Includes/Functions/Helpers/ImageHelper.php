<?php
/**
 * This file contains the image helper functions for the plugin.
 * 
 * @since 1.0.0
 * 
 */
namespace ModPress\Includes\Functions\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ImageHelper {
    /**
     * Get the URL of an image asset.
     *
    * @param string $type The asset type: core or an internal plugin slug.
     * @param string $file The path to the image relative to the Images directory.
     * @return string The full URL to the image asset.
     */
    public static function get_image_url( string $type, string $file ): string {
        $file = self::sanitize_file_path( $file );
        if ( '' === $file ) {
            return '';
        }

        if ( 'core' === strtolower( trim( $type ) ) ) {
            return MODPRESS_ASSETS_URL . '/images/' . $file;
        }

        $plugin_directory = self::get_plugin_directory( $type );
        if ( '' === $plugin_directory ) {
            return '';
        }

        return MODPRESS_PLUGINS_URL . '/' . $plugin_directory . '/Assets/images/' . $file;
    }

    /**
     * Resolve a plugin slug to its actual directory name.
     *
     * @param string $plugin_slug The plugin slug.
     * @return string The plugin directory name, or an empty string when invalid.
     */
    private static function get_plugin_directory( string $plugin_slug ): string {
        $plugin_slug = strtolower( preg_replace( '/[^a-zA-Z0-9_-]/', '', $plugin_slug ) ?? '' );
        if ( '' === $plugin_slug || ! defined( 'MODPRESS_PLUGINS' ) || ! is_dir( MODPRESS_PLUGINS ) ) {
            return '';
        }

        $plugin_slug_variants = [ $plugin_slug ];
        if ( str_starts_with( $plugin_slug, 'mspress-' ) ) {
            $plugin_slug_variants[] = substr( $plugin_slug, 8 );
        }
        if ( str_ends_with( $plugin_slug, '-plugin' ) ) {
            $plugin_slug_variants[] = substr( $plugin_slug, 0, -7 );
        }

        foreach ( scandir( MODPRESS_PLUGINS ) ?: [] as $directory ) {
            if ( '.' !== $directory && '..' !== $directory && is_dir( MODPRESS_PLUGINS . '/' . $directory ) && in_array( strtolower( $directory ), $plugin_slug_variants, true ) ) {
                return $directory;
            }
        }

        return '';
    }

    /**
     * Keep the asset path relative to the Images directory.
     *
     * @param string $file The requested asset path.
     * @return string The sanitized asset path, or an empty string when invalid.
     */
    private static function sanitize_file_path( string $file ): string {
        $file = trim( str_replace( '\\', '/', $file ) );
        if ( '' === $file || str_starts_with( $file, '/' ) || str_contains( $file, '..' ) ) {
            return '';
        }

        $parts = array_filter( explode( '/', $file ), static fn( string $part ): bool => '' !== $part );
        foreach ( $parts as $part ) {
            if ( ! preg_match( '/^[a-zA-Z0-9._-]+$/', $part ) ) {
                return '';
            }
        }

        return implode( '/', $parts );
    }
}