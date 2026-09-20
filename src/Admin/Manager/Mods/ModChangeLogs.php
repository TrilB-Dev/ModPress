<?php
/**
 * Changelog screen for the ModPress mod manager.
 *
 * @package ModPress
 * @subpackage Admin\Manager\Mods
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\Mods;

use ModPress\Includes\ModManagement\ChangeLogManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ModChangeLogs extends ModManager {
	/**
	 * Render the changelog overview.
	 *
	 * @return void
	 */
	public function render_page_content(): void {
		$mod_service = new ChangeLogManager();
		$mods        = get_posts(
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
					<h2 class="h5 mb-0"><?php esc_html_e( 'Change Logs', 'modpress' ); ?></h2>
					<button class="btn btn-primary btn-sm" type="button"><?php esc_html_e( 'Add entry', 'modpress' ); ?></button>
				</div>
				<?php if ( empty( $mods ) ) : ?>
					<p class="text-muted mb-0"><?php esc_html_e( 'No mod changelog entries are available yet.', 'modpress' ); ?></p>
				<?php else : ?>
					<div class="list-group">
						<?php foreach ( $mods as $mod ) : ?>
							<?php $entries = $mod_service->get_entries( (int) $mod->ID ); ?>
							<div class="list-group-item">
								<div class="d-flex justify-content-between gap-3">
									<strong><?php echo esc_html( get_the_title( $mod ) ); ?></strong>
									<span class="text-muted"><?php echo esc_html( count( $entries ) ); ?> <?php esc_html_e( 'entries', 'modpress' ); ?></span>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
