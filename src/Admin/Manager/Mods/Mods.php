<?php
/**
 * Mod list screen for the ModPress mod manager.
 *
 * @package ModPress
 * @subpackage Admin\Manager\Mods
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\Mods;

use ModPress\Includes\ModManagement\GroupsManager;
use ModPress\Includes\ModManagement\ModManagement;
use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\Plugins\FontAwesome\Includes\Functions\Helpers\FAHelper;
use ModPress\Includes\Functions\Helpers\WPMediaHelper;
use ModPress\Includes\Plugins\TinyMCE\Includes\Functions\Helpers\TinyMCEHelper;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Mods extends ModManager {
	/**
	 * Render a list of available mod records.
	 *
	 * @return void
	 */
	public function render_page_content( string $form = '' ): void {
		$form_state = $this->normalize_form_state( $form );
		if ( '' === $form_state && isset( $_GET['form'] ) ) {
			$form_state = $this->normalize_form_state( (string) wp_unslash( $_GET['form'] ) );
		}

		if ( in_array( $form_state, array( 'new', 'edit' ), true ) ) {
			$this->render_mod_form();
			return;
		}

		$mod_service = new ModManagement();
		$mods        = $mod_service->get_mods();
		?>
		<div class="card shadow-sm border-0 mt-4">
			<div class="card-body p-4">
				<div class="d-flex align-items-center justify-content-between gap-3 mb-4">
					<h2 class="h5 mb-0"><?php esc_html_e( 'Available Mods', 'modpress' ); ?></h2>
					<a class="btn btn-primary btn-sm" href="<?php echo esc_url( admin_url( 'admin.php?page=modpress&group=mod-manager&tab=mods&form=new' ) ); ?>"><?php esc_html_e( 'Create mod', 'modpress' ); ?></a>
				</div>
				<?php if ( empty( $mods ) ) : ?>
					<p class="text-muted mb-0"><?php esc_html_e( 'No mods have been created yet.', 'modpress' ); ?></p>
				<?php else : ?>
					<div class="table-responsive">
						<table class="table align-middle mb-0">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Name', 'modpress' ); ?></th>
									<th><?php esc_html_e( 'Status', 'modpress' ); ?></th>
									<th><?php esc_html_e( 'Version', 'modpress' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $mods as $mod ) : ?>
									<?php $summary = $mod_service->get_mod_summary( (int) $mod->ID ); ?>
									<tr>
										<td><?php echo esc_html( $summary['title'] ); ?></td>
										<td><?php echo esc_html( ucfirst( $summary['status'] ) ); ?></td>
										<td><?php echo esc_html( $summary['version'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
	/**
	 * Render the mod form.
	 *
	 * @param array $mod The mod data to populate the form with.
	 * @return void
	 * @since 1.0.0
	 */
	private function render_mod_form( array $mod = [] ): void {
		$mod_id = absint( $mod['ID'] ?? ( $mod['id'] ?? 0 ) );
		$mod    = array_merge(
			array(
				'ID' => 0,
				'post_title' => '',
				'post_name' => '',
				'post_author' => get_current_user_id(),
				'post_status' => 'draft',
				'post_excerpt' => '',
				'post_content' => '',
				'featured_image' => '',
			),
			$mod
		);

		$featured_image = $mod_id > 0 ? (string) get_post_thumbnail_id( $mod_id ) : (string) ( $mod['featured_image'] ?? '' );
		$mod_logo = $mod_id > 0 ? (string) get_post_meta( $mod_id, 'mod_logo', true ) : (string) ( $mod['mod_logo'] ?? '' );
		$mod_gallery = $mod_id > 0 ? get_post_meta( $mod_id, 'mod_gallery', true ) : ( $mod['mod_gallery'] ?? array() );
		if ( ! is_array( $mod_gallery ) ) {
			if ( is_string( $mod_gallery ) ) {
				$decoded_gallery = json_decode( wp_unslash( $mod_gallery ), true );
				if ( is_array( $decoded_gallery ) ) {
					$mod_gallery = $decoded_gallery;
				} else {
					$mod_gallery = array_values( array_filter( array_map( 'absint', preg_split( '/[\s,]+/', trim( $mod_gallery ) ) ) ) );
				}
			} else {
				$mod_gallery = array();
			}
		}
		$mod_gallery = array_values( array_unique( array_filter( array_map( 'absint', (array) $mod_gallery ) ) ) );
		$author_id = absint( $mod['post_author'] ?? get_current_user_id() );
		$can_edit_others = current_user_can( 'modpress_manager_mods_edit_others' );
		$author_options = array();
		if ( function_exists( 'get_users' ) && $can_edit_others ) {
			foreach ( get_users( array( 'orderby' => 'display_name', 'fields' => array( 'ID', 'display_name' ) ) ) as $user ) {
				$author_options[ (string) $user->ID ] = $user->display_name;
			}
		}
		if ( empty( $author_options ) && $can_edit_others ) {
			$author_options[ (string) $author_id ] = __( 'Current User', 'modpress' );
		}
		$current_user = function_exists( 'get_userdata' ) ? get_userdata( $author_id ) : null;
		$current_user_name = $current_user && ! empty( $current_user->display_name ) ? $current_user->display_name : __( 'Current User', 'modpress' );

		$mod_type_options = array();
		if ( function_exists( 'get_terms' ) ) {
			$mod_type_terms = get_terms(
				array(
					'taxonomy'   => 'modpress_type',
					'hide_empty' => false,
					'orderby'    => 'name',
					'order'      => 'ASC',
				)
			);

			if ( ! is_wp_error( $mod_type_terms ) && ! empty( $mod_type_terms ) ) {
				foreach ( $mod_type_terms as $term ) {
					$mod_type_options[ (string) (int) $term->term_id ] = $term->name;
				}
			}
		}

		$mod_type_selected = (string) ( $mod['mod_type'] ?? '' );
		if ( '' === $mod_type_selected && $mod_id > 0 && function_exists( 'wp_get_post_terms' ) ) {
			$selected_mod_type = wp_get_post_terms( $mod_id, 'modpress_type', array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $selected_mod_type ) && ! empty( $selected_mod_type ) ) {
				$mod_type_selected = (string) (int) $selected_mod_type[0];
			}
		}
		if ( '' === $mod_type_selected && ! empty( $mod_type_options ) ) {
			$mod_type_selected = (string) array_key_first( $mod_type_options );
		}

		$dependent_mod_options = array();
		if ( function_exists( 'get_posts' ) ) {
			$mod_posts = get_posts(
				array(
					'post_type'      => 'modpress_mod',
					'post_status'    => array( 'publish', 'draft', 'private', 'future' ),
					'posts_per_page' => -1,
					'orderby'        => 'title',
					'order'          => 'ASC',
				)
			);

			if ( ! empty( $mod_posts ) ) {
				foreach ( $mod_posts as $mod_post ) {
					$dependent_mod_options[ (string) $mod_post->ID ] = $mod_post->post_title;
				}
			}
		}
		$existing_dependents = array();
		if ( $mod_id > 0 ) {
			$saved_dependents = get_post_meta( $mod_id, '_modpress_dependents', true );
			if ( is_array( $saved_dependents ) ) {
				$existing_dependents = array_values( $saved_dependents );
			}
		}

		$file_host_options = array();
		$file_host_urls = array();
		if ( function_exists( 'get_terms' ) ) {
			$file_host_terms = get_terms(
				array(
					'taxonomy'   => 'modpress_file_hosts',
					'hide_empty' => false,
					'orderby'    => 'name',
					'order'      => 'ASC',
				)
			);

			if ( ! is_wp_error( $file_host_terms ) && ! empty( $file_host_terms ) ) {
				foreach ( $file_host_terms as $term ) {
					$file_host_options[ (string) (int) $term->term_id ] = $term->name;
					$term_url = get_term_meta( (int) $term->term_id, 'url', true );
					if ( is_string( $term_url ) ) {
						$term_url = trim( $term_url );
						if ( '' !== $term_url ) {
							$normalized_term_url = function_exists( 'esc_url_raw' ) ? esc_url_raw( $term_url ) : ( function_exists( 'sanitize_url' ) ? sanitize_url( $term_url ) : $term_url );
							$file_host_urls[ (string) (int) $term->term_id ] = $normalized_term_url;
						}
					}
				}
			}
		}

		$existing_files = array();
		if ( $mod_id > 0 ) {
			$saved_files = get_post_meta( $mod_id, '_modpress_files', true );
			if ( is_array( $saved_files ) ) {
				$existing_files = array_values( $saved_files );
			}
		}

		$status_options = array(
			'draft' => __( 'Draft', 'modpress' ),
			'publish' => __( 'Published', 'modpress' ),
			'private' => __( 'Private', 'modpress' ),
			'future' => __( 'Future', 'modpress' ),
		);
		$mod_status_options = array();
		if ( function_exists( 'get_terms' ) ) {
			$mod_status_terms = get_terms(
				array(
					'taxonomy'   => 'modpress_mod_status',
					'hide_empty' => false,
					'orderby'    => 'name',
					'order'      => 'ASC',
				)
			);

			if ( ! is_wp_error( $mod_status_terms ) && ! empty( $mod_status_terms ) ) {
				foreach ( $mod_status_terms as $term ) {
					$mod_status_options[ (string) (int) $term->term_id ] = $term->name;
				}
			}
		}
		$mod_status_selected = (string) ( $mod['mod_status'] ?? '' );
		if ( '' === $mod_status_selected && $mod_id > 0 && function_exists( 'wp_get_post_terms' ) ) {
			$selected_mod_status = wp_get_post_terms( $mod_id, 'modpress_mod_status', array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $selected_mod_status ) && ! empty( $selected_mod_status ) ) {
				$mod_status_selected = (string) (int) $selected_mod_status[0];
			}
		}
		if ( '' === $mod_status_selected && ! empty( $mod_status_options ) ) {
			$mod_status_selected = (string) array_key_first( $mod_status_options );
		}
		?>
		<div class="card shadow-sm border-0">
			<div class="card-body p-4">
				<div class="d-flex align-items-center justify-content-between gap-3 mb-4">
					<h2 class="h4 mb-0"><?php echo esc_html( $mod_id > 0 ? __( 'Edit Mod', 'modpress' ) : __( 'New Mod', 'modpress' ) ); ?></h2>
					<a class="btn btn-outline-secondary btn-sm" href="<?php echo esc_url( admin_url( 'admin.php?page=modpress&group=mod-manager&tab=mods&form=new' ) ); ?>"><?php esc_html_e( 'Reset', 'modpress' ); ?></a>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="modpress_save_mod" />
					<input type="hidden" name="modpress_action" value="modpress_save_mod" />
					<?php if ( $mod_id > 0 ) : ?>
						<input type="hidden" name="mod_id" value="<?php echo esc_attr( (string) $mod_id ); ?>" />
					<?php endif; ?>
					<?php wp_nonce_field( 'modpress_save_mod', 'modpress_mod_nonce' ); ?>
					<div class="row g-3">
						<div class="col-md-6">
							<?php echo FormFieldHelper::label( 'modpress-mod-title', __( 'Mod Name', 'modpress' ) ); ?>
							<?php echo FormFieldHelper::text_input(
								'post_title',
								(string) ( $mod['post_title'] ?? '' ),
								array(
									'id' => 'modpress-mod-title',
									'required' => true,
									'placeholder' => __( 'My Mod', 'modpress' ),
									'class' => 'w-100',
								)
							); ?>
							<div class="form-text"><?php esc_html_e( 'Set the public name for this mod.', 'modpress' ); ?></div>
						</div>
						<div class="col-md-6">
							<?php echo FormFieldHelper::label( 'modpress-mod-slug', __( 'Mod Slug', 'modpress' ) ); ?>
							<?php echo FormFieldHelper::text_input(
								'post_name',
								(string) ( $mod['post_name'] ?? '' ),
								array(
									'id' => 'modpress-mod-slug',
									'placeholder' => __( 'my-mod', 'modpress' ),
									'class' => 'w-100',
								)
							); ?>
							<div class="form-text"><?php esc_html_e( 'Used in URLs and internal references.', 'modpress' ); ?></div>
						</div>
						<div class="col-12">
							<div class="row g-3 align-items-end">
								<div class="col-md-4">
									<?php echo FormFieldHelper::label(
										'modpress-mod-author',
										__( 'Mod Author', 'modpress' ),
										array(
											'tooltip' => __( 'Choose the author assigned to this mod.', 'modpress' ),
										)
									); ?>
									<?php if ( $can_edit_others ) : ?>
										<?php echo FormFieldHelper::bootstrap_select(
											'post_author',
											array(
												'data' => $author_options,
												'selected' => (string) $author_id,
												'id' => 'modpress-mod-author',
												'live_search' => true,
											)
										); ?>
									<?php else : ?>
										<?php echo FormFieldHelper::text_input(
											'post_author_display',
											$current_user_name,
											array(
												'id' => 'modpress-mod-author-display',
												'readonly' => true,
											)
										); ?>
										<input type="hidden" name="post_author" value="<?php echo esc_attr( (string) $author_id ); ?>" />
									<?php endif; ?>
									<div class="form-text"><?php esc_html_e( 'This is the WordPress user tied to the mod entry.', 'modpress' ); ?></div>
								</div>
								<div class="col-md-4">
									<?php echo FormFieldHelper::label(
										'modpress-mod-status',
										__( 'Status', 'modpress' ),
										array(
											'tooltip' => __( 'Control the published state of the mod.', 'modpress' )
										)
									); ?>
									<?php echo FormFieldHelper::bootstrap_select(
										'post_status',
										array(
											'data' => $status_options,
											'selected' => (string) ( $mod['post_status'] ?? 'draft' ),
											'id' => 'modpress-mod-status',
										)
									); ?>
									<div class="form-text"><?php esc_html_e( 'Control the published state of the mod.', 'modpress' ); ?></div>
								</div>
								<div class="col-md-4">
									<?php echo FormFieldHelper::label(
										'modpress-mod-development-status',
										__( 'Mod Status', 'modpress' ),
										array(
											'tooltip' => __( 'Track the release phase for this mod.', 'modpress' )
										)
									); ?>
									<?php echo FormFieldHelper::bootstrap_select(
										'mod_status',
										array(
											'data' => $mod_status_options,
											'selected' => $mod_status_selected,
											'id' => 'modpress-mod-development-status',
										)
									); ?>
									<div class="form-text"><?php esc_html_e( 'This status is used by ModPress for lifecycle tracking.', 'modpress' ); ?></div>
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<?php echo FormFieldHelper::label( 
								'modpress-mod-type', 
								__( 'Mod Type', 'modpress' ), 
								array( 
									'tooltip' => __( 'Choose the kind of mod this is.', 'modpress' ) 
								) 
							); ?>
							<?php echo FormFieldHelper::bootstrap_select(
								'mod_type',
								array(
									'data' => $mod_type_options,
									'selected' => $mod_type_selected,
									'id' => 'modpress-mod-type',
								)
							); ?>
							<div class="form-text"><?php esc_html_e( 'This helps organize mods by their content type.', 'modpress' ); ?></div>
						</div>
						<?php
						$group_taxonomies = array();
						if ( class_exists( '\ModPress\Includes\ModManagement\GroupsManager' ) ) {
							$group_taxonomies = ( new GroupsManager() )->get_group_definitions();
							$selected_group_values = array(
								'mod_type' => $mod_type_selected,
								'modpress_type' => $mod_type_selected,
							);
						}
						foreach ( $group_taxonomies as $taxonomy_key => $group_config ) {
							if ( 'modpress_type' === (string) $taxonomy_key ) {
								continue;
							}

							$taxonomy_terms = function_exists( 'get_terms' ) ? get_terms(
								array(
									'taxonomy'   => $taxonomy_key,
									'hide_empty' => false,
									'orderby'    => 'name',
									'order'      => 'ASC',
								)
							) : array();
							if ( is_wp_error( $taxonomy_terms ) || empty( $taxonomy_terms ) ) {
								continue;
							}

							$taxonomy_options = array();
							foreach ( $taxonomy_terms as $term ) {
								$taxonomy_options[ (string) (int) $term->term_id ] = $term->name;
							}

							$group_multiselect = ! empty( $group_config['group_multiselect'] ?? false );
							$taxonomy_selected = '';
							if ( $mod_id > 0 && function_exists( 'wp_get_post_terms' ) ) {
								$assigned_terms = wp_get_post_terms( $mod_id, $taxonomy_key, array( 'fields' => 'ids' ) );
								if ( ! is_wp_error( $assigned_terms ) && ! empty( $assigned_terms ) ) {
									if ( $group_multiselect ) {
										$taxonomy_selected = array_values( array_map( 'strval', array_map( 'intval', $assigned_terms ) ) );
									} else {
										$taxonomy_selected = (string) (int) $assigned_terms[0];
									}
								}
							}
							$selected_group_values[ $taxonomy_key ] = $taxonomy_selected;
							$should_show_group = GroupsManager::should_show_group( $taxonomy_key, $selected_group_values, $group_taxonomies );
							$parent_group = '';
							$parent_entries = array();
							if ( ! empty( $group_config['group_parent'] ?? array() ) ) {
								$parent_group = GroupsManager::resolve_group_field_name( (string) ( $group_config['group_parent'][0] ?? '' ), $group_taxonomies );
								$parent_entries = array_values( array_map( 'strval', (array) ( $group_config['group_parent_entry'] ?? array() ) ) );
							}
							?>
						<div class="col-md-6 modpress-dynamic-group-field <?php echo $should_show_group ? '' : 'd-none'; ?>" data-modpress-group-taxonomy="<?php echo esc_attr( $taxonomy_key ); ?>" data-modpress-parent-group="<?php echo esc_attr( $parent_group ); ?>" data-modpress-parent-entries="<?php echo esc_attr( wp_json_encode( $parent_entries ) ); ?>" data-modpress-group-multiselect="<?php echo esc_attr( $group_multiselect ? '1' : '0' ); ?>">
							<?php echo FormFieldHelper::label(
								$taxonomy_key,
								(string) ( $group_config['label'] ?? ucfirst( str_replace( array( '-', '_' ), ' ', $taxonomy_key ) ) ),
								array(
								)
							); ?>
							<?php echo $group_multiselect ? FormFieldHelper::bootstrap_multiselect(
								$taxonomy_key,
								array(
									'data' => $taxonomy_options,
									'selected' => $taxonomy_selected,
									'id' => sanitize_key( $taxonomy_key ),
									'live_search' => true,
									'show_selected_tags' => true,
								)
							) : FormFieldHelper::bootstrap_select(
								$taxonomy_key,
								array(
									'data' => $taxonomy_options,
									'selected' => $taxonomy_selected,
									'id' => sanitize_key( $taxonomy_key ),
									'live_search' => true,
								)
							); ?>
							<div class="form-text"><?php esc_html_e( $group_config['description'] ?? '', 'modpress' ); ?></div>
						</div>
						<?php }
						?>
						<?php
							$tag_options = array();
							$tag_taxonomy = 'modpress_tag';
							if ( function_exists( 'get_terms' ) ) {
								$tag_terms = get_terms(
									array(
										'taxonomy'   => $tag_taxonomy,
										'hide_empty' => false,
										'orderby'    => 'name',
										'order'      => 'ASC',
									)
								);
								if ( ! is_wp_error( $tag_terms ) && ! empty( $tag_terms ) ) {
									foreach ( $tag_terms as $term ) {
										$tag_options[ (string) (int) $term->term_id ] = $term->name;
									}
								}
							}
							$tag_selected = array();
							if ( $mod_id > 0 && function_exists( 'wp_get_post_terms' ) ) {
								$assigned_tags = wp_get_post_terms( $mod_id, $tag_taxonomy, array( 'fields' => 'ids' ) );
								if ( ! is_wp_error( $assigned_tags ) && ! empty( $assigned_tags ) ) {
									$tag_selected = array_values( array_map( 'strval', array_map( 'intval', $assigned_tags ) ) );
								}
							}
							?>
						<div class="col-md-6">
							<?php echo FormFieldHelper::label(
								'modpress_tag',
								__( 'Tags', 'modpress' ),
								array()
							); ?>
							<?php echo FormFieldHelper::bootstrap_multiselect(
								'modpress_tag',
								array(
									'data' => $tag_options,
									'selected' => $tag_selected,
									'id' => 'modpress-tag',
									'live_search' => true,
									'show_selected_tags' => true,
									'open_options' => true,
									'live_search_placeholder' => 'Search or create tags',
									'placeholder' => 'Search or create tags',
								)
							); ?>
							<div class="form-text"><?php esc_html_e( 'Choose or add tags that describe this mod.', 'modpress' ); ?></div>
						</div>
						<div class="col-12">
							<div class="card border-0 bg-light-subtle">
								<div class="card-body">
									<div class="d-flex align-items-center justify-content-between gap-3 mb-3">
										<div>
											<?php echo FormFieldHelper::label(
												'modpress-dependent-repeater',
												__( 'Dependents', 'modpress' ),
												array(
													'description' => __( 'Add dependent mods or external references for this mod.', 'modpress' ),
												)
											); ?>
										</div>
										<button type="button" class="btn btn-sm btn-outline-primary" data-modpress-dependent-add>
											<?php echo FAHelper::faicon( 'plus' ); ?> <?php esc_html_e( 'Add dependent', 'modpress' ); ?>
										</button>
									</div>
									<div id="modpress-dependent-repeater" class="modpress-dependent-repeater" data-modpress-saved-dependents="<?php echo esc_attr( wp_json_encode( $existing_dependents ) ); ?>">
										<template id="modpress-dependent-template">
											<div class="modpress-dependent-item border rounded p-3 mb-3" data-dependent-index="0">
												<div class="d-flex justify-content-end mb-2">
													<button type="button" class="btn btn-sm btn-outline-danger modpress-dependent-remove" aria-label="<?php esc_attr_e( 'Remove dependent', 'modpress' ); ?>"><?php echo FAHelper::faicon( 'trash' ); ?></button>
												</div>
												<div class="row g-3">
													<div class="col-md-5">
														<?php echo FormFieldHelper::label( 
															'modpress-dependent-type-template', 
															__( 'Type', 'modpress' ), 
															array( 
															) 
														); ?>
														<?php echo FormFieldHelper::bootstrap_select( 
															'dependents[template][type]', 
															array( 
																'data' => array( 
																	'internal' => __( 'Internal', 'modpress' ), 
																	'external' => __( 'External', 'modpress' ) 
																), 
																'selected' => 'internal', 
																'id' => 'modpress-dependent-type-template', 
																'class' => 'modpress-dependent-type w-100' 
															) 
														); ?>
														<div class="form-text"><?php esc_html_e( 'Choose whether this dependent is internal or external.', 'modpress' ); ?></div>
													</div>
													<div class="col-md-7 modpress-dependent-target-wrapper modpress-dependent-target-internal">
														<?php echo FormFieldHelper::label( 
															'modpress-dependent-mod-template', 
															__( 'Internal Mod', 'modpress' ), 
															array( 
															) 
														); ?>
														<?php echo FormFieldHelper::bootstrap_select( 
															'dependents[template][mod_id]', 
															array( 
																'data' => $dependent_mod_options, 
																'selected' => '', 
																'id' => 'modpress-dependent-mod-template', 
																'class' => 'modpress-dependent-internal', 
																'live_search' => true 
															) 
														); ?>
														<div class="form-text"><?php esc_html_e( 'Choose the internal mod to use as a dependent.', 'modpress' ); ?></div>
													</div>
													<div class="col-md-7 modpress-dependent-target-wrapper modpress-dependent-target-external d-none">
														<?php echo FormFieldHelper::label( 
															'modpress-dependent-url-template', 
															__( 'External URL', 'modpress' ), 
															array( 
															) 
														); ?>
														<?php echo FormFieldHelper::text_input( 
															'dependents[template][external_url]', 
															'', 
															array( 
																'type' => 'url', 
																'id' => 'modpress-dependent-url-template', 
																'placeholder' => 'https://example.com', 
																'class' => 'modpress-dependent-external w-100' 
															) 
														); ?>
														<div class="form-text"><?php esc_html_e( 'Enter the external URL to where this  dependency can be found.', 'modpress' ); ?></div>
													</div>
													<div class="col-12">
														<?php echo FormFieldHelper::label( 
															'modpress-dependent-instructions-template', 
															__( 'Instructions', 'modpress' ), 
															array( 
															) 
														); ?>
														<?php echo FormFieldHelper::textarea( 
															'dependents[template][instructions]', 
															'', 
															array( 
																'id' => 'modpress-dependent-instructions-template', 
																'rows' => 3, 
																'placeholder' => __( 'Add any setup, usage, or dependency notes here.', 'modpress' ), 
																'class' => 'modpress-dependent-instructions w-100' 
															) 
														); ?>
														<div class="form-text"><?php esc_html_e( 'Provide any notes or additional instructions for this dependent.', 'modpress' ); ?></div>
													</div>
												</div>
											</div>
										</template>
									</div>
								</div>
							</div>
						</div>
						<div class="col-12">
							<div class="card border-0 bg-light-subtle">
								<div class="card-body">
									<div class="d-flex align-items-center justify-content-between gap-3 mb-3">
										<div>
											<?php echo FormFieldHelper::label(
												'modpress-file-repeater',
												__( 'Files', 'modpress' ),
												array(
													'description' => __( 'Add files for this mod using the internal or external source options.', 'modpress' ),
												)
											); ?>
										</div>
										<button type="button" class="btn btn-sm btn-outline-primary" data-modpress-file-add>
											<?php echo FAHelper::faicon( 'plus' ); ?> <?php esc_html_e( 'Add file', 'modpress' ); ?>
										</button>
									</div>
									<div id="modpress-file-repeater" class="modpress-file-repeater" data-modpress-saved-files="<?php echo esc_attr( wp_json_encode( $existing_files ) ); ?>" data-modpress-file-host-urls="<?php echo esc_attr( wp_json_encode( $file_host_urls ) ); ?>">
										<template id="modpress-file-template">
											<div class="modpress-file-item border rounded p-3 mb-3" data-file-index="0">
												<div class="d-flex justify-content-end mb-2">
													<button type="button" class="btn btn-sm btn-outline-danger modpress-file-remove" aria-label="<?php esc_attr_e( 'Remove file', 'modpress' ); ?>"><?php echo FAHelper::faicon( 'trash' ); ?></button>
												</div>
												<div class="row g-3">
													<div class="col-12">
														<div class="col-md-10">
															<?php echo FormFieldHelper::label( 
																'modpress-file-name-template', 
																__( 'File Name', 'modpress' ) 
															); ?>
															<?php echo FormFieldHelper::text_input( 
																'files[template][name]', 
																'', 
																array( 
																	'id' => 'modpress-file-name-template', 
																	'class' => 'modpress-file-name w-100', 
																	'placeholder' => 'File Name' 
																) 
															); ?>
															<?php echo FormFieldHelper::form_text(
																__( 'Enter the file name.', 'modpress' )
															); ?>
														</div>
														<div class="col-md-2">
															<?php echo FormFieldHelper::label( 
																'modpress-file-version-template', 
																__( 'File Version', 'modpress' ) 
															); ?>
															<?php echo FormFieldHelper::text_input( 
																'files[template][version]', 
																'', 
																array( 
																	'id' => 'modpress-file-version-template', 
																	'class' => 'modpress-file-version w-100', 
																	'placeholder' => 'File Version' 
																) 
															); ?>
															<?php echo FormFieldHelper::form_text(
																__( 'Enter the file version.', 'modpress' )
															); ?>
														</div>
													</div>
												</div>
												<div class="row g-3">
													<div class="col-12">
														<div class="col-md-10">
															<?php echo FormFieldHelper::label( 
																'modpress-file-description-template', 
																__( 'Description', 'modpress' ) 
															); ?>
															<?php echo FormFieldHelper::textarea( 
																'files[template][description]', 
																'', 
																array( 
																	'id' => 'modpress-file-description-template', 
																	'class' => 'modpress-file-description w-100', 
																	'rows' => 3, 
																	'placeholder' => __( 'Describe the file contents or release notes.', 'modpress' ) 
																) 
															); ?>
															<?php echo FormFieldHelper::form_text(
																__( 'Use this place to describe the file contents or provide instructions on how to use the file.', 'modpress' )
															); ?>
														</div>
													</div>
												</div>
												<div class="row g-3">
													<div class="col-align-self-end d-flex">
														<div class="btn-group btn-group-sm" role="group" aria-label="<?php esc_attr_e( 'File Types', 'modpress' ); ?>">
															<?php echo FormFieldHelper::button(
																FAHelper::faicon( 'plus' ).' '.__('External File', 'modpress'),
																array(
																	'type' => 'button',
																	'class' => 'btn btn-outline-secondary',
																	'attributes' => array(
																		'data-file-add' => 'external',
																	),
																)
															); ?>
															<?php echo FormFieldHelper::button(
																FAHelper::faicon( 'plus' ).' '.__('Internal File', 'modpress'),
																array(
																	'type' => 'button',
																	'class' => 'btn btn-outline-danger',
																	'attributes' => array(
																		'data-file-add' => 'internal',
																	),
																)
															); ?>
														</div>
													</div>
													<div class="row g-3">
														<div class="col-md-10">
															<?php echo WPMediaHelper::create(
																'',
																array(
																	'name' => 'files[template][attachment_id]',
																	'id' => 'modpress-file-attachment-template',
																	'button_text' => __( 'Select File', 'modpress' ),
																	'show_remove' => true,
																	'media_type' => 'file',
																	'return_value' => 'id',
																	'helper_text' => __( 'Pick a file from the Media Library.', 'modpress' ),
																),
																array(
																	'alt' => __( 'Uploaded file preview', 'modpress' ),
																)
															); ?>
														</div>
														<div class="col-md-2">
															<button type="button" class="btn btn-sm btn-outline-primary" data-modpress-file-target-internal-delete>
																<?php echo FAHelper::faicon( 'trash' ); ?>
															</button>
														</div>
													</div>
												</div>

												<div class="row g-3 modpress-file-target-wrapper modpress-file-target-external d-none">
													<div class="col-md-3">
														<?php echo FormFieldHelper::label( 
															'modpress-file-host-selector-template', 
															__( 'Host', 'modpress' ) 
														); ?>
														<?php echo FormFieldHelper::bootstrap_select(
															'files[template][host_id]',
															array(
																'data' => $file_host_options,
																'selected' => '',
																'id' => 'modpress-file-host-selector-template',
																'class' => 'modpress-file-host-selector w-100',
																'live_search' => true,
																'placeholder' => __( 'Select host', 'modpress' ),
															)
														); ?>
													</div>
													<div class="col-md-7">
														<?php echo FormFieldHelper::label(
															'modpress-file-host-url-template',
															__( 'Download URL', 'modpress' )
														); ?>
														<?php echo FormFieldHelper::input_group(
															'<span class="input-group-text modpress-file-host-url-display" id="modpress-file-host-url-template">https://</span>'
																	. '<input type="hidden" name="files[template][host_url]" class="modpress-file-host-url" value="" />'
																	. FormFieldHelper::text_input(
																'files[template][url_suffix]',
																'',
																array(
																	'id' => 'modpress-file-url-suffix-template',
																	'class' => 'modpress-file-url-suffix w-90',
																	'placeholder' => 'path/to/file/link/',
																)
															),
															array(
																'attributes' => array(
																	'class' => 'flex-nowrap',
																	'role' => 'group',
																	'aria-label' => __( 'Download URL', 'modpress' ),
																),
															)
														); ?>
														<?php echo FormFieldHelper::form_text(
															__('Please provide the download URL for the external file.', 'modpress')
														); ?>
													</div>
													<div class="col-md-2">
														<?php echo FormFieldHelper::button(
															'',
															array(
																'type' => 'button',
																'class' => 'btn btn-sm btn-outline-primary',
																'data-modpress-file-target-external-delete' => true,
															),
															FAHelper::faicon( 'trash' )
														); ?>
													</div>
												</div>
											</div>
										</template>
									</div>
								</div>
							</div>
						</div>
						<div class="col-12">
						</div>
						<div class="col-12">
							<?php echo FormFieldHelper::label( 
								'modpress-mod-excerpt', 
								__( 'Short Description', 'modpress' ), 
								array( 
								) 
							); ?>
							<?php echo TinyMCEHelper::render( 
								'modpress-mod-excerpt', 
								'post_excerpt', 
								__( 'Short Description', 'modpress' ), 
								(string) ( $mod['post_excerpt'] ?? '' ), 
								12, 
								false,
								array()
							); ?>
							<div class="form-text"><?php esc_html_e( 'A brief summary shown in listings and previews.', 'modpress' ); ?></div>
						</div>
						<div class="col-12">
							<?php echo TinyMCEHelper::render( 
								'modpress-mod-content', 
								'post_content', 
								__( 'Long Description', 'modpress' ), 
								(string) ( $mod['post_content'] ?? '' ), 
								12, 
								false,
								array()
							); ?>
							<div class="form-text"><?php esc_html_e( 'The full description of the mod.', 'modpress' ); ?></div>
						</div>
						<div class="col-12">
							<?php echo FormFieldHelper::label( 
								'featured_image', 
								__( 'Mod Header', 'modpress' ), 
								array( 
									'tooltip' => __( 'Set the image that appears at the top of the mod page.', 'modpress' ), 
									'description' => __( 'Use a wide image to represent the mod.', 'modpress' ) 
								) 
							); ?>
							<?php echo WPMediaHelper::create(
								$featured_image,
								array(
									'name' => 'featured_image',
									'id' => 'featured_image',
									'button_text' => __( 'Choose Header Image', 'modpress' ),
									'show_remove' => true,
									'media_type' => 'image',
								),
								array(
									'alt' => __( 'Mod header preview', 'modpress' ),
								)
							); ?>
							<div class="form-text"><?php esc_html_e( 'Use a wide image to represent the mod.', 'modpress' ); ?></div>
						</div>
						<div class="col-12">
							<?php echo FormFieldHelper::label(
								'mod_logo',
								__( 'Mod Logo', 'modpress' ),
								array(
									'tooltip' => __( 'Set the square logo that represents the mod in listings and cards.', 'modpress' ),
									'description' => __( 'Use a square image for the mod branding.', 'modpress' )
								)
							); ?>
							<?php echo WPMediaHelper::create(
								$mod_logo,
								array(
									'name' => 'mod_logo',
									'id' => 'mod_logo',
									'button_text' => __( 'Choose Logo', 'modpress' ),
									'show_remove' => true,
									'media_type' => 'image',
								),
								array(
									'alt' => __( 'Mod logo preview', 'modpress' ),
								)
							); ?>
							<div class="form-text"><?php esc_html_e( 'Use a square image for the mod branding.', 'modpress' ); ?></div>
						</div>
						<div class="col-12">
							<?php echo FormFieldHelper::label(
								'mod_gallery',
								__( 'Mod Gallery', 'modpress' ),
								array(
									'tooltip' => __( 'Attach a gallery of images that showcase the mod.', 'modpress' ),
									'description' => __( 'Choose a gallery image or store a list of gallery IDs.', 'modpress' )
								)
							); ?>
							<?php echo WPMediaHelper::create(
								! empty( $mod_gallery ) ? implode( ',', array_map( 'strval', $mod_gallery ) ) : '',
								array(
									'name' => 'mod_gallery',
									'id' => 'mod_gallery',
									'button_text' => __( 'Choose Gallery Images', 'modpress' ),
											'show_remove' => true,
											'media_type' => 'image',
											'multiple' => true,
											'return_value' => 'id',
								),
								array(
									'alt' => __( 'Mod gallery preview', 'modpress' ),
								)
							); ?>
							<div class="form-text"><?php esc_html_e( 'Store gallery IDs for the mod showcase.', 'modpress' ); ?></div>
						</div>
						<div class="col-12 d-flex justify-content-end gap-2">
							<?php echo FormFieldHelper::button( 
								$mod_id > 0 ? __( 'Update Mod', 'modpress' ) : __( 'Create Mod', 'modpress' ), 
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
		<?php
	}
}
