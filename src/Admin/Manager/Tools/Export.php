<?php
/**
 * Export class for ModPress plugin.
 *
 * @package ModPress
 * @subpackage Admin\Manager\Tools
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\Tools;

use ModPress\Includes\Functions\Admin\FunctionsExport;
use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\Functions\Helpers\LoaderHelper;
use ModPress\Includes\Functions\Helpers\UrlHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Export extends ToolsManager {
	/**
	 * Export handler instance.
	 *
	 * @var FunctionsExport
	 */
	private FunctionsExport $export_functions;

	/**
	 * Constructor for the Export class.
	 *
	 * @return void
	 */
	public function __construct( ?LoaderHelper $loader = null ) {
		parent::__construct( false );
		$this->export_functions = new FunctionsExport();
		( $loader ?? new LoaderHelper() )->register_component(
			$this->export_functions,
			array(
				array(
					'type'     => 'action',
					'hook'     => 'admin_post_modpress_export',
					'callback' => 'export_data',
				),
			)
		)->run();
	}
	/**
	 * Render the export form.
	 *
	 * @return void
	 */
	public function render_page_content(): void {
		?>
		<div class="card shadow-sm">
			<div class="card-body">
				<h2 class="h5"><?php esc_html_e( 'Export ModPress data', 'modpress' ); ?></h2>
				<p class="text-secondary"><?php esc_html_e( 'Download a protected JSON export of ModPress records, customer meta, and validation data.', 'modpress' ); ?></p>
				<?php echo wp_kses_post(
					FormFieldHelper::button(
						esc_html__( 'Export ModPress JSON', 'modpress' ),
						array(
							'href'  => UrlHelper::admin_action_nonce( 'modpress_export', 'modpress_export' ),
							'class' => 'btn-outline-primary',
						)
					)
				); ?>
			</div>
		</div>
		<?php
	}
	/**
	 * Render the export page content.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<tr>
			<th scope="row"><?php echo wp_kses_post(
				FormFieldHelper::label(
					'modpress-export',
					esc_html__( 'Import and export', 'modpress' ),
					array(
						'description' => __( 'Export or import ModPress data as a password-protected JSON archive.', 'modpress' ),
						'tooltip'     => __( 'Exports are protected with a WordPress nonce and should use a password whenever shared with partners.', 'modpress' ),
					)
				)
			); ?></th>
			<td><?php echo wp_kses_post(
				FormFieldHelper::button(
					esc_html__( 'Export ModPress JSON', 'modpress' ),
					array(
						'href'  => UrlHelper::admin_action_nonce( 'modpress_export', 'modpress_export' ),
						'class' => 'btn-outline-primary',
					)
				)
			); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php echo FormFieldHelper::label(
				'modpress-database-manager',
				esc_html__( 'Database manager', 'modpress' ),
				array(
					'description' => __( 'ModPress records are kept in the plugin database tables and managed through the core lifecycle.', 'modpress' ),
					'tooltip'     => __( 'Manual database changes are not required for normal ModPress operations.', 'modpress' ),
				)
			); ?></th>
			<td><?php esc_html_e( 'Managed automatically', 'modpress' ); ?></td>
		</tr>
		<?php
	}
}


