<?php
/**
 * Admin class for ModPress plugin.
 *
 * @package ModPress
 * @subpackage Admin
 * @since 1.0.0
 * 
 */
namespace ModPress\Admin;

use ModPress\Includes\Settings\Settings;
use ModPress\Includes\Functions\Admin\FunctionsPlugins;
use ModPress\Includes\Functions\Admin\FunctionsMod;
use ModPress\Includes\Functions\Helpers\AjaxHelper;
use ModPress\Includes\Core\Capabilities;
use ModPress\Includes\Functions\Helpers\LoaderHelper;
use ModPress\Includes\Functions\Admin\FunctionsSidebar;
use ModPress\Assets\Assets;
use ModPress\Admin\Manager\Tools\ToolsManager;
use ModPress\Admin\Manager\Dashboard\DashboardManager;
use ModPress\Admin\Manager\Settings\SettingsManager;
use ModPress\Admin\Manager\Mods\ModManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Admin {
    /**
     * The DashboardManager instance for managing the dashboard page. 
     * 
     * @var DashboardManager
     * */
    private DashboardManager $dashboard_manager;
    /**
     * ModManager instance for managing content-related admin pages.
     *
     * @var ModManager
     */
    private ModManager $mod_manager;
    /**
     * SettingsManager instance for managing settings-related admin pages.
     *
     * @var SettingsManager
     */
    private SettingsManager $settings_manager;
    /**
    * ToolsManager instance for managing tools-related admin pages.
     *
    * @var ToolsManager
     */
    private ToolsManager $tools_manager;
    /**
     * LoaderHelper instance for managing action and filter hooks.
     *
     * @var LoaderHelper
     */
    private LoaderHelper $loader;
    /**
     * FunctionsPlugins instance for managing plugin-related admin functions.
     *
     * @var FunctionsPlugins
     */
    private FunctionsPlugins $plugin_functions;
    /** 
     * Mod functions instance for managing mod-related admin functions.
     * 
     * @var FunctionsMod
     *  */
    private FunctionsMod $mod_functions;

    public function __construct( Assets $assets ) {
        $this->dashboard_manager = new DashboardManager();
        $this->mod_functions = new FunctionsMod();
        $this->mod_manager = new ModManager( $this->mod_functions );
        $this->settings_manager = new SettingsManager();
        $this->tools_manager = new ToolsManager();
        $this->plugin_functions = new FunctionsPlugins();
        $this->loader = new LoaderHelper();
        $this->dashboard_manager->register_assets( $assets );
        $this->mod_manager->register_assets( $assets );
        $this->settings_manager->register_assets( $assets );
        $this->tools_manager->register_assets( $assets );
        $this->loader->register_component( $this, [
            [ 'type' => 'action', 'hook' => 'wp_ajax_modpress_load_settings_tab', 'callback' => 'load_settings_tab' ],
        ] );
        $this->loader->register_component( $this->mod_functions, [
            [ 'type' => 'action', 'hook' => 'wp_ajax_modpress_save_mod_settings', 'callback' => 'save_mod_settings' ],
            [ 'type' => 'action', 'hook' => 'wp_ajax_modpress_delete_mod', 'callback' => 'delete_mod' ],
            [ 'type' => 'action', 'hook' => 'wp_ajax_modpress_delete_mod_page', 'callback' => 'delete_mod_page' ],
            [ 'type' => 'action', 'hook' => 'wp_ajax_modpress_save_mod_term', 'callback' => 'save_mod_term' ],
            [ 'type' => 'action', 'hook' => 'wp_ajax_modpress_delete_mod_term', 'callback' => 'delete_mod_term' ],
        ] );
        $this->loader->register_component( $this->plugin_functions, [
            [ 'type' => 'action', 'hook' => 'wp_ajax_modpress_toggle_plugin', 'callback' => 'toggle_plugin' ],
            [ 'type' => 'action', 'hook' => 'wp_ajax_modpress_save_plugin_settings', 'callback' => 'save_plugin_settings' ],
        ] )->run();
    }
    /**
     * Register admin menu pages and subpages.
     * @since 1.0.0
     */
    public function register_admin_menu(): void {
        FunctionsSidebar::register_admin_menu( $this );
    }

    /**
     * Render the dashboard page.
     *
     * This method is responsible for rendering the dashboard page of the ModPress plugin.
     * It delegates the rendering to the DashboardManager instance.
     */
    public function render_dashboard(): void {
        $this->dashboard_manager->render();
    }
    /**
     * Render the manage mods page.
     *
     * This method is responsible for rendering the manage mods page of the ModPress plugin.
     * It delegates the rendering to the ModManager instance.
     */
    public function render_mods(): void {
        $this->mod_manager->render();
    }
    /**
     * Render the settings page.
     *
     * This method is responsible for rendering the settings page of the ModPress plugin.
     * It delegates the rendering to the SettingsManager instance.
     */
    public function render_settings(): void {
        $this->settings_manager->render();
    }
    /**
     * Render the tools page.
     *
     * @return void
     */
    public function render_tools(): void {
        $this->tools_manager->render();
    }
    /**
     * Render the analytics page.
     *
     * This method is responsible for rendering the analytics page of the ModPress plugin.
     * It delegates the rendering to the AnalyticsManager instance.
     */
    public function load_settings_tab(): void {
        $tab = sanitize_key( $_POST['tab'] ?? 'general' );
        $view_capability = [
            'general' => 'modpress_settings_general_view',
            'layout' => 'modpress_settings_layout_view',
            'access' => 'modpress_settings_access_view',
            'plugins' => 'modpress_settings_plugins_view',
            'third-party' => 'modpress_settings_plugins_ext_view',
        ][ $tab ] ?? 'modpress_settings_general_view';
        if ( ! AjaxHelper::authorized( 'modpress_settings_tabs', $view_capability ) ) {
            AjaxHelper::unauthorized( __( 'You are not authorized to load ModPress settings.', 'modpress' ) );
        }

        $layout_section = sanitize_key( $_POST['layout_section'] ?? 'general' );
        ob_start();
        $this->settings_manager->render_tab_content( $tab, $layout_section );
        $html = (string) ob_get_clean();
        AjaxHelper::success( [ 'html' => $html, 'tab' => $tab, 'layout_section' => $layout_section ] );
    }

    /**
     * Get the capability for a given key, with a fallback.
     *
     * @param string $key The settings key to retrieve the capability for.
     * @param string $fallback The fallback capability if the key is not set or invalid.
     * @return string The capability associated with the key, or the fallback if not valid.
     */
    public function capability( string $key, string $fallback ): string {
        $value = Settings::get( $key, $fallback );
        $values = is_array( $value ) ? $value : [ $value ];
        $allowed = array_merge( [ 'manage_options', 'edit_posts', 'publish_posts', 'manage_categories', 'delete_posts' ], array_keys( Capabilities::definitions() ) );
        foreach ( $values as $value ) {
            $capability = sanitize_key( (string) $value );
            if ( in_array( $capability, $allowed, true ) ) {
                return $capability;
            }
        }
        return $fallback;
    }

}
