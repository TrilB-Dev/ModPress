<?php
/**
 * PostTypes class for registering plugin post types.
 *
 * @package ModPress
 * @subpackage Includes\Pages
 * @since 1.0.0
 */
namespace ModPress\Includes\Pages;

use ModPress\Includes\Core\PostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PostTypes {
	/**
	 * Register all page-layer post types.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public static function register(): void {
		self::mods_posttype();
	}

	/**
	 * Register the core ModPress mod post type.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function mods_posttype(): bool {
		if ( post_type_exists( 'modpress_mod' ) ) {
			return false;
		}
		return PostType::create(
			'modpress_mod',
			array(
				'label'           => __( 'Mods', 'modpress' ),
				'description'     => __( 'Mod entries managed by ModPress.', 'modpress' ),
				'supports'        => array( 'title', 'editor', 'thumbnail', 'comments', 'trackbacks', 'revisions', 'custom-fields', 'page-attributes', 'post-formats' ),
				'taxonomies'      => array( 'modpress_mod_group', 'modpress_mod_tag' ),
				'public'          => true,
				'show_ui'         => true,
				'show_in_menu'    => false,
				'menu_position'   => 20,
				'menu_icon'       => 'dashicons-archive',
				'has_archive'     => true,
				'show_in_rest'    => true,
				'rewrite'         => array( 'slug' => 'mods' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
	}
	/**
	 * Register the core ModPress changelog post type.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function changelog_posttype(): bool {
		if ( post_type_exists( 'modpress_changelog' ) ) {
			return false;
		}
		return PostType::create(
			'modpress_changelog',
			array(
				'label'           => __( 'Changelogs', 'modpress' ),
				'description'     => __( 'Changelog entries managed by ModPress.', 'modpress' ),
				'supports'        => array( 'title', 'editor', 'thumbnail', 'comments', 'trackbacks', 'revisions', 'custom-fields', 'page-attributes', 'post-formats' ),
				'taxonomies'      => array( 'modpress_changelog_category' ),
				'public'          => true,
				'show_ui'         => true,
				'show_in_menu'    => false,
				'menu_position'   => 21,
				'menu_icon'       => 'dashicons-archive',
				'has_archive'     => true,
				'show_in_rest'    => true,
				'rewrite'         => array( 'slug' => 'changelogs' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
	}
	/**
	 * Register the core ModPress file post type.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function file_posttype(): bool {
		if ( post_type_exists( 'modpress_file' ) ) {
			return false;
		}
		return PostType::create(
			'modpress_file',
			array(
				'label'           => __( 'Files', 'modpress' ),
				'description'     => __( 'File entries managed by ModPress.', 'modpress' ),
				'supports'        => array( 'title', 'editor', 'thumbnail', 'comments', 'trackbacks', 'revisions', 'custom-fields', 'page-attributes', 'post-formats' ),
				'taxonomies'      => array( 'modpress_file_type', 'modpress_file_links' ),
				'public'          => true,
				'show_ui'         => true,
				'show_in_menu'    => false,
				'menu_position'   => 22,
				'menu_icon'       => 'dashicons-archive',
				'has_archive'     => true,
				'show_in_rest'    => true,
				'rewrite'         => array( 'slug' => 'files' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
	}
}