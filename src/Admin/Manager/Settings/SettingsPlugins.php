<?php
/**
 * SettingsPlugins class for ModPress plugin.
 *
 * @package ModPress
 * @subpackage Admin\Manager\Settings
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\Settings;

use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\Functions\Helpers\SanitizationHelper;
use ModPress\Includes\Settings\Settings;
use ModPress\Includes\Plugins\Plugins;
use ModPress\Includes\Plugins\PluginInterface;
use ModPress\Includes\Plugins\SettingsPageProviderInterface;

final class SettingsPlugins {
    /**
     * Check if a settings page exists for the given slug.
     *
     * @param string $slug The slug of the settings page.
     * @return bool True if the settings page exists, false otherwise.
     */
    public function has_settings_page( string $slug ): bool {
        return isset( $this->settings_pages()[ $slug ] );
    }
    /**
     * Render the settings page for the given slug.
     *
     * @param string $slug The slug of the settings page.
     * @param array $values The current values of the settings.
     */
    public function render_settings_page( string $slug, array $values ): void {
        $page = $this->settings_pages()[ $slug ] ?? null;
        if ( ! is_array( $page ) ) {
            return;
        }

        echo '<tr><th scope="row">' . esc_html( $page['title'] ?? $page['label'] ) . '</th><td>';
        foreach ( $page['fields'] as $field ) {
            $key = SanitizationHelper::key( $field['key'] ?? '' );
            if ( '' === $key ) {
                continue;
            }
            $default = array_key_exists( 'default', $field ) ? $field['default'] : false;
            $name = 'modpress_' . SanitizationHelper::key( $page['slug'] ) . '[' . $key . ']';
            $value = $values[ $key ] ?? $default;
            $type = SanitizationHelper::key( $field['type'] ?? 'checkbox', 'checkbox' );
            echo '<div class="mb-3">' . FormFieldHelper::label(
                'modpress-' . $key,
                (string) ( $field['label'] ?? $key ),
                [
                    'description' => (string) ( $field['description'] ?? '' ),
                    'tooltip' => (string) ( $field['tooltip'] ?? '' ),
                    'tooltip_type' => SanitizationHelper::key( $field['tooltip_type'] ?? 'question', 'question' ),
                    'tooltip_icon' => (string) ( $field['tooltip_icon'] ?? '' ),
                ]
            );
            if ( 'select' === $type ) {
                echo FormFieldHelper::select( $name, (array) ( $field['options'] ?? [] ), $value, [ 'id' => 'modpress-' . $key ] );
            } elseif ( 'text' === $type ) {
                echo FormFieldHelper::input( $name, is_scalar( $value ) ? (string) $value : '', [ 'id' => 'modpress-' . $key, 'type' => 'text' ] );
            } else {
                echo FormFieldHelper::checkbox( $name, '1', '', [ 'id' => 'modpress-' . $key, 'checked' => ! empty( $value ) ] );
            }
            echo '</div>';
        }
        echo '</td></tr>';
    }
    /**
     * Render the settings page for the given tab.
     *
     * @param string $tab The tab to render.
     */
    public function render( string $tab ): void {
        if ( 'third-party' === $tab ) {
            $this->render_third_party_plugins();
            return;
        }

        $this->render_modpress_plugins();
    }
    /**
     * Get the registered settings pages from enabled plugins.
     *
     * @return array An associative array of registered settings pages.
     */
    private function settings_pages(): array {
        $pages = [];
        foreach ( Plugins::get_instance()->get_registered_plugins() as $plugin ) {
            if ( ! $plugin instanceof PluginInterface || ! $plugin instanceof SettingsPageProviderInterface || ! Plugins::get_instance()->is_plugin_enabled( $plugin->get_slug() ) ) {
                continue;
            }

            $page = $plugin->get_settings_page();
            if ( empty( $page['slug'] ) || empty( $page['label'] ) || empty( $page['fields'] ) ) {
                continue;
            }
            $pages[ SanitizationHelper::key( $page['slug'] ) ] = $page;
        }
        return $pages;
    }
    /**
     * Render the ModPress plugins section.
     * @since 1.0.0
     */
    private function render_modpress_plugins(): void {
        ?>
        <div class="row g-4">
            <?php foreach ( Plugins::get_instance()->get_registered_plugins() as $plugin ) : ?>
                <?php if ( $plugin instanceof PluginInterface && $this->can_view_plugin( $plugin ) ) : ?>
                    <?php $this->render_modpress_plugin_card( $plugin ); ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
    }
    /**
     * Render the third-party plugins section.
     * @since 1.0.0
     */
    private function render_third_party_plugins(): void {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        ?>
        <div class="row g-4">
            <?php foreach ( get_plugins() as $file => $plugin ) : ?>
                <?php if ( function_exists( 'plugin_basename' ) && plugin_basename( MODPRESS_FILE ) === $file ) { continue; } ?>
                <?php $this->render_third_party_plugin_card( $file, $plugin ); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }
    /**
     * Render a card for a third-party plugin.
     *
     * @param string $file The plugin file path.
     * @param array $plugin The plugin data.
     */
    private function render_modpress_plugin_card( $plugin ): void {
        $enabled = Plugins::get_instance()->is_plugin_enabled( $plugin->get_slug() );
        $settings_page = $plugin instanceof SettingsPageProviderInterface ? $plugin->get_settings_page() : [];
        $modal_id = SanitizationHelper::key( $plugin->get_slug() );
        $can_edit = $this->can_edit_plugin( $plugin );
        ?>
        <div class="col-12 col-md-6 col-xl-4 d-flex">
            <article class="card modpress-plugin-card shadow-sm h-100 w-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <?php /* translators: %s is the plugin name. */ ?>
                    <?php echo FormFieldHelper::switch( 'modpress-plugin-status', '1', '', [ 'id' => 'modpress-plugin-status-' . SanitizationHelper::key( $plugin->get_slug() ), 'checked' => $enabled, 'disabled' => ! $can_edit, 'data-modpress-plugin-toggle' => 'true', 'data-plugin-slug' => $plugin->get_slug(), 'aria-label' => sprintf( __( 'Enable %s', 'modpress' ), $plugin->get_name() ) ] ); ?>
                    <span class="fw-semibold"><?php echo esc_html( $plugin->get_name() ); ?></span>
                </div>
                <div class="card-body d-flex flex-column">
                    <span class="modpress-plugin-icon dashicons dashicons-admin-plugins" aria-hidden="true"></span>
                    <p class="card-text text-secondary mt-3"><?php echo esc_html( $plugin->get_description() ); ?></p>
                    <p class="card-text mb-2"><span class="text-secondary"><?php esc_html_e( 'Author:', 'modpress' ); ?></span> <?php echo esc_html( $plugin->get_author() ); ?></p>
                    <p class="card-text mb-2"><span class="text-secondary"><?php esc_html_e( 'Version:', 'modpress' ); ?></span> <?php echo esc_html( $plugin->get_version() ); ?></p>
                    <p class="card-text mb-3"><span class="text-secondary"><?php esc_html_e( 'Docs:', 'modpress' ); ?></span> <a href="<?php echo esc_url( $plugin->get_uri() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View documentation', 'modpress' ); ?></a></p>
                    <?php if ( ! empty( $settings_page['fields'] ) ) : ?>
                        <?php echo FormFieldHelper::button( __( 'Settings', 'modpress' ), [ 'type' => 'button', 'class' => 'btn-primary mt-auto', 'data-bs-toggle' => 'modal', 'data-bs-target' => '#' . $modal_id ] ); ?>
                    <?php endif; ?>
                </div>
            </article>
        </div>
        <?php

        if ( ! empty( $settings_page['fields'] ) ) {
            $this->render_plugin_settings_modal( $plugin, $settings_page, $modal_id, $can_edit );
        }
    }
    /**
     * Render a card for a third-party plugin.
     *
     * @param string $file The plugin file path.
     * @param array $plugin The plugin data.
     */
    private function render_plugin_settings_modal( PluginInterface $plugin, array $settings_page, string $modal_id, bool $can_edit ): void {
        $values = Settings::get_group( SanitizationHelper::key( $settings_page['slug'] ), [] ) ?? [];
        ?>
        <div class="modal fade modpress-plugin-settings-modal" id="<?php echo esc_attr( $modal_id ); ?>" tabindex="-1" aria-labelledby="<?php echo esc_attr( $modal_id . '-label' ); ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="<?php echo esc_attr( $modal_id . '-label' ); ?>"><?php echo esc_html( $plugin->get_name() ); ?></h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'modpress' ); ?>"></button>
                    </div>
                    <div class="modal-body">
                        <section class="modpress-plugin-modal-info mb-4" aria-labelledby="<?php echo esc_attr( $modal_id . '-info' ); ?>">
                            <h3 class="h6" id="<?php echo esc_attr( $modal_id . '-info' ); ?>"><?php esc_html_e( 'Plugin information', 'modpress' ); ?></h3>
                            <p class="text-secondary mb-3"><?php echo esc_html( $plugin->get_description() ); ?></p>
                            <dl class="row mb-0 small">
                                <dt class="col-sm-3 text-secondary"><?php esc_html_e( 'Author', 'modpress' ); ?></dt>
                                <dd class="col-sm-9"><?php echo esc_html( $plugin->get_author() ); ?></dd>
                                <dt class="col-sm-3 text-secondary"><?php esc_html_e( 'Version', 'modpress' ); ?></dt>
                                <dd class="col-sm-9"><?php echo esc_html( $plugin->get_version() ); ?></dd>
                                <dt class="col-sm-3 text-secondary"><?php esc_html_e( 'License', 'modpress' ); ?></dt>
                                <dd class="col-sm-9 mb-0"><?php echo esc_html( $plugin->get_license() ); ?></dd>
                            </dl>
                        </section>
                        <form class="modpress-plugin-settings-form" data-plugin-settings-form data-plugin-slug="<?php echo esc_attr( $plugin->get_slug() ); ?>" data-internal-mod-fields>
                            <h3 class="h6 mb-3"><?php echo esc_html( $settings_page['title'] ?? $settings_page['label'] ); ?></h3>
                            <fieldset <?php disabled( ! $can_edit ); ?>>
                                <?php $this->render_plugin_settings_fields( $settings_page, $values, $modal_id ); ?>
                            </fieldset>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'modpress' ); ?></button>
                        <?php if ( $can_edit ) : ?>
                            <button type="button" class="btn btn-primary" data-plugin-settings-save><?php esc_html_e( 'Save', 'modpress' ); ?></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function can_view_plugin( PluginInterface $plugin ): bool {
        $capability = $this->is_internal_plugin( $plugin ) ? 'modpress_settings_plugins_int_view' : 'modpress_settings_plugins_ext_view';
        return current_user_can( $capability );
    }

    private function can_edit_plugin( PluginInterface $plugin ): bool {
        $capability = $this->is_internal_plugin( $plugin ) ? 'modpress_settings_plugins_int_edit' : 'modpress_settings_plugins_ext_edit';
        return current_user_can( $capability );
    }

    private function is_internal_plugin( PluginInterface $plugin ): bool {
        return 0 === strpos( get_class( $plugin ), 'ModPress\\Includes\\Plugins\\' );
    }
    /**
     * Render the settings fields for a plugin's settings page.
     *
     * @param array $settings_page The settings page configuration.
     * @param array $values The current values of the settings.
     * @param string $prefix The prefix for the field IDs.
     */
    private function render_plugin_settings_fields( array $settings_page, array $values, string $prefix ): void {
        $layout = SanitizationHelper::key( $settings_page['layout'] ?? 'box', 'box' );
        $layout = in_array( $layout, [ 'table', 'box' ], true ) ? $layout : 'box';

        if ( 'table' === $layout ) {
            echo '<div class="modpress-plugin-settings-fields modpress-plugin-settings-fields-table"><table class="table align-middle"><tbody>';
        } else {
            echo '<div class="modpress-plugin-settings-fields modpress-plugin-settings-fields-box">';
        }

        foreach ( $settings_page['fields'] as $field ) {
            $key = SanitizationHelper::key( $field['key'] ?? '' );
            if ( '' === $key ) {
                continue;
            }

            $default = array_key_exists( 'default', $field ) ? $field['default'] : false;
            $value = $values[ $key ] ?? $default;
            $type = SanitizationHelper::key( $field['type'] ?? 'checkbox', 'checkbox' );
            $id = SanitizationHelper::key( $prefix . '-' . $key );
            $name = 'settings[' . $key . ']';
            $wrapper_attributes = [];
            if ( ! empty( $field['wrapper_class'] ) ) {
                $wrapper_attributes['class'] = (string) $field['wrapper_class'];
            }
            if ( ! empty( $field['wrapper_attributes'] ) && is_array( $field['wrapper_attributes'] ) ) {
                $wrapper_attributes = array_merge( $wrapper_attributes, $field['wrapper_attributes'] );
            }
            if ( ! empty( $field['visible_when'] ) && is_array( $field['visible_when'] ) ) {
                $wrapper_attributes['data-modpress-visible-when'] = wp_json_encode( $field['visible_when'] );
            }
            $wrapper_attributes = FormFieldHelper::attributes_to_string( $wrapper_attributes );
            $label = FormFieldHelper::label( $id, (string) ( $field['label'] ?? $key ), [
                'tooltip' => (string) ( $field['tooltip'] ?? '' ),
                'tooltip_type' => SanitizationHelper::key( $field['tooltip_type'] ?? 'question', 'question' ),
                'tooltip_icon' => (string) ( $field['tooltip_icon'] ?? '' ),
            ] );
            if ( 'table' === $layout ) {
                echo '<tr' . ( $wrapper_attributes ? ' ' . $wrapper_attributes : '' ) . '><th scope="row" class="w-50">' . wp_kses_post( $label ) . '</th><td>';
            } else {
                echo '<article class="modpress-plugin-settings-field card h-100"' . ( $wrapper_attributes ? ' ' . $wrapper_attributes : '' ) . '><div class="card-body">';
                echo '<div class="modpress-plugin-settings-field-header d-flex align-items-start justify-content-between gap-3">' . wp_kses_post( $label );
                if ( 'checkbox' === $type ) {
                    echo FormFieldHelper::switch( $name, '1', '', [ 'id' => $id, 'checked' => ! empty( $value ), 'wrapper_class' => 'ms-auto flex-shrink-0' ] );
                }
                echo '</div>';
                if ( ! empty( $field['description'] ) ) {
                    echo '<p class="modpress-plugin-settings-field-description text-secondary mb-3">' . esc_html( (string) $field['description'] ) . '</p>';
                }
            }
            if ( 'table' === $layout && 'select' === $type ) {
                echo FormFieldHelper::select( $name, (array) ( $field['options'] ?? [] ), $value, [ 'id' => $id, 'attributes' => $field['attributes'] ?? [] ] );
            } elseif ( 'table' === $layout && 'multiselect' === $type ) {
                echo FormFieldHelper::bootstrap_multiselect( $name, [ 'id' => $id, 'data' => (array) ( $field['options'] ?? [] ), 'selected' => (array) $value, 'dropup_auto' => $field['dropup_auto'] ?? true, 'show_tick' => $field['show_tick'] ?? null, 'selection_indicator' => $field['selection_indicator'] ?? null, 'attributes' => $field['attributes'] ?? [] ] );
            } elseif ( 'table' === $layout && 'text' === $type ) {
                echo FormFieldHelper::input( $name, is_scalar( $value ) ? (string) $value : '', [ 'id' => $id, 'type' => 'text' ] );
            } elseif ( 'table' === $layout ) {
                echo FormFieldHelper::checkbox( $name, '1', '', [ 'id' => $id, 'checked' => ! empty( $value ) ] );
            } elseif ( 'select' === $type ) {
                echo FormFieldHelper::select( $name, (array) ( $field['options'] ?? [] ), $value, [ 'id' => $id, 'attributes' => $field['attributes'] ?? [] ] );
            } elseif ( 'multiselect' === $type ) {
                echo FormFieldHelper::bootstrap_multiselect( $name, [ 'id' => $id, 'data' => (array) ( $field['options'] ?? [] ), 'selected' => (array) $value, 'dropup_auto' => $field['dropup_auto'] ?? true, 'show_tick' => $field['show_tick'] ?? null, 'selection_indicator' => $field['selection_indicator'] ?? null, 'attributes' => $field['attributes'] ?? [] ] );
            } elseif ( 'text' === $type ) {
                echo FormFieldHelper::input( $name, is_scalar( $value ) ? (string) $value : '', [ 'id' => $id, 'type' => 'text' ] );
            }
            echo 'table' === $layout ? '</td></tr>' : '</div></article>';
        }

        if ( 'table' === $layout ) {
            echo '</tbody></table></div>';
        } else {
            echo '</div>';
        }
    }
    /**
     * Render a card for a third-party plugin.
     *
     * @param string $file The plugin file path.
     * @param array $plugin The plugin data.
     */
    private function render_third_party_plugin_card( string $file, array $plugin ): void {
        $active = function_exists( 'is_plugin_active' ) && is_plugin_active( $file );
        ?>
        <div class="col-12 col-md-6 col-xl-4 d-flex">
            <article class="card modpress-plugin-card shadow-sm h-100 w-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <?php /* translators: %s is the plugin name. */ ?>
                    <?php echo FormFieldHelper::switch( 'modpress-third-party-status', '1', '', [ 'id' => 'modpress-third-party-status-' . SanitizationHelper::key( $file ), 'checked' => $active, 'disabled' => true, 'aria-label' => sprintf( __( 'Enable %s', 'modpress' ), $plugin['Name'] ?? $file ) ] ); ?>
                    <span class="fw-semibold"><?php echo esc_html( $plugin['Name'] ?? $file ); ?></span>
                </div>
                <div class="card-body d-flex flex-column">
                    <span class="modpress-plugin-icon dashicons dashicons-admin-plugins" aria-hidden="true"></span>
                    <p class="card-text text-secondary mt-3"><?php echo esc_html( $plugin['Description'] ?? __( 'No description provided.', 'modpress' ) ); ?></p>
                    <p class="card-text mb-2"><span class="text-secondary"><?php esc_html_e( 'Author:', 'modpress' ); ?></span> <?php echo esc_html( $plugin['AuthorName'] ?? wp_strip_all_tags( $plugin['Author'] ?? __( 'Unknown', 'modpress' ) ) ); ?></p>
                    <p class="card-text mb-2"><span class="text-secondary"><?php esc_html_e( 'Version:', 'modpress' ); ?></span> <?php echo esc_html( $plugin['Version'] ?? __( 'Unknown', 'modpress' ) ); ?></p>
                    <p class="card-text mb-3"><span class="text-secondary"><?php esc_html_e( 'Docs:', 'modpress' ); ?></span> <?php if ( ! empty( $plugin['PluginURI'] ) ) : ?><a href="<?php echo esc_url( $plugin['PluginURI'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View documentation', 'modpress' ); ?></a><?php else : ?><?php esc_html_e( 'Not available', 'modpress' ); ?><?php endif; ?></p>
                    <a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>" class="btn btn-primary mt-auto"><?php esc_html_e( 'Settings', 'modpress' ); ?></a>
                </div>
            </article>
        </div>
        <?php
    }
}