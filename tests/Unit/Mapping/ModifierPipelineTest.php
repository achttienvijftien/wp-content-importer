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

	// --- If modifier tests ---

	public function test_if_modifier_match_returns_then_value(): void {
		$row = [ 'status' => 'gepubliceerd' ];

		$this->assertSame( 'publish', $this->pipeline->process( "status|if('gepubliceerd', 'publish', 'draft')", $row ) );
	}

	public function test_if_modifier_no_match_returns_else_value(): void {
		$row = [ 'status' => 'concept' ];

		$this->assertSame( 'draft', $this->pipeline->process( "status|if('gepubliceerd', 'publish', 'draft')", $row ) );
	}

	public function test_if_modifier_no_else_returns_original_value(): void {
		$row = [ 'status' => 'concept' ];

		$this->assertSame( 'concept', $this->pipeline->process( "status|if('gepubliceerd', 'publish')", $row ) );
	}

	public function test_if_modifier_no_else_match_returns_then_value(): void {
		$row = [ 'status' => 'gepubliceerd' ];

		$this->assertSame( 'publish', $this->pipeline->process( "status|if('gepubliceerd', 'publish')", $row ) );
	}

	public function test_if_modifier_empty_value_matches_empty_string(): void {
		$row = [ 'status' => '' ];

		$this->assertSame( 'none', $this->pipeline->process( "status|if('', 'none', 'has-value')", $row ) );
	}

	public function test_if_modifier_case_sensitive(): void {
		$row = [ 'status' => 'Gepubliceerd' ];

		$this->assertSame( 'draft', $this->pipeline->process( "status|if('gepubliceerd', 'publish', 'draft')", $row ) );
	}

	public function test_if_modifier_chains_with_other_modifiers(): void {
		$row = [ 'status' => 'Gepubliceerd' ];

		$this->assertSame( 'publish', $this->pipeline->process( "status|lower|if('gepubliceerd', 'publish', 'draft')", $row ) );
	}

	public function test_if_modifier_with_column_ref_arg(): void {
		$row = [ 'status' => 'gepubliceerd', 'expected' => 'gepubliceerd' ];

		$this->assertSame( 'match', $this->pipeline->process( "status|if(expected, 'match', 'no-match')", $row ) );
	}

	// --- Ternary / conditional tests ---

	public function test_ternary_equals_true_branch(): void {
		$row = [ 'status' => 'gepubliceerd' ];

		$this->assertSame( 'publish', $this->pipeline->process( "status == 'gepubliceerd' ? 'publish' : 'draft'", $row ) );
	}

	public function test_ternary_equals_false_branch(): void {
		$row = [ 'status' => 'concept' ];

		$this->assertSame( 'draft', $this->pipeline->process( "status == 'gepubliceerd' ? 'publish' : 'draft'", $row ) );
	}

	public function test_ternary_not_equals(): void {
		$row = [ 'status' => 'concept' ];

		$this->assertSame( 'not published', $this->pipeline->process( "status != 'gepubliceerd' ? 'not published' : 'published'", $row ) );
	}

	public function test_ternary_with_column_placeholder_in_branch(): void {
		$row = [ 'type' => 'person', 'voornaam' => 'Jan', 'bedrijfsnaam' => 'Acme' ];

		$this->assertSame( 'Jan', $this->pipeline->process( "type == 'person' ? {voornaam} : {bedrijfsnaam}", $row ) );
	}

	public function test_ternary_with_column_placeholder_false_branch(): void {
		$row = [ 'type' => 'company', 'voornaam' => 'Jan', 'bedrijfsnaam' => 'Acme' ];

		$this->assertSame( 'Acme', $this->pipeline->process( "type == 'company' ? {bedrijfsnaam} : {voornaam}", $row ) );
	}

	public function test_ternary_with_modifier_in_branch(): void {
		$row = [ 'type' => 'person', 'name' => 'jan' ];

		$this->assertSame( 'JAN', $this->pipeline->process( "type == 'person' ? {name|upper} : 'unknown'", $row ) );
	}

	public function test_ternary_is_empty_true(): void {
		$row = [ 'email' => '' ];

		$this->assertSame( 'no-email', $this->pipeline->process( "email is empty ? 'no-email' : {email}", $row ) );
	}

	public function test_ternary_is_not_empty(): void {
		$row = [ 'email' => 'jan@example.com' ];

		$this->assertSame( 'jan@example.com', $this->pipeline->process( "email is not empty ? {email} : 'no-reply@example.com'", $row ) );
	}

	public function test_ternary_greater_than(): void {
		$row = [ 'price' => '200' ];

		$this->assertSame( 'expensive', $this->pipeline->process( "price > '100' ? 'expensive' : 'affordable'", $row ) );
	}

	public function test_ternary_less_than_or_equal(): void {
		$row = [ 'stock' => '0' ];

		$this->assertSame( 'out-of-stock', $this->pipeline->process( "stock <= '0' ? 'out-of-stock' : 'in-stock'", $row ) );
	}

	public function test_ternary_bare_column_ref_on_right_side(): void {
		$row = [ 'col1' => 'hello', 'col2' => 'hello' ];

		$this->assertSame( 'match', $this->pipeline->process( "col1 == col2 ? 'match' : 'no match'", $row ) );
	}

	public function test_ternary_no_else_branch_defaults_to_empty(): void {
		$row = [ 'status' => 'concept' ];

		$this->assertSame( '', $this->pipeline->process( "status == 'gepubliceerd' ? 'publish'", $row ) );
	}

	public function test_ternary_no_else_branch_true(): void {
		$row = [ 'status' => 'gepubliceerd' ];

		$this->assertSame( 'publish', $this->pipeline->process( "status == 'gepubliceerd' ? 'publish'", $row ) );
	}

	public function test_non_ternary_still_works(): void {
		$row = [ 'name' => 'jan' ];

		$this->assertSame( 'JAN', $this->pipeline->process( 'name|upper', $row ) );
	}

	public function test_ternary_with_multiple_placeholders_in_branch(): void {
		$row = [ 'type' => 'person', 'voornaam' => 'Jan', 'achternaam' => 'De Smet', 'bedrijfsnaam' => 'Acme' ];

		$this->assertSame( 'Jan De Smet', $this->pipeline->process( "type == 'person' ? {voornaam} {achternaam} : {bedrijfsnaam}", $row ) );
	}

	public function test_ternary_bare_column_ref_in_branch(): void {
		$row = [ 'voornaam' => 'Harry' ];

		$this->assertSame( 'Henk', $this->pipeline->process( "voornaam == 'Harry' ? 'Henk' : voornaam", $row ) );
	}

	public function test_ternary_bare_column_ref_in_else_branch(): void {
		$row = [ 'voornaam' => 'Jan' ];

		$this->assertSame( 'Jan', $this->pipeline->process( "voornaam == 'Harry' ? 'Henk' : voornaam", $row ) );
	}
}
