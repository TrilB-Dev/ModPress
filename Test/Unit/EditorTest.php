<?php

declare( strict_types=1 );

namespace {
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

    if ( ! function_exists( 'apply_filters' ) ) {
        function apply_filters( $hook, $value ) {
            return $value;
        }
    }
}

namespace ModPress\Tests\Unit {
    use ModPress\Includes\Core\Editor;
    use PHPUnit\Framework\TestCase;

    final class EditorTest extends TestCase {
        public function testCreateRegistersDynamicEditorDefinitions(): void {
            $this->assertTrue( Editor::create( 'modpress_primary_editor', array(
                'label' => 'Primary Editor',
                'description' => 'Main editor',
                'supports' => array( 'title', 'content' ),
            ) ) );

            $this->assertArrayHasKey( 'modpress_primary_editor', Editor::definitions() );
            $this->assertSame( 'Primary Editor', Editor::definitions()['modpress_primary_editor']['label'] );
        }

        public function testDynamicallyCreateNormalizesSlugAndConfig(): void {
            $result = Editor::dynamically_create( array(
                'slug' => 'Primary Editor',
                'label' => 'Primary Editor',
                'supports' => array( 'title', 'content' ),
            ) );

            $this->assertTrue( $result );
            $this->assertArrayHasKey( 'primary_editor', Editor::definitions() );
            $this->assertSame( '', Editor::definitions()['primary_editor']['post_type'] );
            $this->assertSame( array( 'title', 'content' ), Editor::definitions()['primary_editor']['supports'] );
        }

        public function testDynamicallyCreateAcceptsMultiplePostTypes(): void {
            $result = Editor::dynamically_create( array(
                'slug' => 'mods_editor',
                'label' => 'Mods Editor',
                'post_type' => 'modpress_mod',
                'supports' => array( 'title', 'content' ),
            ) );

            $this->assertTrue( $result );
            $this->assertSame( 'modpress_mod', Editor::definitions()['mods_editor']['post_type'] );
        }
    }
}
