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
        ?>
        <tr>
            <th scope="row">
                <?php echo esc_html( $page['title'] ?? $page['label'] ); ?>
            </th>
            <td>
                <?php foreach ( $page['fields'] as $field ) : ?>
                    <?php
                    $key = SanitizationHelper::key( $field['key'] ?? '' );
                    if ( '' === $key ) {
                        continue;
                    }
                    $default = array_key_exists( 'default', $field ) ? $field['default'] : false;
                    $name = 'modpress_' . SanitizationHelper::key( $page['slug'] ) . '[' . $key . ']';
                    $value = $values[ $key ] ?? $default;
                    $type = SanitizationHelper::key( $field['type'] ?? 'checkbox', 'checkbox' );
                    ?>
                    <div class="mb-3">
                        <?php echo FormFieldHelper::label(
                            'modpress-' . $key,
                            (string) ( $field['label'] ?? $key ),
                            [
                                'description' => (string) ( $field['description'] ?? '' ),
                                'tooltip' => (string) ( $field['tooltip'] ?? '' ),
                                'tooltip_type' => SanitizationHelper::key( $field['tooltip_type'] ?? 'question', 'question' ),
                                'tooltip_icon' => (string) ( $field['tooltip_icon'] ?? '' ),
                            ]
                        ); ?>
                        <?php if ( 'select' === $type ) : ?>
                            <?php echo FormFieldHelper::select(
                                $name,
                                (array) ( $field['options'] ?? [] ),
                                $value,
                                [
                                    'id' => 'modpress-' . $key,
                                ]
                            ); ?>
                        <?php elseif ( 'text' === $type ) : ?>
                            <?php echo FormFieldHelper::input(
                                $name,
                                is_scalar( $value ) ? (string) $value : '',
                                [
                                    'id' => 'modpress-' . $key,
                                    'type' => 'text',
                                ]
                            ); ?>
                        <?php else : ?>
                            <?php echo FormFieldHelper::checkbox(
                                $name,
                                '1',
                                '',
                                [
                                    'id' => 'modpress-' . $key,
                                    'checked' => ! empty( $value ),
                                ]
                            ); ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </td>
        </tr>
        <?php
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
        $plugins = Plugins::get_instance()->get_registered_plugins();
        ?>
        <div class="row g-4">
            <?php foreach ( $plugins as $plugin ) : ?>
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
                    <?php echo FormFieldHelper::switch( 
                        'modpress-plugin-status', 
                        '1', 
                        '', 
                        [ 
                            'id' => 'modpress-plugin-status-' . SanitizationHelper::key( $plugin->get_slug() ), 
                            'checked' => $enabled, 
                            'disabled' => ! $can_edit, 
                            'data-modpress-plugin-toggle' => 'true', 
                            'data-plugin-slug' => $plugin->get_slug(), 
                            'aria-label' => sprintf( 
                                __( 'Enable %s', 'modpress' ), 
                                $plugin->get_name() 
                            ) 
                        ] 
                    ); ?>
                    <span class="fw-semibold">
                        <?php echo esc_html( $plugin->get_name() ); ?>
                    </span>
                </div>
                <div class="card-body d-flex flex-column">
                    <span class="modpress-plugin-icon dashicons dashicons-admin-plugins" aria-hidden="true"></span>
                    <p class="card-text text-secondary mt-3">
                        <?php echo esc_html( $plugin->get_description() ); ?>
                    </p>
                    <p class="card-text mb-2">
                        <span class="text-secondary">
                            <?php esc_html_e( 'Author:', 'modpress' ); ?>
                        </span> <?php echo esc_html( $plugin->get_author() ); ?>
                    </p>
                    <p class="card-text mb-2">
                        <span class="text-secondary">
                            <?php esc_html_e( 'Version:', 'modpress' ); ?>
                        </span> <?php echo esc_html( $plugin->get_version() ); ?>
                    </p>
                    <p class="card-text mb-3">
                        <span class="text-secondary">
                            <?php esc_html_e( 'Docs:', 'modpress' ); ?>
                        </span> <a href="<?php echo esc_url( $plugin->get_uri() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View documentation', 'modpress' ); ?></a></p>
                    <?php if ( ! empty( $settings_page['fields'] ) ) : ?>
                        <?php echo FormFieldHelper::button( 
                            __( 'Settings', 'modpress' ), 
                            [ 
                                'type' => 'button', 
                                'class' => 'btn-primary mt-auto', 
                                'data-bs-toggle' => 'modal', 
                                'data-bs-target' => '#' . $modal_id 
                            ] 
                        ); ?>
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
                        <form method="post" 
                            id="modpress-plugin-settings-form-<?php echo esc_attr( $modal_id ); ?>" 
                            class="modpress-plugin-settings-form" 
                            data-plugin-settings-form="" 
                            data-plugin-slug="<?php echo esc_attr( $plugin->get_slug() ); ?>" 
                            data-internal-mod-fields="">
                            <h3 class="h6 mb-3"><?php echo esc_html( $settings_page['title'] ?? $settings_page['label'] ); ?></h3>
                            <fieldset <?php disabled( ! $can_edit ); ?>>
                                <?php $this->render_plugin_settings_fields( $settings_page, $values, $modal_id ); ?>
                            </fieldset>
                    </form>
                    </div>
                    <div class="modal-footer">
                        <?php echo FormFieldHelper::button( 
                            __( 'Cancel', 'modpress' ), 
                            [ 
                                'type' => 'button', 
                                'class' => 'btn-secondary', 
                                'data-bs-dismiss' => 'modal' 
                            ] 
                        ); ?>
                        <?php if ( $can_edit ) : ?>
                            <?php echo FormFieldHelper::button( 
                                __( 'Save', 'modpress' ), 
                                [
                                    'type' => 'submit',
                                    'class' => 'btn-primary',
                                    'form' => 'modpress-plugin-settings-form-' . $modal_id,
                                    'data-plugin-settings-save' => '',
                                ] 
                            ); ?>
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
     * Normalize conditional visibility rules for a field.
     *
     * Supports the common patterns used across plugin settings pages:
     * - visible_when => [ 'fontawesome_type' => 'cdn' ]
     * - visible_when => [ [ 'fontawesome_type' => 'cdn' ], [ 'fontawesome_type' => 'kit' ] ]
     * - required => [ 'fontawesome_type' => 'kit' ]
     *
     * @param array $field Field definition.
     * @return array{visible_when?: array<mixed>, required?: array<mixed>}
     */
    private function get_field_condition_rules( array $field ): array {
        $rules = array(
            'visible_when' => $field['visible_when'] ?? null,
            'required'     => $field['required'] ?? null,
        );

        foreach ( $rules as $key => $value ) {
            if ( is_array( $value ) ) {
                continue;
            }

            $rules[ $key ] = null;
        }

        return array_filter(
            $rules,
            static fn ( $value ) => is_array( $value ) && ! empty( $value )
        );
    }

    /**
     * Determine whether a field should be shown for the current values.
     *
     * @param array $field Field definition.
     * @param array $values Current setting values.
     * @return bool Whether the field should render.
     */
    private function should_render_field( array $field, array $values ): bool {
        $condition = $this->get_field_condition_rules( $field );
        if ( empty( $condition ) ) {
            return true;
        }

        $check_rule = function ( $rule ) use ( $values ) {
            if ( ! is_array( $rule ) ) {
                return true;
            }

            if ( empty( $rule ) ) {
                return true;
            }

            if ( array_is_list( $rule ) ) {
                return array_reduce(
                    $rule,
                    fn ( $carry, $item ) => $carry || $this->matches_rule_condition( $item, $values ),
                    false
                );
            }

            return $this->matches_rule_condition( $rule, $values );
        };

        foreach ( $condition as $rule_type => $rule ) {
            if ( ! $check_rule( $rule ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition array against current field values.
     *
     * @param array $rule Condition map.
     * @param array $values Current values.
     * @return bool Whether the condition matches.
     */
    private function matches_rule_condition( array $rule, array $values ): bool {
        foreach ( $rule as $key => $expected ) {
            $actual = $values[ $key ] ?? '';
            if ( is_array( $expected ) ) {
                $matches = in_array( (string) $actual, array_map( 'strval', $expected ), true );
                if ( ! $matches ) {
                    return false;
                }
                continue;
            }

            if ( (string) $actual !== (string) $expected ) {
                return false;
            }
        }

        return true;
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

        if ( 'table' === $layout ) :
            ?>
            <div class="modpress-plugin-settings-fields modpress-plugin-settings-fields-table">
                <table class="table align-middle">
                    <tbody>
            <?php
        else :
            ?>
            <div class="modpress-plugin-settings-fields modpress-plugin-settings-fields-box">
            <?php
        endif;

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
            $wrapper_attributes['data-modpress-field-key'] = $key;
            if ( ! empty( $field['wrapper_class'] ) ) {
                $wrapper_attributes['class'] = (string) $field['wrapper_class'];
            }
            if ( ! empty( $field['wrapper_attributes'] ) && is_array( $field['wrapper_attributes'] ) ) {
                $wrapper_attributes = array_merge( $wrapper_attributes, $field['wrapper_attributes'] );
            }
            $condition = $this->get_field_condition_rules( $field );
            if ( ! empty( $condition['visible_when'] ) ) {
                $wrapper_attributes['data-modpress-visible-when'] = wp_json_encode( $condition['visible_when'] );
            }
            if ( ! empty( $condition['required'] ) ) {
                $wrapper_attributes['data-modpress-required-when'] = wp_json_encode( $condition['required'] );
            }
            $wrapper_attributes = FormFieldHelper::attributes_to_string( $wrapper_attributes );
            $label = FormFieldHelper::label( $id, (string) ( $field['label'] ?? $key ), [
                'tooltip' => (string) ( $field['tooltip'] ?? '' ),
                'tooltip_type' => SanitizationHelper::key( $field['tooltip_type'] ?? 'question', 'question' ),
                'tooltip_icon' => (string) ( $field['tooltip_icon'] ?? '' ),
            ] );
            if ( 'table' === $layout ) :
                ?>
                <tr<?php echo $wrapper_attributes ? ' ' . $wrapper_attributes : ''; ?>>
                    <th scope="row" class="w-50"><?php echo wp_kses_post( $label ); ?></th>
                    <td>
                <?php
            else :
                ?>
                <article class="modpress-plugin-settings-field card h-100"<?php echo $wrapper_attributes ? ' ' . $wrapper_attributes : ''; ?>>
                    <div class="card-body">
                        <div class="modpress-plugin-settings-field-header d-flex align-items-start justify-content-between gap-3">
                            <?php echo wp_kses_post( $label ); ?>
                            <?php if ( 'checkbox' === $type ) : ?>
                                <?php echo FormFieldHelper::switch( $name, '1', '', [ 'id' => $id, 'checked' => ! empty( $value ), 'wrapper_class' => 'ms-auto flex-shrink-0' ] ); ?>
                            <?php endif; ?>
                        </div>
                        <?php if ( ! empty( $field['description'] ) ) : ?>
                            <p class="modpress-plugin-settings-field-description text-secondary mb-3"><?php echo esc_html( (string) $field['description'] ); ?></p>
                        <?php endif; ?>
                <?php
            endif;

            if ( 'table' === $layout && 'select' === $type ) {
                echo FormFieldHelper::select( 
                    $name, 
                    (array) ( $field['options'] ?? [] ), 
                    $value, 
                    [ 
                        'id' => $id, 
                        'attributes' => $field['attributes'] ?? [] 
                    ] 
                );
            } elseif ( 'table' === $layout && 'multiselect' === $type ) {
                echo FormFieldHelper::bootstrap_multiselect( 
                    $name, 
                    [ 
                        'id' => $id, 
                        'data' => (array) ( $field['options'] ?? [] ), 
                        'selected' => (array) $value, 
                        'dropup_auto' => $field['dropup_auto'] ?? true, 
                        'show_tick' => $field['show_tick'] ?? null, 
                        'selection_indicator' => $field['selection_indicator'] ?? null, 
                        'attributes' => $field['attributes'] ?? [] 
                    ] 
                );
            } elseif ( 'table' === $layout && 'text' === $type ) {
                echo FormFieldHelper::input( 
                    $name, 
                    is_scalar( $value ) ? (string) $value : '', 
                    [ 
                        'id' => $id, 
                        'type' => 'text' 
                    ] 
                );
            } elseif ( 'table' === $layout ) {
                echo FormFieldHelper::checkbox( 
                    $name, 
                    '1', 
                    '', 
                    [ 
                        'id' => $id, 
                        'checked' => ! empty( $value ) 
                    ] 
                );
            } elseif ( 'select' === $type ) {
                echo FormFieldHelper::select( 
                    $name, 
                    (array) ( $field['options'] ?? [] ), 
                    $value, 
                    [ 
                        'id' => $id, 
                        'attributes' => $field['attributes'] ?? [] 
                    ] 
                );
            } elseif ( 'multiselect' === $type ) {
                echo FormFieldHelper::bootstrap_multiselect( 
                    $name, 
                    [ 
                        'id' => $id, 
                        'data' => (array) ( $field['options'] ?? [] ), 
                        'selected' => (array) $value, 
                        'dropup_auto' => $field['dropup_auto'] ?? true, 
                        'show_tick' => $field['show_tick'] ?? null, 
                        'selection_indicator' => $field['selection_indicator'] ?? null, 
                        'attributes' => $field['attributes'] ?? [] 
                    ] 
                );
            } elseif ( 'text' === $type ) {
                echo FormFieldHelper::input( 
                    $name, 
                    is_scalar( $value ) ? (string) $value : '', 
                    [ 
                        'id' => $id, 
                        'type' => 'text' 
                    ] 
                );
            } elseif ( 'checkbox' === $type ) {
                echo FormFieldHelper::switch( 
                    $name, 
                    '1', 
                    '', 
                    [ 
                        'id' => $id, 
                        'checked' => ! empty( $value ) 
                    ] 
                );
            }

            if ( 'table' === $layout ) :
                ?>
                    </td>
                </tr>
                <?php
            else :
                ?>
                    </div>
                </article>
                <?php
            endif;
        }

        if ( 'table' === $layout ) :
            ?>
                    </tbody>
                </table>
            </div>
            <?php
        else :
            ?>
            </div>
            <?php
        endif;
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