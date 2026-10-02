<?php

declare( strict_types=1 );

namespace ModPress\Tests\Unit;

use ModPress\Includes\Core\PostType;
use ModPress\Includes\Core\Taxonomy;
use PHPUnit\Framework\TestCase;

final class LegacyTypeCompatibilityTest extends TestCase {
    public function testCanonicalSlugsAreRegisteredThroughTheCoreRegistry(): void {
        PostType::create( 'modpress_mod', array( 'public' => false, 'show_ui' => false ) );
        Taxonomy::create( 'modpress_mod_group', array( 'object_type' => array( 'modpress_mod' ) ) );

        $this->assertContains( 'modpress_mod', PostType::get_post_type_names() );
        $this->assertContains( 'modpress_mod_group', Taxonomy::get_taxonomy_names() );
    }

    public function testDuplicatePostTypeRegistrationsAreRejectedByTheCoreRegistry(): void {
        $this->assertTrue( PostType::create( 'modpress_duplicate', array( 'public' => false, 'show_ui' => false ) ) );
        $this->assertFalse( PostType::create( 'modpress_duplicate', array( 'public' => false, 'show_ui' => false ) ) );
    }
}
