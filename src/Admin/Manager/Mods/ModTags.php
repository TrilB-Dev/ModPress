<?php
/**
 * Mod Tags page class.
 * Handles the management and display of mod tags within the admin interface.
 * 
 * @package ModPress
 * @subpackage Admin\Manager\Mods
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\Mods;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ModTags extends ModManager {
    /**
     * Renders the content for the Mod Tags page.
     *
     * @since 1.0.0
     */
    public function render_page_content(): void {
        // Render the content for the Mod Tags page here.
        ?>
        <div class="card shadow-sm border-0">
			<div class="card-body p-4">
            </div>
        </div>
        <?php
    }
}