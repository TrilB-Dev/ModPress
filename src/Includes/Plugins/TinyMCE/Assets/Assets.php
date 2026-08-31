<?php
/**
 * TinyMCE Editor Plugin Assets
 *
 * @package ModPress
 * @subpackage Plugins\TinyMCE\Assets
 * @since 1.0.0
 */

namespace ModPress\Includes\Plugins\TinyMCE\Assets;

use ModPress\Includes\Functions\Helpers\LoaderHelper;
use ModPress\Includes\Plugins\TinyMCE\Includes\Settings\Settings;

final class Assets {
    private LoaderHelper $loader;

    public function __construct( ?LoaderHelper $loader = null ) {
        $this->loader = $loader ?? new LoaderHelper();
    }

    /**
     * Constructor for the TinyMCE plugin assets.
     */
    public function register(): void {
        $this->loader->register_component( $this, [
            [ 'type' => 'filter', 'hook' => 'modpress_admin_assets', 'callback' => 'register_admin_assets', 'accepted_args' => 2 ],
        ] )->run();
    }

    public function register_admin_assets( array $assets, string $context = '' ): array {
        $base_url = MODPRESS_URL . 'src/includes/Plugins/TinyMCE/Assets/tinymce/';

        $assets['styles'][] = [
            'handle' => 'modpress-tinymce-skin',
            'src' => $base_url . 'skins/ui/' . Settings::ui_skin() . '/skin.min.css',
        ];
        $assets['scripts'][] = [
            'handle' => 'modpress-tinymce',
            'src' => $base_url . 'tinymce.min.js',
            'in_footer' => true,
        ];
        $assets['scripts'][] = [
            'handle' => 'modpress-tinymce-boot',
            'src' => MODPRESS_URL . 'src/includes/Plugins/TinyMCE/Assets/js/tinymce.js',
            'deps' => [ 'modpress-tinymce' ],
            'in_footer' => true,
            'localize' => [
                'object_name' => 'modpressTinyMCE',
                'data' => [
                    'mediaTitle' => __( 'Insert media', 'modpress' ),
                    'mediaButton' => __( 'Insert into editor', 'modpress' ),
                    'mediaTooltip' => __( 'Insert media', 'modpress' ),
                ],
            ],
        ];

        if ( function_exists( 'wp_enqueue_media' ) ) {
            wp_enqueue_media();
        }

        return $assets;
    }
}