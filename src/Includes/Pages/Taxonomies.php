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
use ModPress\Includes\ModManagement\GroupsManager;

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
		self::tag_taxonomy();
		self::changelog_category_taxonomy();
		self::file_type_taxonomy();
		self::file_links_taxonomy();
	}

	/**
	 * Register the mod tag taxonomy.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function tag_taxonomy(): bool {
		if ( taxonomy_exists( 'modpress_tag' ) ) {
			return false;
		}

		return Taxonomy::create(
			'modpress_tag',
			array(
				'label'             => __( 'Mod Tags', 'modpress' ),
				'description'       => __( 'Tags used to describe mods.', 'modpress' ),
				'post_type'       => array( 'modpress_mod' ),
				'hierarchical'      => false,
				'public'            => true,
				'show_ui'           => true,
				'show_in_menu'      => false,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'tags' ),
			)
		);
	}

	public static function changelog_category_taxonomy(): bool {
		if ( taxonomy_exists( 'modpress_changelog_category' ) ) {
			return false;
		}

		return Taxonomy::create(
			'modpress_changelog_category',
			array(
				'label'             => __( 'Changelog Categories', 'modpress' ),
				'description'       => __( 'Categories used to categorise changelogs.', 'modpress' ),
				'post_type'       	=> array( 'modpress_changelog' ),
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_in_menu'      => false,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'changelog-categories' ),
			)
		);
	}

	public static function file_type_taxonomy(): bool {
		if ( taxonomy_exists( 'modpress_file_type' ) ) {
			return false;
		}

		return Taxonomy::create(
			'modpress_file_type',
			array(
				'label'             => __( 'File Types', 'modpress' ),
				'description'       => __( 'Types used to categorise files.', 'modpress' ),
				'post_type'       	=> array( 'modpress_file' ),
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_in_menu'      => false,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'file-types' ),
			)
		);
	}

	public static function file_links_taxonomy(): bool {
		if ( taxonomy_exists( 'modpress_file_links' ) ) {
			return false;
		}

		return Taxonomy::create(
			'modpress_file_links',
			array(
				'label'             => __( 'File Links', 'modpress' ),
				'description'       => __( 'Links where files may be found.', 'modpress' ),
				'post_type'       	=> array( 'modpress_file' ),
				'hierarchical'      => false,
				'public'            => true,
				'show_ui'           => true,
				'show_in_menu'      => false,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'file-links' ),
			)
		);
	}
}