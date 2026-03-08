<?php

namespace AchttienVijftien\WpContentImporter\Tests\Unit\Mapping;

use AchttienVijftien\WpContentImporter\Mapping\ModifierPipeline;
use AchttienVijftien\WpContentImporter\Mapping\ModifierRegistry;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class ModifierPipelineTest extends TestCase {

	private ModifierPipeline $pipeline;

	protected function set_up(): void {
		parent::set_up();
		$registry = new ModifierRegistry();
		$registry->register_defaults();
		$this->pipeline = new ModifierPipeline( $registry );
	}

	public function test_plain_column_without_modifiers(): void {
		$row = [ 'name' => 'Jan' ];

		$this->assertSame( 'Jan', $this->pipeline->process( 'name', $row ) );
	}

	public function test_single_modifier(): void {
		$row = [ 'name' => 'jan' ];

		$this->assertSame( 'JAN', $this->pipeline->process( 'name|upper', $row ) );
	}

	public function test_chained_modifiers(): void {
		$row = [ 'name' => '  jan  ' ];

		$this->assertSame( 'JAN', $this->pipeline->process( 'name|trim|upper', $row ) );
	}

	public function test_modifier_with_static_arg(): void {
		$row = [ 'date' => '15-01-2024' ];

		$this->assertSame( '2024-01-15', $this->pipeline->process( "date|date('Y-m-d')", $row ) );
	}

	public function test_modifier_with_multiple_static_args(): void {
		$row = [ 'text' => 'hello world' ];

		$this->assertSame( 'hello planet', $this->pipeline->process( "text|replace('world', 'planet')", $row ) );
	}

	public function test_modifier_with_column_ref_arg(): void {
		$row = [ 'text' => 'hello world', 'replacement' => 'planet' ];

		$this->assertSame( 'hello planet', $this->pipeline->process( "text|replace('world', replacement)", $row ) );
	}

	public function test_modifier_with_bare_word_falls_back_to_string(): void {
		$row = [ 'text' => 'hello world' ];

		// 'nonexistent' is not a column, so it falls back to the literal string 'nonexistent'.
		$this->assertSame( 'hello nonexistent', $this->pipeline->process( "text|replace('world', nonexistent)", $row ) );
	}

	public function test_unknown_modifier_passes_value_through(): void {
		$row = [ 'name' => 'jan' ];

		$this->assertSame( 'jan', $this->pipeline->process( 'name|nonexistent_modifier', $row ) );
	}

	public function test_empty_column_value_still_runs_modifiers(): void {
		$row = [ 'name' => '   ' ];

		$this->assertSame( '', $this->pipeline->process( 'name|trim', $row ) );
	}

	public function test_missing_column_returns_empty_string(): void {
		$row = [ 'other' => 'value' ];

		$this->assertSame( '', $this->pipeline->process( 'name|upper', $row ) );
	}

	public function test_number_format_modifier(): void {
		$row = [ 'price' => '1234.5' ];

		$this->assertSame( '1234.50', $this->pipeline->process( "price|number_format(2)", $row ) );
	}

	public function test_truncate_modifier(): void {
		$row = [ 'bio' => 'This is a very long biography text' ];

		$this->assertSame( 'This is a ...', $this->pipeline->process( "bio|truncate(10)", $row ) );
	}

	public function test_capitalize_modifier(): void {
		$row = [ 'name' => 'jan' ];

		$this->assertSame( 'Jan', $this->pipeline->process( 'name|capitalize', $row ) );
	}

	public function test_striptags_modifier(): void {
		$row = [ 'html' => '<p>Hello <b>world</b></p>' ];

		$this->assertSame( 'Hello world', $this->pipeline->process( 'html|striptags', $row ) );
	}

	public function test_slug_modifier(): void {
		$row = [ 'title' => 'Hello World!' ];

		$this->assertSame( 'hello-world', $this->pipeline->process( 'title|slug', $row ) );
	}

	// --- Built-in modifier edge cases ---

	public function test_upper_empty_string(): void {
		$row = [ 'v' => '' ];

		$this->assertSame( '', $this->pipeline->process( 'v|upper', $row ) );
	}

	public function test_upper_multibyte(): void {
		$row = [ 'v' => 'café' ];

		$this->assertSame( 'CAFÉ', $this->pipeline->process( 'v|upper', $row ) );
	}

	public function test_lower_modifier(): void {
		$row = [ 'v' => 'HELLO' ];

		$this->assertSame( 'hello', $this->pipeline->process( 'v|lower', $row ) );
	}

	public function test_lower_empty_string(): void {
		$row = [ 'v' => '' ];

		$this->assertSame( '', $this->pipeline->process( 'v|lower', $row ) );
	}

	public function test_lower_multibyte(): void {
		$row = [ 'v' => 'CAFÉ' ];

		$this->assertSame( 'café', $this->pipeline->process( 'v|lower', $row ) );
	}

	public function test_capitalize_already_capitalized(): void {
		$row = [ 'v' => 'Jan' ];

		$this->assertSame( 'Jan', $this->pipeline->process( 'v|capitalize', $row ) );
	}

	public function test_capitalize_empty_string(): void {
		$row = [ 'v' => '' ];

		$this->assertSame( '', $this->pipeline->process( 'v|capitalize', $row ) );
	}

	public function test_capitalize_multibyte(): void {
		$row = [ 'v' => 'über' ];

		$this->assertSame( 'Über', $this->pipeline->process( 'v|capitalize', $row ) );
	}

	public function test_trim_no_whitespace(): void {
		$row = [ 'v' => 'hello' ];

		$this->assertSame( 'hello', $this->pipeline->process( 'v|trim', $row ) );
	}

	public function test_trim_empty_string(): void {
		$row = [ 'v' => '' ];

		$this->assertSame( '', $this->pipeline->process( 'v|trim', $row ) );
	}

	public function test_slug_special_characters(): void {
		$row = [ 'v' => 'Héllo & Wörld! (2024)' ];

		$result = $this->pipeline->process( 'v|slug', $row );

		$this->assertMatchesRegularExpression( '/^[a-z0-9\-]+$/', $result );
	}

	public function test_slug_empty_string(): void {
		$row = [ 'v' => '' ];

		$this->assertSame( '', $this->pipeline->process( 'v|slug', $row ) );
	}

	public function test_date_default_format(): void {
		$row = [ 'v' => '15 January 2024' ];

		$this->assertSame( '2024-01-15', $this->pipeline->process( 'v|date', $row ) );
	}

	public function test_date_custom_format(): void {
		$row = [ 'v' => '2024-01-15' ];

		$this->assertSame( '15/01/2024', $this->pipeline->process( "v|date('d/m/Y')", $row ) );
	}

	public function test_date_invalid_string_returns_original(): void {
		$row = [ 'v' => 'not a date' ];

		$this->assertSame( 'not a date', $this->pipeline->process( "v|date('Y-m-d')", $row ) );
	}

	public function test_date_empty_string(): void {
		$row = [ 'v' => '' ];

		$this->assertSame( '', $this->pipeline->process( "v|date('Y-m-d')", $row ) );
	}

	public function test_number_format_no_args_defaults_to_zero_decimals(): void {
		$row = [ 'v' => '1234.567' ];

		$this->assertSame( '1235', $this->pipeline->process( 'v|number_format', $row ) );
	}

	public function test_number_format_with_decimal_separator(): void {
		$row = [ 'v' => '1234.5' ];

		$this->assertSame( '1234,50', $this->pipeline->process( "v|number_format(2, ',')", $row ) );
	}

	public function test_number_format_with_thousands_separator(): void {
		$row = [ 'v' => '1234567.89' ];

		$this->assertSame( '1.234.567,89', $this->pipeline->process( "v|number_format(2, ',', '.')", $row ) );
	}

	public function test_number_format_non_numeric_returns_original(): void {
		$row = [ 'v' => 'abc' ];

		$this->assertSame( 'abc', $this->pipeline->process( "v|number_format(2)", $row ) );
	}

	public function test_number_format_empty_string(): void {
		$row = [ 'v' => '' ];

		$this->assertSame( '', $this->pipeline->process( "v|number_format(2)", $row ) );
	}

	public function test_replace_empty_search(): void {
		$row = [ 'v' => 'hello' ];

		$this->assertSame( 'hello', $this->pipeline->process( "v|replace('', 'x')", $row ) );
	}

	public function test_replace_no_match(): void {
		$row = [ 'v' => 'hello' ];

		$this->assertSame( 'hello', $this->pipeline->process( "v|replace('xyz', 'abc')", $row ) );
	}

	public function test_replace_empty_replacement(): void {
		$row = [ 'v' => 'hello world' ];

		$this->assertSame( 'hello ', $this->pipeline->process( "v|replace('world', '')", $row ) );
	}

	public function test_truncate_shorter_than_limit(): void {
		$row = [ 'v' => 'short' ];

		$this->assertSame( 'short', $this->pipeline->process( "v|truncate(100)", $row ) );
	}

	public function test_truncate_exact_length(): void {
		$row = [ 'v' => 'exact' ];

		$this->assertSame( 'exact', $this->pipeline->process( "v|truncate(5)", $row ) );
	}

	public function test_truncate_empty_string(): void {
		$row = [ 'v' => '' ];

		$this->assertSame( '', $this->pipeline->process( "v|truncate(10)", $row ) );
	}

	public function test_striptags_no_tags(): void {
		$row = [ 'v' => 'plain text' ];

		$this->assertSame( 'plain text', $this->pipeline->process( 'v|striptags', $row ) );
	}

	public function test_striptags_empty_string(): void {
		$row = [ 'v' => '' ];

		$this->assertSame( '', $this->pipeline->process( 'v|striptags', $row ) );
	}
}
