<?php
/**
 * Shortcodes management class.
 * Handles the registration and processing of shortcodes within ModPress.
 * 
 * @package ModPress\Includes\Core
 * @since 1.0.0
 */
namespace ModPress\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


final class Shortcodes {
	/**
	 * Registered shortcode definitions.
	 * 
	 * @var array<string, array<string, mixed>> Registered shortcode definitions.
	 */
	private array $definitions = [];

	/**
	 * Register a new shortcode.
	 *
	 * @param array<string, mixed> $definition Shortcode definition.
	 * @param bool $replace Whether to replace an existing shortcode with the same tag.
	 * @return bool True if the shortcode was registered, false otherwise.
	 */
	public function register( array $definition, bool $replace = false ): bool {
		$definition = $this->normalize_definition( $definition );
		$tag = $definition['tag'];

		if ( isset( $this->definitions[ $tag ] ) && ! $replace ) {
			return false;
		}

		$this->definitions[ $tag ] = $definition;
		add_shortcode( $tag, [ $this, 'process' ] );

		return true;
	}

	/**
	 * Register multiple shortcodes at once.
	 *
	 * @param bool $replace Whether to replace existing shortcodes with the same tags.
	 * @param array<int, array<string, mixed>> $definitions Shortcode definitions.
	 * @return array<int, string> Registered tags.
	 */
	public function register_many( array $definitions, bool $replace = false ): array {
		$registered = [];

		foreach ( $definitions as $definition ) {
			if ( $this->register( $definition, $replace ) ) {
				$registered[] = $this->normalize_tag( $definition['tag'] );
			}
		}

		return $registered;
	}
	/**
	 * Unregister a shortcode by its tag.
	 *
	 * @param string $tag Shortcode tag.
	 * @return bool True if the shortcode was unregistered, false otherwise.
	 */
	public function unregister( string $tag ): bool {
		$tag = $this->normalize_tag( $tag );
		if ( ! isset( $this->definitions[ $tag ] ) ) {
			return false;
		}

		unset( $this->definitions[ $tag ] );
		remove_shortcode( $tag );

		return true;
	}

	/**
	 * Get all registered shortcode definitions.
	 *
	 * @return array<string, array<string, mixed>> Registered shortcode definitions.
	 */
	public function definitions(): array {
		return $this->definitions;
	}

	/**
	 * Get the definition of a specific shortcode by its tag.
	 *
	 * @param string $tag Shortcode tag.
	 * @return array<string, mixed>|null Shortcode definition or null if not found.
	 */
	public function definition( string $tag ): ?array {
		return $this->definitions[ $this->normalize_tag( $tag ) ] ?? null;
	}

	/**
	 * Check if a shortcode with the given tag is registered.
	 *
	 * @param string $tag Shortcode tag.
	 * @return bool True if the shortcode is registered, false otherwise.
	 */
	public function has( string $tag ): bool {
		return isset( $this->definitions[ $this->normalize_tag( $tag ) ] );
	}

	/**
	 * WordPress shortcode callback. Callbacks must return their output.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @param string|null  $content Enclosed content, or null for self-closing use.
	 * @param string       $tag Shortcode tag.
	 * @return string Shortcode output.
	 */
	public function process( $atts = [], $content = null, string $tag = '' ): string {
		$tag = $this->normalize_tag( $tag );
		$definition = $this->definition( $tag );
		if ( null === $definition ) {
			return '';
		}

		$attributes = is_array( $atts ) ? array_change_key_case( $atts, CASE_LOWER ) : [];
		$defaults = $definition['attributes'];
		$attributes = function_exists( 'shortcode_atts' )
			? shortcode_atts( $defaults, $attributes, $tag )
			: array_merge( $defaults, $attributes );
		$output = call_user_func( $definition['callback'], $attributes, $content, $tag );

		return is_string( $output ) ? $output : (string) $output;
	}

	/**
	 * Normalize a shortcode definition.
	 *
	 * @param array $definition Shortcode definition.
	 * @return array<string, mixed> Normalized shortcode definition.
	 */
	private function normalize_definition( array $definition ): array {
		$tag = $this->normalize_tag( $definition['tag'] ?? '' );
		if ( '' === $tag ) {
			throw new \InvalidArgumentException( 'A shortcode tag is required.' );
		}
		if ( ! isset( $definition['callback'] ) || ! is_callable( $definition['callback'] ) ) {
            throw new \InvalidArgumentException( sprintf( 'Shortcode callback for "%s" must be callable.', wp_strip_all_tags( $tag ) ) );
        }

        $attributes = $definition['attributes'] ?? $definition['defaults'] ?? [];
        if ( ! is_array( $attributes ) ) {
            throw new \InvalidArgumentException( sprintf( 'Shortcode attributes for "%s" must be an array.', wp_strip_all_tags( $tag ) ) );
        }

        return array_merge(
			[
				'tag' => $tag,
				'callback' => $definition['callback'],
				'attributes' => array_change_key_case( $attributes, CASE_LOWER ),
				'description' => '',
				'category' => '',
				'enclosing' => false,
				'tinymce' => false,
			],
			$definition,
			[ 'tag' => $tag, 'attributes' => array_change_key_case( $attributes, CASE_LOWER ) ]
		);
	}
	/**
	 * Normalize a shortcode tag.
	 *
	 * @param string $tag Shortcode tag.
	 * @return string Normalized shortcode tag.
	 */
	private function normalize_tag( $tag ): string {
		return strtolower( trim( (string) $tag ) );
	}
}
