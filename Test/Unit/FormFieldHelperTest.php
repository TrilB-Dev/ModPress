<?php

declare( strict_types=1 );

namespace ModPress\Test\Unit;

use ModPress\Includes\Functions\Helpers\FormFieldHelper;
use PHPUnit\Framework\TestCase;

final class FormFieldHelperTest extends TestCase {
	public function test_bootstrap_select_renders_selected_value_for_custom_field_types(): void {
		$html = FormFieldHelper::bootstrap_select(
			'custom_field_type[]',
			array(
				'data' => array(
					'text' => 'Text',
					'textarea' => 'Text Area',
					'select' => 'Select',
				),
				'selected' => 'text',
				'attributes' => array(
					'data-field-type' => 'custom_field_type',
				),
			)
		);

		$this->assertStringContainsString( 'name="custom_field_type[]"', $html );
		$this->assertStringContainsString( 'data-field-type="custom_field_type"', $html );
		$this->assertStringContainsString( 'value="text"', $html );
		$this->assertStringContainsString( 'selected', $html );
	}
}
