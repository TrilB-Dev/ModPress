<?php

namespace ModPress\Admin\Manager\Mods;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ModForms {
    public static function render_new_mod_form( array $categories = [], array $tags = [], $fields = '' ): void {
        ?>
        <form method="post" class="modpress-mod-form">
            <input type="hidden" name="modpress_action" value="save_mod">
            <?php wp_nonce_field( 'modpress_save_mod', 'modpress_mod_nonce' ); ?>
            <p>
                <label for="modpress-mod-title"><?php esc_html_e( 'Mod title', 'modpress' ); ?></label>
                <input type="text" id="modpress-mod-title" name="post_title" class="regular-text" required>
            </p>
            <p>
                <label for="modpress-mod-content"><?php esc_html_e( 'Description', 'modpress' ); ?></label>
                <textarea id="modpress-mod-content" name="post_content" class="large-text" rows="8"></textarea>
            </p>
            <?php echo is_string( $fields ) ? wp_kses_post( $fields ) : ''; ?>
            <p><button type="submit" class="button button-primary"><?php esc_html_e( 'Create Mod', 'modpress' ); ?></button></p>
        </form>
        <?php
    }

    public static function render_modals( \WP_Post $mod ): void {
        printf(
            '<div class="modpress-mod-actions" data-mod-id="%1$d"><a class="button" href="%2$s">%3$s</a></div>',
            absint( $mod->ID ),
            esc_url( get_edit_post_link( $mod->ID ) ?: '#' ),
            esc_html__( 'Edit Mod', 'modpress' )
        );
    }

    public static function render_mod_form( string $mod_name, array $mod_data = [] ): void {
        $mod_file = MODPRESS_PATH . "mods/{$mod_name}/form.php";

        if ( file_exists( $mod_file ) ) {
            include $mod_file;
        } else {
            echo '<p>' . esc_html__( 'Mod form not found.', 'modpress' ) . '</p>';
        }
    }
}