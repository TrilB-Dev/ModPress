<?php
/**
 * Higher-level helpers for the ModPress WordPress hook loader.
 *
 * @package ModPress\Includes\Functions\Helpers
 * @since 1.0.0
 */
namespace ModPress\Includes\Functions\Helpers;

use ModPress\Includes\Core\WP\WPLoader;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extend the core loader with component hook registration helpers.
 */
class LoaderHelper extends WPLoader {
	/**
	 * Enqueue a registered stylesheet through the WordPress asset API.
	 *
	 * @param string       $handle The style handle.
	 * @param string       $src    The stylesheet source.
	 * @param array        $deps  Script dependencies.
	 * @param string|false $ver   The stylesheet version.
	 * @param string       $media Media type.
	 * @return void
	 */
	public static function enqueue_style( string $handle, string $src = '', array $deps = array(), $ver = null, string $media = 'all' ): void {
		wp_enqueue_style( $handle, $src, $deps, $ver, $media );
	}

	/**
	 * Enqueue a registered script through the WordPress asset API.
	 *
	 * @param string       $handle    Script handle.
	 * @param string       $src       Script source.
	 * @param array        $deps      Script dependencies.
	 * @param string|false $ver       Script version.
	 * @param bool         $in_footer Whether to load in the footer.
	 * @return void
	 */
	public static function enqueue_script( string $handle, string $src = '', array $deps = array(), $ver = null, bool $in_footer = true ): void {
		wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
	}

	/**
	 * Localize a script using the WordPress script localization API.
	 *
	 * @param string $handle      Script handle.
	 * @param string $object_name JavaScript object name.
	 * @param array  $data        Object payload.
	 * @return void
	 */
	public static function localize_script( string $handle, string $object_name, array $data ): void {
		wp_localize_script( $handle, $object_name, $data );
	}

	/**
	 * Register multiple hooks belonging to one component.
	 *
	 * Each definition requires `type`, `hook`, and `callback`, and may provide
	 * `priority` and `accepted_args`.
	 *
	 * @param object|string|array              $component Callback component.
	 * @param array<int, array<string, mixed>> $hooks Hook definitions.
	 * @return self
	 * @throws \InvalidArgumentException When the hook definition is invalid.
	 */
	public function register_component( object|string|array $component, array $hooks ): self {
		foreach ( $hooks as $definition ) {
			$type          = SanitizationHelper::one_of( SanitizationHelper::key( $definition['type'] ?? 'action', 'action' ), array( 'action', 'filter' ), 'action' );
			$hook          = SanitizationHelper::text( $definition['hook'] ?? '' );
			$callback      = SanitizationHelper::text( $definition['callback'] ?? '' );
			$priority      = SanitizationHelper::integer_range( $definition['priority'] ?? 10, 0, PHP_INT_MAX, 10 );
			$accepted_args = SanitizationHelper::integer_range( $definition['accepted_args'] ?? 1, 0, PHP_INT_MAX, 1 );

			if ( '' === $hook || '' === $callback ) {
				throw new \InvalidArgumentException( 'Hook definitions require a hook and callback string.' );
			}
			if ( 'filter' === $type ) {
				$this->add_filter( $hook, $component, $callback, $priority, $accepted_args );
				continue;
			}
			if ( 'action' === $type ) {
				$this->add_action( $hook, $component, $callback, $priority, $accepted_args );
				continue;
			}
			throw new \InvalidArgumentException( 'Hook definition type must be action or filter.' );
		}

		return $this;
	}
}





