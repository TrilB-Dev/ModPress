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
		$value_type       = self::resolve_value_type( $field_value );
		$return_value_mode = (string) ( $button_config['return_value'] ?? ( 'id' === $value_type ? 'id' : 'url' ) );
		$is_multiple       = ! empty( $button_config['multiple'] );
		$normalized_values = self::normalize_media_values( $field_value, $is_multiple );
		$resolved_value    = self::resolve_hidden_value( $normalized_values, $return_value_mode, $is_multiple );

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
					'data-multiple' => $is_multiple ? 'true' : 'false',
				),
			),
			$button_config
		);
		unset(
			$button_options['button_text'],
			$button_options['text'],
			$button_options['name'],
			$button_options['id'],
			$button_options['wrapper_class'],
			$button_options['style'],
			$button_options['media_type'],
			$button_options['return_value'],
			$button_options['media_title'],
			$button_options['media_button_text'],
			$button_options['multiple']
		);

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

		$preview_markup = self::render_preview_markup( $normalized_values, $is_multiple, $preview_class, $image_preview_config );

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
					'data-multiple' => $is_multiple ? 'true' : 'false',
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
		if ( function_exists( 'wp_enqueue_media' ) && function_exists( 'did_action' ) && ! did_action( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}

		if ( ! defined( 'MODPRESS_ASSETS_URL' ) ) {
			return;
		}

		$script_handle = 'modpress-admin-wpmedia';
		$script_src    = MODPRESS_ASSETS_URL . '/dist/js/admin.wpmedia.js';

		if ( function_exists( 'wp_script_is' ) && ! wp_script_is( $script_handle, 'registered' ) ) {
			if ( function_exists( 'wp_register_script' ) ) {
				wp_register_script( $script_handle, $script_src, array( 'media-editor' ), defined( 'MODPRESS_VERSION' ) ? MODPRESS_VERSION : false, true );
			}
		}

		if ( function_exists( 'wp_enqueue_script' ) ) {
			wp_enqueue_script( $script_handle );
		}
	}

	/**
	 * Normalize the input into a list of saveable values.
	 *
	 * @param mixed $value The raw field value.
	 * @param bool  $is_multiple Whether multiple values should be allowed.
	 * @return array The normalized list of values.
	 */
	private static function normalize_media_values( $value, bool $is_multiple = false ): array {
		if ( is_array( $value ) ) {
			$items = $value;
		} elseif ( is_string( $value ) ) {
			$value = trim( $value );
			if ( '' === $value ) {
				return array();
			}

			$decoded = json_decode( wp_unslash( $value ), true );
			if ( is_array( $decoded ) ) {
				$items = $decoded;
			} else {
				$items = preg_split( '/[\s,]+/', $value ) ?: array();
			}
		} else {
			$items = array( $value );
		}

		$normalized = array();
		foreach ( $items as $item ) {
			if ( is_array( $item ) ) {
				foreach ( $item as $nested ) {
					$nested_value = trim( (string) $nested );
					if ( '' !== $nested_value ) {
						$normalized[] = $nested_value;
					}
				}
				continue;
			}

			$item_value = trim( (string) $item );
			if ( '' !== $item_value ) {
				$normalized[] = $item_value;
			}
		}

		$unique = array_values( array_unique( $normalized ) );
		if ( ! $is_multiple && ! empty( $unique ) ) {
			return array( $unique[0] );
		}

		return $unique;
	}

	/**
	 * Render a preview for either a single-value or multi-value media field.
	 *
	 * @param array  $values The normalized values to render.
	 * @param bool   $is_multiple Whether this field expects multiple values.
	 * @param string $preview_class The CSS class for the preview wrapper.
	 * @param array  $image_preview_config The preview configuration.
	 * @return string The rendered preview markup.
	 */
	private static function render_preview_markup( array $values, bool $is_multiple, string $preview_class, array $image_preview_config ): string {
		if ( empty( $values ) ) {
			return sprintf(
				'<div class="%1$s d-none" data-modpress-media-preview-container="true"><div data-modpress-media-preview-list="true"></div></div>',
				esc_attr( $preview_class )
			);
		}

		$preview_items = array();
		foreach ( $values as $value ) {
			$preview_url = self::resolve_image_url( $value );
			if ( '' === $preview_url ) {
				continue;
			}

			$preview_items[] = sprintf(
				'<img src="%1$s" alt="%2$s" class="%3$s" style="max-width:120px; max-height:120px; width:auto; height:auto; object-fit:cover;" data-modpress-media-preview-image="true" />',
				esc_url( $preview_url ),
				esc_attr( (string) ( $image_preview_config['alt'] ?? 'media preview' ) ),
				esc_attr( self::classes( array( 'img-fluid', 'rounded', $image_preview_config['image_class'] ?? '' ) ) )
			);
		}

		if ( empty( $preview_items ) ) {
			return sprintf(
				'<div class="%1$s d-none" data-modpress-media-preview-container="true"><div data-modpress-media-preview-list="true"></div></div>',
				esc_attr( $preview_class )
			);
		}

		$preview_content = $is_multiple
			? '<div class="d-flex flex-wrap gap-2" data-modpress-media-preview-list="true">' . implode( '', $preview_items ) . '</div>'
			: $preview_items[0];

		return sprintf(
			'<div class="%1$s" data-modpress-media-preview-container="true">%2$s</div>',
			esc_attr( $preview_class ),
			$preview_content
		);
	}

	/**
	 * Resolve a hidden value for storage.
	 *
	 * @param array  $values The normalized values.
	 * @param string $return_value_mode The field return mode.
	 * @param bool   $is_multiple Whether the field supports multiple selection.
	 * @return string The serialized hidden value.
	 */
	private static function resolve_hidden_value( array $values, string $return_value_mode, bool $is_multiple ): string {
		if ( empty( $values ) ) {
			return '';
		}

		$resolved = array();
		foreach ( $values as $value ) {
			if ( 'id' === $return_value_mode ) {
				$id_value = self::resolve_attachment_id( $value );
				if ( '' !== $id_value ) {
					$resolved[] = $id_value;
				}
				continue;
			}

			$url_value = self::resolve_image_url( $value );
			if ( '' !== $url_value ) {
				$resolved[] = $url_value;
			}
		}

		if ( empty( $resolved ) ) {
			return '';
		}

		return $is_multiple ? implode( ',', $resolved ) : (string) $resolved[0];
	}

	/**
	 * Resolve the active value type.
	 *
	 * @param mixed $value The field value.
	 * @return string 'url' | 'id' | 'empty'.
	 */
	private static function resolve_value_type( $value ): string {
		$values = self::normalize_media_values( $value, true );
		if ( empty( $values ) ) {
			return 'empty';
		}

		foreach ( $values as $entry ) {
			if ( preg_match( '/^\d+$/', trim( (string) $entry ) ) ) {
				continue;
			}

			return filter_var( trim( (string) $entry ), FILTER_VALIDATE_URL ) ? 'url' : 'url';
		}

		return 'id';
	}

	/**
	 * Resolve the attachment ID from a URL or raw value.
	 *
	 * @param mixed $value The field value.
	 * @return string The attachment ID as a string.
	 */
	private static function resolve_attachment_id( $value ): string {
		if ( is_array( $value ) ) {
			$ids = array();
			foreach ( $value as $entry ) {
				$id_value = self::resolve_attachment_id( $entry );
				if ( '' !== $id_value ) {
					$ids[] = $id_value;
				}
			}
			return implode( ',', array_unique( $ids ) );
		}

		if ( is_numeric( $value ) ) {
			return (string) $value;
		}

		if ( is_string( $value ) ) {
			$value = trim( $value );
			if ( preg_match( '/^\d+$/', $value ) ) {
				return $value;
			}
			if ( preg_match( '/^\d+(?:\s*,\s*\d+)+$/', $value ) ) {
				return preg_replace( '/\s+/', '', $value );
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

		if ( is_array( $value ) ) {
			foreach ( $value as $entry ) {
				$url_value = self::resolve_image_url( $entry );
				if ( '' !== $url_value ) {
					return $url_value;
				}
			}
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
			if ( preg_match( '/^\d+(?:\s*,\s*\d+)+$/', $value ) ) {
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