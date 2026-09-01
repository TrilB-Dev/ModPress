<?php
/**
 * Header UI component for ModPress admin pages.
 *
 * @package ModPress
 * @subpackage Admin\Manager\UI
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\UI;

use ModPress\Assets\Assets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Header {
	/**
	 * Renders the header for ModPress admin pages.
	 *
	 * @return void
	 */
	public static function render(): void {
		$links = [
					[ 
					'label' => __( 'Documentation', 'modpress' ), 
					'url' => 'https://trilb.dev/collection/web-extension/wordpress/modpress' 
					],
					[ 
					'label' => __( 'Community', 'modpress' ), 
					'url' => 'https://trilb.dev/community' 
					],
					[ 
					'label' => __( 'Extensions', 'modpress' ), 
					'url' => 'https://trilb.dev/extensions' 
					],
					[ 
					'label' => __( 'Support', 'modpress' ), 
					'url' => 'https://trilb.dev/support' 
					],
					[ 
					'label' => __( 'Roadmap', 'modpress' ), 
					'url' => 'https://trilb.dev/roadmap' 
					],
					[ 
					'label' => __( 'Account', 'modpress' ), 
					'url' => 'https://trilb.dev/account' 
					],
			];
		?>
		<header class="modpress-header border-bottom">
			<nav class="navbar navbar-expand-lg" aria-label="<?php esc_attr_e( 'ModPress header navigation', 'modpress' ); ?>">
				<div class="container-fluid modpress-shell px-3 px-lg-4">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=modpress' ) ); ?>">
						<img class="navbar-brand d-flex align-items-center gap-2" src="<?php echo esc_url( Assets::get_image( 'logo/ModPress-Logo.svg' ) ); ?>" alt="" />
					</a>
					<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#modpress-header-menu" aria-controls="modpress-header-menu" aria-expanded="false" aria-label="<?php esc_attr_e( 'Toggle header navigation', 'modpress' ); ?>">
						<span class="navbar-toggler-icon"></span>
					</button>
					<div class="collapse navbar-collapse" id="modpress-header-menu">
						<ul class="navbar-nav ms-auto align-items-lg-start gap-lg-1">
							<?php foreach ( $links as $link ) : ?>
								<li class="nav-item"><a class="nav-link" href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $link['label'] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
				</div>
			</nav>
		</header>
		<?php
	}
}
