<?php
/**
 * Core changelog management service.
 *
 * @package ModPress
 * @subpackage Includes\ModManagement
 * @since 1.0.0
 */
namespace ModPress\Includes\ModManagement;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ChangeLogManager {
	/**
	 * Get changelog entries for a single mod.
	 *
	 * @param int $mod_id Mod identifier.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_entries( int $mod_id ): array {
		$entries = get_post_meta( $mod_id, '_modpress_changelog', true );
		if ( ! is_array( $entries ) ) {
			return array();
		}

		$prepared = array();
		foreach ( $entries as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$prepared[] = array(
				'version'     => (string) ( $entry['version'] ?? '0.0.0' ),
				'date'        => (string) ( $entry['date'] ?? '' ),
				'notes'       => (string) ( $entry['notes'] ?? '' ),
				'category'    => (string) ( $entry['category'] ?? __( 'General', 'modpress' ) ),
			);
		}

		return $prepared;
	}
}
