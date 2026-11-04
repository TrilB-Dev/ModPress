<?php
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        $GLOBALS['modpress_test_actions'][$hook][] = ['callback' => $callback, 'priority' => $priority, 'accepted_args' => $accepted_args];
        return true;
    }
}
if (!function_exists('has_action')) {
    function has_action($hook, $callback = false) {
        if (!isset($GLOBALS['modpress_test_actions'][$hook])) {
            return false;
        }
        if (false === $callback) {
            return !empty($GLOBALS['modpress_test_actions'][$hook]);
        }
        foreach ($GLOBALS['modpress_test_actions'][$hook] as $entry) {
            if ($entry['callback'] === $callback) {
                return true;
            }
        }
        return false;
    }
}
if (!function_exists('current_user_can')) {
    function current_user_can($capability) { return true; }
}
if (!function_exists('register_rest_route')) {
    function register_rest_route($namespace, $route, $args = array()) {
        $GLOBALS['modpress_test_rest_routes'][] = ['namespace' => $namespace, 'route' => $route, 'args' => $args];
        return true;
    }
}
if (!function_exists('sanitize_key')) {
    function sanitize_key($key) { return strtolower(preg_replace('/[^a-z0-9_\-]+/', '', (string) $key)); }
}
if (!function_exists('__')) {
    function __($text, $domain = null) { return (string) $text; }
}
if (!class_exists('WP_REST_Server')) {
    class WP_REST_Server { public const READABLE = 'GET'; }
}
if (!function_exists('rest_ensure_response')) {
    function rest_ensure_response($data) { return $data; }
}
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
}
require 'vendor/autoload.php';
$reflection = new ReflectionClass('ModPress\\Admin\\Manager\\Mods\\ModManager');
$manager = $reflection->newInstanceWithoutConstructor();
$manager->register_rest_routes();

echo method_exists('ModPress\\Admin\\Manager\\Mods\\ModManager', 'register_rest_routes') ? 'REST_METHOD:yes' : 'REST_METHOD:no';
echo PHP_EOL;
echo has_action('rest_api_init', [$manager, 'register_rest_routes']) ? 'REST_ACTION:yes' : 'REST_ACTION:no';
echo PHP_EOL;
echo count($GLOBALS['modpress_test_rest_routes'] ?? []) . ' ROUTES';
echo PHP_EOL;
