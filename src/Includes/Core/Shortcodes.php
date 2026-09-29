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
	 * Shared registry singleton.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registered shortcode definitions.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static array $registered = array();

	/**
	 * Registered shortcode definitions on the current instance.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $definitions = array();

	/**
	 * Get the shared shortcode registry singleton.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		return self::$instance ??= new self();
	}

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
		return self::normalize_definition(
			array_merge(
				array(
					'tag'        => $tag,
					'callback'   => $callback,
					'attributes' => $attributes,
				),
				$metadata
			)
		);
	}

	/**
	 * Register a shortcode definition by tag.
	 *
	 * @param string $tag Shortcode tag.
	 * @param array<string, mixed> $config Registration configuration.
	 * @return bool
	 */
	public static function create( string $tag, array $config = array() ): bool {
		$tag = self::normalize_tag( $tag );
		if ( '' === $tag ) {
			return false;
		}

		if ( isset( self::$registered[ $tag ] ) || shortcode_exists( $tag ) ) {
			return false;
		}

		$definition = self::normalize_definition( array_merge( array( 'tag' => $tag ), $config ) );
		self::$registered[ $tag ] = $definition;
		self::get_instance()->register( $definition, true );

		return true;
	}

	/**
	 * Register multiple shortcodes from a definition map.
	 *
	 * @param array<string, array<string, mixed>> $shortcodes Shortcode definitions.
	 * @return void
	 */
	public static function register_shortcodes( array $shortcodes = array() ): void {
		foreach ( $shortcodes as $tag => $config ) {
			self::create( (string) $tag, (array) $config );
		}
	}

	/**
	 * Create a shortcode from a UI or request payload.
	 *
	 * @param array<string, mixed> $data UI payload.
	 * @return bool
	 */
	public static function dynamically_create( array $data = array() ): bool {
		$tag = self::normalize_tag( $data['tag'] ?? $data['shortcode'] ?? $data['slug'] ?? '' );
		if ( '' === $tag ) {
			return false;
		}

		$callback = $data['callback'] ?? null;
		if ( ! is_callable( $callback ) ) {
			return false;
		}

		$config = array(
			'tag'         => $tag,
			'callback'    => $callback,
			'attributes'  => isset( $data['attributes'] ) && is_array( $data['attributes'] ) ? $data['attributes'] : array(),
			'description' => $data['description'] ?? '',
			'category'    => $data['category'] ?? '',
			'enclosing'   => ! empty( $data['enclosing'] ),
			'tinymce'     => ! empty( $data['tinymce'] ),
		);

		return self::create( $tag, $config );
	}

	/**
	 * Create a shortcode registry and optionally register a batch of definitions.
	 *
	 * @param array<int, array<string, mixed>> $definitions Shortcode definitions.
	 * @param bool $replace Whether to replace existing shortcodes with the same tags.
	 * @return self
	 */
	public static function create_registry( array $definitions = array(), bool $replace = false ): self {
		$registry = new self();
		$registry->register_many( $definitions, $replace );
		return $registry;
	}

	/**
	 * Get all registered shortcode definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		return apply_filters( 'modpress_shortcode_definitions', self::$registered );
	}

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

		if ( shortcode_exists( $tag ) && ! $replace ) {
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
	 * Get all shortcode definitions registered on the current instance.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function registered_definitions(): array {
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
	private static function normalize_definition( array $definition ): array {
		$tag = self::normalize_tag( $definition['tag'] ?? '' );
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

	private static function normalize_tag( $tag ): string {
		return strtolower( trim( (string) $tag ) );
	}
}




