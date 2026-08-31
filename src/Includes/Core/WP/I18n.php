<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://https://trilb.dev/MrTrilB
 * @since      1.0.0
 *
 * @package    ModPress
 * @subpackage ModPress/includes
 */
namespace ModPress\Includes\Core\WP;

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    ModPress
 * @subpackage ModPress/includes
 * @author     MrTrilB <mrtrilb@trilb.dev>
 */
final class I18n {
	/**
	 * Default Local Translation of ModPress
	 * 
	 */
	public const DEFAULT_LOCALE = 'en_GB';

	/** @var string */
	private string $domain;

	/** @var string */
	private string $languages_path;

	/** @var string */
	private string $default_locale;

	/**
	 * Configure a text domain and its language directory.
	 *
	 * @param string $domain Text domain.
	 * @param string|null $languages_path Relative languages directory.
	 * @param string|null $plugin_file Plugin file used to derive the default path.
	 * @param string $default_locale MrTrilBing and translation baseline locale.
	 */
	public function __construct( string $domain = 'modpress', ?string $languages_path = null, ?string $plugin_file = null, string $default_locale = self::DEFAULT_LOCALE ) {
		$this->domain = sanitize_key( $domain );
		$plugin_basename = defined( 'MODPRESS_BASENAME' ) ? MODPRESS_BASENAME : ( $plugin_file && function_exists( 'plugin_basename' ) ? plugin_basename( $plugin_file ) : null );
		$default_path = $plugin_basename ? dirname( $plugin_basename ) . '/src/languages' : 'src/languages';
		$this->languages_path = trim( $languages_path ?? $default_path, '/' );
		$this->default_locale = str_replace( '-', '_', $default_locale );
	}
	/**
	 * Get the text domain.
	 *
	 * @return string
	 */
	public function get_domain(): string {
		return $this->domain;
	}
	/**
	 * Get the languages path.
	 *
	 * @return string
	 */
	public function get_languages_path(): string {
		return $this->languages_path;
	}
	/**
	 * Get the default locale.
	 *
	 * @return string
	 */
	public function get_default_locale(): string {
		return $this->default_locale;
	}
	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain(): bool {

		return load_plugin_textdomain( $this->domain, false, $this->languages_path );

	}
}
