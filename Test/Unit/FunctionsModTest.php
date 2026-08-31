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

    if ( ! function_exists( 'check_admin_referer' ) ) {
        function check_admin_referer( $action, $query_arg ): bool {
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
}

namespace ModPress\Tests\Unit {
    use ModPress\Includes\Functions\Admin\FunctionsMod;
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
    }
}
