<?php

namespace ModPress\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Capabilities {
	/**
	 * Capability definitions contributed by ModPress extensions.
	 *
	 * @var array<string, array{group: string, label: string, description: string}>
	 */
	private static array $extensions = [];

	/**
	 * Return the core and registered extension capability definitions.
	 *
	 * @return array<string, array{group: string, label: string, description: string}>
	 */
	public static function definitions(): array {
		$definitions = [
			'modpress_admin_view' => [
				'group' => 'ModPress',
				'label' => __( 'View ModPress Administration', 'modpress' ),
				'description' => __( 'Allows access to the ModPress administration area.', 'modpress' ),
			],
			'modpress_create' => [
				'group' => 'ModPress',
				'label' => __( 'Create ModPress Content', 'modpress' ),
				'description' => __( 'Allows creating ModPress content.', 'modpress' ),
			],
			'modpress_edit' => [
				'group' => 'ModPress',
				'label' => __( 'Edit ModPress Content', 'modpress' ),
				'description' => __( 'Allows editing ModPress content.', 'modpress' ),
			],
			'modpress_delete' => [
				'group' => 'ModPress',
				'label' => __( 'Delete ModPress Content', 'modpress' ),
				'description' => __( 'Allows deleting ModPress content.', 'modpress' ),
			],
			'modpress_publish' => [
				'group' => 'ModPress',
				'label' => __( 'Publish ModPress Content', 'modpress' ),
				'description' => __( 'Allows publishing ModPress content.', 'modpress' ),
			],
			'modpress_edit_published' => [
				'group' => 'ModPress',
				'label' => __( 'Edit Published ModPress Content', 'modpress' ),
				'description' => __( 'Allows editing published ModPress content.', 'modpress' ),
			],
			'modpress_delete_published' => [
				'group' => 'ModPress',
				'label' => __( 'Delete Published ModPress Content', 'modpress' ),
				'description' => __( 'Allows deleting published ModPress content.', 'modpress' ),
			],
			'modpress_edit_others' => [
				'group' => 'ModPress',
				'label' => __( 'Edit Others ModPress Content', 'modpress' ),
				'description' => __( 'Allows editing ModPress content created by other users.', 'modpress' ),
			],
			'modpress_delete_others' => [
				'group' => 'ModPress',
				'label' => __( 'Delete Others ModPress Content', 'modpress' ),
				'description' => __( 'Allows deleting ModPress content created by other users.', 'modpress' ),
			],
			'modpress_admin_groups_view' => [
				'group' => 'ModPress Groups',
				'label' => __( 'View ModPress Groups Administration', 'modpress' ),
				'description' => __( 'Allows access to the ModPress Groups administration area.', 'modpress' ),
			],
			'modpress_group_create' => [
				'group' => 'ModPress Groups',
				'label' => __( 'Create ModPress Groups', 'modpress' ),
				'description' => __( 'Allows creating ModPress Groups.', 'modpress' ),
			],
			'modpress_group_edit' => [
				'group' => 'ModPress Groups',
				'label' => __( 'Edit ModPress Groups', 'modpress' ),
				'description' => __( 'Allows editing ModPress Groups.', 'modpress' ),
			],
			'modpress_group_delete' => [
				'group' => 'ModPress Groups',
				'label' => __( 'Delete ModPress Groups', 'modpress' ),
				'description' => __( 'Allows deleting ModPress Groups.', 'modpress' ),
			],
			'modpress_group_edit_others' => [
				'group' => 'ModPress Groups',
				'label' => __( 'Edit Others ModPress Groups', 'modpress' ),
				'description' => __( 'Allows editing ModPress Groups created by other users.', 'modpress' ),
			],
			'modpress_group_delete_others' => [
				'group' => 'ModPress Groups',
				'label' => __( 'Delete Others ModPress Groups', 'modpress' ),
				'description' => __( 'Allows deleting ModPress Groups created by other users.', 'modpress' ),
			],
			'modpress_settings_general_view' => [
				'group' => 'ModPress Settings',
				'label' => __( 'View General Settings', 'modpress' ),
				'description' => __( 'Allows viewing general ModPress settings.', 'modpress' ),
			],
			'modpress_settings_general_edit' => [
				'group' => 'ModPress Settings',
				'label' => __( 'Edit General Settings', 'modpress' ),
				'description' => __( 'Allows editing general ModPress settings.', 'modpress' ),
			],
			'modpress_settings_layout_view' => [
				'group' => 'ModPress Settings',
				'label' => __( 'View Layout Settings', 'modpress' ),
				'description' => __( 'Allows viewing ModPress layout settings.', 'modpress' ),
			],
			'modpress_settings_layout_edit' => [
				'group' => 'ModPress Settings',
				'label' => __( 'Edit Layout Settings', 'modpress' ),
				'description' => __( 'Allows editing ModPress layout settings.', 'modpress' ),
			],
			'modpress_settings_plugins_view' => [
				'group' => 'ModPress Settings',
				'label' => __( 'View Plugin Settings', 'modpress' ),
				'description' => __( 'Allows viewing ModPress plugin settings.', 'modpress' ),
			],
			'modpress_settings_plugins_int_view' => [
				'group' => 'ModPress Settings',
				'label' => __( 'View Internal Plugin Settings', 'modpress' ),
				'description' => __( 'Allows viewing settings for internal ModPress plugins.', 'modpress' ),
			],
			'modpress_settings_plugins_int_edit' => [
				'group' => 'ModPress Settings',
				'label' => __( 'Edit Internal Plugin Settings', 'modpress' ),
				'description' => __( 'Allows editing settings for internal ModPress plugins.', 'modpress' ),
			],
			'modpress_settings_plugins_ext_view' => [
				'group' => 'ModPress Settings',
				'label' => __( 'View External Plugin Settings', 'modpress' ),
				'description' => __( 'Allows viewing settings for external ModPress plugins.', 'modpress' ),
			],
			'modpress_settings_plugins_ext_edit' => [
				'group' => 'ModPress Settings',
				'label' => __( 'Edit External Plugin Settings', 'modpress' ),
				'description' => __( 'Allows editing settings for external ModPress plugins.', 'modpress' ),
			],
			'modpress_settings_access_view' => [
				'group' => 'ModPress Settings',
				'label' => __( 'View Access Settings', 'modpress' ),
				'description' => __( 'Allows viewing ModPress access settings.', 'modpress' ),
			],
			'modpress_settings_access_edit' => [
				'group' => 'ModPress Settings',
				'label' => __( 'Edit Access Settings', 'modpress' ),
				'description' => __( 'Allows editing ModPress access settings.', 'modpress' ),
			],
			'modpress_tools_import' => [
				'group' => 'ModPress Tools',
				'label' => __( 'Import ModPress Data', 'modpress' ),
				'description' => __( 'Allows importing ModPress data.', 'modpress' ),
			],
			'modpress_tools_export' => [
				'group' => 'ModPress Tools',
				'label' => __( 'Export ModPress Data', 'modpress' ),
				'description' => __( 'Allows exporting ModPress data.', 'modpress' ),
			],
			'modpress_tools_debug' => [
				'group' => 'ModPress Tools',
				'label' => __( 'View Debug Tools', 'modpress' ),
				'description' => __( 'Allows using ModPress debug tools.', 'modpress' ),
			],
			'modpress_tools_analytics' => [
				'group' => 'ModPress Tools',
				'label' => __( 'View Analytics Tools', 'modpress' ),
				'description' => __( 'Allows viewing ModPress analytics.', 'modpress' ),
			],
		];

		return array_merge( $definitions, self::$extensions );
	}

	/**
	 * Register definitions contributed by a plugin and install any missing caps.
	 *
	 * @param array<string, array{group: string, label: string, description: string}> $definitions Definitions to add.
	 * @return void
	 */
	public static function extend( array $definitions ): void {
		self::$extensions = array_merge( self::$extensions, $definitions );
		self::install();
	}

	/**
	 * Install missing capabilities without removing administrator customizations.
	 *
	 * @return void
	 */
	public static function install(): void {
		$administrator = get_role( 'administrator' );
		if ( ! $administrator ) {
			return;
		}

		foreach ( array_keys( self::definitions() ) as $capability ) {
			if ( ! $administrator->has_cap( $capability ) ) {
				$administrator->add_cap( $capability );
			}
		}
	}
}