<?php
/**
 * ModPress encryption helpers.
 *
 * @package ModPress
 */

namespace ModPress\Includes\Functions\Helpers;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EncryptionHelper {
	/**
	 * Add the ModPress encryption key to wp-config.php when it is not defined.
	 *
	 * @return bool True when the key already exists or was added successfully.
	 */
	public static function ensure_configured(): bool {
		if ( defined( 'MODPRESS_ENCRYPTION_KEY' ) ) {
			return self::has_runtime_key();
		}

		$config_path = self::config_path();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations -- wp-config.php is the bootstrap config file and must be written atomically.
		if ( null === $config_path || ! is_readable( $config_path ) || ! is_writable( $config_path ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- This is only used when the config file cannot be updated safely.
			error_log( 'ModPress: wp-config.php was not found or is not writable; encryption key was not created.' );
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents -- wp-config.php must be loaded as the active runtime config file.
		$config_content = file_get_contents( $config_path );
		if ( false === $config_content ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- This is only used when the config file cannot be updated safely.
			error_log( 'ModPress: could not read wp-config.php; encryption key was not created.' );
			return false;
		}

		if ( preg_match( '/define\s*\(\s*[\'\"]MODPRESS_ENCRYPTION_KEY[\'\"]\s*,/i', $config_content ) ) {
			return true;
		}

		try {
			$key = Key::createNewRandomKey()->saveToAsciiSafeString();
		} catch ( \Throwable $exception ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- This is only used when key generation fails at runtime.
			error_log( 'ModPress: failed to generate the encryption key: ' . $exception->getMessage() );
			return false;
		}

		$new_line        = "define( 'MODPRESS_ENCRYPTION_KEY', '" . addslashes( $key ) . "' );" . PHP_EOL;
		$marker          = "/* That's all, stop editing!";
		$marker_position = strpos( $config_content, $marker );

		if ( false !== $marker_position ) {
			$updated_content = substr_replace( $config_content, $new_line, $marker_position, 0 );
		} else {
			$settings_load     = "require_once ABSPATH . 'wp-settings.php';";
			$settings_position = strpos( $config_content, $settings_load );
			if ( false === $settings_position ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- This is only used when the config file cannot be updated safely.
				error_log( 'ModPress: could not find a safe insertion point in wp-config.php.' );
				return false;
			}
			$updated_content = substr_replace( $config_content, $new_line, $settings_position, 0 );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations -- wp-config.php is the active bootstrap configuration file.
		$handle = fopen( $config_path, 'c+' );
		if ( false === $handle || ! flock( $handle, LOCK_EX ) ) {
			if ( is_resource( $handle ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations -- wp-config.php is the active bootstrap configuration file.
				fclose( $handle );
			}
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- This is only used when the config file cannot be updated safely.
			error_log( 'ModPress: could not lock wp-config.php; encryption key was not created.' );
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations -- wp-config.php is the active bootstrap configuration file.
		$success = ftruncate( $handle, 0 ) && rewind( $handle ) && false !== fwrite( $handle, $updated_content );
		fflush( $handle );
		flock( $handle, LOCK_UN );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations -- wp-config.php is the active bootstrap configuration file.
		fclose( $handle );

		if ( ! $success ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- This is only used when the config file cannot be updated safely.
			error_log( 'ModPress: could not write the encryption key to wp-config.php.' );
			return false;
		}

		if ( ! defined( 'MODPRESS_ENCRYPTION_KEY' ) ) {
			define( 'MODPRESS_ENCRYPTION_KEY', $key );
		}

		return true;
	}

	/**
	 * Encrypt a value using the runtime encryption key.
	 *
	 * @param string $value The value to encrypt.
	 * @return string|null The encrypted value or null on failure.
	 */
	public static function encrypt( string $value ): ?string {
		if ( '' === $value ) {
			return '';
		}

		$key = self::load_key( self::runtime_key() );
		if ( null === $key ) {
			return null;
		}

		try {
			return Crypto::encrypt( $value, $key );
		} catch ( \Throwable $exception ) {
			return null;
		}
	}
	/**
	 * Decrypt a value using the runtime encryption key.
	 *
	 * @param string $value The value to decrypt.
	 * @return string|null The decrypted value or null on failure.
	 */
	public static function decrypt( string $value ): ?string {
		return self::decrypt_with_key( $value, self::runtime_key() );
	}
	/**
	 * Check if a runtime encryption key is available.
	 *
	 * @return bool True if a runtime key is available, false otherwise.
	 */
	public static function has_runtime_key(): bool {
		return null !== self::load_key( self::runtime_key() );
	}

	/**
	 * Find the active WordPress configuration file.
	 *
	 * @return string|null The configuration path or null when it cannot be found.
	 */
	private static function config_path(): ?string {
		$abspath = (string) constant( 'ABSPATH' );
		$paths   = array(
			$abspath . 'wp-config.php',
			dirname( $abspath ) . '/wp-config.php',
		);

		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		return null;
	}
	/**
	 * Decrypt a value using a specified key.
	 *
	 * @param string      $value The value to decrypt.
	 * @param string|null $key_value The key to use for decryption.
	 * @return string|null The decrypted value or null on failure.
	 */
	private static function decrypt_with_key( string $value, ?string $key_value ): ?string {
		if ( '' === $value ) {
			return '';
		}

		$key = self::load_key( $key_value );
		if ( null === $key ) {
			return null;
		}

		try {
			return Crypto::decrypt( $value, $key );
		} catch ( \Throwable $exception ) {
			return null;
		}
	}
	/**
	 * Get the runtime encryption key from the defined constant.
	 *
	 * @return string|null The runtime encryption key or null if not defined.
	 */
	private static function runtime_key(): ?string {
		if ( ! defined( 'MODPRESS_ENCRYPTION_KEY' ) ) {
			return null;
		}

		return (string) constant( 'MODPRESS_ENCRYPTION_KEY' );
	}
	/**
	 * Load a key from an ASCII-safe string.
	 *
	 * @param string|null $value The ASCII-safe string representation of the key.
	 * @return Key|null The loaded key or null on failure.
	 */
	private static function load_key( ?string $value ): ?Key {
		if ( null === $value || '' === $value || 'placeholder-key-for-intelephense' === $value ) {
			return null;
		}

		try {
			return Key::loadFromAsciiSafeString( $value );
		} catch ( \Throwable $exception ) {
			return null;
		}
	}
}







