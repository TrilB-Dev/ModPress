<?php
/**
 * Backwards-compatible changelog wrapper.
 *
 * @package ModPress
 * @subpackage Admin\Manager\Mods
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\Mods;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ModChangeLog extends ModManager {
	/**
	 * Render changelog content through the plural implementation.
	 *
	 * @return void
	 */
	public function render_page_content(): void {
		( new ModChangeLogs() )->render_page_content();
	}
}
