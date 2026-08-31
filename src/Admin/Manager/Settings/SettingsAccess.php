<?php
/**
 * Settings access restriction fields.
 *
 * @package TrilBDev
 * @subpackage Admin\Manager\Settings
 */
namespace ModPress\Admin\Manager\Settings;

use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\Functions\Helpers\SanitizationHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SettingsAccess {
	/**
	 * Render access restriction fields.
	 *
	 * @param array<string, mixed> $values Current settings.
	 * @return void
	 */
	public function render( array $values ): void {
		$fields = [
			'create_mods' => [ 'label' => __( 'Who can create mods?', 'modpress' ), 'description' => __( 'Choose the minimum capability required to create ModPress mods.', 'modpress' ), 'tooltip' => __( 'Users without this capability cannot create new mods.', 'modpress' ) ],
			'write_pages' => [ 'label' => __( 'Who can write mod pages?', 'modpress' ), 'description' => __( 'Choose the minimum capability required to create or edit mod pages.', 'modpress' ), 'tooltip' => __( 'This controls editing access to mod page content.', 'modpress' ) ],
			'view_analytics' => [ 'label' => __( 'Who can check analytics?', 'modpress' ), 'description' => __( 'Choose the minimum capability required to view ModPress analytics.', 'modpress' ), 'tooltip' => __( 'Analytics data is shown only to users who meet this capability.', 'modpress' ), 'tooltip_type' => 'info' ],
			'manage_plugins' => [ 'label' => __( 'Who can manage plugins?', 'modpress' ), 'description' => __( 'Choose the minimum capability required to manage ModPress plugins.', 'modpress' ), 'tooltip' => __( 'Use a trusted administrator-level capability for plugin management.', 'modpress' ), 'tooltip_icon' => 'fa-shield-halved' ],
		];
		foreach ( $fields as $key => $field ) {
			$key = SanitizationHelper::key( $key );
			$id = 'modpress-access-' . $key;
			$name = 'modpress_access[' . $key . ']';
			$options = [
				[ 'value' => 'manage_options', 'label' => __( 'Administrators', 'modpress' ) ],
				[ 'value' => 'edit_posts', 'label' => __( 'Editors', 'modpress' ) ],
				[ 'value' => 'publish_posts', 'label' => __( 'Authors', 'modpress' ) ],
			];
			$current = $values[ $key ] ?? 'manage_options';
			$current = is_array( $current ) ? $current : [ $current ];
			$current = array_values( array_filter( array_map( 'sanitize_key', $current ) ) );
			$selected = [];
			foreach ( $options as $option ) {
				if ( in_array( $option['value'], $current, true ) ) {
					$selected[] = $option;
				}
			}
			if ( empty( $selected ) ) {
				$selected[] = $options[0];
			}
			echo '<tr><th scope="row">' . FormFieldHelper::label( $id, $field['label'], $field ) . '</th><td>' . FormFieldHelper::bootstrap_multiselect( $name, [ 'id' => $id, 'data' => $options, 'selected' => array_column( $selected, 'value' ) ] ) . '</td></tr>';
		}
	}
}
