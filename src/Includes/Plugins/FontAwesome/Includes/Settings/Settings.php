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
            'fontawesome_source' => 'base',
            'fontawesome_kit_id' => '',
            'fontawesome_version' => '7.0.0',
        ] );
    }

    public static function source(): string {
        $source = BaseSettings::get_key( 'fontawesome_source', 'base' );
        return in_array( $source, [ 'base', 'kit' ], true ) ? $source : 'base';
    }

    public static function kit_id(): string {
        return BaseSettings::get_string( 'fontawesome_kit_id' );
    }

    public static function version(): string {
        return BaseSettings::get_string( 'fontawesome_version', '7.0.0' );
    }

    public function get_settings_page(): array {
        return [
            'slug' => 'fontawesome',
            'label' => __( 'Font Awesome', 'modpress' ),
            'title' => __( 'Font Awesome integration', 'modpress' ),
            'layout' => 'table',
            'fields' => [
                [ 
                    'key' => 'fontawesome_source',
                    'label' => __( 'Icon source', 'modpress' ),
                    'description' => __( 'Choose how ModPress loads Font Awesome icons.', 'modpress' ),
                    'tooltip' => __( 'Use the base package for the bundled icons or a Kit when you need a custom Font Awesome configuration.', 'modpress' ),
                    'tooltip_type' => 'info',
                    'type' => 'select',
                    'options' => [ 'base' => __( 'Base package', 'modpress' ),
                    'kit' => __( 'Font Awesome Kit', 'modpress' ) ],
                    'default' => 'base' 
                ],
                [ 
                    'key' => 'fontawesome_kit_id',
                    'label' => __( 'Kit ID', 'modpress' ),
                    'description' => __( 'Enter the ID of your Font Awesome Kit.', 'modpress' ),
                    'tooltip' => __( 'This value is used only when Icon source is set to Font Awesome Kit.', 'modpress' ),
                    'type' => 'text',
                    'default' => '' 
                ],
                [ 
                    'key' => 'fontawesome_version',
                    'label' => __( 'Base package version', 'modpress' ),
                    'description' => __( 'Set the version of the bundled Font Awesome package to load.', 'modpress' ),
                    'tooltip' => __( 'Use a version supported by the installed Font Awesome assets.', 'modpress' ),
                    'tooltip_type' => 'info',
                    'type' => 'text',
                    'default' => '7.0.0'
                ],
            ],
        ];
    }

    public function sanitize( $input ): array {
        $input = is_array( $input ) ? $input : [];
        $source = SanitizationHelper::key( $input['fontawesome_source'] ?? 'base', 'base' );
        $input['fontawesome_source'] = in_array( $source, [ 'base', 'kit' ], true ) ? $source : 'base';
        $input['fontawesome_kit_id'] = SanitizationHelper::text( $input['fontawesome_kit_id'] ?? '' );
        $input['fontawesome_version'] = SanitizationHelper::text( $input['fontawesome_version'] ?? '7.0.0', '7.0.0' );
        BaseSettings::set_group( 'fontawesome', $input );
        return $input;
    }
}