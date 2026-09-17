<?php
/**
 * Settings general fields.
 * @package ModPress
 * @subpackage Admin\Manager\Settings
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\Settings;

use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\Functions\Helpers\PermalinkHelper;
use ModPress\Includes\Functions\Helpers\SanitizationHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SettingsGeneral {
	/**
	 * Render general ModPress settings fields.
	 *
	 * @param array<string, mixed> $values Current settings.
	 * @return void
	 */
	public function render( array $values ): void {
		$root_name = isset( $values['root_name'] ) ? SanitizationHelper::text( (string) $values['root_name'] ) : __( 'ModPress', 'modpress' );
		$root_description = isset( $values['root_description'] ) ? SanitizationHelper::text( (string) $values['root_description'] ) : __( 'A searchable catalogue powered by ModPress.', 'modpress' );
		$archive_title = isset( $values['archive_title'] ) ? SanitizationHelper::text( (string) $values['archive_title'] ) : __( 'ModPress Catalogue', 'modpress' );
		$archive_description = isset( $values['archive_description'] ) ? SanitizationHelper::text( (string) $values['archive_description'] ) : __( 'Browse the ModPress catalogue.', 'modpress' );
		$root_slug = isset( $values['root_slug'] ) ? SanitizationHelper::key( (string) $values['root_slug'] ) : 'catalogue';
		$category_slug = isset( $values['category_slug'] ) ? SanitizationHelper::key( (string) $values['category_slug'] ) : 'catalogue-group';
		$tag_slug = isset( $values['tag_slug'] ) ? SanitizationHelper::key( (string) $values['tag_slug'] ) : 'catalogue-tag';
		$permalink = isset( $values['permalink'] ) ? PermalinkHelper::sanitize_pattern( (string) $values['permalink'] ) : '%root%/%mod_category%/%mod_tag%/%mod_page%';
		$enable_schema = ! empty( $values['enable_schema'] ?? true );
		?>
		<table class="form-table table align-middle" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php echo FormFieldHelper::label( 'modpress-root-name', __( 'ModPress Root Name', 'modpress' ), [
						'description' => __( 'The name used for the main ModPress area.', 'modpress' ),
						'tooltip' => __( 'This name appears in the admin interface and generated titles.', 'modpress' ),
					] ); ?></th>
					<td>
						<?php echo FormFieldHelper::text_input( 'modpress_general[root_name]', $root_name, [ 'id' => 'modpress-root-name' ] ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo FormFieldHelper::label( 'modpress-root-description', __( 'ModPress Description', 'modpress' ), [
						'description' => __( 'A short description for the ModPress knowledge base.', 'modpress' ),
						'tooltip' => __( 'This can be used by themes and integrations when describing the ModPress area.', 'modpress' ),
					] ); ?></th>
					<td>
						<?php echo FormFieldHelper::textarea( 'modpress_general[root_description]', $root_description, [ 'id' => 'modpress-root-description', 'rows' => 3 ] ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo FormFieldHelper::label( 'modpress-archive-title', __( 'ModPress Archive Title', 'modpress' ), [
						'description' => __( 'The title shown on ModPress archive and index views.', 'modpress' ),
						'tooltip' => __( 'Use a concise title that makes the documentation area clear to visitors.', 'modpress' ),
					] ); ?></th>
					<td>
						<?php echo FormFieldHelper::text_input( 'modpress_general[archive_title]', $archive_title, [ 'id' => 'modpress-archive-title' ] ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo FormFieldHelper::label( 'modpress-archive-description', __( 'ModPress Archive Description', 'modpress' ), [
						'description' => __( 'Supporting text shown on ModPress archive and index views.', 'modpress' ),
						'tooltip' => __( 'A short introduction helps visitors understand what they can find in the ModPress area.', 'modpress' ),
					] ); ?></th>
					<td>
						<?php echo FormFieldHelper::textarea( 'modpress_general[archive_description]', $archive_description, [ 'id' => 'modpress-archive-description', 'rows' => 3 ] ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo FormFieldHelper::label( 'modpress-root-slug', __( 'ModPress Root Slug', 'modpress' ), [
						'description' => __( 'The URL slug for the ModPress root.', 'modpress' ),
						'tooltip' => __( 'Use lowercase letters, numbers, and hyphens for the most reliable URLs.', 'modpress' ),
					] ); ?></th>
					<td>
						<?php echo FormFieldHelper::text_input( 'modpress_general[root_slug]', $root_slug, [ 'id' => 'modpress-root-slug' ] ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo FormFieldHelper::label( 'modpress-category-slug', __( 'Custom Category Slug', 'modpress' ), [
						'description' => __( 'The URL slug used for ModPress categories.', 'modpress' ),
						'tooltip' => __( 'Changing this value flushes the WordPress rewrite rules.', 'modpress' ),
						'tooltip_type' => 'info',
					] ); ?></th>
					<td>
						<?php echo FormFieldHelper::text_input( 'modpress_general[category_slug]', $category_slug, [ 'id' => 'modpress-category-slug' ] ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo FormFieldHelper::label( 'modpress-tag-slug', __( 'Custom Tags Slug', 'modpress' ), [
						'description' => __( 'The URL slug used for ModPress tags.', 'modpress' ),
						'tooltip' => __( 'Changing this value flushes the WordPress rewrite rules.', 'modpress' ),
						'tooltip_type' => 'info',
					] ); ?></th>
					<td>
						<?php echo FormFieldHelper::text_input( 'modpress_general[tag_slug]', $tag_slug, [ 'id' => 'modpress-tag-slug' ] ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo FormFieldHelper::label( 'modpress-permalink', __( 'ModPress Permalink', 'modpress' ), [
						'description' => __( 'The permalink structure used by ModPress content.', 'modpress' ),
						'tooltip' => __( 'Choose a structure that remains readable and stable after publication.', 'modpress' ),
					] ); ?></th>
					<td>
						<?php echo FormFieldHelper::text_input( 'modpress_general[permalink]', $permalink, [ 'id' => 'modpress-permalink', 'data-permalink-field' => 'permalink' ] ); ?>
						<div class="modpress-permalink-tokens mt-2" aria-label="<?php echo esc_attr__( 'Available permalink tokens', 'modpress' ); ?>">
							<?php foreach ( PermalinkHelper::token_definitions() as $token => $description ) : ?>
								<?php echo FormFieldHelper::button(
									$token,
									[
										'class' => 'btn-sm btn-outline-secondary me-1 mb-1',
										'type' => 'button',
										'attributes' => [
											'data-permalink-token' => $token,
											'title' => $description,
										],
									]
								); ?>
							<?php endforeach; ?>
						</div>
						<div class="form-text"><?php echo esc_html__( 'Click a token to add it to the pattern. Tokens are inserted with a trailing slash and reappear when removed.', 'modpress' ); ?></div>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo FormFieldHelper::label( 'modpress-enable-schema', __( 'Enable ModPress Schema', 'modpress' ), [
						'description' => __( 'Allow themes and integrations to expose ModPress metadata.', 'modpress' ),
						'tooltip' => __( 'Keep this enabled when search engines and integrations should understand the ModPress structure.', 'modpress' ),
					] ); ?></th>
					<td>
						<?php echo FormFieldHelper::checkbox( 'modpress_general[enable_schema]', '1', __( 'Enable ModPress Schema', 'modpress' ), [ 'id' => 'modpress-enable-schema', 'checked' => $enable_schema ] ); ?>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}
}
