<?php

namespace ModPress\Includes\Functions\Helpers;

use ModPress\Includes\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Convenience methods for defining ModPress shortcodes.
 */
final class ShortcodeHelper {
	/**
	 * Create a shortcode definition for a plugin shortcode list.
	 *
	 * @param array<string, mixed> $metadata Optional descriptor metadata.
	 * @return array<string, mixed>
	 */
	public static function define( string $tag, callable $callback, array $attributes = [], array $metadata = [] ): array {
		return array_merge(
			[
				'tag' => $tag,
				'callback' => $callback,
				'attributes' => $attributes,
				'description' => '',
				'category' => '',
				'enclosing' => false,
				'tinymce' => false,
			],
			$metadata
		);
	}

	/** @param array<string, mixed> $definition */
	public static function register( array $definition, bool $replace = false ): bool {
		return Includes::get_instance()->core()->shortcodes()->register( $definition, $replace );
	}

	/**
	 * @param array<int, array<string, mixed>> $definitions
	 * @return array<int, string>
	 */
	public static function register_many( array $definitions, bool $replace = false ): array {
		return Includes::get_instance()->core()->shortcodes()->register_many( $definitions, $replace );
	}
}
