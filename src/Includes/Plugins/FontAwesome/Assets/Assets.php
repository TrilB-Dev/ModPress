<?php

namespace ModPress\Includes\Plugins\FontAwesome\Assets;

use ModPress\Includes\Functions\Helpers\LoaderHelper;
use ModPress\Includes\Plugins\FontAwesome\Includes\Settings\Settings as FontAwesomeSettings;

final class Assets {
    private LoaderHelper $loader;

    public function __construct( ?LoaderHelper $loader = null ) {
        $this->loader = $loader ?? new LoaderHelper();
    }

    public function register(): void {
        $this->loader->register_component( $this, [
            [ 'type' => 'action', 'hook' => 'admin_enqueue_scripts', 'callback' => 'enqueue_admin_assets' ],
        ] )->run();
    }

    public function enqueue_admin_assets( string $hook_suffix = '' ): void {
        $this->enqueue_fontawesome_vendor_assets( $hook_suffix );
        $this->enqueue_icon_picker();
    }

    private function enqueue_fontawesome_vendor_assets( string $hook_suffix ): void {
        $page = sanitize_key( $_GET['page'] ?? '' );
        if ( false === strpos( $hook_suffix, 'modpress' ) && 0 !== strpos( $page, 'modpress' ) ) {
            return;
        }

        $source = FontAwesomeSettings::source();
        $kit_id = FontAwesomeSettings::kit_id();
        $use_kit = '' !== $kit_id;

        if ( $use_kit ) {
            foreach ( [ 'font-awesome-kit', 'font-awesome-cdn' ] as $handle ) {
                wp_dequeue_style( $handle );
                wp_dequeue_script( $handle );
            }
        }

        wp_add_inline_script(
            'modpress-admin-ui',
            'window.modpressFontAwesomeSettings = ' . wp_json_encode( [
                'source' => $source,
                'kit_id' => $kit_id,
            ] ) . ';',
            'before'
        );

        if ( $use_kit ) {
            return;
        }

        $handle = 'kit' === $source ? 'font-awesome-kit' : 'font-awesome-cdn';
        if ( wp_style_is( $handle, 'registered' ) || wp_style_is( $handle, 'enqueued' ) ) {
                wp_enqueue_style( $handle );
        }

        if ( wp_script_is( $handle, 'registered' ) || wp_script_is( $handle, 'enqueued' ) ) {
            wp_enqueue_script( $handle );
        }
    }

    public function enqueue_icon_picker(): void {
        if ( ! $this->should_enqueue_icon_picker() ) {
            return;
        }

        wp_enqueue_style(
            'modpress-fontawesome-icon-picker',
            MODPRESS_URL . 'src/Includes/Plugins/FontAwesome/Assets/dist/css/icon-picker.css',
            [],
            MODPRESS_VERSION
        );
        wp_enqueue_script(
            'modpress-fontawesome-icon-picker',
            MODPRESS_URL . 'src/Includes/Plugins/FontAwesome/Assets/dist/js/icon-picker.js',
            [ 'jquery' ],
            MODPRESS_VERSION,
            true
        );

        wp_localize_script( 'modpress-fontawesome-icon-picker', 'modpress_fa_picker', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'modpress_fontawesome_picker' ),
            'strings' => [
                'search_placeholder' => __( 'Search icons...', 'modpress' ),
                'no_icons_found' => __( 'No icons found', 'modpress' ),
                'loading' => __( 'Loading...', 'modpress' ),
                'select_icon' => __( 'Select Icon', 'modpress' ),
                'close' => __( 'Close', 'modpress' ),
            ],
        ] );
    }

    private function should_enqueue_icon_picker(): bool {
        $screen = get_current_screen();
        if ( ! $screen ) {
            return false;
        }

        return strpos( $screen->id, 'modpress' ) !== false
            || in_array( $screen->id, [ 'post', 'page', 'custom_css', 'customize' ], true );
    }
}