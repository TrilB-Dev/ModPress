<?php

namespace ModPress\Admin\Manager\Mods;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GroupManager {
    public static function render_mod_group( string $mod_name, string $group_name ): void {
        $mod_group_file = MODPRESS_PATH . "mods/{$mod_name}/groups/{$group_name}.php";

        if ( file_exists( $mod_group_file ) ) {
            include $mod_group_file;
        } else {
            echo '<p>' . esc_html__( 'Mod group not found.', 'modpress' ) . '</p>';
        }
    }
}