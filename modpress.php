<?php

/**
 * ModPress - A WordPress Plugin
 *
 * This is the main plugin file for the ModPress WordPress plugin. It contains the plugin metadata and initializes the plugin by including necessary files and setting up activation and deactivation hooks.
 *
 * Plugin Name:       ModPress
 * Plugin URI:        https://modpress.dev
 * Description:       Modpres is a Wordpress plugin designed to manage and display plugins, mods & extensions in a portfolio.
 * MrTrilB:            MrTrilB
 * MrTrilB URI:        https://trilb.dev
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       modpress
 * Domain Path:       src/languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'MODPRESS_VERSION', '0.0.5' );
define( 'MODPRESS_NAME', 'modpress' );
define( 'MODPRESS_FILE', __FILE__ );
define( 'MODPRESS_DIR', plugin_dir_path( __FILE__ ) );
define( 'MODPRESS_URL', plugin_dir_url( __FILE__ ) );
define( 'MODPRESS_BASENAME', plugin_basename( __FILE__ ) );
define( 'MODPRESS_ROOT', MODPRESS_DIR );
define( 'MODPRESS_ROOT_URL', MODPRESS_URL );
define( 'MODPRESS_API', MODPRESS_DIR . 'src/API' );
define( 'MODPRESS_ASSETS', MODPRESS_DIR . 'src/Assets' );
define( 'MODPRESS_ASSETS_URL', MODPRESS_URL . 'src/Assets' );
define( 'MODPRESS_ADMIN', MODPRESS_DIR . 'src/Admin' );
define( 'MODPRESS_ADMIN_URL', MODPRESS_URL . 'src/Admin' );
define( 'MODPRESS_LANGUAGES', MODPRESS_DIR . 'src/languages' );
define( 'MODPRESS_INCLUDES', MODPRESS_DIR . 'src/includes' );
define( 'MODPRESS_CORE', MODPRESS_INCLUDES . '/Core' );
define( 'MODPRESS_SETTINGS', MODPRESS_INCLUDES . '/Settings' );
define( 'MODPRESS_PLUGINS', MODPRESS_INCLUDES . '/Plugins' );
define( 'MODPRESS_PLUGINS_URL', MODPRESS_URL . 'src/includes/Plugins' );

$modpress_autoloader = MODPRESS_DIR . 'vendor/autoload.php';

if ( is_readable( $modpress_autoloader ) ) {

	require_once $modpress_autoloader;
	
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-modpress-activator.php
 */
function activate_modpress() {
	\ModPress\Includes\Core\WP\Activator::activate();
}

register_activation_hook( __FILE__, 'activate_modpress' );
/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-modpress-deactivator.php
 */
function deactivate_modpress() {
	\ModPress\Includes\Core\WP\Deactivator::deactivate();
}

register_deactivation_hook( __FILE__, 'deactivate_modpress' );
/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once MODPRESS_DIR . 'src/Plugin.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_modpress() {

	$plugin = new \ModPress\Plugin( MODPRESS_FILE, MODPRESS_NAME, MODPRESS_VERSION );
	$plugin->run();

}
run_modpress();
