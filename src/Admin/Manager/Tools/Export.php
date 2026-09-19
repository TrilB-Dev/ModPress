<?php
/**
 * Export class for ModPress plugin.
 *
 * @package ModPress
 * @subpackage Admin\Manager\Tools
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\Tools;

use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\Functions\Helpers\UrlHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Export extends ToolsManager {
	/**
	 * Constructor for the Export class.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct( false );
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
				<h2 class="h5"><?php esc_html_e( 'Export licence data', 'modpress' ); ?></h2>
				<p class="text-secondary"><?php esc_html_e( 'Download a protected JSON export of licence records, customer meta, and validation data.', 'modpress' ); ?></p>
				<?php echo wp_kses_post(
					FormFieldHelper::button(
						esc_html__( 'Export licence JSON', 'modpress' ),
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
					esc_html__( 'Export licence JSON', 'modpress' ),
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
					'description' => __( 'Licence records are kept in the plugin database tables and managed through the core lifecycle.', 'modpress' ),
					'tooltip'     => __( 'Manual database changes are not required for normal licence operations.', 'modpress' ),
				)
			); ?></th>
			<td><?php esc_html_e( 'Managed automatically', 'modpress' ); ?></td>
		</tr>
		<?php
	}
}


