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

class WPLoader {
    /**
     * Registered WordPress actions.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $actions = [];
    /**
     * Registered WordPress filters.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $filters = [];
    /**
     * Indicates whether the hooks have been run.
     *
     * @var bool
     */
    protected bool $has_run = false;
    /**
     * Initialize the WordPress hook loader.
     *
     * @param array<int, array<string, mixed>> $actions Initial actions to register.
     * @param array<int, array<string, mixed>> $filters Initial filters to register.
     */
    public function __construct( array $actions = [], array $filters = [] ) {
        $this->actions = $actions;
        $this->filters = $filters;
    }
    /**
     * Register a WordPress action.
     *
     * @param string $hook Hook name.
     * @param object|string|array $component Component handling the hook.
     * @param string $callback Callback method.
     * @param int $priority Hook priority.
     * @param int $accepted_args Number of accepted arguments.
     * @return self
     */
    public function add_action( string $hook, object|string|array $component, string $callback, int $priority = 10, int $accepted_args = 1 ): self {
        $this->register_record( 'action', $this->component_record( $hook, $component, $callback, $priority, $accepted_args ) );
        return $this;
    }
    /**
     * Register a WordPress filter.
     *
     * @param string $hook Hook name.
     * @param object|string|array $component Component handling the hook.
     * @param string $callback Callback method.
     * @param int $priority Hook priority.
     * @param int $accepted_args Number of accepted arguments.
     * @return self
     */
    public function add_filter( string $hook, object|string|array $component, string $callback, int $priority = 10, int $accepted_args = 1 ): self {
        $this->register_record( 'filter', $this->component_record( $hook, $component, $callback, $priority, $accepted_args ) );
        return $this;
    }
    /**
     * Register a WordPress action with a callable.
     *
     * @param string $hook Hook name.
     * @param callable $callback Callback function.
     * @param int $priority Hook priority.
     * @param int $accepted_args Number of accepted arguments.
     * @return self
     */
    public function add_callable_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): self {
        $this->register_record( 'action', $this->callable_record( $hook, $callback, $priority, $accepted_args ) );
        return $this;
    }
    /**
     * Register a WordPress filter with a callable.
     *
     * @param string $hook Hook name.
     * @param callable $callback Callback function.
     * @param int $priority Hook priority.
     * @param int $accepted_args Number of accepted arguments.
     * @return self
     */
    public function add_callable_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): self {
        $this->register_record( 'filter', $this->callable_record( $hook, $callback, $priority, $accepted_args ) );
        return $this;
    }
    /**
     * Remove a registered WordPress action.
     *
     * @param string $hook Hook name.
     * @param callable $callback Callback function.
     * @param int $priority Hook priority.
     * @return bool
     */
    public function remove_action( string $hook, callable $callback, int $priority = 10 ): bool {
        return $this->remove( 'action', $hook, $callback, $priority );
    }

    /**
     * Remove a registered WordPress filter.
     *
     * @param string $hook Hook name.
     * @param callable $callback Callback function.
     * @param int $priority Hook priority.
     * @return bool
     */
    public function remove_filter( string $hook, callable $callback, int $priority = 10 ): bool {
        return $this->remove( 'filter', $hook, $callback, $priority );
    }

    /**
     * Remove a registered WordPress hook.
     *
     * @param string $type Hook type (action or filter).
     * @param string $hook Hook name.
     * @param callable $callback Callback function.
     * @param int $priority Hook priority.
     * @return bool
     */
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
    /**
     * Get all registered WordPress hooks.
     *
     * @param string|null $type Hook type (action or filter) or null for all.
     * @return array
     */
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
    /**
     * Check if a specific WordPress hook is registered.
     *
     * @param string $type Hook type (action or filter).
     * @param string $hook Hook name.
     * @param callable|null $callback Optional callback function to check.
     * @param int|null $priority Optional hook priority to check.
     * @return bool
     */
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
    /**
     * Run all registered WordPress hooks.
     *
     * @return void
     */
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
    /**
     * Create a record for a component-based hook.
     *
     * @param string $hook Hook name.
     * @param object|string|array $component Component object, class name, or array.
     * @param string $callback Callback method name.
     * @param int $priority Hook priority.
     * @param int $accepted_args Number of accepted arguments.
     * @return array
     */
    private function component_record( string $hook, object|string|array $component, string $callback, int $priority, int $accepted_args ): array {
        return [ 'hook' => $hook, 'component' => $component, 'callback' => $callback, 'priority' => $priority, 'accepted_args' => $accepted_args ];
    }
    /**
     * Create a record for a callable-based hook.
     *
     * @param string $hook Hook name.
     * @param callable $callback Callback function.
     * @param int $priority Hook priority.
     * @param int $accepted_args Number of accepted arguments.
     * @return array
     */
    private function callable_record( string $hook, callable $callback, int $priority, int $accepted_args ): array {
        return [ 'hook' => $hook, 'component' => null, 'callback' => $callback, 'priority' => $priority, 'accepted_args' => $accepted_args ];
    }
    /**
     * Register a hook record.
     *
     * @param string $type Hook type (action or filter).
     * @param array $record Hook record.
     * @return void
     */
    private function register_record( string $type, array $record ): void {
        $property = 'action' === $type ? 'actions' : 'filters';
        $this->{$property}[] = $record;
        if ( ! $this->has_run ) {
            return;
        }
        $callback = $this->record_callback( $record );
        'action' === $type ? add_action( $record['hook'], $callback, $record['priority'], $record['accepted_args'] ) : add_filter( $record['hook'], $callback, $record['priority'], $record['accepted_args'] );
    }
    /**
     * Get the callback for a hook record.
     *
     * @param array $record Hook record.
     * @return callable
     */
    private function record_callback( array $record ): callable {
        $component = $record['component'] ?? null;
        $callback = $record['callback'] ?? null;

        if ( null === $component && is_callable( $callback ) ) {
            return $callback;
        }

        if ( is_array( $component ) && isset( $component[0], $component[1] ) && is_callable( $component ) ) {
            return $component;
        }

        if ( is_string( $component ) && is_string( $callback ) && is_callable( [ $component, $callback ] ) ) {
            return [ $component, $callback ];
        }

        if ( is_object( $component ) && is_string( $callback ) && is_callable( [ $component, $callback ] ) ) {
            return [ $component, $callback ];
        }

        if ( is_string( $component ) && is_string( $callback ) && class_exists( $component ) && method_exists( $component, $callback ) ) {
            return [ $component, $callback ];
        }

        throw new \InvalidArgumentException( sprintf( 'Hook callback %s is not callable.', is_string( $callback ) ? $callback : 'unknown' ) );
    }
}
