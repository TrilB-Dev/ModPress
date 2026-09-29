<?php

namespace ModPress\Includes;

use ModPress\Includes\Core\Capabilities;
use ModPress\Includes\Core\WP\WPLoader;
use ModPress\Includes\Functions\Helpers\LoggerHelper;
use ModPress\Includes\ModManagement\ModManagement;
use ModPress\Includes\Pages\CronJobs;
use ModPress\Includes\Pages\PostTypes;
use ModPress\Includes\Pages\Shortcodes;
use ModPress\Includes\Pages\Taxonomies;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Includes {
    /**
     * Instance of the Includes class.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Array of registered extension initializers.
     *
     * @var array
     */
    private array $extensions = array();

    /**
     * Flag indicating whether the Includes instance has been initialized.
     *
     * @var bool
     */
    private bool $initialized = false;

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct() {
        LoggerHelper::write_log( 'ModPress includes initialized.' );
    }

    /**
     * Get the singleton instance of the Includes class.
     *
     * @return self The singleton instance of the Includes class.
     */
    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    /**
     * Initialize the core ModPress runtime registrations.
     *
     * @return void
     */
    public function register_core(): void {
        if ( $this->initialized ) {
            return;
        }

        /**
         * Capabilities installation.
         * 
         * @since 1.0.0
         */
        Capabilities::install();
        /**
         * Mod management registration.
         * 
         * @since 1.0.0
         */
        ModManagement::register();
        /**
         * Post types registration.
         * 
         * @since 1.0.0
         */
        PostTypes::register();
        /**
         * Taxonomies registration.
         * 
         * @since 1.0.0
         */
        Taxonomies::register();
        /**
         * Shortcodes registration.
         * 
         * @since 1.0.0
         */
        Shortcodes::register();
        /**
         * Cron jobs registration.
         * 
         * @since 1.0.0
         */
        CronJobs::register();

        $this->initialized = true;
    }

    /**
     * Initialize the Includes instance and register core and extension functionalities.
     */
    public function init(): void {
        $this->register_core();

        foreach ( $this->extensions as $extension ) {
            call_user_func( $extension, $this );
        }
    }

    /**
     * Queue an extension initializer for the shared Includes lifecycle.
     *
     * Extensions registered after initialization are invoked immediately.
     *
     * @param callable $extension Callback receiving this Includes instance.
     * @return self
     */
    public function register_extension( callable $extension ): self {
        if ( $this->initialized ) {
            call_user_func( $extension, $this );
        } else {
            $this->extensions[] = $extension;
        }

        return $this;
    }

    /**
     * Attach the shared init lifecycle to an external ModPress loader.
     *
     * @param WPLoader $loader Loader owned by the main runtime or an extension.
     * @param string $hook WordPress action name.
     * @param int $priority Hook priority.
     * @return self
     */
    public function register_hooks( WPLoader $loader, string $hook = 'init', int $priority = 10 ): self {
        $loader->add_action( $hook, $this, 'init', $priority, 0 );
        return $this;
    }

    /**
     * Check if the Includes instance has been initialized.
     *
     * @return bool True if initialized, false otherwise.
     */
    public function is_initialized(): bool {
        return $this->initialized;
    }
}
