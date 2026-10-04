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
     */
    private static ?self $instance = null;

    /**
     * IconPicker instance for the FontAwesome plugin.
     *
     * @var IconPicker|null
     */
    private ?IconPicker $icon_picker = null;

    /**
     * Get the plugin slug.
     *
     * @return string
     */
    public function get_slug(): string {
        return 'modpress-fontawesome';
    }

    /**
     * Get the plugin name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'FontAwesome';
    }

    /**
     * Get the plugin icon.
     *
     * @return array{0: string, 1: string}
     */
    public function get_icon(): array {
        return [ 'fab fa-font-awesome', '#74c1fcff' ];
    }

    /**
     * Get the plugin version.
     *
     * @return string
     */
    public function get_version(): string {
        if ( self::is_wordpress_fontawesome_active() && function_exists( 'FortAwesome\fa' ) && class_exists( '\FortAwesome\FontAwesome' ) ) {
            return \FortAwesome\fa()->version();
        }

        return '1.0.0';
    }

    /**
     * Get the plugin author.
     *
     * @return string
     */
    public function get_author(): string {
        return 'TrilB.Dev Team';
    }

    /**
     * Get the plugin author URI.
     *
     * @return string
     */
    public function get_author_uri(): string {
        return 'https://trilb.dev';
    }

    /**
     * Get the plugin description.
     *
     * @return string
     */
    public function get_description(): string {
        return __( 'Provides Font Awesome enqueueing in Admin, Frontend, Login Page, icon picking, and styling APIs for ModPress.', 'modpress' );
    }

    /**
     * Get the plugin URI.
     *
     * @return string
     */
    public function get_uri(): string {
        return 'https://trilb.dev/collection/web-extension/wordpress/modpress';
    }

    /**
     * Get the plugin license.
     *
     * @return string
     */
    public function get_license(): string {
        return 'GPL-2.0-or-later';
    }

    /**
     * Check if the plugin is active.
     *
     * @return bool
     */
    public function is_active(): bool {
        return true;
    }

    /**
     * Register plugin settings.
     *
     * @return void
     */
    public function register_settings(): void {
        Includes::get_instance()->settings()->register();
    }

    /**
     * Get the settings page config.
     *
     * @return array
     */
    public function get_settings_page(): array {
        return Includes::get_instance()->settings()->get_settings_page();
    }

    /**
     * Sanitize plugin settings.
     *
     * @param mixed $input
     * @return array
     */
    public function sanitize_settings( $input ): array {
        return Includes::get_instance()->settings()->sanitize( $input );
    }

    /**
     * Register plugin assets.
     *
     * @return void
     */
    public function register_assets(): void {
        ( new Assets() )->register();
    }

    /**
     * Load the text domain.
     *
     * @return void
     */
    public function load_textdomain(): void {
        I18n::load_textdomain();
    }

    /**
     * Check whether Font Awesome is available.
     *
     * @return bool
     */
    public function is_available(): bool {
        return self::is_wordpress_fontawesome_active() || ( function_exists( 'FortAwesome\fa' ) && class_exists( '\FortAwesome\FontAwesome' ) );
    }

    /**
     * Determine whether a FontAwesome instance is already present.
     *
     * WordPress FontAwesome wins first. ModPress does not load the bundled
     * composer copy when the official plugin is active.
     *
     * @return bool
     */
    public static function is_wordpress_fontawesome_active(): bool {
        if ( function_exists( 'FortAwesome\fa' ) || class_exists( '\FortAwesome\FontAwesome' ) || class_exists( '\FortAwesome\FontAwesome_Loader' ) ) {
            return true;
        }

        if ( defined( 'FONTAWESOME_PLUGIN_FILE' ) && function_exists( 'is_plugin_active' ) ) {
            return is_plugin_active( FONTAWESOME_PLUGIN_FILE );
        }

        return false;
    }

    /**
     * Get the IconPicker instance.
     *
     * @return IconPicker|null
     */
    public function get_icon_picker(): ?IconPicker {
        return $this->icon_picker;
    }

    /**
     * Get the singleton instance.
     *
     * @return self
     */
    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    /**
     * ModPress does not initialize the bundled vendor copy.
     *
     * @return void
     */
    public static function ensure_vendor_initialized(): void {
        return;
    }

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct() {
        Activator::register( static function (): void {
            // Intentionally empty: ModPress reuses the active WordPress
            // Font Awesome plugin and never loads the bundled vendor copy.
        } );
    }

    /**
     * No bundled vendor fallback is allowed.
     *
     * @return void
     */
    private function load_vendor(): void {
        return;
    }

    /**
     * Initialize the FontAwesome plugin.
     *
     * @return void
     */
    public function init(): void {
        self::ensure_vendor_initialized();
        FontAwesomeAPI::configure();

        if ( $this->is_available() ) {
            $this->icon_picker = IconPicker::get_instance();
        }

        Includes::get_instance()->init();
    }
}
