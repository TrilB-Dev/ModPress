<?php

declare( strict_types=1 );

namespace {
    if ( ! function_exists( 'sanitize_key' ) ) {
        function sanitize_key( $key ): string {
            return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );
        }
    }

    if ( ! function_exists( 'register_nav_menus' ) ) {
        function register_nav_menus( $locations = array() ) {
            $GLOBALS['modpress_test_nav_menus'] = array_merge( $GLOBALS['modpress_test_nav_menus'] ?? array(), (array) $locations );
            return true;
        }
    }
}

namespace ModPress\Tests\Unit {
    use ModPress\Includes\Core\Menus;
    use PHPUnit\Framework\TestCase;

    final class MenusTest extends TestCase {
        protected function setUp(): void {
            parent::setUp();
            $GLOBALS['modpress_test_nav_menus'] = array();
        }

        public function testDuplicateMenuLocationsAreNotRegisteredTwice(): void {
            $registry = Menus::create(
                array(
                    'primary' => 'Primary Navigation',
                )
            );

            $this->assertTrue( $registry->register_location( 'primary', 'Primary Navigation' ) instanceof Menus );
            $this->assertCount( 1, $GLOBALS['modpress_test_nav_menus'] );
        }

        public function testDynamicMenuCanBeCreatedFromUiPayload(): void {
            $this->assertTrue( Menus::dynamically_create(
                array(
                    'location' => 'footer-menu',
                    'label'    => 'Footer Menu',
                )
            ) );

            $this->assertArrayHasKey( 'footer-menu', $GLOBALS['modpress_test_nav_menus'] );
        }
    }
}
