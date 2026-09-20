<?php
/**
 * Extension registration service for ModPress integrations.
 *
 * @package ModPress
 * @subpackage Includes\ModManagement
 * @since 1.0.0
 */
namespace ModPress\Includes\ModManagement;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class IntegrationManager {
	/**
	 * Known integration callbacks.
	 *
	 * @var array<string, callable>
	 */
	private array $integrations = array();

	/**
	 * Register a mod integration callback.
	 *
	 * @param string   $name Callback key.
	 * @param callable $callback Integration callback.
	 * @return self
	 */
	public function register( string $name, callable $callback ): self {
		$this->integrations[ $name ] = $callback;
		return $this;
	}

	/**
	 * Dispatch an integration event.
	 *
	 * @param string $event Event name.
	 * @param array<string, mixed> $context Event context.
	 * @return array<string, mixed>
	 */
	public function dispatch( string $event, array $context = array() ): array {
		$results = array();

		foreach ( $this->integrations as $name => $callback ) {
			$results[ $name ] = call_user_func( $callback, $event, $context );
		}

		return $results;
	}
}
