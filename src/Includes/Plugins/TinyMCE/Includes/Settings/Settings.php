<?php
/**
 * Settings for the TinyMCE plugin.
 * @package ModPress
 * @subpackage Admin\Wiki\Plugins\TinyMCE\Includes
 * @since 1.0.0
 */
namespace ModPress\Includes\Plugins\TinyMCE\Includes\Settings;
use ModPress\Includes\Settings\Settings as BaseSettings;
use ModPress\Includes\Functions\Helpers\SanitizationHelper;

final class Settings {
    /**
     * The settings group name for the TinyMCE plugin.
     */
    public const GROUP = 'tinymce';
    /**
     * The default TinyMCE plugins to enable.
     */
    private const DEFAULT_PLUGINS = [ 'advlist', 'autolink', 'lists', 'link', 'image', 'media', 'table', 'code', 'preview', 'searchreplace', 'wordcount' ];
    /**
     * The available TinyMCE plugins.
     */
    private const PLUGINS = [ 'accordion', 'advlist', 'anchor', 'autolink', 'autoresize', 'autosave', 'charmap', 'code', 'codesample', 'directionality', 'emoticons', 'fullscreen', 'help', 'image', 'importcss', 'insertdatetime', 'link', 'lists', 'media', 'nonbreaking', 'pagebreak', 'preview', 'quickbars', 'save', 'searchreplace', 'table', 'visualblocks', 'visualchars', 'wordcount' ];
    /**
     * The available TinyMCE content skins.
     */
    private const CONTENT_SKINS = [ 'default', 'dark', 'document', 'tinymce-5', 'tinymce-5-dark', 'writer' ];
    /**
     * The available TinyMCE UI skins.
     */
    private const UI_SKINS = [ 'oxide', 'oxide-dark', 'tinymce-5', 'tinymce-5-dark' ];
    /**
     * Register the TinyMCE plugin settings group and default values.
     */
    public function register(): void {
        BaseSettings::register_group( self::GROUP, [
            'tinymce_plugins' => self::DEFAULT_PLUGINS,
            'tinymce_content_skin' => 'default',
            'tinymce_ui_skin' => 'oxide',
        ] );
    }
    /**
     * Get the enabled TinyMCE plugins.
     *
     * @return array The list of enabled TinyMCE plugins.
     */
    public static function plugins(): array {
        $plugins = BaseSettings::get( 'tinymce_plugins', self::DEFAULT_PLUGINS );
        $plugins = is_array( $plugins ) ? array_map( [ SanitizationHelper::class, 'key' ], $plugins ) : self::DEFAULT_PLUGINS;
        $plugins = array_values( array_intersect( array_unique( $plugins ), self::PLUGINS ) );
        return $plugins ?: self::DEFAULT_PLUGINS;
    }
    /**
     * Get the selected TinyMCE content skin.
     *
     * @return string The selected content skin.
     */
    public static function content_skin(): string {
        $skin = BaseSettings::get_key( 'tinymce_content_skin', 'default' );
        return in_array( $skin, self::CONTENT_SKINS, true ) ? $skin : 'default';
    }
    /**
     * Get the selected TinyMCE UI skin.
     *
     * @return string The selected UI skin.
     */
    public static function ui_skin(): string {
        $skin = BaseSettings::get_key( 'tinymce_ui_skin', 'oxide' );
        return in_array( $skin, self::UI_SKINS, true ) ? $skin : 'oxide';
    }
    /**
     * Get the settings page configuration for the TinyMCE plugin.
     *
     * @return array The settings page configuration.
     */
    public function get_settings_page(): array {
        return [
            'slug' => self::GROUP,
            'label' => __( 'TinyMCE', 'modpress' ),
            'title' => __( 'TinyMCE integration', 'modpress' ),
            'layout' => 'table',
            'fields' => [
                [
                    'key' => 'tinymce_content_skin',
                    'label' => __( 'Default content skin', 'modpress' ),
                    'description' => __( 'Choose the stylesheet used inside the editor content area.', 'modpress' ),
                    'tooltip' => __( 'Content skins control typography and content formatting inside the editing area.', 'modpress' ),
                    'type' => 'select',
                    'options' => self::skin_options( self::CONTENT_SKINS ),
                    'default' => 'default',
                ],
                [
                    'key' => 'tinymce_ui_skin',
                    'label' => __( 'Default user-interface skin', 'modpress' ),
                    'description' => __( 'Choose the TinyMCE toolbar and dialog skin.', 'modpress' ),
                    'tooltip' => __( 'UI skins control the toolbar, menus, dialogs, and editor chrome.', 'modpress' ),
                    'type' => 'select',
                    'options' => self::skin_options( self::UI_SKINS ),
                    'default' => 'oxide',
                ],
                [
                    'key' => 'tinymce_plugins',
                    'label' => __( 'Enabled TinyMCE plugins', 'modpress' ),
                    'description' => __( 'Choose which bundled TinyMCE plugins are available to the editor.', 'modpress' ),
                    'tooltip' => __( 'Only selected local plugin files are requested when an editor starts.', 'modpress' ),
                    'type' => 'multiselect',
                    'dropup_auto' => false,
                    'show_tick' => true,
                    'selection_indicator' => 'checkbox',
                    'options' => self::plugin_options(),
                    'default' => self::DEFAULT_PLUGINS,
                ],
            ],
        ];
    }
    /**
     * Sanitize and save the TinyMCE plugin settings.
     *
     * @param mixed $input The input data to sanitize and save.
     * @return array The sanitized settings.
     */
    public function sanitize( $input ): array {
        $input = is_array( $input ) ? $input : [];
        $plugins = is_array( $input['tinymce_plugins'] ?? null ) ? $input['tinymce_plugins'] : [];
        $plugins = array_values( array_intersect( array_unique( array_map( [ SanitizationHelper::class, 'key' ], $plugins ) ), self::PLUGINS ) );
        $content_skin = SanitizationHelper::key( $input['tinymce_content_skin'] ?? '' );
        $ui_skin = SanitizationHelper::key( $input['tinymce_ui_skin'] ?? '' );
        $settings = [
            'tinymce_plugins' => $plugins ?: self::DEFAULT_PLUGINS,
            'tinymce_content_skin' => in_array( $content_skin, self::CONTENT_SKINS, true ) ? $content_skin : 'default',
            'tinymce_ui_skin' => in_array( $ui_skin, self::UI_SKINS, true ) ? $ui_skin : 'oxide',
        ];
        BaseSettings::set_group( self::GROUP, $settings );
        return $settings;
    }
    /**
     * Get the available TinyMCE plugin options for the settings page.
     *
     * @return array The available TinyMCE plugin options.
     */
    private static function plugin_options(): array {
        return self::skin_options( self::PLUGINS );
    }
    /**
     * Get the available TinyMCE skin options for the settings page.
     *
     * @param array $values The available skin values.
     * @return array The available TinyMCE skin options.
     */
    private static function skin_options( array $values ): array {
        $options = [];
        foreach ( $values as $value ) {
            $options[ $value ] = ucwords( str_replace( [ '-', '_' ], ' ', $value ) );
        }
        return $options;
    }
}