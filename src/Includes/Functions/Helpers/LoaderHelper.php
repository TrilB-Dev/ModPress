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
     * Register multiple hooks belonging to one component.
     *
     * Each definition requires `type`, `hook`, and `callback`, and may provide
     * `priority` and `accepted_args`.
     *
     * @param object|string|array $component Callback component.
     * @param array<int, array<string, mixed>> $hooks Hook definitions.
     * @return self
     */
    public function register_component( object|string|array $component, array $hooks ): self {
        foreach ( $hooks as $definition ) {
            $type = SanitizationHelper::one_of( SanitizationHelper::key( $definition['type'] ?? 'action', 'action' ), [ 'action', 'filter' ], 'action' );
            $hook = SanitizationHelper::text( $definition['hook'] ?? '' );
            $callback = SanitizationHelper::text( $definition['callback'] ?? '' );
            $priority = SanitizationHelper::integer_range( $definition['priority'] ?? 10, 0, PHP_INT_MAX, 10 );
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
