<?php
/**
 * AJAX request and response helpers for ModPress and extensions.
 *
 * @package ModPress
 * @subpackage Includes\Functions\Helpers
 * @since 1.0.0
 */
namespace ModPress\Includes\Functions\Helpers;
use ModPress\Includes\Functions\Helpers\LoggerHelper;
use ModPress\Includes\Functions\Helpers\AlertHelper;
use ModPress\Includes\Functions\Helpers\LoaderHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AjaxHelper {
	private static bool $registered = false;
	private static bool $runtime_config_registered = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! self::$registered ) {
			self::register_and_enqueue_script();
			self::$registered = true;
		}

		if ( ! self::$runtime_config_registered ) {
			$this->register_runtime_config();
			self::$runtime_config_registered = true;
		}
	}

	/**
	 * Public entry point for preparing all ModPress REST and AJAX requests.
	 *
	 * Call this from anywhere that needs a request payload, route, nonce, or
	 * request-specific diagnostics. The helper owns URL building, payload
	 * normalization, and common headers so callers only pass the values they need.
	 *
	 * @param string $request_type HTTP verb.
	 * @param string $route        REST route relative to the modpress/v1 namespace.
	 * @param array  $data         Request payload.
	 * @param array  $options      Extra runtime options.
	 * @param array  $headers      Additional HTTP headers.
	 * @param array  $messages     Optional status/logging details.
	 * @return array<string,mixed>
	 */
	public function create( string $request_type = 'GET', string $route = '', array $data = array(), array $options = array(), array $headers = array(), array $messages = array() ): array {
		return self::prepare_request( $request_type, $route, $data, $options, $headers, $messages );
	}

	/**
	 * Prepare a ModPress REST/AJAX request payload.
	 *
	 * @param string $request_type HTTP verb.
	 * @param string $route        REST route relative to the modpress/v1 namespace.
	 * @param array  $data         Request payload.
	 * @param array  $options      Extra runtime options.
	 * @param array  $headers      Additional HTTP headers.
	 * @param array  $messages     Optional status/logging details.
	 * @return array<string,mixed>
	 */
	private static function prepare_request( string $request_type = 'GET', string $route = '', array $data = array(), array $options = array(), array $headers = array(), array $messages = array() ): array {
		$normalized_type = strtoupper( $request_type );
		if ( ! in_array( $normalized_type, array( 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) ) {
			throw new \InvalidArgumentException( 'Invalid AJAX request type.' );
		}

		$normalized_route = self::normalize_route( $route );
		$normalized_data  = self::normalize_data( $data );
		$nonce            = self::rest_nonce();
		$base_url         = self::rest_base_url();
		$normalized_headers = array(
			'X-WP-Nonce' => $nonce,
		);

		foreach ( $headers as $key => $value ) {
			$key = (string) $key;
			if ( is_scalar( $value ) ) {
				$normalized_headers[ $key ] = (string) $value;
				continue;
			}

			$normalized_headers[ $key ] = wp_json_encode( $value );
		}

		if ( 'GET' !== $normalized_type ) {
			$normalized_headers['Content-Type'] = 'application/json';
		}

		$normalized_messages = array_merge(
			array(
				'status'  => 'success',
				'message' => '',
				'alert'   => false,
				'console' => false,
				'log'     => false,
			),
			$messages
		);

		if ( ! empty( $normalized_messages['message'] ) ) {
			$message = (string) $normalized_messages['message'];
			if ( ! empty( $normalized_messages['log'] ) ) {
				LoggerHelper::write_log( $message );
			}
			if ( ! empty( $normalized_messages['console'] ) ) {
				LoggerHelper::write_console( $message );
			}
			if ( ! empty( $normalized_messages['alert'] ) && is_admin() ) {
				$alert_type = $normalized_messages['alert_type'] ?? ( 'error' === strtolower( (string) ( $normalized_messages['status'] ?? 'success' ) ) ? 'error' : 'success' );
				AlertHelper::render_admin_notice( $message, (string) $alert_type );
			}
		}

		$request = array(
			'request_type' => $normalized_type,
			'route'        => $normalized_route,
			'base_url'     => $base_url,
			'url'          => self::build_rest_url( $normalized_route ),
			'nonce'        => $nonce,
			'data'         => $normalized_data,
			'headers'      => $normalized_headers,
			'options'      => $options,
			'messages'     => $normalized_messages,
		);

		if ( 'GET' === $normalized_type ) {
			$request['data'] = wp_unslash( $normalized_data );
		}

		return $request;
	}

	/**
	 * Build the base WordPress REST URL for ModPress.
	 *
	 * @return string
	 */
	public static function rest_base_url(): string {
		if ( function_exists( 'rest_url' ) ) {
			return rtrim( rest_url( 'modpress/v1/' ), '/' ) . '/';
		}

		return rtrim( trailingslashit( site_url( '/wp-json/' ) ), '/' ) . '/';
	}

	/**
	 * Build the final REST URL from a ModPress route.
	 *
	 * @param string $route Relative route, such as groups/my-group or modpress/v1/groups/my-group.
	 * @return string
	 */
	public static function build_rest_url( string $route = '' ): string {
		$normalized_route = self::normalize_route( $route );
		$base_url         = self::rest_base_url();

		if ( '' === $normalized_route ) {
			return $base_url;
		}

		return $base_url . ltrim( $normalized_route, '/' );
	}

	/**
	 * Normalize a request route so callers can pass either a relative route or the full namespace form.
	 *
	 * @param string $route Route string.
	 * @return string
	 */
	public static function normalize_route( string $route = '' ): string {
		$route = trim( (string) $route );
		if ( '' === $route ) {
			return '';
		}

		$route = preg_replace( '#^/?modpress/v1/?#i', '', $route );
		$route = preg_replace( '#^/?wp-json/?#i', '', $route );
		$route = trim( (string) $route, '/' );

		return $route;
	}

	/**
	 * Return the request nonce to use for ModPress REST calls.
	 *
	 * @return string
	 */
	public static function rest_nonce(): string {
		return function_exists( 'wp_create_nonce' ) ? wp_create_nonce( 'wp_rest' ) : '';
	}

	/**
	 * Convert a data array into a sanitized payload for ModPress requests.
	 *
	 * @param array $data Request data.
	 * @return array<string,mixed>
	 */
	public static function normalize_data( array $data = array() ): array {
		$normalized = array();

		foreach ( $data as $key => $value ) {
			if ( is_scalar( $value ) ) {
				$normalized[ $key ] = sanitize_text_field( (string) $value );
				continue;
			}

			$normalized[ $key ] = $value;
		}

		return $normalized;
	}

	/**
	 * Factory for the shared script config that the admin scripts consume.
	 *
	 * @param array $extra Any extra config keys.
	 * @return array<string,string>
	 */
	public static function script_config( array $extra = array() ): array {
		$config = array(
			'restUrl'    => self::rest_base_url(),
			'restNonce'  => self::rest_nonce(),
			'nonce'      => self::rest_nonce(),
			'apiRoot'    => self::rest_base_url(),
			'requestUrl' => self::rest_base_url(),
		);

		return array_merge( $config, $extra );
	}

	/**
	 * Localize the runtime REST config for the active admin page once.
	 *
	 * @return void
	 */
	private function register_runtime_config(): void {
		if ( ! is_admin() ) {
			return;
		}

		$route = array(
			'page'  => RequestHelper::get_key( 'page', '' ),
			'group' => RequestHelper::get_key( 'group', '' ),
			'tab'   => RequestHelper::get_key( 'tab', '' ),
		);

		$settings_page = 'modpress' === $route['page'] && 'settings' === $route['group'];
		$plugin_tab    = in_array( $route['tab'], array( 'plugins', 'third-party' ), true );

		if ( $settings_page && $plugin_tab ) {
			$settings_config = array(
				'nonce'               => wp_create_nonce( 'wp_rest' ),
				'restNonce'           => wp_create_nonce( 'wp_rest' ),
				'pluginNonce'         => wp_create_nonce( 'wp_rest' ),
				'pluginSettingsNonce' => wp_create_nonce( 'wp_rest' ),
			);
			foreach ( array( 'modpress-admin-settings', 'modpress-admin-plugins' ) as $handle ) {
				if ( wp_script_is( $handle, 'enqueued' ) ) {
					LoaderHelper::localize_script( $handle, 'modpressSettingsTabs', $settings_config );
				}
			}
		}

		$mod_manager_handles = array( 'modpress-admin-mods', 'modpress-admin' );
		if ( 'modpress' === $route['page'] && in_array( $route['group'], array( 'manage-mod', 'mods' ), true ) ) {
			foreach ( $mod_manager_handles as $handle ) {
				if ( wp_script_is( $handle, 'enqueued' ) ) {
					LoaderHelper::localize_script(
						$handle,
						'modpressManager',
						self::script_config()
					);
				}
			}
		}
	}

	/**
	 * Checks if the current request is an AJAX request.
	 *
	 * @return bool True if it's an AJAX request, false otherwise.
	 */
	public static function is_ajax_request(): bool {

		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}

	/**
	 * Returns the current HTTP request method.
	 *
	 * @return string The upper-case request method.
	 */
	public static function request_method(): string {

		return strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' );
	}

	/**
	 * Checks whether the current request uses the expected HTTP method.
	 *
	 * @param string $method Expected HTTP method.
	 * @return bool True when the method matches.
	 */
	public static function is_method( string $method ): bool {

		return self::request_method() === strtoupper( $method );
	}

	/**
	 * Checks whether the request contains a valid WordPress nonce.
	 *
	 * @param string                   $action Nonce action.
	 * @param string                   $field  Request field containing the nonce.
	 * @param array<string,mixed>|null $request Request data, defaults to POST data.
	 * @return bool True when the nonce is valid.
	 */
	public static function has_valid_nonce( string $action, string $field = 'nonce', ?array $request = null ): bool {

		$request = null === $request ? $_POST : $request;
		$nonce   = $request[ $field ] ?? '';

		return is_scalar( $nonce ) && (bool) wp_verify_nonce( (string) wp_unslash( $nonce ), $action );
	}

	/**
	 * Checks whether the current user has a capability.
	 *
	 * @param string $capability Capability to check.
	 * @param int    $object_id Optional object ID for meta capabilities.
	 * @return bool True when the current user is authorized.
	 */
	public static function can( string $capability, int $object_id = 0 ): bool {

		return PermissionHelper::can( $capability, $object_id );
	}

	/**
	 * Checks a nonce and capability before an AJAX operation runs.
	 *
	 * @param string $action     Nonce action.
	 * @param string $capability Required capability, or empty to skip the check.
	 * @param int    $object_id  Optional object ID for meta capabilities.
	 * @param string $field      Request field containing the nonce.
	 * @return bool True when all requested checks pass.
	 */
	public static function authorized( string $action, string $capability = '', int $object_id = 0, string $field = 'nonce' ): bool {

		if ( ! self::has_valid_nonce( $action, $field ) ) {
			return false;
		}

		return '' === $capability || self::can( $capability, $object_id );
	}

	/**
	 * Sends a successful JSON response.
	 *
	 * @param mixed $data Response data.
	 * @param int   $status_code HTTP status code.
	 * @return void
	 */
	public static function success( $data = null, int $status_code = 200 ): void {

		wp_send_json_success( $data, $status_code );
	}

	/**
	 * Sends an error JSON response.
	 *
	 * @param mixed $data Response error data or message.
	 * @param int   $status_code HTTP status code.
	 * @return void
	 */
	public static function error( $data = null, int $status_code = 400 ): void {

		wp_send_json_error( $data, $status_code );
	}

	/**
	 * Sends a standard authorization error for a failed AJAX request.
	 *
	 * @param string $message Error message.
	 * @param int    $status_code HTTP status code.
	 * @return void
	 */
	public static function unauthorized( string $message = 'You are not authorized to perform this action.', int $status_code = 403 ): void {

		self::error( array( 'message' => $message ), $status_code );
	}

	/**
	 * Register and enqueue the shared ModPress AJAX script.
	 *
	 * @param string $handle Script handle.
	 * @param string $src Script source URL.
	 * @param array  $deps Script dependencies.
	 * @param bool   $in_footer Whether to load in footer.
	 * @return void
	 */
	public static function register_and_enqueue_script( string $handle = 'modpress-admin-ajax', string $src = '', array $deps = array( 'modpress-bootstrap' ), bool $in_footer = true ): void {
		if ( ! defined( 'MODPRESS_ASSETS_URL' ) ) {
			return;
		}

		if ( '' === $src ) {
			$src = MODPRESS_ASSETS_URL . '/dist/js/admin.ajax.js';
		}

		if ( ! wp_script_is( $handle, 'registered' ) ) {
			wp_register_script( $handle, $src, $deps, defined( 'MODPRESS_VERSION' ) ? MODPRESS_VERSION : false, $in_footer );
		}

		wp_enqueue_script( $handle );
	}
}





