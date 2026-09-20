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
	 * Registered action hooks.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected array $actions = array();
	/**
	 * Registered filter hooks.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected array $filters = array();
	/**
	 * Whether the loader has run registration.
	 *
	 * @var bool
	 */
	protected bool $has_run = false;
	/**
	 * Initialize the loader with optional pre-registered actions and filters.
	 *
	 * @param array<int, array<string, mixed>> $actions Pre-registered action hooks.
	 * @param array<int, array<string, mixed>> $filters Pre-registered filter hooks.
	 */
	public function __construct( array $actions = array(), array $filters = array() ) {
		$this->actions = $actions;
		$this->filters = $filters;
	}
	/**
	 * Register all collected actions and filters with WordPress.
	 *
	 * @return void
	 */
	public function add_action( string $hook, object|string|array $component, string $callback, int $priority = 10, int $accepted_args = 1 ): self {
		$this->register_record( 'action', $this->component_record( $hook, $component, $callback, $priority, $accepted_args ) );
		return $this;
	}
	/**
	 * Register a new filter hook with the loader.
	 *
	 * @param string $hook The name of the filter hook.
	 * @param object|string|array $component The component containing the callback.
	 * @param string $callback The callback method name.
	 * @param int $priority The priority of the filter.
	 * @param int $accepted_args The number of accepted arguments.
	 * @return self
	 */
	public function add_filter( string $hook, object|string|array $component, string $callback, int $priority = 10, int $accepted_args = 1 ): self {
		$this->register_record( 'filter', $this->component_record( $hook, $component, $callback, $priority, $accepted_args ) );
		return $this;
	}

	/**
	 * Register a new callable action hook with the loader.
	 *
	 * @param string $hook The name of the action hook.
	 * @param callable $callback The callback function.
	 * @param int $priority The priority of the action.
	 * @param int $accepted_args The number of accepted arguments.
	 * @return self
	 */
	public function add_callable_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): self {
		$this->register_record( 'action', $this->callable_record( $hook, $callback, $priority, $accepted_args ) );
		return $this;
	}

	/**
	 * Register a new callable filter hook with the loader.
	 *
	 * @param string $hook The name of the filter hook.
	 * @param callable $callback The callback function.
	 * @param int $priority The priority of the filter.
	 * @param int $accepted_args The number of accepted arguments.
	 * @return self
	 */
	public function add_callable_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): self {
		$this->register_record( 'filter', $this->callable_record( $hook, $callback, $priority, $accepted_args ) );
		return $this;
	}

	/**
	 * Remove a previously registered action hook.
	 *
	 * @param string $hook The name of the action hook.
	 * @param callable $callback The callback function.
	 * @param int $priority The priority of the action.
	 * @return bool True if the action was removed, false otherwise.
	 */
	public function remove_action( string $hook, callable $callback, int $priority = 10 ): bool {
		return $this->remove( 'action', $hook, $callback, $priority );
	}
	/**
	 * Remove a previously registered filter hook.
	 *
	 * @param string $hook The name of the filter hook.
	 * @param callable $callback The callback function.
	 * @param int $priority The priority of the filter.
	 * @return bool True if the filter was removed, false otherwise.
	 */
	public function remove_filter( string $hook, callable $callback, int $priority = 10 ): bool {
		return $this->remove( 'filter', $hook, $callback, $priority );
	}
	/**
	 * Remove a previously registered hook of any type.
	 *
	 * @param string $type The type of the hook ('action' or 'filter').
	 * @param string $hook The name of the hook.
	 * @param callable $callback The callback function.
	 * @param int $priority The priority of the hook.
	 * @return bool True if the hook was removed, false otherwise.
	 */
	public function remove( string $type, string $hook, $callback, int $priority = 10 ): bool {
		if ( ! in_array( $type, array( 'action', 'filter' ), true ) ) {
			throw new \InvalidArgumentException( 'Hook type must be action or filter.' );
		}
		$property          = 'action' === $type ? 'actions' : 'filters';
		$removed           = false;
		$this->{$property} = array_values(
			array_filter(
				$this->{$property},
				function ( array $record ) use ( $type, $hook, $callback, $priority, &$removed ): bool {
					$matches = $record['hook'] === $hook && $record['priority'] === $priority && $this->record_callback( $record ) === $callback;
					$removed = $removed || $matches;
					if ( $matches && $this->has_run ) {
						$wordpress_callback = $this->record_callback( $record );
						'action' === $type ? remove_action( $hook, $wordpress_callback, $priority ) : remove_filter( $hook, $wordpress_callback, $priority );
					}
					return ! $matches;
				}
			)
		);
		return $removed;
	}
	/**
	 * Get all registered hooks of a specific type or all hooks if no type is specified.
	 *
	 * @param string|null $type The type of hooks to retrieve ('action' or 'filter'), or null for all hooks.
	 * @return array The array of registered hooks.
	 */
	public function get_hooks( ?string $type = null ): array {
		if ( null !== $type && ! in_array( $type, array( 'action', 'filter' ), true ) ) {
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
	 * Check if a specific hook is registered.
	 *
	 * @param string $type The type of the hook ('action' or 'filter').
	 * @param string $hook The name of the hook.
	 * @param callable|null $callback The callback function, or null to ignore.
	 * @param int|null $priority The priority of the hook, or null to ignore.
	 * @return bool True if the hook is registered, false otherwise.
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
	 * Execute all registered actions and filters.
	 *
	 * @return void
	 */
	public function run(): void {
		if ( $this->has_run ) {
			return;
		}
		foreach ( $this->filters as $record ) {
			if ( ! $this->is_callable_record( $record ) ) {
				continue;
			}
			add_filter( $record['hook'], $this->record_callback( $record ), $record['priority'], $record['accepted_args'] );
		}
		foreach ( $this->actions as $record ) {
			if ( ! $this->is_callable_record( $record ) ) {
				continue;
			}
			add_action( $record['hook'], $this->record_callback( $record ), $record['priority'], $record['accepted_args'] );
		}
		$this->has_run = true;
	}
	/**
	 * Create a record array for a component-based hook.
	 *
	 * @param string $hook The name of the hook.
	 * @param object|string|array $component The component associated with the hook.
	 * @param string $callback The callback method name.
	 * @param int $priority The priority of the hook.
	 * @param int $accepted_args The number of accepted arguments.
	 * @return array The hook record array.
	 */
	private function component_record( string $hook, object|string|array $component, string $callback, int $priority, int $accepted_args ): array {
		return array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}
	/**
	 * Create a record array for a callable-based hook.
	 *
	 * @param string $hook The name of the hook.
	 * @param callable $callback The callback function.
	 * @param int $priority The priority of the hook.
	 * @param int $accepted_args The number of accepted arguments.
	 * @return array The hook record array.
	 */
	private function callable_record( string $hook, callable $callback, int $priority, int $accepted_args ): array {
		return array(
			'hook'          => $hook,
			'component'     => null,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}
	/**
	 * Register a hook record.
	 *
	 * @param string $type The type of the hook ('action' or 'filter').
	 * @param array $record The hook record array.
	 * @return void
	 */
	private function register_record( string $type, array $record ): void {
		if ( ! $this->is_callable_record( $record ) ) {
			return;
		}

		$property            = 'action' === $type ? 'actions' : 'filters';
		$this->{$property}[] = $record;
		if ( ! $this->has_run ) {
			return;
		}
		$callback = $this->record_callback( $record );
		'action' === $type ? add_action( $record['hook'], $callback, $record['priority'], $record['accepted_args'] ) : add_filter( $record['hook'], $callback, $record['priority'], $record['accepted_args'] );
	}

	/**
	 * Check whether a hook record resolves to a callable callback.
	 *
	 * @param array $record The hook record array.
	 * @return bool True when the hook can be registered, false otherwise.
	 */
	private function is_callable_record( array $record ): bool {
		try {
			$this->record_callback( $record );
			return true;
		} catch ( \InvalidArgumentException $exception ) {
			return false;
		}
	}
	/**
	 * Get the callable for a hook record.
	 *
	 * @param array $record The hook record array.
	 * @return callable The callable for the hook.
	 * @throws \InvalidArgumentException If the hook callback is not callable.
	 */
	private function record_callback( array $record ): callable {
		$component = $record['component'] ?? null;
		$callback  = $record['callback'] ?? null;

		if ( null === $component && is_callable( $callback ) ) {
			return $callback;
		}

		if ( is_array( $component ) && isset( $component[0], $component[1] ) && is_callable( $component ) ) {
			return $component;
		}

		if ( is_string( $component ) && is_string( $callback ) && is_callable( array( $component, $callback ) ) ) {
			return array( $component, $callback );
		}

		if ( is_object( $component ) && is_string( $callback ) && is_callable( array( $component, $callback ) ) ) {
			return array( $component, $callback );
		}

		if ( is_string( $component ) && is_string( $callback ) && class_exists( $component ) && method_exists( $component, $callback ) ) {
			return array( $component, $callback );
		}

		throw new \InvalidArgumentException( sprintf( 'Hook callback %s is not callable.', is_string( $callback ) ? $callback : 'unknown' ) );
	}
}




