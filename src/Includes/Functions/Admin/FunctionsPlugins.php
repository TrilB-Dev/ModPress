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
use ModPress\Includes\Settings\Settings;

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

        $slug = $this->resolve_plugin_slug( wp_unslash( $_POST['slug'] ?? '' ) );
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
        try {
            $slug = $this->resolve_plugin_slug( wp_unslash( $_POST['slug'] ?? $_POST['plugin_slug'] ?? '' ) );
            if ( '' === $slug ) {
                $message = __( 'The ModPress plugin slug is missing.', 'modpress' );
                AjaxHelper::error( [ 'message' => $message, 'alert' => AlertHelper::get_admin_notice( $message, 'error' ) ], 400 );
            }

            $plugin = Plugins::get_instance()->get_registered_plugins()[ $slug ] ?? null;
            if ( ! $plugin instanceof PluginInterface || ! $plugin instanceof SettingsPageProviderInterface ) {
                $message = __( 'The requested ModPress plugin settings were not found.', 'modpress' );
                AjaxHelper::error( [ 'message' => $message, 'alert' => AlertHelper::get_admin_notice( $message, 'error' ) ], 404 );
            }

            $capability = $this->is_internal_plugin( $plugin ) ? 'modpress_settings_plugins_int_edit' : 'modpress_settings_plugins_ext_edit';
            $nonce_actions = array( 'modpress_save_plugin_settings', 'modpress_plugin_settings' );
            $has_valid_nonce = false;
            foreach ( $nonce_actions as $action ) {
                if ( AjaxHelper::authorized( $action, $capability ) ) {
                    $has_valid_nonce = true;
                    break;
                }
            }

            if ( ! $has_valid_nonce ) {
                $message = __( 'You are not authorized to save ModPress plugin settings.', 'modpress' );
                AjaxHelper::error( [ 'message' => $message, 'alert' => AlertHelper::get_admin_notice( $message, 'error' ) ], 403 );
            }

            $input = $this->parse_settings_payload();
            if ( ! is_array( $input ) ) {
                $message = __( 'The ModPress plugin settings payload is invalid.', 'modpress' );
                AjaxHelper::error( [ 'message' => $message, 'alert' => AlertHelper::get_admin_notice( $message, 'error' ) ], 400 );
            }

            $settings = $plugin->sanitize_settings( $input );
            if ( ! is_array( $settings ) ) {
                $message = __( 'The ModPress plugin settings could not be sanitized.', 'modpress' );
                AjaxHelper::error( [ 'message' => $message, 'alert' => AlertHelper::get_admin_notice( $message, 'error' ) ], 400 );
            }

            $page = $plugin->get_settings_page();
            $group = sanitize_key( $page['slug'] ?? $slug );
            if ( '' === $group ) {
                $message = __( 'The ModPress plugin settings group could not be resolved.', 'modpress' );
                AjaxHelper::error( [ 'message' => $message, 'alert' => AlertHelper::get_admin_notice( $message, 'error' ) ], 400 );
            }

            if ( ! Settings::set_group( $group, $settings ) ) {
                $message = __( 'The ModPress plugin settings could not be saved.', 'modpress' );
                AjaxHelper::error( [ 'message' => $message, 'alert' => AlertHelper::get_admin_notice( $message, 'error' ) ], 500 );
            }

            AjaxHelper::success(
                [
                    'slug' => $slug,
                    'settings' => $settings,
                    'message' => __( 'Plugin settings saved successfully.', 'modpress' ),
                    'alert' => AlertHelper::get_admin_notice( __( 'Plugin settings saved successfully.', 'modpress' ), 'success' ),
                ]
            );
        } catch ( \Throwable $exception ) {
            $message = __( 'The ModPress plugin settings save failed unexpectedly.', 'modpress' );
            AjaxHelper::error(
                [
                    'message' => $message,
                    'alert' => AlertHelper::get_admin_notice( $message, 'error' ),
                    'details' => $exception->getMessage(),
                ],
                500
            );
        }
    }

    /**
     * Parse a settings payload from either JSON or bracket-notation form fields.
     *
     * @return array<string, mixed>
     */
    private function parse_settings_payload(): array {
        $candidates = array(
            $_POST['settings'] ?? null,
            $_POST['plugin_settings'] ?? null,
        );

        foreach ( $candidates as $candidate ) {
            if ( is_string( $candidate ) ) {
                $decoded = json_decode( wp_unslash( $candidate ), true );
                if ( is_array( $decoded ) ) {
                    return $this->normalize_plugin_settings_input( wp_unslash( $decoded ) );
                }
            }

            if ( is_array( $candidate ) ) {
                return $this->normalize_plugin_settings_input( wp_unslash( $candidate ) );
            }
        }

        $parsed = array();
        foreach ( $_POST as $key => $value ) {
            if ( ! is_string( $key ) ) {
                continue;
            }

            if ( 0 === strpos( $key, 'settings[' ) || 0 === strpos( $key, 'plugin_settings[' ) ) {
                $bracket_index = strrpos( $key, '[' );
                if ( false === $bracket_index ) {
                    continue;
                }

                $trail = substr( $key, $bracket_index + 1, -1 );
                if ( '' !== $trail ) {
                    $parsed[ $trail ] = $value;
                }
            }
        }

        return $this->normalize_plugin_settings_input( $parsed );
    }

    /**
     * Normalize plugin settings payloads from either settings[] or plugin_settings[] posts.
     *
     * @param array $input Submitted settings payload.
     * @return array<string, mixed>
     */
    private function normalize_plugin_settings_input( array $input ): array {
        $normalized = array();

        foreach ( $input as $key => $value ) {
            if ( is_array( $value ) ) {
                $normalized[ $key ] = $this->normalize_plugin_settings_input( $value );
                continue;
            }

            $normalized[ $key ] = $value;
        }

        return $normalized;
    }

    /**
     * Resolve a submitted plugin slug against the registry without losing the original hyphenated key.
     *
     * @param string $slug Submitted slug value.
     * @return string The matching registered slug or a sanitized fallback.
     */
    private function resolve_plugin_slug( string $slug ): string {
        $candidate = trim( (string) $slug );
        if ( '' === $candidate ) {
            return '';
        }

        $registered = Plugins::get_instance()->get_registered_plugins();
        if ( isset( $registered[ $candidate ] ) ) {
            return $candidate;
        }

        $normalized = $this->canonical_slug( $candidate );
        foreach ( $registered as $key => $plugin ) {
            if ( ! $plugin instanceof PluginInterface ) {
                continue;
            }

            if ( $this->canonical_slug( (string) $plugin->get_slug() ) === $normalized ) {
                return (string) $key;
            }
        }

        return $candidate;
    }

    /**
     * Normalize a plugin slug for comparison by removing separators and case.
     *
     * @param string $slug Slug to normalize.
     * @return string
     */
    private function canonical_slug( string $slug ): string {
        $slug = strtolower( trim( (string) $slug ) );
        $slug = str_replace( array( '-', '_', ' ' ), '', $slug );

        return preg_replace( '/[^a-z0-9]/', '', $slug ) ?: '';
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
