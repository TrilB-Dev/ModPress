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
use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\Functions\Helpers\RequestHelper;
use ModPress\Includes\Functions\Helpers\SanitizationHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Sidebar {
	/**
	 * Render the admin sidebar.
	 *
	 * @return void
	 */
	public static function render(): void {
		$current = RequestHelper::get_key( 'page', 'modpress' );
		$groups  = FunctionsSidebar::get_sidebar_groups();
		?>
		<aside class="col-12 col-lg-auto modpress-sidebar-column">
			<div class="modpress-sidebar position-sticky" style="top: 32px;">
				<div class="d-flex align-items-center justify-content-between mb-3 px-2">
					<span class="small text-uppercase fw-semibold text-secondary">
						<?php esc_html_e( 'Navigate', 'modpress' ); ?>
					</span>
					<span class="badge rounded-pill text-bg-light">WP</span>
				</div>
				<nav aria-label="<?php esc_attr_e( 'ModPress admin navigation', 'modpress' ); ?>">
					<a class="modpress-sidebar-link <?php echo 'modpress' === $current ? 'active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=modpress' ) ); ?>">
						<?php echo self::render_icon_markup( 'fa-solid fa-house' ); ?><?php esc_html_e( 'Dashboard', 'modpress' ); ?>
					</a>
					<div id="modpress-sidebar-groups">
						<?php foreach ( $groups as $key => $group ) : ?>
							<?php $expanded = self::group_is_expanded( $key, $group, $current ); ?>
							<div class="modpress-sidebar-group">
								<h3 class="modpress-sidebar-group-heading">
										<?php echo FormFieldHelper::button(
											self::render_icon_markup( (string) ( $group['icon'] ?? '' ) ) . esc_html( $group['label'] ) . '<span class="ms-auto text-secondary">' . count( $group['items'] ) . '</span>',
											array(
												'class'          => 'modpress-sidebar-link modpress-sidebar-group-link border-0 bg-transparent w-100 text-start ' . ( $expanded ? '' : 'collapsed' ),
												'type'           => 'button',
												'data-bs-toggle' => 'collapse',
												'data-bs-target' => '#modpress-group-' . esc_attr( $key ),
												'aria-expanded'  => $expanded ? 'true' : 'false',
												'aria-controls'  => 'modpress-group-' . esc_attr( $key ),
												'raw'            => true,
											)
										); ?>
								</h3>
								<div id="modpress-group-<?php echo esc_attr( $key ); ?>" class="collapse <?php echo $expanded ? 'show' : ''; ?>">
									<div class="nav flex-column modpress-sidebar-group-items">
										<?php foreach ( $group['items'] as $slug => $item ) : ?>
											<?php
										$link   = self::item_link( $slug, $item );
										$page   = self::item_page( $link );
										$query  = self::item_query( $link );
											$active = self::item_is_active( $page, $query, $current );
											?>
											<a class="nav-link <?php echo $active ? 'active' : ''; ?>" <?php echo $active ? 'aria-current="page"' : ''; ?> href="<?php echo esc_url( self::item_url( $page, $query ) ); ?>"><?php echo self::render_icon_markup( (string) ( $item['icon'] ?? '' ), true ); ?><?php echo esc_html( $item['label'] ); ?></a>
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
	/**
	 * Render the icon markup for a sidebar item.
	 *
	 * @param string $icon The icon class or URL.
	 * @param bool $with_spacing Whether to add spacing to the icon.
	 * @return string The HTML markup for the icon.
	 */
	private static function render_icon_markup( string $icon, bool $with_spacing = false ): string {
		$icon = trim( $icon );
		if ( '' === $icon ) {
			return '';
		}

		if ( preg_match( '/^(https?:)?\/\//i', $icon ) || preg_match( '/\.(svg|png|jpg|jpeg|webp)(\?.*)?$/i', $icon ) ) {
			return sprintf(
				'<span class="modpress-sidebar-icon modpress-sidebar-icon-image" aria-hidden="true"><img src="%1$s" alt="" loading="lazy"%2$s /></span>',
				esc_url( $icon ),
				$with_spacing ? ' class="me-2"' : ''
			);
		}

		return sprintf(
			'<span class="modpress-sidebar-icon" aria-hidden="true"><i class="%1$s%2$s"></i></span>',
			esc_attr( $icon ),
			$with_spacing ? ' me-2' : ''
		);
	}

	/**
	 * Determine if a sidebar group should be expanded.
	 *
	 * @param string $key The group key.
	 * @param array<string, mixed> $group The group configuration.
	 * @param string $current The current page slug.
	 * @return bool True if the group should be expanded, false otherwise.
	 */
	private static function group_is_expanded( string $key, array $group, string $current ): bool {
		$current_group = RequestHelper::get_key( 'group', '' );
		if ( 'settings' === $key ) {
			return 'settings' === $current_group;
		}
		if ( 'tools' === $key ) {
			return 'tools' === $current_group;
		}

		foreach ( $group['items'] as $slug => $item ) {
			$link = self::item_link( $slug, $item );
			if ( self::item_is_active( self::item_page( $link ), self::item_query( $link ), $current ) ) {
				return true;
			}
		}

		return false;
	}
	/**
	 * Extract the page part from a sidebar item slug.
	 *
	 * @param string $slug The sidebar item slug.
	 * @return string The page part of the slug.
	 */
	private static function item_page( string $slug ): string {
		$page = trim( (string) strtok( $slug, '&' ) );
		if ( '' === $page || preg_match( '/^\d+$/', $page ) ) {
			return 'modpress';
		}

		return $page;
	}

	/**
	 * Resolve the exact sidebar link for an item, preferring a raw link field.
	 *
	 * @param string $slug The item key.
	 * @param array<string, mixed> $item The item definition.
	 * @return string The exact link target.
	 */
	private static function item_link( string $slug, array $item ): string {
		$link = trim( (string) ( $item['link'] ?? '' ) );
		if ( '' !== $link ) {
			return $link;
		}

		return trim( (string) $slug );
	}

	/**
	 * Extract the query part from a sidebar item slug.
	 *
	 * @param string $slug The sidebar item slug.
	 * @return array<string, string> The query parameters as an associative array.
	 */
	private static function item_query( string $slug ): array {
		$query = array();
		parse_str( (string) strstr( $slug, '&' ), $query );
		return $query;
	}

	/**
	 * Generate the URL for a sidebar item.
	 *
	 * @param string $page The page part of the sidebar item.
	 * @param array<string, string> $query The query parameters for the sidebar item.
	 * @return string The generated URL.
	 */
	private static function item_url( string $page, array $query ): string {
		$page = trim( $page );
		if ( '' === $page || preg_match( '/^\d+$/', $page ) ) {
			$page = 'modpress';
		}
		$query_string = empty( $query ) ? '' : '?' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );
		if ( in_array( $page, array( 'edit.php', 'post-new.php' ), true ) ) {
			return admin_url( $page . $query_string );
		}

		return admin_url( 'admin.php?page=' . $page . ( empty( $query ) ? '' : '&' . ltrim( $query_string, '?' ) ) );
	}

	/**
	 * Determine if a sidebar item is active.
	 *
	 * @param string $page The page part of the sidebar item.
	 * @param array<string, string> $query The query parameters for the sidebar item.
	 * @param string $current The current page slug.
	 * @return bool True if the sidebar item is active, false otherwise.
	 */
	private static function item_is_active( string $page, array $query, string $current ): bool {
		if ( $page !== $current ) {
			return false;
		}

		foreach ( $query as $key => $value ) {
			if ( 'group' === $key ) {
				if ( (string) RequestHelper::value( $_GET, 'group', '' ) !== (string) $value ) {
					return false;
				}
				continue;
			}

			if ( 'tab' === $key ) {
				if ( (string) RequestHelper::value( $_GET, 'tab', '' ) !== (string) $value ) {
					return false;
				}
				continue;
			}

			if ( (string) RequestHelper::value( $_GET, $key, '' ) !== (string) $value ) {
				return false;
			}
		}

		return true;
	}
}
