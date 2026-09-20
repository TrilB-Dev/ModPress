<?php
/**
 * Core mod file manager service.
 *
 * @package ModPress
 * @subpackage Includes\ModManagement
 * @since 1.0.0
 */
namespace ModPress\Includes\ModManagement;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FileManager {
	/**
	 * List downloadable file records for a mod.
	 *
	 * @param int $mod_id Mod identifier.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_files( int $mod_id ): array {
		$files = get_post_meta( $mod_id, '_modpress_files', true );
		if ( ! is_array( $files ) ) {
			return array();
		}

		$prepared = array();
		foreach ( $files as $file ) {
			if ( ! is_array( $file ) ) {
				continue;
			}

			$prepared[] = array(
				'name'      => (string) ( $file['name'] ?? __( 'Unknown file', 'modpress' ) ),
				'url'       => (string) ( $file['url'] ?? '' ),
				'version'   => (string) ( $file['version'] ?? '0.0.0' ),
				'download'  => (string) ( $file['download'] ?? '' ),
				'updated'   => (string) ( $file['updated'] ?? '' ),
			);
		}

		return $prepared;
	}
}
