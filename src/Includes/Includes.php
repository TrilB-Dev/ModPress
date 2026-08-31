<?php

namespace ModPress\Includes;

use ModPress\Includes\Core\Core;
use ModPress\Includes\Core\WP\WPLoader;
use ModPress\Includes\Functions\Helpers\LoggerHelper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Includes {
    private static ?self $instance = null;
    private Core $core;
    /** @var array<int, callable> */
    private array $extensions = [];
    private bool $initialized = false;

    private function __construct() {
        $this->core = new Core();
        LoggerHelper::write_log( 'ModPress core includes initialized.' );
    }

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

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

    public function is_initialized(): bool {
        return $this->initialized;
    }
}
