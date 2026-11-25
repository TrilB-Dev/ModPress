<?php
/**
 * ModPress Integrations class.
 *
 * @package ModPress
 * @subpackage Admin\Manager\Tools
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\Tools;

use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\Functions\Helpers\LoaderHelper;
use ModPress\Includes\Functions\Helpers\PermissionHelper;
use ModPress\Includes\Settings\Settings;
use ModPress\Includes\Functions\Helpers\RequestHelper;
use ModPress\Includes\Functions\Helpers\AlertHelper;
use ModPress\Includes\Integrations\Integrations as IntegrationsAccess;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Integrations extends ToolsManager {
	/**
	 * Constructor for the Integrations class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct( false );
		( $loader ?? new LoaderHelper() )->register_component(
			$this,
			array(
				array(
					'type'     => 'action',
					'hook'     => 'admin_post_modpress_save_tools_integrations',
					'callback' => 'handle_save',
				),
			)
		)->run();
	}
	/**
	 * Render the integrations settings page content.
	 *
	 * @return void
	 */
	public function render_page_content(): void {
		$this->render();
	}

	/**
	 * Render debug-related settings fields.
	 *
	 * Intentionally accepts an optional values array to stay compatible with the
	 * parent ToolsManager contract while the debug tool renders its own page.
	 *
	 * @param array<string, mixed> $values Current settings.
	 * @return void
	 */
	public function render( array $values = array() ): void {
		if ( '1' === RequestHelper::get_text( 'settings_saved' ) ) {
			AlertHelper::render_admin_notice( __( 'The integrations tools settings were saved.', 'modpress' ), 'success' );
		}

		$values = array(
			'enable_integrations' => Settings::get_bool( 'enable_integrations', false ),
		);
		?>
		<div class="modpress-tools-integration-page">
			<div class="card shadow-sm mb-4">
				<div class="card-body">
					<h2 class="h5"><?php esc_html_e( 'General tools settings', 'modpress' ); ?></h2>
					<p class="text-secondary"><?php esc_html_e( 'Configure the default cleanup behaviour used when ModPress is uninstalled or deactivated.', 'modpress' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php echo FormFieldHelper::input( 'action', 'modpress_save_tools_integrations', array( 'type' => 'hidden' ) ); ?>
						<?php wp_nonce_field( 'modpress_tools_integrations', 'modpress_tools_integrations_nonce' ); ?>
						<div class="mt-3">
							<?php echo FormFieldHelper::switch(
								'modpress_tools[enable_integrations]',
								true,
								__( 'Enable integrations', 'modpress' ),
								array(
									'id'      => 'modpress-enable-integrations',
									'checked' => ! empty( $values['enable_integrations'] ) ?? false,
								)
							); ?>
						</div>
						<div id="modpress-integration-switches" class="<?php echo $values['enable_integrations'] ? '' : 'd-none'; ?>">
							<?php foreach ( IntegrationsAccess::get_instance()->get_available_definitions() as $integration ) : ?>
								<div class="mt-3">
									<?php echo FormFieldHelper::switch(
										'modpress_tools[' . esc_attr( $integration['setting_key'] ) . ']',
										true,
										sprintf( __( 'Enable %s integration', 'modpress' ), $integration['name'] ),
										array(
											'id'      => 'modpress-enable-' . esc_attr( $integration['slug'] ) . '-integration',
											'checked' => ! empty( $integration['enabled'] ),
										)
									); ?>
								</div>
							<?php endforeach; ?>
						</div>
						<div class="mt-4">
							<?php echo FormFieldHelper::button(
								__( 'Save settings', 'modpress' ),
								array(
									'type'  => 'submit',
									'class' => 'btn-primary',
								)
							); ?>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

    /**
	 * Save the general tools settings.
	 *
	 * @return void
	 */
	public function handle_save(): void {
		if ( ! PermissionHelper::can( 'modpress_tools_integrations' ) ) {
			wp_die( esc_html__( 'You are not authorized to save these ModPress tools settings.', 'modpress' ) );
		}

		check_admin_referer( 'modpress_tools_integrations', 'modpress_tools_integrations_nonce' );

		$input = isset( $_POST['modpress_tools'] ) && is_array( $_POST['modpress_tools'] ) ? wp_unslash( $_POST['modpress_tools'] ) : array();
		$input['enable_integrations'] = ! empty( $input['enable_integrations'] );

		foreach ( $input as $key => $value ) {
			Settings::set( $key, $value );
		}

		foreach ( IntegrationsAccess::get_instance()->get_available_definitions() as $integration ) {
			$setting_key = $integration['setting_key'];
			$enabled = ! empty( $input[$setting_key] );
			IntegrationsAccess::get_instance()->set_enabled( $integration['slug'], $enabled );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=modpress&group=tools&tab=integrations&settings_saved=1' ) );
		exit;
	}
}


