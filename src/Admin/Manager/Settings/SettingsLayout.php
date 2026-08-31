<?php
/**
 * Settings layout fields.
 *
 * @package TrilBDev
 * @subpackage Admin\Manager\Settings
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\Settings;

use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\Functions\Helpers\SanitizationHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SettingsLayout {
	/**
	 * Render layout settings fields.
	 *
	 * @param array<string, mixed> $values Current settings.
	 * @return void
	 */
	public function render( array $values, string $section = 'general' ): void {
		$sections = [
			'general' => __( 'General', 'modpress' ),
			'search' => __( 'Mod Search', 'modpress' ),
			'sidebar' => __( 'Mod Sidebar', 'modpress' ),
			'page' => __( 'Mod Page', 'modpress' ),
		];
		$section = isset( $sections[ $section ] ) ? $section : 'general';
		echo '<nav aria-label="' . esc_attr__( 'Layout settings sections', 'modpress' ) . '"><div class="nav nav-tabs" id="modpress-layout-tab" role="tablist">';
		foreach ( $sections as $slug => $label ) {
			$target = 'modpress-layout-' . $slug;
			echo FormFieldHelper::button( $label, [
				'class' => 'nav-link ' . ( $section === $slug ? 'active' : '' ),
				'attributes' => [
					'id' => $target . '-tab',
					'data-bs-toggle' => 'tab',
					'data-bs-target' => '#' . $target,
					'role' => 'tab',
					'aria-controls' => $target,
					'aria-selected' => $section === $slug ? 'true' : 'false',
					'data-modpress-layout-tab' => $slug,
				],
			] );
		}
		echo '</div></nav><div class="tab-content" id="modpress-layout-tab-content">';
		foreach ( $sections as $slug => $label ) {
			$target = 'modpress-layout-' . $slug;
			echo '<div class="tab-pane fade ' . ( $section === $slug ? 'show active' : '' ) . '" id="' . esc_attr( $target ) . '" role="tabpanel" aria-labelledby="' . esc_attr( $target . '-tab' ) . '" tabindex="0"><table class="form-table table align-middle"><tbody>';
			$this->render_fields( $values, $slug );
			echo '</tbody></table></div>';
		}
		echo '</div>';
	}
	/**
	 * Render the fields for a specific section.
	 *
	 * @param array<string, mixed> $values Current settings.
	 * @param string $section Section slug.
	 * @return void
	 */
	private function render_fields( array $values, string $section ): void {
		$fields = $this->fields( $section );
		foreach ( $fields as $key => $field ) {
			$key = SanitizationHelper::key( $key );
			$id = 'modpress-layout-' . $section . '-' . $key;
			$name = 'modpress_layout[' . $key . ']';
			$type = $field['type'] ?? 'checkbox';
			echo '<tr><th scope="row">' . FormFieldHelper::label( $id, $field['label'], $field ) . '</th><td>';
			if ( 'select' === $type ) {
				echo FormFieldHelper::select( $name, $field['options'], $values[ $key ] ?? $field['default'], [ 'id' => $id ] );
			} elseif ( 'number' === $type ) {
				echo FormFieldHelper::input( $name, (string) ( $values[ $key ] ?? $field['default'] ), [ 'id' => $id, 'type' => 'number', 'min' => $field['min'], 'max' => $field['max'] ] );
			} elseif ( 'text' === $type ) {
				echo FormFieldHelper::input( $name, SanitizationHelper::text( $values[ $key ] ?? $field['default'] ), [ 'id' => $id, 'type' => 'text' ] );
			} else {
				echo FormFieldHelper::checkbox( $name, '1', $field['label'], [ 'id' => $id, 'checked' => ! empty( $values[ $key ] ?? $field['default'] ) ] );
			}
			echo '</td></tr>';
		}
	}
	/**
	 * Get the fields for a specific section.
	 *
	 * @param string $section Section slug.
	 * @return array<string, mixed> Fields for the section.
	 */
	private function fields( string $section ): array {
		$toggle = static fn( string $label, string $description, string $tooltip, bool $default = false ): array => [ 'label' => $label, 'description' => $description, 'tooltip' => $tooltip, 'default' => $default ];
		return match ( $section ) {
			'search' => [
				'show_search' => $toggle( __( 'Enable Mod Search', 'modpress' ), __( 'Display search controls throughout the Mod.', 'modpress' ), __( 'Visitors can search Mod pages without browsing the sidebar.', 'modpress' ), true ),
				'search_placeholder' => [ 'label' => __( 'Search Placeholder', 'modpress' ), 'description' => __( 'Text shown before a visitor enters a search term.', 'modpress' ), 'tooltip' => __( 'Keep this short so it fits comfortably in the search field.', 'modpress' ), 'type' => 'text', 'default' => 'Search the Mod' ],
				'search_button_text' => [ 'label' => __( 'Search Button Text', 'modpress' ), 'description' => __( 'Text shown on the search submit button.', 'modpress' ), 'tooltip' => __( 'Use a clear action such as Search.', 'modpress' ), 'type' => 'text', 'default' => 'Search' ],
				'search_scope' => [ 'label' => __( 'Search Scope', 'modpress' ), 'description' => __( 'Choose which parts of Mod pages are searched.', 'modpress' ), 'tooltip' => __( 'Searching titles and content gives visitors the broadest results.', 'modpress' ), 'type' => 'select', 'options' => [ 'all' => __( 'Titles and Content', 'modpress' ), 'title' => __( 'Titles Only', 'modpress' ), 'content' => __( 'Content Only', 'modpress' ) ], 'default' => 'all' ],
				'search_no_results_message' => [ 'label' => __( 'No Results Message', 'modpress' ), 'description' => __( 'Message shown when a Mod search returns no results.', 'modpress' ), 'tooltip' => __( 'Give visitors a useful next step instead of leaving the result area empty.', 'modpress' ), 'type' => 'text', 'default' => 'No Mod pages found.' ],
				'search_results_count' => [ 'label' => __( 'Search Results Per Page', 'modpress' ), 'description' => __( 'Maximum number of results shown for one search.', 'modpress' ), 'tooltip' => __( 'Use a smaller value for compact result lists.', 'modpress' ), 'type' => 'number', 'default' => 10, 'min' => 1, 'max' => 50 ],
				'search_min_chars' => [ 'label' => __( 'Minimum Search Characters', 'modpress' ), 'description' => __( 'Minimum number of characters required before a search is performed.', 'modpress' ), 'tooltip' => __( 'A small minimum prevents noisy searches from very short terms.', 'modpress' ), 'type' => 'number', 'default' => 2, 'min' => 1, 'max' => 5 ],
				'search_live_results' => $toggle( __( 'Live Search Results', 'modpress' ), __( 'Update suggestions while visitors type.', 'modpress' ), __( 'Live results make large Mods easier to browse.', 'modpress' ), true ),
			],
			'sidebar' => [
				'show_sidebar' => $toggle( __( 'Show Mod Sidebar', 'modpress' ), __( 'Display the Mod navigation sidebar on Mod pages.', 'modpress' ), __( 'The sidebar is the primary navigation structure for ModPress.', 'modpress' ), true ),
				'sidebar_position' => [ 'label' => __( 'Sidebar Position', 'modpress' ), 'description' => __( 'Choose which side of the page contains the Mod navigation.', 'modpress' ), 'tooltip' => __( 'Use the position that best matches your site layout.', 'modpress' ), 'type' => 'select', 'options' => [ 'left' => __( 'Left', 'modpress' ), 'right' => __( 'Right', 'modpress' ) ], 'default' => 'left' ],
				'sidebar_width' => [ 'label' => __( 'Sidebar Width', 'modpress' ), 'description' => __( 'Set the preferred width of the Mod navigation sidebar in pixels.', 'modpress' ), 'tooltip' => __( 'Allow enough room for long category names without crowding the content.', 'modpress' ), 'type' => 'number', 'default' => 280, 'min' => 180, 'max' => 480 ],
				'sidebar_sticky' => $toggle( __( 'Sticky Sidebar', 'modpress' ), __( 'Keep the Mod navigation visible while the page scrolls.', 'modpress' ), __( 'Sticky navigation is useful for long Mod pages.', 'modpress' ), true ),
				'sidebar_show_categories' => $toggle( __( 'Show Categories', 'modpress' ), __( 'Display Mod categories in the navigation.', 'modpress' ), __( 'Disable this when navigation is provided by a custom component.', 'modpress' ), true ),
				'sidebar_show_category_count' => $toggle( __( 'Show Category Counts', 'modpress' ), __( 'Display the number of pages in each category.', 'modpress' ), __( 'Counts help visitors understand the size of each section.', 'modpress' ) ),
				'sidebar_expand_categories' => $toggle( __( 'Expand Categories', 'modpress' ), __( 'Show child categories when the sidebar loads.', 'modpress' ), __( 'Disable this for Mods with many nested categories.', 'modpress' ), true ),
				'sidebar_show_page_count' => $toggle( __( 'Show Page Counts', 'modpress' ), __( 'Display page counts beside Mod navigation items.', 'modpress' ), __( 'Page counts provide a quick overview of navigation depth.', 'modpress' ) ),
			],
			'page' => [
				'page_show_title' => $toggle( __( 'Show Page Title', 'modpress' ), __( 'Display the Mod page title above its content.', 'modpress' ), __( 'Disable this when your page template already renders the title.', 'modpress' ), true ),
				'show_breadcrumbs' => $toggle( __( 'Show Breadcrumbs', 'modpress' ), __( 'Show the Mod hierarchy above the page content.', 'modpress' ), __( 'Breadcrumbs help visitors understand where the current page belongs.', 'modpress' ), true ),
				'page_show_toc' => $toggle( __( 'Show Table of Contents', 'modpress' ), __( 'Display a table of contents generated from page headings.', 'modpress' ), __( 'The table of contents helps visitors scan long pages.', 'modpress' ), true ),
				'page_toc_position' => [ 'label' => __( 'Table of Contents Position', 'modpress' ), 'description' => __( 'Choose where the page table of contents appears.', 'modpress' ), 'tooltip' => __( 'Place it in the sidebar to keep navigation together or in the content area for a more compact layout.', 'modpress' ), 'type' => 'select', 'options' => [ 'sidebar' => __( 'Mod Sidebar', 'modpress' ), 'content' => __( 'Above Page Content', 'modpress' ) ], 'default' => 'sidebar' ],
				'toc_min_level' => [ 'label' => __( 'TOC Minimum Heading', 'modpress' ), 'description' => __( 'The shallowest heading included in the table of contents.', 'modpress' ), 'tooltip' => __( 'Heading 2 is a useful default for most documentation pages.', 'modpress' ), 'type' => 'number', 'default' => 2, 'min' => 1, 'max' => 5 ],
				'toc_max_level' => [ 'label' => __( 'TOC Maximum Heading', 'modpress' ), 'description' => __( 'The deepest heading included in the table of contents.', 'modpress' ), 'tooltip' => __( 'Avoid including every heading level on very detailed pages.', 'modpress' ), 'type' => 'number', 'default' => 4, 'min' => 2, 'max' => 6 ],
				'show_last_updated' => $toggle( __( 'Show Last Updated Date', 'modpress' ), __( 'Display when the Mod page was last updated.', 'modpress' ), __( 'This reassures visitors that the page is maintained.', 'modpress' ), true ),
				'show_author' => $toggle( __( 'Show Page Author', 'modpress' ), __( 'Display the author of the Mod page.', 'modpress' ), __( 'Useful when Mods are maintained by multiple contributors.', 'modpress' ) ),
				'show_reading_time' => $toggle( __( 'Show Reading Time', 'modpress' ), __( 'Display an estimated reading time for the page.', 'modpress' ), __( 'Reading time helps visitors decide how much time to set aside.', 'modpress' ) ),
				'reading_time_wpm' => [ 'label' => __( 'Reading Speed', 'modpress' ), 'description' => __( 'Words per minute used to calculate reading time.', 'modpress' ), 'tooltip' => __( 'Use a lower value when your audience benefits from a slower estimate.', 'modpress' ), 'type' => 'number', 'default' => 200, 'min' => 100, 'max' => 400 ],
				'show_feedback' => $toggle( __( 'Show Page Feedback', 'modpress' ), __( 'Ask visitors whether the Mod page was helpful.', 'modpress' ), __( 'Feedback gives Mod maintainers a simple quality signal.', 'modpress' ), true ),
				'page_show_navigation' => $toggle( __( 'Show Previous and Next Links', 'modpress' ), __( 'Add navigation links between related Mod pages.', 'modpress' ), __( 'This gives visitors another way to move through a Mod.', 'modpress' ), true ),
				'show_related_pages' => $toggle( __( 'Show Related Pages', 'modpress' ), __( 'Display related Mod pages below the current page.', 'modpress' ), __( 'Related pages encourage visitors to continue exploring.', 'modpress' ), true ),
				'related_pages_count' => [ 'label' => __( 'Related Pages Count', 'modpress' ), 'description' => __( 'Number of related pages to display.', 'modpress' ), 'tooltip' => __( 'Keep this low enough that related content does not overwhelm the page.', 'modpress' ), 'type' => 'number', 'default' => 4, 'min' => 1, 'max' => 12 ],
			],
			default => [
				'show_search' => $toggle( __( 'Enable Mod Search', 'modpress' ), __( 'Display the Mod search interface.', 'modpress' ), __( 'Search is one of the fastest ways to find content in a large Mod.', 'modpress' ), true ),
				'show_breadcrumbs' => $toggle( __( 'Show Breadcrumbs', 'modpress' ), __( 'Display the current Mod hierarchy.', 'modpress' ), __( 'Breadcrumbs connect the current page to its parent structure.', 'modpress' ), true ),
				'show_sidebar' => $toggle( __( 'Show Mod Sidebar', 'modpress' ), __( 'Display the Mod navigation sidebar.', 'modpress' ), __( 'The sidebar keeps parent and child Mod pages discoverable.', 'modpress' ), true ),
			],
		};
	}
}
