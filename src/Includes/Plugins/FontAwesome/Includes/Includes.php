<?php
/**
 * 
 * @since 1.0.0
 */
namespace ModPress\Includes\Plugins\FontAwesome\Includes;

use ModPress\Includes\Plugins\FontAwesome\Includes\Settings\Settings;
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and public-facing site hooks.
 *
 * @since 1.0.0
 */
final class Includes {
    /**
     * The single instance of the class.
     *
     * @var self|null
     */
    private static ?self $instance = null;
    /**
     * The settings instance.
     *
     * @var Settings
     */
    private Settings $settings;
    /**
     * Private constructor to prevent direct instantiation.
     * @since 1.0.0
     */
    private function __construct() {
        $this->settings = new Settings();
    }
    /**
     * Returns the single instance of the class.
     *
     * @return self The single instance of the class.
     */
    public static function get_instance(): self {
        return self::$instance ??= new self();
    }
    /**
     * Initializes the plugin.
     *
     * This method is called to set up the plugin's functionality.
     */
    public function init(): void {}
    /**
     * Returns the settings instance.
     *
     * @return Settings The settings instance.
     */
    public function settings(): Settings {
        return $this->settings;
    }
}