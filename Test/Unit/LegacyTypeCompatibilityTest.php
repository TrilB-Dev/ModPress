<?php

declare( strict_types=1 );

namespace ModPress\Tests\Unit;

use ModPress\Includes\Core\PostType;
use ModPress\Includes\Core\Taxonomy;
use PHPUnit\Framework\TestCase;

final class LegacyTypeCompatibilityTest extends TestCase {
    public function testPostTypeCompatibilityConstantsExist(): void {
        $this->assertSame( 'modpress_mod', PostType::MOD );
        $this->assertSame( 'modpress_page', PostType::PAGE );
        $this->assertSame( 'modpress_page', PostType::MODPRESS );
        $this->assertSame( 'modpress_wiki', PostType::WIKI );
    }

    public function testTaxonomyCompatibilityConstantsExist(): void {
        $this->assertSame( 'modpress_mod_group', Taxonomy::CATEGORY );
        $this->assertSame( 'modpress_mod_tag', Taxonomy::TAG );
    }
}
