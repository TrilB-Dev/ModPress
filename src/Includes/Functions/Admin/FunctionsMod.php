<?php
/**
 * Admin functions for native Mod management.
 *
 * @package ModPress
 */

namespace ModPress\Includes\Functions\Admin;

use ModPress\Includes\Core\PostType;
use ModPress\Includes\Functions\Helpers\AlertHelper;
use ModPress\Includes\Functions\Helpers\SanitizationHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FunctionsMod {

	/**
	 * Save a Mod submitted through the native admin form.
	 *
	 * @return string Rendered admin notice, or an empty string when the form was not submitted.
	 */
	public function save_mod(): string {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return '';
		}

		$action = isset( $_POST['modpress_action'] )
			? sanitize_key( wp_unslash( $_POST['modpress_action'] ) )
			: '';

		if ( 'save_mod' !== $action ) {
			return '';
		}

		if ( ! current_user_can( 'modpress_create' ) ) {
			return AlertHelper::get_admin_notice(
				__( 'You are not allowed to create Mods.', 'modpress' ),
				'error'
			);
		}

		check_admin_referer( 'modpress_save_mod', 'modpress_mod_nonce' );

		$title = isset( $_POST['post_title'] )
			? SanitizationHelper::text( wp_unslash( $_POST['post_title'] ) )
			: '';
		$content = isset( $_POST['post_content'] )
			? wp_kses_post( wp_unslash( $_POST['post_content'] ) )
			: '';

		if ( '' === $title ) {
			return AlertHelper::get_admin_notice(
				__( 'A Mod title is required.', 'modpress' ),
				'error'
			);
		}

		$post_id = wp_insert_post(
			[
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_author'  => get_current_user_id(),
				'post_type'    => PostType::MOD,
			],
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return AlertHelper::get_admin_notice(
				__( 'The Mod could not be saved.', 'modpress' ),
				'error'
			);
		}

		return AlertHelper::get_admin_notice(
			__( 'Mod saved successfully.', 'modpress' ),
			'success'
		);
	}
}