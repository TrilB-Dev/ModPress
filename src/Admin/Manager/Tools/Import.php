<?php
/**
 * Import class for ModPress plugin.
 *
 * @package ModPress
 * @subpackage Admin\Manager\Tools
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\Tools;

use ModPress\Includes\Functions\Admin\FunctionsImport;
use ModPress\Includes\Functions\Helpers\AlertHelper;
use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\Functions\Helpers\LoaderHelper;
use ModPress\Includes\Functions\Helpers\UrlHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Import extends ToolsManager {
	/**
	 * Import handler instance.
	 *
	 * @var FunctionsImport
	 */
	private FunctionsImport $import_functions;

	/**
	 * Render the JSON import form below the tools settings form.
	 *
	 * @return void
	 */
	public function __construct( ?LoaderHelper $loader = null ) {
		parent::__construct( false );
		$this->import_functions = new FunctionsImport();
		( $loader ?? new LoaderHelper() )->register_component(
			$this->import_functions,
			array(
				array(
					'type'     => 'action',
					'hook'     => 'admin_post_modpress_import',
					'callback' => 'import_data',
				),
				array(
					'type'     => 'action',
					'hook'     => 'admin_post_modpress_import_group_defaults',
					'callback' => 'import_group_defaults',
				),
			)
		)->run();
	}
	/**
	 * Render the import page content using the shared manager contract.
	 *
	 * @return void
	 */
	public function render_page_content(): void {
		$this->render();
	}

	/**
	 * Render the import form.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( '1' === (string) ( $_GET['default_groups_imported'] ?? '' ) ) {
			AlertHelper::render_admin_notice( \__( 'The default ModPress groups and taxonomy terms were imported successfully.', 'modpress' ), 'success' );
		}
		if ( '1' === (string) ( $_GET['default_groups_import_failed'] ?? '' ) ) {
			AlertHelper::render_admin_notice( \__( 'The default ModPress groups could not be imported.', 'modpress' ), 'error' );
		}
		?>
		<form
			method="post"
			action="<?php echo \esc_url( UrlHelper::admin_action( 'modpress_import' ) ); ?>"
			enctype="multipart/form-data"
			class="card modpress-import-form shadow-sm mt-4"
		>
			<?php echo \wp_kses_post( FormFieldHelper::input( 'action', 'modpress_import', array( 'type' => 'hidden' ) ) ); ?>
			<?php \wp_nonce_field( 'modpress_import' ); ?>
			<div class="card-body">
				<?php
				echo \wp_kses_post(
					FormFieldHelper::label(
						'modpress-import-file',
						\__( 'Import ModPress JSON', 'modpress' ),
						array(
							'description'  => \__( 'Select a JSON export containing ModPress records, customer data, and validation metadata.', 'modpress' ),
							'tooltip'      => \__( 'Import should use a valid archive and, where required, a password-protected file to preserve security.', 'modpress' ),
							'tooltip_icon' => 'fa-file-import',
						)
					)
				);
				echo \wp_kses_post(
					FormFieldHelper::input(
						'modpress_import_file',
						'',
						array(
							'id'       => 'modpress-import-file',
							'type'     => 'file',
							'class'    => 'mb-3',
							'accept'   => 'application/json,.json',
							'required' => true,
						)
					)
				);
				echo \wp_kses_post(
					FormFieldHelper::button(
						\__( 'Import ModPress JSON', 'modpress' ),
						array(
							'type'  => 'submit',
							'class' => 'btn-primary',
						)
					)
				);
				?>
			</div>
		</form>

		<div class="card modpress-import-form shadow-sm mt-4">
			<div class="card-body">
				<h3 class="h5 mb-3"><?php \esc_html_e( 'Import Group Defaults', 'modpress' ); ?></h3>
				<p class="text-secondary mb-3"><?php \esc_html_e( 'This will clear the existing default taxonomy terms and rebuild the default groups table from scratch.', 'modpress' ); ?></p>
				<button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modpress-import-default-groups-modal">
					<?php \esc_html_e( 'Import Group Defaults', 'modpress' ); ?>
				</button>
			</div>
		</div>

		<div class="modal fade" id="modpress-import-default-groups-modal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered" role="document">
				<form method="post" action="<?php echo \esc_url( UrlHelper::admin_action( 'modpress_import_group_defaults' ) ); ?>">
					<?php \wp_nonce_field( 'modpress_import_group_defaults', 'modpress_import_group_defaults_nonce' ); ?>
					<input type="hidden" name="action" value="modpress_import_group_defaults" />
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title"><?php \esc_html_e( 'Import Group Defaults', 'modpress' ); ?></h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php \esc_attr_e( 'Close', 'modpress' ); ?>"></button>
						</div>
						<div class="modal-body">
							<p><?php \esc_html_e( 'By importing the group defaults, all ModPress data entered by the user will be deleted and cannot be undone.', 'modpress' ); ?></p>
							<?php echo FormFieldHelper::checkbox(
								'confirm_default_reset',
								'1',
								\__( 'I understand this will delete all user-entered data and cannot be undone.', 'modpress' ),
								array(
									'id' => 'modpress-confirm-default-reset',
								)
							); ?>
						</div>
						<div class="modal-footer d-flex justify-content-between">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php \esc_html_e( 'Cancel', 'modpress' ); ?></button>
							<button type="submit" class="btn btn-danger"><?php \esc_html_e( 'Import', 'modpress' ); ?></button>
						</div>
					</div>
				</form>
			</div>
		</div>
		<?php
	}
}


