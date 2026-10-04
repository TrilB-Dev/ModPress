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
		self::group_taxonomy();
		self::tag_taxonomy();
	}

	/**
	 * Backward-compatible alias for the canonical mod group taxonomy.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function type_taxonomy(): bool {
		return self::group_taxonomy();
	}

	/**
	 * Register the mod group taxonomy.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function group_taxonomy(): bool {
		if ( taxonomy_exists( 'modpress_mod_group' ) ) {
			return false;
		}

		return Taxonomy::create(
			'modpress_mod_group',
			array(
				'label'             => __( 'Mod Groups', 'modpress' ),
				'description'       => __( 'Groups used to organize mods.', 'modpress' ),
				'object_type'       => array( 'modpress_mod' ),
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_in_menu'      => false,
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
		if ( taxonomy_exists( 'modpress_mod_tag' ) ) {
			return false;
		}

		return Taxonomy::create(
			'modpress_mod_tag',
			array(
				'label'             => __( 'Mod Tags', 'modpress' ),
				'description'       => __( 'Tags used to describe mods.', 'modpress' ),
				'object_type'       => array( 'modpress_mod' ),
				'hierarchical'      => false,
				'public'            => true,
				'show_ui'           => true,
				'show_in_menu'      => false,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'mod-tag' ),
			)
		);
	}
}
