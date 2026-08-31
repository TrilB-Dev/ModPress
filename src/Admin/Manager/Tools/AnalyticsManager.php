<?php
/**
 * AnalyticsManager class for ModPress plugin.
 *
 * @package ModPress
 * @subpackage Admin\Manager\Tools
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\Tools;

use ModPress\Admin\Manager\Manager;
use ModPress\Assets\Assets;
use ModPress\Includes\Analytics\Analytics;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class AnalyticsManager extends Manager {
    /**
     * The Page variable.
     *
     * @since 1.0.0
     * @access protected
     * @var string $page The page variable.
     */
    protected $page;
    /**
     * `Constructor` method for the `DashboardManager` class. 
     *
     * @since 1.0.0
     * @return void
     */

    public function __construct() {
        /**
         * Set the page variable to 'dashboard'.
         *
         * @since 1.0.0
         */
        $this->page = 'analytics';

    }
    /**
     * Renders the analytics page.
     *
     * @since 1.0.0
     * @return void
     */
    public function render_content(): void {
        echo '<div class="modpress-analytics-summary">';
        $this->card( __( 'Total Mod Page Views', 'modpress' ), Analytics::total_views(), 'modpress-manage' );
        echo '</div><h2 class="h4 mt-4">' . esc_html__( 'Most Viewed Mod Pages', 'modpress' ) . '</h2><div class="table-responsive"><table class="table modpress-analytics-table table-striped table-hover align-middle"><thead><tr><th>' . esc_html__( 'Page', 'modpress' ) . '</th><th>' . esc_html__( 'Views', 'modpress' ) . '</th></tr></thead><tbody>';
        foreach ( Analytics::top_pages() as $page ) {
            printf( '<tr><td><a href="%s">%s</a></td><td>%d</td></tr>', esc_url( $page['link'] ), esc_html( $page['title'] ), absint( $page['views'] ) );
        }
        echo '</tbody></table></div>';
    }
}
