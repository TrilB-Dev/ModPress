<?php
/**
 * Settings for the Font Awesome ModPress plugin.
 * 
 * @package    ModPress
 * @subpackage ModPress/includes
 */
namespace ModPress\Includes\Plugins\FontAwesome\Includes\Settings;
use ModPress\Includes\Settings\Settings as BaseSettings;
use ModPress\Includes\Functions\Helpers\SanitizationHelper;

final class Settings {
    /**
     * Registers the Font Awesome settings group.
     *
     * @since 1.0.0
     */
    public function register(): void {
        BaseSettings::register_group( 'fontawesome', [
            'fontawesome_type' => 'cdn',
            'fontawesome_cdn_technology' => 'webfont',
            'fontawesome_kit_id' => '',
            'fontawesome_enable_icon_picker' => true,
        ] );
    }
    /**
     * Determines whether the icon picker is enabled.
     *
     * @return bool True if the icon picker is enabled, false otherwise.
     * @since 1.0.0
     */
    public static function enable_icon_picker(): bool {
        $enabled = BaseSettings::get_bool( 'fontawesome_enable_icon_picker', true );
        if ( ! $enabled ) {
            $enabled = BaseSettings::get_bool( 'fontawesome_icon_picker', false );
        }

        return $enabled;
    }

    /**
     * Retrieves the selected Font Awesome source type.
     *
     * @return string The source type.
     */
    public static function get_type(): string {
        $type = BaseSettings::get_key( 'fontawesome_type', 'cdn' );
        return in_array( $type, [ 'cdn', 'kit' ], true ) ? $type : 'cdn';
    }

    /**
     * Retrieves the configured Font Awesome kit ID.
     *
     * @return string The Font Awesome kit ID.
     */
    public static function get_kit_id(): string {
        return BaseSettings::get_string( 'fontawesome_kit_id', '' );
    }

    /**
     * Retrieves the selected Font Awesome CDN technology.
     *
     * @return string The CDN technology.
     */
    public static function get_cdn_technology(): string {
        $technology = BaseSettings::get_key( 'fontawesome_cdn_technology', 'webfont' );
        return in_array( $technology, [ 'webfont', 'svg' ], true ) ? $technology : 'webfont';
    }
    /**
     * Retrieves the settings page configuration for the Font Awesome plugin.
     *
     * @return array The settings page configuration.
     * @since 1.0.0
     */
    public function get_settings_page(): array {
        return [
            'slug' => 'fontawesome',
            'label' => __( 'FontAwesome', 'modpress' ),
            'title' => __( 'FontAwesome integration', 'modpress' ),
            'layout' => 'table',
            'fields' => [
                [
                    'key' => 'fontawesome_type',
                    'label' => __( 'FontAwesome Type', 'modpress' ),
                    'description' => __( 'Select how fontawesome is called in ModPress.', 'modpress' ),
                    'tooltip' => __( 'Choose between using the CDN or a Kit for FontAwesome.', 'modpress' ),
                    'tooltip_type' => 'info',
                    'type' => 'select',
                    'class' => 'w-100',
                    'options' => [
                        'cdn' => __( 'CDN', 'modpress' ),
                        'kit' => __( 'Kit', 'modpress' ),
                    ],
                    'default' => 'cdn',
                ],
                [
                    'key' => 'fontawesome_cdn_technology',
                    'label' => __( 'FontAwesome CDN Technology', 'modpress' ),
                    'description' => __( 'Select the technology used for the Font Awesome CDN.', 'modpress' ),
                    'tooltip' => __( 'Choose between different CDN technologies for loading Font Awesome.', 'modpress' ),
                    'tooltip_type' => 'info',
                    'type' => 'select',
                    'class' => 'w-100',
                    'options' => [
                        'svg' => __( 'SVG', 'modpress' ),
                        'webfont' => __( 'Web Font', 'modpress' ),
                    ],
                    'default' => 'webfont',
                    'visible_when' => [
                        'fontawesome_type' => 'cdn',
                    ],
                    'required' => [
                        'fontawesome_type' => 'cdn',
                    ],

                ],
                [
                    'key' => 'fontawesome_kit_id',
                    'label' => __( 'FontAwesome Kit ID', 'modpress' ),
                    'description' => __( 'Enter the ID of your Font Awesome Kit if you selected "Kit" as the type.', 'modpress' ),
                    'tooltip' => __( 'The FontAwesome Kit ID is required when using the Kit type.', 'modpress' ),
                    'tooltip_type' => 'info',
                    'type' => 'text',
                    'class' => 'w-100',
                    'default' => '',
                    'visible_when' => [
                        'fontawesome_type' => 'kit',
                    ],
                    'required' => [
                        'fontawesome_type' => 'kit',
                    ],
                ],
                [
                    'key' => 'fontawesome_enable_icon_picker',
                    'label' => __( 'Enable icon picker', 'modpress' ),
                    'description' => __( 'Enable the bundled Font Awesome icon picker inside ModPress admin interfaces.', 'modpress' ),
                    'tooltip' => __( 'ModPress includes its own icon picker and fallback icon catalog, so no WordPress FontAwesome plugin is required.', 'modpress' ),
                    'tooltip_type' => 'info',
                    'type' => 'switch',
                    'default' => true,
                ],
            ],
        ];
    }
    /**
     * Sanitizes the input for the Font Awesome settings.
     *
     * @param array $input The input array to sanitize.
     * @return array The sanitized input array.
     * @since 1.0.0
     */
    public function sanitize( $input ): array {
        $input = is_array( $input ) ? $input : [];

        $type = SanitizationHelper::key( $input['fontawesome_type'] ?? 'cdn', 'cdn' );
        $input['fontawesome_type'] = in_array( $type, [ 'cdn', 'kit' ], true ) ? $type : 'cdn';

        if ( 'kit' === $input['fontawesome_type'] ) {
            $input['fontawesome_kit_id'] = trim( (string) ( $input['fontawesome_kit_id'] ?? '' ) );
        } else {
            $input['fontawesome_kit_id'] = '';
        }

        $legacy_picker = ! empty( $input['fontawesome_icon_picker'] );
        $input['fontawesome_enable_icon_picker'] = (bool) ( $input['fontawesome_enable_icon_picker'] ?? $legacy_picker );
        $input['fontawesome_icon_picker'] = $input['fontawesome_enable_icon_picker'];

        BaseSettings::set_group( 'fontawesome', $input );
        return $input;
    }
}