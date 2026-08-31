<?php
/**
 * Font Awesome plugin integration for PuginName.
 *
 * @package FontAwesome
 * @textdomain modpress
 * @domainpath Languages
 * @author PuginName Team
 */

namespace PuginName\Includes\Plugins\FontAwesome;

use PuginName\Includes\Plugins\AssetsProviderInterface;
use PuginName\Includes\Plugins\I18nProviderInterface;
use PuginName\Includes\Plugins\PluginInterface;
use PuginName\Includes\Plugins\SettingsProviderInterface;
use PuginName\Includes\Plugins\SettingsPageProviderInterface;
use PuginName\Includes\Plugins\FontAwesome\Assets\Assets;
use PuginName\Includes\Plugins\FontAwesome\Includes\IconPicker;
use PuginName\Includes\Plugins\FontAwesome\API\FontAwesomeAPI;
use PuginName\Includes\Plugins\FontAwesome\Includes\I18n;
use PuginName\Includes\Plugins\FontAwesome\Includes\Includes;
use PuginName\Includes\Core\WP\Activator;

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
     * Check if the FontAwesome library is available.
     *
     * @return bool True if the FontAwesome library is available, false otherwise.
     */
    public function get_slug(): string {
        return 'modpress-fontawesome';
    }
    /**
     * Get the plugin name.
     *
     * @return string The plugin name.
     */
    public function get_name(): string {
        return 'FontAwesome';
    }
    /**
     * Get the plugin version.
     *
     * @return string The plugin version.
     */
    public function get_version(): string {
        return MODPRESS_VERSION;
    }
    /**
     * Get the plugin author.
     *
     * @return string The plugin author.
     */
    public function get_author(): string {
        return 'PuginName Team';
    }
    /**
     * Get the plugin author URI.
     *
     * @return string The plugin author URI.
     */
    public function get_author_uri(): string {
        return 'https://trilb.dev';
    }
    /**
     * Get the plugin description.
     *
     * @return string The plugin description.
     */
    public function get_description(): string {
        return __( 'Provides Font Awesome loading, icon picking, and styling APIs for PuginName.', 'modpress' );
    }
    /**
     * Get the plugin URI.
     *
     * @return string The plugin URI.
     */
    public function get_uri(): string {
        return 'https://modpress.dev';
    }
    /**
     * Get the plugin license.
     *
     * @return string The plugin license.
     */
    public function get_license(): string {
        return 'GPL-2.0-or-later';
    }
    /**
     * Check if the plugin is active.
     *
     * @return bool True if the plugin is active, false otherwise.
     */
    public function is_active(): bool {
        return true;
    }
    /**
     * Register the plugin's settings.
     *
     * This method registers the settings for the FontAwesome plugin using the
     * Settings class.
     */
    public function register_settings(): void {
        Includes::get_instance()->settings()->register();
    }
    /**
     * Get the settings page for the plugin.
     *
     * @return array The settings page configuration.
     */
    public function get_settings_page(): array {
        return Includes::get_instance()->settings()->get_settings_page();
    }
    /**
     * Sanitize the plugin's settings.
     *
     * @param mixed $input The input settings to sanitize.
     * @return array The sanitized settings.
     */
    public function sanitize_settings( $input ): array {
        return Includes::get_instance()->settings()->sanitize( $input );
    }
    /**
     * Register the plugin's assets.
     *
     * This method registers the assets for the FontAwesome plugin using the
     * Assets class.
     */
    public function register_assets(): void {
        ( new Assets() )->register();
    }
    /**
     * Load the plugin's text domain for internationalization.
     *
     * This method loads the text domain for the FontAwesome plugin using the
     * I18n class.
     */
    public function load_textdomain(): void {
        I18n::load_textdomain();
    }
    /**
     * Check if the FontAwesome library is available.
     *
     * @return bool True if the FontAwesome library is available, false otherwise.
     */
    public function is_available(): bool {
        return function_exists( 'FortAwesome\\fa' ) && class_exists( '\\FortAwesome\\FontAwesome' );
    }
    /**
     * Get the IconPicker instance for the FontAwesome plugin.
     *
     * @return IconPicker|null The IconPicker instance, or null if not available.
     */
    public function get_icon_picker(): ?IconPicker {
        return $this->icon_picker;
    }
    /**
     * Get the singleton instance of the FontAwesome plugin.
     *
     * @return self The singleton instance of the FontAwesome plugin.
     */
    public static function get_instance(): self {
        return self::$instance ??= new self();
    }
    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct() {
        $this->load_vendor();
        Activator::register( static function (): void {
            if ( class_exists( '\\FortAwesome\\FontAwesome_Loader' ) ) {
                \FortAwesome\FontAwesome_Loader::initialize();
            }
        } );
    }

    /**
     * Loads the FontAwesome package as part of this internal plugin.
     */
    private function load_vendor(): void {
        $vendor_file = MODPRESS_DIR . 'vendor/fortawesome/wordpress-fontawesome/index.php';
        if ( is_readable( $vendor_file ) ) {
            require_once $vendor_file;
        }
    }
    /**
     * Initialize the FontAwesome plugin.
     *
     * This method configures the FontAwesome API and initializes the IconPicker
     * if the FontAwesome library is available. It also initializes the Includes
     * class for additional functionality.
     */
    public function init(): void {
        FontAwesomeAPI::configure();

        if ( $this->is_available() ) {

            $this->icon_picker = IconPicker::get_instance();

        }

        Includes::get_instance()->init();
    }
}
