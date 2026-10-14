<?php
/**
 * ToolsManager class for ModPress plugin.
 *
 * @package ModPress
 */
namespace ModPress\Admin\Manager\Tools;

use ModPress\Admin\Manager\Manager;
use ModPress\Admin\Manager\Tools\Debug;
use ModPress\Admin\Manager\Tools\Reset;
use ModPress\Admin\Manager\Tools\Import;
use ModPress\Admin\Manager\Tools\Export;
use ModPress\Admin\Manager\Tools\General;
use ModPress\Assets\Assets;
use ModPress\Includes\Functions\Helpers\RequestHelper;
use ModPress\Includes\Functions\Helpers\SanitizationHelper;
use ModPress\Includes\Functions\Helpers\PermissionHelper;


class ToolsManager extends Manager {
	/**
	 * The Page variable.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string $page The page variable.
	 */
	protected string $page;

	/**
	 * `Constructor` method for the `ToolsManager` class.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct( bool $initialize_tools = true ) {
		$this->page = 'tools';
	}
	/**
	 * Renders the tools page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render(): void {
		$tool = RequestHelper::get_key( 'tool', 'debug' );
		if ( ! in_array( $tool, array( 'debug', 'reset', 'import', 'export', 'general' ), true ) ) {
			$tool = 'debug';
		}
		$capabilities = array(
			'debug'  => 'modpress_tools_debug',
			'reset'  => 'modpress_tools_reset',
			'import' => 'modpress_tools_import',
			'export' => 'modpress_tools_export',
			'general' => 'modpress_tools_general',
		);
		if ( ! PermissionHelper::can( $capabilities[ $tool ] ) ) {
			wp_die( esc_html__( 'You are not authorized to access this ModPress tool.', 'modpress' ) );
		}
		$this->header( $this->title( $tool ) );
		$screen = $this->resolve_tool_screen( $tool );
		if ( null !== $screen ) {
			$screen->render_page_content();
		}
		$this->footer();
	}

	/**
	 * Resolve a tool screen lazily to avoid recursive child manager construction.
	 *
	 * @param string $tool Tool slug.
	 * @return object|null
	 * @since 1.0.0
	 */
	private function resolve_tool_screen( string $tool ): ?object {
		switch ( $tool ) {
			case 'reset':
				return new Reset();
			case 'debug':
				return new Debug();
			case 'import':
				return new Import();
			case 'general':
				return new General();
			case 'export':
			default:
				return new Export();
		}
	}
	/**
	 * Registers the assets for the tools page.
	 *
	 * @since 1.0.0
	 * @param Assets $assets The Assets instance.
	 * @return void
	 */
	public function register_assets( Assets $assets ): void {
		$this->register_page_assets( $assets, array( 'modpress-tools' ), 'tools' );
	}
	/**
	 * Returns the title for the given tool.
	 *
	 * @since 1.0.0
	 * @param string $tool The tool name.
	 * @return string The title for the tool.
	 */
	private function title( string $tool ): string {
		return array(
			'debug'  => __( 'Debug', 'modpress' ),
			'reset'  => __( 'Reset', 'modpress' ),
			'import' => __( 'Import', 'modpress' ),
			'export' => __( 'Export', 'modpress' ),
			'general' => __( 'General', 'modpress' ),
		)[ $tool ];
	}
}


