<?php
/**
 * ModGroups class file.
 * Handles the rendering and management of ModPress groups within the admin interface.
 * 
 * @package ModPress
 * @subpackage Admin\Manager\Mods
 * @author TrilB.Dev
 * @version 1.0.0
 */
namespace ModPress\Admin\Manager\Mods;

use ModPress\Includes\Functions\Admin\FunctionsMod;
use ModPress\Includes\Functions\Helpers\AlertHelper;
use ModPress\Includes\Functions\Helpers\DBHelper;
use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\ModManagement\GroupsManager;
use ModPress\Includes\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

final class ModGroups extends ModManager {
	
	/**
	 * Render the taxonomy manager screen with horizontal tabs, modal forms, and entry tables.
	 *
	 * @return void
	 */
	public function render_page_content(): void {
		$flash_notice = FunctionsMod::consume_admin_notice_flash();
		if ( ! empty( $flash_notice['message'] ) ) {
			AlertHelper::render_admin_notice( $flash_notice['message'], $flash_notice['type'] );
		}

		$taxonomy_tabs = array();
		$group_rows    = DBHelper::table_exists( 'groups' ) ? DBHelper::get_all( 'groups', array(), '*', 'group_name ASC' ) : array();

		foreach ( $group_rows as $group ) {
			$group_slug = trim( (string) ( $group['group_slug'] ?? '' ) );
			if ( '' === $group_slug ) {
				continue;
			}

			$taxonomy_key = GroupsManager::get_group_taxonomy_key( $group_slug );
			if ( '' === $taxonomy_key ) {
				continue;
			}

			if ( 'modpress_tag' === $taxonomy_key ) {
				continue;
			}

			$group_key = trim( (string) ( $group['group_key'] ?? $taxonomy_key ) );
			$taxonomy_tabs[] = array(
				'id'                 => isset( $group['id'] ) ? (int) $group['id'] : 0,
				'taxonomy'           => $taxonomy_key,
				'key'                => $taxonomy_key,
				'label'              => (string) ( $group['group_name'] ?? ucfirst( str_replace( array( '-', '_' ), ' ', $taxonomy_key ) ) ),
				'group_key'          => $group_key,
				'group_name'         => (string) ( $group['group_name'] ?? '' ),
				'group_slug'         => (string) ( $group['group_slug'] ?? '' ),
				'group_singular_name' => (string) ( $group['group_singular_name'] ?? '' ),
				'group_description'  => (string) ( $group['group_description'] ?? '' ),
				'group_hierarchical' => ! empty( $group['group_hierarchical'] ),
				'group_parent'       => FunctionsMod::normalize_group_parent_values( $group['group_parent'] ?? array() ),
				'group_parent_entry' => FunctionsMod::normalize_group_parent_values( $group['group_parent_entry'] ?? array() ),
				'group_fields'       => isset( $group['group_fields'] ) ? $group['group_fields'] : null,
				'reserved'           => 'modpress_type' === $group_key,
				'deletable'          => 'modpress_type' !== $group_key,
				'hierarchical'       => ! empty( $group['group_hierarchical'] ),
			);
		}

        usort(
            $taxonomy_tabs,
            static function ( array $a, array $b ): int {
                $a_is_type = 'modpress_type' === (string) ( $a['taxonomy'] ?? $a['key'] ?? '' ) || 'modpress_type' === (string) ( $a['group_key'] ?? '' );
                $b_is_type = 'modpress_type' === (string) ( $b['taxonomy'] ?? $b['key'] ?? '' ) || 'modpress_type' === (string) ( $b['group_key'] ?? '' );

                if ( $a_is_type !== $b_is_type ) {
                    return $a_is_type ? -1 : 1;
                }

                $a_name = strtolower( (string) ( $a['label'] ?? $a['group_name'] ?? '' ) );
                $b_name = strtolower( (string) ( $b['label'] ?? $b['group_name'] ?? '' ) );

                return strcmp( $a_name, $b_name );
            }
        );
		?>

		<div class="modpress-groups-page">
			<div class="card border-0 shadow-sm mb-4">
				<div class="card-body p-4">
					<div class="d-flex align-items-center justify-content-between gap-3 mb-4">
						<h2 class="h5 mb-0"><?php esc_html_e( 'Mod Groups', 'modpress' ); ?></h2>
						<?php echo FormFieldHelper::button( __( 'Add New Group', 'modpress' ), array(
							'type'    => 'button',
							'class'   => 'btn btn-primary btn-sm',
							'attributes' => array(
								'data-bs-toggle' => 'modal',
											'data-bs-target' => '#modpress-group-modal',
											'data-group-add' => 'true',
							),
						) ); ?>
					</div>

					<ul class="nav nav-tabs" id="modpress-group-tabs" role="tablist">
						<?php foreach ( $taxonomy_tabs as $index => $taxonomy ) : ?>
							<?php $taxonomy_key = (string) ( $taxonomy['taxonomy'] ?? $taxonomy['key'] ?? '' ); ?>
							<li class="nav-item group-nav-item" role="presentation">
								<button
									type="button"
									class="nav-link <?php echo 0 === $index ? 'active' : ''; ?>"
									id="modpress-taxonomy-tab-<?php echo esc_attr( $taxonomy_key ); ?>"
									data-bs-toggle="tab"
									data-bs-target="#modpress-taxonomy-pane-<?php echo esc_attr( $taxonomy_key ); ?>"
									role="tab"
									aria-controls="modpress-taxonomy-pane-<?php echo esc_attr( $taxonomy_key ); ?>"
									aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>"
								>
									<?php echo esc_html( $taxonomy['label'] ?? ucfirst( str_replace( array( '-', '_' ), ' ', $taxonomy_key ) ) ); ?>
								</button>
								<?php echo FormFieldHelper::button(
									'<span class="dashicons dashicons-edit"></span>',
									array(
										'type' => 'button',
										'class' => 'btn btn-link btn-sm text-secondary modpress-tab-edit-btn',
										'raw' => true,
										'attributes' => array(
											'data-bs-toggle' => 'modal',
											'data-bs-target' => '#modpress-group-modal-' . sanitize_html_class( (string) ( $taxonomy['group_key'] ?? $taxonomy_key ) ),
											'data-group-id'  => $taxonomy['id'] ?? '',
											'data-group-key' => (string) ( $taxonomy['group_key'] ?? $taxonomy_key ),
											'data-group-label' => $taxonomy['label'] ?? ucfirst( str_replace( array( '-', '_' ), ' ', $taxonomy_key ) ),
											'aria-label' => __( 'Edit group', 'modpress' ),
										),
									)
								); ?>
							</li>
						<?php endforeach; ?>
					</ul>

					<div class="tab-content mt-4" id="modpress-group-tab-content">
						<?php foreach ( $taxonomy_tabs as $index => $taxonomy ) : ?>
							<?php $taxonomy_key = (string) ( $taxonomy['taxonomy'] ?? $taxonomy['key'] ?? '' ); ?>
							<?php
								$group_page_key = 'modpress_group_page_' . sanitize_key( $taxonomy_key );
								$current_page = absint( $_GET[ $group_page_key ] ?? 1 );
								$per_page = 10;
								$terms_response = taxonomy_exists( $taxonomy_key ) ? get_terms(
									array(
										'taxonomy'   => $taxonomy_key,
										'hide_empty' => false,
										'fields'     => 'ids',
									)
								) : array();
								$total_terms = is_wp_error( $terms_response ) ? 0 : count( (array) $terms_response );
								$total_pages = 0 === $total_terms ? 1 : (int) ceil( $total_terms / $per_page );
								$current_page = max( 1, min( $current_page, $total_pages ) );
								$terms = taxonomy_exists( $taxonomy_key ) ? get_terms(
									array(
										'taxonomy' => $taxonomy_key,
										'hide_empty' => false,
										'number' => $per_page,
										'offset' => ( $current_page - 1 ) * $per_page,
									)
								) : array();
								$pagination_args = array();
								foreach ( $taxonomy_tabs as $taxonomy_nav ) {
									$taxonomy_nav_key = 'modpress_group_page_' . sanitize_key( (string) ( $taxonomy_nav['taxonomy'] ?? $taxonomy_nav['key'] ?? '' ) );
									if ( '' !== $taxonomy_nav_key ) {
										$pagination_args[] = $taxonomy_nav_key;
									}
								}
								$pagination_base = remove_query_arg( $pagination_args, wp_unslash( $_SERVER['REQUEST_URI'] ?? admin_url( 'admin.php' ) ) );
							?>
							<div
								class="tab-pane fade <?php echo 0 === $index ? 'show active' : ''; ?>"
								id="modpress-taxonomy-pane-<?php echo esc_attr( $taxonomy_key ); ?>"
								role="tabpanel"
								aria-labelledby="modpress-taxonomy-tab-<?php echo esc_attr( $taxonomy_key ); ?>"
							>
								<div class="d-flex justify-content-between align-items-center gap-3 mb-3">
									<div></div>
									<div class="btn-group btn-group-sm" role="group" aria-label="<?php esc_attr_e( 'Entry actions', 'modpress' ); ?>">
                                        <?php echo FormFieldHelper::button( __( 'Add New Entry', 'modpress' ), array(
                                            'type' => 'button',
                                            'class' => 'btn btn-outline-primary',
                                            'attributes' => array(
                                                'data-bs-toggle' => 'modal',
                                                'data-bs-target' => '#modpress-entry-modal-' . sanitize_html_class( $taxonomy_key ),
												'data-group-entry-id' => '',
                                                'data-taxonomy-key' => $taxonomy_key,
                                                'data-taxonomy-label' => $taxonomy['label'] ?? ucfirst( str_replace( array( '-', '_' ), ' ', $taxonomy_key ) ),
                                            ),
                                        ) ); ?>
                                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="btn-group btn-group-sm d-inline-flex" role="group" aria-label="<?php esc_attr_e( 'Delete selected entries', 'modpress' ); ?>">
                                            <input type="hidden" name="action" value="modpress_delete_group_entry" />
                                            <input type="hidden" name="group_slug" value="<?php echo esc_attr( $taxonomy_key ); ?>" />
                                            <?php wp_nonce_field( 'modpress_delete_group_entry', 'modpress_group_entry_nonce' ); ?>
                                            <?php echo FormFieldHelper::button( 
                                                __( 'Delete', 'modpress' ), 
                                                array(
                                                    'type' => 'submit',
                                                    'class' => 'btn btn-outline-danger btn-sm btn-delete-selected',
                                                    'disabled' => true,
                                                ) 
                                            ); ?>
                                        </form>
									</div>
								</div>

								<div class="table-responsive">
									<table class="table table-hover align-middle mb-0">
										<thead>
											<tr>
												<th style="width: 42px;">
													<input type="checkbox" class="form-check-input modpress-select-all" data-taxonomy="<?php echo esc_attr( $taxonomy_key ); ?>" aria-label="Select all entries" />
												</th>
												<th><?php esc_html_e( 'Name', 'modpress' ); ?></th>
												<th><?php esc_html_e( 'Slug', 'modpress' ); ?></th>
												<th><?php esc_html_e( 'Description', 'modpress' ); ?></th>
												<th class="text-end"><?php esc_html_e( 'Actions', 'modpress' ); ?></th>
											</tr>
										</thead>
										<tbody>
											<?php if ( empty( $terms ) || is_wp_error( $terms ) ) : ?>
												<tr>
													<td colspan="5" class="text-muted text-center py-4"><?php esc_html_e( 'No entries in this group yet.', 'modpress' ); ?></td>
												</tr>
											<?php else : ?>
												<?php foreach ( $terms as $term ) : ?>
													<tr>
														<td>
															<input type="checkbox" class="form-check-input modpress-row-checkbox" data-taxonomy="<?php echo esc_attr( $taxonomy_key ); ?>" name="group_entry_ids[]" value="<?php echo esc_attr( (string) $term->term_id ); ?>" aria-label="Select entry <?php echo esc_attr( $term->name ); ?>" />
														</td>
														<td>
															<?php echo esc_html( $term->name ); ?>
														</td>
														<td>
															<?php echo esc_html( $term->slug ); ?>
														</td>
														<td>
															<?php echo esc_html( wp_trim_words( $term->description, 12 ) ); ?>
														</td>
														<td class="text-end">
															<div class="btn-group btn-group-sm" role="group" aria-label="<?php esc_attr_e( 'Entry actions', 'modpress' ); ?>">
																<?php echo FormFieldHelper::button(
																	__( 'Edit', 'modpress' ),
																	array(
																		'type' => 'button',
																		'class' => 'btn btn-outline-secondary',
																		'attributes' => array(
																			'data-bs-toggle' => 'modal',
																			'data-bs-target' => '#modpress-entry-modal-' . sanitize_html_class( $taxonomy_key ),
																			'data-taxonomy-key' => $taxonomy_key,
																			'data-taxonomy-label' => $taxonomy['label'] ?? ucfirst( str_replace( array( '-', '_' ), ' ', $taxonomy_key ) ),
																			'data-group-entry-id' => (int) $term->term_id,
																		),
																	)
																); ?>
																<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="btn-group btn-group-sm d-inline-flex" role="group" aria-label="<?php esc_attr_e( 'Delete entry', 'modpress' ); ?>">
																	<input type="hidden" name="action" value="modpress_delete_group_entry" />
																	<input type="hidden" name="group_slug" value="<?php echo esc_attr( $taxonomy_key ); ?>" />
																	<input type="hidden" name="group_entry_id" value="<?php echo esc_attr( (string) $term->term_id ); ?>" />
																	<?php wp_nonce_field( 'modpress_delete_group_entry', 'modpress_group_entry_nonce' ); ?>
																	<?php echo FormFieldHelper::button(
																		__( 'Delete', 'modpress' ),
																		array(
																			'type' => 'submit',
																			'class' => 'btn btn-outline-danger',
																			'attributes' => array(
																				'data-role' => 'delete-group-entry',
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
									<?php if ( $total_terms > $per_page ) : ?>
										<nav aria-label="<?php esc_attr_e( 'Group pagination', 'modpress' ); ?>" class="mt-3">
											<ul class="pagination pagination-sm justify-content-end mb-0">
												<li class="page-item <?php echo 1 === $current_page ? 'disabled' : ''; ?>">
													<a class="page-link modpress-group-page-link" data-modpress-pagination-key="<?php echo esc_attr( $group_page_key ); ?>" data-modpress-taxonomy-key="<?php echo esc_attr( $taxonomy_key ); ?>" href="<?php echo esc_url( add_query_arg( $group_page_key, max( 1, $current_page - 1 ), $pagination_base ) ); ?>"><?php esc_html_e( 'Previous', 'modpress' ); ?></a>
												</li>
												<?php for ( $page_no = 1; $page_no <= $total_pages; $page_no++ ) : ?>
													<li class="page-item <?php echo $page_no === $current_page ? 'active' : ''; ?>">
														<a class="page-link modpress-group-page-link" data-modpress-pagination-key="<?php echo esc_attr( $group_page_key ); ?>" data-modpress-taxonomy-key="<?php echo esc_attr( $taxonomy_key ); ?>" href="<?php echo esc_url( add_query_arg( $group_page_key, $page_no, $pagination_base ) ); ?>"><?php echo esc_html( (string) $page_no ); ?></a>
													</li>
												<?php endfor; ?>
												<li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
													<a class="page-link modpress-group-page-link" data-modpress-pagination-key="<?php echo esc_attr( $group_page_key ); ?>" data-modpress-taxonomy-key="<?php echo esc_attr( $taxonomy_key ); ?>" href="<?php echo esc_url( add_query_arg( $group_page_key, min( $total_pages, $current_page + 1 ), $pagination_base ) ); ?>"><?php esc_html_e( 'Next', 'modpress' ); ?></a>
												</li>
											</ul>
										</nav>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>

		<?php foreach ( $taxonomy_tabs as $taxonomy ) : ?>
			<?php $this->render_group_modal( $taxonomy_tabs, $taxonomy ); ?>
		<?php endforeach; ?>
		<?php $this->render_group_modal( $taxonomy_tabs ); ?>
		<?php foreach ( $taxonomy_tabs as $taxonomy ) : ?>
			<?php $this->render_entry_modal( $taxonomy_tabs, (string) ( $taxonomy['taxonomy'] ?? $taxonomy['key'] ?? '' ) ); ?>
		<?php endforeach; ?>

		<?php
	}

	/**
	 * Render a single table row for a helper-driven form field.
	 *
	 * @param string        $field_name     Name/id for the field.
	 * @param string        $label          Field label.
	 * @param callable      $field_callback Field callback producing the field markup.
	 * @param string        $description    Optional field description.
	 * @param string        $tooltip       Optional tooltip copy.
	 * @return void
	 */
	private function render_form_row( string $field_name, string $label, callable $field_callback, string $description = '', string $tooltip = '', ?string $condition = null, ?string $row_id = null ): void {
		$field_id = 'modpress-' . sanitize_key( str_replace( array( '[]', '[', ']' ), '', $field_name ) );
		$field    = call_user_func( $field_callback );
		$meta     = array(
			'description' => $description,
			'tooltip'     => $tooltip,
		);
		$tr_attrs = '';
		if ( null !== $condition && '' !== $condition ) {
			$tr_attrs = ' data-custom-field-condition="' . esc_attr( $condition ) . '" class="d-none"';
		}
		if ( null !== $row_id && '' !== trim( $row_id ) ) {
			$tr_attrs .= ' id="' . esc_attr( $row_id ) . '"';
		}
		?>
		<tr<?php echo $tr_attrs; ?>>
			<th scope="row" class="align-top text-nowrap pe-4">
				<?php echo wp_kses_post(
						FormFieldHelper::label(
						$field_id,
						$label,
						$meta
					)
				); ?>
			</th>
			<td>
				<?php echo $field; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render the taxonomy group modal.
	 *
	 * @param array<int, array<string, mixed>> $taxonomy_tabs Taxonomy tabs for the page.
	 * @param array<string, mixed>             $selected_group Selected taxonomy group values.
	 * @return void
	 */
	private function render_group_modal( array $taxonomy_tabs, array $selected_group = array() ): void {
		$selected_group = array_merge(
			array(
				'id'                 => 0,
				'taxonomy'           => '',
				'key'                => '',
				'group_key'          => '',
				'group_name'         => '',
				'group_slug'         => '',
				'group_singular_name' => '',
				'group_description'  => '',
				'group_hierarchical' => false,
				'group_parent'       => '',
				'group_parent_entry' => '',
			),
			$selected_group
		);
		$selected_group['group_parent'] = array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( $value ) use ( $taxonomy_tabs ) {
							return FunctionsMod::resolve_group_parent_key( $value, $taxonomy_tabs );
						},
						FunctionsMod::normalize_group_parent_values( $selected_group['group_parent'] ?? array() )
					),
					static fn ( $value ) => '' !== trim( (string) $value )
				)
			)
		);
		$selected_group['group_parent_entry'] = FunctionsMod::normalize_group_parent_values( $selected_group['group_parent_entry'] ?? array() );

		$group_options = array(
			'id'                 => $selected_group['id'] ?? 0,
			'taxonomy'           => (string) ( $selected_group['taxonomy'] ?? $selected_group['key'] ?? '' ),
			'key'                => (string) ( $selected_group['key'] ?? $selected_group['taxonomy'] ?? '' ),
			'group_key'          => (string) ( $selected_group['group_key'] ?? $selected_group['taxonomy'] ?? $selected_group['key'] ?? '' ),
			'group_name'         => (string) ( $selected_group['group_name'] ?? '' ),
			'group_slug'         => (string) ( $selected_group['group_slug'] ?? '' ),
			'group_singular_name' => (string) ( $selected_group['group_singular_name'] ?? '' ),
			'group_description'  => (string) ( $selected_group['group_description'] ?? '' ),
			'group_hierarchical' => ! empty( $selected_group['group_hierarchical'] ),
			'group_parent'       => is_array( $selected_group['group_parent'] ?? null ) ? $selected_group['group_parent'] : (array) ( $selected_group['group_parent'] ?? array() ),
			'group_parent_entry' => is_array( $selected_group['group_parent_entry'] ?? null ) ? $selected_group['group_parent_entry'] : (array) ( $selected_group['group_parent_entry'] ?? array() ),
		);

		$group_entries = array();
		$group_parent_options = array();
		$group_parent_key_lookup = array();
		$fa_settings = Settings::get_group( 'fontawesome' );
		$fa_icon_picker = false;
		if ( isset($fa_settings['fontawesome_icon_picker']) && $fa_settings['fontawesome_icon_picker'] ) {
			$fa_icon_picker = true;
		}

		foreach ( $taxonomy_tabs as $taxonomy ) {
			$taxonomy_key = (string) ( $taxonomy['taxonomy'] ?? $taxonomy['key'] ?? '' );
			if ( '' !== $taxonomy_key ) {
				$taxonomy_id = absint( $taxonomy['id'] ?? 0 );
				$option_key = $taxonomy_id > 0 ? (string) $taxonomy_id : $taxonomy_key;
				$group_parent_options[ $option_key ] = $taxonomy['label'] ?? ucfirst( str_replace( array( '-', '_' ), ' ', $taxonomy_key ) );
				$group_options[ $option_key ] = $taxonomy['label'] ?? ucfirst( str_replace( array( '-', '_' ), ' ', $taxonomy_key ) );
				if ( $taxonomy_id > 0 ) {
					$group_parent_key_lookup[ (string) $taxonomy_id ] = $taxonomy_key;
				}

				$terms = taxonomy_exists( $taxonomy_key ) ? get_terms( array( 'taxonomy' => $taxonomy_key, 'hide_empty' => false ) ) : array();
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					$group_entries[ $taxonomy_key ] = array();
					foreach ( $terms as $term ) {
						$group_entries[ $taxonomy_key ][ (string) $term->term_id ] = $term->name;
					}
					if ( $taxonomy_id > 0 ) {
						$group_entries[ (string) $taxonomy_id ] = $group_entries[ $taxonomy_key ];
					}
				}
			}
		}
		$selected_group_key = (string) ( $selected_group['group_key'] ?? $selected_group['taxonomy'] ?? $selected_group['key'] ?? '' );
		$selected_group_key = '' !== $selected_group_key ? $selected_group_key : (string) ( $selected_group['group_slug'] ?? '' );
		$modal_id = '' === $selected_group_key ? 'modpress-group-modal' : 'modpress-group-modal-' . sanitize_html_class( $selected_group_key );
		$save_form_id = 'modpress-group-save-form-' . ( '' === $selected_group_key ? 'new' : sanitize_html_class( $selected_group_key ) );
		$delete_form_id = 'modpress-group-delete-form-' . ( '' === $selected_group_key ? 'new' : sanitize_html_class( $selected_group_key ) );
		$modal_title = '' === $selected_group_key ? __( 'Add Group', 'modpress' ) : __( 'Edit Group', 'modpress' );
		?>
		<div class="modal fade" id="<?php echo esc_attr( $modal_id ); ?>" tabindex="-1" aria-labelledby="<?php echo esc_attr( $modal_id . '-title' ); ?>" aria-hidden="true" data-group-entries='<?php echo esc_attr( wp_json_encode( $group_entries ) ); ?>'>
			<div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable" role="document">
				<div class="modal-content">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="<?php echo esc_attr( $save_form_id ); ?>" data-role="group-save-form">
						<input type="hidden" name="action" value="modpress_save_group" />
						<?php wp_nonce_field( 'modpress_save_group', 'modpress_group_nonce' ); ?>
						<div class="modal-header">
							<h5 class="modal-title" id="<?php echo esc_attr( $modal_id . '-title' ); ?>"><?php echo esc_html( $modal_title ); ?></h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body" style="overflow:scroll">
							<input type="hidden" name="group_edit_key" value="<?php echo esc_attr( $selected_group_key ); ?>" />
							<input type="hidden" name="group_id" value="<?php echo esc_attr( (string) ( $selected_group['id'] ?? 0 ) ); ?>" />
							<div class="accordion" id="modpress-group-accordion">
								<div class="accordion-item">
									<h2 class="accordion-header" id="modpress-group-info-heading">
										<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#modpress-group-info" aria-expanded="true" aria-controls="modpress-group-info">
											<?php esc_html_e( 'Group Info', 'modpress' ); ?>
										</button>
									</h2>
									<div id="modpress-group-info" class="accordion-collapse collapse show" aria-labelledby="modpress-group-info-heading" data-bs-parent="#modpress-group-accordion">
										<div class="accordion-body">
											<table class="table table-borderless mb-0">
												<tbody>
													<?php $this->render_form_row(
														'group_name',
														__( 'Name', 'modpress' ),
														function () {
															return FormFieldHelper::text_input(
																'group_name',
																$group_options['group_name']  ?? '',
																array(
																	'id' => 'group_name',
																	'class' => 'w-100',
																)
															);
														},
														__( 'The display name for this group.', 'modpress' ),
														__( 'Use a clear name that editors will recognise immediately.', 'modpress' )
													); ?>
													<?php $this->render_form_row(
														'group_singular_name',
														__( 'Singular Name', 'modpress' ),
														function () {
															return FormFieldHelper::text_input(
																'group_singular_name',
																$group_options['group_singular_name'] ?? '',
																array(
																	'id' => 'group_singular_name',
																	'class' => 'w-100',
																)
															);
														},
														__( 'Single-item label for this group.', 'modpress' ),
														__( 'This label is used when the group is shown as a single item.', 'modpress' )
													); ?>
													<?php $this->render_form_row(
														'group_slug',
														__( 'Slug', 'modpress' ),
														function () {
															return FormFieldHelper::text_input(
																'group_slug',
																$group_options['group_slug'] ?? '',
																array(
																	'id' => 'group_slug',
																	'class' => 'w-100',
																)
															);
														},
														__( 'Unique taxonomy slug.', 'modpress' ),
														__( 'This is automatically created from the name when left blank.', 'modpress' )
													); ?>
													<?php $this->render_form_row(
														'group_description',
														__( 'Description', 'modpress' ),
														function () {
															return FormFieldHelper::textarea(
																'group_description',
																$group_options['group_description'] ?? '',
																array(
																	'id' => 'group_description',
																	'rows' => 4,
																	'class' => 'w-100',
																)
															);
														},
														__( 'Explain what this group is used for.', 'modpress' ),
														__( 'This text helps editors understand how the taxonomy should be used.', 'modpress' )
													); ?>
													<?php $this->render_form_row(
														'group_hierarchical',
														__( 'Hierarchical', 'modpress' ),
														function () {
															return FormFieldHelper::switch(
																'group_hierarchical',
																true,
																__( 'Enabled', 'modpress' ),
																array(
																	'id' => 'group_hierarchical',
																	'checked' => (bool) ( $group_options['group_hierarchical'] ?? false ),
																)
															);
														},
														__( 'Allow nested entries within this group.', 'modpress' ),
														__( 'Enable this when the group should behave like a parent/child structure.', 'modpress' )
													); ?>
													<?php $this->render_form_row(
														'group_parent',
														__( 'Parent Group', 'modpress' ),
														function () use ( $group_parent_options, $group_entries, $selected_group ) { 
															$selected_parent_group_value = $selected_group['group_parent'] ?? array();
															$selected_parent_group_values = array_values(
																array_filter(
																	array_map( 'strval', (array) $selected_parent_group_value )
																)
															);
															$parent_group_select = FormFieldHelper::bootstrap_multiselect(
																'group_parent',
																array(
																	'data' => $group_parent_options,
																	'selected' => $selected_parent_group_values,
																	'id' => 'group_parent',
																	'placeholder' => __( 'Select a parent group', 'modpress' ),
																)
															);
															$selected_parent_group_values = array_values( array_filter( array_map( 'strval', (array) ( $selected_group['group_parent'] ?? array() ) ) ) );
															$selected_parent_group = ! empty( $selected_parent_group_values ) ? $selected_parent_group_values[0] : '';
															$selected_parent_entry_values = array_values( array_filter( array_map( 'strval', (array) ( $selected_group['group_parent_entry'] ?? array() ) ) ) );
															$parent_entry_data = array();
															if ( '' !== $selected_parent_group && isset( $group_entries[ $selected_parent_group ] ) ) {
																$parent_entry_data = $group_entries[ $selected_parent_group ];
															}
															$parent_entry_select = FormFieldHelper::bootstrap_multiselect(
																'group_parent_entry',
																array(
																	'data' => $parent_entry_data,
																	'selected' => $selected_parent_entry_values,
																	'id' => 'group_parent_entry',
																	'placeholder' => __( 'Select a parent entry', 'modpress' ),
																)
															);

															return 	'<div class="d-flex align-items-end flex-wrap gap-3 w-100">' .
																	'<div class="flex-fill min-width-0">' . $parent_group_select . '</div>' .
																	'<div id="modpress-group-parent-entry-row" class="' . ( '' === $selected_parent_group ? 'd-none ' : '' ) . 'flex-fill min-width-0">' . $parent_entry_select . '</div>' .
																	'</div>';
														},
														__( 'Choose a parent taxonomy group if needed.', 'modpress' ),
														__( 'This creates a hierarchy between related taxonomy groups.', 'modpress' ),
														null,
														'modpress-group-parent-row'
													); ?>
												</tbody>
											</table>
										</div>
									</div>
								</div>
								<div class="accordion-item">
									<h2 class="accordion-header" id="modpress-group-fields-heading">
										<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#modpress-group-fields" aria-expanded="false" aria-controls="modpress-group-fields">
											<?php esc_html_e( 'Group Fields', 'modpress' ); ?>
										</button>
									</h2>
									<div id="modpress-group-fields" class="accordion-collapse collapse" aria-labelledby="modpress-group-fields-heading" data-bs-parent="#modpress-group-accordion">
										<div class="accordion-body">
											<div class="d-flex justify-content-end mb-3">
												<?php echo FormFieldHelper::button(
													__( 'Add Custom Field', 'modpress' ),
													array(
														'type' => 'button',
														'class' => 'btn btn-outline-primary btn-sm',
														'data-role' => 'add-custom-field'
													)
												); ?>
											</div>
											<div id="modpress-custom-fields-container" class="modpress-custom-fields-container">
												<div class="accordion" id="modpress-custom-fields-accordion">
													<template id="modpress-custom-field-template">
														<div class="accordion-item modpress-custom-field-row d-none">
															<input type="hidden" name="custom_field_id[]" value="" />
																					<div class="accordion-header d-flex justify-content-between align-items-center gap-2">
																<button class="accordion-button collapsed flex-grow-1 text-start" type="button" data-bs-toggle="collapse" aria-expanded="false">
																	<span data-custom-field-prefix><?php esc_html_e( 'Field', 'modpress' ); ?>:</span>
																	<span class="ms-1" data-custom-field-title><?php esc_html_e( 'Field', 'modpress' ); ?></span>
																</button>
																<?php echo FormFieldHelper::button(
																	__( 'Remove', 'modpress' ),
																	array(
																		'type' => 'button',
																		'class' => 'btn btn-outline-danger btn-sm ms-2 modpress-remove-custom-field',
																		'data-role' => 'remove-custom-field',
																		'id' => 'remove-custom-field-button'
																	)
																); ?>
															</div>
															<div class="accordion-collapse collapse" data-bs-parent="#modpress-custom-fields-accordion">
																<div class="accordion-body">
																	<table class="table table-borderless mb-0">
																		<tbody>
																			<?php $this->render_form_row(
																				'custom_field_label[]',
																				__( 'Field Label', 'modpress' ),
																				function () {
																					return FormFieldHelper::text_input(
																						'custom_field_label[]',
																						'',
																						array(
																							'placeholder' => __( 'Example: Groupe Subtitle', 'modpress' ),
																							'class' => 'w-100'
																						)
																					);
																				},
																				__( 'Human-readable label for the field.', 'modpress' ),
																				__( 'This will be displayed in the form and table headers.', 'modpress' )
																			); ?>
																			<?php $this->render_form_row(
																				'custom_field_slug[]',
																				__( 'Field Slug', 'modpress' ),
																				function () {
																					return FormFieldHelper::text_input(
																						'custom_field_slug[]',
																						'',
																						array(
																							'placeholder' => __( 'Example: group_subtitle', 'modpress' ),
																							'class' => 'w-100'
																						)
																					);
																				},
																				__( 'Field slug used for storage and lookup.', 'modpress' ),
																				__( 'Keep this safe and consistent with your mod metadata.', 'modpress' )
																			); ?>
																			<?php $this->render_form_row(
																				'custom_field_description[]',
																				__( 'Field Description', 'modpress' ),
																				function () {
																					return FormFieldHelper::text_input(
																						'custom_field_description[]',
																						'',
																						array(
																							'placeholder' => __( 'Example: The subtitle for the group.', 'modpress' ),
																							'class' => 'w-100'
																						)
																					);
																				},
																				__( 'Description for the field.', 'modpress' ),
																				__( 'This will be displayed as a hint or tooltip in the form.', 'modpress' )
																			); ?>
																			<?php $this->render_form_row(
																				'custom_field_type[]',
																				__( 'Field Type', 'modpress' ),
																				function ()  {
																					return FormFieldHelper::select(
																						'custom_field_type[]',
																						array(
																							'text' => __( 'Text', 'modpress' ),
																							'textarea' => __( 'Text Area', 'modpress' ),
																							'number' => __( 'Number', 'modpress' ),
																							'color' => __( 'Colour', 'modpress' ),
																							'switch' => __( 'Switch', 'modpress' ),
																							'radio' => __( 'Radio button group', 'modpress' ),
																							'select' => __( 'Select', 'modpress' ),
																							'image' => __( 'Image/icon button', 'modpress' ),
																						),
																						'text',
																						array(
																							'class' => 'form-select w-100'
																						)
																					);
																				},
																				__( 'Choose how the field should be entered.', 'modpress' ),
																				__( 'Different field types will change the editor experience.', 'modpress' )
																			); ?>
																			<?php $this->render_form_row(
																				'custom_field_type_textarea_rows[]',
																				__( 'Text Area Rows', 'modpress' ),
																				function () {
																					return FormFieldHelper::input(
																						'custom_field_type_textarea_rows[]',
																						'',
																						array(
																							'type' => 'number',
																							'min' => 1,
																							'class' => 'w-100',
																						)
																					);
																				},
																				__( 'Text area for the field.', 'modpress' ),
																				__( 'Use this for longer text input.', 'modpress' ),
																				'textarea'
																			); ?>
																			<?php $this->render_form_row(
																				'custom_field_type_radio[]',
																				__( 'Radio Button Options', 'modpress' ),
																				function () {
																					return FunctionsMod::repeatable_option_row('radio');
																				},
																				__( 'Options for the radio button group.', 'modpress' ),
																				__( 'Enter each option using the options repeater.', 'modpress' ),
																				'radio'
																			); ?>
																			<?php $this->render_form_row(
																				'custom_field_type_select[]',
																				__( 'Select Options', 'modpress' ),
																				function () {
																					return FunctionsMod::repeatable_option_row('select');
																				},
																				__( 'Options for the select dropdown.', 'modpress' ),
																				__( 'Enter each option using the options repeater.', 'modpress' ),
																				'select'
																			); ?>
																			<?php $this->render_form_row(
																				'custom_field_type_image_type[]',
																				__( 'Image Options', 'modpress' ),
																				function () use ( $fa_icon_picker ) {
																					$choices = array();
																					$choices[] = FormFieldHelper::switch(
																						'custom_field_type_image_type_image[]',
																						'image',
																						__( 'Image', 'modpress' ),
																						array(
																							'checked' => false,
																						)
																					);
																					if ( $fa_icon_picker ) {
																						$choices[] = FormFieldHelper::switch(
																							'custom_field_type_image_type_icon[]',
																							'icon',
																							__( 'Icon', 'modpress' ),
																							array(
																								'checked' => false,
																							)
																						);
																					}
																					$choices[] = FormFieldHelper::switch(
																						'custom_field_type_image_type_url[]',
																						'url',
																						__( 'URL', 'modpress' ),
																						array(
																							'checked' => false,
																						)
																					);
																					return implode( '', $choices );
																				},
																				__( 'Options for the image field.', 'modpress' ),
																				__( 'Image fields in ModPress have the option to choose between Images, Icons and URLs.', 'modpress' ),
																				'image'
																			); ?>
																			<?php $this->render_form_row(
																				'custom_field_show_in_table[]',
																				__( 'Show In Table', 'modpress' ),
																				function () {
																					return FormFieldHelper::switch(
																						'custom_field_show_in_table[]',
																						true,
																						__( 'Enabled', 'modpress' ),
																						array(
																							'checked' => false,
																						)
																					);
																				},
																				__( 'Display this field in the group table view.', 'modpress' ),
																				__( 'Enable this when the field should remain visible in the listing.', 'modpress' )
																			); ?>
																		</tbody>
																	</table>
																</div>
															</div>
														</div>
													</template>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</form>
					<div class="modal-footer d-flex justify-content-between align-items-center w-100">
							<?php if ( 1 !== $group_options['id'] ): ?>
								<div class="modpress-group-delete-wrap d-none me-auto">
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="d-inline-block m-0" id="<?php echo esc_attr( $delete_form_id ); ?>" data-role="group-delete-form">
										<input type="hidden" name="action" value="modpress_delete_group" />
										<input type="hidden" name="group_id" value="<?php echo esc_attr( (string) ( $group_options['id'] ?? 0 ) ); ?>" />
										<input type="hidden" name="group_edit_key" value="<?php echo esc_attr( (string) ( $group_options['group_key'] ?? '' ) ); ?>" />
										<?php wp_nonce_field( 'modpress_delete_group', 'modpress_group_nonce' ); ?>
										<?php echo FormFieldHelper::button(
											__( 'Delete', 'modpress' ),
											array(
												'type' => 'submit',
												'class' => 'btn btn-outline-danger',
												'attributes' => array(
													'data-role' => 'delete-group',
													'form' => $delete_form_id,
												)
											)
										); ?>
									</form>
								</div>
							<?php endif; ?>
							<div class="d-flex gap-2 align-self-end ms-auto">
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
								<?php echo FormFieldHelper::button(
									__( 'Save', 'modpress' ),
									array(
										'type' => 'submit',
										'class' => 'btn btn-primary',
										'attributes' => array(
											'form' => $save_form_id,
										)
									)
								); ?>
							</div>
						</div>
				</div>
			</div>
		</div>
		<?php
	}


	/**
	 * Render the entry modal used to add or edit a taxonomy item.
	 *
	 * @param array<int, array<string, mixed>> $taxonomy_tabs Taxonomy tabs for the page.
	 * @return void
	 */
	private function render_entry_modal( array $taxonomy_tabs, string $selected_group_key = '', array $selected_entry = array() ): void {
		$selected_group_key = sanitize_key( (string) $selected_group_key );
		$selected_entry     = array_merge(
			array(
				'term_id'    => 0,
				'name'       => '',
				'slug'       => '',
				'description'=> '',
				'parent'     => 0,
				'taxonomy'   => $selected_group_key,
			),
			$selected_entry
		);
		$entry_parent_options = array(
			'0' => __( 'None', 'modpress' ),
		);
		$group_field_config   = array();
		$default_group_key    = '';

		foreach ( $taxonomy_tabs as $taxonomy ) {
			$taxonomy_key = (string) ( $taxonomy['taxonomy'] ?? $taxonomy['key'] ?? '' );
			if ( '' === $taxonomy_key || ! taxonomy_exists( $taxonomy_key ) ) {
				continue;
			}

			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy_key,
					'hide_empty' => false,
				)
			);

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				$entry_parent_options[ (string) $term->term_id ] = $term->name;
			}

			$group_fields = $taxonomy['group_fields'] ?? null;
			if ( is_string( $group_fields ) && '' !== trim( $group_fields ) ) {
				$decoded = json_decode( $group_fields, true );
				if ( is_array( $decoded ) ) {
					$group_field_config[ $taxonomy_key ] = $decoded;
					if ( '' === $default_group_key ) {
						$default_group_key = $taxonomy_key;
					}
				}
			}
		}
		if ( '' === $selected_group_key && '' !== $default_group_key ) {
			$selected_group_key = $default_group_key;
		}
		$modal_title = (int) ( $selected_entry['term_id'] ?? 0 ) > 0 ? __( 'Edit Entry', 'modpress' ) : __( 'Add Entry', 'modpress' );
		$selected_group_fields = $group_field_config[ $selected_group_key ] ?? array();
		$selected_group_values = array();
		if ( '' !== $selected_group_key && (int) ( $selected_entry['term_id'] ?? 0 ) > 0 ) {
			foreach ( $selected_group_fields as $field ) {
				if ( ! is_array( $field ) ) {
					continue;
				}
				$field_slug = trim( (string) ( $field['slug'] ?? $field['name'] ?? '' ) );
				if ( '' === $field_slug ) {
					continue;
				}
				$field_id = trim( (string) ( $field['id'] ?? '' ) );
				if ( '' === $field_id ) {
					$field_id = 'field_' . sanitize_key( $field_slug );
				}
				$field_name_key = 'entry_custom_field_' . sanitize_key( $field_id );
				$meta_value = GroupsManager::get_group_entry( (int) $selected_entry['term_id'], $selected_group_key, $field_slug );
				$selected_group_values[ $field_slug ] = $meta_value;
				$selected_group_values[ $field_name_key ] = $meta_value;
			}
		}
		?>
		<?php $modal_id = 'modpress-entry-modal-' . sanitize_html_class( (string) ( $selected_group_key ?: ( $taxonomy_tabs[0]['taxonomy'] ?? $taxonomy_tabs[0]['key'] ?? 'default' ) ) ); ?>
		<div class="modal fade" id="<?php echo esc_attr( $modal_id ); ?>" tabindex="-1" aria-labelledby="<?php echo esc_attr( $modal_id . '-title' ); ?>" aria-hidden="true" data-group-field-config='<?php echo esc_attr( wp_json_encode( $group_field_config ) ); ?>'>
			<div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable" role="document">
				<div class="modal-content">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="modpress_save_group_entry" />
						<input type="hidden" name="group_slug" value="<?php echo esc_attr( $selected_group_key ); ?>" />
						<input type="hidden" name="group_entry_id" value="<?php echo esc_attr( (string) ( $selected_entry['term_id'] ?? 0 ) ); ?>" />
						<input type="hidden" name="entry_group_taxonomy" value="<?php echo esc_attr( (string) ( $selected_entry['taxonomy'] ?? $selected_group_key ) ); ?>" />
						<?php wp_nonce_field( 'modpress_save_group_entry', 'modpress_group_entry_nonce' ); ?>
						<div class="modal-header">
							<h5 class="modal-title" id="<?php echo esc_attr( $modal_id . '-title' ); ?>"><?php echo esc_html( $modal_title ); ?></h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body" style="overflow:scroll">
							<table class="table table-borderless mb-0">
								<tbody>
									<?php $this->render_form_row(
										'entry_name',
										__( 'Name', 'modpress' ),
										function () use ( $selected_entry ) {
											return FormFieldHelper::text_input(
												'entry_name',
												(string) ( $selected_entry['name'] ?? '' ),
												array(
													'id' => 'entry_name',
													'class' => 'w-100'
												)
											);
										},
										__( 'Display name for the entry.', 'modpress' ),
										__( 'Use a human-friendly name that is easy to recognise.', 'modpress' )
									); ?>
									<?php $this->render_form_row(
										'entry_slug',
										__( 'Slug', 'modpress' ),
										function () use ( $selected_entry ) {
											return FormFieldHelper::text_input(
												'entry_slug',
												(string) ( $selected_entry['slug'] ?? '' ),
												array(
													'id' => 'entry_slug',
													'class' => 'w-100'
												)
											);
										},
										__( 'Unique identifier used internally.', 'modpress' ),
										__( 'This should be stable and match the taxonomy slug pattern.', 'modpress' )
									); ?>
									<?php $this->render_form_row(
										'entry_description',
										__( 'Description', 'modpress' ),
										function () use ( $selected_entry ) {
											return FormFieldHelper::textarea(
												'entry_description',
												(string) ( $selected_entry['description'] ?? '' ),
												array(
													'id' => 'entry_description',
													'rows' => 4,
													'class' => 'w-100'
												)
											);
										},
										__( 'Optional description for this entry.', 'modpress' ),
										__( 'Brief descriptions help editors understand what this entry represents.', 'modpress' )
									); ?>
									<?php $this->render_form_row(
										'entry_parent',
										__( 'Parent', 'modpress' ),
										function () use ( $entry_parent_options, $selected_entry ) {
											return FormFieldHelper::bootstrap_select(
												'entry_parent',
												array(
													'data'     => $entry_parent_options,
													'selected' => (string) ( $selected_entry['parent'] ?? '0' ),
													'id'       => 'entry_parent',
												)
											);
										},
										__( 'Choose a parent entry if required.', 'modpress' ),
										__( 'This is useful for nested categories or grouped options.', 'modpress' )
									); ?>
								<tr class="modpress-entry-custom-fields-row">
									<th scope="row" class="align-top text-nowrap pe-4">
										<label for="modpress-entry-custom-fields">
											<?php esc_html_e( 'Custom Fields', 'modpress' ); ?>
										</label>
									</th>
									<td>
										<div class="modpress-entry-custom-fields-container">
											<?php foreach ( $selected_group_fields as $field ) : ?>
											<?php echo FunctionsMod::render_group_custom_field( $field, $selected_group_values ); ?>
											<?php endforeach; ?>
										</div>
									</td>
								</tr>
								</tbody>
							</table>
						</div>
						<div class="modal-footer">
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
							<?php echo FormFieldHelper::button(
								__( 'Save', 'modpress' ),
								array(
									'type' => 'submit',
									'class' => 'btn btn-primary'
								)
							); ?>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}
}