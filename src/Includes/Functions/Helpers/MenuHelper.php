<?php
/**
 * Helper wrapper around ModPress core menu registration.
 *
 * @package ModPress\Includes\Functions\Helpers
 */
namespace ModPress\Includes\Functions\Helpers;

use ModPress\Includes\Core\Menus;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MenuHelper {
	/**
	 * Core menu registry instance.
	 *
	 * @var Menus
	 */
	private Menus $menus;

	public function __construct( ?Menus $menus = null ) {
		$this->menus = $menus ?? new Menus();
	}

	/**
	 * Register a menu location and return the helper instance.
	 *
	 * @param string $location Menu location slug.
	 * @param string $label Menu location label.
	 * @return self
	 */
	public static function register_location( string $location, string $label = '' ): self {
		$helper = new self();
		$helper->menus->register_location( $location, $label );
		return $helper;
	}

	/**
	 * Get all registered menu locations.
	 *
	 * @return array<string, string>
	 */
	public function get_locations(): array {
		return $this->menus->get_locations();
	}
}

