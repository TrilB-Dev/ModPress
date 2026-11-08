<?php
/**
 * Changelog screen for the ModPress mod manager.
 *
 * @package ModPress
 * @subpackage Admin\Manager\Mods
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\Mods;

use ModPress\Includes\Functions\Admin\FunctionsMod;
use ModPress\Includes\Functions\Helpers\AlertHelper;
use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\ModManagement\ChangeLogManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ModChangeLogs extends ModManager {
	/**
	 * Render the changelog manager page with changelog and category tabs.
	 *
	 * @return void
	 */
	public function render_page_content(): void {
		$flash_notice = FunctionsMod::consume_admin_notice_flash();
		if ( ! empty( $flash_notice['message'] ) ) {
			AlertHelper::render_admin_notice( $flash_notice['message'], $flash_notice['type'] );
		}

		$tabs = array(
			array(
				'key'       => 'changelogs',
				'label'     => __( 'Changelog Entries', 'modpress' ),
				'post_type' => $this->resolve_post_type( 'modpress_changelog', 'modpress_changelog' ),
			),
			array(
				'key'      => 'categories',
				'label'    => __( 'Categories', 'modpress' ),
				'taxonomy' => $this->resolve_taxonomy( 'modpress_changelog_category', 'modpress_changelog_category' ),
			),
		);

		$active_tab = sanitize_key( wp_unslash( $_GET['changelog_tab'] ?? 'changelogs' ) );
		$tab_keys   = array_map(
			static function ( array $tab ): string {
				return (string) ( $tab['key'] ?? '' );
			},
			$tabs
		);

		if ( ! in_array( $active_tab, $tab_keys, true ) ) {
			$active_tab = 'changelogs';
		}
		?>
		<div class="modpress-changelog-page">
			<div class="card border-0 shadow-sm mb-4">
				<div class="card-body p-4">
					<div class="d-flex align-items-center justify-content-between gap-3 mb-4">
						<h2 class="h5 mb-0"><?php esc_html_e( 'Mod Changelogs', 'modpress' ); ?></h2>
					</div>

					<ul class="nav nav-tabs" id="modpress-changelog-tabs" role="tablist">
						<?php foreach ( $tabs as $tab ) : ?>
							<?php $tab_key = (string) ( $tab['key'] ?? '' ); ?>
							<li class="nav-item" role="presentation">
								<button
									type="button"
									class="nav-link <?php echo $active_tab === $tab_key ? 'active' : ''; ?>"
									id="modpress-changelog-tab-<?php echo esc_attr( $tab_key ); ?>"
									data-bs-toggle="tab"
									data-bs-target="#modpress-changelog-pane-<?php echo esc_attr( $tab_key ); ?>"
									role="tab"
									aria-controls="modpress-changelog-pane-<?php echo esc_attr( $tab_key ); ?>"
									aria-selected="<?php echo $active_tab === $tab_key ? 'true' : 'false'; ?>"
								>
									<?php echo esc_html( $tab['label'] ?? ucfirst( str_replace( array( '-', '_' ), ' ', $tab_key ) ) ); ?>
								</button>
							</li>
						<?php endforeach; ?>
					</ul>

					<div class="tab-content mt-4" id="modpress-changelog-tab-content">
						<?php foreach ( $tabs as $tab ) : ?>
							<?php $tab_key = (string) ( $tab['key'] ?? '' ); ?>
							<div
								class="tab-pane fade <?php echo $active_tab === $tab_key ? 'show active' : ''; ?>"
								id="modpress-changelog-pane-<?php echo esc_attr( $tab_key ); ?>"
								role="tabpanel"
								aria-labelledby="modpress-changelog-tab-<?php echo esc_attr( $tab_key ); ?>"
							>
								<?php if ( 'changelogs' === $tab_key ) : ?>
									<?php $this->render_changelog_entries_tab( (string) ( $tab['post_type'] ?? 'modpress_changelog' ) ); ?>
								<?php elseif ( 'categories' === $tab_key ) : ?>
									<?php $this->render_changelog_categories_tab( (string) ( $tab['taxonomy'] ?? 'modpress_changelog_category' ) ); ?>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the changelog entries tab.
	 *
	 * @param string $post_type Changelog post type slug.
	 * @return void
	 */
	private function render_changelog_entries_tab( string $post_type ): void {
		$entries = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => 25,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		?>
		<div class="d-flex align-items-center justify-content-between gap-3 mb-3">
			<div class="text-muted small">
				<?php echo esc_html( sprintf( _n( '%s changelog entry', '%s changelog entries', count( $entries ), 'modpress' ), number_format_i18n( count( $entries ) ) ) ); ?>
			</div>
			<div class="btn-group btn-group-sm" role="group" aria-label="<?php esc_attr_e( 'Changelog actions', 'modpress' ); ?>">
				<?php echo FormFieldHelper::button(
					'+' . ' ' . __( 'Add New Changelog', 'modpress' ),
					array(
						'type' => 'button',
						'class' => 'btn btn-outline-primary',
						'raw' => true,
						'attributes' => array(
							'data-bs-toggle' => 'modal',
							'data-bs-target' => '#modpress-changelog-modal',
						),
					)
				); ?>
			</div>
		</div>

		<div class="table-responsive">
			<table class="table align-middle mb-0">
				<thead>
					<tr>
						<th style="width: 42px;">
							<input type="checkbox" class="form-check-input modpress-select-all" data-taxonomy="modpress_changelog" aria-label="<?php esc_attr_e( 'Select all entries', 'modpress' ); ?>" />
						</th>
						<th><?php esc_html_e( 'Version', 'modpress' ); ?></th>
						<th><?php esc_html_e( 'Date', 'modpress' ); ?></th>
						<th><?php esc_html_e( 'Category', 'modpress' ); ?></th>
						<th class="text-end"><?php esc_html_e( 'Actions', 'modpress' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $entries ) ) : ?>
						<tr>
							<td colspan="5" class="text-center text-muted py-4"><?php esc_html_e( 'No changelog entries found.', 'modpress' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $entries as $entry ) : ?>
							<?php
								$term_names = array();
								if ( function_exists( 'wp_get_post_terms' ) ) {
									$terms = wp_get_post_terms( (int) $entry->ID, 'modpress_changelog_category', array( 'fields' => 'names' ) );
									if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
										$term_names = array_map( 'strval', $terms );
									}
								}
							?>
							<tr>
								<td>
									<input type="checkbox" class="form-check-input modpress-row-checkbox" name="changelog_ids[]" value="<?php echo esc_attr( (string) $entry->ID ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Select %s', 'modpress' ), $entry->post_title ) ); ?>" />
								</td>
								<td><?php echo esc_html( $entry->post_title ); ?></td>
								<td><?php
									$date_display = function_exists( 'get_the_date' ) ? get_the_date( '', $entry ) : ( isset( $entry->post_date ) ? gmdate( 'Y-m-d', strtotime( $entry->post_date ) ) : '' );
									echo esc_html( $date_display ?: __( '—', 'modpress' ) );
								?></td>
								<td><?php echo esc_html( ! empty( $term_names ) ? implode( ', ', $term_names ) : __( 'Uncategorized', 'modpress' ) ); ?></td>
								<td class="text-end">
									<div class="btn-group btn-group-sm" role="group" aria-label="<?php esc_attr_e( 'Changelog actions', 'modpress' ); ?>">
										<?php echo FormFieldHelper::button(
											'✎',
											array(
												'type' => 'button',
												'class' => 'btn btn-outline-secondary',
												'raw' => true,
												'attributes' => array(
													'data-bs-toggle' => 'modal',
													'data-bs-target' => '#modpress-changelog-modal',
													'data-modpress-changelog-id' => (string) $entry->ID,
													'data-modpress-changelog-version' => $entry->post_title ?? '',
													'data-modpress-changelog-date' => function_exists( 'get_the_date' ) ? get_the_date( 'Y-m-d', $entry ) : ( isset( $entry->post_date ) ? gmdate( 'Y-m-d', strtotime( $entry->post_date ) ) : '' ),
													'data-modpress-changelog-notes' => $entry->post_content ?? '',
													'data-modpress-changelog-category' => $this->get_first_term_id( $entry->ID, 'modpress_changelog_category' ),
												),
											)
										); ?>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="d-inline" aria-label="<?php esc_attr_e( 'Delete changelog entry', 'modpress' ); ?>">
											<input type="hidden" name="action" value="modpress_delete_changelog" />
											<input type="hidden" name="changelog_ids[]" value="<?php echo esc_attr( (string) $entry->ID ); ?>" />
											<?php wp_nonce_field( 'modpress_delete_changelog', 'modpress_changelog_nonce' ); ?>
											<?php echo FormFieldHelper::button(
												'🗑',
												array(
													'type' => 'submit',
													'class' => 'btn btn-outline-danger btn-sm',
													'raw' => true,
													'attributes' => array(
														'aria-label' => sprintf( __( 'Delete %s', 'modpress' ), $entry->post_title ),
														'onclick' => 'return window.confirm(\'Delete this changelog entry?\');',
													),
												)
											); ?>
										</form>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<div class="modal fade" id="modpress-changelog-modal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-centered">
				<div class="modal-content">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="modpress_save_changelog" />
						<?php wp_nonce_field( 'modpress_save_changelog', 'modpress_changelog_nonce' ); ?>
						<div class="modal-header">
							<h3 class="modal-title h5"><?php esc_html_e( 'Add Changelog Entry', 'modpress' ); ?></h3>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'modpress' ); ?>"></button>
						</div>
						<div class="modal-body">
							<input type="hidden" name="modpress-changelog-id" id="modpress-changelog-id" value="0" />
							<div class="row g-3">
								<div class="col-md-6">
									<label class="form-label" for="modpress-changelog-version"><?php esc_html_e( 'Version', 'modpress' ); ?></label>
									<?php echo FormFieldHelper::text_input( 'modpress-changelog-version', '', array( 'id' => 'modpress-changelog-version', 'placeholder' => '1.4.0' ) ); ?>
								</div>
								<div class="col-md-6">
									<label class="form-label" for="modpress-changelog-date"><?php esc_html_e( 'Date', 'modpress' ); ?></label>
									<?php echo FormFieldHelper::input( 'modpress-changelog-date', '', array( 'type' => 'date', 'id' => 'modpress-changelog-date' ) ); ?>
								</div>
								<div class="col-12">
									<label class="form-label" for="modpress-changelog-category"><?php esc_html_e( 'Category', 'modpress' ); ?></label>
									<?php
									$category_terms = get_terms(
										array(
											'taxonomy'   => 'modpress_changelog_category',
											'hide_empty' => false,
											'orderby'    => 'name',
											'order'      => 'ASC',
										)
									);
									if ( is_wp_error( $category_terms ) ) {
										$category_terms = array();
									}
									$category_options = array( '' => __( 'Uncategorized', 'modpress' ) );
									foreach ( $category_terms as $term ) {
										$category_options[ (string) $term->term_id ] = $term->name;
									}
									echo FormFieldHelper::select( 'modpress-changelog-category', $category_options, array(), array( 'id' => 'modpress-changelog-category', 'class' => 'form-select' ) );
									?>
								</div>
								<div class="col-12">
									<label class="form-label" for="modpress-changelog-notes"><?php esc_html_e( 'Notes', 'modpress' ); ?></label>
									<?php echo FormFieldHelper::textarea( 'modpress-changelog-notes', '', array( 'id' => 'modpress-changelog-notes', 'rows' => 6, 'placeholder' => __( 'Add the release notes for this version.', 'modpress' ) ) ); ?>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<?php echo FormFieldHelper::button( __( 'Cancel', 'modpress' ), array( 'type' => 'button', 'class' => 'btn btn-outline-secondary', 'attributes' => array( 'data-bs-dismiss' => 'modal' ) ) ); ?>
							<?php echo FormFieldHelper::button( __( 'Save Changelog', 'modpress' ), array( 'type' => 'submit', 'class' => 'btn btn-primary' ) ); ?>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the changelog category taxonomy tab.
	 *
	 * @param string $taxonomy Changelog category taxonomy slug.
	 * @return void
	 */
	private function render_changelog_categories_tab( string $taxonomy ): void {
		if ( '' === $taxonomy ) {
			return;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);
		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}
		?>
		<div class="d-flex align-items-center justify-content-between gap-3 mb-3">
			<div class="text-muted small"><?php echo esc_html( sprintf( _n( '%s category', '%s categories', count( $terms ), 'modpress' ), number_format_i18n( count( $terms ) ) ) ); ?></div>
			<div class="btn-group btn-group-sm" role="group" aria-label="<?php esc_attr_e( 'Category actions', 'modpress' ); ?>">
				<?php echo FormFieldHelper::button(
					'+' . ' ' . __( 'Add New Category', 'modpress' ),
					array(
						'type' => 'button',
						'class' => 'btn btn-outline-primary',
						'raw' => true,
						'attributes' => array(
							'data-bs-toggle' => 'modal',
							'data-bs-target' => '#modpress-changelog-categories-modal',
							'data-modpress-taxonomy-reset' => 'changelog_category',
						),
					)
				); ?>
			</div>
		</div>

		<div class="table-responsive">
			<table class="table align-middle mb-0">
				<thead>
					<tr>
						<th style="width: 42px;">
							<input type="checkbox" class="form-check-input modpress-select-all" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" aria-label="<?php esc_attr_e( 'Select all categories', 'modpress' ); ?>" />
						</th>
						<th><?php esc_html_e( 'Name', 'modpress' ); ?></th>
						<th><?php esc_html_e( 'Slug', 'modpress' ); ?></th>
						<th><?php esc_html_e( 'Description', 'modpress' ); ?></th>
						<th class="text-end"><?php esc_html_e( 'Actions', 'modpress' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $terms ) ) : ?>
						<tr>
							<td colspan="5" class="text-center text-muted py-4"><?php esc_html_e( 'No categories found.', 'modpress' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $terms as $term ) : ?>
							<tr>
								<td>
									<input type="checkbox" class="form-check-input modpress-row-checkbox" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" name="changelog_category_ids[]" value="<?php echo esc_attr( (string) $term->term_id ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Select %s', 'modpress' ), $term->name ) ); ?>" />
								</td>
								<td><?php echo esc_html( $term->name ); ?></td>
								<td><?php echo esc_html( $term->slug ); ?></td>
								<td><?php echo esc_html( $term->description ?: __( 'No description', 'modpress' ) ); ?></td>
								<td class="text-end">
									<div class="btn-group btn-group-sm" role="group" aria-label="<?php esc_attr_e( 'Category actions', 'modpress' ); ?>">
										<?php echo FormFieldHelper::button(
											'✎',
											array(
												'type' => 'button',
												'class' => 'btn btn-outline-secondary',
												'raw' => true,
												'attributes' => array(
													'data-bs-toggle' => 'modal',
													'data-bs-target' => '#modpress-changelog-categories-modal',
													'data-modpress-taxonomy-edit' => 'changelog_category',
													'data-term-id' => (string) $term->term_id,
													'data-term-name' => $term->name,
													'data-term-slug' => $term->slug,
													'data-term-description' => $term->description,
												),
											)
										); ?>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="d-inline" aria-label="<?php esc_attr_e( 'Delete changelog category', 'modpress' ); ?>">
											<input type="hidden" name="action" value="modpress_delete_changelog_category" />
											<input type="hidden" name="changelog_category_slug" value="<?php echo esc_attr( $taxonomy ); ?>" />
											<input type="hidden" name="changelog_category_ids[]" value="<?php echo esc_attr( (string) $term->term_id ); ?>" />
											<?php wp_nonce_field( 'modpress_delete_changelog_category', 'modpress_changelog_category_nonce' ); ?>
											<?php echo FormFieldHelper::button(
												'🗑',
												array(
													'type' => 'submit',
													'class' => 'btn btn-outline-danger btn-sm',
													'raw' => true,
													'attributes' => array(
														'aria-label' => sprintf( __( 'Delete %s', 'modpress' ), $term->name ),
														'onclick' => 'return window.confirm(\'Delete this category?\');',
													),
												)
											); ?>
										</form>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<div class="modal fade" id="modpress-changelog-categories-modal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-centered">
				<div class="modal-content">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="modpress_save_changelog_category" />
						<input type="hidden" name="changelog_category_slug" value="<?php echo esc_attr( $taxonomy ); ?>" />
						<?php echo FormFieldHelper::input( 'modpress-changelog-category-term-id', '', array( 'type' => 'hidden', 'id' => 'modpress-changelog-category-term-id' ) ); ?>
						<?php wp_nonce_field( 'modpress_save_changelog_category', 'modpress_changelog_category_nonce' ); ?>
						<div class="modal-header">
							<h3 class="modal-title h5"><?php esc_html_e( 'Add Changelog Category', 'modpress' ); ?></h3>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'modpress' ); ?>"></button>
						</div>
						<div class="modal-body">
							<div class="row g-3">
								<div class="col-md-6">
									<label class="form-label" for="modpress-changelog-category-name"><?php esc_html_e( 'Name', 'modpress' ); ?></label>
									<?php echo FormFieldHelper::text_input( 'modpress-changelog-category-name', '', array( 'id' => 'modpress-changelog-category-name', 'placeholder' => __( 'Bug Fixes', 'modpress' ) ) ); ?>
								</div>
								<div class="col-md-6">
									<label class="form-label" for="modpress-changelog-category-slug"><?php esc_html_e( 'Slug', 'modpress' ); ?></label>
									<?php echo FormFieldHelper::text_input( 'modpress-changelog-category-slug', '', array( 'id' => 'modpress-changelog-category-slug', 'placeholder' => __( 'bug-fixes', 'modpress' ) ) ); ?>
								</div>
								<div class="col-12">
									<label class="form-label" for="modpress-changelog-category-description"><?php esc_html_e( 'Description', 'modpress' ); ?></label>
									<?php echo FormFieldHelper::textarea( 'modpress-changelog-category-description', '', array( 'id' => 'modpress-changelog-category-description', 'rows' => 4, 'placeholder' => __( 'Optional category description', 'modpress' ) ) ); ?>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<?php echo FormFieldHelper::button( __( 'Cancel', 'modpress' ), array( 'type' => 'button', 'class' => 'btn btn-outline-secondary', 'attributes' => array( 'data-bs-dismiss' => 'modal' ) ) ); ?>
							<?php echo FormFieldHelper::button( __( 'Save Category', 'modpress' ), array( 'type' => 'submit', 'class' => 'btn btn-primary' ) ); ?>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Resolve the preferred post type when a custom post type is available.
	 *
	 * @param string $preferred Preferred post type slug.
	 * @param string $fallback Fallback slug.
	 * @return string
	 */
	private function resolve_post_type( string $preferred, string $fallback ): string {
		if ( post_type_exists( $preferred ) ) {
			return $preferred;
		}

		if ( post_type_exists( $fallback ) ) {
			return $fallback;
		}

		return $preferred;
	}

	/**
	 * Resolve the preferred taxonomy when a custom taxonomy is available.
	 *
	 * @param string $preferred Preferred taxonomy slug.
	 * @param string $fallback Fallback slug.
	 * @return string
	 */
	private function resolve_taxonomy( string $preferred, string $fallback ): string {
		if ( taxonomy_exists( $preferred ) ) {
			return $preferred;
		}

		if ( taxonomy_exists( $fallback ) ) {
			return $fallback;
		}

		return $preferred;
	}

	/**
	 * Get the first term id for a given object and taxonomy.
	 *
	 * @param int    $object_id Object id.
	 * @param string $taxonomy Taxonomy slug.
	 * @return string
	 */
	private function get_first_term_id( int $object_id, string $taxonomy ): string {
		if ( ! function_exists( 'wp_get_post_terms' ) ) {
			return '';
		}

		$terms = wp_get_post_terms( $object_id, $taxonomy, array( 'fields' => 'ids' ) );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		return (string) absint( $terms[0] );
	}
}
