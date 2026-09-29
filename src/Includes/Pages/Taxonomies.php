<?php
/**
 * Taxonomies class for registering plugin taxonomies.
 *
 * @package ModPress
 * @subpackage Includes\Pages
 * @since 1.0.0
 */
namespace ModPress\Includes\Pages;

use ModPress\Includes\Core\Taxonomy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Taxonomies {
	/**
	 * Register the page-layer taxonomies.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public static function register(): void {
		self::type_taxonomy();
		self::group_taxonomy();
		self::tag_taxonomy();
	}

	/**
	 * Register the mod type taxonomy.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function type_taxonomy(): bool {
		return Taxonomy::create(
			'mod_type',
			array(
				'label'             => __( 'Mod Types', 'modpress' ),
				'description'       => __( 'Types used to classify mods.', 'modpress' ),
				'object_type'       => array( 'modpress_mod' ),
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'mod-type' ),
			)
		);
	}

	/**
	 * Register the mod group taxonomy.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function group_taxonomy(): bool {
		return Taxonomy::create(
			'mod_group',
			array(
				'label'             => __( 'Mod Groups', 'modpress' ),
				'description'       => __( 'Groups used to organize mods.', 'modpress' ),
				'object_type'       => array( 'modpress_mod' ),
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'mod-group' ),
			)
		);
	}

	/**
	 * Register the mod tag taxonomy.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function tag_taxonomy(): bool {
		return Taxonomy::create(
			'mod_tag',
			array(
				'label'             => __( 'Mod Tags', 'modpress' ),
				'description'       => __( 'Tags used to describe mods.', 'modpress' ),
				'object_type'       => array( 'modpress_mod' ),
				'hierarchical'      => false,
				'public'            => true,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'mod-tag' ),
			)
		);
	}
}
