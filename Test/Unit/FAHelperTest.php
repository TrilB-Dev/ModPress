<?php

declare( strict_types=1 );

namespace {

	use ModPress\Includes\Plugins\FontAwesome\Includes\Functions\Helpers\FAHelper;
	use PHPUnit\Framework\TestCase;

	final class FAHelperTest extends TestCase {
		public function test_symbol_use_renders_svg_use_markup_for_registered_symbols(): void {
			FAHelper::symbol( 'modpress-trash', 'trash' );

			$html = FAHelper::symbol_use( 'modpress-trash', array( 'class' => 'btn-icon' ) );

			$this->assertStringContainsString( '<svg', $html );
			$this->assertStringContainsString( 'href="#modpress-trash"', $html );
			$this->assertStringContainsString( 'btn-icon', $html );
			$this->assertStringNotContainsString( '<i class=', $html );
		}

		public function test_button_keeps_inline_icon_markup_when_label_has_text(): void {
			FAHelper::symbol( 'modpress-trash', 'trash' );

			$html = \ModPress\Includes\Functions\Helpers\FormFieldHelper::button(
				FAHelper::symbol_use( 'modpress-trash', array( 'class' => 'fa-svg-symbol' ) ) . ' Delete',
				array(
					'type' => 'button',
					'class' => 'btn btn-outline-danger',
				)
			);

			$this->assertStringContainsString( 'fa-trash', $html );
			$this->assertStringContainsString( 'Delete', $html );
		}
	}
}
