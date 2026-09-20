<?php
/**
 * Manager class for ModPress plugin.
 *
 * @package ModPress
 * @subpackage Admin\Manager
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager;

use ModPress\Assets\Assets;
use ModPress\Admin\Manager\UI\Footer;
use ModPress\Admin\Manager\UI\Header;
use ModPress\Admin\Manager\UI\Sidebar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Manager {
	/**
	 * The current manager section key.
	 *
	 * @var string
	 */
	protected string $page = '';

	/**
	 * Get the manager section key.
	 *
	 * @return string
	 */
	public function get_page(): string {
		return $this->page;
	}

	/**
	 * Register page assets for this manager.
	 *
	 * Subclasses should override this with their own asset registration.
	 *
	 * @param Assets $assets Asset registry.
	 * @return void
	 */
	public function register_assets( Assets $assets ): void {
		// Intentionally left empty; section managers override this when needed.
	}

	/**
	 * Normalize a manager route alias to a canonical key.
	 *
	 * @param string $value Raw group or tab value.
	 * @param array<string, string> $aliases Supported aliases.
	 * @param string $fallback Default value.
	 * @return string
	 */
	protected function normalize_route( string $value, array $aliases, string $fallback = '' ): string {
		$key = sanitize_key( $value );
		if ( '' === $key ) {
			return $fallback;
		}

		return $aliases[ $key ] ?? $fallback;
	}

	/**
	 * Register one asset bundle for a group of admin pages.
	 *
	 * @param Assets             $assets Asset registry.
	 * @param array<int, string> $pages Admin page slugs.
	 * @param string             $bundle Compiled bundle name.
	 * @return void
	 */
	protected function register_page_assets( Assets $assets, array $pages, string $bundle ): void {
		foreach ( $pages as $page ) {
			$assets->register_page( $page, $this->assets( $bundle ) );
		}
	}

	/**
	 * Build the asset definition for an admin bundle.
	 *
	 * @param string $bundle Compiled bundle name.
	 * @return array<string, array<int, array<string, mixed>>> Asset definition.
	 */
	protected function assets( string $bundle ): array {
		$bundle_name = $this->resolve_bundle_name( $bundle );
		$style_name  = $this->resolve_style_bundle_name( $bundle );

		return array(
			'styles'  => array(
				array(
					'handle' => 'modpress-admin-' . $bundle,
					'src'    => MODPRESS_ASSETS_URL . '/dist/css/' . $style_name . '.css',
					'deps'   => array( 'modpress-bootstrap', 'modpress-admin-ui' ),
				),
			),
			'scripts' => array(
				array(
					'handle'    => 'modpress-admin-' . $bundle,
					'src'       => MODPRESS_ASSETS_URL . '/dist/js/' . $bundle_name . '.js',
					'deps'      => array( 'modpress-bootstrap', 'modpress-admin-ui' ),
					'in_footer' => true,
				),
			),
		);
	}

	/**
	 * Map the logical bundle name to the compiled asset file name.
	 *
	 * @param string $bundle Logical bundle name.
	 * @return string Compiled bundle file name.
	 */
	protected function resolve_bundle_name( string $bundle ): string {
		if ( '' === trim( $bundle ) ) {
			return 'admin.ui';
		}

		if ( false !== strpos( $bundle, '.' ) ) {
			return $bundle;
		}

		$mapping = array(
			'dashboard' => 'admin.dashboard',
			'mods'     => 'admin.mods',
			'settings'  => 'admin.settings',
			'tools'     => 'admin.tools',
			'plugins'   => 'admin.plugins',
			'ui'        => 'admin.ui',
		);

		return $mapping[ $bundle ] ?? ( 'admin.' . $bundle );
	}

	/**
	 * Map the logical bundle to the shared compiled CSS file.
	 *
	 * All admin entry styles are emitted into the shared admin.ui.css bundle.
	 *
	 * @param string $bundle Logical bundle name.
	 * @return string Compiled stylesheet bundle file name.
	 */
	protected function resolve_style_bundle_name( string $bundle ): string {
		if ( '' === trim( $bundle ) ) {
			return 'admin.ui';
		}

		return 'admin.ui';
	}

	/**
	 * Render the shared admin page header.
	 *
	 * @param string $title Page title.
	 * @return void
	 */
	protected function header( string $title ): void {
		echo '<div class="wrap modpress-admin">';
		Header::render();
		?>
		<main class="modpress-admin-main">
			<div class="container-fluid modpress-shell px-3 px-lg-4 py-4">
				<div class="row g-4">
					<?php Sidebar::render(); ?>
					<section class="col-12 col-lg flex-grow-1" aria-labelledby="modpress-page-title">
						<div class="modpress-page-heading d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
							<h1 class="h1 mb-0" id="modpress-page-title"><?php echo esc_html( $title ); ?></h1>
						</div>
		<?php
	}

	/**
	 * Render the shared admin page footer.
	 *
	 * @return void
	 */
	protected function footer(): void {
		Footer::render();
		echo '</div>';
	}

	/**
	 * Render a dashboard statistic card.
	 *
	 * @param string $label Card label.
	 * @param mixed  $value Card value.
	 * @param string $slug Destination admin page slug.
	 * @return void
	 */
	protected function card( string $label, $value, string $slug ): void {
		printf(
			'<div class="col-md-6 col-xl-3 mb-4"><div class="card modpress-dashboard-card h-100 shadow-sm"><div class="card-body"><h2 class="h6 text-muted">%s</h2><p class="display-6 mb-0"><a class="text-decoration-none" href="%s">%s</a></p></div></div></div>',
			esc_html( $label ),
			esc_url( admin_url( 'admin.php?page=' . $slug ) ),
			esc_html( (string) $value )
		);
	}
}