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
        return BaseSettings::get_bool( 'fontawesome_enable_icon_picker', true );
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
     * Retrieves the settings page configuration for the Font Awesome plugin.
     *
     * @return array The settings page configuration.
     * @since 1.0.0
     */
    public function get_settings_page(): array {
        return [
            'slug' => 'fontawesome',
            'label' => __( 'Font Awesome', 'modpress' ),
            'title' => __( 'Font Awesome integration', 'modpress' ),
            'layout' => 'table',
            'fields' => [
                [
                    'key' => 'fontawesome_type',
                    'label' => __( 'FontAwesome Type', 'modpress' ),
                    'description' => __( 'Select how fontawesome is called in ModPress.', 'modpress' ),
                    'tooltip' => __( 'Choose between using the CDN or a Kit for FontAwesome.', 'modpress' ),
                    'tooltip_type' => 'info',
                    'type' => 'select',
                    'options' => [
                        'cdn' => __( 'CDN', 'modpress' ),
                        'kit' => __( 'Kit', 'modpress' ),
                    ],
                    'default' => 'cdn',
                ],
                [
                    'key' => 'fontawesome_kit_id',
                    'label' => __( 'FontAwesome Kit ID', 'modpress' ),
                    'description' => __( 'Enter the ID of your Font Awesome Kit if you selected "Kit" as the type.', 'modpress' ),
                    'tooltip' => __( 'The FontAwesome Kit ID is required when using the Kit type.', 'modpress' ),
                    'tooltip_type' => 'info',
                    'type' => 'text',
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
                    'description' => __( 'Enable the Font Awesome icon picker inside ModPress admin interfaces.', 'modpress' ),
                    'tooltip' => __( 'This only controls the ModPress icon picker UI. The official Font Awesome admin bundle is still enqueued for the ModPress screens.', 'modpress' ),
                    'tooltip_type' => 'info',
                    'type' => 'checkbox',
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

        $input['fontawesome_enable_icon_picker'] = (bool) ( $input['fontawesome_enable_icon_picker'] ?? true );
        BaseSettings::set_group( 'fontawesome', $input );
        return $input;
    }
}