<?php

namespace ModPress\Admin\Manager\Mods;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ChangeLogManager {
    public static function render_mod_changelog( string $mod_name ): void {
        $mod_changelog_file = MODPRESS_PATH . "mods/{$mod_name}/changelog.php";

        if ( file_exists( $mod_changelog_file ) ) {
            include $mod_changelog_file;
        } else {
            echo '<p>' . esc_html__( 'Mod changelog not found.', 'modpress' ) . '</p>';
        }
    }
}