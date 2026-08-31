<?php
/**
 * ToolsManager class for ModPress plugin.
 *
 * @package ModPress
 * @subpackage Admin\Manager\Tools
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\Tools;

use ModPress\Admin\Manager\Manager;
use ModPress\Admin\Manager\Tools\DebugManager;
use ModPress\Admin\Manager\Tools\ExportManager;
use ModPress\Admin\Manager\Tools\ImportManager;
use ModPress\Admin\Manager\Tools\AnalyticsManager;
use ModPress\Assets\Assets;
use ModPress\Includes\Functions\Helpers\SanitizationHelper;


final class ToolsManager extends Manager {
    /**
     * The Page variable.
     *
     * @since 1.0.0
     * @access protected
     * @var string $page The page variable.
     */
    protected $page;
    /**
     * `Constructor` method for the `ToolsManager` class.
     *
     * @since 1.0.0
     * @return void
     */
    private DebugManager $debug_manager;
    /**
     * ExportManager instance for managing export-related admin pages.
     *
     * @var ExportManager
     */
    private ExportManager $export_manager;
    /**
     * ImportManager instance for managing import-related admin pages.
     *
     * @var ImportManager
     */
    private ImportManager $import_manager;
    /**
     * AnalyticsManager instance for managing analytics-related admin pages.
     *
     * @var AnalyticsManager
     */
    private AnalyticsManager $analytics_manager;

    /**
     * `Constructor` method for the `ToolsManager` class.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        /**
         * Set the page variable to 'tools'.
         *
         * @since 1.0.0
         */
        $this->page = 'tools';
        /**
         * Initialize the Debug Manager page.
         *
         * @since 1.0.0
         */
        $this->debug_manager = new DebugManager();
        /**
         * Initialize the Export Manager page.
         *
         * @since 1.0.0
         */
        $this->export_manager = new ExportManager();
        /**
         * Initialize the Import Manager page.
         *
         * @since 1.0.0
         */
        $this->import_manager = new ImportManager();
        /**
         * Initialize the Analytics Manager page.
         *
         * @since 1.0.0
         */
        $this->analytics_manager = new AnalyticsManager();
    }
    /**
     * Renders the tools page.
     *
     * @since 1.0.0
     * @return void
     */
    public function render(): void {
        $tool = SanitizationHelper::key( $_GET['tool'] ?? 'debug', 'debug' );
        $tool = in_array( $tool, [ 'analytics', 'debug', 'import', 'export' ], true ) ? $tool : 'debug';
        $capabilities = [
            'analytics' => 'modpress_tools_analytics',
            'debug' => 'modpress_tools_debug',
            'import' => 'modpress_tools_import',
            'export' => 'modpress_tools_export',
        ];
        if ( ! current_user_can( $capabilities[ $tool ] ) ) {
            wp_die( esc_html__( 'You are not authorized to access this ModPress tool.', 'modpress' ) );
        }
        $this->header( $this->title( $tool ) );
        if ( 'analytics' === $tool ) {
            $this->analytics_manager->render_content();
        } elseif ( 'debug' === $tool ) {
            $this->debug_manager->render_page_content();
        } elseif ( 'import' === $tool ) {
            $this->import_manager->render();
        } else {
            $this->export_manager->render_page_content();
        }
        $this->footer();
    }
    /**
     * Registers the assets for the tools page.
     *
     * @since 1.0.0
     * @param Assets $assets The Assets instance.
     * @return void
     */
    public function register_assets( Assets $assets ): void {
        $this->register_page_assets( $assets, [ 'modpress-tools' ], 'analytics' );
    }
    /**
     * Returns the title for the given tool.
     *
     * @since 1.0.0
     * @param string $tool The tool name.
     * @return string The title for the tool.
     */
    private function title( string $tool ): string {
        return [
            'analytics' => __( 'Analytics', 'modpress' ),
            'debug' => __( 'Debug', 'modpress' ),
            'import' => __( 'Import', 'modpress' ),
            'export' => __( 'Export', 'modpress' ),
        ][ $tool ];
    }
}