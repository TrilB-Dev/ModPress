<?php
/**
 * ModPress - Plugins
 *
 * Handles discovery and loading of ModPress plugin modules.
 *
 * @package ModPress
 * @subpackage Includes\ModPress\Plugins
 * @since 1.0.0
 */

namespace ModPress\Includes\Plugins;

use ModPress\Includes\Functions\Helpers\LoggerHelper;
use ModPress\Includes\Settings\Settings;
use ModPress\Includes\Plugins\PluginInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Class Plugins
 *
 * Manages the discovery, loading, and initialization of ModPress plugin modules.
 */
class Plugins {
    /**
     * Singleton instance of the Plugins class.
     *
     * @var Plugins|null
     */
    private static ?Plugins $instance = null;
    /**
     * Array of loaded plugin class names.
     *
     * @var array
     */
    private array $loaded_plugins = [];
    /**
     * Array of registered plugin instances.
     *
     * @var array
     */
    private array $registered_plugins = [];
    /**
     * Indicates whether plugins should be auto-activated upon registration.
     *
     * @var bool
     */
    private bool $auto_activate = true;
    /**
     * Indicates whether the plugin system has been initialized.
     *
     * @var bool
     */
    private bool $initialized = false;
    /**
     * Get the singleton instance of the Plugins class.
     *
     * @return Plugins The singleton instance.
     */
    public static function get_instance(): Plugins {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }
    /**
     * Initializes the plugin system by discovering and loading plugin files.
     */
    public function init(): void {
        if ( $this->initialized ) {
            return;
        }

        $this->initialized = true;
        $this->auto_activate = $this->should_auto_activate();

        $directory = $this->resolve_plugin_directory();
        $files = $this->discover_plugin_files( $directory );

        foreach ( $files as $file ) {
            $this->load_plugin_file( $file );
        }

        /**
         * Allow WordPress plugins to register ModPress extensions.
         *
         * Plugins installed via the normal WordPress plugin system can hook
         * into this action and call ModPress\Plugins::register_plugin().
         */
        do_action( 'pluginpress_register_plugin', $this );
    }
    /**
     * Retrieves the list of loaded plugin class names.
     *
     * @return array List of loaded plugin class names.
     */
    public function get_loaded_plugins(): array {
        return $this->loaded_plugins;
    }
    /**
     * Retrieves the list of registered plugin instances.
     *
     * @return array List of registered plugin instances.
     */
    public function get_registered_plugins(): array {
        return $this->registered_plugins;
    }

    /**
     * Determines whether a plugin is enabled.
     *
     * Plugins without a saved state remain enabled for backwards compatibility.
     *
     * @param string $slug Plugin slug.
     * @return bool True when the plugin is enabled.
     */
    public function is_plugin_enabled( string $slug ): bool {
        $states = Settings::get_group( 'plugins', [] );
        if ( ! is_array( $states ) || ! array_key_exists( $slug, $states ) ) {
            return true;
        }

        return (bool) $states[ $slug ];
    }

    /**
     * Persists a plugin's enabled state.
     *
     * @param string $slug Plugin slug.
     * @param bool   $enabled Whether the plugin should be enabled.
     * @return bool True when the state is saved.
     */
    public function set_plugin_enabled( string $slug, bool $enabled ): bool {
        $slug = sanitize_key( $slug );
        if ( '' === $slug || ! isset( $this->registered_plugins[ $slug ] ) ) {
            return false;
        }

        $states = Settings::get_group( 'plugins', [] );
        $states = is_array( $states ) ? $states : [];
        $states[ $slug ] = $enabled;

        return Settings::set_group( 'plugins', $states );
    }
    /**
     * Registers a plugin instance with the plugin system.
     *
     * @param PluginInterface $plugin The plugin instance to register.
     */
    public static function register_plugin( PluginInterface $plugin ): void {
        self::get_instance()->register_plugin_instance( $plugin );
    }
    /**
     * Registers a plugin instance with the plugin system.
     *
     * @param PluginInterface $plugin The plugin instance to register.
     */
    public function register_plugin_instance( PluginInterface $plugin ): void {
        $slug = trim( $plugin->get_slug() );
        if ( $slug === '' ) {
            return;
        }

        if ( isset( $this->registered_plugins[ $slug ] ) ) {
            return;
        }

        $this->registered_plugins[ $slug ] = $plugin;

        if ( $this->initialized && $this->auto_activate && $this->is_plugin_enabled( $slug ) ) {
            $this->initialize_plugin( $plugin );
        }
    }
    /**
     * Resolves the plugin directory path based on settings or defaults.
     *
     * @return string The resolved plugin directory path.
     */
    private function resolve_plugin_directory(): string {
        $path = trim( Settings::get( 'pluginpress_plugin_directory', MODPRESS_PLUGINS ) );
        $resolved = $this->is_absolute_path( $path )
            ? untrailingslashit( $path )
            : untrailingslashit( MODPRESS_ROOT ) . '/' . ltrim( str_replace( '\\', '/', $path ), '/' );

        if ( ! is_dir( $resolved ) ) {
            return MODPRESS_PLUGINS;
        }

        return $resolved;
    }
    /**
     * Discovers plugin files in the specified directory and its subdirectories.
     *
     * @param string $directory The directory to search for plugin files.
     * @return array List of discovered plugin file paths.
     */
    private function discover_plugin_files( string $directory ): array {
        if ( ! is_dir( $directory ) ) {
            return [];
        }

        $files = glob( $directory . '/*.php' ) ?: [];
        $subdirs = glob( $directory . '/*', GLOB_ONLYDIR ) ?: [];

        foreach ( $subdirs as $subdir ) {
            $subfiles = glob( $subdir . '/*.php' ) ?: [];
            $files = array_merge( $files, array_filter( $subfiles, function ( string $file ): bool {
                $contents = file_get_contents( $file );
                return is_string( $contents ) && $this->extract_class_name( $contents ) !== '';
            } ) );
        }

        $files = array_filter( array_unique( $files ), 'is_file' );

        return array_values( array_filter( $files, static function ( string $file ): bool {
            return ! in_array( basename( $file ), [
                'Plugins.php',
                'PluginsInterface.php',
            ], true );
        } ) );
    }
    /**
     * Loads a plugin file, extracts its namespace and class name, and initializes the plugin if applicable.
     *
     * @param string $file The path to the plugin file.
     */
    private function load_plugin_file( string $file ): void {
        $contents = file_get_contents( $file );
        if ( ! is_string( $contents ) ) {
            return;
        }

        $namespace = $this->extract_namespace( $contents );
        $class_name = $this->extract_class_name( $contents );
        $expected_class = $namespace !== '' && $class_name !== ''
            ? sprintf( '%s\\%s', trim( $namespace, '\\' ), $class_name )
            : $class_name;
        $declared_before = get_declared_classes();

        try {
            $this->load_plugin_includes( dirname( $file ) );
            require_once $file;

            $plugin_classes = array_filter(
                array_diff( get_declared_classes(), $declared_before ),
                static fn ( string $class ): bool => is_subclass_of( $class, PluginInterface::class )
            );

            $fqcn = ! empty( $plugin_classes )
                ? (string) reset( $plugin_classes )
                : $expected_class;

            if ( ! class_exists( $fqcn ) || ! is_a( $fqcn, PluginInterface::class, true ) ) {
                LoggerHelper::write_log( sprintf( 'ModPress plugin file %s does not declare a PluginInterface implementation.', $file ) );
                return;
            }

            $instance = is_callable( [ $fqcn, 'get_instance' ] )
                ? $fqcn::get_instance()
                : new $fqcn();

            if ( ! $instance instanceof PluginInterface ) {
                LoggerHelper::write_log( sprintf( 'ModPress plugin %s does not implement PluginInterface.', $fqcn ) );
                return;
            }

            $this->register_plugin_instance( $instance );
            $this->loaded_plugins[] = $fqcn;
        } catch ( \Throwable $e ) {
            LoggerHelper::write_log( sprintf( 'ModPress plugin loader failed to require file %s: %s', $file, $e->getMessage() ) );
        }
    }
    /**
     * Loads additional includes for a plugin if they exist.
     *
     * @param string $plugin_directory The directory of the plugin.
     */
    private function load_plugin_includes( string $plugin_directory ): void {
        foreach ( [ 'Includes/Includes.php', 'Includes/I18n.php', 'Includes/Shortcodes.php' ] as $includes_file ) {
            $includes_path = trailingslashit( $plugin_directory ) . $includes_file;
            if ( is_readable( $includes_path ) ) {
                require_once $includes_path;
            }
        }
    }
    /**
     * Extracts the namespace from the given PHP file content.
     *
     * @param string $content The content of the PHP file.
     * @return string The extracted namespace, or an empty string if not found.
     */
    private function extract_namespace( string $content ): string {
        if ( preg_match( '/namespace\s+([^;]+);/i', $content, $matches ) ) {
            return trim( $matches[1] );
        }

        return '';
    }
    /**
     * Extracts the class name from the given PHP file content.
     *
     * @param string $content The content of the PHP file.
     * @return string The extracted class name, or an empty string if not found.
     */
    private function extract_class_name( string $content ): string {
        $tokens = token_get_all( $content );
        $token_count = count( $tokens );

        for ( $index = 0; $index < $token_count; $index++ ) {
            if ( ! is_array( $tokens[ $index ] ) || T_CLASS !== $tokens[ $index ][0] ) {
                continue;
            }

            for ( $index++; $index < $token_count; $index++ ) {
                if ( is_array( $tokens[ $index ] ) && T_STRING === $tokens[ $index ][0] ) {
                    return $tokens[ $index ][1];
                }
            }
        }

        return '';
    }
    /**
     * Determines whether plugins should be auto-activated upon registration.
     *
     * @return bool True if plugins should be auto-activated, false otherwise.
     */
    private function should_auto_activate(): bool {
        return Settings::get( 'mod_plugin_auto_activate', 'on' ) === 'on';
    }
    /**
     * Initializes a registered plugin instance if it is active.
     *
     * @param PluginInterface $plugin The plugin instance to initialize.
     */
    private function initialize_plugin( PluginInterface $plugin ): void {
        if ( ! $plugin->is_active() || ! $this->is_plugin_enabled( $plugin->get_slug() ) ) {
            return;
        }

        try {
            if ( $plugin instanceof SettingsProviderInterface ) {
                $plugin->register_settings();
            }

            if ( $plugin instanceof DatabaseProviderInterface ) {
                $plugin->register_tables();
            }

            if ( $plugin instanceof ShortcodeProviderInterface ) {
                \ModPress\Includes\Functions\Helpers\ShortcodeHelper::register_many( $plugin->get_shortcodes() );
            }

            if ( $plugin instanceof AssetsProviderInterface ) {
                $plugin->register_assets();
            }

            if ( $plugin instanceof AdminPageProviderInterface ) {
                $plugin->register_admin_pages();
            }

            if ( $plugin instanceof RestRouteProviderInterface ) {
                $plugin->register_rest_routes();
            }

            if ( $plugin instanceof FrontendProviderInterface ) {
                $plugin->register_frontend();
            }

            if ( $plugin instanceof I18nProviderInterface ) {
                $plugin->load_textdomain();
            }

            $plugin->init();
        } catch ( \Throwable $e ) {
            LoggerHelper::write_log( sprintf( 'ModPress plugin %s failed to initialize: %s', $plugin->get_slug(), $e->getMessage() ) );
        }
    }
    /**
     * Checks if the given path is an absolute path.
     *
     * @param string $path The path to check.
     * @return bool True if the path is absolute, false otherwise.
     */
    private function is_absolute_path( string $path ): bool {
        return preg_match( '/^(?:[A-Za-z]:[\\\\\/]|[\\\\\\/])/', $path ) === 1;
    }
}