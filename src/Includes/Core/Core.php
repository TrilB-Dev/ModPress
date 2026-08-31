<?php
/**
 * Core component for ModPress.
 *
 * @package ModPress\Includes\Core
 */
namespace ModPress\Includes\Core;

use ModPress\Includes\Core\WP\WPLoader;
use ModPress\Includes\Pages\Shortcodes as PageShortcodes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Coordinate ModPress core registration and reusable WordPress services.
 *
 * Post types and taxonomies remain separate components; this class provides a
 * single lifecycle boundary for ModPress and extension plugins.
 */
final class Core {
    /**
     * @var PostType Post type registrar.
     */
    private PostType $post_types;
    /**
     * @var Taxonomy Taxonomy registrar.
     */
    private Taxonomy $taxonomies;
    /**
     * @var Shortcodes Shortcode registrar.
     */
    private Shortcodes $shortcodes;
    /**
     * @var bool Whether core registration has run.
     */
    private bool $registered = false;

    /**
     * Create the core coordinator with optional component instances.
     *
     * @param PostType|null $post_types Post type registrar.
     * @param Taxonomy|null $taxonomies Taxonomy registrar.
     */
    public function __construct( ?PostType $post_types = null, ?Taxonomy $taxonomies = null, ?Shortcodes $shortcodes = null ) {
        $this->post_types = $post_types ?? new PostType();
        $this->taxonomies = $taxonomies ?? new Taxonomy();
        $this->shortcodes = $shortcodes ?? new Shortcodes();
    }

    /**
     * Register the core components once.
     *
     * @return void
     */
    public function register(): void {
        if ( $this->registered ) {
            return;
        }

        Capabilities::install();
        $this->post_types->register();
        $this->taxonomies->register();
        PageShortcodes::register( $this->shortcodes );
        $this->registered = true;
    }

    /**
     * Attach core registration to a WordPress loader.
     *
    * @param WPLoader $loader Hook loader.
     * @param string $hook WordPress action name.
     * @param int $priority Registration priority.
     * @return self
     */
    public function register_hooks( WPLoader $loader, string $hook = 'init', int $priority = 10 ): self {
        $loader->add_action( $hook, $this, 'register', $priority, 0 );
        return $this;
    }

    /**
     * Get the post type registrar.
     *
     * @return PostType Post type registrar.
     */
    public function post_types(): PostType {
        return $this->post_types;
    }

    /**
     * Get the taxonomy registrar.
     *
     * @return Taxonomy Taxonomy registrar.
     */
    public function taxonomies(): Taxonomy {
        return $this->taxonomies;
    }

    public function shortcodes(): Shortcodes {
        return $this->shortcodes;
    }

    /**
     * Return whether core registration has run.
     *
     * @return bool Registration state.
     */
    public function is_registered(): bool {
        return $this->registered;
    }
}
