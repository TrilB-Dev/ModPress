<?php
/**
 * ModPress menu registration and sidebar definitions.
 *
 * @package ModPress
 * @subpackage Includes\Functions\Admin
 * @since 1.0.0
 */
namespace ModPress\Includes\Functions\Admin;

use ModPress\Admin\Admin;
use ModPress\Includes\Functions\Helpers\LoggerHelper;
use ModPress\Includes\Functions\Helpers\AMHelper;
use ModPress\Includes\Functions\Helpers\ASMHelper;
use ModPress\Includes\Plugins\AdminMenuProviderInterface;
use ModPress\Includes\Plugins\AdminSidebarProviderInterface;
use ModPress\Includes\Plugins\Plugins;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Owns ModPress menu data and registration.
 *
 * Rendering remains in Admin and Sidebar. This class builds menu data,
 * applies extension filters, and calls the WordPress admin API.
 */
final class FunctionsSidebar {
	/**
	 * Register the core WordPress menu followed by plugin-provided menus.
	 *
	 * @param Admin $admin Core admin callbacks and capability resolver.
	 * @return void
	 */
	public static function register_admin_menu( Admin $admin ): void {
		foreach ( self::core_wordpress_menus( $admin ) as $menu ) {
			self::register_wordpress_menu( $menu );
		}

		foreach ( AMHelper::filter( self::plugin_wordpress_menus() ) as $menu ) {
			self::register_wordpress_menu( $menu );
		}
	}

	/**
	 * Return the built-in and filtered AccessPress sidebar groups.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_sidebar_groups(): array {
		$groups = self::core_sidebar_groups();
		$menus  = ASMHelper::filter( self::plugin_sidebar_menus() );

		// Create parents first so children can target a parent in any order.
		foreach ( $menus as $menu ) {
			if ( '' === self::parent_slug( $menu ) ) {
				self::add_sidebar_group( $groups, $menu );
			}
		}

		foreach ( $menus as $menu ) {
			$parent = self::parent_slug( $menu );
			if ( '' !== $parent ) {
				self::add_sidebar_item( $groups, $parent, $menu );
			}
		}

		foreach ( $groups as $group_key => $group ) {
			$filtered_items = array();
			foreach ( $group['items'] as $item ) {
				$capability = sanitize_key( (string) ( $item['capability'] ?? '' ) );
				if ( self::can_view_menu_item( $capability ) ) {
					$filtered_items[] = $item;
				}
			}
			$groups[ $group_key ]['items'] = $filtered_items;
		}

		$groups = array_filter( $groups, static fn ( array $group ): bool => ! empty( $group['items'] ) );

		return $groups;
	}

	/**
	 * Get a AccessPress sidebar page URL.
	 *
	 * @param string $slug Page slug, optionally followed by a query string.
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_admin_sidebar_menu_page_url( string $slug ): string {
		return admin_url( 'admin.php?page=' . $slug );
	}

	/** 
	 * Get the core WordPress admin menus.
	 * 
	 * @return array<int, array<string, mixed>>
	 * @since 1.0.0
	 */
	private static function core_wordpress_menus( Admin $admin ): array {
		return [
			[
				'name'       => __( 'ModPress', 'modpress' ),
				'slug'       => 'modpress',
				'icon'       => 'dashicons-book-alt',
				'parent'     => '',
				'callback'   => [ $admin, 'render_dashboard' ],
				'capability' => 'modpress_admin_view',
				'position'   => 30,
			],
			[
				'name'       => __( 'Dashboard', 'modpress' ),
				'slug'       => 'modpress',
				'parent'     => 'modpress',
				'callback'   => [ $admin, 'render_dashboard' ],
				'capability' => 'modpress_admin_view',
			],
			[
				'name'       => __( 'Manage Mods', 'modpress' ),
				'slug'       => 'modpress&group=mod-manager&tab=dashboard',
				'parent'     => 'modpress',
				'callback'   => [ $admin, 'render_mods' ],
				'capability' => 'modpress_admin_view',
			],
			[
				'name'       => __( 'Settings', 'modpress' ),
				'slug'       => 'modpress&group=settings&tab=general',
				'parent'     => 'modpress',
				'callback'   => [ $admin, 'render_settings' ],
				'capability' => 'modpress_settings_general_view',
			],
			[
				'name'       => __( 'Tools', 'modpress' ),
				'slug'       => 'modpress&group=tools&tab=general',
				'parent'     => 'modpress',
				'callback'   => [ $admin, 'render_tools' ],
				'capability' => 'modpress_tools_debug',
			],
		];
	}

	/**
	 * Get the core sidebar groups.
	 *
	 * @return array<string, array<string, mixed>>
	 * @since 1.0.0
	 */
	private static function core_sidebar_groups(): array {
		return [
			'mod-manager' => [
				'label' => __( 'Manage Mods', 'modpress' ),
				'icon'  => 'fa-solid fa-file-lines',
				'items' => [
					'manage' => [
						'label'      => __( 'Manage Mods', 'modpress' ),
						'icon'       => 'fa-solid fa-book-open-lines',
						'link'       => 'modpress&group=mod-manager&tab=dashboard',
						'capability' => 'modpress_admin_view',
					],
					'groups' => [
						'label'      => __( 'Groups', 'modpress' ),
						'icon'       => 'fa-solid fa-book-open-lines-category',
						'link'       => 'modpress&group=mod-manager&tab=groups',
						'capability' => 'modpress_edit',
					],
					'tags' => [
						'label'      => __( 'Tags', 'modpress' ),
						'icon'       => 'fa-solid fa-book-open-lines-tag',
						'link'       => 'modpress&group=mod-manager&tab=tags',
						'capability' => 'modpress_edit',
					],
					'new' => [
						'label'      => __( 'New Mod', 'modpress' ),
						'icon'       => 'fa-kit fa-solid-book-open-lines-circle-plus',
						'link'       => 'modpress&group=mod-manager&tab=new',
						'capability' => 'modpress_create',
					],
				],
			],
			'settings' => [
				'label' => __( 'Settings', 'modpress' ),
				'icon'  => 'fa-solid fa-gear',
				'items' => [
					'general' => [
						'label'      => __( 'General', 'modpress' ),
						'icon'       => 'fa-solid fa-sliders',
						'link'       => 'modpress&group=settings&tab=general',
						'capability' => 'modpress_settings_general_view',
					],
					'layout' => [
						'label'      => __( 'Layout', 'modpress' ),
						'icon'       => 'fa-solid fa-table-columns',
						'link'       => 'modpress&group=settings&tab=layout',
						'capability' => 'modpress_settings_layout_view',
					],
					'plugins' => [
						'label'      => __( 'Plugins', 'modpress' ),
						'icon'       => 'fa-solid fa-puzzle-piece',
						'link'       => 'modpress&group=settings&tab=plugins',
						'capability' => 'modpress_settings_plugins_view',
					],
					'third-party' => [
						'label'      => __( '3rd Party', 'modpress' ),
						'icon'       => 'fa-solid fa-plug',
						'link'       => 'modpress&group=settings&tab=third-party',
						'capability' => 'modpress_settings_plugins_ext_view',
					],
					'access' => [
						'label'      => __( 'Access', 'modpress' ),
						'icon'       => 'fa-solid fa-user-shield',
						'link'       => 'modpress&group=settings&tab=access',
						'capability' => 'modpress_settings_access_view',
					],
				],
			],
			'tools' => [
				'label' => __( 'Tools', 'modpress' ),
				'icon'  => 'fa-solid fa-toolbox',
				'items' => [
					'debug' => [
						'label'      => __( 'Debug', 'modpress' ),
						'icon'       => 'fa-solid fa-bug-slash',
						'link'       => 'modpress&group=tools&tab=debug',
						'capability' => 'modpress_tools_debug',
					],
					'import' => [
						'label'      => __( 'Import', 'modpress' ),
						'icon'       => 'fa-solid fa-file-import',
						'link'       => 'modpress&group=tools&tab=import',
						'capability' => 'modpress_tools_import',
					],
					'export' => [
						'label'      => __( 'Export', 'modpress' ),
						'icon'       => 'fa-solid fa-file-export',
						'link'       => 'modpress&group=tools&tab=export',
						'capability' => 'modpress_tools_export',
					],
					'analytics' => [
						'label'      => __( 'Analytics', 'modpress' ),
						'icon'       => 'fa-solid fa-chart-line',
						'link'       => 'modpress&group=tools&tab=analytics',
						'capability' => 'modpress_tools_analytics',
					],
				],
			],
		];
	}
	/**
	 * Register a WordPress menu.
	 *
	 * @param array<string, mixed> $menu The menu definition.
	 * @return void
	 * @since 1.0.0
	 */
	private static function register_wordpress_menu( array $menu ): void {
		$callback   = $menu['callback'] ?? null;
		$raw_slug   = (string) ( $menu['slug'] ?? '' );
		$slug       = self::menu_page_slug( $raw_slug );
		$name       = (string) ( $menu['name'] ?? '' );
		$parent     = self::admin_parent_slug( (string) ( $menu['parent'] ?? '' ) );
		$capability = self::resolve_menu_capability( sanitize_key( (string) ( $menu['capability'] ?? 'manage_options' ) ) );

		if ( '' === $slug || '' === $name || ! is_callable( $callback ) ) {
			LoggerHelper::write_log( sprintf( 'AccessPress skipped menu registration for empty or invalid page: %s', $raw_slug ) );
			return;
		}

		LoggerHelper::write_log( sprintf( 'AccessPress registering admin menu: %s (slug=%s, parent=%s, capability=%s)', $name, $slug, $parent, $capability ) );

		try {
			if ( '' === $parent ) {
				add_menu_page( $name, $name, $capability, $slug, $callback, $menu['icon'] ?? 'dashicons-admin-generic', $menu['position'] ?? null );
				return;
			}

			if ( $slug === $parent ) {
				LoggerHelper::write_log( sprintf( 'AccessPress skipped submenu registration because slug matches parent: %s', $slug ) );
				return;
			}

			add_submenu_page( $parent, $name, $name, $capability, $slug, $callback, $menu['position'] ?? null );
		} catch ( \Throwable $e ) {
			LoggerHelper::write_log( sprintf( 'AccessPress menu registration failed for %s (%s): %s', $name, $slug, $e->getMessage() ) );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				wp_die( esc_html( $e->getMessage() ), __( 'AccessPress menu registration error', 'licencepress' ), array( 'back_link' => true ) );
			}
		}
	}

	/**
	 * Get the WordPress menus provided by active AccessPress plugins.
	 *
	 * @return array<int, array<string, mixed>> The WordPress menus.
	 * @since 1.0.0
	*/
	private static function plugin_wordpress_menus(): array {
		$menus = array();

		foreach ( Plugins::get_instance()->get_registered_plugins() as $plugin ) {
			if ( ! $plugin instanceof AdminMenuProviderInterface || ! $plugin->is_active() ) {
				continue;
			}

			try {
				foreach ( $plugin->get_admin_menu() as $definition ) {
					if ( ! is_array( $definition ) ) {
						continue;
					}

					$menus[] = self::normalize_wordpress_menu( $definition );
					foreach ( $definition['children'] ?? array() as $child ) {
						if ( is_array( $child ) ) {
							$child['parent'] = $definition['menu_slug'] ?? '';
							$menus[]         = self::normalize_wordpress_menu( $child );
						}
					}
				}
			} catch ( \Throwable $e ) {
				LoggerHelper::write_log( sprintf( 'AccessPress plugin %s failed to provide WordPress menus: %s', $plugin->get_slug(), $e->getMessage() ) );
			}
		}

		return array_values( array_filter( $menus, static fn ( $menu ): bool => is_array( $menu ) ) );
	}

	/**
	 * Normalize a WordPress menu definition.
	 *
	 * @param array<string, mixed> $definition The menu definition.
	 * @return array<string, mixed> The normalized menu.
	 * @since 1.0.0
	 */
	private static function normalize_wordpress_menu( array $definition ): array {
		return array(
			'name'       => $definition['menu_title'] ?? $definition['page_title'] ?? '',
			'slug'       => $definition['menu_slug'] ?? '',
			'icon'       => $definition['icon'] ?? 'dashicons-admin-generic',
			'parent'     => $definition['parent'] ?? '',
			'callback'   => $definition['callback'] ?? null,
			'capability' => $definition['capability'] ?? 'manage_options',
			'position'   => $definition['position'] ?? null,
		);
	}
	/**
	 * Sanitize an admin parent slug.
	 *
	 * @param string $parent The parent slug to sanitize.
	 * @return string The sanitized parent slug.
	 * @since 1.0.0
	 */
	private static function admin_parent_slug( string $parent ): string {
		$parent = strtolower( sanitize_text_field( $parent ) );
		return (string) preg_replace( '/[^a-z0-9._-]/', '', $parent );
	}
	/**
	 * Sanitize a menu page slug.
	 *
	 * @param string $slug The menu page slug.
	 * @return string The sanitized menu page slug.
	 * @since 1.0.0
	 */
	private static function menu_page_slug( string $slug ): string {
		$slug = trim( (string) $slug );
		if ( '' === $slug || preg_match( '/^\d+$/', $slug ) ) {
			return '';
		}

		if ( false !== strpos( $slug, '&' ) ) {
			$base = trim( (string) strtok( $slug, '&' ) );
			if ( '' === $base || preg_match( '/^\d+$/', $base ) ) {
				return '';
			}
			return $base . substr( $slug, strlen( $base ) );
		}

		$base = sanitize_key( $slug );
		return '' !== $base && ! preg_match( '/^\d+$/', $base ) ? $base : '';
	}

	/**
	 * Get the sidebar menus provided by active AccessPress plugins.
	 *
	 * @return array<int, array<string, mixed>> The sidebar menus.
	 * @since 1.0.0
	 */
	private static function plugin_sidebar_menus(): array {
		$menus = array();

		foreach ( Plugins::get_instance()->get_registered_plugins() as $plugin ) {
			if ( ! $plugin instanceof AdminSidebarProviderInterface || ! $plugin->is_active() ) {
				continue;
			}

			try {
				foreach ( $plugin->get_admin_sidebar() as $definition ) {
					if ( ! is_array( $definition ) ) {
						continue;
					}

					if ( 'group' === ( $definition['type'] ?? '' ) ) {
						$menus[] = ASMHelper::define( $definition['label'] ?? '', $definition['slug'] ?? '', $definition['icon'] ?? '', '', $definition['capability'] ?? '' );
						foreach ( $definition['items'] ?? array() as $child ) {
							if ( is_array( $child ) ) {
								$menus[] = ASMHelper::define( $child['label'] ?? '', self::sidebar_slug( $child ), $child['icon'] ?? '', $definition['slug'] ?? '', $child['capability'] ?? '' );
							}
						}
						continue;
					}

					$menus[] = ASMHelper::define( $definition['label'] ?? '', self::sidebar_slug( $definition ), $definition['icon'] ?? '', $definition['parent'] ?? '', $definition['capability'] ?? '' );
				}
			} catch ( \Throwable $e ) {
				LoggerHelper::write_log( sprintf( 'AccessPress plugin %s failed to provide sidebar menus: %s', $plugin->get_slug(), $e->getMessage() ) );
			}
		}

		return $menus;
	}

	/**
	 * Generate a sidebar slug from a menu definition.
	 *
	 * @param array<string, mixed> $definition The menu definition.
	 * @return string The generated sidebar slug.
	 * @since 1.0.0
	 */
	private static function sidebar_slug( array $definition ): string {
		$page  = (string) ( $definition['page'] ?? $definition['slug'] ?? '' );
		$query = $definition['query'] ?? array();

		if ( ! is_array( $query ) || empty( $query ) ) {
			return $page;
		}

		return $page . '&' . http_build_query( array_filter( $query, 'is_scalar' ), '', '&', PHP_QUERY_RFC3986 );
	}

	/**
	 * Add a sidebar group to the collection of groups.
	 *
	 * @param array<string, array<string, mixed>> $groups The collection of sidebar groups.
	 * @param array<string, mixed> $menu The menu definition for the group.
	 * @return void
	 * @since 1.0.0
	 */
	private static function add_sidebar_group( array &$groups, array $menu ): void {
		$slug  = self::menu_slug( $menu );
		$label = (string) ( $menu['name'] ?? '' );
		$icon  = (string) ( $menu['icon'] ?? '' );

		if ( '' !== $slug && ! preg_match( '/^\d+$/', $slug ) && '' !== $label && '' !== $icon ) {
			$groups[ $slug ] = array(
				'label' => $label,
				'icon'  => $icon,
				'items' => array(),
			);
		}
	}

	/**
	 * Add a sidebar item to a parent group.
	 *
	 * @param array<string, array<string, mixed>> $groups The collection of sidebar groups.
	 * @param string $parent The parent group slug.
	 * @param array<string, mixed> $menu The menu definition for the item.
	 * @return void
	 * @since 1.0.0
	 */
	private static function add_sidebar_item( array &$groups, string $parent, array $menu ): void {
		$slug  = trim( (string) ( $menu['slug'] ?? '' ) );
		$label = (string) ( $menu['name'] ?? '' );
		$icon  = (string) ( $menu['icon'] ?? '' );

		$capability = sanitize_key( (string) ( $menu['capability'] ?? '' ) );
		if ( isset( $groups[ $parent ] ) && '' !== $slug && ! preg_match( '/^\d+$/', $slug ) && '' !== $label && '' !== $icon && ( '' === $capability || current_user_can( $capability ) ) ) {
			$groups[ $parent ]['items'][ $slug ] = array(
				'label'      => $label,
				'icon'       => $icon,
				'capability' => $capability,
			);
		}
	}

	/**
	 * Get the parent slug from a menu definition.
	 *
	 * @param array<string, mixed> $menu The menu definition.
	 * @return string The parent slug.
	 * @since 1.0.0
	 */
	private static function parent_slug( array $menu ): string {
		return sanitize_key( (string) ( $menu['parent'] ?? '' ) );
	}

	/**
	 * Get the menu slug from a menu definition.
	 *
	 * @param array<string, mixed> $menu The menu definition.
	 * @return string The menu slug.
	 * @since 1.0.0
	 */
	private static function menu_slug( array $menu ): string {
		return sanitize_key( (string) ( $menu['slug'] ?? '' ) );
	}

	/**
	 * Determine if the current user can view a menu item.
	 *
	 * Administrators keep access even when a fresh role capability install has not
	 * yet refreshed their user capability cache.
	 *
	 * @param string $capability The capability to check.
	 * @return bool True if the menu item should be visible.
	 * @since 1.0.0
	 */
	private static function can_view_menu_item( string $capability ): bool {
		if ( '' === $capability ) {
			return true;
		}

		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return current_user_can( $capability );
	}

	/**
	 * Resolve the effective capability to use when registering a WordPress menu.
	 *
	 * Admins should remain able to see the AccessPress menu while the custom
	 * role capability map catches up after activation or a role refresh.
	 *
	 * @param string $capability The capability to normalize.
	 * @return string The effective capability.
	 * @since 1.0.0
	 */
	private static function resolve_menu_capability( string $capability ): string {
		if ( '' === $capability ) {
			return 'manage_options';
		}

		if ( current_user_can( 'manage_options' ) ) {
			return 'manage_options';
		}

		return $capability;
	}
}