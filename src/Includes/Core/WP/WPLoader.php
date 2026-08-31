<?php
/**
 * Collect and register WordPress actions and filters.
 *
 * @package ModPress\Includes\Core\WP
 * @since 1.0.0
 */
namespace ModPress\Includes\Core\WP;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Core WordPress hook registry used by ModPress and extensions.
 */
class WPLoader {
    protected array $actions = [];
    protected array $filters = [];
    protected bool $has_run = false;

    public function __construct( array $actions = [], array $filters = [] ) {
        $this->actions = $actions;
        $this->filters = $filters;
    }

    public function add_action( string $hook, object|string|array $component, string $callback, int $priority = 10, int $accepted_args = 1 ): self {
        $this->register_record( 'action', $this->component_record( $hook, $component, $callback, $priority, $accepted_args ) );
        return $this;
    }

    public function add_filter( string $hook, object|string|array $component, string $callback, int $priority = 10, int $accepted_args = 1 ): self {
        $this->register_record( 'filter', $this->component_record( $hook, $component, $callback, $priority, $accepted_args ) );
        return $this;
    }

    public function add_callable_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): self {
        $this->register_record( 'action', $this->callable_record( $hook, $callback, $priority, $accepted_args ) );
        return $this;
    }

    public function add_callable_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): self {
        $this->register_record( 'filter', $this->callable_record( $hook, $callback, $priority, $accepted_args ) );
        return $this;
    }

    public function remove_action( string $hook, callable $callback, int $priority = 10 ): bool {
        return $this->remove( 'action', $hook, $callback, $priority );
    }

    public function remove_filter( string $hook, callable $callback, int $priority = 10 ): bool {
        return $this->remove( 'filter', $hook, $callback, $priority );
    }

    public function remove( string $type, string $hook, $callback, int $priority = 10 ): bool {
        if ( ! in_array( $type, [ 'action', 'filter' ], true ) ) {
            throw new \InvalidArgumentException( 'Hook type must be action or filter.' );
        }
        $property = 'action' === $type ? 'actions' : 'filters';
        $removed = false;
        $this->{$property} = array_values( array_filter( $this->{$property}, function ( array $record ) use ( $type, $hook, $callback, $priority, &$removed ): bool {
            $matches = $record['hook'] === $hook && $record['priority'] === $priority && $this->record_callback( $record ) === $callback;
            $removed = $removed || $matches;
            if ( $matches && $this->has_run ) {
                $wordpress_callback = $this->record_callback( $record );
                'action' === $type ? remove_action( $hook, $wordpress_callback, $priority ) : remove_filter( $hook, $wordpress_callback, $priority );
            }
            return ! $matches;
        } ) );
        return $removed;
    }

    public function get_hooks( ?string $type = null ): array {
        if ( null !== $type && ! in_array( $type, [ 'action', 'filter' ], true ) ) {
            throw new \InvalidArgumentException( 'Hook type must be action or filter.' );
        }
        if ( 'action' === $type ) {
            return $this->actions;
        }
        if ( 'filter' === $type ) {
            return $this->filters;
        }
        return array_merge( $this->actions, $this->filters );
    }

    public function has_hook( string $type, string $hook, ?callable $callback = null, ?int $priority = null ): bool {
        foreach ( $this->get_hooks( $type ) as $record ) {
            if ( $record['hook'] !== $hook || ( null !== $priority && $record['priority'] !== $priority ) ) {
                continue;
            }
            if ( null === $callback || $this->record_callback( $record ) === $callback ) {
                return true;
            }
        }
        return false;
    }

    public function run(): void {
        if ( $this->has_run ) {
            return;
        }
        foreach ( $this->filters as $record ) {
            add_filter( $record['hook'], $this->record_callback( $record ), $record['priority'], $record['accepted_args'] );
        }
        foreach ( $this->actions as $record ) {
            add_action( $record['hook'], $this->record_callback( $record ), $record['priority'], $record['accepted_args'] );
        }
        $this->has_run = true;
    }

    private function component_record( string $hook, object|string|array $component, string $callback, int $priority, int $accepted_args ): array {
        return [ 'hook' => $hook, 'component' => $component, 'callback' => $callback, 'priority' => $priority, 'accepted_args' => $accepted_args ];
    }

    private function callable_record( string $hook, callable $callback, int $priority, int $accepted_args ): array {
        return [ 'hook' => $hook, 'component' => null, 'callback' => $callback, 'priority' => $priority, 'accepted_args' => $accepted_args ];
    }

    private function register_record( string $type, array $record ): void {
        $property = 'action' === $type ? 'actions' : 'filters';
        $this->{$property}[] = $record;
        if ( ! $this->has_run ) {
            return;
        }
        $callback = $this->record_callback( $record );
        'action' === $type ? add_action( $record['hook'], $callback, $record['priority'], $record['accepted_args'] ) : add_filter( $record['hook'], $callback, $record['priority'], $record['accepted_args'] );
    }

    private function record_callback( array $record ): callable {
        return null === $record['component'] ? $record['callback'] : [ $record['component'], $record['callback'] ];
    }
}
