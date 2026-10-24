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
	public function __construct() {
		// Initialize default values here.
	}

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

	private static function encode_parent_ids( array $ids = array() ): ?string {
		$values = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
		return empty( $values ) ? null : serialize( $values );
	}

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
			),
			'modpress_software' => array(
				'id'                 => 4,
				'group_name'         => 'Software',
				'group_slug'         => 'software',
				'group_singular_name' => 'Software',
				'group_description'  => 'The Software that mods belong to. This group is used to categorise your mods by their software.',
				'group_parent'       => array( $mod_type_group_id ),
				'group_parent_entry' => array( $type_term_map['software'] ?? 0 ),
			),
			'modpress_web' => array(
				'id'                 => 5,
				'group_name'         => 'Web Extensions',
				'group_slug'         => 'web',
				'group_singular_name' => 'Web Extension',
				'group_description'  => 'The Web Extension that mods belong to. This group is used to categorise your mods by their web extensions.',
				'group_parent'       => array( $mod_type_group_id ),
				'group_parent_entry' => array( $type_term_map['web'] ?? 0 ),
			),
			'modpress_platforms' => array(
				'id'                 => 6,
				'group_name'         => 'Platforms',
				'group_slug'         => 'platforms',
				'group_singular_name' => 'Platform',
				'group_description'  => 'The Platform that mods belong to. This group is used to categorise your mods by their platform.',
				'group_parent'       => array( $mod_type_group_id ),
				'group_parent_entry' => array_filter( array( $type_term_map['games'] ?? 0, $type_term_map['software'] ?? 0 ) ),
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
    private static function default_file_type(): void {
        $FileType = [
            '3DS'     => [
                'name' => '3D Studio Scene'
            ],
            '3G2'     => [
                'name' => '3GPP2 Multimedia File'
            ],
            '3GP'     => [
                'name' => '3GPP Multimedia File'
            ],
            '7Z'      => [
                'name' => '7-Zip Compressed File'
            ],
            'AAC'     => [
                'name' => 'Advanced Audio Coding'
            ],
            'ACCDB'   => [
                'name' => 'Access 2007 Database'
            ],
            'AE'      => [
                'name' => 'Adobe After Effects'
            ],
            'AEP'     => [
                'name' => 'After Effects Project'
            ],
            'AEPX'    => [
                'name' => 'After Effects XML Project'
            ],
            'AI'      => [
                'name' => 'Adobe Illustrator'
            ],
            'AIF'     => [
                'name' => 'Audio Interchange File Format'
            ],
            'AIFF'    => [
                'name' => 'Audio Interchange File Format'
            ],
            'ALAC'    => [
                'name' => 'Apple Lossless Audio Codec'
            ],
            'AMR'     => [
                'name' => 'Adaptive Multi-Rate Codec'
            ],
            'AN'      => [
                'name' => 'Adobe Animate'
            ],
            'APK'     => [
                'name' => 'Android Package'
            ],
            'APP'     => [
                'name' => 'Application Package'
            ],
            'AR'      => [
                'name' => 'Adobe Aero'
            ],
            'ARJ'     => [
                'name' => 'ARJ Compressed File'
            ],
            'ARW'     => [
                'name' => 'Sony Digital Camera Image'
            ],
            'AU'      => [
                'name' => 'Adobe Audition'
            ],
            'AVI'     => [
                'name' => 'Audio Video Interleave'
            ],
            'AZW'     => [
                'name' => 'Amazon Kindle eBook'
            ],
            'BAK'     => [
                'name' => 'Backup File'
            ],
            'BASH'    => [
                'name' => 'Bash Script File'
            ],
            'BAT'     => [
                'name' => 'Batch File'
            ],
            'BIN'     => [
                'name' => 'OS Disk Image'
            ],
            'BLEND'   => [
                'name' => 'Blender Project File'
            ],
            'BMP'     => [
                'name' => 'Blender Project File'
            ],
            'C'       => [
                'name' => 'C Source File'
            ],
            'C++'     => [
                'name' => 'C++ Source File'
            ],
            'C4D'     => [
                'name' => 'Cinema 4D Project'
            ],
            'CER'     => [
                'name' => 'Internet Security Certificate'
            ],
            'CFG'     => [
                'name' => 'Configuration File'
            ],
            'CGI'     => [
                'name' => 'Common Gateway Interface Script'
            ],
            'CH'      => [
                'name' => 'Adobe Character Animator'
            ],
            'CPP'     => [
                'name' => 'C++ Source Code'
            ],
            'CR2'     => [
                'name' => 'Canon Raw Image'
            ],
            'CRT'     => [
                'name' => 'Security Certificate'
            ],
            'CS'      => [
                'name' => 'C# Source File'
            ],
            'CSS'     => [
                'name' => 'Cascading Style Sheets'
            ],
            'CSV'     => [
                'name' => 'Comma-Separated Values'
            ],
            'CUE'     => [
                'name' => 'Disk Image Cue Sheet'
            ],
            'DAE'     => [
                'name' => 'Digital Asset Exchange'
            ],
            'DB'      => [
                'name' => 'Database File'
            ],
            'DEB'     => [
                'name' => 'Debian Software Package'
            ],
            'DJVU'    => [
                'name' => 'DjVu Document'
            ],
            'DLL'     => [
                'name' => 'Dynamic Link Library'
            ],
            'DMG'     => [
                'name' => 'Apple Disk Image'
            ],
            'DN'      => [
                'name' => 'Adobe Dimension Project'
            ],
            'DNG'     => [
                'name' => 'Digital Negative Image'
            ],
            'DOC'     => [
                'name' => 'Microsoft Word Document'
            ],
            'DOCX'    => [
                'name' => 'Microsoft Word Open XML Document'
            ],
            'DRV'     => [
                'name' => 'Device Driver'
            ],
            'DSD'     => [
                'name' => 'Super Audio CD'
            ],
            'DW'      => [
                'name' => 'Autodesk Drawing File'
            ],
            'EPS'     => [
                'name' => 'Encapsulated PostScript'
            ],
            'EPUB'    => [
                'name' => 'ePublication File'
            ],
            'EXE'     => [
                'name' => 'Executable File'
            ],
            'FBX'     => [
                'name' => 'Autodesk Filmbox Format'
            ],
            'FIG'     => [
                'name' => 'Figma'
            ],
            'FLAC'    => [
                'name' => 'Free Lossless Audio Codec'
            ],
            'FLV'     => [
                'name' => 'Flash Video File'
            ],
            'FNT'     => [
                'name' => 'Font File'
            ],
            'FON'     => [
                'name' => 'Font File'
            ],
            'FR'      => [
                'name' => 'Adobe FrameMaker File'
            ],
            'GEOJSON' => [
                'name' => 'Geographic JSON File'
            ],
            'GIF'     => [
                'name' => 'Graphics Interchange Format'
            ],
            'GLB'     => [
                'name' => 'GL Transmission Format (Binary)'
            ],
            'GLTF'    => [
                'name' => 'GL Transmission Format'
            ],
            'GPG'     => [
                'name' => 'GNU Privacy Guard File'
            ],
            'GPX'     => [
                'name' => 'GPS Exchange Format'
            ],
            'GZ'      => [
                'name' => 'Gzip Compressed File'
            ],
            'H5'      => [
                'name' => 'HDF5 File Format'
            ],
            'HDF5'    => [
                'name' => 'Hierarchical Data Format'
            ],
            'HTM'     => [
                'name' => 'HyperText Markup Language'
            ],
            'HTML'    => [
                'name' => 'HyperText Markup Language'
            ],
            'IC'      => [
                'name' => 'Adobe InCopy'
            ],
            'ICO'     => [
                'name' => 'Windows Icon File'
            ],
            'ID'      => [
                'name' => 'Adobe InDesign'
            ],
            'IMG'     => [
                'name' => 'Disk Image File'
            ],
            'INI'     => [
                'name' => 'Initialization File'
            ],
            'ISO'     => [
                'name' => 'Disc Image File'
            ],
            'JAVA'    => [
                'name' => 'Java Source File'
            ],
            'JPEG'    => [
                'name' => 'JPEG Image'
            ],
            'JPG'     => [
                'name' => 'JPEG Image'
            ],
            'JS'      => [
                'name' => 'JavaScript File'
            ],
            'JSON'    => [
                'name' => 'JavaScript Object Notation'
            ],
            'JSX'     => [
                'name' => 'JavaScript XML'
            ],
            'KEY'     => [
                'name' => 'Apple Keynote File'
            ],
            'KML'     => [
                'name' => 'Keyhole Markup Language'
            ],
            'KMZ'     => [
                'name' => 'Compressed KML File'
            ],
            'LOG'     => [
                'name' => 'Log File'
            ],
            'LOTTIE'  => [
                'name' => 'Lottie Animation File'
            ],
            'LR'      => [
                'name' => 'Adobe Lightroom'
            ],
            'LRC'     => [
                'name' => 'Lyrics File - Adobe Audition'
            ],
            'LXO'     => [
                'name' => 'Luxology Modo Scene'
            ],
            'M4A'     => [
                'name' => 'MPEG-4 Audio'
            ],
            'M4V'     => [
                'name' => 'MPEG-4 Video'
            ],
            'MA'      => [
                'name' => 'Autodesk Maya ASCII Scene'
            ],
            'MAT'     => [
                'name' => 'MATLAB Data File'
            ],
            'MAX'     => [
                'name' => '3ds Max Scene File'
            ],
            'MB'      => [
                'name' => 'Maya Binary Scene File'
            ],
            'MDB'     => [
                'name' => 'Microsoft Access Database'
            ],
            'ME'      => [
                'name' => 'Adobe Media Encoder'
            ],
            'MKV'     => [
                'name' => 'Matroska Video File'
            ],
            'MO'      => [
                'name' => 'Machine Object File'
            ],
            'MOBI'    => [
                'name' => 'Kindle eBook'
            ],
            'MOGRT'   => [
                'name' => 'Motion Graphics Template'
            ],
            'MOV'     => [
                'name' => 'Apple QuickTime Movie'
            ],
            'MP3'     => [
                'name' => 'MP3 Audio File'
            ],
            'MP4'     => [
                'name' => 'MPEG-4 Video File'
            ],
            'MPG'     => [
                'name' => 'MPEG Video File'
            ],
            'MSG'     => [
                'name' => 'Outlook Email Message'
            ],
            'MSI'     => [
                'name' => 'Microsoft Installer Package'
            ],
            'MTL'     => [
                'name' => 'Material Template Library'
            ],
            'NEF'     => [
                'name' => 'Nikon Electronic Format'
            ],
            'OBB'     => [
                'name' => 'Opaque Binary Blob'
            ],
            'OBJ'     => [
                'name' => 'Wavefront 3D Object'
            ],
            'ODP'     => [
                'name' => 'OpenDocument Presentation'
            ],
            'ODS'     => [
                'name' => 'OpenDocument Spreadsheet'
            ],
            'ODT'     => [
                'name' => 'OpenDocument Text'
            ],
            'OGG'     => [
                'name' => 'Ogg Vorbis Audio'
            ],
            'ONNX'    => [
                'name' => 'Open Neural Network Exchange'
            ],
            'OPUS'    => [
                'name' => 'Opus Audio Format'
            ],
            'ORF'     => [
                'name' => 'Olympus Raw File'
            ],
            'OST'     => [
                'name' => 'Outlook Offline Data'
            ],
            'OTF'     => [
                'name' => 'OpenType Font'
            ],
            'P'       => [
                'name' => 'PowerPoint Presentation'
            ],
            'PAT'     => [
                'name' => 'Pattern File'
            ],
            'PB'      => [
                'name' => 'TensorFlow Model File'
            ],
            'PCM'     => [
                'name' => 'Pulse-Code Modulation'
            ],
            'PDF'     => [
                'name' => 'Portable Document Format'
            ],
            'PEF'     => [
                'name' => 'Pentax Raw Image'
            ],
            'PEM'     => [
                'name' => 'Cryptographic Key File'
            ],
            'PFX'     => [
                'name' => 'Personal Information Exchange File'
            ],
            'PHP'     => [
                'name' => 'PHP Script'
            ],
            'PKG'     => [
                'name' => 'Mac OS X Installer'
            ],
            'PL'      => [
                'name' => 'Adobe Prelude'
            ],
            'PLY'     => [
                'name' => '3D Scan File (Polygon File Format)'
            ],
            'PNG'     => [
                'name' => 'Portable Network Graphics'
            ],
            'PPT'     => [
                'name' => 'PowerPoint Presentation'
            ],
            'PPTX'    => [
                'name' => 'PowerPoint Open XML Presentation'
            ],
            'PR'      => [
                'name' => 'Adobe Premiere Pro'
            ],
            'PRJ'     => [
                'name' => 'Project Dependency File'
            ],
            'PRPROJ'  => [
                'name' => 'Premiere Pro Project'
            ],
            'PS'      => [
                'name' => 'Adobe Photoshop'
            ],
            'PS1'     => [
                'name' => 'PowerShell Script'
            ],
            'PSC'     => [
                'name' => 'Adobe Presenter Content'
            ],
            'PSD'     => [
                'name' => 'Adobe Photoshop Document'
            ],
            'PSDT'    => [
                'name' => 'Photoshop Template'
            ],
            'PST'     => [
                'name' => 'Outlook Email Backup'
            ],
            'PSX'     => [
                'name' => 'Adobe Photoshop Extended'
            ],
            'PT'      => [
                'name' => 'Microsoft PowerPoint File'
            ],
            'PTH'     => [
                'name' => 'PyTorch Model File'
            ],
            'PTX'     => [
                'name' => 'Pro Tools Session File'
            ],
            'PY'      => [
                'name' => 'Python Script'
            ],
            'RAF'     => [
                'name' => 'Fujifilm RAW File'
            ],
            'RAR'     => [
                'name' => 'RAR Archive'
            ],
            'RAW'     => [
                'name' => 'Raw Image File'
            ],
            'RB'      => [
                'name' => 'Ruby Script'
            ],
            'RDATA'   => [
                'name' => 'R Language Data File'
            ],
            'RESUME'  => [
                'name' => 'Resume File'
            ],
            'RM'      => [
                'name' => 'RealMedia File'
            ],
            'RMVB'    => [
                'name' => 'RealMedia Variable Bitrate'
            ],
            'RPM'     => [
                'name' => 'Red Hat Linux Package'
            ],
            'RTF'     => [
                'name' => 'Rich Text Format'
            ],
            'RU'      => [
                'name' => 'Adobe Rush Project'
            ],
            'RVT'     => [
                'name' => 'Revit Project File'
            ],
            'SAFARIEXTZ' => [
                'name' => 'Safari Extension'
            ],
            'SAV'     => [
                'name' => 'SPSS Data File'
            ],
            'SCPT'    => [
                'name' => 'AppleScript Script File'
            ],
            'SDF'     => [
                'name' => 'Structured Data File'
            ],
            'SFX'     => [
                'name' => 'Self-Extracting Archive'
            ],
            'SH'      => [
                'name' => 'Shell Script'
            ],
            'SHP'     => [
                'name' => 'ESRI Shapefile'
            ],
            'SKETCH'  => [
                'name' => 'Sketch Design File'
            ],
            'SKP'     => [
                'name' => 'SketchUp File'
            ],
            'SLDPRT'  => [
                'name' => 'SolidWorks Part File'
            ],
            'SLDX'    => [
                'name' => 'PowerPoint Slide Show File'
            ],
            'SP'      => [
                'name' => 'Adobe Soundbooth Project'
            ],
            'SQL'     => [
                'name' => 'Structured Query Language File'
            ],
            'STEP'    => [
                'name' => 'STEP 3D File Format'
            ],
            'STL'     => [
                'name' => 'Stereolithography File'
            ],
            'SVG'     => [
                'name' => 'Scalable Vector Graphics'
            ],
            'SWF'     => [
                'name' => 'Shockwave Flash File'
            ],
            'SYS'     => [
                'name' => 'System File'
            ],
            'TAR'     => [
                'name' => 'Unix Archive File'
            ],
            'TEMP'    => [
                'name' => 'Temporary File'
            ],
            'TFLITE'  => [
                'name' => 'TensorFlow Lite Model'
            ],
            'TGA'     => [
                'name' => 'Targa Graphic File'
            ],
            'THMX'    => [
                'name' => 'Office Theme File'
            ],
            'TIF'     => [
                'name' => 'Tagged Image File Format'
            ],
            'TIFF'    => [
                'name' => 'Tagged Image File Format'
            ],
            'TMP'     => [
                'name' => 'Temporary File'
            ],
            'TORRENT' => [
                'name' => 'Torrent File'
            ],
            'TS'      => [
                'name' => 'TypeScript File'
            ],
            'TSX'     => [
                'name' => 'TypeScript XML File'
            ],
            'TTF'     => [
                'name' => 'TrueType Font'
            ],
            'TXT'     => [
                'name' => 'Text File'
            ],
            'USD'     => [
                'name' => 'Pixar Universal Scene Description'
            ],
            'USDA'    => [
                'name' => 'Pixar USD ASCII'
            ],
            'USDC'    => [
                'name' => 'Pixar USD ASCII'
            ],
            'USDZ'    => [
                'name' => 'Universal Scene Description Zip'
            ],
            'VHD'     => [
                'name' => 'Virtual Hard Disk (Microsoft)'
            ],
            'VHDX'    => [
                'name' => 'Virtual Hard Disk Extended'
            ],
            'VOB'     => [
                'name' => 'DVD Video File'
            ],
            'VTT'     => [
                'name' => 'Web Video Text Tracks'
            ],
            'W'       => [
                'name' => 'Microsoft Word File'
            ],
            'WAV'     => [
                'name' => 'Waveform Audio File'
            ],
            'WEBM'    => [
                'name' => 'Web Media File'
            ],
            'WEBP'    => [
                'name' => 'Web Image Format'
            ],
            'WMA'     => [
                'name' => 'Windows Media Audio'
            ],
            'WMV'     => [
                'name' => 'Windows Media Video'
            ],
            'WOFF'    => [
                'name' => 'Web Open Font Format'
            ],
            'X'       => [
                'name' => 'Microsoft Excel Chart'
            ],
            'XCF'     => [
                'name' => 'GIMP Image File'
            ],
            'XD'      => [
                'name' => 'Adobe XD'
            ],
            'XLS'     => [
                'name' => 'Excel Spreadsheet'
            ],
            'XLSX'    => [
                'name' => 'Excel Open XML Spreadsheet'
            ],
            'XML'     => [
                'name' => 'eXtensible Markup Language'
            ],
            'YML'     => [
                'name' => 'YAML Configuration File'
            ],
            'ZIP'     => [
                'name' => 'ZIP Archive'
            ]
        ];
        foreach ( $FileTypes as $type => $data ) {
            if ( ! term_exists( $type, 'modpress_file_type' ) ) {
                $result = wp_insert_term( $type, 'modpress_file_type' );
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