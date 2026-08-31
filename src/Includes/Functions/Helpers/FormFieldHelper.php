<?php
/**
 * Bootstrap form field rendering helpers.
 *
 * @package ModPress
 * @since 1.0.0
 */

namespace ModPress\Includes\Functions\Helpers;

use ModPress\Includes\Plugins\TinyMCE\Includes\Functions\Helpers\TinyMCEHelper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class FormFieldHelper {
    /**
     * Render a text input.
     *
     * @param string $name       The name attribute for the input.
     * @param string $value      The value attribute for the input.
     * @param array  $attributes Additional attributes for the input.
     * @return string The HTML markup for the text input.
     */
    public static function text_input( string $name, string $value = '', array $attributes = [] ): string {

        return self::input( $name, $value, $attributes + [ 'type' => 'text' ] );

    }

    /**
     * Render a password input.
     *
     * @param string $name       The name attribute for the input.
     * @param string $value      The value attribute for the input.
     * @param array  $attributes Additional attributes for the input.
     * @return string The HTML markup for the password input.
     */
    public static function input( string $name, $value = '', array $options = [] ): string {

        $type = sanitize_key( (string) ( $options['type'] ?? 'text' ) );

        $class = self::classes( [ 'form-control', $options['class'] ?? '', self::validation_class( $options ) ] );

        if ( 'color' === $type ) {

            $class = self::classes( [ $class, 'form-control-color' ] );

        }

        $attributes = array_merge( $options['attributes'] ?? [], $options );

        unset( $attributes['attributes'], $attributes['class'], $attributes['type'], $attributes['value'], $attributes['validation'] );

        $attributes['class'] = $class;
        $attributes['type'] = $type;
        $attributes['name'] = $name;

        if ( 'file' !== $type ) {

            $attributes['value'] = $value;

        }

        return '<input ' . self::attributes_to_string( $attributes ) . ' />' . self::feedback( $options );

    }
    /**
     * Render a textarea.
     *
     * @param string $name       The name attribute for the textarea.
     * @param string $value      The value attribute for the textarea.
     * @param array  $attributes Additional attributes for the textarea.
     * @return string The HTML markup for the textarea.
     */
    public static function textarea( string $name, string $value = '', array $options = [] ): string {

        $attributes = array_merge( $options['attributes'] ?? [], $options );

        unset( $attributes['attributes'], $attributes['class'], $attributes['value'], $attributes['validation'] );

        $attributes['class'] = self::classes( [ 'form-control', $options['class'] ?? '', self::validation_class( $options ) ] );

        $attributes['name'] = $name;

        return '<textarea ' . self::attributes_to_string( $attributes ) . '>' . esc_textarea( $value ) . '</textarea>' . self::feedback( $options );

    }

    /**
     * Render a TinyMCE editor.
     *
     * @param string $id            The ID of the editor.
     * @param string $name          The name attribute for the textarea.
     * @param string $label         The label for the editor.
     * @param string $value         The initial content of the editor.
     * @param int    $rows          The number of rows for the textarea.
     * @param bool   $media_buttons Whether to show media buttons.
     * @return void
     */
    public static function tinymce( string $id, string $name, string $label, string $value = '', int $rows = 8, bool $media_buttons = false ): void {
        if ( ! class_exists( TinyMCEHelper::class ) ) {
            $editor_id = sanitize_key( $id );
            printf( '<label class="form-label" for="%1$s">%2$s</label>', esc_attr( $editor_id ), esc_html( $label ) );
            printf( '<textarea class="form-control" id="%1$s" name="%2$s" rows="%3$d">%4$s</textarea>', esc_attr( $editor_id ), esc_attr( $name ), max( 1, $rows ), esc_textarea( $value ) );
            return;
        }

        TinyMCEHelper::render( $id, $name, $label, $value, $rows, $media_buttons );
    }

    /**
     * Render a select dropdown.
     *
     * @param string $name       The name attribute for the select.
     * @param array  $options    The options for the select.
     * @param mixed  $selected   The selected value(s) for the select.
     * @param array  $attributes Additional attributes for the select.
     * @return string The HTML markup for the select dropdown.
     */
    public static function select( string $name, array $options = [], $selected = [], array $attributes = [] ): string {

        return self::render_select( $name, $options, $selected, $attributes, false );

    }

    private static function render_select( string $name, array $options = [], $selected = [], array $attributes = [], bool $bootstrap_select = false ): string {

        $selected = array_map( 'strval', (array) $selected );
        $class = $attributes['class'] ?? '';
        $validation = $attributes['validation'] ?? [];
        $attributes = array_merge( $attributes['attributes'] ?? [], $attributes );
        unset( $attributes['attributes'], $attributes['class'], $attributes['options'], $attributes['selected'], $attributes['validation'] );
        $attributes['class'] = self::classes( [ $bootstrap_select ? $class : 'form-select', $class, self::validation_class( [ 'validation' => $validation ] ) ] );
        $attributes['name'] = $name;
        $html = '<select ' . self::attributes_to_string( $attributes ) . '>';

        foreach ( $options as $key => $option ) {

            if ( is_array( $option ) && isset( $option['options'] ) ) {

                $html .= '<optgroup label="' . esc_attr( $option['label'] ?? $key ) . '"' . ( ! empty( $option['disabled'] ) ? ' disabled' : '' ) . '>';
                $html .= self::option_list( $option['options'], $selected ) . '</optgroup>';
                continue;

            }

            $value = is_array( $option ) ? (string) ( $option['value'] ?? $key ) : (string) $key;
            $label = is_array( $option ) ? (string) ( $option['label'] ?? $value ) : (string) $option;
            $html .= self::option( $value, $label, in_array( $value, $selected, true ), is_array( $option ) && ! empty( $option['disabled'] ), is_array( $option ) ? $option : [] );

        }

        return $html . '</select>' . self::feedback( $attributes );

    }
    /**
     * Render a checkbox input.
     *
     * @param string $name       The name attribute for the checkbox.
     * @param string $value      The value attribute for the checkbox.
     * @param string $label      The label text for the checkbox.
     * @param array  $attributes Additional attributes for the checkbox.
     * @return string The HTML markup for the checkbox input.
     */
    public static function checkbox( string $name, string $value = '1', string $label = '', array $options = [] ): string {

        return self::check( 'checkbox', $name, $value, $label, $options );

    }
    /**
     * Render a radio input.
     *
     * @param string $name       The name attribute for the radio button.
     * @param string $value      The value attribute for the radio button.
     * @param string $label      The label text for the radio button.
     * @param array  $attributes Additional attributes for the radio button.
     * @return string The HTML markup for the radio input.
     */
    public static function radio( string $name, string $value, string $label = '', array $options = [] ): string {

        return self::check( 'radio', $name, $value, $label, $options );

    }
    /**
     * Render a switch input (checkbox styled as a switch).
     *
     * @param string $name       The name attribute for the switch.
     * @param string $value      The value attribute for the switch.
     * @param string $label      The label text for the switch.
     * @param array  $attributes Additional attributes for the switch.
     * @return string The HTML markup for the switch input.
     */
    public static function switch( string $name, string $value = '1', string $label = '', array $options = [] ): string {

        $options['switch'] = true;

        return self::check( 'checkbox', $name, $value, $label, $options );

    }

    /**
     * Render a Bootstrap button group.
     *
     * @param string       $name     The input name for checkbox or radio groups.
     * @param array        $buttons  Button definitions keyed by value or containing value/label pairs.
     * @param mixed        $selected The selected value or values.
     * @param array        $options  Group and button options.
     * @return string The button group markup.
     */
    public static function button_group( string $name, array $buttons = [], $selected = [], array $options = [] ): string {

        $type = in_array( $options['type'] ?? 'radio', [ 'button', 'checkbox', 'radio' ], true ) ? $options['type'] : 'radio';
        $size = in_array( $options['size'] ?? '', [ 'sm', 'lg' ], true ) ? $options['size'] : '';
        $group_id = (string) ( $options['id'] ?? sanitize_title( $name . '-group' ) );
        $group_attributes = array_merge( $options['attributes'] ?? [], $options );
        unset( $group_attributes['attributes'], $group_attributes['id'], $group_attributes['class'], $group_attributes['type'], $group_attributes['size'], $group_attributes['vertical'], $group_attributes['variant'], $group_attributes['button_class'], $group_attributes['aria_label'], $group_attributes['append_array'] );
        $group_attributes['id'] = $group_id;
        $group_attributes['role'] = $options['role'] ?? 'group';

        if ( ! empty( $options['aria_label'] ) ) {
            $group_attributes['aria-label'] = $options['aria_label'];
        }

        $html = '<div class="' . esc_attr( self::classes( [ 'btn-group', ! empty( $options['vertical'] ) ? 'btn-group-vertical' : '', $size ? 'btn-group-' . $size : '', $options['class'] ?? '' ] ) ) . '" ' . self::attributes_to_string( $group_attributes ) . '>';
        $selected_values = array_map( 'strval', (array) $selected );
        $input_name = $name;

        if ( 'checkbox' === $type && false !== ( $options['append_array'] ?? true ) && ! str_ends_with( $input_name, '[]' ) ) {
            $input_name .= '[]';
        }

        foreach ( $buttons as $key => $button ) {
            $definition = is_array( $button ) ? $button : [];
            $value = (string) ( $definition['value'] ?? ( is_array( $button ) ? $key : $key ) );
            $label = (string) ( $definition['label'] ?? ( is_array( $button ) ? $value : $button ) );
            $id = (string) ( $definition['id'] ?? sanitize_title( $group_id . '-' . $value ) );
            $variant = (string) ( $definition['variant'] ?? $options['variant'] ?? 'outline-primary' );
            $button_class = self::classes( [ 'btn', 'btn-' . sanitize_html_class( $variant ), $definition['class'] ?? $options['button_class'] ?? '' ] );
            $disabled = ! empty( $definition['disabled'] );

            if ( 'button' === $type ) {
                if ( isset( $definition['dropdown'] ) && is_array( $definition['dropdown'] ) ) {
                    $html .= self::dropdown_button( $label, $definition['dropdown'], $definition );
                    continue;
                }

                $button_options = $definition;
                $button_options['class'] = $button_class;
                $button_options['type'] = $definition['type'] ?? 'button';
                $html .= self::button( $label, $button_options );
                continue;
            }

            $input_attributes = array_merge( $definition['input_attributes'] ?? [], [ 'autocomplete' => 'off' ] );
            $input_attributes['class'] = 'btn-check';
            $input_attributes['id'] = $id;
            $input_attributes['type'] = $type;
            $input_attributes['name'] = $input_name;
            $input_attributes['value'] = $value;

            $is_selected = in_array( $value, $selected_values, true );
            if ( $is_selected ) {
                $input_attributes['checked'] = true;
            }
            if ( $disabled ) {
                $input_attributes['disabled'] = true;
            }

            $html .= '<input ' . self::attributes_to_string( $input_attributes ) . ' />';
            $html .= '<label class="' . esc_attr( $button_class ) . '" for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';
        }

        return $html . '</div>' . self::feedback( $options );
    }

    /**
     * Render a regular Bootstrap button for use in a button group or toolbar.
     *
     * @param string $label   Button label.
     * @param array  $options Button attributes and content options.
     * @return string The button markup.
     */
    public static function button( string $label, array $options = [] ): string {

        $tag = ! empty( $options['href'] ) ? 'a' : 'button';
        $attributes = array_merge( $options['attributes'] ?? [], $options );
        unset( $attributes['attributes'], $attributes['href'], $attributes['class'], $attributes['type'], $attributes['label'], $attributes['value'], $attributes['disabled'] );
        $attributes['class'] = self::classes( [ 'btn', $options['class'] ?? 'btn-primary' ] );

        if ( 'a' === $tag ) {
            $attributes['href'] = $options['href'];
        } else {
            $attributes['type'] = in_array( $options['type'] ?? 'button', [ 'button', 'submit', 'reset' ], true ) ? $options['type'] : 'button';
        }

        if ( ! empty( $options['disabled'] ) ) {
            $attributes['disabled'] = true;
            if ( 'a' === $tag ) {
                $attributes['aria-disabled'] = 'true';
                $attributes['tabindex'] = '-1';
            }
        }

        return '<' . $tag . ' ' . self::attributes_to_string( $attributes ) . '>' . esc_html( $label ) . '</' . $tag . '>';
    }

    /**
     * Render a Bootstrap dropdown button for nesting in a button group.
     *
     * @param string $label   Toggle label.
     * @param array  $items   Dropdown item definitions.
     * @param array  $options Dropdown and toggle options.
     * @return string The dropdown button markup.
     */
    public static function dropdown_button( string $label, array $items = [], array $options = [] ): string {

        $dropdown_id = (string) ( $options['id'] ?? sanitize_title( $label . '-dropdown' ) );
        $size = in_array( $options['size'] ?? '', [ 'sm', 'lg' ], true ) ? $options['size'] : '';
        $variant = (string) ( $options['variant'] ?? 'primary' );
        $button_class = self::classes( [ 'btn', 'btn-' . sanitize_html_class( $variant ), $size ? 'btn-' . $size : '', $options['class'] ?? '' ] );
        $attributes = array_merge( $options['attributes'] ?? [], [ 'type' => 'button', 'class' => $button_class, 'id' => $dropdown_id, 'data-bs-toggle' => 'dropdown', 'aria-expanded' => 'false' ] );
        unset( $attributes['attributes'], $attributes['id'], $attributes['class'], $attributes['type'], $attributes['variant'], $attributes['size'], $attributes['split'], $attributes['dropdown'], $attributes['label'] );
        $attributes['id'] = $dropdown_id;
        $attributes['type'] = 'button';
        $attributes['class'] = $button_class . ( ! empty( $options['split'] ) ? ' dropdown-toggle-split' : ' dropdown-toggle' );
        $toggle = '<button ' . self::attributes_to_string( $attributes ) . '>' . esc_html( $label );

        if ( ! empty( $options['split'] ) ) {
            $toggle .= '<span class="visually-hidden">' . esc_html__( 'Toggle Dropdown', 'modpress' ) . '</span>';
        }

        $toggle .= '</button>';
        $menu = '<ul class="dropdown-menu" aria-labelledby="' . esc_attr( $dropdown_id ) . '">';

        foreach ( $items as $item ) {
            if ( is_string( $item ) ) {
                $item = [ 'label' => $item ];
            }

            if ( ! empty( $item['divider'] ) ) {
                $menu .= '<li><hr class="dropdown-divider"></li>';
                continue;
            }

            if ( isset( $item['header'] ) ) {
                $menu .= '<li><h6 class="dropdown-header">' . esc_html( (string) $item['header'] ) . '</h6></li>';
                continue;
            }

            $item_label = (string) ( $item['label'] ?? '' );
            $item_attributes = array_merge( $item['attributes'] ?? [], [ 'class' => self::classes( [ 'dropdown-item', ! empty( $item['active'] ) ? 'active' : '', ! empty( $item['class'] ) ? $item['class'] : '' ] ) ] );
            unset( $item_attributes['attributes'], $item_attributes['class'], $item_attributes['active'], $item_attributes['disabled'], $item_attributes['href'], $item_attributes['label'] );
            $item_tag = ! empty( $item['href'] ) ? 'a' : 'button';
            $item_attributes['class'] = self::classes( [ 'dropdown-item', ! empty( $item['active'] ) ? 'active' : '', ! empty( $item['class'] ) ? $item['class'] : '' ] );
            $item_attributes['aria-current'] = ! empty( $item['active'] ) ? 'true' : null;

            if ( 'a' === $item_tag ) {
                $item_attributes['href'] = $item['href'];
            } else {
                $item_attributes['type'] = 'button';
            }

            if ( ! empty( $item['disabled'] ) ) {
                $item_attributes['disabled'] = true;
                $item_attributes['aria-disabled'] = 'true';
            }

            $menu .= '<li><' . $item_tag . ' ' . self::attributes_to_string( $item_attributes ) . '>' . esc_html( $item_label ) . '</' . $item_tag . '></li>';
        }

        return '<div class="btn-group">' . $toggle . $menu . '</ul></div>';
    }

    /**
     * Render a Bootstrap button toolbar from button group markup.
     *
     * @param array $groups  Rendered button group strings.
     * @param array $options Toolbar options.
     * @return string The toolbar markup.
     */
    public static function button_toolbar( array $groups = [], array $options = [] ): string {

        $attributes = array_merge( $options['attributes'] ?? [], $options );
        unset( $attributes['attributes'], $attributes['id'], $attributes['class'], $attributes['aria_label'], $attributes['gap'] );
        $attributes['id'] = (string) ( $options['id'] ?? sanitize_title( 'modpress-button-toolbar' ) );
        $attributes['role'] = 'toolbar';

        if ( ! empty( $options['aria_label'] ) ) {
            $attributes['aria-label'] = $options['aria_label'];
        }

        $class = self::classes( [ 'btn-toolbar', ! empty( $options['gap'] ) ? 'gap-' . absint( $options['gap'] ) : '', $options['class'] ?? '' ] );
        return '<div class="' . esc_attr( $class ) . '" ' . self::attributes_to_string( $attributes ) . '>' . implode( '', $groups ) . '</div>';
    }
    /**
     * Render a checkbox or radio input with label.
     *
     * @param string $type       The type of input ('checkbox' or 'radio').
     * @param string $name       The name attribute for the input.
     * @param string $value      The value attribute for the input.
     * @param string $label      The label text for the input.
     * @param array  $attributes Additional attributes for the input.
     * @return string The HTML markup for the checkbox or radio input.
     */
    public static function check( string $type, string $name, string $value, string $label = '', array $options = [] ): string {

        $type = in_array( $type, [ 'checkbox', 'radio' ], true ) ? $type : 'checkbox';
        $id = (string) ( $options['id'] ?? sanitize_title( $name . '-' . $value ) );
        $wrapper = self::classes( [ 'form-check', ! empty( $options['inline'] ) ? 'form-check-inline' : '', ! empty( $options['switch'] ) ? 'form-switch' : '', ! empty( $options['reverse'] ) ? 'form-check-reverse' : '', $options['wrapper_class'] ?? '' ] );
        $attributes = array_merge( $options['attributes'] ?? [], $options );
        unset( $attributes['attributes'], $attributes['class'], $attributes['wrapper_class'], $attributes['inline'], $attributes['switch'], $attributes['reverse'], $attributes['checked'], $attributes['id'], $attributes['type'], $attributes['value'], $attributes['validation'] );
        $attributes['class'] = self::classes( [ 'form-check-input', $options['class'] ?? '', self::validation_class( $options ) ] );
        $attributes['id'] = $id;
        $attributes['type'] = $type;
        $attributes['name'] = $name;
        $attributes['value'] = $value;

        if ( ! empty( $options['checked'] ) ) {

            $attributes['checked'] = true;

        }

        if ( ! empty( $options['switch'] ) ) {

            $attributes['role'] = 'switch';

        }

        $html = '<div class="' . esc_attr( $wrapper ) . '"><input ' . self::attributes_to_string( $attributes ) . ' />';

        if ( '' !== $label ) {

            $html .= '<label class="form-check-label" for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';

        }

        return $html . self::feedback( $options ) . '</div>';

    }
    /**
     * Render a range input with optional output display.
     *
     * @param string $name       The name attribute for the range input.
     * @param int    $value      The value attribute for the range input.
     * @param array  $attributes Additional attributes for the range input.
     * @return string The HTML markup for the range input.
     */
    public static function range( string $name, $value = 0, array $options = [] ): string {

        $show_output = ! empty( $options['output'] );
        $options['type'] = 'range';
        $options['class'] = self::classes( [ 'form-range', $options['class'] ?? '' ] );
        unset( $options['output'] );
        $html = self::input( $name, $value, $options );

        if ( $show_output ) {

            $id = (string) ( $options['id'] ?? sanitize_title( $name ) );
            $html .= '<output for="' . esc_attr( $id ) . '" id="' . esc_attr( $id . '-output' ) . '">' . esc_html( (string) $value ) . '</output>';

        }

        return $html;

    }
    /**
     * Render a datalist input with options.
     *
     * @param string $name       The name attribute for the input.
     * @param array  $values     The values for the datalist options.
     * @param string $value      The value attribute for the input.
     * @param array  $attributes Additional attributes for the input.
     * @return string The HTML markup for the datalist input.
     */
    public static function datalist( string $name, array $values, $value = '', array $options = [] ): string {

        $id = (string) ( $options['list'] ?? sanitize_title( $name ) . '-options' );
        $options['list'] = $id;
        $html = self::input( $name, $value, $options ) . '<datalist id="' . esc_attr( $id ) . '">';

        foreach ( $values as $option ) {

            $option_value = is_array( $option ) ? ( $option['value'] ?? '' ) : $option;
            $label = is_array( $option ) && isset( $option['label'] ) ? esc_html( $option['label'] ) : '';
            $html .= '<option value="' . esc_attr( $option_value ) . '">' . $label . '</option>';

        }

        return $html . '</datalist>';

    }

    /**
     * Render a Bootstrap Select single-select.
     *
     * @param string $name    The name attribute for the select.
     * @param array  $options Select options and Bootstrap Select settings.
     * @return string The rendered select element.
     */
    public static function bootstrap_select( string $name, array $options = [] ): string {

        $options['class'] = self::bootstrap_select_classes( $options['class'] ?? '' );

        return self::render_bootstrap_select( $name, $options );

    }

    /**
     * Render a Bootstrap Select multi-select.
     *
     * @param string $name    The name attribute for the select.
     * @param array  $options Select options and Bootstrap Select settings.
     * @return string The rendered select element.
     */
    public static function bootstrap_multiselect( string $name, array $options = [] ): string {

        if ( ! str_ends_with( $name, '[]' ) ) {
            $name .= '[]';
        }

        $options['class'] = self::bootstrap_select_classes( $options['class'] ?? '' );
        $options['multiple'] = true;

        return self::render_bootstrap_select( $name, $options );

    }

    private static function render_bootstrap_select( string $name, array $options ): string {

        $settings = [
            'icons_base' => [ 'icons-base', 'fa-solid' ],
            'tick_icon' => [ 'tick-icon', 'fa-check' ],
            'live_search' => [ 'live-search', true ],
            'show_selected_tags' => [ 'show-selected-tags', true ],
            'open_options' => [ 'open-options', false ],
            'dropup_auto' => [ 'dropup-auto', true ],
            'show_tick' => [ 'show-tick', null ],
            'selection_indicator' => [ 'selection-indicator', null ],
            'live_search_placeholder' => [ 'live-search-placeholder', __( 'Search or create', 'modpress' ) ],
            'open_options_text' => [ 'open-options-text', __( 'Create "{0}"', 'modpress' ) ],
            'selected_text_format' => [ 'selected-text-format', 'count' ],
            'selected_items_style' => [ 'selected-items-style', 'tags' ],
            'selected_tag_remove_label' => [ 'selected-tag-remove-label', __( 'Remove', 'modpress' ) ],
            'placeholder' => [ 'placeholder', __( 'Select options', 'modpress' ) ],
            'width' => [ 'width', '100%' ],
            'size' => [ 'size', null ],
            'actions_box' => [ 'actions-box', null ],
            'max_options' => [ 'max-options', null ],
            'live_search_normalize' => [ 'live-search-normalize', null ],
            'live_search_style' => [ 'live-search-style', null ],
        ];

        foreach ( $settings as $option_key => [ $attribute, $default ] ) {
            $value = $options[ $option_key ] ?? $default;
            if ( null !== $value ) {
                $options[ 'data-' . $attribute ] = is_bool( $value ) ? ( $value ? 'true' : 'false' ) : $value;
            }
        }

        if ( true === ( $options['show_tick'] ?? false ) ) {
            $options['class'] = self::bootstrap_select_classes( (string) ( $options['class'] ?? '' ) . ' show-tick' );
        }

        $select_options = $options['data'] ?? [];
        $selected = $options['selected'] ?? [];
        unset( $options['icons_base'], $options['tick_icon'], $options['live_search_placeholder'], $options['open_options_text'], $options['selected_text_format'], $options['selected_items_style'], $options['selected_tag_remove_label'], $options['placeholder'], $options['width'], $options['size'], $options['actions_box'], $options['max_options'], $options['live_search_normalize'], $options['live_search_style'], $options['live_search'], $options['show_selected_tags'], $options['open_options'], $options['dropup_auto'], $options['show_tick'], $options['selection_indicator'], $options['data'], $options['selected'] );

        return self::render_select( $name, $select_options, $selected, $options, true );
    }

    private static function bootstrap_select_classes( string $classes ): string {

        $allowed = [ 'selectpicker', 'dropup', 'show-tick' ];
        $classes = preg_split( '/\s+/', trim( $classes ) ) ?: [];

        return implode( ' ', array_values( array_unique( array_merge( [ 'selectpicker' ], array_intersect( $classes, $allowed ) ) ) ) );
    }
    /**
     * Render a select option.
     *
     * @param string $value    The value attribute for the option.
     * @param string $label    The label text for the option.
     * @param bool   $selected Whether the option is selected.
     * @param bool   $disabled Whether the option is disabled.
     * @return string The HTML markup for the select option.
     */
    public static function label( string $for, string $text, array $options = [] ): string {

        $attributes = array_merge( $options['attributes'] ?? [], $options );
        unset( $attributes['attributes'], $attributes['class'], $attributes['key'], $attributes['label'], $attributes['type'], $attributes['default'], $attributes['options'], $attributes['description'], $attributes['tooltip'], $attributes['tooltip_icon'], $attributes['tooltip_type'] );
        $attributes['class'] = self::classes( [ 'form-label', $options['class'] ?? '' ] );
        $attributes['for'] = $for;

        $html = '<label ' . self::attributes_to_string( $attributes ) . '>' . esc_html( $text ) . '</label>';

        if ( ! empty( $options['tooltip'] ) ) {
            $tooltip_type = in_array( $options['tooltip_type'] ?? 'question', [ 'question', 'info' ], true ) ? $options['tooltip_type'] : 'question';
            $default_icon = 'info' === $tooltip_type ? 'fa-circle-info' : 'fa-circle-question';
            $icon = self::icon_class( $options['tooltip_icon'] ?? $default_icon, $default_icon );
            $html .= ' <button type="button" class="btn btn-link p-0 align-baseline modpress-field-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="' . esc_attr( (string) $options['tooltip'] ) . '" aria-label="' . esc_attr( (string) $options['tooltip'] ) . '"><i class="' . esc_attr( $icon ) . '" aria-hidden="true"></i></button>';
        }

        if ( ! empty( $options['description'] ) ) {
            $html .= '<div class="form-text">' . esc_html( (string) $options['description'] ) . '</div>';
        }

        return $html;

    }

    /**
     * Normalize a Font Awesome icon class while providing a fallback icon.
     *
     * @param string $icon     Icon class or icon name.
     * @param string $fallback Fallback icon name.
     * @return string Complete Font Awesome icon class list.
     */
    private static function icon_class( string $icon, string $fallback ): string {
        $icon = trim( $icon );
        if ( '' === $icon ) {
            $icon = $fallback;
        }

        return str_contains( $icon, 'fa-' ) ? ( str_contains( $icon, 'fa-solid' ) || str_contains( $icon, 'fa-regular' ) || str_contains( $icon, 'fa-brands' ) ? $icon : 'fa-solid ' . $icon ) : 'fa-solid fa-' . sanitize_html_class( $icon );
    }
    /**
     * Render a select option.
     *
     * @param string $value    The value attribute for the option.
     * @param string $label    The label text for the option.
     * @param bool   $selected Whether the option is selected.
     * @param bool   $disabled Whether the option is disabled.
     * @return string The HTML markup for the select option.
     */
    public static function form_text( string $text, array $options = [] ): string {

        $attributes = $options['attributes'] ?? [];
        $attributes['class'] = self::classes( [ 'form-text', $options['class'] ?? '' ] );

        if ( isset( $options['id'] ) ) {

            $attributes['id'] = $options['id'];

        }

        return '<div ' . self::attributes_to_string( $attributes ) . '>' . esc_html( $text ) . '</div>';

    }
    /**
     * Render validation feedback for a form field.
     *
     * @param array $options The options for the form field.
     * @return string The HTML markup for the validation feedback.
     */
    public static function feedback( array $options = [] ): string {

        $validation = $options['validation'] ?? [];

        if ( is_string( $validation ) ) {

            $validation = [ 'state' => 'invalid', 'message' => $validation ];

        }

        if ( empty( $validation['message'] ) ) {

            return '';

        }

        $state = 'valid' === ( $validation['state'] ?? 'invalid' ) ? 'valid' : 'invalid';
        $class = self::classes( [ $state . '-feedback', ! empty( $validation['tooltip'] ) ? $state . '-tooltip' : '' ] );
        $attributes = [ 'class' => $class ];

        if ( isset( $validation['id'] ) ) {

            $attributes['id'] = $validation['id'];

        }

        return '<div ' . self::attributes_to_string( $attributes ) . '>' . esc_html( $validation['message'] ) . '</div>';

    }
    /**
     * Render an input group with optional size and validation feedback.
     *
     * @param string $content The content of the input group.
     * @param array  $options The options for the input group.
     * @return string The HTML markup for the input group.
     */
    public static function input_group( string $content, array $options = [] ): string {

        $attributes = $options['attributes'] ?? [];
        $attributes['class'] = self::classes( [ 'input-group', ! empty( $options['size'] ) ? 'input-group-' . $options['size'] : '', ! empty( $options['validation'] ) ? 'has-validation' : '', $options['class'] ?? '' ] );

        return '<div ' . self::attributes_to_string( $attributes ) . '>' . $content . self::feedback( $options ) . '</div>';

    }

    /**
     * Render a floating label input group.
     *
     * @param string $control The input control HTML.
     * @param string $label   The label text for the floating label.
     * @param array  $options The options for the floating label.
     * @return string The HTML markup for the floating label input group.
     */
    public static function floating( string $control, string $label, array $options = [] ): string {

        $attributes = $options['attributes'] ?? [];
        $attributes['class'] = self::classes( [ 'form-floating', $options['class'] ?? '' ] );

        return '<div ' . self::attributes_to_string( $attributes ) . '>' . $control . self::label( (string) ( $options['for'] ?? '' ), $label, [ 'class' => $options['label_class'] ?? '' ] ) . '</div>';

    }

    /**
     * Render a form opening tag with optional attributes.
     *
     * @param string $action  The action URL for the form.
     * @param string $method  The HTTP method for the form (default: 'post').
     * @param array  $options Additional attributes for the form tag.
     * @return string The HTML markup for the form opening tag.
     */
    public static function form_open( string $action = '', string $method = 'post', array $options = [] ): string {

        $attributes = array_merge( $options['attributes'] ?? [], $options );
        unset( $attributes['attributes'], $attributes['class'], $attributes['action'], $attributes['method'], $attributes['validation'] );
        $attributes['action'] = $action;
        $attributes['method'] = strtolower( $method );
        $attributes['class'] = self::classes( [ $options['class'] ?? '', ! empty( $options['validation'] ) ? 'needs-validation' : '' ] );

        return '<form ' . self::attributes_to_string( $attributes ) . '>';

    }
    /**
     * Render a form closing tag.
     *
     * @return string The HTML markup for the form closing tag.
     */
    public static function form_close(): string {

        return '</form>';

    }
    /**
     * Convert an associative array of attributes to a string for HTML output.
     *
     * @param array $attributes The associative array of attributes.
     * @return string The string representation of the attributes.
     */
    public static function attributes_to_string( array $attributes ): string {

        $output = [];

        foreach ( $attributes as $key => $value ) {

            if ( null === $value || false === $value ) {

                continue;

            }

            $key = sanitize_key( (string) $key );

            if ( '' === $key ) {

                continue;

            }

            if ( true === $value && str_starts_with( $key, 'data-' ) ) {
                $output[] = esc_attr( $key ) . '="true"';
                continue;
            }

            $output[] = true === $value ? esc_attr( $key ) : esc_attr( $key ) . '="' . esc_attr( (string) $value ) . '"';

        }

        return implode( ' ', $output );

    }
    /**
     * Normalize and sanitize an array of CSS classes.
     *
     * @param array $classes The array of CSS classes.
     * @return string The normalized and sanitized CSS classes as a space-separated string.
     */
    private static function classes( array $classes ): string {

        $normalized = [];

        foreach ( $classes as $class_list ) {

            foreach ( preg_split( '/\s+/', trim( (string) $class_list ) ) as $class ) {

                if ( '' !== $class ) {

                    $normalized[] = sanitize_html_class( $class );

                }
            }
        }

        return implode( ' ', array_filter( array_unique( $normalized ) ) );

    }
    /**
     * Determine the validation class based on the provided options.
     *
     * @param array $options The options for the form field.
     * @return string The validation class ('is-valid', 'is-invalid', or an empty string).
     */
    private static function validation_class( array $options ): string {

        $validation = $options['validation'] ?? [];

        if ( is_string( $validation ) ) {

            return 'is-invalid';

        }

        if ( ! empty( $validation['state'] ) && in_array( $validation['state'], [ 'valid', 'invalid' ], true ) ) {

            return 'is-' . $validation['state'];

        }

        return '';

    }
    /**
     * Render a list of select options.
     *
     * @param array $options  The options for the select.
     * @param array $selected The selected value(s) for the select.
     * @return string The HTML markup for the select option list.
     */
    private static function option_list( array $options, array $selected ): string {

        $html = '';

        foreach ( $options as $key => $option ) {

            $value = is_array( $option ) ? (string) ( $option['value'] ?? $key ) : (string) $key;
            $label = is_array( $option ) ? (string) ( $option['label'] ?? $value ) : (string) $option;
            $html .= self::option( $value, $label, in_array( $value, $selected, true ), is_array( $option ) && ! empty( $option['disabled'] ), is_array( $option ) ? $option : [] );

        }

        return $html;

    }
    /**
     * Render a select option.
     *
     * @param string $value    The value attribute for the option.
     * @param string $label    The label text for the option.
     * @param bool   $selected Whether the option is selected.
     * @param bool   $disabled Whether the option is disabled.
     * @return string The HTML markup for the select option.
     */
    private static function option( string $value, string $label, bool $selected, bool $disabled, array $options = [] ): string {

        $attributes = [];
        foreach ( $options as $key => $option_value ) {
            if ( in_array( $key, [ 'value', 'label', 'selected', 'disabled', 'options' ], true ) || null === $option_value || false === $option_value ) {
                continue;
            }
            $attributes[ str_starts_with( $key, 'data-' ) ? $key : 'data-' . $key ] = $option_value;
        }

        $attributes['value'] = $value;
        if ( $selected ) {
            $attributes['selected'] = true;
        }
        if ( $disabled ) {
            $attributes['disabled'] = true;
        }

        return '<option ' . self::attributes_to_string( $attributes ) . '>' . esc_html( $label ) . '</option>';
        
    }
}
