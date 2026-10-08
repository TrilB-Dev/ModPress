<?php
/**
 * Font Awesome plugin integration for ModPress.
 *
 * @package FontAwesome
 * @textdomain modpress
 * @domainpath Languages
 * @author ModPress Team
 */

namespace ModPress\Includes\Plugins\FontAwesome;

use ModPress\Includes\Plugins\AssetsProviderInterface;
use ModPress\Includes\Plugins\I18nProviderInterface;
use ModPress\Includes\Plugins\PluginInterface;
use ModPress\Includes\Plugins\SettingsProviderInterface;
use ModPress\Includes\Plugins\SettingsPageProviderInterface;
use ModPress\Includes\Plugins\FontAwesome\Assets\Assets;
use ModPress\Includes\Plugins\FontAwesome\Includes\IconPicker;
use ModPress\Includes\Plugins\FontAwesome\API\FontAwesomeAPI;
use ModPress\Includes\Plugins\FontAwesome\Includes\Core\I18n;
use ModPress\Includes\Plugins\FontAwesome\Includes\Includes;
use ModPress\Includes\Core\WP\Activator;

final class FontAwesome implements PluginInterface, SettingsProviderInterface, SettingsPageProviderInterface, AssetsProviderInterface, I18nProviderInterface {
    /**
     * Singleton instance of the FontAwesome plugin.
     *
     * @var self|null
     * @since 1.0.0
     */
    private static ?self $instance = null;

    /**
     * IconPicker instance for the FontAwesome plugin.
     *
     * @var IconPicker|null
     * @since 1.0.0
     */
    private ?IconPicker $icon_picker = null;

    /**
     * Get the plugin slug.
     *
     * @return string
     * @since 1.0.0
     */
    public function get_slug(): string {
        return 'modpress-fontawesome';
    }

    /**
     * Get the plugin name.
     *
     * @return string
     * @since 1.0.0
     */
    public function get_name(): string {
        return 'FontAwesome';
    }

    /**
     * Get the plugin icon.
     *
     * @return array{0: string, 1: string}
     * @since 1.0.0
     */
    public function get_icon(): array {
        return [ 'fab fa-font-awesome', '#74c1fcff' ];
    }

    /**
     * Get the plugin version.
     *
     * @return string
     * @since 1.0.0
     */
    public function get_version(): string {

        return '1.0.0';
    }

    /**
     * Get the plugin author.
     *
     * @return string
     * @since 1.0.0
     */
    public function get_author(): string {
        return 'TrilB.Dev Team';
    }

    /**
     * Get the plugin author URI.
     *
     * @return string
     * @since 1.0.0
     */
    public function get_author_uri(): string {
        return 'https://trilb.dev/';
    }

    /**
     * Get the plugin description.
     *
     * @return string
     * @since 1.0.0
     */
    public function get_description(): string {
        return __( 'Provides FontAwesome CDN & Kit enqueueing in Admin, Frontend & Login Page, also adds an enhanced icon picker, and styling APIs for ModPress.', 'modpress' );
    }

    /**
     * Get the plugin URI.
     *
     * @return string
     * @since 1.0.0
     */
    public function get_uri(): string {
        return 'https://trilb.dev/collection/web-extension/wordpress/modpress';
    }

    /**
     * Get the plugin license.
     *
     * @return string
     * @since 1.0.0
     */
    public function get_license(): string {
        return 'GPL-2.0-or-later';
    }

    /**
     * Check if the plugin is active.
     *
     * @return bool
     * @since 1.0.0
     */
    public function is_active(): bool {
        return true;
    }

    /**
     * Private constructor to prevent direct instantiation.
     *
     * @since 1.0.0
     */
    private function __construct() {
    }
    /**
     * Initialize the FontAwesome plugin.
     *
     * @return void
     * @since 1.0.0
     */
    public function init(): void {

        FontAwesomeAPI::configure();
        $this->icon_picker = IconPicker::get_instance();
        Includes::get_instance()->init();
    }

    /**
     * Register plugin settings.
     *
     * @return void
     * @since 1.0.0
     */
    public function register_settings(): void {
        Includes::get_instance()->settings()->register();
    }

    /**
     * Get the settings page config.
     *
     * @return array
     * @since 1.0.0
     */
    public function get_settings_page(): array {
        return Includes::get_instance()->settings()->get_settings_page();
    }

    /**
     * Sanitize plugin settings.
     *
     * @param mixed $input
     * @return array
     * @since 1.0.0
     */
    public function sanitize_settings( $input ): array {
        return Includes::get_instance()->settings()->sanitize( $input );
    }

    /**
     * Register plugin assets.
     *
     * @return void
     * @since 1.0.0
     */
    public function register_assets(): void {
        ( new Assets() )->register();
    }

    /**
     * Load the text domain.
     *
     * @return void
     * @since 1.0.0
     */
    public function load_textdomain(): void {
        I18n::load_textdomain();
    }

    /**
     * Get the IconPicker instance.
     *
     * @return IconPicker|null
     * @since 1.0.0
     */
    public function get_icon_picker(): ?IconPicker {
        return $this->icon_picker;
    }

    /**
     * Get the singleton instance.
     *
     * @return self
     * @since 1.0.0
     */
    public static function get_instance(): self {
        return self::$instance ??= new self();
    }
}
