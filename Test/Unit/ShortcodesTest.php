<?php

declare( strict_types=1 );

namespace {
    if ( ! function_exists( 'add_shortcode' ) ) {
        function add_shortcode( string $tag, callable $callback ): bool {
            $GLOBALS['modpress_test_shortcodes'][ $tag ] = $callback;
            return true;
        }
    }

    if ( ! function_exists( 'remove_shortcode' ) ) {
        function remove_shortcode( string $tag ): bool {
            if ( isset( $GLOBALS['modpress_test_shortcodes'][ $tag ] ) ) {
                unset( $GLOBALS['modpress_test_shortcodes'][ $tag ] );
                return true;
            }

            return false;
        }
    }

    if ( ! function_exists( 'shortcode_exists' ) ) {
        function shortcode_exists( string $tag ): bool {
            return isset( $GLOBALS['modpress_test_shortcodes'][ $tag ] );
        }
    }
}

namespace ModPress\Tests\Unit {
    use ModPress\Includes\Core\Shortcodes;
    use PHPUnit\Framework\TestCase;

    final class ShortcodesTest extends TestCase {
        protected function setUp(): void {
            parent::setUp();
            $GLOBALS['modpress_test_shortcodes'] = array();
        }

        public function testDuplicateShortcodesAreNotRegisteredTwice(): void {
            $callback = static function ( array $attributes = array(), $content = null, string $tag = '' ): string {
                return 'ok';
            };

            $this->assertTrue( Shortcodes::create( 'modpress_alert', array( 'callback' => $callback ) ) );
            $this->assertFalse( Shortcodes::create( 'modpress_alert', array( 'callback' => $callback ) ) );
            $this->assertCount( 1, $GLOBALS['modpress_test_shortcodes'] );
        }
    }
}
