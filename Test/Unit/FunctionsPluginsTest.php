<?php

declare( strict_types=1 );

namespace {
    if ( ! function_exists( 'wp_unslash' ) ) {
        function wp_unslash( $value ) {
            return is_string( $value ) ? stripslashes( $value ) : $value;
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

    if ( ! function_exists( 'wp_verify_nonce' ) ) {
        function wp_verify_nonce( $nonce, $action ) {
            return true;
        }
    }

    if ( ! function_exists( 'wp_send_json_success' ) ) {
        function wp_send_json_success( $data = null, $status_code = 200 ) {
            $GLOBALS['modpress_test_json_response'] = [
                'success' => true,
                'data' => $data,
                'status_code' => $status_code,
            ];
            return $data;
        }
    }

    if ( ! function_exists( 'wp_send_json_error' ) ) {
        function wp_send_json_error( $data = null, $status_code = 400 ) {
            $GLOBALS['modpress_test_json_response'] = [
                'success' => false,
                'data' => $data,
                'status_code' => $status_code,
            ];
            return $data;
        }
    }

    if ( ! function_exists( 'current_user_can' ) ) {
        function current_user_can( $capability ): bool {
            return true;
        }
    }

    if ( ! function_exists( 'maybe_serialize' ) ) {
        function maybe_serialize( $data ) {
            return serialize( $data );
        }
    }

    if ( ! function_exists( 'maybe_unserialize' ) ) {
        function maybe_unserialize( $data ) {
            return is_string( $data ) ? @unserialize( $data ) : $data;
        }
    }

    if ( ! function_exists( 'current_time' ) ) {
        function current_time( $type = 'mysql' ) {
            return '2024-01-01 00:00:00';
        }
    }

    if ( ! class_exists( 'wpdb' ) ) {
        class wpdb {
            public $prefix = 'wp_';

            public function prepare( $query, ...$args ) {
                return $query;
            }

            public function replace( $table, $data, $format = null ) {
                $GLOBALS['modpress_settings_store'][ $table ] = $data;
                return true;
            }

            public function get_var( $query ) {
                return null;
            }

            public function delete( $table, $where, $format = null ) {
                return true;
            }
        }
    }
}

namespace ModPress\Tests\Unit {
    use ModPress\Includes\Functions\Admin\FunctionsPlugins;
    use ModPress\Includes\Plugins\PluginInterface;
    use ModPress\Includes\Plugins\Plugins;
    use ModPress\Includes\Plugins\SettingsPageProviderInterface;
    use PHPUnit\Framework\TestCase;

    final class FunctionsPluginsTest extends TestCase {
        protected function setUp(): void {
            parent::setUp();

            $_POST = [];
            $GLOBALS['modpress_test_json_response'] = null;
            $GLOBALS['modpress_settings_store'] = [];
            $GLOBALS['wpdb'] = new \wpdb();
        }

        public function testSavePluginSettingsPersistsToModPressSettingsTable(): void {
            $plugin = new class implements PluginInterface, SettingsPageProviderInterface {
                public function get_slug(): string {
                    return 'example-plugin';
                }

                public function get_name(): string {
                    return 'Example Plugin';
                }

                public function get_version(): string {
                    return '1.0.0';
                }

                public function get_author(): string {
                    return 'ModPress';
                }

                public function get_author_uri(): string {
                    return 'https://example.com';
                }

                public function get_description(): string {
                    return 'Example Plugin';
                }

                public function get_uri(): string {
                    return 'https://example.com';
                }

                public function get_license(): string {
                    return 'GPL-2.0';
                }

                public function is_active(): bool {
                    return true;
                }

                public function init(): void {
                }

                public function get_settings_page(): array {
                    return [
                        'slug' => 'example',
                        'label' => 'Example Plugin',
                        'title' => 'Example Plugin settings',
                        'fields' => [
                            [ 'key' => 'enabled' ],
                        ],
                    ];
                }

                public function sanitize_settings( $input ): array {
                    return is_array( $input ) ? $input : [];
                }
            };

            Plugins::register_plugin( $plugin );
            $_POST = [
                'slug' => 'example-plugin',
                'settings' => [
                    'enabled' => true,
                    'label' => 'Alpha',
                ],
                'nonce' => 'valid-nonce',
            ];

            ( new FunctionsPlugins() )->save_plugin_settings();

            $table = \ModPress\Includes\Core\WP\Database::table_name( 'settings' );
            $this->assertArrayHasKey( $table, $GLOBALS['modpress_settings_store'] );
            $this->assertSame( 'modpress_example', $GLOBALS['modpress_settings_store'][ $table ]['setting_group'] );
            $this->assertSame(
                [ 'enabled' => true, 'label' => 'Alpha' ],
                maybe_unserialize( $GLOBALS['modpress_settings_store'][ $table ]['setting_value'] )
            );
        }
    }
}
