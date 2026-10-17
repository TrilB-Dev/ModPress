const fs = require('fs');
const path = 'c:\\Users\\Anthony\\OneDrive\\Modding\\Wordpress Plugins\\ModPress\\src\\Admin\\Manager\\Mods\\ModGroups.php';
const content = fs.readFileSync(path, 'utf8');
const start = content.indexOf('<div id="modpress-group-fields"');
if (start < 0) throw new Error('start marker missing');
const end = content.indexOf('<div class="modal-footer justify-content-between">', start);
if (end < 0) throw new Error('end marker missing');

const newBlock = `<div id="modpress-group-fields" class="accordion-collapse collapse" aria-labelledby="modpress-group-fields-heading" data-bs-parent="#modpress-group-accordion">
														<div class="accordion-body">
															<div class="d-flex justify-content-end mb-3">
																<button type="button" class="btn btn-outline-primary btn-sm" data-role="add-custom-field"><?php esc_html_e( 'Add Custom Field', 'modpress' ); ?></button>
															</div>
															<div id="modpress-custom-fields-container" class="modpress-custom-fields-container"></div>
															<div id="modpress-custom-field-template" class="modpress-custom-field-row d-none">
																<div class="d-flex justify-content-between align-items-center mb-3">
																	<h6 class="mb-0"><?php esc_html_e( 'Field', 'modpress' ); ?></h6>
																	<button type="button" class="btn btn-link btn-sm text-danger p-0" data-role="remove-custom-field"><?php esc_html_e( 'Delete', 'modpress' ); ?></button>
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
																						'placeholder' => __( 'Example: release_date', 'modpress' ),
																						'class' => 'w-100'
																					)
																				);
																			},
																			__( 'Field name used for storage and lookup.', 'modpress' ),
																			__( 'Keep this safe and consistent with your mod metadata.', 'modpress' )
																		); ?>
																		<?php $this->render_form_row(
																			'custom_field_type[]',
																			__( 'Field Type', 'modpress' ),
																			function () {
																				return FormFieldHelper::bootstrap_select(
																					'custom_field_type[]',
																					array(
																						'data' => array(
																							'text' => __( 'Text', 'modpress' ),
																							'textarea' => __( 'Text Area', 'modpress' ),
																							'number' => __( 'Number', 'modpress' ),
																							'color' => __( 'Colour', 'modpress' ),
																							'switch' => __( 'Switch', 'modpress' ),
																							'radio' => __( 'Radio button group', 'modpress' ),
																							'select' => __( 'Select', 'modpress' ),
																							'image' => __( 'Image/icon button', 'modpress' ),
																						),
																						'selected' => '',
																						'data-field-type' => 'true',
																					),
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
																					array(
																						'checked' => false,
																					)
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
																						'placeholder' => __( 'Option 1\\nOption 2', 'modpress' ),
																						'class' => 'w-100'
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
												</div>`;

const updated = content.slice(0, start) + newBlock + content.slice(end);
fs.writeFileSync(path, updated, 'utf8');
console.log('done');
