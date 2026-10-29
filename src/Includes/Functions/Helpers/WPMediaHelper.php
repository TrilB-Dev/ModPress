<?php
/**
 * WordPress media helper.
 *
 * Provides a small Bootstrap 5-friendly wrapper around the WordPress media
 * modal so fields can accept either a saved image URL or attachment ID and
 * still render a live preview without jQuery.
 *
 * @package ModPress
 * @subpackage Includes\Functions\Helpers
 * @since 1.0.0
 */
namespace ModPress\Includes\Functions\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WPMediaHelper {
	/**
	 * Create a media picker field that can render an image preview and use the
	 * WordPress media library in a Bootstrap-friendly markup structure.
	 *
	 * Example:
	 * WPMediaHelper::create(
	 *     $image_url,
	 *     array(
	 *         'name' => 'hero_image',
	 *         'id' => 'hero_image',
	 *         'button_text' => __( 'Choose Image', 'modpress' ),
	 *         'class' => 'btn btn-primary',
	 *     ),
	 *     array(
	 *         'class' => 'img-fluid rounded',
	 *         'style' => 'card',
	 *     )
	 * );
	 *
	 * @param mixed $field_value The saved value; can be a URL, attachment ID, or empty string.
	 * @param array $button_config Button configuration passed through to FormFieldHelper.
	 * @param array $image_preview_config Preview configuration.
	 * @return string The rendered field markup.
	 */
	public static function create( $field_value = '', array $button_config = array(), array $image_preview_config = array() ): string {
		self::register_and_enqueue_script();

		$field_name        = (string) ( $button_config['name'] ?? 'media_value' );
		$field_id          = (string) ( $button_config['id'] ?? sanitize_key( $field_name ) );
		$button_label      = (string) ( $button_config['button_text'] ?? $button_config['text'] ?? __( 'Select Media', 'modpress' ) );
		$return_value_mode = (string) ( $button_config['return_value'] ?? 'url' );
		$value_type        = self::resolve_value_type( $field_value );
		$resolved_value    = 'id' === $return_value_mode ? self::resolve_attachment_id( $field_value ) : self::resolve_image_url( $field_value );
		$resolved_value    = is_string( $resolved_value ) ? $resolved_value : '';

		$wrapper_class = self::classes(
			array(
				'modpress-media-picker',
				$button_config['wrapper_class'] ?? '',
				$button_config['style'] ?? 'default',
			)
		);

		$preview_class = self::classes(
			array(
				'modpress-media-preview',
				'border rounded bg-light p-2',
				$image_preview_config['class'] ?? 'img-fluid rounded',
				$image_preview_config['style'] ?? 'card',
			)
		);

		$button_options = array_merge(
			array(
				'type' => 'button',
				'class' => 'btn btn-primary',
				'attributes' => array(
					'data-modpress-media-picker' => 'true',
					'data-media-field-name' => $field_name,
					'data-media-field-id' => $field_id,
					'data-media-target' => $field_name,
					'data-media-type' => $button_config['media_type'] ?? 'image',
					'data-return-value' => $return_value_mode,
					'data-media-title' => $button_config['media_title'] ?? __( 'Select Media', 'modpress' ),
					'data-media-button-text' => $button_config['media_button_text'] ?? __( 'Use Media', 'modpress' ),
				),
			),
			$button_config
		);
		unset( $button_options['button_text'], $button_options['text'], $button_options['name'], $button_options['id'], $button_options['wrapper_class'], $button_options['style'], $button_options['media_type'], $button_options['return_value'], $button_options['media_title'], $button_options['media_button_text'] );

		$button_markup = FormFieldHelper::button(
			$button_label,
			$button_options
		);

		$remove_button = '';
		if ( ! empty( $button_config['show_remove'] ) || ! empty( $image_preview_config['show_remove'] ) ) {
			$remove_button = FormFieldHelper::button(
				__( 'Remove', 'modpress' ),
				array(
					'type' => 'button',
					'class' => 'btn btn-outline-danger btn-sm d-none ms-2',
					'attributes' => array(
						'data-modpress-media-remove' => 'true',
						'aria-label' => __( 'Remove media', 'modpress' ),
					),
				)
			);
		}

		$preview_hidden = '' !== $resolved_value ? '' : ' d-none';
		$preview_src    = esc_url( $resolved_value );
		$preview_alt    = esc_attr( (string) ( $image_preview_config['alt'] ?? $field_name ) );
		$preview_markup = sprintf(
			'<div class="%1$s%2$s" data-modpress-media-preview-container="true">' .
			'<img src="%3$s" alt="%4$s" class="%5$s" data-modpress-media-preview-image="true" />' .
			'</div>',
			esc_attr( $preview_class ),
			esc_attr( $preview_hidden ),
			$preview_src,
			$preview_alt,
			esc_attr( self::classes( array( 'img-fluid', $image_preview_config['image_class'] ?? 'rounded' ) ) )
		);

		$hidden_input = FormFieldHelper::input(
			$field_name,
			(string) $resolved_value,
			array(
				'type' => 'hidden',
				'id' => $field_id,
				'attributes' => array(
					'data-media-field' => 'true',
					'data-media-field-name' => $field_name,
					'data-media-field-id' => $field_id,
					'data-media-target' => $field_name,
					'data-media-value-type' => $value_type,
				),
			)
		);

		return sprintf(
			'<div class="%1$s" data-modpress-media-wrapper="true" data-media-id="%2$s">%3$s%4$s%5$s%6$s</div>',
			esc_attr( $wrapper_class ),
			esc_attr( $field_id ),
			$hidden_input,
			'<div class="d-flex align-items-center flex-wrap gap-2 mb-2">' . $button_markup . $remove_button . '</div>',
			$preview_markup,
			! empty( $button_config['helper_text'] ) ? '<div class="form-text mt-2">' . esc_html( (string) $button_config['helper_text'] ) . '</div>' : ''
		);
	}

	/**
	 * Backward-compatible alias for create().
	 *
	 * @param mixed $field_value The current media value.
	 * @param array $button_config Button configuration.
	 * @param array $image_preview_config Preview configuration.
	 * @return string The rendered media picker field.
	 */
	public static function render( $field_value = '', array $button_config = array(), array $image_preview_config = array() ): string {
		return self::create( $field_value, $button_config, $image_preview_config );
	}

	/**
	 * Register and enqueue the delegated media-picker script through the helper.
	 *
	 * @return void
	 */
	private static function register_and_enqueue_script(): void {
		if ( function_exists( 'wp_enqueue_media' ) && ! did_action( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}

		if ( ! defined( 'MODPRESS_ASSETS_URL' ) ) {
			return;
		}

		$script_handle = 'modpress-admin-wpmedia';
		$script_src    = MODPRESS_ASSETS_URL . '/dist/js/admin.wpmedia.js';

		if ( ! wp_script_is( $script_handle, 'registered' ) ) {
			wp_register_script( $script_handle, $script_src, array( 'media-editor' ), defined( 'MODPRESS_VERSION' ) ? MODPRESS_VERSION : false, true );
		}

		wp_enqueue_script( $script_handle );
	}

	/**
	 * Resolve the active value type.
	 *
	 * @param mixed $value The field value.
	 * @return string 'url' | 'id' | 'empty'.
	 */
	private static function resolve_value_type( $value ): string {
		if ( null === $value || '' === trim( (string) $value ) ) {
			return 'empty';
		}

		if ( is_numeric( $value ) || ( is_string( $value ) && preg_match( '/^\d+$/', trim( $value ) ) ) ) {
			return 'id';
		}

		return filter_var( trim( (string) $value ), FILTER_VALIDATE_URL ) ? 'url' : 'url';
	}

	/**
	 * Resolve the attachment ID from a URL or raw value.
	 *
	 * @param mixed $value The field value.
	 * @return string The attachment ID as a string.
	 */
	private static function resolve_attachment_id( $value ): string {
		if ( is_numeric( $value ) ) {
			return (string) $value;
		}

		if ( is_string( $value ) ) {
			$value = trim( $value );
			if ( preg_match( '/^\d+$/', $value ) ) {
				return $value;
			}
		}

		return '';
	}

	/**
	 * Resolve the preview URL from either a URL or attachment ID.
	 *
	 * @param mixed $value The field value.
	 * @return string The public image URL.
	 */
	private static function resolve_image_url( $value ): string {
		if ( null === $value ) {
			return '';
		}

		if ( is_numeric( $value ) ) {
			return '';
		}

		if ( is_string( $value ) ) {
			$value = trim( $value );
			if ( '' === $value ) {
				return '';
			}
			return filter_var( $value, FILTER_VALIDATE_URL ) ? $value : '';
		}

		return '';
	}

	/**
	 * Get a safe combined CSS class string.
	 *
	 * @param array $classes The class names to combine.
	 * @return string The combined CSS classes.
	 */
	private static function classes( array $classes = array() ): string {
		$normalized = array();
		foreach ( $classes as $class ) {
			if ( ! is_string( $class ) ) {
				continue;
			}
			foreach ( preg_split( '/\s+/', trim( $class ) ) as $segment ) {
				if ( '' !== $segment ) {
					$normalized[] = $segment;
				}
			}
		}

		return implode( ' ', array_unique( $normalized ) );
	}
}