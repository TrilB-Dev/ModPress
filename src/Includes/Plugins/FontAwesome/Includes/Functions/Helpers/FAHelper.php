<?php

namespace ModPress\Includes\Plugins\FontAwesome\Includes\Functions\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

final class FAHelper {
	/**
	 * Render a Font Awesome icon element.
	 *
	 * Supports the official Font Awesome styling API, including modifier classes,
	 * transforms, animations, and inline custom properties.
	 *
	 * @param string $icon_name The icon name, for example `house` or `fa-house`.
	 * @param array  $options   Rendering options.
	 * @return string The output markup.
	 */
	public static function render_icon( string $icon_name, array $options = [] ): string {
		$icon_name = self::normalize_icon_name( $icon_name );
		if ( '' === $icon_name ) {
			return '';
		}

		$family = self::normalize_family( $options['family'] ?? 'classic' );
		$style = self::normalize_style( $family, $options['style'] ?? 'solid' );
		$classes = [];

		if ( 'brands' === $family ) {
			$classes[] = 'fa-brands';
		} else {
			$classes[] = 'fa-' . $style;
		}

		$classes[] = $icon_name;

		$size = self::normalize_size( $options['size'] ?? '' );
		if ( ! empty( $size ) ) {
			$classes = array_merge( $classes, (array) $size );
		}

		$classes = array_merge( $classes, self::build_modifier_classes( $options ) );
		$classes = array_values( array_unique( array_filter( $classes, 'strlen' ) ) );

		$attributes = self::build_attributes( $options );
		$attributes['class'] = implode( ' ', $classes );

		$style_string = self::build_inline_style( $options );
		$tag = '<i';
		foreach ( $attributes as $name => $value ) {
			if ( '' === (string) $value ) {
				continue;
			}
			$tag .= ' ' . esc_attr( $name ) . '="' . esc_attr( (string) $value ) . '"';
		}
		if ( '' !== $style_string ) {
			$tag .= ' style="' . esc_attr( $style_string ) . '"';
		}
		$tag .= '></i>';

		return $tag;
	}
	/**
	 * Alias for render_icon.
	 *
	 * @param string $icon_name The icon name.
	 * @param array  $options   Rendering options.
	 * @return string The output markup.
	 */
	public static function faicon( string $icon_name, array $options = [] ): string {
		
		return self::render_icon( $icon_name, $options );
	}
	/**
	 * Normalize the icon name by removing any leading "fa-" and converting it to a valid format.
	 *
	 * @param string $icon_name The icon name.
	 * @return string The normalized icon name with "fa-" prefix.
	 * @internal This method is used internally by the FAHelper class to generate icon names.
	 * @since 1.0.0
	 */
	private static function normalize_icon_name( string $icon_name ): string {
		$icon_name = trim( $icon_name );
		if ( '' === $icon_name ) {
			return '';
		}

		$icon_name = preg_replace( '/^fa-+/i', '', $icon_name );
		$icon_name = trim( preg_replace( '/\s+/', '-', strtolower( $icon_name ) ) );
		$icon_name = preg_replace( '/[^a-z0-9_\-]/', '-', $icon_name );
		$icon_name = trim( preg_replace( '/-+/', '-', $icon_name ), '-' );

		return '' === $icon_name ? '' : 'fa-' . $icon_name;
	}
	/**
	 * Normalize the icon family by converting it to a valid format.
	 *
	 * @param string $family The icon family.
	 * @return string The normalized icon family.
	 * @internal This method is used internally by the FAHelper class to generate icon families.
	 * @since 1.0.0
	 */
	private static function normalize_family( string $family ): string {
		$family = strtolower( trim( $family ) );
		if ( in_array( $family, [ 'brands', 'brand' ], true ) ) {
			return 'brands';
		}
		return 'classic';
	}
	/**
	 * Normalize the icon style by converting it to a valid format.
	 *
	 * @param string $family The icon family.
	 * @param string $style  The icon style.
	 * @return string The normalized icon style.
	 * @internal This method is used internally by the FAHelper class to generate icon styles.
	 * @since 1.0.0
	 */
	private static function normalize_style( string $family, string $style ): string {
		if ( 'brands' === $family ) {
			return 'brands';
		}

		$style = strtolower( trim( $style ) );
		$valid_styles = [ 'solid', 'regular', 'light', 'thin', 'duotone' ];
		return in_array( $style, $valid_styles, true ) ? $style : 'solid';
	}

	/**
	 * Normalize the icon size by converting it to a valid format.
	 *
	 * @param string $size The icon size.
	 * @return array The corresponding size classes.
	 * @internal This method is used internally by the FAHelper class to generate size classes.
	 * @since 1.0.0
	 */
	private static function normalize_size( string $size ) {
		$size = strtolower( trim( $size ) );
		$valid_sizes = [
			'2xs' => [ 'fa-size-2xs', 'fa-2xs' ],
			'xs' => [ 'fa-size-xs', 'fa-xs' ],
			'sm' => [ 'fa-size-sm', 'fa-sm' ],
			'lg' => [ 'fa-size-lg', 'fa-lg' ],
			'xl' => [ 'fa-size-xl', 'fa-xl' ],
			'2xl' => [ 'fa-size-2xl', 'fa-2xl' ],
			'1x' => [ 'fa-size-1x', 'fa-1x' ],
			'2x' => [ 'fa-size-2x', 'fa-2x' ],
			'3x' => [ 'fa-size-3x', 'fa-3x' ],
			'5x' => [ 'fa-size-5x', 'fa-5x' ],
			'7x' => [ 'fa-size-7x', 'fa-7x' ],
			'10x' => [ 'fa-size-10x', 'fa-10x' ],
		];

		return $valid_sizes[ $size ] ?? [];
	}
	/**
	 * Build the modifier classes for the icon based on the provided options.
	 *
	 * @param array $options The rendering options.
	 * @return array The array of modifier classes.
	 * @internal This method is used internally by the FAHelper class to generate modifier classes.
	 * @since 1.0.0
	 */
	private static function build_modifier_classes( array $options ): array {
		$classes = [];

		if ( ! empty( $options['fixed_width'] ) ) {
			$classes[] = 'fa-fw';
		}
		if ( ! empty( $options['border'] ) ) {
			$classes[] = 'fa-border';
		}
		if ( ! empty( $options['li'] ) || ! empty( $options['list_item'] ) ) {
			$classes[] = 'fa-li';
		}
		if ( ! empty( $options['pull'] ) ) {
			$pull = strtolower( trim( (string) $options['pull'] ) );
			if ( 'left' === $pull ) {
				$classes[] = 'fa-pull-left';
			} elseif ( 'right' === $pull ) {
				$classes[] = 'fa-pull-right';
			}
		}

		$flip = strtolower( trim( (string) ( $options['flip'] ?? '' ) ) );
		if ( in_array( $flip, [ 'horizontal', 'vertical', 'both' ], true ) ) {
			$classes[] = 'fa-flip-' . $flip;
		}

		$rotation = $options['rotate'] ?? null;
		if ( is_numeric( $rotation ) || ( is_string( $rotation ) && preg_match( '/^-?\d+$/', (string) $rotation ) ) ) {
			$classes[] = 'fa-rotate-' . (int) $rotation;
		}

		$animation = strtolower( trim( (string) ( $options['animation'] ?? '' ) ) );
		if ( in_array( $animation, [ 'spin', 'pulse', 'beat', 'bounce', 'fade', 'beat-fade', 'flash', 'shake' ], true ) ) {
			$classes[] = 'fa-' . str_replace( '_', '-', $animation );
		}

		foreach ( [ 'spin', 'pulse', 'beat', 'bounce', 'fade', 'beat_fade', 'beat-fade', 'flash', 'shake' ] as $key ) {
			if ( ! empty( $options[ $key ] ) ) {
				$classes[] = 'fa-' . str_replace( '_', '-', $key );
			}
		}

		if ( ! empty( $options['class'] ) ) {
			$custom_classes = is_array( $options['class'] ) ? $options['class'] : preg_split( '/\s+/', (string) $options['class'] );
			foreach ( (array) $custom_classes as $custom_class ) {
				$trimmed = trim( (string) $custom_class );
				if ( '' !== $trimmed ) {
					$classes[] = $trimmed;
				}
			}
		}

		return $classes;
	}

	/**
	 * Build the HTML attributes for the icon based on the provided options.
	 *
	 * @param array $options The rendering options.
	 * @return array The array of HTML attributes.
	 * @internal This method is used internally by the FAHelper class to generate HTML attributes.
	 * @since 1.0.0
	 */
	private static function build_attributes( array $options ): array {
		$attributes = [];
		$ignored_keys = [
			'family',
			'style',
			'size',
			'fixed_width',
			'border',
			'li',
			'list_item',
			'pull',
			'flip',
			'rotate',
			'animation',
			'spin',
			'pulse',
			'beat',
			'bounce',
			'fade',
			'beat_fade',
			'beat-fade',
			'flash',
			'shake',
			'class',
			'classes',
			'transform',
			'inline_style',
			'color',
			'font_size',
			'primary_color',
			'secondary_color',
			'secondary_opacity',
			'opacity',
		];

		foreach ( $options as $key => $value ) {
			if ( in_array( $key, $ignored_keys, true ) ) {
				continue;
			}

			if ( 'aria_label' === $key ) {
				$attributes['aria-label'] = $value;
				continue;
			}
			if ( 'aria_hidden' === $key ) {
				$attributes['aria-hidden'] = ! empty( $value ) ? 'true' : 'false';
				continue;
			}
			if ( 'title' === $key ) {
				$attributes['title'] = $value;
				continue;
			}

			if ( is_bool( $value ) ) {
				$attributes[ self::normalize_attribute_name( $key ) ] = $value ? 'true' : 'false';
				continue;
			}

			if ( is_scalar( $value ) || null === $value ) {
				$attributes[ self::normalize_attribute_name( $key ) ] = (string) $value;
			}
		}

		return $attributes;
	}
	/**
	 * Build the inline style string for the icon based on the provided options.
	 *
	 * @param array $options The rendering options.
	 * @return string The inline style string.
	 * @internal This method is used internally by the FAHelper class to generate inline styles.
	 * @since 1.0.0
	 */
	private static function normalize_attribute_name( string $key ): string {
		$key = trim( $key );
		if ( '' === $key ) {
			return '';
		}
		if ( 0 === stripos( $key, 'data_' ) ) {
			return 'data-' . str_replace( '_', '-', substr( $key, 5 ) );
		}
		if ( 0 === stripos( $key, 'aria_' ) ) {
			return 'aria-' . str_replace( '_', '-', substr( $key, 5 ) );
		}
		return str_replace( '_', '-', $key );
	}
	/**
	 * Normalize the HTML attribute name by converting underscores to hyphens and handling data and aria attributes.
	 *
	 * @param string $key The attribute name.
	 * @return string The normalized attribute name.
	 * @internal This method is used internally by the FAHelper class to generate inline styles.
	 * @since 1.0.0
	 */
	private static function build_inline_style( array $options ): string {
		$styles = [];

		$transform = $options['transform'] ?? '';
		if ( is_array( $transform ) ) {
			$parts = [];
			if ( ! empty( $transform['rotate'] ) ) {
				$parts[] = 'rotate(' . (string) $transform['rotate'] . ')';
			}
			if ( ! empty( $transform['x'] ) || ! empty( $transform['y'] ) ) {
				$parts[] = 'translate(' . (string) ( $transform['x'] ?? 0 ) . 'px, ' . (string) ( $transform['y'] ?? 0 ) . 'px)';
			}
			if ( ! empty( $transform['grow'] ) ) {
				$parts[] = 'grow(' . (string) $transform['grow'] . ')';
			}
			if ( ! empty( $transform['shrink'] ) ) {
				$parts[] = 'shrink(' . (string) $transform['shrink'] . ')';
			}
			if ( ! empty( $transform['scale'] ) ) {
				$parts[] = 'scale(' . (string) $transform['scale'] . ')';
			}
			if ( ! empty( $parts ) ) {
				$styles[] = '--fa-transform: ' . implode( ' ', $parts ) . ';';
			}
		} elseif ( is_string( $transform ) && '' !== trim( $transform ) ) {
			$styles[] = '--fa-transform: ' . trim( $transform ) . ';';
		}

		if ( ! empty( $options['color'] ) ) {
			$styles[] = 'color: ' . sanitize_text_field( (string) $options['color'] ) . ';';
		}
		if ( ! empty( $options['font_size'] ) ) {
			$styles[] = 'font-size: ' . sanitize_text_field( (string) $options['font_size'] ) . ';';
		}
		if ( ! empty( $options['primary_color'] ) ) {
			$styles[] = '--fa-primary-color: ' . sanitize_text_field( (string) $options['primary_color'] ) . ';';
		}
		if ( ! empty( $options['secondary_color'] ) ) {
			$styles[] = '--fa-secondary-color: ' . sanitize_text_field( (string) $options['secondary_color'] ) . ';';
		}
		if ( ! empty( $options['secondary_opacity'] ) ) {
			$styles[] = '--fa-secondary-opacity: ' . sanitize_text_field( (string) $options['secondary_opacity'] ) . ';';
		}
		if ( ! empty( $options['opacity'] ) ) {
			$styles[] = '--fa-opacity: ' . sanitize_text_field( (string) $options['opacity'] ) . ';';
		}
		if ( ! empty( $options['inline_style'] ) ) {
			$styles[] = (string) $options['inline_style'];
		}

		return implode( ' ', $styles );
	}
}