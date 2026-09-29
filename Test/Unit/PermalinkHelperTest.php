<?php

declare( strict_types=1 );

namespace ModPress\Tests\Unit {

    use ModPress\Includes\Functions\Helpers\PermalinkHelper;
    use PHPUnit\Framework\TestCase;

    final class PermalinkHelperTest extends TestCase {
        public function testCustomAliasesAreNormalizedForReusablePatterns(): void {
            $aliases = array(
                '%wiki%'          => '%object%',
                '%wiki_category%' => '%object_category%',
                '%wiki_tag%'      => '%object_tag%',
                '%wiki_page%'     => '%object_slug%',
            );

            $pattern = '%root%/%wiki_category%/%wiki_tag%/%wiki_page%';

            $this->assertSame( '%root%/%object_category%/%object_tag%/%object_slug%', PermalinkHelper::sanitize_pattern( $pattern, $aliases ) );
        }

        public function testUnknownTokensRemainAvailableForPluginSpecificDefinitions(): void {
            $pattern = '%custom_root%/%custom_category%/%custom_page%';

            $this->assertSame( '%custom_root%/%custom_category%/%custom_page%', PermalinkHelper::sanitize_pattern( $pattern ) );
        }
    }
}
