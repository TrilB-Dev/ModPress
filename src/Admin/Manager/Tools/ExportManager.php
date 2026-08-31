<?php
/**
 * ExportManager class for ModPress plugin.
 * 
 * @package ModPress
 * @subpackage Admin\Manager\Tools
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\Tools;

use ModPress\Admin\Manager\Manager;
use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\Functions\Helpers\UrlHelper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ExportManager extends Manager {
    /**
     * Render the JSON export form below the tools settings form.
     *
     * @return void
     */
    public function __construct() {
    }
    /**
     * Render the JSON export form below the tools settings form.
     *
     * @return void
     */
    public function render_page_content(): void {
        echo '<div class="card shadow-sm"><div class="card-body"><h2 class="h5">' . esc_html__( 'Export ModPress data', 'modpress' ) . '</h2><p class="text-secondary">' . esc_html__( 'Download your ModPress content and settings as a JSON file.', 'modpress' ) . '</p>';
        echo wp_kses_post( FormFieldHelper::button( esc_html__( 'Export ModPress JSON', 'modpress' ), [ 'href' => UrlHelper::admin_action_nonce( 'modpress_export', 'modpress_export' ), 'class' => 'btn-outline-primary' ] ) );
        echo '</div></div>';
    }

    /**
     * Render export and database tool fields.
     *
     * @return void
     */
    public function render(): void {
        echo '<tr><th scope="row">' . wp_kses_post( FormFieldHelper::label( 'modpress-export', esc_html__( 'Import and export', 'modpress' ), [ 'description' => __( 'Export or import ModPress content and settings as JSON.', 'modpress' ), 'tooltip' => __( 'Exports are protected with a WordPress nonce.', 'modpress' ) ] ) ) . '</th><td>' . wp_kses_post( FormFieldHelper::button( esc_html__( 'Export ModPress JSON', 'modpress' ), [ 'href' => UrlHelper::admin_action_nonce( 'modpress_export', 'modpress_export' ), 'class' => 'btn-outline-primary' ] ) ) . '</td></tr>';
        echo '<tr><th scope="row">' . FormFieldHelper::label( 'modpress-database-manager', esc_html__( 'Database manager', 'modpress' ), [ 'description' => __( 'The settings table is managed automatically during plugin activation.', 'modpress' ), 'tooltip' => __( 'Manual database changes are not required for normal ModPress operation.', 'modpress' ) ] ) . '</th><td>' . esc_html__( 'Managed automatically', 'modpress' ) . '</td></tr>';
    }
}