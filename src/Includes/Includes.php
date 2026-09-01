<?php

namespace ModPress\Includes;

use ModPress\Includes\Core\Core;
use ModPress\Includes\Core\WP\WPLoader;
use ModPress\Includes\Functions\Helpers\LoggerHelper;

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
     * Core instance for managing core functionalities.
     *
     * @var Core
     */
    private Core $core;
    /**
     * Array of registered extension initializers.
     *
     * @var array
     */
    private array $extensions = [];
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
        $this->core = new Core();
        LoggerHelper::write_log( 'ModPress core includes initialized.' );
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
     * Initialize the Includes instance and register core and extension functionalities.
     */
    public function init(): void {
        if ( $this->initialized ) {
            return;
        }

        $this->core->register();
        foreach ( $this->extensions as $extension ) {
            call_user_func( $extension, $this );
        }
        $this->initialized = true;
    }
    /**
     * Get the Core instance for managing core functionalities.
     *
     * @return Core The Core instance.
     */
    public function core(): Core {
        return $this->core;
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
     * Attach Core registration to an external ModPress loader.
     *
    * @param WPLoader $loader Loader owned by the main runtime or an extension.
     * @param string $hook WordPress action name.
     * @param int $priority Hook priority.
     * @return self
     */
    public function register_hooks( WPLoader $loader, string $hook = 'init', int $priority = 10 ): self {
        $this->core->register_hooks( $loader, $hook, $priority );
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
