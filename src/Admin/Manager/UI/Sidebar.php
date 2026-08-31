<?php
/**
 * Sidebar UI component for ModPress admin pages.
 *
 * @package ModPress
 * @subpackage Admin\Manager\UI
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\UI;

use ModPress\Includes\Functions\Admin\FunctionsSidebar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the sidebar from the centralized FunctionsSidebar menu model.
 */
final class Sidebar {
	/**
	 * Render the admin sidebar.
	 *
	 * @return void
	 */
	public static function render(): void {
		$current = sanitize_key( $_GET['page'] ?? 'modpress' );
		$groups  = FunctionsSidebar::get_sidebar_groups();
		?>
		<aside class="col-12 col-lg-auto modpress-sidebar-column">
			<div class="modpress-sidebar position-sticky" style="top: 32px;">
				<div class="d-flex align-items-center justify-content-between mb-3 px-2">
					<span class="small text-uppercase fw-semibold text-secondary"><?php esc_html_e( 'Navigate', 'modpress' ); ?></span>
					<span class="badge rounded-pill text-bg-light">WP</span>
				</div>
				<nav aria-label="<?php esc_attr_e( 'ModPress admin navigation', 'modpress' ); ?>">
					<a class="modpress-sidebar-link <?php echo 'modpress' === $current ? 'active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=modpress' ) ); ?>">
						<span class="modpress-sidebar-icon" aria-hidden="true"><i class="fa-solid fa-house"></i></span><?php esc_html_e( 'Dashboard', 'modpress' ); ?>
					</a>
					<div id="modpress-sidebar-groups">
						<?php foreach ( $groups as $key => $group ) : ?>
							<?php $expanded = self::group_is_expanded( $key, $group, $current ); ?>
							<div class="modpress-sidebar-group">
								<h3 class="modpress-sidebar-group-heading">
									<button class="modpress-sidebar-link modpress-sidebar-group-link border-0 bg-transparent w-100 text-start <?php echo $expanded ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#modpress-group-<?php echo esc_attr( $key ); ?>" aria-expanded="<?php echo $expanded ? 'true' : 'false'; ?>" aria-controls="modpress-group-<?php echo esc_attr( $key ); ?>">
										<span class="modpress-sidebar-icon" aria-hidden="true"><i class="<?php echo esc_attr( $group['icon'] ); ?>"></i></span><?php echo esc_html( $group['label'] ); ?><span class="ms-auto text-secondary"><?php echo count( $group['items'] ); ?></span>
									</button>
								</h3>
								<div id="modpress-group-<?php echo esc_attr( $key ); ?>" class="collapse <?php echo $expanded ? 'show' : ''; ?>">
									<div class="nav flex-column modpress-sidebar-group-items">
										<?php foreach ( $group['items'] as $slug => $item ) : ?>
											<?php $page = self::item_page( $slug ); $query = self::item_query( $slug ); $active = self::item_is_active( $page, $query, $current ); ?>
											<a class="nav-link <?php echo $active ? 'active' : ''; ?>" <?php echo $active ? 'aria-current="page"' : ''; ?> href="<?php echo esc_url( self::item_url( $page, $query ) ); ?>"><i class="<?php echo esc_attr( $item['icon'] ); ?> me-2" aria-hidden="true"></i><?php echo esc_html( $item['label'] ); ?></a>
										<?php endforeach; ?>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</nav>
			</div>
		</aside>
		<?php
	}

	/** @param array<string, mixed> $group */
	private static function group_is_expanded( string $key, array $group, string $current ): bool {
		if ( 'settings' === $key ) {
			return 'modpress-settings' === $current;
		}
		if ( 'tools' === $key ) {
			return 'modpress-tools' === $current;
		}

		foreach ( $group['items'] as $slug => $item ) {
			if ( self::item_is_active( self::item_page( $slug ), self::item_query( $slug ), $current ) ) {
				return true;
			}
		}

		return false;
	}

	private static function item_page( string $slug ): string {
		return strtok( $slug, '&' );
	}

	/** @return array<string, string> */
	private static function item_query( string $slug ): array {
		$query = [];
		parse_str( (string) strstr( $slug, '&' ), $query );
		return $query;
	}

	/** @param array<string, string> $query */
	private static function item_url( string $page, array $query ): string {
		return admin_url( 'admin.php?page=' . $page . ( empty( $query ) ? '' : '&' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 ) ) );
	}

	/** @param array<string, string> $query */
	private static function item_is_active( string $page, array $query, string $current ): bool {
		if ( $page !== $current ) {
			return false;
		}

		foreach ( $query as $key => $value ) {
			if ( (string) ( $_GET[ $key ] ?? '' ) !== (string) $value ) {
				return false;
			}
		}

		return true;
	}
}
