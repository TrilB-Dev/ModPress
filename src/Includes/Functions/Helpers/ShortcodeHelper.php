<?php
/**
 * Backward-compatible shortcode helper.
 *
 * @package ModPress
 * @subpackage Includes\Functions\Helpers
 * @since 1.0.0
 */
namespace ModPress\Includes\Functions\Helpers;

use ModPress\Includes\Core\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ShortcodeHelper {
	/**
	 * Build a normalized shortcode definition array.
	 *
	 * @param string $tag Shortcode tag.
	 * @param callable $callback Callback to execute when shortcode is rendered.
	 * @param array<string, mixed> $attributes Default shortcode attributes.
	 * @param array<string, mixed> $metadata Optional definition metadata.
	 * @return array<string, mixed>
	 */
	public static function define( string $tag, callable $callback, array $attributes = array(), array $metadata = array() ): array {
		return Shortcodes::define( $tag, $callback, $attributes, $metadata );
	}

	/**
	 * Register a single shortcode definition.
	 *
	 * @param array<string, mixed> $definition Shortcode definition array.
	 * @param bool $replace Whether to replace an existing shortcode with the same tag.
	 * @return bool True on success, false on failure.
	 */
	public static function register( array $definition, bool $replace = false ): bool {
		return Shortcodes::get_instance()->register( $definition, $replace );
	}

	/**
	 * Register multiple shortcode definitions at once.
	 *
	 * @param array<int, array<string, mixed>> $definitions Array of shortcode definitions.
	 * @param bool $replace Whether to replace existing shortcodes with the same tags.
	 * @return array<int, string> List of registered shortcode tags.
	 */
	public static function register_many( array $definitions, bool $replace = false ): array {
		return Shortcodes::get_instance()->register_many( $definitions, $replace );
	}
}





