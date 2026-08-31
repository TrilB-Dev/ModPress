<?php
/**
 * Settings general fields.
 * @package ModPress
 * @subpackage Admin\Manager\Settings
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\Settings;

use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\Functions\Helpers\PermalinkHelper;
use ModPress\Includes\Functions\Helpers\SanitizationHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SettingsGeneral {
	/**
	 * Render general ModPress settings fields.
	 *
	 * @param array<string, mixed> $values Current settings.
	 * @return void
	 */
	public function render( array $values ): void {
		$fields = [
			'root_name' => [ 'label' => __( 'ModPress Root Name', 'modpress' ), 'description' => __( 'The name used for the main ModPress area.', 'modpress' ), 'tooltip' => __( 'This name appears in the admin interface and generated titles.', 'modpress' ) ],
			'root_description' => [ 'label' => __( 'ModPress Description', 'modpress' ), 'description' => __( 'A short description for the ModPress knowledge base.', 'modpress' ), 'tooltip' => __( 'This can be used by themes and integrations when describing the ModPress area.', 'modpress' ), 'type' => 'textarea' ],
			'archive_title' => [ 'label' => __( 'Mod Archive Title', 'modpress' ), 'description' => __( 'The title shown on Mod archive and index views.', 'modpress' ), 'tooltip' => __( 'Use a concise title that makes the documentation area clear to visitors.', 'modpress' ) ],
			'archive_description' => [ 'label' => __( 'Mod Archive Description', 'modpress' ), 'description' => __( 'Supporting text shown on Mod archive and index views.', 'modpress' ), 'tooltip' => __( 'A short introduction helps visitors understand what they can find in the Mod.', 'modpress' ), 'type' => 'textarea' ],
			'root_slug' => [ 'label' => __( 'ModPress Root Slug', 'modpress' ), 'description' => __( 'The URL slug for the ModPress root.', 'modpress' ), 'tooltip' => __( 'Use lowercase letters, numbers, and hyphens for the most reliable URLs.', 'modpress' ) ],
			'category_slug' => [ 'label' => __( 'Custom Category Slug', 'modpress' ), 'description' => __( 'The URL slug used for ModPress categories.', 'modpress' ), 'tooltip' => __( 'Changing this value flushes the WordPress rewrite rules.', 'modpress' ), 'tooltip_type' => 'info' ],
			'tag_slug' => [ 'label' => __( 'Custom Tags Slug', 'modpress' ), 'description' => __( 'The URL slug used for ModPress tags.', 'modpress' ), 'tooltip' => __( 'Changing this value flushes the WordPress rewrite rules.', 'modpress' ), 'tooltip_type' => 'info' ],
			'permalink' => [ 'label' => __( 'ModPress Permalink', 'modpress' ), 'description' => __( 'The permalink structure used by ModPress content.', 'modpress' ), 'tooltip' => __( 'Choose a structure that remains readable and stable after publication.', 'modpress' ) ],
			'enable_schema' => [ 'label' => __( 'Enable Documentation Schema', 'modpress' ), 'description' => __( 'Allow ModPress themes and integrations to expose documentation metadata.', 'modpress' ), 'tooltip' => __( 'Keep this enabled when search engines and integrations should understand the Mod structure.', 'modpress' ), 'type' => 'checkbox', 'default' => true ],
		];
		foreach ( $fields as $key => $field ) {
			$key = SanitizationHelper::key( $key );
			$id = 'modpress-' . $key;
			$name = 'modpress_general[' . $key . ']';
			$value = 'permalink' === $key ? PermalinkHelper::sanitize_pattern( $values[ $key ] ?? '' ) : SanitizationHelper::text( $values[ $key ] ?? $field['default'] ?? '' );
			echo '<tr><th scope="row">' . FormFieldHelper::label( $id, $field['label'], $field ) . '</th><td>';
			if ( 'textarea' === ( $field['type'] ?? '' ) ) {
				echo FormFieldHelper::textarea( $name, $value, [ 'id' => $id, 'rows' => 3 ] );
			} elseif ( 'checkbox' === ( $field['type'] ?? '' ) ) {
				echo FormFieldHelper::checkbox( $name, '1', $field['label'], [ 'id' => $id, 'checked' => ! empty( $values[ $key ] ?? $field['default'] ) ] );
			} else {
				echo FormFieldHelper::text_input( $name, $value, [ 'id' => $id, 'data-permalink-field' => 'permalink' === $key ? 'permalink' : null ] );
			}
			if ( 'permalink' === $key ) {
				echo '<div class="modpress-permalink-tokens mt-2" aria-label="' . esc_attr__( 'Available permalink tokens', 'modpress' ) . '">';
				foreach ( PermalinkHelper::token_definitions() as $token => $description ) {
					echo FormFieldHelper::button( $token, [
						'class' => 'btn-sm btn-outline-secondary me-1 mb-1',
						'type' => 'button',
						'attributes' => [
							'data-permalink-token' => $token,
							'title' => $description,
						],
					] );
				}
				echo '</div><div class="form-text">' . esc_html__( 'Click a token to add it to the pattern. Tokens are inserted with a trailing slash and reappear when removed.', 'modpress' ) . '</div>';
			}
			echo '</td></tr>';
		}
	}
}
