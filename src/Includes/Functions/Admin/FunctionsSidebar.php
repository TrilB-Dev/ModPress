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
use ModPress\Includes\Functions\Helpers\MPAMHelper;
use ModPress\Includes\Functions\Helpers\MPASMHelper;
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

		foreach ( MPAMHelper::filter( self::plugin_wordpress_menus() ) as $menu ) {
			self::register_wordpress_menu( $menu );
		}
	}

	/**
	 * Return the built-in and filtered ModPress sidebar groups.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_sidebar_groups(): array {
		$groups = self::core_sidebar_groups();
		$menus  = MPASMHelper::filter( self::plugin_sidebar_menus() );

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

		foreach ( $groups as &$group ) {
			$group['items'] = array_filter(
				$group['items'],
				static fn ( array $item ): bool => '' === ( $capability = sanitize_key( (string) ( $item['capability'] ?? '' ) ) ) || current_user_can( $capability )
			);
		}
		unset( $group );

		return array_filter( $groups, static fn ( array $group ): bool => ! empty( $group['items'] ) );
	}

	/**
	 * Get a ModPress sidebar page URL.
	 *
	 * @param string $slug Page slug, optionally followed by a query string.
	 * @return string
	 */
	public static function get_admin_sidebar_menu_page_url( string $slug ): string {
		return admin_url( 'admin.php?page=' . $slug );
	}

	/** @return array<int, array<string, mixed>> */
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
				'name'       => __( 'Manage Wiki', 'modpress' ),
				'slug'       => 'modpress-manage',
				'parent'     => 'modpress',
				'callback'   => [ $admin, 'render_mods' ],
				'capability' => 'modpress_admin_view',
			],
			[
				'name'       => __( 'Settings', 'modpress' ),
				'slug'       => 'modpress-settings',
				'parent'     => 'modpress',
				'callback'   => [ $admin, 'render_settings' ],
				'capability' => 'modpress_settings_general_view',
			],
			[
				'name'       => __( 'Tools', 'modpress' ),
				'slug'       => 'modpress-tools',
				'parent'     => 'modpress',
				'callback'   => [ $admin, 'render_tools' ],
				'capability' => 'modpress_tools_debug',
			],
		];
	}

	/** @return array<string, array<string, mixed>> */
	private static function core_sidebar_groups(): array {
		return [
			'manage-mod' => [
				'label' => __( 'Manage Wiki', 'modpress' ),
				'icon'  => 'fa-solid fa-file-lines',
				'items' => [
					'modpress-manage'                  => [ 'label' => __( 'Manage Wiki', 'modpress' ), 'icon' => 'fa-solid fa-book-open-lines', 'capability' => 'modpress_admin_view' ],
					'modpress-manage&mod=categories' => [ 'label' => __( 'Categories', 'modpress' ), 'icon' => 'fa-book-open-lines-category', 'capability' => 'modpress_edit' ],
					'modpress-manage&mod=tags'       => [ 'label' => __( 'Tags', 'modpress' ), 'icon' => 'fa-kit fa-solid-book-open-lines-tag', 'capability' => 'modpress_edit' ],
					'modpress-manage&mod=new'        => [ 'label' => __( 'New Wiki', 'modpress' ), 'icon' => 'fa-kit fa-solid-book-open-lines-circle-plus', 'capability' => 'modpress_create' ],
				],
			],
			'settings' => [
				'label' => __( 'Settings', 'modpress' ),
				'icon'  => 'fa-solid fa-gear',
				'items' => [
					'modpress-settings&tab=general'     => [ 'label' => __( 'General', 'modpress' ), 'icon' => 'fa-solid fa-sliders', 'capability' => 'modpress_settings_general_view' ],
					'modpress-settings&tab=layout'      => [ 'label' => __( 'Layout', 'modpress' ), 'icon' => 'fa-solid fa-table-columns', 'capability' => 'modpress_settings_layout_view' ],
					'modpress-settings&tab=plugins'     => [ 'label' => __( 'Plugins', 'modpress' ), 'icon' => 'fa-solid fa-puzzle-piece', 'capability' => 'modpress_settings_plugins_view' ],
					'modpress-settings&tab=third-party' => [ 'label' => __( '3rd Party', 'modpress' ), 'icon' => 'fa-solid fa-plug', 'capability' => 'modpress_settings_plugins_ext_view' ],
					'modpress-settings&tab=access'      => [ 'label' => __( 'Access', 'modpress' ), 'icon' => 'fa-solid fa-user-shield', 'capability' => 'modpress_settings_access_view' ],
				],
			],
			'tools' => [
				'label' => __( 'Tools', 'modpress' ),
				'icon'  => 'fa-solid fa-toolbox',
				'items' => [
					'modpress-tools&tool=debug'     => [ 'label' => __( 'Debug', 'modpress' ), 'icon' => 'fa-solid fa-bug-slash', 'capability' => 'modpress_tools_debug' ],
					'modpress-tools&tool=import'    => [ 'label' => __( 'Import', 'modpress' ), 'icon' => 'fa-solid fa-file-import', 'capability' => 'modpress_tools_import' ],
					'modpress-tools&tool=export'    => [ 'label' => __( 'Export', 'modpress' ), 'icon' => 'fa-solid fa-file-export', 'capability' => 'modpress_tools_export' ],
					'modpress-tools&tool=analytics' => [ 'label' => __( 'Analytics', 'modpress' ), 'icon' => 'fa-solid fa-chart-line', 'capability' => 'modpress_tools_analytics' ],
				],
			],
		];
	}

	private static function register_wordpress_menu( array $menu ): void {
		$callback   = $menu['callback'] ?? null;
		$slug       = sanitize_key( (string) ( $menu['slug'] ?? '' ) );
		$name       = (string) ( $menu['name'] ?? '' );
		$parent     = self::admin_parent_slug( (string) ( $menu['parent'] ?? '' ) );
		$capability = sanitize_key( (string) ( $menu['capability'] ?? 'manage_options' ) );

		if ( '' === $slug || '' === $name || ! is_callable( $callback ) ) {
			return;
		}

		if ( '' === $parent ) {
			add_menu_page( $name, $name, $capability, $slug, $callback, $menu['icon'] ?? 'dashicons-admin-generic', $menu['position'] ?? null );
			return;
		}

		add_submenu_page( $parent, $name, $name, $capability, $slug, $callback, $menu['position'] ?? null );
	}

	/** @return array<int, array<string, mixed>> */
	private static function plugin_wordpress_menus(): array {
		$menus = [];

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
					foreach ( $definition['children'] ?? [] as $child ) {
						if ( is_array( $child ) ) {
							$child['parent'] = $definition['menu_slug'] ?? '';
							$menus[] = self::normalize_wordpress_menu( $child );
						}
					}
				}
			} catch ( \Throwable $e ) {
				LoggerHelper::write_log( sprintf( 'ModPress plugin %s failed to provide WordPress menus: %s', $plugin->get_slug(), $e->getMessage() ) );
			}
		}

		return array_values( array_filter( $menus, static fn ( $menu ): bool => is_array( $menu ) ) );
	}

	/** @param array<string, mixed> $definition @return array<string, mixed> */
	private static function normalize_wordpress_menu( array $definition ): array {
		return [
			'name'       => $definition['menu_title'] ?? $definition['page_title'] ?? '',
			'slug'       => $definition['menu_slug'] ?? '',
			'icon'       => $definition['icon'] ?? 'dashicons-admin-generic',
			'parent'     => $definition['parent'] ?? '',
			'callback'   => $definition['callback'] ?? null,
			'capability' => $definition['capability'] ?? 'manage_options',
			'position'   => $definition['position'] ?? null,
		];
	}

	private static function admin_parent_slug( string $parent ): string {
		$parent = strtolower( sanitize_text_field( $parent ) );
		return (string) preg_replace( '/[^a-z0-9._-]/', '', $parent );
	}

	/** @return array<int, array<string, mixed>> */
	private static function plugin_sidebar_menus(): array {
		$menus = [];

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
						$menus[] = MPASMHelper::define( $definition['label'] ?? '', $definition['slug'] ?? '', $definition['icon'] ?? '', '', $definition['capability'] ?? '' );
						foreach ( $definition['items'] ?? [] as $child ) {
							if ( is_array( $child ) ) {
								$menus[] = MPASMHelper::define( $child['label'] ?? '', self::sidebar_slug( $child ), $child['icon'] ?? '', $definition['slug'] ?? '', $child['capability'] ?? '' );
							}
						}
						continue;
					}

					$menus[] = MPASMHelper::define( $definition['label'] ?? '', self::sidebar_slug( $definition ), $definition['icon'] ?? '', $definition['parent'] ?? '', $definition['capability'] ?? '' );
				}
			} catch ( \Throwable $e ) {
				LoggerHelper::write_log( sprintf( 'ModPress plugin %s failed to provide sidebar menus: %s', $plugin->get_slug(), $e->getMessage() ) );
			}
		}

		return $menus;
	}

	/** @param array<string, mixed> $definition */
	private static function sidebar_slug( array $definition ): string {
		$page  = (string) ( $definition['page'] ?? $definition['slug'] ?? '' );
		$query = $definition['query'] ?? [];

		if ( ! is_array( $query ) || empty( $query ) ) {
			return $page;
		}

		return $page . '&' . http_build_query( array_filter( $query, 'is_scalar' ), '', '&', PHP_QUERY_RFC3986 );
	}

	/** @param array<string, array<string, mixed>> $groups @param array<string, mixed> $menu */
	private static function add_sidebar_group( array &$groups, array $menu ): void {
		$slug  = self::menu_slug( $menu );
		$label = (string) ( $menu['name'] ?? '' );
		$icon  = (string) ( $menu['icon'] ?? '' );

		if ( '' !== $slug && '' !== $label && '' !== $icon ) {
			$groups[ $slug ] = [ 'label' => $label, 'icon' => $icon, 'items' => [] ];
		}
	}

	/** @param array<string, array<string, mixed>> $groups @param array<string, mixed> $menu */
	private static function add_sidebar_item( array &$groups, string $parent, array $menu ): void {
		$slug  = (string) ( $menu['slug'] ?? '' );
		$label = (string) ( $menu['name'] ?? '' );
		$icon  = (string) ( $menu['icon'] ?? '' );

		$capability = sanitize_key( (string) ( $menu['capability'] ?? '' ) );
		if ( isset( $groups[ $parent ] ) && '' !== $slug && '' !== $label && '' !== $icon && ( '' === $capability || current_user_can( $capability ) ) ) {
			$groups[ $parent ]['items'][ $slug ] = [ 'label' => $label, 'icon' => $icon, 'capability' => $capability ];
		}
	}

	/** @param array<string, mixed> $menu */
	private static function parent_slug( array $menu ): string {
		return sanitize_key( (string) ( $menu['parent'] ?? '' ) );
	}

	/** @param array<string, mixed> $menu */
	private static function menu_slug( array $menu ): string {
		return sanitize_key( (string) ( $menu['slug'] ?? '' ) );
	}
}
