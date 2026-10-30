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

    if ( ! function_exists( '__' ) ) {
        function __( $text, $domain = null ): string {
            return (string) $text;
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
            return $GLOBALS['modpress_test_can_create'] ?? false;
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
            $values = $GLOBALS['modpress_test_term_meta'][ (int) $term_id ][ $key ] ?? [];
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
    use ModPress\Includes\Settings\SettingsManager;
    use PHPUnit\Framework\TestCase;

    final class FunctionsModTest extends TestCase {
        protected function setUp(): void {
            parent::setUp();
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = [];
            $GLOBALS['modpress_test_can_create'] = true;
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

        public function testFontAwesomeRuntimePrefersWordPressOfficialPluginByDefault(): void {
            $this->assertFalse( \ModPress\Includes\Plugins\FontAwesome\FontAwesome::is_wordpress_fontawesome_active() );
            \ModPress\Includes\Plugins\FontAwesome\FontAwesome::ensure_vendor_initialized();
            $this->assertFalse( \ModPress\Includes\Plugins\FontAwesome\FontAwesome::is_wordpress_fontawesome_active() );
        }

        public function testFontAwesomePluginSettingsGroupIsNormalizedToUnderscoreStorage(): void {
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

            $this->assertStringContainsString( 'name="entry_custom_field_field_3_mode"', $output );
            $this->assertStringContainsString( 'name="entry_custom_field_field_3"', $output );
            $this->assertStringNotContainsString( 'name="entry_custom_field_image_mode"', $output );
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
    }
}
