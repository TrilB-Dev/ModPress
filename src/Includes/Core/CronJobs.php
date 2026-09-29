<?php
/**
 * CronJobs class.
 *
 * Handles the scheduling and execution of ModPress cron jobs.
 *
 * @package ModPress\Includes\Core
 */
namespace ModPress\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CronJobs {
	/**
	 * Shared registry singleton.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registered cron jobs.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static array $registered = array();

	/**
	 * Instance-local cron definitions.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $jobs = array();

	/**
	 * Get the shared cron registry singleton.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		return self::$instance ??= new self();
	}

	/**
	 * Build a normalized cron definition array.
	 *
	 * @param string        $scope      Scope owner.
	 * @param string        $hook       Hook name.
	 * @param string        $schedule   Recurrence identifier or custom interval.
	 * @param array         $args       Args passed to the callback.
	 * @param callable|null $callback   Optional callback.
	 * @param string        $description Human readable description.
	 * @return array<string, mixed>
	 */
	public static function define( string $scope, string $hook, string $schedule, array $args = array(), $callback = null, string $description = '' ): array {
		return self::normalize_definition(
			array(
				'scope'      => $scope,
				'hook'       => $hook,
				'schedule'   => $schedule,
				'args'       => $args,
				'callback'   => $callback,
				'description'=> $description,
			)
		);
	}

	/**
	 * Create and register a cron job from a standard dynamic definition.
	 *
	 * @param string        $scope      The owner scope, such as core, plugin, or extension.
	 * @param string        $hook       The cron hook name to schedule.
	 * @param string        $schedule   The WordPress recurrence key or a custom interval.
	 * @param array         $args       Args passed to the callback.
	 * @param callable|null $callback   Optional callback to hook to the cron action.
	 * @param string        $description Optional human-readable description.
	 * @return bool True when scheduled successfully.
	 */
	public static function create( string $scope, string $hook, string $schedule, array $args = array(), $callback = null, string $description = '' ): bool {
		$scope = self::normalize_scope( $scope );
		$hook  = self::normalize_hook( $scope, $hook );
		if ( '' === $hook ) {
			return false;
		}

		$already_registered = isset( self::$registered[ $hook ] );
		$already_scheduled  = function_exists( 'wp_next_scheduled' ) && false !== wp_next_scheduled( $hook, $args );
		if ( $already_registered || $already_scheduled ) {
			return false;
		}

		$definition = self::normalize_definition(
			array(
				'scope'      => $scope,
				'hook'       => $hook,
				'schedule'   => $schedule,
				'args'       => $args,
				'callback'   => $callback,
				'description'=> $description,
			)
		);

		self::$registered[ $hook ] = $definition;
		self::get_instance()->register( $definition, true );

		return true;
	}

	/**
	 * Register multiple cron jobs from a definitions map.
	 *
	 * @param array<string, array<string, mixed>> $jobs Job definitions keyed by hook.
	 * @return void
	 */
	public static function register_jobs( array $jobs = array() ): void {
		foreach ( $jobs as $definition ) {
			if ( is_array( $definition ) ) {
				self::create(
					$definition['scope'] ?? 'core',
					$definition['hook'] ?? '',
					$definition['schedule'] ?? 'hourly',
					isset( $definition['args'] ) && is_array( $definition['args'] ) ? $definition['args'] : array(),
					$definition['callback'] ?? null,
					$definition['description'] ?? ''
				);
			}
		}
	}

	/**
	 * Create a cron job from a UI or request payload.
	 *
	 * @param array<string, mixed> $data UI payload.
	 * @return bool
	 */
	public static function dynamically_create( array $data = array() ): bool {
		$scope = self::normalize_scope( $data['scope'] ?? 'core' );
		$hook  = (string) ( $data['hook'] ?? $data['name'] ?? '' );
		if ( '' === $hook ) {
			return false;
		}

		$callback = $data['callback'] ?? null;
		if ( null !== $callback && ! is_callable( $callback ) ) {
			return false;
		}

		$schedule = (string) ( $data['schedule'] ?? 'hourly' );
		$args     = isset( $data['args'] ) && is_array( $data['args'] ) ? $data['args'] : array();

		return self::create( $scope, $hook, $schedule, $args, $callback, (string) ( $data['description'] ?? '' ) );
	}

	/**
	 * Create a cron registry and optionally register a batch of definitions.
	 *
	 * @param array<int, array<string, mixed>> $definitions Cron definitions.
	 * @param bool $replace Whether to replace existing job definitions with the same hook.
	 * @return self
	 */
	public static function create_registry( array $definitions = array(), bool $replace = false ): self {
		$registry = new self();
		$registry->register_many( $definitions, $replace );
		return $registry;
	}

	/**
	 * Get all registered cron definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		return apply_filters( 'modpress_cronjob_definitions', self::$registered );
	}

	/**
	 * Register a cron definition.
	 *
	 * @param array<string, mixed> $definition Cron definition.
	 * @param bool $replace Whether to replace an existing definition by hook.
	 * @return bool
	 */
	public function register( array $definition, bool $replace = false ): bool {
		$definition = self::normalize_definition( $definition );
		$scope      = $definition['scope'];
		$hook       = $definition['hook'];

		if ( isset( $this->jobs[ $hook ] ) && ! $replace ) {
			return false;
		}

		$already_scheduled = function_exists( 'wp_next_scheduled' ) && false !== wp_next_scheduled( $hook, $definition['args'] );
		if ( $already_scheduled && ! $replace ) {
			return false;
		}

		$this->jobs[ $hook ] = $definition;

		if ( null !== $definition['callback'] && is_callable( $definition['callback'] ) ) {
			if ( ! has_action( $hook, $definition['callback'] ) ) {
				add_action( $hook, $definition['callback'], 10, count( $definition['args'] ) );
			}
		}

		if ( ! function_exists( 'wp_schedule_event' ) ) {
			return false;
		}

		$timestamp = time() + 60;
		if ( ! wp_schedule_event( $timestamp, $definition['schedule'], $hook, $definition['args'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Register multiple cron jobs at once.
	 *
	 * @param array<int, array<string, mixed>> $definitions Cron definitions.
	 * @param bool $replace Whether to replace existing jobs with the same hook.
	 * @return array<int, string>
	 */
	public function register_many( array $definitions, bool $replace = false ): array {
		$registered = array();

		foreach ( $definitions as $definition ) {
			if ( $this->register( $definition, $replace ) ) {
				$registered[] = $definition['hook'] ?? '';
			}
		}

		return array_values( array_filter( $registered ) );
	}

	/**
	 * Unregister a cron job by scope and hook.
	 *
	 * @param string $scope Scope owner.
	 * @param string $hook  Hook name.
	 * @return bool True if the cron job was successfully unregistered.
	 */
	public function unregister( string $scope, string $hook ): bool {
		$scope = self::normalize_scope( $scope );
		$hook  = self::normalize_hook( $scope, $hook );
		if ( '' === $hook ) {
			return false;
		}

		unset( $this->jobs[ $hook ] );
		unset( self::$registered[ $hook ] );

		if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
			return wp_clear_scheduled_hook( $hook );
		}

		return false;
	}

	/**
	 * Get the definition of a specific cron job.
	 *
	 * @param string $scope Scope owner.
	 * @param string $hook  Hook name.
	 * @return array<string, mixed>|null
	 */
	public function definition( string $scope, string $hook ): ?array {
		$scope = self::normalize_scope( $scope );
		$hook  = self::normalize_hook( $scope, $hook );
		return $this->jobs[ $hook ] ?? self::$registered[ $hook ] ?? null;
	}

	/**
	 * Check if a cron job exists in the registry.
	 *
	 * @param string $scope Scope owner.
	 * @param string $hook  Hook name.
	 * @return bool
	 */
	public function has( string $scope, string $hook ): bool {
		$scope = self::normalize_scope( $scope );
		$hook  = self::normalize_hook( $scope, $hook );
		return isset( $this->jobs[ $hook ] ) || isset( self::$registered[ $hook ] );
	}

	/**
	 * Register a core cron job.
	 *
	 * @param string        $hook     Hook name.
	 * @param string        $schedule Recurrence identifier.
	 * @param array         $args     Job args.
	 * @param callable|null $callback Callback.
	 * @return bool True when the schedule was registered.
	 */
	public static function register_core( string $hook, string $schedule, array $args = array(), $callback = null ): bool {
		return self::register( 'core', $hook, $schedule, $args, $callback );
	}

	/**
	 * Clear a scheduled cron job for a scope.
	 *
	 * @param string $scope The owner scope.
	 * @param string $hook  The cron hook name.
	 * @return bool True when cleared.
	 */
	public static function clear( string $scope, string $hook ): bool {
		$scope = self::normalize_scope( $scope );
		$hook  = self::normalize_hook( $scope, $hook );
		if ( '' === $hook || ! function_exists( 'wp_clear_scheduled_hook' ) ) {
			return false;
		}

		unset( self::$registered[ $hook ] );
		return wp_clear_scheduled_hook( $hook );
	}

	/**
	 * Clear a core cron job.
	 *
	 * @param string $hook Hook name.
	 * @return bool True when the schedule was cleared.
	 */
	public static function clear_core( string $hook ): bool {
		return self::clear( 'core', $hook );
	}

	/**
	 * Check whether a cron job exists.
	 *
	 * @param string $scope The owner scope.
	 * @param string $hook  The cron hook name.
	 * @param array  $args  The scheduled arguments.
	 * @return bool True when the job is scheduled.
	 */
	public static function exists( string $scope, string $hook, array $args = array() ): bool {
		$scope = self::normalize_scope( $scope );
		$hook  = self::normalize_hook( $scope, $hook );
		if ( '' === $hook || ! function_exists( 'wp_next_scheduled' ) ) {
			return false;
		}

		return false !== wp_next_scheduled( $hook, $args );
	}

	/**
	 * Build a scope-aware cron hook name.
	 *
	 * @param string $scope Owner scope.
	 * @param string $hook  Raw hook name.
	 * @return string Normalized hook name.
	 */
	public static function normalize_hook( string $scope, string $hook ): string {
		$scope = self::normalize_scope( $scope );
		$hook  = sanitize_key( (string) $hook );
		if ( '' === $hook ) {
			return '';
		}

		return 'modpress_' . $scope . '_' . $hook;
	}

	/**
	 * Normalize a cron owner scope.
	 *
	 * @param string $scope Scope value.
	 * @return string Normalized scope.
	 */
	public static function normalize_scope( string $scope ): string {
		$scope = sanitize_key( trim( (string) $scope ) );
		return '' === $scope ? 'core' : $scope;
	}

	/**
	 * Normalize a cron definition to ensure it has all required fields.
	 *
	 * @param array<string, mixed> $definition Definition.
	 * @return array<string, mixed>
	 */
	private static function normalize_definition( array $definition ): array {
		$scope    = self::normalize_scope( $definition['scope'] ?? 'core' );
		$hook     = (string) ( $definition['hook'] ?? '' );
		if ( '' !== $hook && 0 !== strpos( $hook, 'modpress_' . $scope . '_' ) ) {
			$hook = self::normalize_hook( $scope, $hook );
		} elseif ( '' === $hook ) {
			$hook = self::normalize_hook( $scope, '' );
		}
		$schedule = trim( (string) ( $definition['schedule'] ?? 'hourly' ) );
		$args     = isset( $definition['args'] ) && is_array( $definition['args'] ) ? $definition['args'] : array();
		$callback = $definition['callback'] ?? null;

		if ( '' === $hook ) {
			throw new \InvalidArgumentException( 'A cron hook is required.' );
		}

		if ( null !== $callback && ! is_callable( $callback ) ) {
			throw new \InvalidArgumentException( sprintf( 'Cron callback for "%s" must be callable.', wp_strip_all_tags( $hook ) ) );
		}

		return array_merge(
			array(
				'scope'      => $scope,
				'hook'       => $hook,
				'schedule'   => '' === $schedule ? 'hourly' : $schedule,
				'args'       => $args,
				'callback'   => $callback,
				'description'=> '',
			),
			$definition,
			array(
				'scope'      => $scope,
				'hook'       => $hook,
				'args'       => $args,
				'callback'   => $callback,
				'schedule'   => '' === $schedule ? 'hourly' : $schedule,
			)
		);
	}
}


