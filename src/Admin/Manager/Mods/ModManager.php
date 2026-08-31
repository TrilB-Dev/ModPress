<?php

namespace ModPress\Admin\Manager\Mods;

use ModPress\Admin\Manager\Manager;
use ModPress\Assets\Assets;
use ModPress\Includes\Core\PostType;
use ModPress\Includes\Core\Taxonomy;
use ModPress\Includes\Core\Editor;
use ModPress\Includes\Functions\Helpers\PostHelper;
use ModPress\Includes\Functions\Helpers\QueryHelper;
use ModPress\Includes\Functions\Helpers\TaxonomyHelper;
use ModPress\Includes\Functions\Admin\FunctionsMod;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ModManager extends Manager {
	private FunctionsMod $mod_functions;

	public function __construct( FunctionsMod $mod_functions ) {
		$this->mod_functions = $mod_functions;
	}

	public function register_assets( Assets $assets ): void {
		$this->register_page_assets( $assets, [ 'modpress-manage' ], 'mod' );
		$this->register_page_assets( $assets, [ 'modpress-manage' ], 'navbuilder' );
		add_action( 'admin_enqueue_scripts', static function (): void {
			if ( 'modpress-manage' === sanitize_key( $_GET['page'] ?? '' ) ) {
				wp_enqueue_media();
			}
		} );
	}

	public function render(): void {
		$mod_action = sanitize_key( wp_unslash( $_GET['mod'] ?? '' ) );
		if ( 'new' === $mod_action ) {
			$this->render_new_mod();
			return;
		}
		if ( in_array( $mod_action, [ 'page-new', 'page-edit' ], true ) ) {
			$this->render_page_editor();
			return;
		}

		$this->header( __( 'Manage Mod', 'modpress' ) );
		$mods = get_posts( [
			'post_type'      => PostType::MOD,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );
		?>
		<div class="row g-4">
			<?php if ( $mods ) : ?>
				<?php foreach ( $mods as $mod ) : ?>
					<?php $this->render_mod_card( $mod ); ?>
					<?php ModForms::render_modals( $mod ); ?>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="col-12">
					<div class="card border-0 shadow-sm">
						<div class="card-body p-4">
							<h2 class="h5"><?php esc_html_e( 'No Mods created yet', 'modpress' ); ?></h2>
							<p class="text-secondary mb-3"><?php esc_html_e( 'Create your first Mod to start organising your knowledge.', 'modpress' ); ?></p>
							<a class="btn btn-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=modpress-manage&mod=new' ) ); ?>"><?php esc_html_e( 'Get Started', 'modpress' ); ?></a>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
		$this->footer();
	}

	private function render_new_mod(): void {
		$notice = $this->mod_functions->save_mod();
		$this->header( __( 'Create a New Mod', 'modpress' ) );
		if ( $notice ) {
			echo wp_kses_post( $notice );
		}
		$categories = TaxonomyHelper::terms( Taxonomy::CATEGORY );
		$tags = TaxonomyHelper::terms( Taxonomy::TAG );
		$fields = apply_filters( 'modpress_mod_form_fields', '', null );
		ModForms::render_new_mod_form( $categories, $tags, $fields );
		$this->footer();
	}

	private function render_page_editor(): void {
		$mod_id = absint( wp_unslash( $_GET['mod_id'] ?? 0 ) );
		$page_id = absint( wp_unslash( $_GET['page_id'] ?? 0 ) );
		$page = $page_id ? get_post( $page_id ) : null;
		if ( $page && ( ! PostHelper::is_mod_page( $page ) || (int) get_post_meta( $page_id, '_modpress_mod_id', true ) !== $mod_id ) ) {
			$page = null;
		}
		if ( Editor::save_mod_page( $mod_id, $page_id ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=modpress-manage' ) );
			exit;
		}
		$this->header( $page ? __( 'Edit Mod Page', 'modpress' ) : __( 'Create Mod Page', 'modpress' ) );
		Editor::render_mod_page_form( $page );
		$this->footer();
	}

	private function render_mod_card( \WP_Post $mod ): void {
		$page_count = QueryHelper::posts( [
			'post_type'      => PostType::PAGE,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => false,
			'meta_key'       => '_modpress_mod_id',
			'meta_value'     => $mod->ID,
		] )->found_posts;
		$image_url = get_the_post_thumbnail_url( $mod, 'medium' );
		$logo_id = absint( get_post_meta( $mod->ID, '_modpress_logo_id', true ) );
		$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
		$author_url = get_avatar_url( $mod->post_author, [ 'size' => 64 ] );
		$description = wp_trim_words( wp_strip_all_tags( $mod->post_excerpt ?: $mod->post_content ), 24 );
		$settings_id = 'modpress-mod-settings-' . $mod->ID;
		$manage_id = 'modpress-mod-manage-' . $mod->ID;
		?>
		<div class="col-12 col-md-6 col-xl-4 d-flex">
			<article class="card modpress-mod-card text-start shadow-sm h-100 w-100">
				<div class="card-header d-flex align-items-center justify-content-between gap-2">
					<span class="fw-semibold"><?php echo esc_html( get_the_title( $mod ) ); ?></span>
					<span class="badge text-bg-<?php echo 'publish' === $mod->post_status ? 'success' : 'secondary'; ?>"><?php echo esc_html( ucfirst( $mod->post_status ) ); ?></span>
				</div>
				<div class="card-body d-flex flex-column">
					<?php if ( $logo_url ) : ?>
						<img src="<?php echo esc_url( $logo_url ); ?>" class="modpress-mod-image rounded mx-auto d-block mb-3" alt="<?php echo esc_attr( get_the_title( $mod ) ); ?>">
					<?php elseif ( $image_url ) : ?>
						<img src="<?php echo esc_url( $image_url ); ?>" class="modpress-mod-image rounded mx-auto d-block mb-3" alt="<?php echo esc_attr( get_the_title( $mod ) ); ?>">
					<?php else : ?>
						<div class="modpress-mod-image modpress-mod-image-placeholder rounded mx-auto d-flex align-items-center justify-content-center mb-3" aria-hidden="true"><span class="dashicons dashicons-book-alt"></span></div>
					<?php endif; ?>
					<p class="card-text text-secondary"><?php echo esc_html( $description ?: __( 'No description provided yet.', 'modpress' ) ); ?></p>
					<div class="d-flex align-items-center gap-2 mb-3">
						<img src="<?php echo esc_url( $author_url ); ?>" class="modpress-author-image rounded-circle" alt="">
						<p class="card-text mb-0"><span class="text-secondary"><?php esc_html_e( 'Author:', 'modpress' ); ?></span> <?php echo esc_html( get_the_author_meta( 'display_name', $mod->post_author ) ); ?></p>
					</div>
					<p class="card-text mb-2"><span class="text-secondary"><?php esc_html_e( 'Created:', 'modpress' ); ?></span> <?php echo esc_html( get_the_date( '', $mod ) ); ?></p>
					<div class="d-flex justify-content-between gap-3 mt-auto">
						<p class="card-text mb-0"><span class="text-secondary"><?php esc_html_e( 'Pages:', 'modpress' ); ?></span> <?php echo esc_html( number_format_i18n( $page_count ) ); ?></p>
						<p class="card-text mb-0"><span class="text-secondary"><?php esc_html_e( 'Visitors:', 'modpress' ); ?></span> 0</p>
					</div>
				</div>
				<div class="card-footer d-flex flex-wrap gap-2">
					<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#<?php echo esc_attr( $settings_id ); ?>"><?php esc_html_e( 'Settings', 'modpress' ); ?></button>
					<?php /* translators: %s is the title of the Mod. */ ?>
					<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#<?php echo esc_attr( $manage_id ); ?>"><?php printf( esc_html__( 'Manage %s', 'modpress' ), esc_html( get_the_title( $mod ) ) ); ?></button>
					<?php /* translators: %s is the title of the Mod. */ ?>
					<button type="button" class="btn btn-outline-danger btn-sm ms-auto" data-modpress-delete-mod="<?php echo esc_attr( $mod->ID ); ?>"><?php printf( esc_html__( 'Delete %s', 'modpress' ), esc_html( get_the_title( $mod ) ) ); ?></button>
				</div>
			</article>
		</div>
		<?php
	}
}
