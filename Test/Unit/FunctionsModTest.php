<?php

declare( strict_types=1 );

namespace {
    if ( ! class_exists( 'WP_Error' ) ) {
        final class WP_Error {
            private string $code;

            public function __construct( string $code = '' ) {
                $this->code = $code;
            }

            public function get_error_code(): string {
                return $this->code;
            }
        }
    }

    if ( ! function_exists( 'is_wp_error' ) ) {
        function is_wp_error( $thing ): bool {
            return $thing instanceof \WP_Error;
        }
    }

    if ( ! function_exists( 'sanitize_key' ) ) {
        function sanitize_key( $key ): string {
            return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );
        }
    }

    if ( ! function_exists( 'absint' ) ) {
        function absint( $value ): int {
            $value = (int) $value;
            return $value < 0 ? abs( $value ) : $value;
        }
    }

    if ( ! function_exists( '__' ) ) {
        function __( $text, $domain = null ): string {
            return (string) $text;
        }
    }

    if ( ! function_exists( '_n' ) ) {
        function _n( $singular, $plural, $number, $domain = null ): string {
            return (string) ( 1 === (int) $number ? $singular : $plural );
        }
    }

    if ( ! function_exists( 'number_format_i18n' ) ) {
        function number_format_i18n( $number ) {
            return number_format( (float) $number, 0, '.', ',' );
        }
    }

    if ( ! function_exists( 'sanitize_text_field' ) ) {
        function sanitize_text_field( $text ): string {
            return trim( strip_tags( (string) $text ) );
        }
    }

    if ( ! function_exists( 'wp_unslash' ) ) {
        function wp_unslash( $value ) {
            return is_string( $value ) ? stripslashes( $value ) : $value;
        }
    }

    if ( ! function_exists( 'current_user_can' ) ) {
        function current_user_can( $capability ): bool {
            $capability = (string) $capability;
            if ( array_key_exists( $capability, $GLOBALS['modpress_test_capabilities'] ?? array() ) ) {
                return (bool) ( $GLOBALS['modpress_test_capabilities'][ $capability ] ?? false );
            }

            return $GLOBALS['modpress_test_can_create'] ?? false;
        }
    }

    if ( ! function_exists( 'get_userdata' ) ) {
        function get_userdata( $user_id ) {
            $user_id = (int) $user_id;
            return $GLOBALS['modpress_test_users'][ $user_id ] ?? null;
        }
    }

    if ( ! function_exists( 'add_action' ) ) {
        function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ): bool {
            $GLOBALS['modpress_test_actions'][ $hook ][] = [
                'callback' => $callback,
                'priority' => $priority,
                'accepted_args' => $accepted_args,
            ];
            return true;
        }
    }

    if ( ! function_exists( 'has_action' ) ) {
        function has_action( $hook, $callback = false ): bool {
            if ( ! isset( $GLOBALS['modpress_test_actions'][ $hook ] ) ) {
                return false;
            }

            if ( false === $callback ) {
                return ! empty( $GLOBALS['modpress_test_actions'][ $hook ] );
            }

            foreach ( $GLOBALS['modpress_test_actions'][ $hook ] as $entry ) {
                if ( $entry['callback'] === $callback ) {
                    return true;
                }
            }

            return false;
        }
    }

    if ( ! function_exists( 'check_admin_referer' ) ) {
        function check_admin_referer( $action, $query_arg = null ): bool {
            $GLOBALS['modpress_test_nonce'] = [
                'action' => $action,
                'query_arg' => $query_arg,
            ];
            return true;
        }
    }

    if ( ! function_exists( 'wp_kses_post' ) ) {
        function wp_kses_post( $content ): string {
            return strip_tags( (string) $content, '<p><strong><em><a>' );
        }
    }

    if ( ! function_exists( 'apply_filters' ) ) {
        function apply_filters( $hook, $value ) {
            return $value;
        }
    }

    if ( ! function_exists( 'wp_insert_post' ) ) {
        function wp_insert_post( $postarr, $wp_error = false ) {
            $GLOBALS['modpress_test_inserted_post'] = $postarr;
            return $GLOBALS['modpress_test_insert_result'] ?? 123;
        }
    }

    if ( ! function_exists( 'wp_get_post_terms' ) ) {
        function wp_get_post_terms( $post_id, $taxonomy, $args = array() ) {
            $terms = $GLOBALS['modpress_test_post_terms'][ (int) $post_id ][ (string) $taxonomy ] ?? array();
            if ( ! empty( $args ) && isset( $args['fields'] ) && 'ids' === $args['fields'] ) {
                return array_values( array_map( 'intval', $terms ) );
            }

            return array();
        }
    }

    if ( ! function_exists( 'wp_set_post_terms' ) ) {
        function wp_set_post_terms( $post_id, $terms, $taxonomy, $append = false ) {
            $GLOBALS['modpress_test_post_terms'][ (int) $post_id ][ (string) $taxonomy ] = array_map( 'intval', (array) $terms );
            return array_map( 'intval', (array) $terms );
        }
    }

    if ( ! function_exists( 'get_posts' ) ) {
        function get_posts( $args = array() ) {
            $post_type = isset( $args['post_type'] ) ? (string) $args['post_type'] : '';
            $posts = $GLOBALS['modpress_test_posts'][ $post_type ] ?? array();
            return array_values( $posts );
        }
    }

    if ( ! function_exists( 'get_post_meta' ) ) {
        function get_post_meta( $post_id, $key = '', $single = false ) {
            $meta = $GLOBALS['modpress_test_post_meta'][ (int) $post_id ] ?? array();
            if ( '' === $key ) {
                return $meta;
            }

            $values = $meta[ $key ] ?? array();
            if ( $single ) {
                return $values[0] ?? '';
            }

            return $values;
        }
    }

    if ( ! function_exists( 'update_post_meta' ) ) {
        function update_post_meta( $post_id, $meta_key, $meta_value, $prev_value = '' ) {
            $GLOBALS['modpress_test_post_meta'][ (int) $post_id ][ $meta_key ] = array( $meta_value );
            return true;
        }
    }

    if ( ! function_exists( 'get_current_user_id' ) ) {
        function get_current_user_id(): int {
            return 42;
        }
    }

    if ( ! function_exists( 'esc_attr' ) ) {
        function esc_attr( $text ): string {
            return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
        }
    }

    if ( ! function_exists( 'esc_html' ) ) {
        function esc_html( $text ): string {
            return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
        }
    }

    if ( ! function_exists( 'esc_textarea' ) ) {
        function esc_textarea( $text ): string {
            return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
        }
    }

    if ( ! function_exists( 'esc_html_e' ) ) {
        function esc_html_e( $text, $domain = null ): void {
            echo esc_html( $text );
        }
    }

    if ( ! function_exists( 'esc_attr_e' ) ) {
        function esc_attr_e( $text, $domain = null ): void {
            echo esc_attr( $text );
        }
    }

    if ( ! function_exists( 'taxonomy_exists' ) ) {
        function taxonomy_exists( $taxonomy ): bool {
            return isset( $GLOBALS['modpress_test_taxonomies'][ (string) $taxonomy ] );
        }
    }

    if ( ! function_exists( 'get_terms' ) ) {
        function get_terms( $args = array() ) {
            $taxonomy = isset( $args['taxonomy'] ) ? (string) $args['taxonomy'] : '';
            $terms = $GLOBALS['modpress_test_terms'][ $taxonomy ] ?? array();

            if ( empty( $terms ) ) {
                return array();
            }

            return array_values( $terms );
        }
    }

    if ( ! function_exists( 'get_taxonomy' ) ) {
        function get_taxonomy( $taxonomy ) {
            $definition = $GLOBALS['modpress_test_taxonomies'][ (string) $taxonomy ] ?? null;
            if ( ! $definition ) {
                return false;
            }

            $label = $definition['args']['label'] ?? (string) $taxonomy;
            $object_types = $definition['args']['object_type'] ?? ( $definition['post_type'] ?? array() );

            return (object) array(
                'name' => (string) $taxonomy,
                'labels' => (object) array( 'name' => $label ),
                'object_type' => (array) $object_types,
                'hierarchical' => ! empty( $definition['args']['hierarchical'] ),
            );
        }
    }

    if ( ! function_exists( 'wp_count_terms' ) ) {
        function wp_count_terms( $taxonomy, $args = array() ) {
            $terms = $GLOBALS['modpress_test_terms'][ (string) $taxonomy ] ?? array();
            return count( $terms );
        }
    }

    if ( ! function_exists( 'get_term' ) ) {
        function get_term( $term, $taxonomy = '', $output = OBJECT ) {
            $key = is_object( $term ) ? $term->term_id : (int) $term;
            return $GLOBALS['modpress_test_terms'][ $taxonomy ][ $key ] ?? null;
        }
    }

    if ( ! function_exists( 'term_exists' ) ) {
        function term_exists( $term, $taxonomy = '', $parent = 0 ) {
            foreach ( $GLOBALS['modpress_test_terms'][ $taxonomy ] ?? [] as $term_id => $term_data ) {
                if ( (string) $term_data->name === (string) $term || (string) $term_data->slug === (string) $term ) {
                    return array( 'term_id' => (int) $term_id );
                }
            }
            return 0;
        }
    }

    if ( ! function_exists( 'get_term_meta' ) ) {
        function get_term_meta( $term_id, $key = '', $single = false ) {
            $meta = $GLOBALS['modpress_test_term_meta'][ (int) $term_id ] ?? array();
            if ( '' === $key ) {
                return $meta;
            }

            $values = $meta[ $key ] ?? [];
            if ( $single ) {
                return $values[0] ?? '';
            }

            return $values;
        }
    }

    if ( ! function_exists( 'add_term_meta' ) ) {
        function add_term_meta( $term_id, $meta_key, $meta_value, $unique = false ) {
            $GLOBALS['modpress_test_term_meta'][ (int) $term_id ][ $meta_key ][] = $meta_value;
            return true;
        }
    }

    if ( ! function_exists( 'update_term_meta' ) ) {
        function update_term_meta( $term_id, $meta_key, $meta_value, $prev_value = '' ) {
            $GLOBALS['modpress_test_term_meta'][ (int) $term_id ][ $meta_key ] = array( $meta_value );
            return true;
        }
    }

    if ( ! function_exists( 'delete_term_meta' ) ) {
        function delete_term_meta( $term_id, $meta_key, $meta_value = '' ) {
            unset( $GLOBALS['modpress_test_term_meta'][ (int) $term_id ][ $meta_key ] );
            return true;
        }
    }

    if ( ! function_exists( 'sanitize_title' ) ) {
        function sanitize_title( $title ): string {
            return strtolower( preg_replace( '/[^a-z0-9]+/', '-', trim( (string) $title ) ) );
        }
    }

    if ( ! function_exists( 'sanitize_html_class' ) ) {
        function sanitize_html_class( $class ): string {
            return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $class );
        }
    }

    if ( ! function_exists( 'admin_url' ) ) {
        function admin_url( $path = '' ): string {
            return 'http://example.com/wp-admin/' . ltrim( (string) $path, '/' );
        }
    }

    if ( ! function_exists( 'build_query' ) ) {
        function build_query( array $query ): string {
            return http_build_query( $query );
        }
    }

    if ( ! function_exists( 'wp_safe_redirect' ) ) {
        function wp_safe_redirect( $location ) {
            $GLOBALS['modpress_test_redirect'] = (string) $location;
            return true;
        }
    }

    if ( ! function_exists( 'set_post_thumbnail' ) ) {
        function set_post_thumbnail( $post_id, $thumbnail_id ) {
            $GLOBALS['modpress_test_thumbnail'][ (int) $post_id ] = (int) $thumbnail_id;
            return true;
        }
    }

    if ( ! function_exists( 'set_transient' ) ) {
        function set_transient( $key, $value, $expiration = 0 ) {
            $GLOBALS['modpress_test_transients'][ (string) $key ] = $value;
            return true;
        }
    }

    if ( ! function_exists( 'get_transient' ) ) {
        function get_transient( $key ) {
            return $GLOBALS['modpress_test_transients'][ (string) $key ] ?? false;
        }
    }

    if ( ! function_exists( 'delete_transient' ) ) {
        function delete_transient( $key ) {
            unset( $GLOBALS['modpress_test_transients'][ (string) $key ] );
            return true;
        }
    }

    if ( ! function_exists( 'wp_insert_term' ) ) {
        function wp_insert_term( $term, $taxonomy, $args = array() ) {
            $term_id = (int) ( $GLOBALS['modpress_test_next_term_id'] ?? 1000 );
            $GLOBALS['modpress_test_next_term_id'] = $term_id + 1;
            $taxonomy = (string) $taxonomy;
            $GLOBALS['modpress_test_terms'][ $taxonomy ][ $term_id ] = (object) array(
                'term_id' => $term_id,
                'name' => (string) $term,
                'slug' => (string) ( $args['slug'] ?? sanitize_title( $term ) ),
                'taxonomy' => $taxonomy,
            );
            return array( 'term_id' => $term_id, 'term_taxonomy_id' => $term_id );
        }
    }

    if ( ! function_exists( 'wp_update_term' ) ) {
        function wp_update_term( $term_id, $taxonomy, $args = array() ) {
            $term_id = (int) $term_id;
            $taxonomy = (string) $taxonomy;
            if ( isset( $GLOBALS['modpress_test_terms'][ $taxonomy ][ $term_id ] ) ) {
                $GLOBALS['modpress_test_terms'][ $taxonomy ][ $term_id ]->name = (string) ( $args['name'] ?? $GLOBALS['modpress_test_terms'][ $taxonomy ][ $term_id ]->name );
                $GLOBALS['modpress_test_terms'][ $taxonomy ][ $term_id ]->slug = (string) ( $args['slug'] ?? $GLOBALS['modpress_test_terms'][ $taxonomy ][ $term_id ]->slug );
            }
            return array( 'term_id' => $term_id, 'term_taxonomy_id' => $term_id );
        }
    }

    if ( ! function_exists( 'wp_delete_term' ) ) {
        function wp_delete_term( $term_id, $taxonomy ) {
            unset( $GLOBALS['modpress_test_terms'][ (string) $taxonomy ][ (int) $term_id ] );
            return true;
        }
    }

    if ( ! function_exists( 'esc_url' ) ) {
        function esc_url( $url ) {
            return (string) $url;
        }
    }

    if ( ! function_exists( 'wp_nonce_field' ) ) {
        function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $echo = true ) {
            $output = '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $action ) . '" />';
            if ( $echo ) {
                echo $output;
            }
            return $output;
        }
    }

    if ( ! function_exists( 'check_ajax_referer' ) ) {
        function check_ajax_referer( $action = -1, $query_arg = false, $die = true ) {
            return true;
        }
    }

    if ( ! function_exists( 'wp_send_json_success' ) ) {
        function wp_send_json_success( $data = null, $status_code = 200 ) {
            $GLOBALS['modpress_last_json_success'] = $data;
            return $data;
        }
    }
}

namespace ModPress\Admin\Manager\Mods {
    if ( ! function_exists( __NAMESPACE__ . '\\absint' ) ) {
        function absint( $value ): int {
            $value = (int) $value;
            return $value < 0 ? abs( $value ) : $value;
        }
    }

    if ( ! function_exists( __NAMESPACE__ . '\\wp_json_encode' ) ) {
        function wp_json_encode( $data ) {
            return json_encode( $data );
        }
    }
}

namespace ModPress\Includes\Functions\Admin {
    if ( ! function_exists( __NAMESPACE__ . '\\current_time' ) ) {
        function current_time( $type = 'mysql' ) {
            return '2024-01-01 00:00:00';
        }
    }

    if ( ! function_exists( __NAMESPACE__ . '\\wp_json_encode' ) ) {
        function wp_json_encode( $data ) {
            return json_encode( $data );
        }
    }

    if ( ! function_exists( __NAMESPACE__ . '\\esc_html__' ) ) {
        function esc_html__( $text, $domain = null ) {
            return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
        }
    }

    if ( ! function_exists( __NAMESPACE__ . '\\esc_attr__' ) ) {
        function esc_attr__( $text, $domain = null ) {
            return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
        }
    }

    if ( ! function_exists( __NAMESPACE__ . '\\get_current_user_id' ) ) {
        function get_current_user_id(): int {
            return 42;
        }
    }

    if ( ! function_exists( __NAMESPACE__ . '\\sanitize_key' ) ) {
        function sanitize_key( $key ) {
            return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );
        }
    }

    if ( ! function_exists( __NAMESPACE__ . '\\absint' ) ) {
        function absint( $value ): int {
            $value = (int) $value;
            return $value < 0 ? abs( $value ) : $value;
        }
    }
}

namespace ModPress\Tests\Unit {
    use ModPress\Includes\Core\Capabilities;
    use ModPress\Includes\Core\Schema;
    use ModPress\Includes\Core\WP\Database;
    use ModPress\Includes\Functions\Admin\FunctionsMod;
    use ModPress\Includes\Functions\Helpers\DBHelper;
    use ModPress\Includes\Functions\Helpers\FAHelper;
    use ModPress\Includes\Functions\Helpers\FormFieldHelper;
    use ModPress\Includes\ModManagement\GroupsManager;
    use ModPress\Includes\Settings\SettingsManager;
    use PHPUnit\Framework\TestCase;

    final class FunctionsModTest extends TestCase {
        protected function setUp(): void {
            parent::setUp();
            if ( ! defined( 'MODPRESS_ASSETS_URL' ) ) {
                define( 'MODPRESS_ASSETS_URL', 'http://example.com/wp-content/plugins/modpress/assets' );
            }
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = [];
            $GLOBALS['modpress_test_can_create'] = true;
            $GLOBALS['modpress_test_capabilities'] = array();
            $GLOBALS['modpress_test_users'] = array(
                42 => (object) array(
                    'ID' => 42,
                    'display_name' => 'Current User',
                ),
            );
            $GLOBALS['modpress_test_insert_result'] = 123;
            $GLOBALS['modpress_test_inserted_post'] = null;
            $GLOBALS['modpress_test_nonce'] = null;
        }

        protected function tearDown(): void {
            unset( $_SERVER['REQUEST_METHOD'] );
            $_POST = [];
            parent::tearDown();
        }

        public function testNonPostRequestsAreIgnored(): void {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_POST['modpress_action'] = 'save_mod';

            $this->assertSame( '', ( new FunctionsMod() )->save_mod() );
            $this->assertNull( $GLOBALS['modpress_test_inserted_post'] );
        }

        public function testOtherActionsAreIgnored(): void {
            $_POST['modpress_action'] = 'other_action';

            $this->assertSame( '', ( new FunctionsMod() )->save_mod() );
            $this->assertNull( $GLOBALS['modpress_test_inserted_post'] );
        }

        public function testCreateCapabilityIsRequired(): void {
            $GLOBALS['modpress_test_can_create'] = false;
            $_POST['modpress_action'] = 'save_mod';

            $notice = ( new FunctionsMod() )->save_mod();

            $this->assertStringContainsString( 'You are not allowed to create Mods.', $notice );
            $this->assertNull( $GLOBALS['modpress_test_nonce'] );
        }

        public function testEmptyTitleIsRejectedAfterNonceCheck(): void {
            $_POST = [
                'modpress_action' => 'save_mod',
                'modpress_mod_nonce' => 'valid',
                'post_title' => '   ',
            ];

            $notice = ( new FunctionsMod() )->save_mod();

            $this->assertStringContainsString( 'A Mod title is required.', $notice );
            $this->assertSame(
                [
                    'action' => 'modpress_save_mod',
                    'query_arg' => 'modpress_mod_nonce',
                ],
                $GLOBALS['modpress_test_nonce']
            );
            $this->assertNull( $GLOBALS['modpress_test_inserted_post'] );
        }

        public function testModFormRendersCorePostFieldNames(): void {
            $manager = new \ModPress\Admin\Manager\Mods\Mods();
            $method = new \ReflectionMethod( $manager, 'render_mod_form' );
            $method->setAccessible( true );

            ob_start();
            $method->invoke(
                $manager,
                array(
                    'post_title' => 'Example Mod',
                    'post_name' => 'example-mod',
                    'post_excerpt' => 'Short description',
                    'post_content' => '<p>Long description</p>',
                )
            );
            $html = ob_get_clean();

            $this->assertStringContainsString( 'name="post_title"', $html );
            $this->assertStringContainsString( 'name="post_name"', $html );
            $this->assertStringContainsString( 'name="post_author"', $html );
            $this->assertStringContainsString( 'name="post_excerpt"', $html );
            $this->assertStringContainsString( 'name="post_content"', $html );
            $this->assertStringContainsString( 'name="featured_image"', $html );
            $this->assertStringContainsString( 'name="modpress_action"', $html );
        }

        public function testModFormShowsCurrentUserNameAndHiddenIdWhenUserCannotEditOthers(): void {
            $GLOBALS['modpress_test_capabilities'] = array(
                'modpress_manager_mods_edit_others' => false,
            );
            $GLOBALS['modpress_test_users'][42] = (object) array(
                'ID' => 42,
                'display_name' => 'Current User',
            );

            $manager = new \ModPress\Admin\Manager\Mods\Mods();
            $method = new \ReflectionMethod( $manager, 'render_mod_form' );
            $method->setAccessible( true );

            ob_start();
            $method->invoke( $manager, array( 'post_author' => 42 ) );
            $html = ob_get_clean();

            $this->assertStringContainsString( 'name="post_author_display"', $html );
            $this->assertStringContainsString( 'value="Current User"', $html );
            $this->assertStringContainsString( 'name="post_author"', $html );
            $this->assertStringContainsString( 'type="hidden"', $html );
            $this->assertStringContainsString( 'value="42"', $html );
            $this->assertStringNotContainsString( 'id="modpress-mod-author" class="selectpicker"', $html );
        }

        public function testModsScreenOpensTheNewModFormWhenFormStateIsNew(): void {
            $manager = new \ModPress\Admin\Manager\Mods\Mods();

            ob_start();
            $manager->render_page_content( 'new' );
            $html = ob_get_clean();

            $this->assertStringContainsString( 'New Mod', $html );
            $this->assertStringContainsString( 'name="post_title"', $html );
            $this->assertStringNotContainsString( 'Available Mods', $html );
        }

        public function testModFormIncludesDynamicGroupTaxonomySelects(): void {
            $GLOBALS['modpress_test_db_groups'] = array(
                array(
                    'id' => 11,
                    'group_key' => 'modpress_type',
                    'group_slug' => 'type',
                    'group_name' => 'Mod Type',
                ),
                array(
                    'id' => 12,
                    'group_key' => 'modpress_games',
                    'group_slug' => 'games',
                    'group_name' => 'Games',
                ),
                array(
                    'id' => 13,
                    'group_key' => 'modpress_software',
                    'group_slug' => 'software',
                    'group_name' => 'Software',
                ),
            );
            $GLOBALS['modpress_test_terms'] = array(
                'modpress_type' => array(
                    1 => (object) array( 'term_id' => 1, 'name' => 'Core', 'slug' => 'core', 'taxonomy' => 'modpress_type' ),
                    2 => (object) array( 'term_id' => 2, 'name' => 'Addon', 'slug' => 'addon', 'taxonomy' => 'modpress_type' ),
                ),
                'modpress_games' => array(
                    10 => (object) array( 'term_id' => 10, 'name' => 'Skyrim', 'slug' => 'skyrim', 'taxonomy' => 'modpress_games' ),
                    11 => (object) array( 'term_id' => 11, 'name' => 'Fallout', 'slug' => 'fallout', 'taxonomy' => 'modpress_games' ),
                ),
                'modpress_software' => array(
                    20 => (object) array( 'term_id' => 20, 'name' => 'Gamebryo', 'slug' => 'gamebryo', 'taxonomy' => 'modpress_software' ),
                ),
            );

            $manager = new \ModPress\Admin\Manager\Mods\Mods();
            $method = new \ReflectionMethod( $manager, 'render_mod_form' );
            $method->setAccessible( true );

            ob_start();
            $method->invoke( $manager, array() );
            $html = ob_get_clean();

            $this->assertStringContainsString( 'name="mod_type"', $html );
            $this->assertStringContainsString( 'value="1"', $html );
            $this->assertStringContainsString( '>Core</option>', $html );
            $this->assertStringContainsString( 'value="2"', $html );
            $this->assertStringContainsString( '>Addon</option>', $html );
            $this->assertStringNotContainsString( 'Core Mod', $html );
            $this->assertStringNotContainsString( 'Resource Pack', $html );
            $this->assertStringContainsString( 'name="modpress_games"', $html );
            $this->assertStringContainsString( 'Skyrim', $html );
            $this->assertStringContainsString( 'name="modpress_software"', $html );
            $this->assertStringNotContainsString( 'name="modpress_type"', $html );
        }

        public function testModFormIncludesModStatusTermsFromTaxonomy(): void {
            $GLOBALS['modpress_test_terms'] = array(
                'modpress_mod_status' => array(
                    1 => (object) array( 'term_id' => 1, 'name' => 'Planned', 'slug' => 'planned', 'taxonomy' => 'modpress_mod_status' ),
                    2 => (object) array( 'term_id' => 2, 'name' => 'Released', 'slug' => 'released', 'taxonomy' => 'modpress_mod_status' ),
                ),
            );

            $manager = new \ModPress\Admin\Manager\Mods\Mods();
            $method = new \ReflectionMethod( $manager, 'render_mod_form' );
            $method->setAccessible( true );

            ob_start();
            $method->invoke( $manager, array() );
            $html = ob_get_clean();

            $this->assertStringContainsString( 'name="mod_status"', $html );
            $this->assertStringContainsString( 'value="1"', $html );
            $this->assertStringContainsString( '>Planned</option>', $html );
            $this->assertStringContainsString( 'value="2"', $html );
            $this->assertStringContainsString( '>Released</option>', $html );
            $this->assertStringNotContainsString( 'value="draft">Draft', $html );
            $this->assertStringNotContainsString( 'value="alpha">Alpha', $html );
        }

        public function testSaveModPersistsDynamicGroupTerms(): void {
            $GLOBALS['modpress_test_db_groups'] = array(
                array(
                    'id' => 12,
                    'group_key' => 'modpress_games',
                    'group_slug' => 'games',
                    'group_name' => 'Games',
                ),
                array(
                    'id' => 13,
                    'group_key' => 'modpress_software',
                    'group_slug' => 'software',
                    'group_name' => 'Software',
                ),
            );
            $GLOBALS['modpress_test_post_terms'] = array();
            $_POST = array(
                'modpress_action' => 'modpress_save_mod',
                'modpress_mod_nonce' => 'valid',
                'post_title' => 'Example Mod',
                'post_name' => 'example-mod',
                'post_author' => '42',
                'post_status' => 'draft',
                'post_excerpt' => 'Short excerpt',
                'post_content' => '<p>Long content</p>',
                'mod_type' => '2',
                'modpress_games' => '10',
                'modpress_software' => '20',
            );

            $notice = ( new FunctionsMod() )->save_mod();

            $this->assertStringContainsString( 'Mod saved successfully.', $notice );
            $this->assertSame( array( 2 ), $GLOBALS['modpress_test_post_terms'][123]['modpress_type'] ?? array() );
            $this->assertSame( array( 10 ), $GLOBALS['modpress_test_post_terms'][123]['modpress_games'] ?? array() );
            $this->assertSame( array( 20 ), $GLOBALS['modpress_test_post_terms'][123]['modpress_software'] ?? array() );
        }

        public function testSaveModPersistsCorePostFieldsAndThumbnail(): void {
            $_POST = [
                'modpress_action' => 'modpress_save_mod',
                'modpress_mod_nonce' => 'valid',
                'post_title' => 'My Mod',
                'post_name' => 'my-mod',
                'post_author' => '7',
                'post_status' => 'draft',
                'post_excerpt' => 'Short summary',
                'post_content' => '<p>Detailed description</p>',
                'featured_image' => '321',
                'mod_status' => 'released',
            ];

            $notice = ( new FunctionsMod() )->save_mod();

            $this->assertStringContainsString( 'Mod saved successfully.', $notice );
            $this->assertSame( 'My Mod', $GLOBALS['modpress_test_inserted_post']['post_title'] );
            $this->assertSame( 'my-mod', $GLOBALS['modpress_test_inserted_post']['post_name'] );
            $this->assertSame( 7, (int) $GLOBALS['modpress_test_inserted_post']['post_author'] );
            $this->assertSame( 'draft', $GLOBALS['modpress_test_inserted_post']['post_status'] );
            $this->assertSame( 'Short summary', $GLOBALS['modpress_test_inserted_post']['post_excerpt'] );
            $this->assertSame( '<p>Detailed description</p>', $GLOBALS['modpress_test_inserted_post']['post_content'] );
            $this->assertSame( 321, (int) ( $GLOBALS['modpress_test_thumbnail'][ 123 ] ?? 0 ) );
        }

        public function testButtonHelperRendersHtmlLabelsForIcons(): void {
            $markup = FormFieldHelper::button(
                '<i class="fa fa-trash"></i>',
                array(
                    'type' => 'button',
                    'class' => 'btn btn-danger',
                )
            );

            $this->assertStringContainsString( '<i class="fa fa-trash"></i>', $markup );
            $this->assertStringNotContainsString( '&lt;i class=&quot;fa fa-trash&quot;&gt;', $markup );
        }

        public function testFileTypeAndFileHostSavesPersistTaxonomyMetadata(): void {
            $GLOBALS['modpress_test_taxonomies'] = array(
                'modpress_file_type' => array(
                    'args' => array( 'hierarchical' => true ),
                ),
                'modpress_file_hosts' => array(
                    'args' => array( 'hierarchical' => false ),
                ),
            );
            $GLOBALS['modpress_test_terms'] = array(
                'modpress_file_type' => array(),
                'modpress_file_hosts' => array(),
            );
            $GLOBALS['modpress_test_term_meta'] = array();
            $GLOBALS['modpress_test_next_term_id'] = 1001;

            $handler = new \ReflectionMethod( FunctionsMod::class, 'persist_taxonomy_term' );
            $handler->setAccessible( true );

            $file_type_id = $handler->invoke(
                new FunctionsMod(),
                'modpress_file_type',
                array(
                    'name' => 'PNG',
                    'slug' => 'png',
                    'description' => 'Portable network graphics',
                    'image' => 'https://example.com/images/png.png',
                )
            );

            $this->assertGreaterThan( 0, $file_type_id );
            $this->assertNotEmpty( $GLOBALS['modpress_test_terms']['modpress_file_type'] );
            $this->assertSame( 'PNG', current( $GLOBALS['modpress_test_terms']['modpress_file_type'] )->name );
            $this->assertSame( 'https://example.com/images/png.png', get_term_meta( $file_type_id, 'image', true ) );

            $host_id = $handler->invoke(
                new FunctionsMod(),
                'modpress_file_hosts',
                array(
                    'name' => 'MegaHost',
                    'slug' => 'megahost',
                    'description' => 'Premium host',
                    'image' => 'https://example.com/images/logo.png',
                    'url' => 'https://megahost.example.com',
                )
            );

            $this->assertGreaterThan( 0, $host_id );
            $this->assertNotEmpty( $GLOBALS['modpress_test_terms']['modpress_file_hosts'] );
            $this->assertSame( 'https://megahost.example.com', get_term_meta( $host_id, 'url', true ) );
            $this->assertSame( 'https://example.com/images/logo.png', get_term_meta( $host_id, 'image', true ) );
        }

        public function testFileTypeAndHostTableRowsUseDedicatedDeleteArrays(): void {
            $GLOBALS['modpress_test_taxonomies'] = array(
                'modpress_file_type' => array(
                    'args' => array( 'hierarchical' => true ),
                ),
                'modpress_file_hosts' => array(
                    'args' => array( 'hierarchical' => false ),
                ),
            );
            $GLOBALS['modpress_test_terms'] = array(
                'modpress_file_type' => array(
                    101 => (object) array(
                        'term_id' => 101,
                        'name' => 'PNG',
                        'slug' => 'png',
                    ),
                ),
                'modpress_file_hosts' => array(
                    202 => (object) array(
                        'term_id' => 202,
                        'name' => 'MegaHost',
                        'slug' => 'megahost',
                    ),
                ),
            );

            $manager = new \ModPress\Admin\Manager\Mods\ModFiles();
            $type_method = new \ReflectionMethod( $manager, 'render_file_types_tab' );
            $type_method->setAccessible( true );
            $host_method = new \ReflectionMethod( $manager, 'render_file_hosts_tab' );
            $host_method->setAccessible( true );

            ob_start();
            $type_method->invoke( $manager, 'modpress_file_type' );
            $type_html = ob_get_clean();

            ob_start();
            $host_method->invoke( $manager, 'modpress_file_hosts' );
            $host_html = ob_get_clean();

            $this->assertStringContainsString( 'name="file_type_ids[]"', $type_html );
            $this->assertStringContainsString( 'value="modpress_delete_file_type"', $type_html );
            $this->assertStringContainsString( 'name="modpress-file-types-term-id"', $type_html );
            $this->assertStringContainsString( 'data-term-id="101"', $type_html );
            $this->assertStringContainsString( 'value="PNG"', $type_html );
            $this->assertStringNotContainsString( 'modpress_delete_group_entry', $type_html );
            $this->assertStringContainsString( 'name="file_host_ids[]"', $host_html );
            $this->assertStringContainsString( 'value="modpress_delete_file_host"', $host_html );
            $this->assertStringContainsString( 'name="modpress-file-hosts-term-id"', $host_html );
            $this->assertStringContainsString( 'data-term-id="202"', $host_html );
            $this->assertStringContainsString( 'value="MegaHost"', $host_html );
            $this->assertStringNotContainsString( 'modpress_delete_group_entry', $host_html );
        }

        public function testChangelogPageRendersEntryAndCategoryTabs(): void {
            $GLOBALS['modpress_test_taxonomies'] = array(
                'modpress_changelog_category' => array(
                    'args' => array( 'hierarchical' => true ),
                ),
            );
            $GLOBALS['modpress_test_terms'] = array(
                'modpress_changelog_category' => array(
                    301 => (object) array(
                        'term_id' => 301,
                        'name' => 'Bug Fixes',
                        'slug' => 'bug-fixes',
                        'description' => 'Fixes and stability improvements',
                    ),
                ),
            );
            $GLOBALS['modpress_test_term_meta'] = array(
                301 => array(
                    'category_color' => array( '#ff9900' ),
                ),
            );
            $GLOBALS['modpress_test_posts'] = array(
                'modpress_changelog' => array(
                    401 => (object) array(
                        'ID' => 401,
                        'post_title' => '1.4.0',
                        'post_status' => 'publish',
                        'post_type' => 'modpress_changelog',
                    ),
                ),
            );

            $manager = new \ModPress\Admin\Manager\Mods\ModChangeLogs();
            ob_start();
            $manager->render_page_content();
            $html = ob_get_clean();

            $this->assertStringContainsString( 'modpress-changelog-pane-changelogs', $html );
            $this->assertStringContainsString( 'modpress-changelog-pane-categories', $html );
            $this->assertStringContainsString( 'modpress_save_changelog', $html );
            $this->assertStringContainsString( 'modpress_save_changelog_category', $html );
            $this->assertStringContainsString( 'modpress_changelog_category', $html );
            $this->assertStringContainsString( 'background-color: #ff9900', $html );
            $this->assertStringContainsString( 'data-term-color="#ff9900"', $html );
            $this->assertStringContainsString( 'name="modpress-changelog-category-name"', $html );
            $this->assertStringContainsString( 'name="modpress-changelog-category-color"', $html );
        }

        public function testEntryModalIncludesGroupCustomFieldsBeforeTaxonomyRegistration(): void {
            $GLOBALS['modpress_test_taxonomies'] = array();
            $manager = new \ModPress\Admin\Manager\Mods\ModGroups();
            $method = new \ReflectionMethod( $manager, 'render_entry_modal' );
            $method->setAccessible( true );

            ob_start();
            $method->invoke(
                $manager,
                array(
                    array(
                        'taxonomy' => 'modpress_new_group',
                        'key' => 'modpress_new_group',
                        'group_fields' => json_encode(
                            array(
                                array(
                                    'id' => 'field_1',
                                    'name' => 'Color',
                                    'slug' => 'color',
                                    'type' => 'color',
                                ),
                            )
                        ),
                    ),
                ),
                'modpress_new_group'
            );
            $html = ob_get_clean();

            $this->assertStringContainsString( 'Color', $html );
            $this->assertStringContainsString( 'name="color"', $html );
            $this->assertStringNotContainsString( 'name="entry_custom_field_color"', $html );
        }

        public function testGroupsManagerProvidesGroupAndEntryLookupContracts(): void {
            $GLOBALS['modpress_test_terms'] = array(
                'modpress_games' => array(
                    15 => (object) array(
                        'term_id' => 15,
                        'name' => 'Skyrim',
                        'slug' => 'skyrim',
                        'taxonomy' => 'modpress_games',
                    ),
                ),
            );
            $GLOBALS['modpress_test_term_meta'] = array(
                15 => array(
                    'color' => array( 'red' ),
                ),
            );
            $GLOBALS['modpress_test_db_groups'] = array(
                array(
                    'id' => 7,
                    'group_key' => 'modpress_games',
                    'group_slug' => 'games',
                    'group_name' => 'Games',
                    'group_fields' => json_encode( array(
                        array(
                            'id' => 'field_1',
                            'name' => 'Color',
                            'slug' => 'color',
                            'type' => 'color',
                        ),
                    ) ),
                ),
            );

            $this->assertSame( 'modpress_games', GroupsManager::get_group_key( 'games' ) );
            $this->assertSame( 'modpress_games', GroupsManager::get_group_key( 'modpress_games' ) );
            $this->assertSame( 'red', GroupsManager::get_group_entry( 15, 'modpress_games', 'color' ) );
            $this->assertSame( 7, (int) GroupsManager::get_group( 'games' )['id'] );
        }

        public function testRestGetGroupEntryReturnsSingleCanonicalCustomFieldValue(): void {
            $GLOBALS['modpress_test_taxonomies'] = array(
                'modpress_games' => array(
                    'args' => array( 'hierarchical' => true ),
                ),
            );
            $GLOBALS['modpress_test_terms'] = array(
                'modpress_games' => array(
                    88 => (object) array(
                        'term_id' => 88,
                        'name' => 'Skyrim',
                        'slug' => 'skyrim',
                        'taxonomy' => 'modpress_games',
                    ),
                ),
            );
            $GLOBALS['modpress_test_term_meta'] = array(
                88 => array(
                    'priority' => array( 'high' ),
                    'entry_icon' => array( 'dashicons-admin-site-alt3' ),
                ),
            );
            $GLOBALS['modpress_test_db_groups'] = array(
                array(
                    'id' => 7,
                    'group_key' => 'modpress_games',
                    'group_slug' => 'games',
                    'group_name' => 'Games',
                    'group_fields' => json_encode( array(
                        array(
                            'id' => 'field_7',
                            'name' => 'Priority',
                            'slug' => 'priority',
                            'type' => 'radio',
                        ),
                    ) ),
                ),
            );

            $request = new \WP_REST_Request( 'GET', '/modpress/v1/groups/modpress_games/entries/88' );
            $request->set_param( 'group_key', 'modpress_games' );
            $request->set_param( 'group_entry_id', 88 );

            $response = \ModPress\Includes\ModManagement\GroupsManager::rest_get_group_entry( $request );
            $this->assertInstanceOf( '\\WP_REST_Response', $response );

            $data = $response->get_data();
            $this->assertArrayHasKey( 'priority', $data['custom_fields'] );
            $this->assertSame( 'high', $data['custom_fields']['priority'] );
            $this->assertCount( 1, $data['custom_fields'] );
        }

        public function testRestGetGroupEntryUsesStableFieldIdAliases(): void {
            $GLOBALS['modpress_test_taxonomies'] = array(
                'modpress_games' => array(
                    'args' => array( 'hierarchical' => true ),
                ),
            );
            $GLOBALS['modpress_test_terms'] = array(
                'modpress_games' => array(
                    99 => (object) array(
                        'term_id' => 99,
                        'name' => 'The Elder Scrolls',
                        'slug' => 'the-elder-scrolls',
                        'taxonomy' => 'modpress_games',
                    ),
                ),
            );
            $GLOBALS['modpress_test_term_meta'] = array(
                99 => array(
                    'field_7' => array( 'low' ),
                    'entry_icon' => array( 'dashicons-admin-site-alt3' ),
                ),
            );
            $GLOBALS['modpress_test_db_groups'] = array(
                array(
                    'id' => 7,
                    'group_key' => 'modpress_games',
                    'group_slug' => 'games',
                    'group_name' => 'Games',
                    'group_fields' => json_encode( array(
                        array(
                            'id' => 'field_7',
                            'name' => 'Priority',
                            'slug' => 'priority',
                            'type' => 'radio',
                        ),
                    ) ),
                ),
            );

            $request = new \WP_REST_Request( 'GET', '/modpress/v1/groups/modpress_games/entries/99' );
            $request->set_param( 'group_key', 'modpress_games' );
            $request->set_param( 'group_entry_id', 99 );

            $response = \ModPress\Includes\ModManagement\GroupsManager::rest_get_group_entry( $request );
            $this->assertInstanceOf( '\\WP_REST_Response', $response );

            $data = $response->get_data();
            $this->assertSame( 'low', $data['custom_fields']['priority'] );
        }

        public function testRenderGroupCustomFieldUnwrapsTermMetaArrayValues(): void {
            $field = array(
                'id' => 'field_7',
                'name' => 'Priority',
                'slug' => 'priority',
                'type' => 'text',
            );

            $html = FunctionsMod::render_group_custom_field(
                $field,
                array(
                    'priority' => array( 'high' ),
                    'priority' => array( 'high' ),
                )
            );

            $this->assertStringContainsString( 'value="high"', $html );
            $this->assertStringNotContainsString( 'Array', $html );
        }

        public function testRenderGroupCustomFieldUsesStoredMetaKeyToResolveFieldType(): void {
            $field = array(
                'id' => 'field_7',
                'name' => 'Priority',
                'slug' => 'priority',
                'type' => 'text',
            );

            $group_fields = array(
                array(
                    'id' => 'field_7',
                    'name' => 'Priority',
                    'slug' => 'priority',
                    'type' => 'radio',
                    'options' => array(
                        array( 'label' => 'Low', 'value' => 'low' ),
                        array( 'label' => 'High', 'value' => 'high' ),
                    ),
                ),
            );

            $html = FunctionsMod::render_group_custom_field(
                $field,
                array(
                    'priority' => array( 'high' ),
                ),
                $group_fields
            );

            $this->assertStringContainsString( 'checked', $html );
            $this->assertStringContainsString( 'value="high"', $html );
        }

        public function testRenderGroupCustomFieldHydratesImageFieldVariantMetaKeys(): void {
            $field = array(
                'id' => 'field_logo',
                'name' => 'Logo',
                'slug' => 'logo',
                'type' => 'image',
                'image_types' => array( 'image', 'icon', 'url' ),
            );

            $html = FunctionsMod::render_group_custom_field(
                $field,
                array(
                    'logo_image' => array( 'https://example.com/logo.png' ),
                    'logo_icon' => array( 'fa-solid fa-star' ),
                    'logo_url' => array( 'https://example.com/logo-url.png' ),
                )
            );

            $this->assertStringContainsString( 'name="logo_image"', $html );
            $this->assertStringContainsString( 'value="https://example.com/logo.png"', $html );
            $this->assertStringContainsString( 'data-image-mode="image"', $html );
        }

        public function testGroupsManagerResolvesImageFieldVariantMetaKeys(): void {
            $GLOBALS['modpress_test_term_meta'] = array(
                77 => array(
                    'logo_image' => array( 'https://example.com/logo.png' ),
                    'logo_icon' => array( 'fa-solid fa-star' ),
                    'logo_url' => array( 'https://example.com/logo-url.png' ),
                ),
            );

            $this->assertSame( 'https://example.com/logo.png', GroupsManager::get_group_entry( 77, 'modpress_games', 'logo' ) );
            $this->assertSame( 'fa-solid fa-star', GroupsManager::get_group_entry( 77, 'modpress_games', 'logo_icon' ) );
        }

        public function testValidSubmissionIsSanitizedAndInserted(): void {
            $_POST = [
                'modpress_action' => 'save_mod',
                'modpress_mod_nonce' => 'valid',
                'post_title' => "  <b>My Mod</b>  ",
                'post_content' => '<p>Details</p><script>alert(1)</script><strong>Ready</strong>',
            ];

            $notice = ( new FunctionsMod() )->save_mod();

            $this->assertStringContainsString( 'Mod saved successfully.', $notice );
            $this->assertSame(
                [
                    'post_title' => 'My Mod',
                    'post_content' => '<p>Details</p>alert(1)<strong>Ready</strong>',
                    'post_status' => 'publish',
                    'post_author' => 42,
                    'post_type' => 'modpress_mod',
                ],
                $GLOBALS['modpress_test_inserted_post']
            );
        }

        public function testInsertErrorsReturnAnErrorNotice(): void {
            $GLOBALS['modpress_test_insert_result'] = new \WP_Error( 'insert_failed' );
            $_POST = [
                'modpress_action' => 'save_mod',
                'modpress_mod_nonce' => 'valid',
                'post_title' => 'My Mod',
            ];

            $notice = ( new FunctionsMod() )->save_mod();

            $this->assertStringContainsString( 'The Mod could not be saved.', $notice );
        }

        public function testModManagerRoutesGroupSavesToFunctionsMod(): void {
            $ref = new \ReflectionClass( '\ModPress\Admin\Manager\Mods\ModManager' );
            $manager = $ref->newInstanceWithoutConstructor();
            $property = $ref->getProperty( 'functions_mod' );
            $property->setAccessible( true );
            $functions_mod = new \ModPress\Includes\Functions\Admin\FunctionsMod();
            $property->setValue( $manager, $functions_mod );
            $manager->register_actions();

            $this->assertTrue( has_action( 'admin_post_modpress_save_mod_group', [ $functions_mod, 'handle_group_save' ] ) );
            $this->assertTrue( has_action( 'admin_post_modpress_save_mod_group_entry', [ $functions_mod, 'handle_group_entry_save' ] ) );
        }

        public function testModManagerConstructorRegistersAdminPostHandlers(): void {
            $manager = new \ModPress\Admin\Manager\Mods\ModManager();
            $ref = new \ReflectionClass( $manager );
            $property = $ref->getProperty( 'functions_mod' );
            $property->setAccessible( true );
            $functions_mod = $property->getValue( $manager );

            $this->assertNotNull( $functions_mod );
            $this->assertTrue( has_action( 'admin_post_modpress_save_group', [ $functions_mod, 'save_group' ] ) );
            $this->assertTrue( has_action( 'admin_post_modpress_save_group_entry', [ $functions_mod, 'save_group_entry' ] ) );
            $this->assertTrue( has_action( 'admin_post_modpress_delete_group_entry', [ $functions_mod, 'delete_group_entry' ] ) );
        }

        public function testAdminPostCallbacksExistForAllRegisteredModActions(): void {
            $functions_mod = new \ModPress\Includes\Functions\Admin\FunctionsMod();

            $this->assertTrue( method_exists( $functions_mod, 'handle_group_save' ) );
            $this->assertTrue( method_exists( $functions_mod, 'handle_group_entry_save' ) );
            $this->assertTrue( method_exists( $functions_mod, 'handle_tags_save' ) );
            $this->assertTrue( method_exists( $functions_mod, 'handle_files_save' ) );
            $this->assertTrue( method_exists( $functions_mod, 'handle_file_type_save' ) );
            $this->assertTrue( method_exists( $functions_mod, 'handle_changelog_save' ) );
            $this->assertTrue( method_exists( $functions_mod, 'handle_changelog_category_save' ) );
            $this->assertTrue( method_exists( $functions_mod, 'handle_mod_save' ) );
        }

        public function testRootModTypeGroupCannotBeDeleted(): void {
            $functions_mod = new \ModPress\Includes\Functions\Admin\FunctionsMod();

            $this->assertTrue( method_exists( $functions_mod, 'delete_group' ) );
        }

        public function testGroupsTableIsRegisteredForCustomModGroupStorage(): void {
            Schema::register_tables();

            $this->assertArrayHasKey( 'groups', Database::get_core_tables() );
            $this->assertSame( 'wp_modpress_groups', DBHelper::table_name( 'groups' ) );
        }

        public function testLayoutConfigurationCapabilitiesExistForSettingsAccess(): void {
            $definitions = Capabilities::definitions();

            $this->assertArrayHasKey( 'modpress_settings_layout_view', $definitions );
            $this->assertArrayHasKey( 'modpress_settings_layout_edit', $definitions );
        }

        public function testSettingsInstallOnlySeedsOncePerRuntime(): void {
            $wpdb = new class {
                public string $prefix = 'wp_';
                public array $storage = [];

                public function prepare( string $query, ...$args ): string {
                    foreach ( $args as $arg ) {
                        $query = preg_replace( '/%s/', (string) $arg, $query, 1 );
                    }
                    return $query;
                }

                public function get_var( string $query ) {
                    if ( preg_match( '/SHOW TABLES LIKE \'([^\']+)\'/', $query, $matches ) ) {
                        return $matches[1] === 'wp_modpress_settings' ? 'wp_modpress_settings' : null;
                    }

                    if ( preg_match( "/SELECT setting_value FROM wp_modpress_settings WHERE setting_group = '([^']+)'/", $query, $matches ) ) {
                        return $this->storage['modpress_settings'][ $matches[1] ] ?? null;
                    }

                    return null;
                }

                public function replace( string $table, array $data, array $formats = [] ) {
                    $this->storage[ $table ][ $data['setting_group'] ?? $data['setting_key'] ] = serialize( $data['setting_value'] );
                    return true;
                }
            };

            $GLOBALS['wpdb'] = $wpdb;

            $class = new \ReflectionClass( SettingsManager::class );
            $property = $class->getProperty( 'seeded' );
            $property->setAccessible( true );
            $property->setValue( null, false );

            SettingsManager::install();
            SettingsManager::install();

            $this->assertTrue( $property->getValue() );
        }

        public function testFontAwesomeSettingsGroupIsNormalizedToUnderscoreStorage(): void {
            $method = new \ReflectionMethod( SettingsManager::class, 'normalize_group' );
            $method->setAccessible( true );

            $this->assertSame( 'fontawesome', $method->invoke( null, 'modpress-fontawesome' ) );
            $this->assertSame( 'fontawesome', $method->invoke( null, 'fontawesome' ) );
            $this->assertSame( 'fontawesome', $method->invoke( null, 'modpress_fontawesome' ) );
        }

        public function testGroupsManagerCanReadTermFieldsAndMetaByTaxonomy(): void {
            $GLOBALS['modpress_test_taxonomies'] = [ 'modpress_type' => true ];
            $GLOBALS['modpress_test_terms'] = [
                'modpress_type' => [
                    21 => (object) [
                        'term_id' => 21,
                        'name' => 'Maps',
                        'slug' => 'maps',
                        'description' => 'Example description.',
                        'parent' => 0,
                    ],
                ],
            ];
            $GLOBALS['modpress_test_term_meta'] = [
                21 => [
                    'entry_icon' => [ 'dashicons-admin-site-alt3' ],
                ],
            ];

            $this->assertSame( 'Maps', \ModPress\Includes\ModManagement\GroupsManager::get_group_entry( 21, 'modpress_type', 'name' ) );
            $this->assertSame( 'maps', \ModPress\Includes\ModManagement\GroupsManager::get_group_entry( 21, 'modpress_type', 'slug' ) );
            $this->assertSame( 'Example description.', \ModPress\Includes\ModManagement\GroupsManager::get_group_entry( 21, 'modpress_type', 'description' ) );
            $this->assertSame( 'dashicons-admin-site-alt3', \ModPress\Includes\ModManagement\GroupsManager::get_group_entry( 21, 'modpress_type', 'entry_icon' ) );
        }

        public function testGroupsManagerBuildsTaxonomyDefinitionsFromDatabaseRows(): void {
            $GLOBALS['modpress_test_db_groups'] = [
                [
                    'id' => 1,
                    'group_name' => 'Mod Type',
                    'group_slug' => 'type',
                    'group_description' => 'Root type group.',
                    'group_hierarchical' => 0,
                ],
            ];

            $service = new \ModPress\Includes\ModManagement\GroupsManager();
            $definitions = $service->get_registered_taxonomies();

            $this->assertArrayHasKey( 'modpress_type', $definitions );
            $this->assertSame( 'Mod Type', $definitions['modpress_type']['label'] );
            $this->assertSame( array( 'modpress_mod' ), $definitions['modpress_type']['post_type'] );
        }

        public function testEntryModalSubmitsToAdminPostEndpoint(): void {
            $file = __DIR__ . '/../../src/Admin/Manager/Mods/ModGroups.php';
            $this->assertFileExists( $file );

            $source = file_get_contents( $file );
            $this->assertNotFalse( $source );
            $this->assertStringContainsString( "action=\"<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>\"", $source );
            $this->assertStringContainsString( 'name="action" value="modpress_save_group_entry"', $source );
        }

        public function testPluginSlugLookupMatchesHyphenatedAndSanitizedSlugs(): void {
            $plugin = new class implements \ModPress\Includes\Plugins\PluginInterface, \ModPress\Includes\Plugins\SettingsPageProviderInterface {
                public function get_slug(): string { return 'modpress-fontawesome'; }
                public function get_name(): string { return 'FontAwesome'; }
                public function get_version(): string { return '1.0.0'; }
                public function get_author(): string { return 'ModPress'; }
                public function get_author_uri(): string { return 'https://example.com'; }
                public function get_description(): string { return 'Example'; }
                public function get_uri(): string { return 'https://example.com'; }
                public function get_license(): string { return 'GPL'; }
                public function is_active(): bool { return true; }
                public function init(): void {}
                public function get_settings_page(): array { return [ 'slug' => 'fontawesome', 'label' => 'FontAwesome', 'title' => 'FontAwesome', 'fields' => [] ]; }
                public function sanitize_settings( $input ): array { return is_array( $input ) ? $input : []; }
            };

            \ModPress\Includes\Plugins\Plugins::get_instance()->register_plugin_instance( $plugin );

            $handler = new \ModPress\Includes\Functions\Admin\FunctionsPlugins();
            $method = new \ReflectionMethod( \ModPress\Includes\Functions\Admin\FunctionsPlugins::class, 'resolve_plugin_slug' );
            $method->setAccessible( true );

            $this->assertSame( 'modpress-fontawesome', $method->invoke( $handler, 'modpress-fontawesome' ) );
            $this->assertSame( 'modpress-fontawesome', $method->invoke( $handler, 'modpressfontawesome' ) );
        }

        public function testSettingsGroupWriteFailsWhenTheDatabaseDoesNotConfirmPersistedValue(): void {
            $wpdb = new class {
                public string $prefix = 'wp_';
                public array $storage = [];

                public function prepare( string $query, ...$args ): string {
                    foreach ( $args as $arg ) {
                        $query = preg_replace( '/%s/', (string) $arg, $query, 1 );
                    }
                    return $query;
                }

                public function replace( string $table, array $data, array $formats = [] ): bool {
                    $this->storage[ $table ][ $data['setting_group'] ] = serialize( $data['setting_value'] );
                    return true;
                }

                public function get_var( string $query ) {
                    if ( preg_match( "/WHERE setting_group = '([^']+)'/", $query, $matches ) ) {
                        $group = $matches[1];
                        return $this->storage[ 'modpress_settings' ][ $group ] ?? null;
                    }
                    return null;
                }
            };

            $GLOBALS['wpdb'] = $wpdb;
            $wpdb->storage['modpress_settings']['modpress_general'] = serialize( 'stale-value' );

            $this->assertFalse( \ModPress\Includes\Settings\SettingsManager::set_group( 'general', [ 'root_name' => 'Updated root' ] ) );
        }

        public function testAdminManagerConstructorsDoNotRecurseIntoChildScreens(): void {
            $manager = new \ModPress\Admin\Manager\Mods\ModManager();
            $screen  = new \ModPress\Admin\Manager\Mods\ModDashboard();

            $this->assertSame( 'mod-manager', $manager->get_page() );
            $this->assertSame( 'mod-manager', $screen->get_page() );

            $tools = new \ModPress\Admin\Manager\Tools\ToolsManager( false );
            $this->assertSame( 'tools', $tools->get_page() );
        }

        public function testPersistGroupRecordUsesCanonicalGroupKeyWhenEditingExistingGroups(): void {
            $GLOBALS['wpdb'] = new class {
                public string $prefix = 'wp_';
                public int $insert_id = 0;
                public string $last_error = '';
                public array $storage = [
                    'wp_modpress_groups' => [
                        55 => [
                            'id' => 55,
                            'group_key' => 'modpress_type',
                            'group_slug' => 'type',
                            'group_name' => 'Mod Type',
                            'group_singular_name' => 'Mod Type',
                            'group_description' => 'Root type group.',
                            'group_hierarchical' => 0,
                            'group_fields' => null,
                            'created_by' => 1,
                            'created_at' => '2024-01-01 00:00:00',
                            'updated_at' => '2024-01-01 00:00:00',
                        ],
                    ],
                ];

                public function prepare( string $query, ...$args ): string {
                    $flat_args = [];
                    foreach ( $args as $arg ) {
                        if ( is_array( $arg ) ) {
                            $flat_args = array_merge( $flat_args, $arg );
                            continue;
                        }
                        $flat_args[] = $arg;
                    }

                    foreach ( $flat_args as $arg ) {
                        $query = preg_replace( '/%s/', (string) $arg, $query, 1 );
                    }

                    return $query;
                }

                public function get_row( string $query, $output = null ) {
                    if ( preg_match( '/FROM wp_modpress_groups WHERE .*group_key.*=\s*([A-Za-z0-9_\-]+)/', $query, $matches ) ) {
                        foreach ( $this->storage['wp_modpress_groups'] as $row ) {
                            if ( ( $row['group_key'] ?? '' ) === $matches[1] ) {
                                return $row;
                            }
                        }
                        return null;
                    }

                    if ( preg_match( '/FROM wp_modpress_groups WHERE .*group_slug.*=\s*([A-Za-z0-9_\-]+)/', $query, $matches ) ) {
                        foreach ( $this->storage['wp_modpress_groups'] as $row ) {
                            if ( ( $row['group_slug'] ?? '' ) === $matches[1] ) {
                                return $row;
                            }
                        }
                        return null;
                    }

                    return null;
                }

                public function query( string $query ) {
                    if ( preg_match( '/UPDATE wp_modpress_groups SET .* WHERE id = (\\d+)/', $query, $matches ) ) {
                        return true;
                    }

                    return true;
                }
            };

            $_POST['group_edit_key'] = 'modpress_type';

            $method = new \ReflectionMethod( \ModPress\Includes\Functions\Admin\FunctionsMod::class, 'persist_group_record' );
            $method->setAccessible( true );

            $payload = [
                'group_key' => 'modpress_type',
                'group_name' => 'Mod Type',
                'group_slug' => 'type',
                'group_singular_name' => 'Mod Type',
                'group_description' => 'Updated group description.',
                'group_hierarchical' => 0,
                'group_fields' => null,
                'created_by' => 1,
            ];

            $this->assertSame( 55, $method->invoke( new \ModPress\Includes\Functions\Admin\FunctionsMod(), $payload ) );
        }

        public function testDbHelperTreatsZeroRowWriteAsFailure(): void {
            $GLOBALS['wpdb'] = new class {
                public string $prefix = 'wp_';
                public int $rows_affected = 0;
                public int $insert_id = 0;

                public function prepare( string $query, ...$args ): string {
                    return $query;
                }

                public function query( string $query ) {
                    return 0;
                }
            };

            $this->assertFalse( \ModPress\Includes\Functions\Helpers\DBHelper::update( 'groups', [ 'group_name' => 'Sample' ], [ 'id' => 999 ] ) );
            $this->assertFalse( \ModPress\Includes\Functions\Helpers\DBHelper::delete( 'groups', [ 'id' => 999 ] ) );
        }

        public function testFontAwesomeHelperBuildsStyledAndAnimatedIconMarkup(): void {
            $output = FAHelper::render_icon(
                'house',
                [
                    'family' => 'classic',
                    'style' => 'solid',
                    'size' => 'lg',
                    'fixed_width' => true,
                    'flip' => 'horizontal',
                    'rotate' => 90,
                    'animation' => 'spin',
                    'transform' => 'grow-2 right-4',
                    'class' => 'demo-icon',
                    'aria_label' => 'Home icon',
                    'title' => 'Home',
                    'data_test' => 'example',
                ]
            );

            $this->assertStringContainsString( 'fa-solid', $output );
            $this->assertStringContainsString( 'fa-house', $output );
            $this->assertStringContainsString( 'fa-size-lg', $output );
            $this->assertStringContainsString( 'fa-fw', $output );
            $this->assertStringContainsString( 'fa-flip-horizontal', $output );
            $this->assertStringContainsString( 'fa-rotate-90', $output );
            $this->assertStringContainsString( 'fa-spin', $output );
            $this->assertStringContainsString( 'demo-icon', $output );
            $this->assertStringContainsString( 'aria-label="Home icon"', $output );
            $this->assertStringContainsString( 'title="Home"', $output );
            $this->assertStringContainsString( 'data-test="example"', $output );
        }

        public function testGroupParentValuesAreNormalizedToSerializedPhpArrays(): void {
            $values = \ModPress\Includes\Functions\Admin\FunctionsMod::normalize_group_parent_values( array( '12', '7', '7' ) );

            $this->assertSame( array( '12', '7' ), $values );
            $this->assertSame( 'a:2:{i:0;s:2:"12";i:1;s:1:"7";}', serialize( $values ) );
        }

        public function testGroupParentSelectionValuesStayAsStoredIds(): void {
            $values = \ModPress\Includes\Functions\Admin\FunctionsMod::normalize_group_parent_values( array( 420, 421, 421 ) );

            $this->assertSame( array( '420', '421' ), $values );
            $this->assertSame( 'a:2:{i:0;i:420;i:1;i:421;}', serialize( array( 420, 421 ) ) );
        }

        public function testGroupPayloadSkipsZeroParentIdsAndRetainsDescription(): void {
            $_POST = array(
                'group_name' => 'Language',
                'group_slug' => 'modpress_language',
                'group_singular_name' => 'Language',
                'group_description' => 'Language group description.',
                'group_hierarchical' => '1',
                'group_parent' => array( 0, 420, 421, 0 ),
                'group_parent_entry' => array( 0, 84, 85 ),
            );

            $method = new \ReflectionMethod( \ModPress\Includes\Functions\Admin\FunctionsMod::class, 'build_group_payload' );
            $method->setAccessible( true );

            $payload = $method->invoke( new \ModPress\Includes\Functions\Admin\FunctionsMod(), 'Language', 'modpress_language' );

            $this->assertSame( 'Language group description.', $payload['group_description'] );
            $this->assertSame( 'a:2:{i:0;i:420;i:1;i:421;}', $payload['group_parent'] );
            $this->assertSame( 'a:2:{i:0;i:84;i:1;i:85;}', $payload['group_parent_entry'] );
        }

        public function testGroupPayloadPersistsMultiselectFlag(): void {
            $_POST = array(
                'group_name' => 'Language',
                'group_slug' => 'modpress_language',
                'group_singular_name' => 'Language',
                'group_description' => 'Language group description.',
                'group_hierarchical' => '0',
                'group_multiselect' => '1',
                'group_parent' => array( 0, 420 ),
                'group_parent_entry' => array( 0, 84 ),
            );

            $method = new \ReflectionMethod( \ModPress\Includes\Functions\Admin\FunctionsMod::class, 'build_group_payload' );
            $method->setAccessible( true );

            $payload = $method->invoke( new \ModPress\Includes\Functions\Admin\FunctionsMod(), 'Language', 'modpress_language' );

            $this->assertArrayHasKey( 'group_multiselect', $payload );
            $this->assertTrue( (bool) $payload['group_multiselect'] );
        }

        public function testSwitchSelectPreservesSelectedModeValue(): void {
            $_POST = array(
                'group_name' => 'Mod release',
                'group_slug' => 'modpress_mod_release',
                'group_singular_name' => 'Mod release',
                'group_description' => '',
                'group_hierarchical' => '0',
                'custom_field_label' => array( 'Toggle label' ),
                'custom_field_slug' => array( 'toggle_label' ),
                'custom_field_type' => array( 'switch' ),
                'custom_field_type_switch' => array( 'custom' ),
                'custom_field_type_switch_custom' => array( 'enable, disable' ),
            );

            $method = new \ReflectionMethod( \ModPress\Includes\Functions\Admin\FunctionsMod::class, 'build_group_payload' );
            $method->setAccessible( true );

            $payload = $method->invoke( new \ModPress\Includes\Functions\Admin\FunctionsMod(), 'Mod release', 'modpress_mod_release' );
            $fields = json_decode( (string) $payload['group_fields'], true );

            $this->assertSame( 'custom', $fields[0]['switch'] );
            $this->assertSame( array( 'enable', 'disable' ), $fields[0]['switch_custom'] );
        }

        public function testSwitchSettingRendersBootstrapSwitchMarkupInsteadOfCheckboxFallback(): void {
            $method = new \ReflectionMethod( \ModPress\Admin\Manager\Settings\SettingsPlugins::class, 'render_plugin_settings_fields' );
            $method->setAccessible( true );

            ob_start();
            $method->invoke(
                new \ModPress\Admin\Manager\Settings\SettingsPlugins(),
                array(
                    'slug' => 'fontawesome',
                    'layout' => 'table',
                    'fields' => array(
                        array(
                            'key' => 'fontawesome_enable_icon_picker',
                            'label' => 'Enable icon picker',
                            'type' => 'switch',
                            'default' => true,
                        ),
                    ),
                ),
                array(
                    'fontawesome_enable_icon_picker' => true,
                ),
                'modpress-fontawesome'
            );
            $output = ob_get_clean();

            $this->assertStringContainsString( 'name="settings[fontawesome_enable_icon_picker]"', $output );
            $this->assertStringContainsString( 'role="switch"', $output );
            $this->assertStringContainsString( 'class="form-check form-switch', $output );
        }

        public function testSwitchCheckboxUsesStandardValueWhenBooleanFalseIsPassed(): void {
            $output = \ModPress\Includes\Functions\Helpers\FormFieldHelper::switch(
                'custom_field_show_in_table[]',
                false,
                __( 'Enabled', 'modpress' ),
                array(
                    'checked' => false,
                )
            );

            $this->assertStringContainsString( 'name="custom_field_show_in_table[]"', $output );
            $this->assertStringContainsString( 'value="1"', $output );
            $this->assertStringContainsString( 'type="hidden"', $output );
            $this->assertStringContainsString( 'value="0"', $output );
        }

        public function testColorFieldUsesBooleanCustomColorSetting(): void {
            $_POST = array(
                'group_name' => 'Mod release',
                'group_slug' => 'modpress_mod_release',
                'group_singular_name' => 'Mod release',
                'group_description' => '',
                'group_hierarchical' => '0',
                'custom_field_label' => array( 'Accent colour' ),
                'custom_field_slug' => array( 'accent-colour' ),
                'custom_field_type' => array( 'color' ),
                'custom_field_type_color' => array( '1' ),
            );

            $method = new \ReflectionMethod( \ModPress\Includes\Functions\Admin\FunctionsMod::class, 'build_group_payload' );
            $method->setAccessible( true );

            $payload = $method->invoke( new \ModPress\Includes\Functions\Admin\FunctionsMod(), 'Mod release', 'modpress_mod_release' );
            $fields = json_decode( (string) $payload['group_fields'], true );

            $this->assertSame( true, $fields[0]['custom_color_use'] );
            $this->assertArrayNotHasKey( 'color', $fields[0] );
        }

        public function testColorFieldUsesBooleanCustomColorSettingWhenRowsAreSparseAndIdKeyed(): void {
            $_POST = array(
                'group_name' => 'Mod release',
                'group_slug' => 'modpress_mod_release',
                'group_singular_name' => 'Mod release',
                'group_description' => '',
                'group_hierarchical' => '0',
                'custom_field_id' => array(
                    'field_1' => 'field_1',
                    'field_3' => 'field_3',
                ),
                'custom_field_label' => array(
                    'field_1' => 'First field',
                    'field_3' => 'Accent colour',
                ),
                'custom_field_slug' => array(
                    'field_1' => 'first-field',
                    'field_3' => 'accent-colour',
                ),
                'custom_field_type' => array(
                    'field_1' => 'text',
                    'field_3' => 'color',
                ),
                'custom_field_type_color' => array(
                    'field_3' => '1',
                ),
            );

            $method = new \ReflectionMethod( \ModPress\Includes\Functions\Admin\FunctionsMod::class, 'build_group_payload' );
            $method->setAccessible( true );

            $payload = $method->invoke( new \ModPress\Includes\Functions\Admin\FunctionsMod(), 'Mod release', 'modpress_mod_release' );
            $fields = json_decode( (string) $payload['group_fields'], true );

            $this->assertSame( true, $fields[1]['custom_color_use'] );
            $this->assertSame( 'Accent colour', $fields[1]['name'] );
        }

        public function testRenderGroupCustomFieldUsesUniqueFieldIdsForModeInputs(): void {
            $output = \ModPress\Includes\Functions\Admin\FunctionsMod::render_group_custom_field(
                array(
                    'id' => 'field_3',
                    'name' => 'Image',
                    'slug' => 'image',
                    'type' => 'image',
                    'image_types' => array( 'image', 'icon', 'url' ),
                ),
                array()
            );

            $this->assertStringContainsString( 'name="image_mode"', $output );
            $this->assertStringContainsString( 'name="image"', $output );
            $this->assertStringNotContainsString( 'name="entry_custom_field_image_mode"', $output );
        }

        public function testGroupEntryAjaxPayloadIncludesFieldIdAliasesForHydration(): void {
            $GLOBALS['modpress_test_taxonomies'] = array(
                'modpress_games' => array(
                    'args' => array( 'hierarchical' => true ),
                ),
            );
            $GLOBALS['modpress_test_terms'] = array(
                'modpress_games' => array(
                    88 => (object) array(
                        'term_id' => 88,
                        'name' => 'Skyrim',
                        'slug' => 'skyrim',
                        'taxonomy' => 'modpress_games',
                    ),
                ),
            );
            $GLOBALS['modpress_test_term_meta'] = array(
                88 => array(
                    'priority' => array( 'high' ),
                    'entry_icon' => array( 'dashicons-admin-site-alt3' ),
                ),
            );
            $GLOBALS['modpress_test_db_groups'] = array(
                array(
                    'id' => 7,
                    'group_key' => 'modpress_games',
                    'group_slug' => 'games',
                    'group_name' => 'Games',
                    'group_fields' => json_encode( array(
                        array(
                            'id' => 'field_7',
                            'name' => 'Priority',
                            'slug' => 'priority',
                            'type' => 'radio',
                        ),
                    ) ),
                ),
            );

            $_POST = array(
                'group_slug' => 'modpress_games',
                'group_entry_id' => '88',
                'nonce' => 'valid',
            );
            $GLOBALS['modpress_last_json_success'] = null;

            \ModPress\Includes\ModManagement\GroupsManager::load_group_entry();

            $this->assertNotNull( $GLOBALS['modpress_last_json_success'] );
            $this->assertSame( 'high', $GLOBALS['modpress_last_json_success']['priority'] );
            $this->assertSame( 'high', $GLOBALS['modpress_last_json_success']['priority'] );
        }

        public function testRenderGroupCustomFieldRestoresNestedSavedOptionValues(): void {
            $output = \ModPress\Includes\Functions\Admin\FunctionsMod::render_group_custom_field(
                array(
                    'id' => 'field_7',
                    'name' => 'Priority',
                    'slug' => 'priority',
                    'type' => 'radio',
                    'options' => array(
                        array( 'label' => 'Low', 'value' => 'low' ),
                        array( 'label' => 'High', 'value' => 'high' ),
                    ),
                ),
                array(
                    'entry_custom_field_field_7' => array(
                        array( 'value' => 'high' ),
                    ),
                )
            );

            $this->assertStringContainsString( 'value="high"', $output );
            $this->assertStringContainsString( 'checked', $output );
        }

        public function testRenderGroupModalIncludesSavedCustomFieldDataForHydration(): void {
            $GLOBALS['modpress_test_taxonomies'] = array(
                'modpress_language' => array( 'args' => array( 'hierarchical' => true ) ),
            );
            $GLOBALS['modpress_test_terms'] = array(
                'modpress_language' => array(
                    42 => (object) array( 'term_id' => 42, 'name' => 'English' ),
                ),
            );

            $method = new \ReflectionMethod( \ModPress\Admin\Manager\Mods\ModGroups::class, 'render_group_modal' );
            $method->setAccessible( true );

            $instance = \ModPress\Admin\Manager\Mods\ModGroups::class;
            $group_manager = ( new \ReflectionClass( $instance ) )->newInstanceWithoutConstructor();

            ob_start();
            $method->invoke(
                $group_manager,
                array(
                    array( 'id' => 2, 'taxonomy' => 'modpress_language', 'label' => 'Language' ),
                ),
                array(
                    'id' => 7,
                    'group_key' => 'modpress_language',
                    'group_name' => 'Language',
                    'group_slug' => 'modpress_language',
                    'group_singular_name' => 'Language',
                    'group_description' => '',
                    'group_hierarchical' => false,
                    'group_parent' => array(),
                    'group_parent_entry' => array(),
                    'group_fields' => json_encode( array(
                        array(
                            'id' => 'field_7',
                            'name' => 'Priority',
                            'slug' => 'priority',
                            'type' => 'radio',
                            'options' => array(
                                array( 'label' => 'Low', 'value' => 'low' ),
                                array( 'label' => 'High', 'value' => 'high' ),
                            ),
                        ),
                    ) ),
                )
            );
            $output = ob_get_clean();

            $this->assertStringContainsString( 'data-group-fields=', $output );
            $this->assertStringContainsString( 'field_7', $output );
            $this->assertStringContainsString( 'priority', strtolower( $output ) );
        }

        public function testRenderGroupModalKeepsSavedParentSelectionValues(): void {
            $GLOBALS['modpress_test_taxonomies'] = array(
                'modpress_assets' => array( 'args' => array( 'hierarchical' => true ) ),
                'modpress_language' => array( 'args' => array( 'hierarchical' => true ) ),
            );
            $GLOBALS['modpress_test_terms'] = array(
                'modpress_assets' => array(
                    42 => (object) array( 'term_id' => 42, 'name' => 'Scripts' ),
                    43 => (object) array( 'term_id' => 43, 'name' => 'Libraries' ),
                ),
            );

            $method = new \ReflectionMethod( \ModPress\Admin\Manager\Mods\ModGroups::class, 'render_group_modal' );
            $method->setAccessible( true );

            $instance = \ModPress\Admin\Manager\Mods\ModGroups::class;
            $group_manager = ( new \ReflectionClass( $instance ) )->newInstanceWithoutConstructor();

            ob_start();
            $method->invoke(
                $group_manager,
                array(
                    array( 'id' => 2, 'taxonomy' => 'modpress_assets', 'label' => 'Assets' ),
                    array( 'id' => 7, 'taxonomy' => 'modpress_language', 'label' => 'Language' ),
                ),
                array(
                    'id' => 7,
                    'group_key' => 'modpress_language',
                    'group_name' => 'Language',
                    'group_slug' => 'modpress_language',
                    'group_singular_name' => 'Language',
                    'group_description' => '',
                    'group_hierarchical' => false,
                    'group_parent' => array( '2' ),
                    'group_parent_entry' => array( '42' ),
                )
            );
            $output = ob_get_clean();

            $this->assertStringContainsString( 'value="2"', $output );
            $this->assertStringContainsString( 'selected', $output );
        }

        public function testRenderGroupModalSelectsSavedParentGroupWhenStoredAsTaxonomyKey(): void {
            $GLOBALS['wpdb'] = new class {
                public string $prefix = 'wp_';
                public array $storage = [
                    'wp_modpress_groups' => [
                        2 => [
                            'id' => 2,
                            'group_key' => 'modpress_assets',
                            'group_slug' => 'assets',
                            'group_name' => 'Assets',
                            'group_singular_name' => 'Asset',
                            'group_description' => 'Asset group.',
                            'group_hierarchical' => 1,
                        ],
                    ],
                ];

                public function prepare( string $query, ...$args ): string {
                    return $query;
                }

                public function get_row( string $query, $output = null ) {
                    foreach ( (array) ( $this->storage['wp_modpress_groups'] ?? array() ) as $row ) {
                        if ( (string) ( $row['group_key'] ?? '' ) === 'modpress_assets' || (string) ( $row['group_slug'] ?? '' ) === 'assets' ) {
                            return $row;
                        }
                    }

                    return null;
                }
            };

            $method = new \ReflectionMethod( \ModPress\Admin\Manager\Mods\ModGroups::class, 'render_group_modal' );
            $method->setAccessible( true );

            $instance = \ModPress\Admin\Manager\Mods\ModGroups::class;
            $group_manager = ( new \ReflectionClass( $instance ) )->newInstanceWithoutConstructor();

            ob_start();
            $method->invoke(
                $group_manager,
                array(
                    array( 'id' => 2, 'taxonomy' => 'modpress_assets', 'label' => 'Assets' ),
                    array( 'id' => 7, 'taxonomy' => 'modpress_language', 'label' => 'Language' ),
                ),
                array(
                    'id' => 7,
                    'group_key' => 'modpress_language',
                    'group_name' => 'Language',
                    'group_slug' => 'modpress_language',
                    'group_singular_name' => 'Language',
                    'group_description' => '',
                    'group_hierarchical' => false,
                    'group_parent' => array( 'modpress_assets' ),
                    'group_parent_entry' => array( '42' ),
                )
            );
            $output = ob_get_clean();

            $this->assertMatchesRegularExpression( '/name="group_parent\\[\\]"[^>]*>.*?value="2"[^>]*selected/s', $output );
        }

        public function testGroupParentSaveUsesNumericGroupIds(): void {
            $GLOBALS['wpdb'] = new class {
                public string $prefix = 'wp_';
                public array $storage = [
                    'wp_modpress_groups' => [
                        2 => [
                            'id' => 2,
                            'group_key' => 'modpress_assets',
                            'group_slug' => 'assets',
                            'group_name' => 'Assets',
                            'group_singular_name' => 'Asset',
                            'group_description' => 'Asset group.',
                            'group_hierarchical' => 1,
                        ],
                    ],
                ];

                public function prepare( string $query, ...$args ): string {
                    return $query;
                }

                public function get_row( string $query, $output = null ) {
                    if ( preg_match( '/group_key.*?=\s*\'([^\']+)\'/i', $query, $matches ) ) {
                        $value = $matches[1];
                        foreach ( (array) ( $this->storage['wp_modpress_groups'] ?? array() ) as $row ) {
                            if ( (string) ( $row['group_key'] ?? '' ) === (string) $value ) {
                                return $row;
                            }
                        }
                    }

                    if ( preg_match( '/group_slug.*?=\s*\'([^\']+)\'/i', $query, $matches ) ) {
                        $value = $matches[1];
                        foreach ( (array) ( $this->storage['wp_modpress_groups'] ?? array() ) as $row ) {
                            if ( (string) ( $row['group_slug'] ?? '' ) === (string) $value ) {
                                return $row;
                            }
                        }
                    }

                    return null;
                }
            };

            $_POST = array(
                'group_name' => 'Language',
                'group_slug' => 'modpress_language',
                'group_singular_name' => 'Language',
                'group_description' => 'Language group description.',
                'group_hierarchical' => '1',
                'group_parent' => array( 'modpress_assets' ),
                'group_parent_entry' => array( 42, 43 ),
            );

            $method = new \ReflectionMethod( \ModPress\Includes\Functions\Admin\FunctionsMod::class, 'build_group_payload' );
            $method->setAccessible( true );

            $payload = $method->invoke( new \ModPress\Includes\Functions\Admin\FunctionsMod(), 'Language', 'modpress_language' );

            $this->assertSame( 'a:1:{i:0;i:2;}', $payload['group_parent'] );
            $this->assertSame( 'a:2:{i:0;i:42;i:1;i:43;}', $payload['group_parent_entry'] );
        }

        public function testGroupVisibilityRulesMatchSavedParentGroupAndTermValues(): void {
            $GLOBALS['modpress_test_db_groups'] = array(
                array(
                    'id' => 1,
                    'group_key' => 'modpress_type',
                    'group_slug' => 'type',
                    'group_name' => 'Mod Type',
                    'group_singular_name' => 'Mod Type',
                    'group_parent' => array(),
                    'group_parent_entry' => array(),
                ),
                array(
                    'id' => 2,
                    'group_key' => 'modpress_assets',
                    'group_slug' => 'assets',
                    'group_name' => 'Asset Types',
                    'group_singular_name' => 'Asset Type',
                    'group_parent' => array( 1 ),
                    'group_parent_entry' => array( 9 ),
                ),
                array(
                    'id' => 3,
                    'group_key' => 'modpress_language',
                    'group_slug' => 'language',
                    'group_name' => 'Languages',
                    'group_singular_name' => 'Language',
                    'group_parent' => array( 2 ),
                    'group_parent_entry' => array( 42, 43 ),
                ),
            );

            $definitions = ( new \ModPress\Includes\ModManagement\GroupsManager() )->get_group_definitions();

            $this->assertSame( array( '1' ), $definitions['modpress_assets']['group_parent'] );
            $this->assertSame( array( '9' ), $definitions['modpress_assets']['group_parent_entry'] );
            $this->assertTrue( \ModPress\Includes\ModManagement\GroupsManager::should_show_group( 'modpress_assets', array( 'mod_type' => '9' ), $definitions ) );
            $this->assertFalse( \ModPress\Includes\ModManagement\GroupsManager::should_show_group( 'modpress_language', array( 'modpress_assets' => '99' ), $definitions ) );
            $this->assertTrue( \ModPress\Includes\ModManagement\GroupsManager::should_show_group( 'modpress_language', array( 'modpress_assets' => '42' ), $definitions ) );
        }
    }
}
