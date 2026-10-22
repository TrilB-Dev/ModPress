<?php
namespace ModPress\Admin\Manager\Mods;

/**
 * Group management screen for the ModPress mod manager.
 *
 * @package ModPress
 * @subpackage Admin\Manager\Mods
 * @since 1.0.0
 */

use ModPress\Includes\Functions\Admin\FunctionsMod;
use ModPress\Includes\Functions\Helpers\AlertHelper;
use ModPress\Includes\Functions\Helpers\DBHelper;
use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\ModManagement\GroupsManager;
use ModPress\Includes\Settings\Settings;

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
				'group_parent_id'    => isset( $group['group_parent_id'] ) ? (int) $group['group_parent_id'] : 0,
				'group_parent_entry' => isset( $group['group_parent_entry'] ) ? (int) $group['group_parent_entry'] : 0,
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
											'data-bs-target' => '#modpress-group-modal',
											'data-group-id'  => $taxonomy['id'] ?? '',
											'data-group-key' => $taxonomy_key,
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
							<?php $terms = taxonomy_exists( $taxonomy_key ) ? get_terms( array( 'taxonomy' => $taxonomy_key, 'hide_empty' => false ) ) : array(); ?>
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
                                                'data-bs-target' => '#modpress-entry-modal',
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
														<td><?php echo esc_html( $term->name ); ?></td>
														<td><?php echo esc_html( $term->slug ); ?></td>
														<td><?php echo esc_html( wp_trim_words( $term->description, 12 ) ); ?></td>
														<td class="text-end">
															<div class="btn-group btn-group-sm" role="group" aria-label="<?php esc_attr_e( 'Entry actions', 'modpress' ); ?>">
																<?php echo FormFieldHelper::button(
																	__( 'Edit', 'modpress' ),
																	array(
																		'type' => 'button',
																		'class' => 'btn btn-outline-secondary',
																		'attributes' => array(
																			'data-bs-toggle' => 'modal',
																			'data-bs-target' => '#modpress-entry-modal',
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
	 * @return void
	 */
	private function render_group_modal( array $taxonomy_tabs ): void {
		$group_options = array();
		$group_entries = array();
		$fa_settings = Settings::get_group( 'fontawesome' );
		$fa_icon_picker = false;
		if ( isset($fa_settings['fontawesome_icon_picker']) && $fa_settings['fontawesome_icon_picker'] ) {
			$fa_icon_picker = true;
		}

		foreach ( $taxonomy_tabs as $taxonomy ) {
			$taxonomy_key = (string) ( $taxonomy['taxonomy'] ?? $taxonomy['key'] ?? '' );
			if ( '' !== $taxonomy_key ) {
				$group_options[ $taxonomy_key ] = $taxonomy['label'] ?? ucfirst( str_replace( array( '-', '_' ), ' ', $taxonomy_key ) );

				$terms = taxonomy_exists( $taxonomy_key ) ? get_terms( array( 'taxonomy' => $taxonomy_key, 'hide_empty' => false ) ) : array();
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					$group_entries[ $taxonomy_key ] = array();
					foreach ( $terms as $term ) {
						$group_entries[ $taxonomy_key ][ (string) $term->term_id ] = $term->name;
					}
				}
			}
		}
		?>
		<div class="modal fade" id="modpress-group-modal" tabindex="-1" aria-labelledby="modpress-group-modal-title" aria-hidden="true" data-group-entries='<?php echo esc_attr( wp_json_encode( $group_entries ) ); ?>'>
			<div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable" role="document">
				<div class="modal-content">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="modpress_save_group" />
						<?php wp_nonce_field( 'modpress_save_group', 'modpress_group_nonce' ); ?>
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
															return FormFieldHelper::text_input(
																'group_name',
																'',
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
																'',
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
																'',
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
																'',
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
																false,
																__( 'Enabled', 'modpress' ),
																array(
																	'id' => 'group_hierarchical'
																)
															);
														},
														__( 'Allow nested entries within this group.', 'modpress' ),
														__( 'Enable this when the group should behave like a parent/child structure.', 'modpress' )
													); ?>
													<?php $this->render_form_row(
														'group_parent',
														__( 'Parent Group', 'modpress' ),
														function () use ( $group_options ) {
															$parent_group_select = FormFieldHelper::bootstrap_select(
																'group_parent',
																array(
																	'data' => $group_options,
																	'selected' => '',
																	'id' => 'group_parent',
																	'placeholder' => __( 'Select a parent group', 'modpress' ),
																)
															);
															$parent_entry_select = FormFieldHelper::bootstrap_select(
																'group_parent_entry',
																array(
																	'data' => array(),
																	'selected' => '',
																	'id' => 'group_parent_entry',
																	'placeholder' => __( 'Select a parent entry', 'modpress' ),
																)
															);

															return '<div class="d-flex align-items-end flex-wrap gap-3 w-100">' .
																																			'<div class="flex-fill min-width-0">' . $parent_group_select . '</div>' .
																																			'<div id="modpress-group-parent-entry-row" class="d-none flex-fill min-width-0">' . $parent_entry_select . '</div>' .
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
												<button type="button" class="btn btn-outline-primary btn-sm" data-role="add-custom-field"><?php esc_html_e( 'Add Custom Field', 'modpress' ); ?></button>
											</div>
											<div id="modpress-custom-fields-container" class="modpress-custom-fields-container">
												<template id="modpress-custom-field-template">
													<div class="modpress-custom-field-row d-none">
														<div class="d-flex justify-content-between align-items-center mb-3">
															<h6 class="mb-0"><?php esc_html_e( 'Field', 'modpress' ); ?></h6>
														</div>
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
																			false,
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
                                    			</template>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<div class="d-flex gap-2 align-self-start modpress-group-delete-wrap d-none">
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="d-inline-block m-0" id="modpress-group-delete-form">
									<input type="hidden" name="action" value="modpress_delete_group" />
									<input type="hidden" name="group_id" value="" />
									<input type="hidden" name="group_edit_key" value="" />
									<?php wp_nonce_field( 'modpress_delete_group', 'modpress_group_nonce' ); ?>
									<?php echo FormFieldHelper::button(
										__( 'Delete', 'modpress' ),
										array(
											'type' => 'submit',
											'class' => 'btn btn-outline-danger',
											'attributes' => array(
												'data-role' => 'delete-group',
												'form' => 'modpress-group-delete-form',
											)
										)
									); ?>
								</form>
							</div>
							<div class="d-flex gap-2 align-self-end">
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
		$entry_parent_options = array(
			'0' => __( 'None', 'modpress' ),
		);
		$group_field_config   = array();

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
				}
			}
		}
		?>
		<div class="modal fade" id="modpress-entry-modal" tabindex="-1" aria-labelledby="modpress-entry-modal-title" aria-hidden="true" data-group-field-config='<?php echo esc_attr( wp_json_encode( $group_field_config ) ); ?>'>
			<div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable" role="document">
				<div class="modal-content">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="modpress_save_group_entry" />
						<input type="hidden" name="group_slug" value="" />
						<input type="hidden" name="group_entry_id" value="" />
						<input type="hidden" name="entry_group_taxonomy" value="" />
						<?php wp_nonce_field( 'modpress_save_group_entry', 'modpress_group_entry_nonce' ); ?>
						<div class="modal-header">
							<h5 class="modal-title" id="modpress-entry-modal-title"><?php esc_html_e( 'Add Entry', 'modpress' ); ?></h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							<table class="table table-borderless mb-0">
								<tbody>
									<?php $this->render_form_row(
										'entry_name',
										__( 'Name', 'modpress' ),
										function () {
											return FormFieldHelper::text_input(
												'entry_name',
												'',
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
										function () {
											return FormFieldHelper::text_input(
												'entry_slug',
												'',
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
										function () {
											return FormFieldHelper::textarea(
												'entry_description',
												'',
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
											function () use ( $entry_parent_options ) {
												return FormFieldHelper::bootstrap_select(
													'entry_parent',
													array(
														'data'     => $entry_parent_options,
														'selected' => '0',
														'id'       => 'entry_parent',
													)
												);
										},
										__( 'Choose a parent entry if required.', 'modpress' ),
										__( 'This is useful for nested categories or grouped options.', 'modpress' )
									); ?>
								<tr class="modpress-entry-custom-fields-row">
									<th scope="row" class="align-top text-nowrap pe-4">
										<label for="modpress-entry-custom-fields"><?php esc_html_e( 'Custom Fields', 'modpress' ); ?></label>
									</th>
									<td>
										<div class="modpress-entry-custom-fields-container"></div>
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
