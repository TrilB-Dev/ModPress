<?php
/**
 * Import class for ModPress plugin.
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

final class Import extends ToolsManager {
	/**
	 * Render the JSON import form below the tools settings form.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct( false );
	}
	/**
	 * Render the import form.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<form
			method="post"
			action="<?php echo esc_url( UrlHelper::admin_action( 'modpress_import' ) ); ?>"
			enctype="multipart/form-data"
			class="card modpress-import-form shadow-sm mt-4"
		>
			<?php echo wp_kses_post( FormFieldHelper::input( 'action', 'modpress_import', array( 'type' => 'hidden' ) ) ); ?>
			<?php wp_nonce_field( 'modpress_import' ); ?>
			<div class="card-body">
				<?php
				echo wp_kses_post(
					FormFieldHelper::label(
						'modpress-import-file',
						__( 'Import licence JSON', 'modpress' ),
						array(
							'description'  => __( 'Select a JSON export containing ModPress records, customer data, and validation metadata.', 'modpress' ),
							'tooltip'      => __( 'Import should use a valid archive and, where required, a password-protected file to preserve security.', 'modpress' ),
							'tooltip_icon' => 'fa-file-import',
						)
					)
				);
				echo wp_kses_post(
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
				echo wp_kses_post(
					FormFieldHelper::button(
						__( 'Import JSON', 'modpress' ),
						array(
							'type'  => 'submit',
							'class' => 'btn-primary',
						)
					)
				);
				?>
			</div>
		</form>
		<?php
	}
}


