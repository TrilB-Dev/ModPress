<?php
/**
 * Convenience methods for defining ModPress shortcodes.
 *
 * @package ModPress
 * @subpackage Includes\Functions\Helpers
 * @since 1.0.0
 */
namespace ModPress\Includes\Functions\Helpers;

use ModPress\Includes\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ShortcodeHelper {
	/**
	 * Create a shortcode definition for a plugin shortcode list.
	 *
	 * @param array<string, mixed> $metadata Optional descriptor metadata.
	 * @return array<string, mixed>
	 */
	public static function define( string $tag, callable $callback, array $attributes = array(), array $metadata = array() ): array {
		return array_merge(
			array(
				'tag'         => $tag,
				'callback'    => $callback,
				'attributes'  => $attributes,
				'description' => '',
				'category'    => '',
				'enclosing'   => false,
				'tinymce'     => false,
			),
			$metadata
		);
	}

	/**
	 * Register a single shortcode definition.
	 *
	 * @param array<string, mixed> $definition Shortcode definition array.
	 * @param bool $replace Whether to replace an existing shortcode with the same tag.
	 * @return bool True on success, false on failure.
	 * @since 1.0.0
	 */
	public static function register( array $definition, bool $replace = false ): bool {
		return Includes::get_instance()->core()->shortcodes()->register( $definition, $replace );
	}

	/**
	 * Register multiple shortcode definitions at once.
	 *
	 * @param array<int, array<string, mixed>> $definitions Array of shortcode definitions.
	 * @param bool $replace Whether to replace existing shortcodes with the same tags.
	 * @return array<int, string> List of registered shortcode tags.
	 * @since 1.0.0
	 */
	public static function register_many( array $definitions, bool $replace = false ): array {
		return Includes::get_instance()->core()->shortcodes()->register_many( $definitions, $replace );
	}
}





