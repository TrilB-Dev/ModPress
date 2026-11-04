<?php

declare( strict_types=1 );

namespace {
	if ( ! function_exists( 'register_rest_route' ) ) {
		function register_rest_route( string $namespace, string $route, array $args = array() ): bool {
			$GLOBALS['modpress_test_rest_routes'][] = array(
				'namespace' => $namespace,
				'route'     => $route,
				'args'      => $args,
			);
			return true;
		}
	}

	if ( ! function_exists( 'rest_url' ) ) {
		function rest_url( string $path = '' ): string {
			return 'http://example.com/wp-json/' . ltrim( $path, '/' );
		}
	}

	if ( ! function_exists( 'wp_create_nonce' ) ) {
		function wp_create_nonce( string $action = '' ): string {
			return 'nonce:' . $action;
		}
	}

	if ( ! class_exists( 'WP_REST_Server' ) ) {
		class WP_REST_Server {
			public const READABLE = 'GET';
			public const CREATABLE = 'POST';
			public const EDITABLE = 'PUT';
			public const DELETABLE = 'DELETE';
		}
	}
}

namespace ModPress\Tests\Unit {
	use ModPress\Admin\Manager\Mods\ModManager;
	use PHPUnit\Framework\TestCase;

	final class ModManagerRestTest extends TestCase {
		public function test_register_actions_uses_rest_routes_only(): void {
			$GLOBALS['modpress_test_actions'] = array();
			$GLOBALS['modpress_test_rest_routes'] = array();

			$manager = new ModManager();

			$this->assertTrue( has_action( 'rest_api_init', array( $manager, 'register_rest_routes' ) ) );
			$this->assertFalse( has_action( 'wp_ajax_modpress_get_group', '\ModPress\Includes\ModManagement\GroupsManager::load_group' ) );
			$this->assertFalse( has_action( 'wp_ajax_modpress_get_group_entry', '\ModPress\Includes\ModManagement\GroupsManager::load_group_entry' ) );

			$manager->register_rest_routes();

			$this->assertNotEmpty( $GLOBALS['modpress_test_rest_routes'] );
			$this->assertContains( 'modpress/v1', array_column( $GLOBALS['modpress_test_rest_routes'], 'namespace' ) );
			$this->assertContains( '/groups/(?P<group_key>[^/]+)', array_column( $GLOBALS['modpress_test_rest_routes'], 'route' ) );
			$this->assertContains( '/groups/(?P<group_key>[^/]+)/entries/(?P<group_entry_id>\d+)', array_column( $GLOBALS['modpress_test_rest_routes'], 'route' ) );
		}
	}
}
