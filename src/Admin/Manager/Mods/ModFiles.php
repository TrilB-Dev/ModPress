<?php
/**
 * Files screen for the ModPress mod manager.
 *
 * @package ModPress
 * @subpackage Admin\Manager\Mods
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\Mods;

use ModPress\Includes\ModManagement\FileManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ModFiles extends ModManager {
	/**
	 * Render the mod file manager overview.
	 *
	 * @return void
	 */
	public function render_page_content(): void {
		$file_service = new FileManager();
		$mods         = get_posts(
			array(
				'post_type'      => array( 'modpress_mod', 'mod' ),
				'post_status'    => 'any',
				'posts_per_page' => 10,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		?>
		<div class="card shadow-sm border-0">
			<div class="card-body p-4">
				<div class="d-flex align-items-center justify-content-between gap-3 mb-4">
					<h2 class="h5 mb-0"><?php esc_html_e( 'Mod Files', 'modpress' ); ?></h2>
					<button class="btn btn-primary btn-sm" type="button"><?php esc_html_e( 'Upload file', 'modpress' ); ?></button>
				</div>
				<?php if ( empty( $mods ) ) : ?>
					<p class="text-muted mb-0"><?php esc_html_e( 'No mods exist yet, so there are no files to manage.', 'modpress' ); ?></p>
				<?php else : ?>
					<div class="table-responsive">
						<table class="table align-middle mb-0">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Mod', 'modpress' ); ?></th>
									<th><?php esc_html_e( 'Files', 'modpress' ); ?></th>
									<th><?php esc_html_e( 'Status', 'modpress' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $mods as $mod ) : ?>
									<?php $files = $file_service->get_files( (int) $mod->ID ); ?>
									<tr>
										<td><?php echo esc_html( get_the_title( $mod ) ); ?></td>
										<td><?php echo esc_html( count( $files ) ); ?></td>
										<td><?php echo esc_html( ucfirst( $mod->post_status ) ); ?></td>
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
}
