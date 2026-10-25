<?php
define('ABSPATH', __DIR__ . '/');
require __DIR__ . '/vendor/autoload.php';

$_POST = array(
    'custom_field_label' => array('Test field'),
    'custom_field_slug' => array('test_field'),
    'custom_field_type' => array('text'),
    'custom_field_show_in_table' => array('1'),
);

$m = new \ModPress\Includes\Functions\Admin\FunctionsMod();
$ref = new ReflectionMethod($m, 'build_group_payload');
$ref->setAccessible(true);
$payload = $ref->invoke($m, 'My Group', 'my_group');
var_export($payload['fields'][0]['show_in_table'] ?? null);
