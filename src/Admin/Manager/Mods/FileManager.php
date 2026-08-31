<?php

namespace ModPress\Admin\Manager\Mods;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FileManager {
    public static function render_mod_file( string $mod_name, string $file_name ): void {
        $mod_file = MODPRESS_PATH . "mods/{$mod_name}/{$file_name}";

        if ( file_exists( $mod_file ) ) {
            include $mod_file;
        } else {
            echo '<p>' . esc_html__( 'Mod file not found.', 'modpress' ) . '</p>';
        }
    }
}