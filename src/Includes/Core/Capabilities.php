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
		return array_merge(
			[
				'modpress_admin_view' => [ 'group' => 'ModPress Wikis', 'label' => __( 'View Wikis Administration', 'modpress' ), 'description' => __( 'Allows access to the ModPress Wikis administration area.', 'modpress' ) ],
				'modpress_create' => [ 'group' => 'ModPress Wikis', 'label' => __( 'Create Wikis', 'modpress' ), 'description' => __( 'Allows creating Wikis.', 'modpress' ) ],
				'modpress_edit' => [ 'group' => 'ModPress Wikis', 'label' => __( 'Edit Wikis', 'modpress' ), 'description' => __( 'Allows editing Wikis.', 'modpress' ) ],
				'modpress_delete' => [ 'group' => 'ModPress Wikis', 'label' => __( 'Delete Wikis', 'modpress' ), 'description' => __( 'Allows deleting Wikis.', 'modpress' ) ],
				'modpress_publish' => [ 'group' => 'ModPress Wikis', 'label' => __( 'Publish Wikis', 'modpress' ), 'description' => __( 'Allows publishing Wikis.', 'modpress' ) ],
				'modpress_edit_published' => [ 'group' => 'ModPress Wikis', 'label' => __( 'Edit Published Wikis', 'modpress' ), 'description' => __( 'Allows editing published Wikis.', 'modpress' ) ],
				'modpress_delete_published' => [ 'group' => 'ModPress Wikis', 'label' => __( 'Delete Published Wikis', 'modpress' ), 'description' => __( 'Allows deleting published Wikis.', 'modpress' ) ],
				'modpress_edit_others' => [ 'group' => 'ModPress Wikis', 'label' => __( 'Edit Others Wikis', 'modpress' ), 'description' => __( 'Allows editing Wikis created by other users.', 'modpress' ) ],
				'modpress_delete_others' => [ 'group' => 'ModPress Wikis', 'label' => __( 'Delete Others Wikis', 'modpress' ), 'description' => __( 'Allows deleting Wikis created by other users.', 'modpress' ) ],
				'modpress_admin_page_view' => [ 'group' => 'ModPress Wiki Pages', 'label' => __( 'View Wiki Pages Administration', 'modpress' ), 'description' => __( 'Allows access to the ModPress Wiki Pages administration area.', 'modpress' ) ],
				'modpress_page_create' => [ 'group' => 'ModPress Wiki Pages', 'label' => __( 'Create Wiki Pages', 'modpress' ), 'description' => __( 'Allows creating Wiki Pages.', 'modpress' ) ],
				'modpress_page_edit' => [ 'group' => 'ModPress Wiki Pages', 'label' => __( 'Edit Wiki Pages', 'modpress' ), 'description' => __( 'Allows editing Wiki Pages.', 'modpress' ) ],
				'modpress_page_delete' => [ 'group' => 'ModPress Wiki Pages', 'label' => __( 'Delete Wiki Pages', 'modpress' ), 'description' => __( 'Allows deleting Wiki Pages.', 'modpress' ) ],
				'modpress_page_edit_others' => [ 'group' => 'ModPress Wiki Pages', 'label' => __( 'Edit Others Wiki Pages', 'modpress' ), 'description' => __( 'Allows editing Wiki Pages created by other users.', 'modpress' ) ],
				'modpress_page_delete_others' => [ 'group' => 'ModPress Wiki Pages', 'label' => __( 'Delete Others Wiki Pages', 'modpress' ), 'description' => __( 'Allows deleting Wiki Pages created by other users.', 'modpress' ) ],
				'modpress_page_publish' => [ 'group' => 'ModPress Wiki Pages', 'label' => __( 'Publish Wiki Pages', 'modpress' ), 'description' => __( 'Allows publishing Wiki Pages.', 'modpress' ) ],
				'modpress_page_edit_published' => [ 'group' => 'ModPress Wiki Pages', 'label' => __( 'Edit Published Wiki Pages', 'modpress' ), 'description' => __( 'Allows editing published Wiki Pages.', 'modpress' ) ],
				'modpress_page_delete_published' => [ 'group' => 'ModPress Wiki Pages', 'label' => __( 'Delete Published Wiki Pages', 'modpress' ), 'description' => __( 'Allows deleting published Wiki Pages.', 'modpress' ) ],
				'modpress_settings_general_view' => [ 'group' => 'ModPress Settings', 'label' => __( 'View General Settings', 'modpress' ), 'description' => __( 'Allows viewing general ModPress settings.', 'modpress' ) ],
				'modpress_settings_general_edit' => [ 'group' => 'ModPress Settings', 'label' => __( 'Edit General Settings', 'modpress' ), 'description' => __( 'Allows editing general ModPress settings.', 'modpress' ) ],
				'modpress_settings_layout_view' => [ 'group' => 'ModPress Settings', 'label' => __( 'View Layout Settings', 'modpress' ), 'description' => __( 'Allows viewing ModPress layout settings.', 'modpress' ) ],
				'modpress_settings_layout_edit' => [ 'group' => 'ModPress Settings', 'label' => __( 'Edit Layout Settings', 'modpress' ), 'description' => __( 'Allows editing ModPress layout settings.', 'modpress' ) ],
				'modpress_settings_plugins_view' => [ 'group' => 'ModPress Settings', 'label' => __( 'View Plugin Settings', 'modpress' ), 'description' => __( 'Allows viewing ModPress plugin settings.', 'modpress' ) ],
				'modpress_settings_plugins_int_view' => [ 'group' => 'ModPress Settings', 'label' => __( 'View Internal Plugin Settings', 'modpress' ), 'description' => __( 'Allows viewing settings for internal ModPress plugins.', 'modpress' ) ],
				'modpress_settings_plugins_int_edit' => [ 'group' => 'ModPress Settings', 'label' => __( 'Edit Internal Plugin Settings', 'modpress' ), 'description' => __( 'Allows editing settings for internal ModPress plugins.', 'modpress' ) ],
				'modpress_settings_plugins_ext_view' => [ 'group' => 'ModPress Settings', 'label' => __( 'View External Plugin Settings', 'modpress' ), 'description' => __( 'Allows viewing settings for external ModPress plugins.', 'modpress' ) ],
				'modpress_settings_plugins_ext_edit' => [ 'group' => 'ModPress Settings', 'label' => __( 'Edit External Plugin Settings', 'modpress' ), 'description' => __( 'Allows editing settings for external ModPress plugins.', 'modpress' ) ],
				'modpress_settings_access_view' => [ 'group' => 'ModPress Settings', 'label' => __( 'View Access Settings', 'modpress' ), 'description' => __( 'Allows viewing ModPress access settings.', 'modpress' ) ],
				'modpress_settings_access_edit' => [ 'group' => 'ModPress Settings', 'label' => __( 'Edit Access Settings', 'modpress' ), 'description' => __( 'Allows editing ModPress access settings.', 'modpress' ) ],
				'modpress_tools_import' => [ 'group' => 'ModPress Tools', 'label' => __( 'Import ModPress Data', 'modpress' ), 'description' => __( 'Allows importing ModPress data.', 'modpress' ) ],
				'modpress_tools_export' => [ 'group' => 'ModPress Tools', 'label' => __( 'Export ModPress Data', 'modpress' ), 'description' => __( 'Allows exporting ModPress data.', 'modpress' ) ],
				'modpress_tools_debug' => [ 'group' => 'ModPress Tools', 'label' => __( 'View Debug Tools', 'modpress' ), 'description' => __( 'Allows using ModPress debug tools.', 'modpress' ) ],
				'modpress_tools_analytics' => [ 'group' => 'ModPress Tools', 'label' => __( 'View Analytics Tools', 'modpress' ), 'description' => __( 'Allows viewing ModPress analytics.', 'modpress' ) ],
			],
			self::$extensions
		);
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