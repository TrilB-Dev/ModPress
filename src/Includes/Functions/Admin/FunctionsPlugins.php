<?php
/**
 * Plugin-related admin functions for ModPress.
 *
 * @package ModPress
 * @subpackage Includes\Functions\Admin
 * @since 1.0.0
 */
namespace ModPress\Includes\Functions\Admin;

use ModPress\Includes\Functions\Helpers\AjaxHelper;
use ModPress\Includes\Functions\Helpers\AlertHelper;
use ModPress\Includes\Plugins\PluginInterface;
use ModPress\Includes\Plugins\Plugins;
use ModPress\Includes\Plugins\SettingsPageProviderInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class FunctionsPlugins {
    /**
     * Toggle the enabled state of a ModPress plugin.
     *
     * @return void
     */
    public function toggle_plugin(): void {
        if ( ! AjaxHelper::authorized( 'modpress_plugin_toggle', 'modpress_settings_plugins_int_edit' ) ) {
            AjaxHelper::unauthorized( __( 'You are not authorized to manage ModPress plugins.', 'modpress' ) );
        }

        $slug = sanitize_key( wp_unslash( $_POST['slug'] ?? '' ) );
        $enabled = ! empty( $_POST['enabled'] );
        $plugin = Plugins::get_instance()->get_registered_plugins()[ $slug ] ?? null;

        if ( ! $plugin instanceof PluginInterface ) {
            AjaxHelper::error( [ 'message' => __( 'The requested ModPress plugin was not found.', 'modpress' ) ], 404 );
        }
		if ( ! $this->is_internal_plugin( $plugin ) ) {
			AjaxHelper::unauthorized( __( 'You are not authorized to manage external ModPress plugins.', 'modpress' ) );
		}

        if ( ! Plugins::get_instance()->set_plugin_enabled( $slug, $enabled ) ) {
            AjaxHelper::error( [ 'message' => __( 'The ModPress plugin state could not be saved.', 'modpress' ) ], 500 );
        }

        AjaxHelper::success( [ 'slug' => $slug, 'enabled' => $enabled ] );
    }

    /**
     * Save settings submitted from a ModPress plugin modal.
     *
     * @return void
     */
    public function save_plugin_settings(): void {
        $slug = sanitize_key( wp_unslash( $_POST['slug'] ?? '' ) );
        $plugin = Plugins::get_instance()->get_registered_plugins()[ $slug ] ?? null;
        if ( ! $plugin instanceof PluginInterface || ! $plugin instanceof SettingsPageProviderInterface ) {
            $message = __( 'The requested ModPress plugin settings were not found.', 'modpress' );
            AjaxHelper::error( [ 'message' => $message, 'alert' => AlertHelper::get_admin_notice( $message, 'error' ) ], 404 );
        }
        $capability = $this->is_internal_plugin( $plugin ) ? 'modpress_settings_plugins_int_edit' : 'modpress_settings_plugins_ext_edit';
        if ( ! AjaxHelper::authorized( 'modpress_plugin_settings', $capability ) ) {
            $message = __( 'You are not authorized to save ModPress plugin settings.', 'modpress' );
            AjaxHelper::error( [ 'message' => $message, 'alert' => AlertHelper::get_admin_notice( $message, 'error' ) ], 403 );
        }

        $input = isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : [];
        $settings = $plugin->sanitize_settings( $input );

        AjaxHelper::success(
            [
                'slug' => $slug,
                'settings' => $settings,
                'message' => __( 'Plugin settings saved successfully.', 'modpress' ),
                'alert' => AlertHelper::get_admin_notice( __( 'Plugin settings saved successfully.', 'modpress' ), 'success' ),
            ]
        );
    }

    private function is_internal_plugin( PluginInterface $plugin ): bool {
        return 0 === strpos( get_class( $plugin ), 'ModPress\\Includes\\Plugins\\' );
    }

    /**
     * Collect settings pages from enabled ModPress plugins.
     *
     * @return array<int, array{provider: SettingsPageProviderInterface, slug: string, label: string, title: string, fields: array}>
     */
    public function plugin_settings_pages(): array {
        $pages = [];
        foreach ( Plugins::get_instance()->get_registered_plugins() as $plugin ) {
            if ( ! $plugin instanceof PluginInterface || ! $plugin instanceof SettingsPageProviderInterface || ! Plugins::get_instance()->is_plugin_enabled( $plugin->get_slug() ) ) {
                continue;
            }

            $page = $plugin->get_settings_page();
            if ( empty( $page['slug'] ) || empty( $page['label'] ) || empty( $page['fields'] ) ) {
                continue;
            }

            $page['provider'] = $plugin;
            $pages[] = $page;
        }
        return $pages;
    }
}
