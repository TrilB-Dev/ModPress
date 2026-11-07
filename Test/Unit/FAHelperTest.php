<?php

declare( strict_types=1 );

namespace {

	use ModPress\Includes\Plugins\FontAwesome\Includes\Functions\Helpers\FAHelper;
	use PHPUnit\Framework\TestCase;

	final class FAHelperTest extends TestCase {
		public function test_symbol_renders_icon_marker_and_symbol_use_renders_svg_use_reference(): void {
			$symbol = FAHelper::symbol( 'modpress-trash-symbol-test', 'trash', array( 'title' => 'Delete' ) );
			$this->assertStringContainsString( 'data-fa-symbol="modpress-trash-symbol-test"', $symbol );
			$this->assertStringContainsString( 'fa-solid', $symbol );
			$this->assertStringContainsString( 'fa-trash', $symbol );
			$this->assertStringContainsString( 'title="Delete"', $symbol );

			$html = FAHelper::symbol_use( 'modpress-trash-symbol-test', array( 'class' => 'btn-icon' ) );

			$this->assertStringContainsString( '<svg', $html );
			$this->assertStringContainsString( 'href="#modpress-trash-symbol-test"', $html );
			$this->assertStringContainsString( 'btn-icon', $html );
			$this->assertStringNotContainsString( '<i class=', $html );
		}

		public function test_button_keeps_inline_icon_markup_when_label_has_text(): void {
			FAHelper::symbol( 'modpress-trash-button-test', 'trash' );

			$html = \ModPress\Includes\Functions\Helpers\FormFieldHelper::button(
				FAHelper::symbol_use( 'modpress-trash-button-test', array( 'class' => 'fa-svg-symbol' ) ) . ' Delete',
				array(
					'type' => 'button',
					'class' => 'btn btn-outline-danger',
				)
			);

			$this->assertStringContainsString( 'href="#modpress-trash-button-test"', $html );
			$this->assertStringContainsString( 'Delete', $html );
		}
	}
}
