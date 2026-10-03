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
    public function register(): void {
        BaseSettings::register_group( 'fontawesome', [
            'fontawesome_enable_icon_picker' => true,
        ] );
    }

    public static function enable_icon_picker(): bool {
        return BaseSettings::get_bool( 'fontawesome_enable_icon_picker', true );
    }

    public function get_settings_page(): array {
        return [
            'slug' => 'fontawesome',
            'label' => __( 'Font Awesome', 'modpress' ),
            'title' => __( 'Font Awesome integration', 'modpress' ),
            'layout' => 'table',
            'fields' => [
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

    public function sanitize( $input ): array {
        $input = is_array( $input ) ? $input : [];
        $input['fontawesome_enable_icon_picker'] = (bool) ( $input['fontawesome_enable_icon_picker'] ?? true );
        BaseSettings::set_group( 'fontawesome', $input );
        return $input;
    }
}