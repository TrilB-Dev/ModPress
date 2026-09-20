<?php
/**
 * Editor class for managing the editing and saving of ModPress pages.
 *
 * @package ModPress\Includes\Core
 */
namespace ModPress\Includes\Core;

use ModPress\Includes\Functions\Helpers\SanitizationHelper;
use ModPress\Includes\Functions\Helpers\FormFieldHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Editor {
	public static function save_page( int $page_id, ?\WP_Post $page = null ): bool {
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) || 'save_modpress_page' !== ( $_POST['modpress_action'] ?? '' ) || ! check_admin_referer( 'modpress_save_modpress_page', 'modpress_save_modpress_page_nonce' ) ) {
			return false;
		}
		$page = $page_id ? get_post( $page_id ) : null;
		if ( ! $page_id && ! current_user_can( 'modpress_page_create' ) ) {
			return false;
		}
		if ( $page_id && ( ! $page || PostType::MODPRESS !== $page->post_type || ! current_user_can( 'modpress_page_edit' ) || ( (int) $page->post_author !== get_current_user_id() && ! current_user_can( 'modpress_page_edit_others' ) ) || ( 'publish' === $page->post_status && ! current_user_can( 'modpress_page_edit_published' ) ) ) ) {
			return false;
		}

		$input = wp_unslash( $_POST['modpress_page'] ?? array() );
		$input = is_array( $input ) ? $input : array();
		$title = SanitizationHelper::text( $input['title'] ?? '' );
		if ( '' === $title ) {
			return false;
		}

		if ( ! current_user_can( 'modpress_page_publish' ) ) {
			return false;
		}

		$post_id = wp_insert_post(
			array(
				'ID'           => $page_id,
				'post_type'    => PostType::MODPRESS,
				'post_title'   => $title,
				'post_content' => wp_kses_post( (string) ( $input['content'] ?? '' ) ),
				'post_status'  => 'publish',
				'post_author'  => get_current_user_id(),
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		update_post_meta( $post_id, '_modpress_page_id', $page_id );
		return true;
	}

	public static function render_modpress_page_form( ?\WP_Post $page = null ): void {
		?>
		<form method="post" class="card shadow-sm">
			<?php wp_nonce_field( 'modpress_save_modpress_page', 'modpress_save_modpress_page_nonce' ); ?>
			<input type="hidden" name="modpress_action" value="save_modpress_page">
			<div class="card-body"><div class="mb-3"><label class="form-label" for="modpress-page-title"><?php esc_html_e( 'Page Title', 'modpress' ); ?></label><input class="form-control" id="modpress-page-title" name="modpress_page[title]" value="<?php echo esc_attr( $page ? $page->post_title : '' ); ?>" required></div><?php FormFieldHelper::tinymce( 'modpress-page-content', 'modpress_page[content]', __( 'Page Content', 'modpress' ), $page ? $page->post_content : '', 14, true ); ?></div>
			<div class="card-footer d-flex justify-content-end gap-2"><a class="btn btn-outline-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=modpress-manage' ) ); ?>"><?php esc_html_e( 'Cancel', 'modpress' ); ?></a><button class="btn btn-primary" type="submit"><?php echo esc_html( $page ? __( 'Save Page', 'modpress' ) : __( 'Create Page', 'modpress' ) ); ?></button></div>
		</form>
		<?php
	}
}






