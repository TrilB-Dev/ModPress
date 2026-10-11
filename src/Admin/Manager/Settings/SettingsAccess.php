<?php
/**
 * Settings access restriction fields.
 *
 * @package TrilBDev
 * @subpackage Admin\Manager\Settings
 */
namespace ModPress\Admin\Manager\Settings;

use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\Functions\Helpers\SanitizationHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SettingsAccess {
	/**
	 * Render access restriction fields.
	 *
	 * @param array<string, mixed> $values Current settings.
	 * @return void
	 */
	public function render( array $values ): void {
		$can_edit = current_user_can( 'manage_options' ) || current_user_can( 'modpress_settings_access_edit' );
		$options = [
			[ 'value' => 'manage_options', 'label' => __( 'Administrators', 'modpress' ) ],
			[ 'value' => 'edit_posts', 'label' => __( 'Editors', 'modpress' ) ],
			[ 'value' => 'publish_posts', 'label' => __( 'Authors', 'modpress' ) ],
		];
		$create_mods = $values['create_mods'] ?? [ 'manage_options' ];
		$write_pages = $values['write_pages'] ?? [ 'manage_options' ];
		$view_analytics = $values['view_analytics'] ?? [ 'manage_options' ];
		$manage_plugins = $values['manage_plugins'] ?? [ 'manage_options' ];
		?>
		<?php if ( $can_edit ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="modpress-settings-form">
			<input type="hidden" name="action" value="modpress_save_access_settings" />
			<input type="hidden" name="modpress_tab" value="access" />
			<?php wp_nonce_field( 'modpress_save_access_settings' ); ?>
		<?php endif; ?>
			<table class="form-table table align-middle" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<?php echo FormFieldHelper::label(
								'modpress-access-create-mods',
								__( 'Who can create mods?', 'modpress' ),
								[
									'description' => __( 'Choose the minimum capability required to create ModPress mods.', 'modpress' ),
									'tooltip' => __( 'Users without this capability cannot create new mods.', 'modpress' ),
								]
							); ?>
						</th>
						<td>
							<?php echo FormFieldHelper::bootstrap_multiselect(
								'modpress_access[create_mods]',
								[
									'id' => 'modpress-access-create-mods',
									'data' => $options,
									'selected' => array_values( 
										array_filter( 
											array_map( 
												'sanitize_key', 
												(array) $create_mods 
											) 
										) 
									),
								]
							); ?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php echo FormFieldHelper::label(
								'modpress-access-write-pages',
								__( 'Who can write mod pages?', 'modpress' ),
								[
									'description' => __( 'Choose the minimum capability required to create or edit mod pages.', 'modpress' ),
									'tooltip' => __( 'This controls editing access to mod page content.', 'modpress' ),
								]
							); ?>
						</th>
						<td>
							<?php echo FormFieldHelper::bootstrap_multiselect(
								'modpress_access[write_pages]',
								[
									'id' => 'modpress-access-write-pages',
									'data' => $options,
									'selected' => array_values( 
										array_filter( 
											array_map( 
												'sanitize_key', 
												(array) $write_pages 
											) 
										) 
									),
								]
							); ?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php echo FormFieldHelper::label(
								'modpress-access-view-analytics',
								__( 'Who can check analytics?', 'modpress' ),
								[
									'description' => __( 'Choose the minimum capability required to view ModPress analytics.', 'modpress' ),
									'tooltip' => __( 'Analytics data is shown only to users who meet this capability.', 'modpress' ),
									'tooltip_type' => 'info',
								]
							); ?>
						</th>
						<td>
							<?php echo FormFieldHelper::bootstrap_multiselect(
								'modpress_access[view_analytics]',
								[
									'id' => 'modpress-access-view-analytics',
									'data' => $options,
									'selected' => array_values( 
										array_filter( 
											array_map( 
												'sanitize_key', 
												(array) $view_analytics 
											) 
										) 
									),
								]
							); ?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php echo FormFieldHelper::label(
								'modpress-access-manage-plugins',
								__( 'Who can manage plugins?', 'modpress' ),
								[
									'description' => __( 'Choose the minimum capability required to manage ModPress plugins.', 'modpress' ),
									'tooltip' => __( 'Use a trusted administrator-level capability for plugin management.', 'modpress' ),
									'tooltip_icon' => 'fa-shield-halved',
								]
							); ?>
						</th>
						<td>
							<?php echo FormFieldHelper::bootstrap_multiselect(
								'modpress_access[manage_plugins]',
								[
									'id' => 'modpress-access-manage-plugins',
									'data' => $options,
									'selected' => array_values( 
										array_filter( 
											array_map( 
												'sanitize_key', 
												(array) $manage_plugins 
											) 
										) 
									),
								]
							); ?>
						</td>
					</tr>
				</tbody>
			</table>
		<?php if ( $can_edit ) : ?>
			<div class="modpress-settings-actions d-flex justify-content-end mt-3">
				<?php echo FormFieldHelper::button(
					__( 'Save Changes', 'modpress' ),
					[
						'type' => 'submit',
						'name' => 'submit',
						'class' => 'btn-primary',
					]
				); ?>
			</div>
		</form>
		<?php endif; ?>
		<?php
	}
}
