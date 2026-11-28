<?php
/**
 * WikiPress integration for ModPress admin.
 *
 * @package ModPress
 * @subpackage Includes\Integrations\WikiPress\Admin
 * @since 1.0.0
 */
namespace ModPress\Includes\Integrations\WikiPress\Admin;

use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\Functions\Helpers\PermissionHelper;
use ModPress\Includes\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Integrations {
	/**
	 * WikiPress integration settings.
	 * 
	 * @since 1.0.0
	 */
	public const MODE_SETTING = 'wikipress_mod_wiki_create_mode';
	/**
	 * WikiPress integration types.
	 *
	 * @since 1.0.0
	 */
	public const TYPES_SETTING = 'wikipress_mod_wiki_create_types';
	/**
	 * WikiPress integration action for saving settings.
	 *
	 * @since 1.0.0
	 */
	public const ACTION_SAVE = 'modpress_save_wikipress_mod_wiki_integration';
	/**
	 * Meta key for creating a wiki for a mod.
	 *
	 * @since 1.0.0
	 */
	public const META_CREATE_WIKI = '_modpress_create_wiki';
	/**
	 * Meta key for storing the wiki ID associated with a mod.
	 *
	 * @since 1.0.0
	 */
	public const META_WIKI_ID = '_modpress_wiki_id';
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}
	/**
	 * Initialize the integration by adding necessary hooks.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function init(): void {
		add_action( 'admin_post_' . self::ACTION_SAVE, array( $this, 'handle_save' ) );
		add_action( 'modpress_mod_form_integration_fields', array( $this, 'render_individual_mod_field' ), 10, 2 );
		add_action( 'modpress_mod_saved', array( $this, 'handle_mod_saved' ), 10, 4 );
	}
	/**
	 * Render the integration settings page content.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function render_page_content(): void {
		$mode = Settings::get_string( self::MODE_SETTING, 'disabled' );
		if ( ! in_array( $mode, array( 'disabled', 'all', 'by_type', 'individually' ), true ) ) {
			$mode = 'disabled';
		}
		$selected_types = array_values(array_map('strval', (array) Settings::get(self::TYPES_SETTING, array())));
		$type_options = array();
		if ( function_exists( 'get_terms' ) ) {
			$type_terms = get_terms(array(
				'taxonomy' => 'modpress_type',
				'hide_empty' => false,
			));
			if ( ! is_wp_error( $type_terms ) ) {
				foreach ( $type_terms as $term ) {
					$type_options[(string) (int) $term->term_id] = $term->name;
				}
			}
		}
		?>
		<div id="wikipress-mod-wiki-integration">
			<h2 class="h5"><?php esc_html_e( 'Mod Wiki Creation', 'wikipress' ); ?></h2>
			<p class="text-secondary"><?php esc_html_e( 'Choose when WikiPress creates a wiki for a saved Mod.', 'wikipress' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_SAVE ); ?>" />
				<?php wp_nonce_field( self::ACTION_SAVE, 'modpress_wikipress_integration_nonce' ); ?>
				<div class="mt-3">
					<?php echo FormFieldHelper::bootstrap_select(
						'wikipress_mod_wiki_mode',
						array(
							'data' => array(
								'disabled' => __( 'Disabled', 'wikipress' ),
								'all' => __( 'All', 'wikipress' ),
								'by_type' => __( 'By Type', 'wikipress' ),
								'individually' => __( 'Individually', 'wikipress' ),
							),
							'selected' => $mode,
							'id' => 'wikipress-mod-wiki-mode',
						)
					); ?>
				</div>
				<div id="wikipress-mod-wiki-types" class="mt-3<?php echo 'by_type' === $mode ? '' : ' d-none'; ?>">
					<?php echo FormFieldHelper::bootstrap_multiselect(
						'wikipress_mod_wiki_types',
						array(
							'data' => $type_options,
							'selected' => $selected_types,
							'id' => 'wikipress-mod-wiki-types-select',
							'live_search' => true,
							'show_selected_tags' => true,
						)
					); ?>
				</div>
				<div class="mt-4">
					<button type="submit" class="btn btn-primary"><?php esc_html_e( 'Save settings', 'wikipress' ); ?></button>
				</div>
			</form>
		</div>
		<?php
	}
	/**
	 * Handle saving the integration settings.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function handle_save(): void {
		if ( ! PermissionHelper::can( 'modpress_tools_integrations' ) ) {
			wp_die( esc_html__( 'You are not authorized to save WikiPress integration settings.', 'modpress' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION_SAVE, 'modpress_wikipress_integration_nonce' );

		$mode = isset( $_POST['wikipress_mod_wiki_mode'] ) ? sanitize_key( wp_unslash( $_POST['wikipress_mod_wiki_mode'] ) ) : 'disabled';
		if ( ! in_array( $mode, array( 'disabled', 'all', 'by_type', 'individually' ), true ) ) {
			$mode = 'disabled';
		}
		$type_ids = isset( $_POST['wikipress_mod_wiki_types'] ) ? array_values(array_filter(array_map('absint', (array) wp_unslash( $_POST['wikipress_mod_wiki_types'] )))) : array();
		Settings::set( self::MODE_SETTING, $mode );
		Settings::set( self::TYPES_SETTING, $type_ids );

		wp_safe_redirect( admin_url( 'admin.php?page=modpress&group=tools&tab=integrations&settings_saved=1' ) );
		exit;
	}
	/**
	 * Render the individual mod field for WikiPress integration.
	 *
	 * @param int $mod_id The ID of the mod.
	 * @return void
	 * @since 1.0.0
	 */
	public function render_individual_mod_field( int $mod_id = 0 ): void {
		$mode = Settings::get_string( self::MODE_SETTING, 'disabled' );
		if ( 'individually' !== $mode ) {
			return;
		}
		$create_wiki = $mod_id > 0
			? '1' === (string) get_post_meta( $mod_id, self::META_CREATE_WIKI, true )
			: false;
		?>
		<div class="col-md-3">
			<div class="card">
				<div class="card-body">
					<?php echo FormFieldHelper::switch(
						'modpress_create_wiki',
						true,
						__( 'Create Mod wiki', 'modpress' ),
						array(
							'id' => 'modpress-create-wiki',
							'checked' => $create_wiki,
						)
					); ?>
				</div>
			</div>
		</div>
		<?php
	}
	/**
	 * Handle the saving of a mod and create a WikiPress wiki if necessary.
	 *
	 * @param int $mod_post_id The post ID of the mod being saved.
	 * @param int $existing_mod_id The existing mod ID, if any.
	 * @param array $post_args The post arguments.
	 * @param array $request The request data.
	 * @return void
	 * @since 1.0.0
	 */
	public function handle_mod_saved( int $mod_post_id, int $existing_mod_id, array $post_args = array(), array $request = array() ): void {
		$mode = Settings::get_string( self::MODE_SETTING, 'disabled' );
		if ( 'disabled' === $mode || $existing_mod_id > 0 || post_type_exists( 'wikipress_wiki' ) === false ) {
			update_post_meta( $mod_post_id, self::META_CREATE_WIKI, ! empty( $request['modpress_create_wiki'] ) ? '1' : '0' );
			return;
		}

		$selected_types = array_values(array_map('strval', (array) Settings::get(self::TYPES_SETTING, array())));
		$create = 'all' === $mode;
		if ( 'by_type' === $mode ) {
			$mod_type_ids = wp_get_post_terms( $mod_post_id, 'modpress_type', array( 'fields' => 'ids' ) );
			$create = ! is_wp_error( $mod_type_ids ) && ! empty( array_intersect( array_map( 'strval', $mod_type_ids ), $selected_types ) );
		}
		if ( 'individually' === $mode ) {
			$create = ! empty( $request['modpress_create_wiki'] );
		}

		update_post_meta( $mod_post_id, self::META_CREATE_WIKI, $create ? '1' : '0' );
		if ( ! $create ) {
			return;
		}

		$existing_wiki = get_posts(array(
			'post_type' => 'wikipress_wiki',
			'post_status' => 'any',
			'posts_per_page' => 1,
			'meta_key' => self::META_WIKI_ID,
			'meta_value' => (string) $mod_post_id,
		));
		if ( ! empty( $existing_wiki ) ) {
			return;
		}

		$title = (string) ( $post_args['post_title'] ?? get_the_title( $mod_post_id ) );
		$wiki_id = wp_insert_post(array(
			'post_type' => 'wikipress_wiki',
			'post_title' => $title,
			'post_name' => sanitize_title( (string) ( $post_args['post_name'] ?? $title ) ),
			'post_excerpt' => (string) ( $post_args['post_excerpt'] ?? '' ),
			'post_content' => '',
			'post_status' => 'publish',
		), true);
		if ( ! is_wp_error( $wiki_id ) ) {
			update_post_meta( $mod_post_id, self::META_WIKI_ID, (int) $wiki_id );
			update_post_meta( $wiki_id, self::META_WIKI_ID, (int) $mod_post_id );
		}
	}
}