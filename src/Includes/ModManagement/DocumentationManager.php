<?php
/**
 * Core documentation generator service.
 *
 * @package ModPress
 * @subpackage Includes\ModManagement
 * @since 1.0.0
 */
namespace ModPress\Includes\ModManagement;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DocumentationManager {
	/**
	 * Build the default documentation payload for a mod.
	 *
	 * @param int $mod_id Mod identifier.
	 * @return array<string, mixed>
	 */
	public function build_documentation( int $mod_id ): array {
		$mod = get_post( $mod_id );
		if ( ! $mod instanceof \WP_Post ) {
			return array(
				'status' => 'missing',
				'content' => array(),
			);
		}

		return array(
			'status' => 'ready',
			'mod_id' => $mod_id,
			'title'  => get_the_title( $mod ),
			'content' => array(
				array(
					'heading' => __( 'Overview', 'modpress' ),
					'body'    => wp_trim_words( wp_strip_all_tags( $mod->post_content ), 32 ),
				),
			),
		);
	}
}
