<?php
/**
 * Debug class for ModPress plugin.
 *
 * @package ModPress
 * @subpackage Admin\Manager\Tools
 * @since 1.0.0
 */
namespace ModPress\Admin\Manager\Tools;

use ModPress\Includes\Functions\Admin\FunctionsTools;
use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use ModPress\Includes\Functions\Helpers\LoaderHelper;
use ModPress\Includes\Functions\Helpers\PermissionHelper;
use ModPress\Includes\Functions\Helpers\RequestHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Debug extends ToolsManager {
	/**
	 * Constructor for the Debug class.
	 *
	 * @since 1.0.0
	 */
	public function __construct( ?LoaderHelper $loader = null ) {
		parent::__construct( false );
		( $loader ?? new LoaderHelper() )->register_component(
			$this,
			array(
				array(
					'type'     => 'action',
					'hook'     => 'admin_post_modpress_run_graph_diagnostics',
					'callback' => 'handle_graph_diagnostics',
				),
			)
		)->run();
	}
	/**
	 * Render the debug settings page content.
	 *
	 * @return void
	 */
	public function render_page_content(): void {
		$diagnostics = '1' === RequestHelper::get_text( 'graph_diagnostics' )
			? FunctionsTools::run_diagnostics()
			: null;
		?>
		<div class="card shadow-sm">
			<div class="card-body">
		<?php if ( is_array( $diagnostics ) ) : ?>
			<div class="notice <?php echo ! empty( $diagnostics['success'] ) ? 'notice-success' : 'notice-error'; ?> is-dismissible">
				<p><strong><?php echo esc_html( $diagnostics['message'] ?? __( 'Diagnostics completed.', 'modpress' ) ); ?></strong></p>
			</div>
			<div class="row g-4 mb-4">
				<?php
				foreach ( array(
					'curl'       => __( 'cURL diagnostic', 'modpress' ),
					'http_graph' => __( 'HTTP Graph diagnostic', 'modpress' ),
				) as $transport => $title ) :
					?>
								<?php $result = is_array( $diagnostics[ $transport ] ?? null ) ? $diagnostics[ $transport ] : array(); ?>
					<div class="col-12 col-xl-6">
						<div class="card shadow-sm h-100">
							<div class="card-body">
								<h2 class="h5"><?php echo esc_html( $title ); ?></h2>
								<p class="mb-3"><strong><?php echo esc_html( $result['message'] ?? __( 'No result available.', 'modpress' ) ); ?></strong></p>
								<?php if ( ! empty( $result['trace'] ) && is_array( $result['trace'] ) ) : ?>
									<pre class="bg-light border rounded p-3 mb-0" style="white-space: pre-wrap;"><?php echo esc_html( implode( "\n", array_map( 'strval', $result['trace'] ) ) ); ?></pre>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<?php if ( ! empty( $diagnostics['diagnostics'] ) && is_array( $diagnostics['diagnostics'] ) ) : ?>
				<div class="card shadow-sm mb-4">
					<div class="card-body">
						<h2 class="h5"><?php esc_html_e( 'Shared diagnostic context', 'modpress' ); ?></h2>
						<dl class="row mb-0">
							<?php foreach ( $diagnostics['diagnostics'] as $key => $value ) : ?>
								<dt class="col-sm-4 text-break"><?php echo esc_html( (string) $key ); ?></dt>
								<dd class="col-sm-8 text-break"><?php echo esc_html( is_array( $value ) ? wp_json_encode( $value ) : (string) $value ); ?></dd>
							<?php endforeach; ?>
						</dl>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>
		<div class="card shadow-sm mb-4">
			<div class="card-body">
				<h2 class="h5"><?php esc_html_e( 'Microsoft Graph diagnostics', 'modpress' ); ?></h2>
				<p class="text-secondary"><?php esc_html_e( 'Test the configured application credentials against Microsoft Entra over TLS 1.2. The test does not display credentials or tokens.', 'modpress' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="modpress_run_graph_diagnostics" />
					<?php wp_nonce_field( 'modpress_run_graph_diagnostics', 'modpress_graph_diagnostics_nonce' ); ?>
					<?php echo FormFieldHelper::input( 'modpress_run_graph_diagnostics', '1', array( 'type' => 'hidden' ) ); ?>
					<?php
					echo FormFieldHelper::button(
						__( 'Run Graph diagnostics', 'modpress' ),
						array(
							'type'  => 'submit',
							'class' => 'btn-primary',
						)
					);
					?>
				</form>
				<p class="small text-secondary mt-3 mb-0"><?php esc_html_e( 'A successful result confirms token acquisition, but does not test delegated OAuth mailbox access.', 'modpress' ); ?></p>
			</div>
		</div>
		<div class="card shadow-sm">
			<div class="card-body">
				<h2 class="h5"><?php esc_html_e( 'Debug logging', 'modpress' ); ?></h2>
				<p class="text-secondary"><?php esc_html_e( 'Configure diagnostic logging from the Tools settings tab.', 'modpress' ); ?></p>
				<a class="btn btn-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=modpress&group=tools&tab=general' ) ); ?>"><?php esc_html_e( 'Open Tools Settings', 'modpress' ); ?></a>
			</div>
		</div>
		</div>
		</div>
		<?php
	}

	/**
	 * Handle the Graph diagnostics request.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function handle_graph_diagnostics(): void {
		if ( ! PermissionHelper::can( 'modpress_tools_debug' ) ) {
			wp_die( esc_html__( 'You are not authorized to run ModPress diagnostics.', 'modpress' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'modpress_run_graph_diagnostics', 'modpress_graph_diagnostics_nonce' );

		wp_safe_redirect( admin_url( 'admin.php?page=modpress&group=tools&tab=debug&graph_diagnostics=1' ) );
		exit;
	}

	/**
	 * Render debug-related settings fields.
	 *
	 * Intentionally accepts an optional values array to stay compatible with the
	 * parent ToolsManager contract while the debug tool renders its own page.
	 *
	 * @param array<string, mixed> $values Current settings.
	 * @return void
	 */
	public function render( array $values = array() ): void {
		$field_id = 'modpress-debug-logging';
		$field    = array(
			'description'  => __( 'Write diagnostic information to the WordPress debug log.', 'modpress' ),
			'tooltip'      => __( 'Enable this only while investigating a problem, because logs can grow over time.', 'modpress' ),
			'tooltip_type' => 'info',
		);
		?>
		<tr>
			<th scope="row"><?php echo wp_kses_post( FormFieldHelper::label( $field_id, __( 'Debug logging', 'modpress' ), $field ) ); ?></th>
			<td><?php echo wp_kses_post(
				FormFieldHelper::checkbox(
					'modpress_tools[debug_logging]',
					'1',
					__( 'Enable ModPress debug logging', 'modpress' ),
					array(
						'id'      => $field_id,
						'checked' => ! empty( $values['debug_logging'] ),
					)
				)
			); ?></td>
		</tr>
		<?php
		$field_id = 'modpress-console-logging';
		$field    = array(
			'description' => __( 'Write diagnostic information to the browser console.', 'modpress' ),
			'tooltip'     => __( 'Use this during frontend troubleshooting and disable it afterward.', 'modpress' ),
		);
		?>
		<tr>
			<th scope="row"><?php echo wp_kses_post( FormFieldHelper::label( $field_id, __( 'Console logging', 'modpress' ), $field ) ); ?></th>
			<td><?php echo wp_kses_post(
				FormFieldHelper::checkbox(
					'modpress_tools[console_logging]',
					'1',
					__( 'Enable browser console logging', 'modpress' ),
					array(
						'id'      => $field_id,
						'checked' => ! empty( $values['console_logging'] ),
					)
				)
			); ?></td>
		</tr>
		<?php
	}
}


