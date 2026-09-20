<?php
/**
 * Group management screen for the ModPress mod manager.
 *
 * @package ModPress
 * @subpackage Admin\Manager\Mods
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\Mods;

use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\ModManagement\GroupsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ModGroups extends ModManager {
	/**
	 * Render the taxonomy manager screen with horizontal tabs, modal forms, and entry tables.
	 *
	 * @return void
	 */
	public function render_page_content(): void {
		$groups_service = new GroupsManager();
		$taxonomy_tabs  = array();
		$group_tree     = $groups_service->get_group_tree();

		foreach ( $group_tree as $group ) {
			if ( ! empty( $group['items'] ) ) {
				foreach ( $group['items'] as $item ) {
					$taxonomy_tabs[] = $item;
				}
			}
		}

		if ( empty( $taxonomy_tabs ) ) {
			$taxonomy_tabs[] = array(
				'key'      => 'modpress_mod_group',
				'taxonomy' => 'modpress_mod_group',
				'label'    => __( 'Mod Groups', 'modpress' ),
				'count'    => 0,
			);
		}

		$active_tab = $taxonomy_tabs[0]['taxonomy'] ?? $taxonomy_tabs[0]['key'] ?? 'modpress_mod_group';
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
								<?php echo FormFieldHelper::button( '<span class="dashicons dashicons-edit"></span>', array(
									'type' => 'button',
									'class' => 'btn btn-link btn-sm text-secondary modpress-tab-edit-btn',
									'raw' => true,
									'attributes' => array(
										'data-bs-toggle' => 'modal',
										'data-bs-target' => '#modpress-group-modal',
										'data-group-key' => $taxonomy_key,
										'data-group-label' => $taxonomy['label'] ?? ucfirst( str_replace( array( '-', '_' ), ' ', $taxonomy_key ) ),
										'aria-label' => __( 'Edit group', 'modpress' ),
									),
								) ); ?>
							</li>
						<?php endforeach; ?>
					</ul>

					<div class="tab-content mt-4" id="modpress-group-tab-content">
						<?php foreach ( $taxonomy_tabs as $index => $taxonomy ) : ?>
							<?php $taxonomy_key = (string) ( $taxonomy['taxonomy'] ?? $taxonomy['key'] ?? '' ); ?>
							<?php $terms = taxonomy_exists( $taxonomy_key ) ? get_terms( array( 'taxonomy' => $taxonomy_key, 'hide_empty' => false ) ) : array(); ?>
							<div
								class="tab-pane fade <?php echo 0 === $index ? 'show active' : ''; ?>"
								id="modpress-taxonomy-pane-<?php echo esc_attr( $taxonomy_key ); ?>"
								role="tabpanel"
								aria-labelledby="modpress-taxonomy-tab-<?php echo esc_attr( $taxonomy_key ); ?>"
							>
								<div class="d-flex justify-content-between align-items-center gap-3 mb-3">
									<div></div>
									<div class="btn-group">
											<?php echo FormFieldHelper::button( __( 'Add New Entry', 'modpress' ), array(
												'type' => 'button',
												'class' => 'btn btn-outline-primary btn-sm',
												'attributes' => array(
													'data-bs-toggle' => 'modal',
													'data-bs-target' => '#modpress-entry-modal',
													'data-taxonomy-key' => $taxonomy_key,
													'data-taxonomy-label' => $taxonomy['label'] ?? ucfirst( str_replace( array( '-', '_' ), ' ', $taxonomy_key ) ),
												),
											) ); ?>
											<?php echo FormFieldHelper::button( __( 'Delete', 'modpress' ), array(
												'type' => 'button',
												'class' => 'btn btn-outline-secondary btn-sm btn-delete-selected',
												'disabled' => true,
											) ); ?>
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
															<input type="checkbox" class="form-check-input modpress-row-checkbox" data-taxonomy="<?php echo esc_attr( $taxonomy_key ); ?>" value="<?php echo esc_attr( (string) $term->term_id ); ?>" aria-label="Select entry <?php echo esc_attr( $term->name ); ?>" />
														</td>
														<td><?php echo esc_html( $term->name ); ?></td>
														<td><?php echo esc_html( $term->slug ); ?></td>
														<td><?php echo esc_html( wp_trim_words( $term->description, 12 ) ); ?></td>
														<td class="text-end">
															<div class="btn-group btn-group-sm">
																<?php echo FormFieldHelper::button( __( 'Edit', 'modpress' ), array(
																	'type' => 'button',
																	'class' => 'btn btn-outline-secondary',
																	'attributes' => array(
																		'data-bs-toggle' => 'modal',
																		'data-bs-target' => '#modpress-entry-modal',
																		'data-taxonomy-key' => $taxonomy_key,
																		'data-entry-name' => $term->name,
																		'data-entry-slug' => $term->slug,
																		'data-entry-description' => $term->description,
																	),
																) ); ?>
																<?php echo FormFieldHelper::button( __( 'Delete', 'modpress' ), array(
																	'type' => 'button',
																	'class' => 'btn btn-outline-danger',
																) ); ?>
															</div>
														</td>
													</tr>
												<?php endforeach; ?>
											<?php endif; ?>
										</tbody>
									</table>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>

		<?php $this->render_group_modal( $taxonomy_tabs ); ?>
		<?php $this->render_entry_modal( $taxonomy_tabs ); ?>

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
	private function render_form_row( string $field_name, string $label, callable $field_callback, string $description = '', string $tooltip = '' ): void {
		$field_id = 'modpress-' . sanitize_key( str_replace( array( '[]', '[', ']' ), '', $field_name ) );
		$field    = call_user_func( $field_callback );
		$meta     = array(
			'description' => $description,
			'tooltip'     => $tooltip,
		);
		?>
		<tr>
			<th scope="row" class="align-top text-nowrap pe-4">
				<?php echo wp_kses_post( FormFieldHelper::label( $field_id, $label, $meta ) ); ?>
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
	 * @return void
	 */
	private function render_group_modal( array $taxonomy_tabs ): void {
		$group_options = array(
			'none' => __( 'None', 'modpress' ),
		);

		foreach ( $taxonomy_tabs as $taxonomy ) {
			$taxonomy_key = (string) ( $taxonomy['taxonomy'] ?? $taxonomy['key'] ?? '' );
			if ( '' !== $taxonomy_key ) {
				$group_options[ $taxonomy_key ] = $taxonomy['label'] ?? ucfirst( str_replace( array( '-', '_' ), ' ', $taxonomy_key ) );
			}
		}
		?>
		<div class="modal fade" id="modpress-group-modal" tabindex="-1" aria-labelledby="modpress-group-modal-title" aria-hidden="true">
			<div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
				<div class="modal-content">
					<form method="post">
						<div class="modal-header">
							<h5 class="modal-title" id="modpress-group-modal-title"><?php esc_html_e( 'Add Group', 'modpress' ); ?></h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							<input type="hidden" name="group_edit_key" value="" />
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
															return FormFieldHelper::text_input( 'group_name', '', array( 'id' => 'group_name' ) );
														},
														__( 'The display name for this group.', 'modpress' ),
														__( 'Use a clear name that editors will recognise immediately.', 'modpress' )
													); ?>
													<?php $this->render_form_row(
														'group_singular_name',
														__( 'Singular Name', 'modpress' ),
														function () {
															return FormFieldHelper::text_input( 'group_singular_name', '', array( 'id' => 'group_singular_name' ) );
														},
														__( 'Single-item label for this group.', 'modpress' ),
														__( 'This label is used when the group is shown as a single item.', 'modpress' )
													); ?>
													<?php $this->render_form_row(
														'group_description',
														__( 'Description', 'modpress' ),
														function () {
															return FormFieldHelper::textarea( 'group_description', '', array( 'id' => 'group_description', 'rows' => 4 ) );
														},
														__( 'Explain what this group is used for.', 'modpress' ),
														__( 'This text helps editors understand how the taxonomy should be used.', 'modpress' )
													); ?>
													<?php $this->render_form_row(
														'group_hierarchical',
														__( 'Hierarchical', 'modpress' ),
														function () {
															return FormFieldHelper::switch( 'group_hierarchical', '1', __( 'Enabled', 'modpress' ), array( 'id' => 'group_hierarchical' ) );
														},
														__( 'Allow nested entries within this group.', 'modpress' ),
														__( 'Enable this when the group should behave like a parent/child structure.', 'modpress' )
													); ?>
													<?php $this->render_form_row(
														'group_parent',
														__( 'Parent Group', 'modpress' ),
														function () use ( $group_options ) {
															return FormFieldHelper::select( 'group_parent', $group_options, 'none', array( 'id' => 'group_parent', 'class' => 'selectpicker' ) );
														},
														__( 'Choose a parent taxonomy group if needed.', 'modpress' ),
														__( 'This creates a hierarchy between related taxonomy groups.', 'modpress' )
													); ?>
													<?php $this->render_form_row(
														'modpress-parent-option-block',
														__( 'Parent Option', 'modpress' ),
														function () {
															return '<div id="modpress-parent-option-block" class="border rounded p-3 bg-light text-muted">' . esc_html__( 'Parent group values will appear here when a parent is selected.', 'modpress' ) . '</div>';
														},
														__( 'Show values from the selected parent group.', 'modpress' ),
														__( 'Values appear here when a parent source is selected.', 'modpress' )
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
											<table class="table table-borderless mb-0">
												<tbody>
													<?php $this->render_form_row(
														'field_name',
														__( 'Name', 'modpress' ),
														function () { return FormFieldHelper::text_input( 'field_name', '', array( 'id' => 'field_name' ) ); },
														__( 'The field name shown in the taxonomy form.', 'modpress' ),
														__( 'This value should be short, clear, and easy to search for.', 'modpress' )
													); ?>
													<?php $this->render_form_row(
														'field_slug',
														__( 'Slug', 'modpress' ),
														function () { return FormFieldHelper::text_input( 'field_slug', '', array( 'id' => 'field_slug' ) ); },
														__( 'Unique machine-friendly identifier.', 'modpress' ),
														__( 'This slug is used internally and should remain consistent.', 'modpress' )
													); ?>
													<?php $this->render_form_row(
														'field_parent',
														__( 'Parent', 'modpress' ),
														function () { return FormFieldHelper::select( 'field_parent', array( 'none' => __( 'None', 'modpress' ) ), 'none', array( 'id' => 'field_parent', 'class' => 'selectpicker' ) ); },
														__( 'Choose a parent field if this field is nested.', 'modpress' ),
														__( 'Parent fields help organise sectional metadata.', 'modpress' )
													); ?>
													<?php $this->render_form_row(
														'field_description',
														__( 'Description', 'modpress' ),
														function () { return FormFieldHelper::textarea( 'field_description', '', array( 'id' => 'field_description', 'rows' => 3 ) ); },
														__( 'Description of the field purpose.', 'modpress' ),
														__( 'A concise description helps editors understand what should go here.', 'modpress' )
													); ?>
												</tbody>
											</table>
											<div class="d-flex justify-content-between align-items-center mb-3">
												<h6 class="mb-0"><?php esc_html_e( 'Custom Fields', 'modpress' ); ?></h6>
												<?php echo FormFieldHelper::button( 
                                                    __( 'Add New Field', 'modpress' ), 
                                                    array( 
                                                        'type' => 'button', 
                                                        'class' => 'btn btn-outline-secondary btn-sm', 
                                                        'attributes' => array( 
                                                            'data-role' => 'add-custom-field' 
                                                        ) 
                                                    ) 
                                                ); ?>
											</div>
											<div id="modpress-custom-fields-container">
												<div class="modpress-custom-field-row">
													<div class="d-flex justify-content-between align-items-center mb-3">
														<h6 class="mb-0"><?php esc_html_e( 'Field', 'modpress' ); ?></h6>
														<?php echo FormFieldHelper::button( 
                                                            __( 'Delete', 'modpress' ), 
                                                            array( 
                                                                'type' => 'button', 
                                                                'class' => 'btn btn-link btn-sm text-danger p-0', 
                                                                'attributes' => array( 
                                                                    'data-role' => 'remove-custom-field' 
                                                                ) 
                                                            ) 
                                                        ); ?>
													</div>
													<table class="table table-borderless mb-0">
														<tbody>
															<?php $this->render_form_row(
																'custom_field_name[]',
																__( 'Field Name', 'modpress' ),
																function () { return FormFieldHelper::text_input( 'custom_field_name[]', '', array( 'placeholder' => __( 'Example: release_date', 'modpress' ) ) ); },
																__( 'Field name used for storage and lookup.', 'modpress' ),
																__( 'Keep this safe and consistent with your mod metadata.', 'modpress' )
															); ?>
															<?php $this->render_form_row(
																'custom_field_description[]',
																__( 'Field Description', 'modpress' ),
																function () { return FormFieldHelper::textarea( 'custom_field_description[]', '', array( 'rows' => 2, 'placeholder' => __( 'Describe the field purpose.', 'modpress' ) ) ); },
																__( 'Optional description for editors.', 'modpress' ),
																__( 'This explanation helps maintainers decide what belongs in this field.', 'modpress' )
															); ?>
															<?php $this->render_form_row(
																'custom_field_tooltip[]',
																__( 'Field Tooltip', 'modpress' ),
																function () { return FormFieldHelper::textarea( 'custom_field_tooltip[]', '', array( 'rows' => 2, 'placeholder' => __( 'Tooltip shown to editors.', 'modpress' ) ) ); },
																__( 'The tooltip shown when hovering over the field.', 'modpress' ),
																__( 'Keep it short and practical so editors can act quickly.', 'modpress' )
															); ?>
															<?php $this->render_form_row(
																'custom_field_type[]',
																__( 'Field Type', 'modpress' ),
																function () { return FormFieldHelper::select( 'custom_field_type[]', array( 'text' => __( 'Text', 'modpress' ), 'textarea' => __( 'Text Area', 'modpress' ), 'number' => __( 'Number', 'modpress' ), 'image' => __( 'Image/icon button', 'modpress' ), 'switch' => __( 'Switch', 'modpress' ), 'radio' => __( 'Radio button group', 'modpress' ), 'select' => __( 'Select', 'modpress' ), 'color' => __( 'Colour', 'modpress' ) ), '', array( 'class' => 'selectpicker', 'data-field-type' => 'true' ) ); },
																__( 'Choose how the field should be entered.', 'modpress' ),
																__( 'Different field types will change the editor experience.', 'modpress' )
															); ?>
															<?php $this->render_form_row(
																'custom_field_show_in_table[]',
																__( 'Show In Table', 'modpress' ),
																function () { return FormFieldHelper::switch( 'custom_field_show_in_table[]', '1', __( 'Enabled', 'modpress' ), array( 'checked' => false ) ); },
																__( 'Display this field in the group table view.', 'modpress' ),
																__( 'Enable this when the field should remain visible in the listing.', 'modpress' )
															); ?>
															<?php $this->render_form_row(
																'custom_field_options[]',
																__( 'Field Options', 'modpress' ),
																function () { return FormFieldHelper::textarea( 'custom_field_options[]', '', array( 'rows' => 3, 'placeholder' => __( 'Option 1\nOption 2', 'modpress' ) ) ); },
																__( 'Option values for radio, select, or checkbox fields.', 'modpress' ),
																__( 'Place one option per line so the field can be rendered correctly.', 'modpress' )
															); ?>
														</tbody>
													</table>
												</div>
											</div>
											<div id="modpress-custom-field-template" class="modpress-custom-field-row d-none">
												<div class="d-flex justify-content-between align-items-center mb-3">
													<h6 class="mb-0"><?php esc_html_e( 'Field', 'modpress' ); ?></h6>
													<?php echo FormFieldHelper::button( 
                                                        __( 'Delete', 'modpress' ), 
                                                        array( 
                                                            'type' => 'button', 
                                                            'class' => 'btn btn-link btn-sm text-danger p-0', 
                                                            'attributes' => array( 
                                                                'data-role' => 'remove-custom-field' 
                                                                ) 
                                                            ) 
                                                        ); ?>
												</div>
												<table class="table table-borderless mb-0">
													<tbody>
														<?php $this->render_form_row( 
                                                            'custom_field_name[]', 
                                                            __( 'Field Name', 'modpress' ), 
                                                            function () { 
                                                                return FormFieldHelper::text_input( 
                                                                    'custom_field_name[]', 
                                                                    '', 
                                                                    array( 
                                                                        'placeholder' => __( 'Example: release_date', 'modpress' ) 
                                                                    ) 
                                                                );
                                                            }, 
                                                            __( 'Field name used for storage and lookup.', 'modpress' ), 
                                                            __( 'Keep this safe and consistent with your mod metadata.', 'modpress' ) 
                                                        ); ?>
														<?php $this->render_form_row( 
                                                            'custom_field_description[]', 
                                                            __( 'Field Description', 'modpress' ), 
                                                            function () { 
                                                                return FormFieldHelper::textarea( 
                                                                    'custom_field_description[]', 
                                                                    '', 
                                                                    array( 
                                                                        'rows' => 2, 
                                                                        'placeholder' => __( 'Describe the field purpose.', 'modpress' ) 
                                                                    ) 
                                                                ); 
                                                            }, 
                                                            __( 'Optional description for editors.', 'modpress' ), 
                                                            __( 'This explanation helps maintainers decide what belongs in this field.', 'modpress' ) 
                                                        ); ?>
														<?php $this->render_form_row( 
                                                            'custom_field_tooltip[]', 
                                                            __( 'Field Tooltip', 'modpress' ), 
                                                            function () { 
                                                                return FormFieldHelper::textarea( 
                                                                    'custom_field_tooltip[]', 
                                                                    '', 
                                                                    array( 
                                                                        'rows' => 2, 
                                                                        'placeholder' => __( 'Tooltip shown to editors.', 'modpress' ) 
                                                                    ) 
                                                                ); 
                                                            }, 
                                                            __( 'The tooltip shown when hovering over the field.', 'modpress' ), 
                                                            __( 'Keep it short and practical so editors can act quickly.', 'modpress' ) 
                                                        ); ?>
														<?php $this->render_form_row( 
                                                            'custom_field_type[]', 
                                                            __( 'Field Type', 'modpress' ), 
                                                            function () { 
                                                                return FormFieldHelper::select( 
                                                                    'custom_field_type[]', 
                                                                    array( 
                                                                        'text' => __( 'Text', 'modpress' ), 
                                                                        'textarea' => __( 'Text Area', 'modpress' ), 
                                                                        'number' => __( 'Number', 'modpress' ), 
                                                                        'image' => __( 'Image/icon button', 'modpress' ), 
                                                                        'switch' => __( 'Switch', 'modpress' ), 
                                                                        'radio' => __( 'Radio button group', 'modpress' ), 
                                                                        'select' => __( 'Select', 'modpress' ), 
                                                                        'color' => __( 'Colour', 'modpress' ) 
                                                                    ), 
                                                                    '', 
                                                                    array( 'class' => 'selectpicker', 'data-field-type' => 'true' ) 
                                                                ); 
                                                            }, 
                                                            __( 'Choose how the field should be entered.', 'modpress' ), 
                                                            __( 'Different field types will change the editor experience.', 'modpress' ) 
                                                        ); ?>
														<?php $this->render_form_row( 
                                                            'custom_field_show_in_table[]', 
                                                            __( 'Show In Table', 'modpress' ), 
                                                            function () { 
                                                                return FormFieldHelper::switch( 
                                                                    'custom_field_show_in_table[]', 
                                                                    '1', 
                                                                    __( 'Enabled', 'modpress' ), 
                                                                    array( 'checked' => false ) 
                                                                );
                                                            }, 
                                                            __( 'Display this field in the group table view.', 'modpress' ), 
                                                            __( 'Enable this when the field should remain visible in the listing.', 'modpress' ) 
                                                        ); ?>
														<?php $this->render_form_row( 
                                                            'custom_field_options[]', 
                                                            __( 'Field Options', 'modpress' ), 
                                                            function () { 
                                                                return FormFieldHelper::textarea( 
                                                                    'custom_field_options[]', 
                                                                    '', 
                                                                    array( 
                                                                        'rows' => 3, 
                                                                        'placeholder' => __( 'Option 1\nOption 2', 'modpress' ) 
                                                                    ) 
                                                                ); 
                                                            }, 
                                                            __( 'Option values for radio, select, or checkbox fields.', 'modpress' ), 
                                                            __( 'Place one option per line so the field can be rendered correctly.', 'modpress' ) 
                                                        ); ?>
													</tbody>
												</table>
											</div>
										</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<?php echo FormFieldHelper::button( __( 'Cancel', 'modpress' ), array( 'type' => 'button', 'class' => 'btn btn-outline-secondary', 'attributes' => array( 'data-bs-dismiss' => 'modal' ) ) ); ?>
						<?php echo FormFieldHelper::button( __( 'Save', 'modpress' ), array( 'type' => 'submit', 'class' => 'btn btn-primary' ) ); ?>
					</div>
				</form>
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
	private function render_entry_modal( array $taxonomy_tabs ): void {
		?>
		<div class="modal fade" id="modpress-entry-modal" tabindex="-1" aria-labelledby="modpress-entry-modal-title" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
				<div class="modal-content">
					<form method="post">
						<div class="modal-header">
							<h5 class="modal-title" id="modpress-entry-modal-title"><?php esc_html_e( 'Add Entry', 'modpress' ); ?></h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							<input type="hidden" name="entry_group_taxonomy" value="" />
							<table class="table table-borderless mb-0">
								<tbody>
									<?php $this->render_form_row(
										'entry_name',
										__( 'Name', 'modpress' ),
										function () {
											return FormFieldHelper::text_input( 'entry_name', '', array( 'id' => 'entry_name' ) );
										},
										__( 'Display name for the entry.', 'modpress' ),
										__( 'Use a human-friendly name that is easy to recognise.', 'modpress' )
									); ?>
									<?php $this->render_form_row(
										'entry_slug',
										__( 'Slug', 'modpress' ),
										function () {
											return FormFieldHelper::text_input( 'entry_slug', '', array( 'id' => 'entry_slug' ) );
										},
										__( 'Unique identifier used internally.', 'modpress' ),
										__( 'This should be stable and match the taxonomy slug pattern.', 'modpress' )
									); ?>
									<?php $this->render_form_row(
										'entry_description',
										__( 'Description', 'modpress' ),
										function () {
											return FormFieldHelper::textarea( 'entry_description', '', array( 'id' => 'entry_description', 'rows' => 4 ) );
										},
										__( 'Optional description for this entry.', 'modpress' ),
										__( 'Brief descriptions help editors understand what this entry represents.', 'modpress' )
									); ?>
									<?php $this->render_form_row(
										'entry_parent',
										__( 'Parent', 'modpress' ),
										function () {
											return FormFieldHelper::select( 'entry_parent', array( 'none' => __( 'None', 'modpress' ) ), 'none', array( 'id' => 'entry_parent', 'class' => 'selectpicker' ) );
										},
										__( 'Choose a parent entry if required.', 'modpress' ),
										__( 'This is useful for nested categories or grouped options.', 'modpress' )
									); ?>
									<?php $this->render_form_row(
										'entry_icon',
										__( 'Image/Icon', 'modpress' ),
										function () {
											return FormFieldHelper::text_input( 'entry_icon', '', array( 'id' => 'entry_icon', 'placeholder' => __( 'e.g. dashicons-admin-generic', 'modpress' ) ) );
										},
										__( 'Optional icon or image identifier.', 'modpress' ),
										__( 'Use a Dashicon class or similar icon reference for display.', 'modpress' )
									); ?>
								</tbody>
							</table>
						</div>
						<div class="modal-footer">
							<?php echo FormFieldHelper::button( __( 'Cancel', 'modpress' ), array( 'type' => 'button', 'class' => 'btn btn-outline-secondary', 'attributes' => array( 'data-bs-dismiss' => 'modal' ) ) ); ?>
							<?php echo FormFieldHelper::button( __( 'Save', 'modpress' ), array( 'type' => 'submit', 'class' => 'btn btn-primary' ) ); ?>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}
}
