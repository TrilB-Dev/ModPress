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

			$type = (string) ( $file['type'] ?? 'internal' );
			$download = (string) ( $file['download'] ?? $file['url'] ?? '' );
			if ( 'external' === $type ) {
				$host_url = (string) ( $file['host_url'] ?? '' );
				$path_suffix = (string) ( $file['url_suffix'] ?? '' );
				if ( '' !== $host_url ) {
					$download = rtrim( $host_url, '/' );
					if ( '' !== $path_suffix ) {
						$download .= '/' . ltrim( $path_suffix, '/' );
					}
				}
			}
			if ( '' === $download && isset( $file['host_id'] ) ) {
				$download = (string) ( $file['host_url'] ?? '' );
			}

			$prepared[] = array(
				'name'      => (string) ( $file['name'] ?? ( $file['file_name'] ?? __( 'Unknown file', 'modpress' ) ) ),
				'url'       => (string) ( $file['url'] ?? $download ),
				'version'   => (string) ( $file['version'] ?? '0.0.0' ),
				'download'  => $download,
				'updated'   => (string) ( $file['updated'] ?? '' ),
				'type'      => $type,
				'host_id'   => (int) ( $file['host_id'] ?? 0 ),
				'description' => (string) ( $file['description'] ?? '' ),
			);
		}

		return $prepared;
	}
}
