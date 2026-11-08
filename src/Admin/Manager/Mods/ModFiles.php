<?php
/**
 * Files screen for the ModPress mod manager.
 *
 * @package ModPress
 * @subpackage Admin\Manager\Mods
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\Mods;

use ModPress\Includes\Functions\Admin\FunctionsMod;
use ModPress\Includes\Functions\Helpers\AlertHelper;
use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\Functions\Helpers\WPMediaHelper;
use ModPress\Includes\ModManagement\FileManager;
use ModPress\Includes\Plugins\FontAwesome\Includes\Functions\Helpers\FAHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ModFiles extends ModManager {
	/**
	 * Render the files manager page with tabbed file, file type, and file host views.
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
				'key'       => 'files',
				'label'     => __( 'Files', 'modpress' ),
				'post_type' => $this->resolve_post_type( 'modpress_files', 'modpress_file' ),
			),
			array(
				'key'      => 'file-types',
				'label'    => __( 'File Types', 'modpress' ),
				'taxonomy' => $this->resolve_taxonomy( 'modpress_file_types', 'modpress_file_type' ),
			),
			array(
				'key'      => 'file-hosts',
				'label'    => __( 'File Hosts', 'modpress' ),
				'taxonomy' => $this->resolve_taxonomy( 'modpress_file_hosts', 'modpress_file_hosts' ),
			),
		);

		$active_tab = sanitize_key( wp_unslash( $_GET['files_tab'] ?? 'files' ) );
		$tab_keys   = array_map(
			static function ( array $tab ): string {
				return (string) ( $tab['key'] ?? '' );
			},
			$tabs
		);

		if ( ! in_array( $active_tab, $tab_keys, true ) ) {
			$active_tab = 'files';
		}
		?>
		<div class="modpress-files-page">
			<div class="card border-0 shadow-sm mb-4">
				<div class="card-body p-4">
					<div class="d-flex align-items-center justify-content-between gap-3 mb-4">
						<h2 class="h5 mb-0"><?php esc_html_e( 'Mod Files', 'modpress' ); ?></h2>
					</div>

					<ul class="nav nav-tabs" id="modpress-files-tabs" role="tablist">
						<?php foreach ( $tabs as $index => $tab ) : ?>
							<?php $tab_key = (string) ( $tab['key'] ?? '' ); ?>
							<li class="nav-item" role="presentation">
								<button
									type="button"
									class="nav-link <?php echo $active_tab === $tab_key ? 'active' : ''; ?>"
									id="modpress-file-tab-<?php echo esc_attr( $tab_key ); ?>"
									data-bs-toggle="tab"
									data-bs-target="#modpress-file-pane-<?php echo esc_attr( $tab_key ); ?>"
									role="tab"
									aria-controls="modpress-file-pane-<?php echo esc_attr( $tab_key ); ?>"
									aria-selected="<?php echo $active_tab === $tab_key ? 'true' : 'false'; ?>"
								>
									<?php echo esc_html( $tab['label'] ?? ucfirst( str_replace( array( '-', '_' ), ' ', $tab_key ) ) ); ?>
								</button>
							</li>
						<?php endforeach; ?>
					</ul>

					<div class="tab-content mt-4" id="modpress-file-tab-content">
						<?php foreach ( $tabs as $tab ) : ?>
							<?php $tab_key = (string) ( $tab['key'] ?? '' ); ?>
							<div
								class="tab-pane fade <?php echo $active_tab === $tab_key ? 'show active' : ''; ?>"
								id="modpress-file-pane-<?php echo esc_attr( $tab_key ); ?>"
								role="tabpanel"
								aria-labelledby="modpress-file-tab-<?php echo esc_attr( $tab_key ); ?>"
							>
								<?php if ( 'files' === $tab_key ) : ?>
									<?php $this->render_files_tab( (string) ( $tab['post_type'] ?? 'modpress_file' ) ); ?>
								<?php elseif ( 'file-types' === $tab_key ) : ?>
									<?php $this->render_file_types_tab( (string) ( $tab['taxonomy'] ?? '' ) ); ?>
								<?php elseif ( 'file-hosts' === $tab_key ) : ?>
									<?php $this->render_file_hosts_tab( (string) ( $tab['taxonomy'] ?? '' ) ); ?>
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
	 * Render the files table for the selected mod entries.
	 *
	 * @param string $post_type Post type slug.
	 * @return void
	 */
	private function render_files_tab( string $post_type ): void {
		$file_service = new FileManager();
		$entries      = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => 25,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		?>
		<div class="d-flex align-items-center justify-content-between gap-3 mb-3">
			<div class="text-muted small">
				<?php echo esc_html( sprintf( _n( '%s file', '%s files', count( $entries ), 'modpress' ), number_format_i18n( count( $entries ) ) ) ); ?>
			</div>
			<div class="btn-group btn-group-sm" role="group" aria-label="<?php esc_attr_e( 'File actions', 'modpress' ); ?>">
				<?php echo FormFieldHelper::button(
					FAHelper::faicon( 'plus' ) . ' ' . __( 'Add New File', 'modpress' ),
					array(
						'type' => 'button',
						'class' => 'btn btn-outline-primary',
						'raw' => true,
						'attributes' => array(
							'data-bs-toggle' => 'modal',
							'data-bs-target' => '#modpress-file-modal',
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
							<input type="checkbox" class="form-check-input" aria-label="<?php esc_attr_e( 'Select all files', 'modpress' ); ?>" />
						</th>
						<th><?php esc_html_e( 'Name', 'modpress' ); ?></th>
						<th><?php esc_html_e( 'Mod', 'modpress' ); ?></th>
						<th><?php esc_html_e( 'Files', 'modpress' ); ?></th>
						<th class="text-end"><?php esc_html_e( 'Actions', 'modpress' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $entries ) ) : ?>
						<tr>
							<td colspan="5" class="text-center text-muted py-4"><?php esc_html_e( 'No files found.', 'modpress' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $entries as $entry ) : ?>
							<?php $files = $file_service->get_files( (int) $entry->ID ); ?>
							<tr>
								<td>
									<input type="checkbox" class="form-check-input modpress-row-checkbox" name="group_entry_ids[]" value="<?php echo esc_attr( (string) $entry->ID ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Select %s', 'modpress' ), $entry->post_title ) ); ?>" />
								</td>
								<td><?php echo esc_html( $entry->post_title ); ?></td>
								<td><?php echo esc_html( get_the_title( (int) $entry->post_parent ) ?: __( 'Unassigned', 'modpress' ) ); ?></td>
								<td><?php echo esc_html( count( $files ) ); ?></td>
								<td class="text-end">
									<div class="btn-group btn-group-sm" role="group" aria-label="<?php esc_attr_e( 'File actions', 'modpress' ); ?>">
										<?php echo FormFieldHelper::button(
											FAHelper::faicon( 'pen-to-square' ),
											array(
												'type' => 'button',
												'class' => 'btn btn-outline-secondary',
												'raw' => true,
												'attributes' => array(
													'data-bs-toggle' => 'modal',
													'data-bs-target' => '#modpress-file-modal',
												),
											)
										); ?>
										<a href="<?php echo esc_url( admin_url( 'post.php?post=' . (int) $entry->ID . '&action=edit' ) ); ?>" class="btn btn-outline-secondary btn-sm" aria-label="<?php echo esc_attr( sprintf( __( 'Edit %s', 'modpress' ), $entry->post_title ) ); ?>"><?php echo FAHelper::faicon( 'pencil' ); ?></a>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<div class="modal fade" id="modpress-file-modal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-centered">
				<div class="modal-content">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="modpress_save_files" />
						<?php wp_nonce_field( 'modpress_save_files', 'modpress_files_nonce' ); ?>
						<div class="modal-header">
							<h3 class="modal-title h5"><?php esc_html_e( 'Add File', 'modpress' ); ?></h3>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'modpress' ); ?>"></button>
						</div>
						<div class="modal-body">
							<div class="row g-3">
								<div class="col-md-6">
									<label class="form-label" for="modpress-file-name"><?php esc_html_e( 'Name', 'modpress' ); ?></label>
									<?php echo FormFieldHelper::text_input( 'file_name', '', array( 'id' => 'modpress-file-name', 'placeholder' => __( 'File name', 'modpress' ) ) ); ?>
								</div>
								<div class="col-md-6">
									<label class="form-label" for="modpress-file-version"><?php esc_html_e( 'Version', 'modpress' ); ?></label>
									<?php echo FormFieldHelper::text_input( 'file_version', '', array( 'id' => 'modpress-file-version', 'placeholder' => '1.0.0' ) ); ?>
								</div>
								<div class="col-12">
									<label class="form-label" for="modpress-file-url"><?php esc_html_e( 'Download URL', 'modpress' ); ?></label>
									<?php echo FormFieldHelper::text_input( 'file_url', '', array( 'type' => 'url', 'id' => 'modpress-file-url', 'placeholder' => 'https://' ) ); ?>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<?php echo FormFieldHelper::button( __( 'Save File', 'modpress' ), array( 'type' => 'submit', 'class' => 'btn btn-primary' ) ); ?>
							<?php echo FormFieldHelper::button( __( 'Cancel', 'modpress' ), array( 'type' => 'button', 'class' => 'btn btn-outline-secondary', 'attributes' => array( 'data-bs-dismiss' => 'modal' ) ) ); ?>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the file type taxonomy tab.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return void
	 */
	private function render_file_types_tab( string $taxonomy ): void {
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
			<div class="text-muted small"><?php echo esc_html( sprintf( _n( '%s entry', '%s entries', count( $terms ), 'modpress' ), number_format_i18n( count( $terms ) ) ) ); ?></div>
			<div class="btn-group btn-group-sm" role="group" aria-label="<?php esc_attr_e( 'Taxonomy actions', 'modpress' ); ?>">
				<?php echo FormFieldHelper::button(
					FAHelper::faicon( 'plus' ) . ' ' . __( 'Add New Type', 'modpress' ),
					array(
						'type' => 'button',
						'class' => 'btn btn-outline-primary',
						'raw' => true,
						'attributes' => array(
							'data-bs-toggle' => 'modal',
							'data-bs-target' => '#modpress-file-types-modal',
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
							<input type="checkbox" class="form-check-input modpress-select-all" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" aria-label="<?php esc_attr_e( 'Select all entries', 'modpress' ); ?>" />
						</th>
						<th><?php esc_html_e( 'Image', 'modpress' ); ?></th>
						<th><?php esc_html_e( 'Name', 'modpress' ); ?></th>
						<th><?php esc_html_e( 'Slug', 'modpress' ); ?></th>
						<th class="text-end"><?php esc_html_e( 'Actions', 'modpress' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $terms ) ) : ?>
						<tr>
							<td colspan="5" class="text-center text-muted py-4"><?php esc_html_e( 'No items found for this taxonomy yet.', 'modpress' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $terms as $term ) : ?>
							<?php $image_url = get_term_meta( (int) $term->term_id, 'image', true ); ?>
							<tr>
								<td>
									<input type="checkbox" class="form-check-input modpress-row-checkbox" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" name="file_type_ids[]" value="<?php echo esc_attr( (string) $term->term_id ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Select %s', 'modpress' ), $term->name ) ); ?>" />
								</td>
								<td>
									<?php if ( ! empty( $image_url ) ) : ?>
										<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $term->name ); ?>" style="max-width: 32px; max-height: 32px; object-fit: cover; border-radius: 4px;" />
									<?php else : ?>
										<span class="text-muted"><?php esc_html_e( 'None', 'modpress' ); ?></span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $term->name ); ?></td>
								<td><?php echo esc_html( $term->slug ); ?></td>
								<td class="text-end">
									<div class="btn-group btn-group-sm" role="group" aria-label="<?php esc_attr_e( 'Entry actions', 'modpress' ); ?>">
										<?php echo FormFieldHelper::button(
											FAHelper::faicon( 'pen-to-square' ),
											array(
												'type' => 'button',
												'class' => 'btn btn-outline-secondary',
												'raw' => true,
												'attributes' => array(
													'data-bs-toggle' => 'modal',
													'data-bs-target' => '#modpress-file-types-modal',
												),
											)
										); ?>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="d-inline-block">
											<input type="hidden" name="action" value="modpress_delete_file_type" />
											<input type="hidden" name="file_type_slug" value="<?php echo esc_attr( $taxonomy ); ?>" />
											<input type="hidden" name="file_type_ids[]" value="<?php echo esc_attr( (string) $term->term_id ); ?>" />
											<?php wp_nonce_field( 'modpress_delete_file_type', 'modpress_file_type_nonce' ); ?>
											<?php echo FormFieldHelper::button(
												FAHelper::faicon( 'trash' ),
												array(
													'type' => 'submit',
													'class' => 'btn btn-outline-danger',
													'raw' => true,
													'attributes' => array(
														'aria-label' => sprintf( __( 'Delete %s', 'modpress' ), $term->name ),
														'onclick' => 'return window.confirm(\'Delete this entry?\');',
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

		<div class="modal fade" id="modpress-file-types-modal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-centered">
				<div class="modal-content">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="modpress_save_file_type" />
						<input type="hidden" name="file_type_slug" value="<?php echo esc_attr( $taxonomy ); ?>" />
						<?php wp_nonce_field( 'modpress_save_file_type', 'modpress_file_type_nonce' ); ?>
						<div class="modal-header">
							<h3 class="modal-title h5"><?php esc_html_e( 'Add File Type', 'modpress' ); ?></h3>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'modpress' ); ?>"></button>
						</div>
						<div class="modal-body">
							<table class="table table-borderless mb-0">
								<tbody>
									<tr>
										<th scope="row" class="align-top text-nowrap pe-4">
											<?php echo FormFieldHelper::label(
												'modpress-file-types-name',
												__( 'File Type Uppercase', 'modpress' ),
												array(
													'description' => __( 'Enter the file type extension. e.g., "jpg", "png".', 'modpress' ),
													'tooltip'     => __( 'File type names are can be an uppercase version of file extensions.', 'modpress' ),
												)
										); ?>
										</th>
										<td>
											<?php echo FormFieldHelper::text_input( 
												'modpress-file-types-name', 
												'', 
												array( 
													'id' => 'modpress-file-types-name', 
													'placeholder' => __( 'PNG', 'modpress' ),
													'class' => 'text-uppercase w-100'
												) 
											); ?>
										</td>
									</tr>
									<tr>
										<th scope="row" class="align-top text-nowrap pe-4">
											<?php echo FormFieldHelper::label(
												'modpress-file-types-slug',
												__( 'File Type Lowercase', 'modpress' ),
												array(
													'description' => __( 'Enter the lowercase file type extension. e.g., "jpg", "png".', 'modpress' ),
													'tooltip'     => __( 'File type slugs are generally lowercase versions of file extensions.', 'modpress' ),
												)
										); ?>
										</th>
										<td>
											<?php echo FormFieldHelper::text_input( 
												'modpress-file-types-slug', 
												'', 
												array( 
													'id' => 'modpress-file-types-slug', 
													'placeholder' => __( 'png', 'modpress' ),
													'class' => 'text-lowercase w-100'
												) 
											); ?>
										</td>
									</tr>
									<tr>
										<th scope="row" class="align-top text-nowrap pe-4">
											<?php echo FormFieldHelper::label(
												'modpress-file-types-description',
												__( 'Description', 'modpress' ),
												array(
													'description' => __( 'Provide a description for the file type.', 'modpress' ),
													'tooltip'     => __( 'Provide a brief description for the file type, explaining which programs or contexts it is used in.', 'modpress' ),
												)
										); ?>
										</th>
										<td>
											<?php echo FormFieldHelper::textarea( 
												'modpress-file-types-description', 
												'', 
												array( 
													'id' => 'modpress-file-types-description', 
													'rows' => 4, 
													'placeholder' => __( 'Optional description', 'modpress' ),
													'class' => 'w-100'
												) 
											); ?>
										</td>
									</tr>
									<tr>
										<th scope="row" class="align-top text-nowrap pe-4">
											<?php echo FormFieldHelper::label(
												'modpress-file-types-image',
												__( 'Image', 'modpress' ),
												array(
													'description' => __( 'Please provide an image to represent the file type.', 'modpress' ),
													'tooltip'     => __( 'By providing an image, you help visually identify the file type.', 'modpress' ),
												)
										); ?>
										</th>
										<td>
											<?php echo WPMediaHelper::create(
												'',
												array(
													'name' => 'modpress-file-types-image',
													'id' => 'modpress-file-types-image',
													'button_text' => __( 'Choose Image', 'modpress' ),
													'show_remove' => true,
												),
												array(
													'alt' => __( 'File type image', 'modpress' ),
												)
											); ?>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div class="modal-footer">
							<?php echo FormFieldHelper::button( 
								__( 'Save', 'modpress' ), 
								array( 
									'type' => 'submit', 
									'class' => 'btn btn-primary' 
								) 
							); ?>
							<?php echo FormFieldHelper::button( 
								__( 'Cancel', 'modpress' ), 
								array( 
									'type' => 'button', 
									'class' => 'btn btn-outline-secondary', 
									'attributes' => array( 
										'data-bs-dismiss' => 'modal' 
									) 
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
	 * Render the file host taxonomy tab.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return void
	 */
	private function render_file_hosts_tab( string $taxonomy ): void {
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
			<div class="text-muted small"><?php echo esc_html( sprintf( _n( '%s entry', '%s entries', count( $terms ), 'modpress' ), number_format_i18n( count( $terms ) ) ) ); ?></div>
			<div class="btn-group btn-group-sm" role="group" aria-label="<?php esc_attr_e( 'Taxonomy actions', 'modpress' ); ?>">
				<?php echo FormFieldHelper::button(
					FAHelper::faicon( 'plus' ) . ' ' . __( 'Add New Host', 'modpress' ),
					array(
						'type' => 'button',
						'class' => 'btn btn-outline-primary',
						'raw' => true,
						'attributes' => array(
							'data-bs-toggle' => 'modal',
							'data-bs-target' => '#modpress-file-hosts-modal',
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
							<input type="checkbox" class="form-check-input modpress-select-all" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" aria-label="<?php esc_attr_e( 'Select all entries', 'modpress' ); ?>" />
						</th>
						<th><?php esc_html_e( 'Image', 'modpress' ); ?></th>
						<th><?php esc_html_e( 'Name', 'modpress' ); ?></th>
						<th><?php esc_html_e( 'Slug', 'modpress' ); ?></th>
						<th><?php esc_html_e( 'URL', 'modpress' ); ?></th>
						<th class="text-end"><?php esc_html_e( 'Actions', 'modpress' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $terms ) ) : ?>
						<tr>
							<td colspan="6" class="text-center text-muted py-4"><?php esc_html_e( 'No items found for this taxonomy yet.', 'modpress' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $terms as $term ) : ?>
							<?php $image_url = get_term_meta( (int) $term->term_id, 'image', true ); ?>
							<?php $term_url = get_term_meta( (int) $term->term_id, 'url', true ); ?>
							<tr>
								<td>
									<input type="checkbox" class="form-check-input modpress-row-checkbox" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" name="file_host_ids[]" value="<?php echo esc_attr( (string) $term->term_id ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Select %s', 'modpress' ), $term->name ) ); ?>" />
								</td>
								<td>
									<?php if ( ! empty( $image_url ) ) : ?>
										<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $term->name ); ?>" style="max-width: 32px; max-height: 32px; object-fit: cover; border-radius: 4px;" />
									<?php else : ?>
										<span class="text-muted"><?php esc_html_e( 'None', 'modpress' ); ?></span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $term->name ); ?></td>
								<td><?php echo esc_html( $term->slug ); ?></td>
								<td>
									<?php if ( ! empty( $term_url ) ) : ?>
										<a href="<?php echo esc_url( $term_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( wp_parse_url( $term_url, PHP_URL_HOST ) ?: $term_url ); ?></a>
									<?php else : ?>
										<span class="text-muted"><?php esc_html_e( 'No URL', 'modpress' ); ?></span>
									<?php endif; ?>
								</td>
								<td class="text-end">
									<div class="btn-group btn-group-sm" role="group" aria-label="<?php esc_attr_e( 'Entry actions', 'modpress' ); ?>">
										<?php echo FormFieldHelper::button(
											FAHelper::faicon( 'pen-to-square' ),
											array(
												'type' => 'button',
												'class' => 'btn btn-outline-secondary',
												'raw' => true,
												'attributes' => array(
													'data-bs-toggle' => 'modal',
													'data-bs-target' => '#modpress-file-hosts-modal',
												),
											)
										); ?>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="d-inline-block">
											<input type="hidden" name="action" value="modpress_delete_file_host" />
											<input type="hidden" name="file_host_slug" value="<?php echo esc_attr( $taxonomy ); ?>" />
											<input type="hidden" name="file_host_ids[]" value="<?php echo esc_attr( (string) $term->term_id ); ?>" />
											<?php wp_nonce_field( 'modpress_delete_file_host', 'modpress_file_host_nonce' ); ?>
											<?php echo FormFieldHelper::button(
												FAHelper::faicon( 'trash' ),
												array(
													'type' => 'submit',
													'class' => 'btn btn-outline-danger',
													'raw' => true,
													'attributes' => array(
														'aria-label' => sprintf( __( 'Delete %s', 'modpress' ), $term->name ),
														'onclick' => 'return window.confirm(\'Delete this entry?\');',
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

		<div class="modal fade" id="modpress-file-hosts-modal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-centered">
				<div class="modal-content">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="modpress_save_file_host" />
						<input type="hidden" name="file_host_slug" value="<?php echo esc_attr( $taxonomy ); ?>" />
						<?php wp_nonce_field( 'modpress_save_file_host', 'modpress_file_host_nonce' ); ?>
						<div class="modal-header">
							<h3 class="modal-title h5"><?php esc_html_e( 'Add File Host', 'modpress' ); ?></h3>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'modpress' ); ?>"></button>
						</div>
						<div class="modal-body">
							<table class="table table-bordered">
								<tbody>
									<tr>
										<th scope="row" class="align-top text-nowrap pe-4">
											<?php echo FormFieldHelper::label(
												'modpress-file-hosts-name',
												__( 'Host Name', 'modpress' ),
												array(
													'description' => __( 'Enter the name of the host you want to add.', 'modpress' ),
													'tooltip'     => __( 'The name will be used to identify the host.', 'modpress' ),
												)
										); ?>
										</th>
										<td>
											<?php echo FormFieldHelper::text_input( 
												'modpress-file-hosts-name', 
												'', 
												array( 
													'id' => 'modpress-file-hosts-name', 
													'placeholder' => __( 'Entry name', 'modpress' ),
													'class' => 'w-100'
												) 
											); ?>
										</td>
									</tr>
									<tr>
										<th scope="row" class="align-top text-nowrap pe-4">
											<?php echo FormFieldHelper::label(
												'modpress-file-hosts-slug',
												__( 'Slug', 'modpress' ),
												array(
													'description' => __( 'Enter a unique slug for the host.', 'modpress' ),
													'tooltip'     => __( 'The slug is a unique identifier for the host.', 'modpress' ),
												)
										); ?>
										</th>
										<td>
											<?php echo FormFieldHelper::text_input( 
												'modpress-file-hosts-slug', 
												'', 
												array( 
													'id' => 'modpress-file-hosts-slug', 
													'placeholder' => __( 'entry-slug', 'modpress' ),
													'class' => 'text-lowercase w-100'
												) 
											); ?>
										</td>
									</tr>
									<tr>
										<th scope="row" class="align-top text-nowrap pe-4">
											<?php echo FormFieldHelper::label(
												'modpress-file-hosts-description',
												__( 'Host Description', 'modpress' ),
												array(
													'description' => __( 'Please provide a short description about the host.', 'modpress' ),
													'tooltip'     => __( 'This description will be used internally.', 'modpress' ),
												)
										); ?>
										</th>
										<td>
											<?php echo FormFieldHelper::textarea( 
												'modpress-file-hosts-description', 
												'', 
												array( 
													'id' => 'modpress-file-hosts-description', 
													'rows' => 4, 
													'placeholder' => __( 'Optional description', 'modpress' ),
													'class' => 'w-100'
												) 
											); ?>
										</td>
									</tr>
									<tr>
										<th scope="row" class="align-top text-nowrap pe-4">
											<?php echo FormFieldHelper::label(
												'modpress-file-hosts-url',
												__( 'Host URL', 'modpress' ),
												array(
													'description' => __( 'Please provide the first part of the host URL.', 'modpress' ),
													'tooltip'     => __( 'By providing the first part of the host URL, you ensure consistency when constructing full URLs for the files attached to your mods.', 'modpress' ),
												)
										); ?>
										</th>
										<td>
											<?php echo FormFieldHelper::input( 
												'modpress-file-hosts-url', 
												'', 
												array( 
													'type' => 'url', 
													'id' => 'modpress-file-hosts-url', 
													'placeholder' => 'https://domain.com/base/path/url/',
													'class' => 'w-100' 
												) 
											); ?>
										</td>
									</tr>
									<tr>
										<th scope="row" class="align-top text-nowrap pe-4">
											<?php echo FormFieldHelper::label(
												'modpress-file-hosts-logo',
												__( 'Host Logo', 'modpress' ),
												array(
													'description' => __( 'Please provide a logo for the host.', 'modpress' ),
													'tooltip'     => __( 'This logo will be used to visually represent the host to users and will be a link to your file.', 'modpress' ),
												)
										); ?>
										</th>
										<td>
											<?php echo WPMediaHelper::create(
												'',
												array(
													'name' => 'modpress-file-hosts-logo',
													'id' => 'modpress-file-hosts-logo',
													'button_text' => __( 'Choose Image', 'modpress' ),
													'show_remove' => true,
												),
												array(
													'alt' => __( 'Host Logo', 'modpress' ),
												)
											); ?>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div class="modal-footer">
							<?php echo FormFieldHelper::button( 
								__( 'Save', 'modpress' ), 
								array( 
									'type' => 'submit', 
									'class' => 'btn btn-primary' 
								) 
							); ?>
							<?php echo FormFieldHelper::button( 
								__( 'Cancel', 'modpress' ), 
								array( 
									'type' => 'button', 
									'class' => 'btn btn-outline-secondary', 
									'attributes' => array( 
										'data-bs-dismiss' => 'modal' 
									) 
								) 
							); ?>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Resolve the preferred post type when a pluralized custom post type is available.
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
	 * Resolve the preferred taxonomy when a custom pluralized taxonomy is available.
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
}
