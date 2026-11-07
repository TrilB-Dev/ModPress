<?php

declare( strict_types=1 );

namespace {

	use ModPress\Includes\Plugins\FontAwesome\Includes\Functions\Helpers\FAHelper;
	use PHPUnit\Framework\TestCase;

	final class FAHelperTest extends TestCase {
		public function test_faicon_renders_direct_font_awesome_markup(): void {
			$html = FAHelper::faicon( 'trash', array(
				'class' => 'btn-icon',
				'title' => 'Delete',
			) );

			$this->assertStringContainsString( '<i', $html );
			$this->assertStringContainsString( 'fa-solid', $html );
			$this->assertStringContainsString( 'fa-trash', $html );
			$this->assertStringContainsString( 'btn-icon', $html );
			$this->assertStringContainsString( 'title="Delete"', $html );
			$this->assertStringNotContainsString( '<svg', $html );
		}

		public function test_button_keeps_inline_icon_markup_when_label_has_text(): void {
			$html = \ModPress\Includes\Functions\Helpers\FormFieldHelper::button(
				FAHelper::faicon( 'trash', array( 'class' => 'fa-svg-symbol' ) ) . ' Delete',
				array(
					'type' => 'button',
					'class' => 'btn btn-outline-danger',
				)
			);

			$this->assertStringContainsString( 'fa-trash', $html );
			$this->assertStringContainsString( 'Delete', $html );
			$this->assertStringContainsString( 'fa-svg-symbol', $html );
			$this->assertStringNotContainsString( '<svg', $html );
		}

		public function test_faicon_keeps_icon_name_when_extra_svg_class_is_passed(): void {
			$html = FAHelper::faicon( 'trash', array( 'class' => 'fa-svg-symbol' ) );

			$this->assertStringContainsString( 'fa-trash', $html );
			$this->assertStringContainsString( 'fa-svg-symbol', $html );
			$this->assertSame( 1, substr_count( $html, 'fa-trash' ) );
		}
	}
}
