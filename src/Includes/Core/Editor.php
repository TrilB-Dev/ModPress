<?php
/**
 * Editor registry for ModPress content editors.
 *
 * @package ModPress\Includes\Core
 */
namespace ModPress\Includes\Core;

use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\Functions\Helpers\SanitizationHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Editor {
	/**
	 * Registered editor definitions.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static array $registered = array();

	/**
	 * Register a dynamic editor definition.
	 *
	 * @param string $slug Editor slug.
	 * @param array<string, mixed> $config Editor configuration.
	 * @return bool
	 */
	public static function create( string $slug, array $config = array() ): bool {
		$slug = self::normalize_slug( $slug );
		if ( '' === $slug || isset( self::$registered[ $slug ] ) ) {
			return false;
		}

		self::$registered[ $slug ] = self::normalize_definition( $slug, $config );
		return true;
	}

	/**
	 * Create an editor from UI payload data.
	 *
	 * @param array<string, mixed> $data UI payload.
	 * @return bool
	 */
	public static function dynamically_create( array $data = array() ): bool {
		$slug = self::normalize_slug( (string) ( $data['slug'] ?? $data['editor'] ?? $data['key'] ?? '' ) );
		if ( '' === $slug ) {
			return false;
		}

		$post_type = $data['post_type'] ?? $data['type'] ?? null;
		$config = array(
			'label'       => $data['label'] ?? self::humanize_slug( $slug ),
			'description' => $data['description'] ?? '',
			'post_type'   => null === $post_type ? '' : self::normalize_post_type( $post_type ),
			'supports'    => self::normalize_supports( (array) ( $data['supports'] ?? array( 'title', 'content' ) ) ),
			'fields'      => isset( $data['fields'] ) ? (array) $data['fields'] : array(),
			'template'    => isset( $data['template'] ) ? (array) $data['template'] : array(),
			'allow_html'  => (bool) ( $data['allow_html'] ?? false ),
		);

		return self::create( $slug, $config );
	}

	/**
	 * Register multiple editors.
	 *
	 * @param array<string, array<string, mixed>> $editors Editor definitions.
	 * @return void
	 */
	public static function register( array $editors = array() ): void {
		foreach ( $editors as $slug => $config ) {
			self::create( (string) $slug, (array) $config );
		}
	}

	/**
	 * Get all registered editor definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		return apply_filters( 'modpress_editor_definitions', self::$registered );
	}

	/**
	 * Get editor slugs.
	 *
	 * @return array<int, string>
	 */
	public static function get_names(): array {
		return array_keys( self::definitions() );
	}

	/**
	 * Save a ModPress page submission.
	 *
	 * @param int $page_id Page ID.
	 * @param \WP_Post|null $page Current page object.
	 * @return bool
	 */
	public static function save_page( int $page_id, ?\WP_Post $page = null ): bool {
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) || 'save_modpress_page' !== ( $_POST['modpress_action'] ?? '' ) || ! check_admin_referer( 'modpress_save_modpress_page', 'modpress_save_modpress_page_nonce' ) ) {
			return false;
		}

		$page = $page_id ? get_post( $page_id ) : null;
		if ( ! $page_id && ! current_user_can( 'modpress_page_create' ) ) {
			return false;
		}
		if ( $page_id && ( ! $page || 'modpress_page' !== $page->post_type || ! current_user_can( 'modpress_page_edit' ) || ( (int) $page->post_author !== get_current_user_id() && ! current_user_can( 'modpress_page_edit_others' ) ) || ( 'publish' === $page->post_status && ! current_user_can( 'modpress_page_edit_published' ) ) ) ) {
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

		$post_type = self::normalize_post_type( $input['post_type'] ?? ( $page ? $page->post_type : '' ) );
		$post_id = wp_insert_post(
			array(
				'ID'           => $page_id,
				'post_type'    => $post_type,
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

	/**
	 * Render a ModPress page editor form.
	 *
	 * @param \WP_Post|null $page Page being edited.
	 * @return void
	 */
	public static function render_modpress_page_form( ?\WP_Post $page = null ): void {
		?>
		<form method="post" class="card shadow-sm">
			<?php wp_nonce_field( 'modpress_save_modpress_page', 'modpress_save_modpress_page_nonce' ); ?>
			<input type="hidden" name="modpress_action" value="save_modpress_page">
			<input type="hidden" name="modpress_page[post_type]" value="<?php echo esc_attr( $page ? $page->post_type : '' ); ?>">
			<div class="card-body">
				<div class="mb-3">
					<label class="form-label" for="modpress-page-title">
						<?php esc_html_e( 'Page Title', 'modpress' ); ?>
					</label>
					<input class="form-control" id="modpress-page-title" name="modpress_page[title]" value="<?php echo esc_attr( $page ? $page->post_title : '' ); ?>" required>
				</div>
				<?php FormFieldHelper::tinymce( 
					'modpress-page-content', 
					'modpress_page[content]', 
					__( 'Page Content', 'modpress' ), 
					$page ? $page->post_content : '', 
					14, 
					true 
				); ?>
			</div>
			<div class="card-footer d-flex justify-content-end gap-2">
				<a class="btn btn-outline-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=modpress-manage' ) ); ?>">
					<?php esc_html_e( 'Cancel', 'modpress' ); ?>
				</a>
				<button class="btn btn-primary" type="submit">
					<?php echo esc_html( $page ? __( 'Save Page', 'modpress' ) : __( 'Create Page', 'modpress' ) ); ?>
				</button>
			</div>
		</form>
		<?php
	}

	/**
	 * Normalize the editor slug.
	 *
	 * @param string $slug Raw slug.
	 * @return string
	 */
	private static function normalize_slug( string $slug ): string {
		$slug = trim( (string) $slug );
		if ( '' === $slug ) {
			return '';
		}

		$slug = preg_replace( '/[\s\/]+/u', '_', $slug );
		$slug = preg_replace( '/[^A-Za-z0-9_\-]+/', '', $slug );
		$slug = str_replace( '-', '_', $slug );
		$slug = preg_replace( '/_+/', '_', strtolower( $slug ) );
		$slug = trim( (string) $slug, '_' );

		return '' === (string) $slug ? '' : (string) $slug;
	}

	/**
	 * Convert a slug to a title-like label.
	 *
	 * @param string $slug Raw slug.
	 * @param bool $singular Whether to singularize the label.
	 * @return string
	 */
	private static function humanize_slug( string $slug, bool $singular = false ): string {
		$slug = trim( str_replace( array( '_', '-' ), ' ', (string) $slug ) );
		$label = ucwords( $slug );
		if ( $singular && 'ies' === substr( strtolower( $label ), -3 ) ) {
			$label = substr( $label, 0, -3 ) . 'y';
		}

		return $label;
	}

	/**
	 * Normalize supported editor features.
	 *
	 * @param array<int, string> $supports Raw supports list.
	 * @return array<int, string>
	 */
	private static function normalize_supports( array $supports ): array {
		$valid = array( 'title', 'content', 'excerpt', 'thumbnail', 'custom-fields', 'revisions' );
		$normalized = array();

		foreach ( $supports as $support ) {
			$support = SanitizationHelper::key( $support );
			if ( in_array( $support, $valid, true ) ) {
				$normalized[] = $support;
			}
		}

		if ( empty( $normalized ) ) {
			$normalized = array( 'title', 'content' );
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Normalize a stored editor definition.
	 *
	 * @param string $slug Editor slug.
	 * @param array<string, mixed> $config Raw config.
	 * @return array<string, mixed>
	 */
	private static function normalize_definition( string $slug, array $config ): array {
		$definition = array(
			'label'       => self::humanize_slug( $slug ),
			'description' => '',
			'post_type'   => '',
			'supports'    => array( 'title', 'content' ),
			'fields'      => array(),
			'template'    => array(),
			'allow_html'  => false,
		);

		$definition = array_replace_recursive( $definition, $config );
		if ( isset( $definition['post_type'] ) ) {
			$definition['post_type'] = self::normalize_post_type( $definition['post_type'] );
		}
		if ( isset( $definition['supports'] ) ) {
			$definition['supports'] = self::normalize_supports( (array) $definition['supports'] );
		}

		return $definition;
	}


	/**
	 * Normalize a post type value.
	 *
	 * @param mixed $value Post type value.
	 * @return string
	 */
	private static function normalize_post_type( $value ): string {
		if ( null === $value ) {
			return '';
		}

		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$post_type = SanitizationHelper::key( (string) $value );
		return '' !== $post_type ? $post_type : '';
	}
}






