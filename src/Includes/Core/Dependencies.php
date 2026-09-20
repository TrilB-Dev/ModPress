<?php
/**
 * Dependency installer for external plugins and extras packages required by ModPress.
 *
 * @package ModPress\Includes\Core
 */
namespace ModPress\Includes\Core;

use ModPress\Includes\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Dependencies {
	/**
	 * Return the list of registered dependency definitions.
	 *
	 * Each dependency is a separate function so new packages can be added cleanly.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function dependencies(): array {
		return array(
			self::fontawesome(),
		);
	}

	/**
	 * Install all required dependencies.
	 *
	 * @return void
	 */
	public static function install(): void {
		foreach ( self::dependencies() as $dependency ) {
			self::install_dependency( $dependency );
		}
	}

	/**
	 * Uninstall/deactivate all required dependencies.
	 *
	 * @return void
	 */
	public static function uninstall(): void {
		if ( ! Settings::get_bool( 'uninstall_3rd_party_plugins', false ) ) {
			return;
		}

		foreach ( self::dependencies() as $dependency ) {
			self::uninstall_dependency( $dependency );
		}
	}

	/**
	 * Configure the official Font Awesome plugin dependency.
	 *
	 * @return array<string, mixed>
	 */
	public static function fontawesome(): array {
		return array(
			'name'             => 'Font Awesome',
			'slug'             => 'font-awesome',
			'file'             => 'font-awesome/font-awesome.php',
			'source'           => 'wordpress_org',
			'version'          => 'latest',
			'delete_on_uninstall' => false,
		);
	}

	/**
	 * Install a single dependency using the source defined in the package metadata.
	 *
	 * @param array<string, mixed> $dependency Dependency definition.
	 * @return void
	 */
	private static function install_dependency( array $dependency ): void {
		$plugin_file = self::plugin_file( $dependency );
		if ( empty( $plugin_file ) ) {
			return;
		}

		if ( is_plugin_active( $plugin_file ) ) {
			return;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_data = get_plugins();
		if ( isset( $plugin_data[ $plugin_file ] ) ) {
			activate_plugin( $plugin_file, '', false, true );
			return;
		}

		if ( 'wordpress_org' === ( $dependency['source'] ?? 'wordpress_org' ) ) {
			self::install_from_wordpress_org( $dependency );
			return;
		}

		if ( 'github' === ( $dependency['source'] ?? '' ) ) {
			self::install_from_github( $dependency );
		}
	}

	/**
	 * Uninstall/deactivate a single dependency.
	 *
	 * @param array<string, mixed> $dependency Dependency definition.
	 * @return void
	 */
	private static function uninstall_dependency( array $dependency ): void {
		$plugin_file = self::plugin_file( $dependency );
		if ( empty( $plugin_file ) ) {
			return;
		}

		if ( is_plugin_active( $plugin_file ) ) {
			deactivate_plugins( $plugin_file, true, false );
		}

		if ( ! empty( $dependency['delete_on_uninstall'] ) && ! empty( $dependency['target_dir'] ) ) {
			$target_dir = WP_PLUGIN_DIR . '/' . $dependency['target_dir'];
			if ( is_dir( $target_dir ) ) {
				self::delete_directory( $target_dir );
			}
		}
	}

	/**
	 * Install a dependency from the WordPress.org plugin repository.
	 *
	 * @param array<string, mixed> $dependency Dependency definition.
	 * @return void
	 */
	private static function install_from_wordpress_org( array $dependency ): void {
		$slug = (string) ( $dependency['slug'] ?? '' );
		if ( empty( $slug ) ) {
			return;
		}

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}

		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
		}

		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => $slug,
				'fields' => array(
					'sections' => false,
					'tags'     => false,
				),
			)
		);

		if ( is_wp_error( $api ) || empty( $api->download_link ) ) {
			return;
		}

		$upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
		$result   = $upgrader->install( $api->download_link );

		if ( ! is_wp_error( $result ) ) {
			$plugin_file = self::plugin_file( $dependency );
			if ( $plugin_file && file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
				activate_plugin( $plugin_file, '', false, true );
			}
		}
	}

	/**
	 * Install a dependency from the TrilB Dev GitHub extras repository.
	 *
	 * The downloaded package is stored using the plugin name and version to keep each payload isolated.
	 *
	 * @param array<string, mixed> $dependency Dependency definition.
	 * @return void
	 */
	private static function install_from_github( array $dependency ): void {
		$slug       = (string) ( $dependency['slug'] ?? '' );
		$repo       = (string) ( $dependency['repo'] ?? 'TrilB-Dev/WP-Plugin-Extras' );
		$version    = (string) ( $dependency['version'] ?? 'latest' );
		$asset_name = (string) ( $dependency['asset_name'] ?? $slug );

		if ( empty( $slug ) || 'latest' === $version ) {
			return;
		}

		$plugin_file = self::plugin_file( $dependency );
		if ( empty( $plugin_file ) ) {
			return;
		}

		if ( is_plugin_active( $plugin_file ) ) {
			return;
		}

		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
		}

		$package_url = sprintf(
			'https://github.com/%s/archive/refs/tags/%s-%s.zip',
			$repo,
			sanitize_title( $asset_name ),
			sanitize_title( $version )
		);

		$target_dir = sanitize_file_name( $slug . '-' . $version );
		$dependency['target_dir'] = $target_dir;
		$dependency['file'] = $target_dir . '/' . basename( $slug ) . '.php';

		if ( file_exists( WP_PLUGIN_DIR . '/' . $target_dir ) ) {
			$plugin_file = self::plugin_file( $dependency );
			if ( $plugin_file && file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
				activate_plugin( $plugin_file, '', false, true );
			}
			return;
		}

		$upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
		$result   = $upgrader->install( $package_url );

		if ( ! is_wp_error( $result ) ) {
			$plugin_file = self::plugin_file( $dependency );
			if ( $plugin_file && file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
				activate_plugin( $plugin_file, '', false, true );
			}
		}
	}

	/**
	 * Resolve the plugin file path for a dependency definition.
	 *
	 * @param array<string, mixed> $dependency Dependency definition.
	 * @return string
	 */
	private static function plugin_file( array $dependency ): string {
		if ( ! empty( $dependency['file'] ) ) {
			return (string) $dependency['file'];
		}

		$slug = (string) ( $dependency['slug'] ?? '' );
		if ( empty( $slug ) ) {
			return '';
		}

		return $slug . '/' . $slug . '.php';
	}

	/**
	 * Delete a plugin directory recursively.
	 *
	 * @param string $directory Directory to remove.
	 * @return void
	 */
	private static function delete_directory( string $directory ): void {
		if ( ! is_dir( $directory ) ) {
			return;
		}

		$items = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $directory, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $items as $item ) {
			if ( $item->isDir() ) {
				rmdir( $item->getPathname() );
			} else {
				unlink( $item->getPathname() );
			}
		}

		rmdir( $directory );
	}
}
