<?php
/**
 * ModPress Plugin interface for all plugins.
 *
 * @package ModPress
 * @subpackage Includes\Plugins
 */
namespace ModPress\Includes\Plugins;

/**
 * Plugin interface for all plugins.
 */
interface PluginInterface {
    /**
     * Get the plugin slug.
     *
     * @return string The plugin slug.
     */
    public function get_slug(): string;
    /**
     * Get the plugin name.
     *
     * @return string The plugin name.
     */
    public function get_name(): string;
    /**
     * Get the plugin version.
     *
     * @return string The plugin version.
     */
    public function get_version(): string;
    /**
     * Get the plugin author.
     *
     * @return string The plugin author.
     */
    public function get_author(): string;
    /**
     * Get the plugin author URI.
     *
     * @return string The plugin author URI.
     */
    public function get_author_uri(): string;
    /**
     * Get the plugin description.
     *
     * @return string The plugin description.
     */
    public function get_description(): string;
    /**
     * Get the plugin URI.
     *
     * @return string The plugin URI.
     */
    public function get_uri(): string;
    /**
     * Get the plugin license.
     *
     * @return string The plugin license.
     */
    public function get_license(): string;
    /**
     * Check if the plugin is active.
     *
     * @return bool True if the plugin is active, false otherwise.
     */
    public function is_active(): bool;
    /**
     * Initialize the plugin.
     *
     * @return void
     */
    public function init(): void;
}
/**
 * Setting provider interface for plugins.
 * @since 1.0.0
 */
interface SettingsProviderInterface {
    /**
     * Register settings for the plugin.
     *
     * @return void
     */
    public function register_settings(): void;
}

/**
 * Provides an automatically generated settings tab for a ModPress plugin.
 * @since 1.0.0
 */
interface SettingsPageProviderInterface {
    /**
     * Returns the plugin settings tab and field definitions.
     *
    * The returned array may include `layout` with a value of `table` or
    * `box` to control the presentation of the plugin settings modal.
    *
    * @return array<string, mixed>
     */
    public function get_settings_page(): array;

    /**
     * Sanitizes and persists the plugin settings.
     *
     * @param mixed $input Submitted settings.
     * @return array<string, mixed>
     */
    public function sanitize_settings( $input ): array;
}
/**
 * Shortcode provider interface for plugins.
 * @since 1.0.0
 */
interface DatabaseProviderInterface {
    /**
     * Register database tables for the plugin.
     *
     * @return void
     */
    public function register_tables(): void;
}
/**
 * Provides shortcode definitions for a ModPress extension.
 * @since 1.0.0
 */
interface ShortcodeProviderInterface {
    /**
     * Return definitions created with ShortcodeHelper::define().
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_shortcodes(): array;
}
/**
 * Assets provider interface for plugins.
 * @since 1.0.0
 */
interface AssetsProviderInterface {
    /**
     * Register assets for the plugin.
     *
     * @return void
     */
    public function register_assets(): void;
}
/**
 * Admin provider interface for plugins.
 * @since 1.0.0
 */
interface AdminPageProviderInterface {
    /**
     * Register admin pages for the plugin.
     *
     * @return void
     */
    public function register_admin_pages(): void;
}
/**
 * Provides ModPress admin menu definitions for a plugin.
 *
 * Definitions may use `type => menu` for a new top-level menu or provide a
 * `parent` slug for a submenu under an existing ModPress menu.
 * @since 1.0.0
 */
interface AdminMenuProviderInterface extends PluginInterface {
    /**
     * Return admin menu definitions.
     *
     * Each definition supports `page_title`, `menu_title`, `capability`,
     * `menu_slug`, `callback`, optional `icon`, `position`, `parent`, and
     * `children` for top-level menu definitions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_admin_menu(): array;
}
/**
 * Provides entries for the ModPress in-page admin sidebar.
 * @since 1.0.0
 */
interface AdminSidebarProviderInterface extends PluginInterface {
    /**
     * Return sidebar item and group definitions.
     *
     * An item uses `type => item` and a `parent` of `manage-mod`, `settings`,
     * or `tools`. A group uses `type => group` and an `items` array.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_admin_sidebar(): array;
}
/**
 * Rest Rout provider interface for plugins.
 * @since 1.0.0
 */
interface RestRouteProviderInterface {
    /**
     * Register REST API routes for the plugin.
     *
     * @return void
     */
    public function register_rest_routes(): void;
}
/**
 * Frontend provider interface for plugins.
 * @since 1.0.0
 */
interface FrontendProviderInterface {
    /**
     * Register frontend functionality for the plugin.
     *
     * @return void
     */
    public function register_frontend(): void;
}
/**
 * I18n provider interface for plugins.
 * @since 1.0.0
 */
interface I18nProviderInterface {
    public function load_textdomain(): void;
}