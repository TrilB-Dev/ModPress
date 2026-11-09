<?php
/**
 * Defaults class for defining plugin default values.
 *
 * @package ModPress
 * @subpackage Includes\Core
 * @since 1.0.0
 */
namespace ModPress\Includes\Core;

use ModPress\Includes\Functions\Helpers\DBHelper;
use ModPress\Includes\Functions\Helpers\LoggerHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Defaults {
    /**
     * Constructor for the Defaults class.
     *
     * @since 1.0.0
     */
	public function __construct() {
		// Initialize default values here.
	}
    /**
     * Install all default values for the plugin.
     *
     * @return void
     * @since 1.0.0
     */
	public static function install_defaults(): void {
		self::default_types();
		self::default_mod_status();
		self::default_changelog_category();
		self::default_file_type();
		self::default_softwares();
		self::default_web();
		self::default_games();
		self::default_platforms();
		self::default_assets();
		self::default_languages();
		self::default_tags();
		self::default_licences();
		self::default_groups();
	}
    /**
     * Reset the default groups by deleting and recreating the groups table.
     *
     * @return bool True on success, false on failure.
     * @since 1.0.0
     */
	public static function reset_group_defaults(): bool {
		self::delete_default_terms();

		if ( DBHelper::table_exists( 'groups' ) ) {
			DBHelper::drop_table( 'groups' );
		}

		Schema::register_tables();
		if ( ! DBHelper::create_table( 'groups' ) ) {
			return false;
		}

		self::install_defaults();
		return true;
	}
    /**
     * Delete all default terms for the plugin's taxonomies.
     *
     * @return void
     * @since 1.0.0
     */
	private static function delete_default_terms(): void {
		$taxonomies = array_unique( array_merge(
			get_taxonomies( array(), 'names' ),
			array(
				'modpress_software',
				'modpress_platforms',
				'modpress_assets',
				'modpress_web',
				'modpress_games',
				'modpress_type',
				'modpress_language',
				'modpress_tags',
				'modpress_mod_status',
				'modpress_file_type',
				'modpress_changelog_category',
				'modpress_licences',
			)
		) );
		foreach ( $taxonomies as $taxonomy ) {
			if ( 0 !== strpos( (string) $taxonomy, 'modpress_' ) ) {
				continue;
			}

			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'fields'     => 'ids',
				)
			);

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term_id ) {
				wp_delete_term( (int) $term_id, $taxonomy );
			}
		}
	}
    /**
     * Encode an array of parent IDs into a serialized string.
     *
     * @param array $ids The array of parent IDs.
     * @return string|null The serialized string of parent IDs or null if empty.
     * @since 1.0.0
     */
	private static function encode_parent_ids( array $ids = array() ): ?string {
		$values = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
		return empty( $values ) ? null : serialize( $values );
	}
    /**
     * Get a map of term slugs to term IDs for a given taxonomy.
     *
     * @param string $taxonomy The taxonomy to retrieve terms for.
     * @return array An associative array mapping term slugs to term IDs.
     * @since 1.0.0
     */
	private static function get_term_id_map( string $taxonomy ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$map = array();
		foreach ( $terms as $term ) {
			$map[ $term->slug ] = (int) $term->term_id;
		}

		return $map;
	}

	/**
	 * Define default groups.
	 * This method inserts the default groups into the database.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public static function default_groups(): void {
		if ( ! DBHelper::table_exists( 'groups' ) ) {
			Schema::register_tables();
			DBHelper::create_table( 'groups' );
		}

		if ( DBHelper::count( 'groups' ) > 0 ) {
			return;
		}

		$type_term_map = self::get_term_id_map( 'modpress_type' );
		$asset_term_map = self::get_term_id_map( 'modpress_assets' );
		$mod_type_group_id = 1;

		DBHelper::insert(
			'groups',
			array(
				'id'                  => $mod_type_group_id,
				'group_key'           => 'modpress_type',
				'group_name'          => 'Mod Type',
				'group_slug'          => 'type',
				'group_singular_name' => 'Mod Type',
				'group_description'   => 'The Mod Type group is used to categorise your mods by their type.',
				'group_hierarchical'  => false,
				'group_parent'        => null,
				'group_parent_entry'  => null,
				'group_fields'        => null,
				'created_by'          => 1,
				'created_at'          => current_time( 'mysql' ),
				'updated_at'          => current_time( 'mysql' ),
				'group_multiselect'   => false, 
			)
		);

		$group_rows = array(
			'modpress_assets' => array(
				'id'                 => 2,
				'group_name'         => 'Asset Types',
				'group_slug'         => 'assets',
				'group_singular_name' => 'Asset Type',
				'group_description'  => 'The Mod Asset Type group is used to categorise your mods by their asset type.',
				'group_parent'       => array( $mod_type_group_id ),
				'group_parent_entry' => array( $type_term_map['assets'] ?? 0 ),
                'group_multiselect'  => false,
			),
			'modpress_games' => array(
				'id'                 => 3,
				'group_name'         => 'Games',
				'group_slug'         => 'games',
				'group_singular_name' => 'Game',
                'group_hierarchical'  => true,
				'group_description'  => 'The Game that mods belong to. This group is used to categorise your mods by their game.',
				'group_parent'       => array( $mod_type_group_id ),
				'group_parent_entry' => array( $type_term_map['games'] ?? 0 ),
                'group_multiselect'  => false,
			),
			'modpress_software' => array(
				'id'                 => 4,
				'group_name'         => 'Software',
				'group_slug'         => 'software',
				'group_singular_name' => 'Software',
				'group_description'  => 'The Software that mods belong to. This group is used to categorise your mods by their software.',
				'group_parent'       => array( $mod_type_group_id ),
				'group_parent_entry' => array( $type_term_map['software'] ?? 0 ),
                'group_multiselect'  => false,
			),
			'modpress_web' => array(
				'id'                 => 5,
				'group_name'         => 'Web Extensions',
				'group_slug'         => 'web',
				'group_singular_name' => 'Web Extension',
				'group_description'  => 'The Web Extension that mods belong to. This group is used to categorise your mods by their web extensions.',
				'group_parent'       => array( $mod_type_group_id ),
				'group_parent_entry' => array( $type_term_map['web'] ?? 0 ),
                'group_multiselect'  => false,
			),
			'modpress_platforms' => array(
				'id'                 => 6,
				'group_name'         => 'Platforms',
				'group_slug'         => 'platforms',
				'group_singular_name' => 'Platform',
				'group_description'  => 'The Platform that mods belong to. This group is used to categorise your mods by their platform.',
				'group_parent'       => array( $mod_type_group_id ),
				'group_parent_entry' => array_filter( array( $type_term_map['games'] ?? 0, $type_term_map['software'] ?? 0 ) ),
                'group_multiselect'  => true,
			),
			'modpress_language' => array(
				'id'                 => 7,
				'group_name'         => 'Languages',
				'group_slug'         => 'language',
				'group_singular_name' => 'Language',
				'group_description'  => 'The Language that this asset belongs to',
                'group_hierarchical'  => true,
				'group_parent'       => array( 2 ),
				'group_parent_entry' => array_filter( array( $asset_term_map['scripts'] ?? 0, $asset_term_map['libraries'] ?? 0, $asset_term_map['snippets'] ?? 0 ) ),
                'group_multiselect'  => true,
			),
		);

		foreach ( $group_rows as $group_key => $group_data ) {
			$group_id = isset( $group_data['id'] ) ? (int) $group_data['id'] : 0;
			$group_parent_ids = array_values(
				array_filter(
					array_map( 'absint', (array) ( $group_data['group_parent'] ?? array( $mod_type_group_id ) ) )
				)
			);
			$group_parent_entry_ids = array_values(
				array_filter(
					array_map( 'absint', (array) ( $group_data['group_parent_entry'] ?? array() ) )
				)
			);

			DBHelper::insert(
				'groups',
				array(
					'id'                  => $group_id,
					'group_key'           => $group_key,
					'group_name'          => $group_data['group_name'],
					'group_slug'          => $group_data['group_slug'],
					'group_singular_name' => $group_data['group_singular_name'],
					'group_description'   => $group_data['group_description'],
					'group_hierarchical'  => $group_data['group_hierarchical'] ?? false,
					'group_multiselect'   => $group_data['group_multiselect'] ?? false,
					'group_parent'        => self::encode_parent_ids( $group_parent_ids ),
					'group_parent_entry'  => self::encode_parent_ids( $group_parent_entry_ids ),
					'group_fields'        => null,
					'created_by'          => 1,
					'created_at'          => current_time( 'mysql' ),
					'updated_at'          => current_time( 'mysql' ),
				)
			);
		}
	}
    /**
     * Define default types.
     *
     * This method inserts the default types into the database.
     *
     * @return void
     * @since 1.0.0
     */
    public static function default_types(): void {

        $types = array(
            'Asset' => array(
                'slug'        => 'assets',
                'description' => 'Asset type',
            ),
            'Game Mod' => array(
                'slug'        => 'games',
                'description' => 'Game Mod type',
            ),
            'Software Extension' => array(
                'slug'        => 'software',
                'description' => 'Software Extension type',
            ),
            'Web Extension' => array(
                'slug'        => 'web',
                'description' => 'Web Extension type',
            ),
        );

        foreach ( $types as $type => $data ) {
            if ( ! term_exists( $type, 'modpress_type' ) ) {
                $result = wp_insert_term( $type, 'modpress_type', $data );
                if ( is_wp_error( $result ) ) {
                    LoggerHelper::write_log( 'ModPress: Failed to create type term ' . $type . ': ' . $result->get_error_message() );
                }
            }
        }

    }
    /**
     * Define default mod statuses.
     *
     * This method inserts the default mod statuses into the database.
     *
     * @return void
     * @since 1.0.0
     */
    public static function default_mod_status(): void {
        $statuses = [
            'Planned' =>[
                'slug' => 'planned',
                'description' => 'Planned status',
                'status_color' => '#6c757d'
            ],
            'In Development' => [
                'slug' => 'in-development',
                'description' => 'In development status',
                'status_color' => '#0d6efd'
            ],
            'Testing' => [
                'slug' => 'testing',
                'description' => 'Testing status',
                'status_color' => '#0dcaf0'
            ],
            'Released' => [
                'slug' => 'released',
                'description' => 'Released status',
                'status_color' => '#198754'
            ],
            'Deprecated' => [
                'slug' => 'deprecated',
                'description' => 'Deprecated status',
                'status_color' => '#dc3545'
            ],
            'Maintenance' => [
                'slug' => 'maintenance',
                'description' => 'Maintenance status',
                'status_color' => '#ffc107'
            ]
        ];
        foreach ( $statuses as $status => $data ) {
            if ( ! term_exists( $status, 'modpress_mod_status' ) ) {
                $result = wp_insert_term( $status, 'modpress_mod_status' );
                if ( ! is_wp_error( $result ) ) {
                    update_term_meta( $result['term_id'], 'status_color', $data['status_color'] );
                } else {
                    LoggerHelper::write_log( 'ModPress: Failed to create mod status term ' . $status . ': ' . $result->get_error_message() );
                }
            }
        }
    }
    /**
     * Define default changelog categories.
     *
     * This method inserts the default changelog categories into the database.
     *
     * @return void
     * @since 1.0.0
     */
    public static function default_changelog_category(): void {
         $ChangeLogCategories = [ 
            'Removed' => [
                'slug' => 'removed',
                'description' => 'Items that have been removed',
                'category_color' => '#dc3545'
            ],
            'Added' => [
                'slug' => 'added',
                'description' => 'Items that have been added',
                'category_color' => '#28a745',
            ],
            'Updated' => [
                'slug' => 'updated',
                'description' => 'Items that have been updated',
                'category_color' => '#007cba',
            ],
            'Fixed' => [
                'slug' => 'fixed',
                'description' => 'Items that have been fixed',
                'category_color' => '#fd7e14',
            ],
            'Improved' => [
                'slug' => 'improved',
                'description' => 'Items that have been improved',
                'category_color' => '#6f42c1',
            ],
            'Security' => [
                'slug' => 'security',
                'description' => 'Security-related items',
                'category_color' => '#e83e8c',
            ],
            'Performance' => [
                'slug' => 'performance',
                'description' => 'Performance-related items',
                'category_color' => '#20c997',
            ],
            'Deprecated' => [
                'slug' => 'deprecated',
                'description' => 'Deprecated items',
                'category_color' => '#6610f2',
            ],
            'Documentation' => [
                'slug' => 'documentation',
                'description' => 'Documentation-related items',
                'category_color' => '#17a2b8',
            ],
            'Refactored' => [
                'slug' => 'refactored',
                'description' => 'Refactored items',
                'category_color' => '#8b4513',
            ]
        ];
        foreach ( $ChangeLogCategories as $category => $data ) {
            if ( ! term_exists( $category, 'modpress_changelog_category' ) ) {
                $result = wp_insert_term( $category, 'modpress_changelog_category' );
                if ( ! is_wp_error( $result ) ) {
                    // Set the color for the category
                    update_term_meta( $result['term_id'], 'category_color', $data['category_color'] );
                } else {
                    LoggerHelper::write_log( 'ModPress: Failed to create category term ' . $category . ': ' . $result->get_error_message() );
                }
            }
        }
    }
    /**
     * Define default file types.
     *
     * This method inserts the default file types into the database.
     *
     * @return void
     * @since 1.0.0
     */
    public static function default_file_type(): void {
        $file_types = [
            '3DS' => [
                'slug' => '3ds',
                'description' => '3D Studio Scene',
            ],
            '3G2' => [
                'slug' => '3g2',
                'description' => '3GPP2 Multimedia File',
            ],
            '3GP' => [
                'slug' => '3gp',
                'description' => '3GPP Multimedia File',
            ],
            '7Z' => [
                'slug' => '7z',
                'description' => '7-Zip Compressed File',
            ],
            'AAC' => [
                'slug' => 'aac',
                'description' => 'Advanced Audio Coding',
            ],
            'ACCDB' => [
                'slug' => 'accdb',
                'description' => 'Access 2007 Database',
            ],
            'AE' => [
                'slug' => 'ae',
                'description' => 'Adobe After Effects',
            ],
            'AEP' => [
                'slug' => 'aep',
                'description' => 'After Effects Project',
            ],
            'AEPX' => [
                'slug' => 'aepx',
                'description' => 'After Effects XML Project',
            ],
            'AI' => [
                'slug' => 'ai',
                'description' => 'Adobe Illustrator',
            ],
            'AIF' => [
                'slug' => 'aif',
                'description' => 'Audio Interchange File Format',
            ],
            'AIFF' => [
                'slug' => 'aiff',
                'description' => 'Audio Interchange File Format',
            ],
            'ALAC' => [
                'slug' => 'alac',
                'description' => 'Apple Lossless Audio Codec',
            ],
            'AMR' => [
                'slug' => 'amr',
                'description' => 'Adaptive Multi-Rate Codec',
            ],
            'AN' => [
                'slug' => 'an',
                'description' => 'Adobe Animate',
            ],
            'APK' => [
                'slug' => 'apk',
                'description' => 'Android Package',
            ],
            'APP' => [
                'slug' => 'app',
                'description' => 'Application Package',
            ],
            'AR' => [
                'slug' => 'ar',
                'description' => 'Adobe Aero',
            ],
            'ARJ' => [
                'slug' => 'arj',
                'description' => 'ARJ Compressed File',
            ],
            'ARW' => [
                'slug' => 'arw',
                'description' => 'Sony Digital Camera Image',
            ],
            'AU' => [
                'slug' => 'au',
                'description' => 'Adobe Audition',
            ],
            'AVI' => [
                'slug' => 'avi',
                'description' => 'Audio Video Interleave',
            ],
            'AZW' => [
                'slug' => 'azw',
                'description' => 'Amazon Kindle eBook',
            ],
            'BAK' => [
                'slug' => 'bak',
                'description' => 'Backup File',
            ],
            'BASH' => [
                'slug' => 'bash',
                'description' => 'Bash Script File',
            ],
            'BAT' => [
                'slug' => 'bat',
                'description' => 'Batch File',
            ],
            'BIN' => [
                'slug' => 'bin',
                'description' => 'OS Disk Image',
            ],
            'BLEND' => [
                'slug' => 'blend',
                'description' => 'Blender Project File',
            ],
            'BMP' => [
                'slug' => 'bmp',
                'description' => 'Blender Project File',
            ],
            'C' => [
                'slug' => 'c',
                'description' => 'C Source File',
            ],
            'C++' => [
                'slug' => 'c-plus-plus',
                'description' => 'C++ Source File',
            ],
            'C4D' => [
                'slug' => 'c4d',
                'description' => 'Cinema 4D Project',
            ],
            'CER' => [
                'slug' => 'cer',
                'description' => 'Internet Security Certificate',
            ],
            'CFG' => [
                'slug' => 'cfg',
                'description' => 'Configuration File',
            ],
            'CGI' => [
                'slug' => 'cgi',
                'description' => 'Common Gateway Interface Script',
            ],
            'CH' => [
                'slug' => 'ch',
                'description' => 'Adobe Character Animator',
            ],
            'CPP' => [
                'slug' => 'cpp',
                'description' => 'C++ Source Code',
            ],
            'CR2' => [
                'slug' => 'cr2',
                'description' => 'Canon Raw Image',
            ],
            'CRT' => [
                'slug' => 'crt',
                'description' => 'Security Certificate',
            ],
            'CS' => [
                'slug' => 'cs',
                'description' => 'C# Source File',
            ],
            'CSS' => [
                'slug' => 'css',
                'description' => 'Cascading Style Sheets',
            ],
            'CSV' => [
                'slug' => 'csv',
                'description' => 'Comma-Separated Values',
            ],
            'CUE' => [
                'slug' => 'cue',
                'description' => 'Disk Image Cue Sheet',
            ],
            'DAE' => [
                'slug' => 'dae',
                'description' => 'Digital Asset Exchange',
            ],
            'DB' => [
                'slug' => 'db',
                'description' => 'Database File',
            ],
            'DEB' => [
                'slug' => 'deb',
                'description' => 'Debian Software Package',
            ],
            'DJVU' => [
                'slug' => 'djvu',
                'description' => 'DjVu Document',
            ],
            'DLL' => [
                'slug' => 'dll',
                'description' => 'Dynamic Link Library',
            ],
            'DMG' => [
                'slug' => 'dmg',
                'description' => 'Apple Disk Image',
            ],
            'DN' => [
                'slug' => 'dn',
                'description' => 'Adobe Dimension Project',
            ],
            'DNG' => [
                'slug' => 'dng',
                'description' => 'Digital Negative Image',
            ],
            'DOC' => [
                'slug' => 'doc',
                'description' => 'Microsoft Word Document',
            ],
            'DOCX' => [
                'slug' => 'docx',
                'description' => 'Microsoft Word Open XML Document',
            ],
            'DRV' => [
                'slug' => 'drv',
                'description' => 'Device Driver',
            ],
            'DSD' => [
                'slug' => 'dsd',
                'description' => 'Super Audio CD',
            ],
            'DW' => [
                'slug' => 'dw',
                'description' => 'Autodesk Drawing File',
            ],
            'EPS' => [
                'slug' => 'eps',
                'description' => 'Encapsulated PostScript',
            ],
            'EPUB' => [
                'slug' => 'epub',
                'description' => 'ePublication File',
            ],
            'EXE' => [
                'slug' => 'exe',
                'description' => 'Executable File',
            ],
            'FBX' => [
                'slug' => 'fbx',
                'description' => 'Autodesk Filmbox Format',
            ],
            'FIG' => [
                'slug' => 'fig',
                'description' => 'Figma',
            ],
            'FLAC' => [
                'slug' => 'flac',
                'description' => 'Free Lossless Audio Codec',
            ],
            'FLV' => [
                'slug' => 'flv',
                'description' => 'Flash Video File',
            ],
            'FNT' => [
                'slug' => 'fnt',
                'description' => 'Font File',
            ],
            'FON' => [
                'slug' => 'fon',
                'description' => 'Font File',
            ],
            'FR' => [
                'slug' => 'fr',
                'description' => 'Adobe FrameMaker File',
            ],
            'GEOJSON' => [
                'slug' => 'geojson',
                'description' => 'Geographic JSON File',
            ],
            'GIF' => [
                'slug' => 'gif',
                'description' => 'Graphics Interchange Format',
            ],
            'GLB' => [
                'slug' => 'glb',
                'description' => 'GL Transmission Format (Binary)',
            ],
            'GLTF' => [
                'slug' => 'gltf',
                'description' => 'GL Transmission Format',
            ],
            'GPG' => [
                'slug' => 'gpg',
                'description' => 'GNU Privacy Guard File',
            ],
            'GPX' => [
                'slug' => 'gpx',
                'description' => 'GPS Exchange Format',
            ],
            'GZ' => [
                'slug' => 'gz',
                'description' => 'Gzip Compressed File',
            ],
            'H5' => [
                'slug' => 'h5',
                'description' => 'HDF5 File Format',
            ],
            'HDF5' => [
                'slug' => 'hdf5',
                'description' => 'Hierarchical Data Format',
            ],
            'HTM' => [
                'slug' => 'htm',
                'description' => 'HyperText Markup Language',
            ],
            'HTML' => [
                'slug' => 'html',
                'description' => 'HyperText Markup Language',
            ],
            'IC' => [
                'slug' => 'ic',
                'description' => 'Adobe InCopy',
            ],
            'ICO' => [
                'slug' => 'ico',
                'description' => 'Windows Icon File',
            ],
            'ID' => [
                'slug' => 'id',
                'description' => 'Adobe InDesign',
            ],
            'IMG' => [
                'slug' => 'img',
                'description' => 'Disk Image File',
            ],
            'INI' => [
                'slug' => 'ini',
                'description' => 'Initialization File',
            ],
            'ISO' => [
                'slug' => 'iso',
                'description' => 'Disc Image File',
            ],
            'JAVA' => [
                'slug' => 'java',
                'description' => 'Java Source File',
            ],
            'JPEG' => [
                'slug' => 'jpeg',
                'description' => 'JPEG Image',
            ],
            'JPG' => [
                'slug' => 'jpg',
                'description' => 'JPEG Image',
            ],
            'JS' => [
                'slug' => 'js',
                'description' => 'JavaScript File',
            ],
            'JSON' => [
                'slug' => 'json',
                'description' => 'JavaScript Object Notation',
            ],
            'JSX' => [
                'slug' => 'jsx',
                'description' => 'JavaScript XML',
            ],
            'KEY' => [
                'slug' => 'key',
                'description' => 'Apple Keynote File',
            ],
            'KML' => [
                'slug' => 'kml',
                'description' => 'Keyhole Markup Language',
            ],
            'KMZ' => [
                'slug' => 'kmz',
                'description' => 'Compressed KML File',
            ],
            'LOG' => [
                'slug' => 'log',
                'description' => 'Log File',
            ],
            'LOTTIE' => [
                'slug' => 'lottie',
                'description' => 'Lottie Animation File',
            ],
            'LR' => [
                'slug' => 'lr',
                'description' => 'Adobe Lightroom',
            ],
            'LRC' => [
                'slug' => 'lrc',
                'description' => 'Lyrics File - Adobe Audition',
            ],
            'LXO' => [
                'slug' => 'lxo',
                'description' => 'Luxology Modo Scene',
            ],
            'M4A' => [
                'slug' => 'm4a',
                'description' => 'MPEG-4 Audio',
            ],
            'M4V' => [
                'slug' => 'm4v',
                'description' => 'MPEG-4 Video',
            ],
            'MA' => [
                'slug' => 'ma',
                'description' => 'Autodesk Maya ASCII Scene',
            ],
            'MAT' => [
                'slug' => 'mat',
                'description' => 'MATLAB Data File',
            ],
            'MAX' => [
                'slug' => 'max',
                'description' => '3ds Max Scene File',
            ],
            'MB' => [
                'slug' => 'mb',
                'description' => 'Maya Binary Scene File',
            ],
            'MDB' => [
                'slug' => 'mdb',
                'description' => 'Microsoft Access Database',
            ],
            'ME' => [
                'slug' => 'me',
                'description' => 'Adobe Media Encoder',
            ],
            'MKV' => [
                'slug' => 'mkv',
                'description' => 'Matroska Video File',
            ],
            'MO' => [
                'slug' => 'mo',
                'description' => 'Machine Object File',
            ],
            'MOBI' => [
                'slug' => 'mobi',
                'description' => 'Kindle eBook',
            ],
            'MOGRT' => [
                'slug' => 'mogrt',
                'description' => 'Motion Graphics Template',
            ],
            'MOV' => [
                'slug' => 'mov',
                'description' => 'Apple QuickTime Movie',
            ],
            'MP3' => [
                'slug' => 'mp3',
                'description' => 'MP3 Audio File',
            ],
            'MP4' => [
                'slug' => 'mp4',
                'description' => 'MPEG-4 Video File',
            ],
            'MPG' => [
                'slug' => 'mpg',
                'description' => 'MPEG Video File',
            ],
            'MSG' => [
                'slug' => 'msg',
                'description' => 'Outlook Email Message',
            ],
            'MSI' => [
                'slug' => 'msi',
                'description' => 'Microsoft Installer Package',
            ],
            'MTL' => [
                'slug' => 'mtl',
                'description' => 'Material Template Library',
            ],
            'NEF' => [
                'slug' => 'nef',
                'description' => 'Nikon Electronic Format',
            ],
            'OBB' => [
                'slug' => 'obb',
                'description' => 'Opaque Binary Blob',
            ],
            'OBJ' => [
                'slug' => 'obj',
                'description' => 'Wavefront 3D Object',
            ],
            'ODP' => [
                'slug' => 'odp',
                'description' => 'OpenDocument Presentation',
            ],
            'ODS' => [
                'slug' => 'ods',
                'description' => 'OpenDocument Spreadsheet',
            ],
            'ODT' => [
                'slug' => 'odt',
                'description' => 'OpenDocument Text',
            ],
            'OGG' => [
                'slug' => 'ogg',
                'description' => 'Ogg Vorbis Audio',
            ],
            'ONNX' => [
                'slug' => 'onnx',
                'description' => 'Open Neural Network Exchange',
            ],
            'OPUS' => [
                'slug' => 'opus',
                'description' => 'Opus Audio Format',
            ],
            'ORF' => [
                'slug' => 'orf',
                'description' => 'Olympus Raw File',
            ],
            'OST' => [
                'slug' => 'ost',
                'description' => 'Outlook Offline Data',
            ],
            'OTF' => [
                'slug' => 'otf',
                'description' => 'OpenType Font',
            ],
            'P' => [
                'slug' => 'p',
                'description' => 'PowerPoint Presentation',
            ],
            'PAT' => [
                'slug' => 'pat',
                'description' => 'Pattern File',
            ],
            'PB' => [
                'slug' => 'pb',
                'description' => 'TensorFlow Model File',
            ],
            'PCM' => [
                'slug' => 'pcm',
                'description' => 'Pulse-Code Modulation',
            ],
            'PDF' => [
                'slug' => 'pdf',
                'description' => 'Portable Document Format',
            ],
            'PEF' => [
                'slug' => 'pef',
                'description' => 'Pentax Raw Image',
            ],
            'PEM' => [
                'slug' => 'pem',
                'description' => 'Cryptographic Key File',
            ],
            'PFX' => [
                'slug' => 'pfx',
                'description' => 'Personal Information Exchange File',
            ],
            'PHP' => [
                'slug' => 'php',
                'description' => 'PHP Script',
            ],
            'PKG' => [
                'slug' => 'pkg',
                'description' => 'Mac OS X Installer',
            ],
            'PL' => [
                'slug' => 'pl',
                'description' => 'Adobe Prelude',
            ],
            'PLY' => [
                'slug' => 'ply',
                'description' => '3D Scan File (Polygon File Format)',
            ],
            'PNG' => [
                'slug' => 'png',
                'description' => 'Portable Network Graphics',
            ],
            'PPT' => [
                'slug' => 'ppt',
                'description' => 'PowerPoint Presentation',
            ],
            'PPTX' => [
                'slug' => 'pptx',
                'description' => 'PowerPoint Open XML Presentation',
            ],
            'PR' => [
                'slug' => 'pr',
                'description' => 'Adobe Premiere Pro',
            ],
            'PRJ' => [
                'slug' => 'prj',
                'description' => 'Project Dependency File',
            ],
            'PRPROJ' => [
                'slug' => 'prproj',
                'description' => 'Premiere Pro Project',
            ],
            'PS' => [
                'slug' => 'ps',
                'description' => 'Adobe Photoshop',
            ],
            'PS1' => [
                'slug' => 'ps1',
                'description' => 'PowerShell Script',
            ],
            'PSC' => [
                'slug' => 'psc',
                'description' => 'Adobe Presenter Content',
            ],
            'PSD' => [
                'slug' => 'psd',
                'description' => 'Adobe Photoshop Document',
            ],
            'PSDT' => [
                'slug' => 'psdt',
                'description' => 'Photoshop Template',
            ],
            'PST' => [
                'slug' => 'pst',
                'description' => 'Outlook Email Backup',
            ],
            'PSX' => [
                'slug' => 'psx',
                'description' => 'Adobe Photoshop Extended',
            ],
            'PT' => [
                'slug' => 'pt',
                'description' => 'Microsoft PowerPoint File',
            ],
            'PTH' => [
                'slug' => 'pth',
                'description' => 'PyTorch Model File',
            ],
            'PTX' => [
                'slug' => 'ptx',
                'description' => 'Pro Tools Session File',
            ],
            'PY' => [
                'slug' => 'py',
                'description' => 'Python Script',
            ],
            'RAF' => [
                'slug' => 'raf',
                'description' => 'Fujifilm RAW File',
            ],
            'RAR' => [
                'slug' => 'rar',
                'description' => 'RAR Archive',
            ],
            'RAW' => [
                'slug' => 'raw',
                'description' => 'Raw Image File',
            ],
            'RB' => [
                'slug' => 'rb',
                'description' => 'Ruby Script',
            ],
            'RDATA' => [
                'slug' => 'rdata',
                'description' => 'R Language Data File',
            ],
            'RESUME' => [
                'slug' => 'resume',
                'description' => 'Resume File',
            ],
            'RM' => [
                'slug' => 'rm',
                'description' => 'RealMedia File',
            ],
            'RMVB' => [
                'slug' => 'rmvb',
                'description' => 'RealMedia Variable Bitrate',
            ],
            'RPM' => [
                'slug' => 'rpm',
                'description' => 'Red Hat Linux Package',
            ],
            'RTF' => [
                'slug' => 'rtf',
                'description' => 'Rich Text Format',
            ],
            'RU' => [
                'slug' => 'ru',
                'description' => 'Adobe Rush Project',
            ],
            'RVT' => [
                'slug' => 'rvt',
                'description' => 'Revit Project File',
            ],
            'SAFARIEXTZ' => [
                'slug' => 'safariextz',
                'description' => 'Safari Extension',
            ],
            'SAV' => [
                'slug' => 'sav',
                'description' => 'SPSS Data File',
            ],
            'SCPT' => [
                'slug' => 'scpt',
                'description' => 'AppleScript Script File',
            ],
            'SDF' => [
                'slug' => 'sdf',
                'description' => 'Structured Data File',
            ],
            'SFX' => [
                'slug' => 'sfx',
                'description' => 'Self-Extracting Archive',
            ],
            'SH' => [
                'slug' => 'sh',
                'description' => 'Shell Script',
            ],
            'SHP' => [
                'slug' => 'shp',
                'description' => 'ESRI Shapefile',
            ],
            'SKETCH' => [
                'slug' => 'sketch',
                'description' => 'Sketch Design File',
            ],
            'SKP' => [
                'slug' => 'skp',
                'description' => 'SketchUp File',
            ],
            'SLDPRT' => [
                'slug' => 'sldprt',
                'description' => 'SolidWorks Part File',
            ],
            'SLDX' => [
                'slug' => 'sldx',
                'description' => 'PowerPoint Slide Show File',
            ],
            'SP' => [
                'slug' => 'sp',
                'description' => 'Adobe Soundbooth Project',
            ],
            'SQL' => [
                'slug' => 'sql',
                'description' => 'Structured Query Language File',
            ],
            'STEP' => [
                'slug' => 'step',
                'description' => 'STEP 3D File Format',
            ],
            'STL' => [
                'slug' => 'stl',
                'description' => 'Stereolithography File',
            ],
            'SVG' => [
                'slug' => 'svg',
                'description' => 'Scalable Vector Graphics',
            ],
            'SWF' => [
                'slug' => 'swf',
                'description' => 'Shockwave Flash File',
            ],
            'SYS' => [
                'slug' => 'sys',
                'description' => 'System File',
            ],
            'TAR' => [
                'slug' => 'tar',
                'description' => 'Unix Archive File',
            ],
            'TEMP' => [
                'slug' => 'temp',
                'description' => 'Temporary File',
            ],
            'TFLITE' => [
                'slug' => 'tflite',
                'description' => 'TensorFlow Lite Model',
            ],
            'TGA' => [
                'slug' => 'tga',
                'description' => 'Targa Graphic File',
            ],
            'THMX' => [
                'slug' => 'thmx',
                'description' => 'Office Theme File',
            ],
            'TIF' => [
                'slug' => 'tif',
                'description' => 'Tagged Image File Format',
            ],
            'TIFF' => [
                'slug' => 'tiff',
                'description' => 'Tagged Image File Format',
            ],
            'TMP' => [
                'slug' => 'tmp',
                'description' => 'Temporary File',
            ],
            'TORRENT' => [
                'slug' => 'torrent',
                'description' => 'Torrent File',
            ],
            'TS' => [
                'slug' => 'ts',
                'description' => 'TypeScript File',
            ],
            'TSX' => [
                'slug' => 'tsx',
                'description' => 'TypeScript XML File',
            ],
            'TTF' => [
                'slug' => 'ttf',
                'description' => 'TrueType Font',
            ],
            'TXT' => [
                'slug' => 'txt',
                'description' => 'Text File',
            ],
            'USD' => [
                'slug' => 'usd',
                'description' => 'Pixar Universal Scene Description',
            ],
            'USDA' => [
                'slug' => 'usda',
                'description' => 'Pixar USD ASCII',
            ],
            'USDC' => [
                'slug' => 'usdc',
                'description' => 'Pixar USD ASCII',
            ],
            'USDZ' => [
                'slug' => 'usdz',
                'description' => 'Universal Scene Description Zip',
            ],
            'VHD' => [
                'slug' => 'vhd',
                'description' => 'Virtual Hard Disk (Microsoft)',
            ],
            'VHDX' => [
                'slug' => 'vhdx',
                'description' => 'Virtual Hard Disk Extended',
            ],
            'VOB' => [
                'slug' => 'vob',
                'description' => 'DVD Video File',
            ],
            'VTT' => [
                'slug' => 'vtt',
                'description' => 'Web Video Text Tracks',
            ],
            'W' => [
                'slug' => 'w',
                'description' => 'Microsoft Word File',
            ],
            'WAV' => [
                'slug' => 'wav',
                'description' => 'Waveform Audio File',
            ],
            'WEBM' => [
                'slug' => 'webm',
                'description' => 'Web Media File',
            ],
            'WEBP' => [
                'slug' => 'webp',
                'description' => 'Web Image Format',
            ],
            'WMA' => [
                'slug' => 'wma',
                'description' => 'Windows Media Audio',
            ],
            'WMV' => [
                'slug' => 'wmv',
                'description' => 'Windows Media Video',
            ],
            'WOFF' => [
                'slug' => 'woff',
                'description' => 'Web Open Font Format',
            ],
            'X' => [
                'slug' => 'x',
                'description' => 'Microsoft Excel Chart',
            ],
            'XCF' => [
                'slug' => 'xcf',
                'description' => 'GIMP Image File',
            ],
            'XD' => [
                'slug' => 'xd',
                'description' => 'Adobe XD',
            ],
            'XLS' => [
                'slug' => 'xls',
                'description' => 'Excel Spreadsheet',
            ],
            'XLSX' => [
                'slug' => 'xlsx',
                'description' => 'Excel Open XML Spreadsheet',
            ],
            'XML' => [
                'slug' => 'xml',
                'description' => 'eXtensible Markup Language',
            ],
            'YML' => [
                'slug' => 'yml',
                'description' => 'YAML Configuration File',
            ],
            'ZIP' => [
                'slug' => 'zip',
                'description' => 'ZIP Archive',
            ],
        ];

        foreach ( $file_types as $name => $data ) {
            if ( ! term_exists( $name, 'modpress_file_type' ) ) {
                $result = wp_insert_term(
                    $name,
                    'modpress_file_type',
                    array(
                        'slug' => $data['slug'],
                        'description' => $data['description'],
                    )
                );
                if ( is_wp_error( $result ) ) {
                    LoggerHelper::write_log( 'ModPress: Failed to create file type term ' . $name . ': ' . $result->get_error_message() );
                }
            }
        }
    }
    /**
     * Define default softwares.
     *
     * This method inserts the default softwares into the database.
     *
     * @return void
     * @since 1.0.0
     */
    private static function default_softwares(): void {
        $software = [ 
            'VSCode' =>[
                'slug' => 'vscode',
                'description' => 'VSCode software'
            ],
            'GitHub' =>[
                'slug' => 'github',
                'description' => 'GitHub software'
            ],
            'WordPress' =>[
                'slug' => 'wordpress',
                'description' => 'WordPress software'
            ],
            'Elementor' =>[
                'slug' => 'elementor',
                'description' => 'Elementor software'
            ],
            'WooCommerce' =>[
                'slug' => 'woocommerce',
                'description' => 'WooCommerce software'
            ],
            'Discord' =>[
                'slug' => 'discord',
                'description' => 'Discord software'
            ],
            'Steam' =>[
                'slug' => 'steam',
                'description' => 'Steam software'
            ],
            'Epic Games' =>[
                'slug' => 'epic-games',
                'description' => 'Epic Games software'
            ],
            'Unity' =>[
                'slug' => 'unity',
                'description' => 'Unity software'
            ],
            'Unreal Engine' =>[
                'slug' => 'unreal-engine',
                'description' => 'Unreal Engine software'
            ],
            'Blender' =>[
                'slug' => 'blender',
                'description' => 'Blender software'
            ],
            'Photoshop' =>[
                'slug' => 'photoshop',
                'description' => 'Photoshop software'
            ],
            'Illustrator' =>[
                'slug' => 'illustrator',
                'description' => 'Illustrator software'
            ]
        ];
        foreach ( $software as $name => $data ) {
            if ( ! term_exists( $name, 'modpress_software' ) ) {
                $result = wp_insert_term( $name, 'modpress_software', array(
                    'slug'        => $data['slug'],
                    'description' => $data['description'],
                ) );
                if ( is_wp_error( $result ) ) {
                    LoggerHelper::write_log( 'ModPress: Failed to create software term ' . $name . ': ' . $result->get_error_message() );
                }
            }
        }
    }
    /**
     * Define default web platforms.
     *
     * This method inserts the default web platforms into the database.
     *
     * @return void
     * @since 1.0.0
     */
    private static function default_web(): void {
         // Default Web Platforms
        $Web = [ 
            'VSCode.Dev'=>[
                'slug' => 'vscode-dev',
                'description' => 'VSCode.Dev web platform'
            ], 
            'GitHub'=>[
                'slug' => 'github',
                'description' => 'GitHub web platform'
            ], 
            'WordPress'=>[
                'slug' => 'wordpress',
                'description' => 'WordPress web platform'
            ], 
            'Elementor'=>[
                'slug' => 'elementor',
                'description' => 'Elementor WordPress Plugin'
            ], 
            'WooCommerce'=>[
                'slug' => 'woocommerce',
                'description' => 'WooCommerce WordPress Plugin'
            ], 
            'Discord'=>[
                'slug' => 'discord',
                'description' => 'Discord web platform'
            ]
        ];
        foreach ( $Web as $name => $data ) {
            if ( ! term_exists( $name, 'modpress_web' ) ) {
                $result = wp_insert_term( $name, 'modpress_web', array(
                    'slug'        => $data['slug'],
                    'description' => $data['description'],
                ) );
                if ( is_wp_error( $result ) ) {
                    LoggerHelper::write_log( 'ModPress: Failed to create web platform term ' . $name . ': ' . $result->get_error_message() );
                }
            }
        }
    }
    /**
     * Define default games.
     *
     * This method inserts the default games into the database.
     *
     * @return void
     * @since 1.0.0
     */
    private static function default_games(): void {
        // Default games
        $games = [ 
            'The Elder Scrolls'=>[
                'slug' => 'the-elder-scrolls',
                'description' => 'The Elder Scrolls game series'
            ],
            'The Elder Scrolls V: Skyrim'=>[
                'slug' => 'the-elder-scrolls-v-skyrim',
                'description' => 'The Elder Scrolls V: Skyrim game',
                'parent' => 'the-elder-scrolls'
            ],
            'The Elder Scrolls IV: Oblivion'=>[
                'slug' => 'the-elder-scrolls-iv-oblivion',
                'description' => 'The Elder Scrolls IV: Oblivion game',
                'parent' => 'the-elder-scrolls'
            ],
            'Fallout'=>[
                'slug' => 'fallout',
                'description' => 'Fallout game'
            ],
            'Fallout 4'=>[
                'slug' => 'fallout-4',
                'description' => 'Fallout 4 game',
                'parent' => 'fallout'
            ],
            'Fallout: New Vegas'=>[
                'slug' => 'fallout-new-vegas',
                'description' => 'Fallout: New Vegas game',
                'parent' => 'fallout'
            ],
            'Minecraft'=>[
                'slug' => 'minecraft',
                'description' => 'Minecraft game'
            ],
            'World of Warcraft'=>[
                'slug' => 'world-of-warcraft',
                'description' => 'World of Warcraft game'
            ],
            'League of Legends'=>[
                'slug' => 'league-of-legends',
                'description' => 'League of Legends game'
            ],
            'Counter-Strike 2'=>[
                'slug' => 'counter-strike-2',
                'description' => 'Counter-Strike 2 game'
            ],
            'Dota 2'=>[
                'slug' => 'dota-2',
                'description' => 'Dota 2 game'
            ],
            'Apex Legends'=>[
                'slug' => 'apex-legends',
                'description' => 'Apex Legends game'
            ],
            'Fortnite'=>[
                'slug' => 'fortnite',
                'description' => 'Fortnite game'
            ],
            'Among Us'=>[
                'slug' => 'among-us',
                'description' => 'Among Us game'
            ],
            'Stardew Valley'=>[
                'slug' => 'stardew-valley',
                'description' => 'Stardew Valley game'
            ],
            'The Sims 4'=>[
                'slug' => 'the-sims-4',
                'description' => 'The Sims 4 game'
            ],
            'Cities: Skylines'=>[
                'slug' => 'cities-skylines',
                'description' => 'Cities: Skylines game'
            ],
            'Euro Truck Simulator 2'=>[
                'slug' => 'euro-truck-simulator-2',
                'description' => 'Euro Truck Simulator 2 game'
            ],
            'American Truck Simulator'=>[
                'slug' => 'american-truck-simulator',
                'description' => 'American Truck Simulator game'
            ],
            'No Man\'s Sky'=>[
                'slug' => 'no-mans-sky',
                'description' => 'No Man\'s Sky game'
            ],
            'Kerbal Space Program'=>[
                'slug' => 'kerbal-space-program',
                'description' => 'Kerbal Space Program game'
            ],
            'Starfield'=>[
                'slug' => 'starfield',
                'description' => 'Starfield game'
            ],
            'Microsoft Flight Simulator'=>[
                'slug' => 'microsoft-flight-simulator',
                'description' => 'Microsoft Flight Simulator game'
            ],
            'Microsoft Flight Simulator 2022'=>[
                'slug' => 'microsoft-flight-simulator-2022',
                'description' => 'Microsoft Flight Simulator 2022 game',
                'parent' => 'microsoft-flight-simulator'
            ],
            'Microsoft Flight Simulator 2024'=>[
                'slug' => 'microsoft-flight-simulator-2024',
                'description' => 'Microsoft Flight Simulator 2024 game',
                'parent' => 'microsoft-flight-simulator'
            ],
            'Space Engineers'=>[
                'slug' => 'space-engineers',
                'description' => 'Space Engineers game'
            ]
        ];
        foreach ( $games as $name => $data ) {
            if ( ! term_exists( $name, 'modpress_games' ) ) {
                $result = wp_insert_term( $name, 'modpress_games', array(
                    'slug'        => $data['slug'],
                    'description' => $data['description'],
                ) );
                if ( is_wp_error( $result ) ) {
                    LoggerHelper::write_log( 'ModPress: Failed to create game term ' . $name . ': ' . $result->get_error_message() );
                }
            }
        }
    }
    /**
     * Define default platforms.
     *
     * This method inserts the default platforms into the database.
     *
     * @return void
     * @since 1.0.0
     */
    private static function default_platforms(): void {
        $platforms = [ 
            'Windows PC' =>[
                'slug' => 'windows-pc',
                'description' => 'Windows PC platform'
            ],
            'macOS PC' =>[
                'slug' => 'macos-pc',
                'description' => 'macOS PC platform'
            ],
            'Linux PC' =>[
                'slug' => 'linux-pc',
                'description' => 'Linux PC platform'
            ],
            'Xbox One' =>[
                'slug' => 'xbox-one',
                'description' => 'Xbox One platform'
            ],
            'Xbox Series X/S' =>[
                'slug' => 'xbox-series-x-s',
                'description' => 'Xbox Series X/S platform'
            ],
            'PlayStation 4' =>[
                'slug' => 'playstation-4',
                'description' => 'PlayStation 4 platform'
            ],
            'PlayStation 5' =>[
                'slug' => 'playstation-5',
                'description' => 'PlayStation 5 platform'
            ],
            'Nintendo Switch' =>[
                'slug' => 'nintendo-switch',
                'description' => 'Nintendo Switch platform'
            ],
            'Nintendo Switch 2' =>[
                'slug' => 'nintendo-switch-2',
                'description' => 'Nintendo Switch 2 platform'
            ],
            'Android Mobile' =>[
                'slug' => 'android-mobile',
                'description' => 'Android Mobile platform'
            ],
            'iOS Mobile' =>[
                'slug' => 'ios-mobile',
                'description' => 'iOS Mobile platform'
            ],
            'Meta Quest 2' =>[
                'slug' => 'meta-quest-2',
                'description' => 'Meta Quest 2 platform'
            ],
            'Meta Quest Pro' =>[
                'slug' => 'meta-quest-pro',
                'description' => 'Meta Quest Pro platform'
            ],
            'Meta Quest 3' =>[
                'slug' => 'meta-quest-3',
                'description' => 'Meta Quest 3 platform'
            ],
            'Steam VR' =>[
                'slug' => 'steam-vr',
                'description' => 'Steam VR platform'
            ]
        ];
        foreach ( $platforms as $name => $data ) {
            if ( ! term_exists( $name, 'modpress_platforms' ) ) {
                $result = wp_insert_term( $name, 'modpress_platforms', array(
                    'slug'        => $data['slug'],
                    'description' => $data['description'],
                ) );
                if ( is_wp_error( $result ) ) {
                    LoggerHelper::write_log( 'ModPress: Failed to create platform term ' . $name . ': ' . $result->get_error_message() );
                }
            }
        }
    }
    /**
     * Define default asset types.
     *
     * This method inserts the default asset types into the database.
     *
     * @return void
     * @since 1.0.0
     */
    private static function default_assets(): void {
        $asset_types = [ 
            'Textures' =>[
                'slug' => 'textures',
                'description' => 'Texture assets'
            ], 
            'Models' =>[
                'slug' => 'models',
                'description' => 'Model assets'
            ],
            'Sounds' =>[
                'slug' => 'sounds',
                'description' => 'Sound assets'
            ],
            'Scripts' =>[
                'slug' => 'scripts',
                'description' => 'Script assets'
            ],
            'Libraries' =>[
                'slug' => 'libraries',
                'description' => 'Library assets'
            ],
            'Snippets' =>[
                'slug' => 'snippets',
                'description' => 'Snippet assets'
            ],
            'Shaders' =>[
                'slug' => 'shaders',
                'description' => 'Shader assets'
            ],
            'Animations' =>[
                'slug' => 'animations',
                'description' => 'Animation assets'
            ],
            'Materials' =>[
                'slug' => 'materials',
                'description' => 'Material assets'
            ],
            'Prefabs' =>[
                'slug' => 'prefabs',
                'description' => 'Prefab assets'
            ],
            'Sprites' =>[
                'slug' => 'sprites',
                'description' => 'Sprite assets'
            ],
            'Fonts' =>[
                'slug' => 'fonts',
                'description' => 'Font assets'
            ],
            'Particles' =>[
                'slug' => 'particles',
                'description' => 'Particle assets'
            ],
            'Meshes' =>[
                'slug' => 'meshes',
                'description' => 'Mesh assets'
            ],
            'Audio' =>[
                'slug' => 'audio',
                'description' => 'Audio assets'
            ],
            'Videos' =>[
                'slug' => 'videos',
                'description' => 'Video assets'
            ],
            'Documents' =>[
                'slug' => 'documents',
                'description' => 'Document assets'
            ],
            'Archives' =>[
                'slug' => 'archives',
                'description' => 'Archive assets'
            ]
        ];
        foreach ( $asset_types as $name => $data ) {
            if ( ! term_exists( $name, 'modpress_assets' ) ) {
                $result = wp_insert_term( $name, 'modpress_assets', array(
                    'slug'        => $data['slug'],
                    'description' => $data['description'],
                ) );
                if ( is_wp_error( $result ) ) {
                    LoggerHelper::write_log( 'ModPress: Failed to create asset type term ' . $name . ': ' . $result->get_error_message() );
                }
            }
        }
    }
    /**
     * Define default languages.
     *
     * This method inserts the default programming languages into the database.
     *
     * @return void
     * @since 1.0.0
     */
    private static function default_languages(): void {
        // Default languages
        $languages = [ 
            'PHP'=>[
                'slug' => 'php',
                'description' => 'PHP language'
            ], 
            'JavaScript'=>[
                'slug' => 'javascript',
                'description' => 'JavaScript language'
            ], 
            'Python'=>[
                'slug' => 'python',
                'description' => 'Python language'
            ], 
            'CSS'=>[
                'slug' => 'css',
                'description' => 'CSS language'
            ], 
            'HTML'=>[
                'slug' => 'html',
                'description' => 'HTML language'
            ], 
            'TypeScript'=>[
                'slug' => 'typescript',
                'description' => 'TypeScript language'
            ], 
            'SQL'=>[
                'slug' => 'sql',
                'description' => 'SQL language'
            ], 
            'Bash'=>[
                'slug' => 'bash',
                'description' => 'Bash language'
            ], 
            'C#'=>[
                'slug' => 'csharp',
                'description' => 'C# language'
            ], 
            'C++'=>[
                'slug' => 'cpp',
                'description' => 'C++ language'
            ], 
            'Java'=>[
                'slug' => 'java',
                'description' => 'Java language'
            ], 
            'Ruby'=>[
                'slug' => 'ruby',
                'description' => 'Ruby language'
            ], 
            'Go'=>[
                'slug' => 'go',
                'description' => 'Go language'
            ], 
            'Swift'=>[
                'slug' => 'swift',
                'description' => 'Swift language'
            ], 
            'Kotlin'=>[
                'slug' => 'kotlin',
                'description' => 'Kotlin language'
            ],
            'Papyrus'=>[
                'slug' => 'papyrus',
                'description' => 'Papyrus language'
            ], 
            'Lua'=>[
                'slug' => 'lua',
                'description' => 'Lua language'
            ] ];
        foreach ( $languages as $name => $data ) {
            if ( ! term_exists( $name, 'modpress_language' ) ) {
                $result = wp_insert_term( $name, 'modpress_language', array(
                    'slug'        => $data['slug'],
                    'description' => $data['description'],
                ) );
                if ( is_wp_error( $result ) ) {
                    LoggerHelper::write_log( 'ModPress: Failed to create language term ' . $name . ': ' . $result->get_error_message() );
                }
            }
        }
    }
    /**
     * Define default tags.
     *
     * This method inserts the default tags into the database.
     *
     * @return void
     * @since 1.0.0
     */
    private static function default_tags(): void {
        $tags = [ 
            'UI'=>[
                'slug' => 'ui'
            ], 
            'Graphics'=>[
                'slug' => 'graphics'
            ], 
            'Performance'=>[
                'slug' => 'performance'
            ], 
            'Balance'=>[
                'slug' => 'balance'
            ], 
            'Quality of Life'=>[
                'slug' => 'quality-of-life'
            ], 
            'Multiplayer'=>[
                'slug' => 'multiplayer'
            ], 
            'Single Player'=>[
                'slug' => 'single-player'
            ], 
            'Survival'=>[
                'slug' => 'survival'
            ], 
            'Roleplaying'=>[
                'slug' => 'roleplaying'
            ], 
            'Adventure'=>[
                'slug' => 'adventure'
            ], 
            'Simulation'=>[
                'slug' => 'simulation'
            ], 
            'Strategy'=>[
                'slug' => 'strategy'
            ], 
            'Action'=>[
                'slug' => 'action'
            ], 
            'Horror'=>[
                'slug' => 'horror'
            ], 
            'Comedy'=>[
                'slug' => 'comedy'
            ], 
            'Realism'=>[
                'slug' => 'realism'
            ], 
            'Fantasy'=>[
                'slug' => 'fantasy'
            ], 
            'Sci-Fi'=>[
                'slug' => 'sci-fi'
            ], 
            'Medieval'=>[
                'slug' => 'medieval'
            ], 
            'Modern'=>[
                'slug' => 'modern'
            ]
        ];
        foreach ( $tags as $tag => $tag_data ) {
            if ( ! term_exists( $tag, 'modpress_tags' ) ) {
                $result = wp_insert_term( $tag, 'modpress_tags', [ 'slug' => $tags[$tag]['slug'] ] );
                if ( is_wp_error( $result ) ) {
                    LoggerHelper::write_log( 'ModPress: Failed to create tag term ' . $tag . ': ' . $result->get_error_message() );
                }
            }
        }
    }
    /**
     * Register default licences.
     *
     * @return void
     * @since 1.0.0
     */
    private static function default_licences(): void {
        $licences = [
            'GPL'=>[
                'slug' => 'gpl'
            ],
            'GPLv2'=>[
                'slug' => 'gplv2',
                'parent' => 'gpl'
            ],
            'GPLv3'=>[
                'slug' => 'gplv3',
                'parent' => 'gpl'
            ],
            'MIT'=>[
                'slug' => 'mit'
            ],
            'Creative Commons'=>[
                'slug' => 'creative-commons'
            ],
            'Proprietary'=>[
                'slug' => 'proprietary'
            ],
            'Public Domain'=>[
                'slug' => 'public-domain'
            ],
            'Unlicensed'=>[
                'slug' => 'unlicensed'
            ]
        ];
        foreach ( $licences as $licence => $licence_data ) {
            if ( ! term_exists( $licence, 'modpress_licences' ) ) {
                $result = wp_insert_term( $licence, 'modpress_licences', [ 'slug' => $licences[$licence]['slug'] ] );
                if ( is_wp_error( $result ) ) {
                    LoggerHelper::write_log( 'ModPress: Failed to create licence term ' . $licence . ': ' . $result->get_error_message() );
                }
            }
        }
    }

}