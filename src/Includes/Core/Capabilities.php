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
	private static array $extensions = array();

	/**
	 * Return the core and registered extension capability definitions.
	 *
	 * @return array<string, array{group: string, label: string, description: string}>
	 */
	public static function definitions(): array {
		return array_merge(
			array(
				'modpress_admin_view'                => array(
					'group'       => 'ModPress Licence',
					'label'       => __( 'View Licence Administration', 'modpress' ),
					'description' => __( 'Allows access to the ModPress dashboard and admin pages.', 'modpress' ),
				),
				'modpress_dashboard_view'            => array(
					'group'       => 'ModPress Licence',
					'label'       => __( 'View Licence Dashboard', 'modpress' ),
					'description' => __( 'Allows viewing the ModPress dashboard and summary status.', 'modpress' ),
				),
				'modpress_settings_general_view'     => array(
					'group'       => 'ModPress Settings',
					'label'       => __( 'View Licence Settings', 'modpress' ),
					'description' => __( 'Allows viewing the general licence management settings.', 'modpress' ),
				),
				'modpress_settings_general_edit'     => array(
					'group'       => 'ModPress Settings',
					'label'       => __( 'Edit Licence Settings', 'modpress' ),
					'description' => __( 'Allows editing the licence management settings.', 'modpress' ),
				),
				'modpress_settings_access_view'      => array(
					'group'       => 'ModPress Settings',
					'label'       => __( 'View Access Controls', 'modpress' ),
					'description' => __( 'Allows viewing who can do what inside ModPress.', 'modpress' ),
				),
				'modpress_settings_access_edit'      => array(
					'group'       => 'ModPress Settings',
					'label'       => __( 'Edit Access Controls', 'modpress' ),
					'description' => __( 'Allows changing licence access roles and permission boundaries.', 'modpress' ),
				),
				'modpress_settings_security_view'    => array(
					'group'       => 'ModPress Settings',
					'label'       => __( 'View Security Settings', 'modpress' ),
					'description' => __( 'Allows viewing security and export protection settings.', 'modpress' ),
				),
				'modpress_settings_security_edit'    => array(
					'group'       => 'ModPress Settings',
					'label'       => __( 'Edit Security Settings', 'modpress' ),
					'description' => __( 'Allows editing export passwords, encryption controls, and security flags.', 'modpress' ),
				),
				'modpress_tools_import'              => array(
					'group'       => 'ModPress Tools',
					'label'       => __( 'Import Licence Data', 'modpress' ),
					'description' => __( 'Allows importing licence exports into the system securely.', 'modpress' ),
				),
				'modpress_tools_export'              => array(
					'group'       => 'ModPress Tools',
					'label'       => __( 'Export Licence Data', 'modpress' ),
					'description' => __( 'Allows exporting licence records using encryption and a password.', 'modpress' ),
				),
				'modpress_tools_debug'               => array(
					'group'       => 'ModPress Tools',
					'label'       => __( 'View Debug Tools', 'modpress' ),
					'description' => __( 'Allows using ModPress debug and diagnostics tools.', 'modpress' ),
				),
				'modpress_tools_reset'               => array(
					'group'       => 'ModPress Tools',
					'label'       => __( 'Reset Licence Data', 'modpress' ),
					'description' => __( 'Allows resetting or clearing licence records and related data.', 'modpress' ),
				),
				'modpress_settings_plugins_view'     => array(
					'group'       => 'ModPress Settings',
					'label'       => __( 'View Plugin Settings', 'modpress' ),
					'description' => __( 'Allows viewing ModPress plugin settings.', 'modpress' ),
				),
				'modpress_settings_plugins_int_view' => array(
					'group'       => 'ModPress Settings',
					'label'       => __( 'View Internal Plugin Settings', 'modpress' ),
					'description' => __( 'Allows viewing settings for internal ModPress plugins.', 'modpress' ),
				),
				'modpress_settings_plugins_int_edit' => array(
					'group'       => 'ModPress Settings',
					'label'       => __( 'Edit Internal Plugin Settings', 'modpress' ),
					'description' => __( 'Allows editing settings for internal ModPress plugins.', 'modpress' ),
				),
				'modpress_settings_plugins_ext_view' => array(
					'group'       => 'ModPress Settings',
					'label'       => __( 'View External Plugin Settings', 'modpress' ),
					'description' => __( 'Allows viewing settings for external ModPress plugins.', 'modpress' ),
				),
				'modpress_settings_plugins_ext_edit' => array(
					'group'       => 'ModPress Settings',
					'label'       => __( 'Edit External Plugin Settings', 'modpress' ),
					'description' => __( 'Allows editing settings for external ModPress plugins.', 'modpress' ),
				),
				'modpress_roles_view' => [ 
					'group' => 'ModPress User Roles', 
					'label' => __( 'View User Roles Manager', 'modpress' ), 
					'description' => __( 'Allows viewing the ModPress User Roles Manager.', 'modpress' ) 
				],
				'modpress_roles_create' => [ 
					'group' => 'ModPress User Roles', 
					'label' => __( 'Create User Roles', 'modpress' ), 
					'description' => __( 'Allows creating user roles.', 'modpress' ) 
				],
				'modpress_roles_edit' => [ 
					'group' => 'ModPress User Roles', 
					'label' => __( 'Edit User Roles', 'modpress' ), 
					'description' => __( 'Allows editing user roles.', 'modpress' ) 
				],
				'modpress_roles_delete' => [ 
					'group' => 'ModPress User Roles', 
					'label' => __( 'Delete User Roles', 'modpress' ), 
					'description' => __( 'Allows deleting user roles.', 'modpress' ) 
				],
				'modpress_manager_dashboard' => array(
					'group'       => 'ModPress User Management',
					'label'       => __( 'View User Management Dashboard', 'modpress' ),
					'description' => __( 'Allows viewing the ModPress User Management Dashboard.', 'modpress' ),
				),
				'modpress_manager_mods_view' => array(
					'group'       => 'ModPress User Management',
					'label'       => __( 'Manage Mods', 'modpress' ),
					'description' => __( 'Allows viewing mods within ModPress User Management.', 'modpress' ),
				),
				'modpress_manager_mods_edit' => array(
					'group'       => 'ModPress User Management',
					'label'       => __( 'Edit Mods', 'modpress' ),
					'description' => __( 'Allows editing mods within ModPress User Management.', 'modpress' ),
				),
				'modpress_manager_groups_view' => array(
					'group'       => 'ModPress User Management',
					'label'       => __( 'Manage User Groups', 'modpress' ),
					'description' => __( 'Allows viewing user groups within ModPress User Management.', 'modpress' ),
				),
				'modpress_manager_groups_edit' => array(
					'group'       => 'ModPress User Management',
					'label'       => __( 'Edit User Groups', 'modpress' ),
					'description' => __( 'Allows editing user groups within ModPress User Management.', 'modpress' ),
				),
				'modpress_manager_tags_view' => array(
					'group'       => 'ModPress User Management',
					'label'       => __( 'Manage Tags', 'modpress' ),
					'description' => __( 'Allows viewing tags within ModPress User Management.', 'modpress' ),
				),
				'modpress_manager_tags_edit' => array(
					'group'       => 'ModPress User Management',
					'label'       => __( 'Edit Tags', 'modpress' ),
					'description' => __( 'Allows editing tags within ModPress User Management.', 'modpress' ),
				),
			),
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





