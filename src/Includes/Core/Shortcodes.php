<?php
/**
 * Core shortcode definitions for ModPress.
 *
 * @package ModPress
 * @subpackage Includes\Core
 * @since 1.0.0
 */
namespace ModPress\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register and process ModPress shortcode definitions.
 */
final class Shortcodes {
	/**
	 * Registered shortcode definitions.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $definitions = array();

	/**
	 * Register a new shortcode.
	 *
	 * @param array<string, mixed> $definition Shortcode definition.
	 * @param bool $replace Whether to replace an existing shortcode with the same tag.
	 * @return bool True if the shortcode was successfully registered, false otherwise.
	 * @param array<string, mixed> $definition Shortcode definition.
	 */
	public function register( array $definition, bool $replace = false ): bool {
		$definition = $this->normalize_definition( $definition );
		$tag        = $definition['tag'];

		if ( isset( $this->definitions[ $tag ] ) && ! $replace ) {
			return false;
		}

		$this->definitions[ $tag ] = $definition;
		add_shortcode( $tag, array( $this, 'process' ) );

		return true;
	}

	/**
	 * Register multiple shortcodes at once.
	 *
	 * @param array<int, array<string, mixed>> $definitions Shortcode definitions.
	 * @param bool $replace Whether to replace existing shortcodes with the same tags.
	 * @return array<int, string> Registered tags.
	 */
	public function register_many( array $definitions, bool $replace = false ): array {
		$registered = array();

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
	 * @param string $tag Shortcode tag to unregister.
	 * @return bool True if the shortcode was successfully unregistered, false otherwise.
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
	 * @return array<string, array<string, mixed>> All registered shortcode definitions.
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
	public function process( $atts = array(), $content = null, string $tag = '' ): string {
		$tag        = $this->normalize_tag( $tag );
		$definition = $this->definition( $tag );
		if ( null === $definition ) {
			return '';
		}

		$attributes = is_array( $atts ) ? array_change_key_case( $atts, CASE_LOWER ) : array();
		$defaults   = $definition['attributes'];
		$attributes = function_exists( 'shortcode_atts' )
			? shortcode_atts( $defaults, $attributes, $tag )
			: array_merge( $defaults, $attributes );
		$output     = call_user_func( $definition['callback'], $attributes, $content, $tag );

		return is_string( $output ) ? $output : (string) $output;
	}

	/**
	 * Normalize a shortcode definition to ensure it has all required fields.
	 * This includes validating the tag, callback, and attributes.
	 *
	 * @param array<string, mixed> $definition Shortcode definition.
	 * @return array<string, mixed>
	 * @throws \InvalidArgumentException If a shortcode definition is invalid.
	 */
	private function normalize_definition( array $definition ): array {
		$tag = $this->normalize_tag( $definition['tag'] ?? '' );
		if ( '' === $tag ) {
			throw new \InvalidArgumentException( 'A shortcode tag is required.' );
		}
		if ( ! isset( $definition['callback'] ) || ! is_callable( $definition['callback'] ) ) {
			throw new \InvalidArgumentException( sprintf( 'Shortcode callback for "%s" must be callable.', wp_strip_all_tags( $tag ) ) );
		}

		$attributes = $definition['attributes'] ?? $definition['defaults'] ?? array();
		if ( ! is_array( $attributes ) ) {
			throw new \InvalidArgumentException( sprintf( 'Shortcode attributes for "%s" must be an array.', wp_strip_all_tags( $tag ) ) );
		}

		return array_merge(
			array(
				'tag'         => $tag,
				'callback'    => $definition['callback'],
				'attributes'  => array_change_key_case( $attributes, CASE_LOWER ),
				'description' => '',
				'category'    => '',
				'enclosing'   => false,
				'tinymce'     => false,
			),
			$definition,
			array(
				'tag'        => $tag,
				'attributes' => array_change_key_case( $attributes, CASE_LOWER ),
			)
		);
	}

	private function normalize_tag( $tag ): string {
		return strtolower( trim( (string) $tag ) );
	}
}




