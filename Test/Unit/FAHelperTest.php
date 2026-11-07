<?php

declare( strict_types=1 );

namespace {

	use ModPress\Includes\Plugins\FontAwesome\Includes\Functions\Helpers\FAHelper;
	use PHPUnit\Framework\TestCase;

	final class FAHelperTest extends TestCase {
		public function test_symbol_use_renders_a_visible_font_awesome_icon_for_registered_symbols(): void {
			FAHelper::symbol( 'modpress-trash', 'trash' );

			$html = FAHelper::symbol_use( 'modpress-trash', array( 'class' => 'btn-icon' ) );

			$this->assertStringContainsString( 'fa-solid', $html );
			$this->assertStringContainsString( 'fa-trash', $html );
			$this->assertStringContainsString( 'btn-icon', $html );
			$this->assertStringNotContainsString( '<use href="#modpress-trash"', $html );
		}
	}
}
