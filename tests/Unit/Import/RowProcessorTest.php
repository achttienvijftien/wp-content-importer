<?php

namespace AchttienVijftien\WpContentImporter\Tests\Unit\Import;

use AchttienVijftien\WpContentImporter\Import\RowProcessor;
use AchttienVijftien\WpContentImporter\Mapping\ModifierRegistry;
use ReflectionMethod;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class RowProcessorTest extends TestCase {

	private RowProcessor $processor;
	private ReflectionMethod $map_values;
	private ReflectionMethod $build_post_args;

	protected function set_up(): void {
		parent::set_up();
		ModifierRegistry::instance()->register_defaults();
		$this->processor       = new RowProcessor();
		$this->map_values      = new ReflectionMethod( RowProcessor::class, 'map_values' );
		$this->build_post_args = new ReflectionMethod( RowProcessor::class, 'build_post_args' );
	}

	private function map( array $data, array $mapping ): array {
		return $this->map_values->invoke( $this->processor, $data, $mapping );
	}

	private function build_args( array $mapped, string $post_type = 'post' ): array {
		return $this->build_post_args->invoke( $this->processor, $mapped, $post_type );
	}

	public function test_single_column_placeholder(): void {
		$data    = [ 'voornaam' => 'Jan', 'achternaam' => 'De Smet' ];
		$mapping = [
			'first_name' => [ 'template' => '{voornaam}', 'type' => 'text' ],
		];

		$result = $this->map( $data, $mapping );

		$this->assertSame( 'Jan', $result['first_name']['value'] );
		$this->assertSame( 'text', $result['first_name']['type'] );
	}

	public function test_multiple_columns_combined(): void {
		$data    = [ 'voornaam' => 'Jan', 'achternaam' => 'De Smet' ];
		$mapping = [
			'post_title' => [ 'template' => '{voornaam} {achternaam}', 'type' => 'text' ],
		];

		$result = $this->map( $data, $mapping );

		$this->assertSame( 'Jan De Smet', $result['post_title']['value'] );
	}

	public function test_format_string_with_static_text(): void {
		$data    = [ 'voornaam' => 'Jan', 'achternaam' => 'De Smet' ];
		$mapping = [
			'post_title' => [ 'template' => 'krak: {voornaam} {achternaam}', 'type' => 'text' ],
		];

		$result = $this->map( $data, $mapping );

		$this->assertSame( 'krak: Jan De Smet', $result['post_title']['value'] );
	}

	public function test_static_value_without_placeholders(): void {
		$data    = [ 'voornaam' => 'Jan' ];
		$mapping = [
			'post_status' => [ 'template' => 'publish', 'type' => 'text' ],
		];

		$result = $this->map( $data, $mapping );

		$this->assertSame( 'publish', $result['post_status']['value'] );
	}

	public function test_missing_column_resolves_to_empty_string(): void {
		$data    = [ 'voornaam' => 'Jan' ];
		$mapping = [
			'post_title' => [ 'template' => '{voornaam} {achternaam}', 'type' => 'text' ],
		];

		$result = $this->map( $data, $mapping );

		$this->assertSame( 'Jan ', $result['post_title']['value'] );
	}

	public function test_empty_template_is_skipped(): void {
		$data    = [ 'voornaam' => 'Jan' ];
		$mapping = [
			'post_title' => [ 'template' => '', 'type' => 'text' ],
		];

		$result = $this->map( $data, $mapping );

		$this->assertArrayNotHasKey( 'post_title', $result );
	}

	public function test_all_placeholders_empty_skips_field(): void {
		$data    = [ 'voornaam' => '', 'achternaam' => '' ];
		$mapping = [
			'post_title' => [ 'template' => '{voornaam}{achternaam}', 'type' => 'text' ],
		];

		$result = $this->map( $data, $mapping );

		$this->assertArrayNotHasKey( 'post_title', $result );
	}

	public function test_same_column_mapped_to_multiple_fields(): void {
		$data    = [ 'voornaam' => 'Jan', 'achternaam' => 'De Smet' ];
		$mapping = [
			'post_title' => [ 'template' => '{voornaam} {achternaam}', 'type' => 'text' ],
			'first_name' => [ 'template' => '{voornaam}', 'type' => 'text' ],
			'last_name'  => [ 'template' => '{achternaam}', 'type' => 'text' ],
		];

		$result = $this->map( $data, $mapping );

		$this->assertSame( 'Jan De Smet', $result['post_title']['value'] );
		$this->assertSame( 'Jan', $result['first_name']['value'] );
		$this->assertSame( 'De Smet', $result['last_name']['value'] );
	}

	public function test_type_defaults_to_text(): void {
		$data    = [ 'voornaam' => 'Jan' ];
		$mapping = [
			'first_name' => [ 'template' => '{voornaam}' ],
		];

		$result = $this->map( $data, $mapping );

		$this->assertSame( 'text', $result['first_name']['type'] );
	}

	public function test_type_is_preserved(): void {
		$data    = [ 'email' => 'jan@example.com' ];
		$mapping = [
			'field_email' => [ 'template' => '{email}', 'type' => 'email' ],
		];

		$result = $this->map( $data, $mapping );

		$this->assertSame( 'email', $result['field_email']['type'] );
	}

	public function test_wci_mapped_value_filter_alters_value(): void {
		$filter = function ( $value, $target_key ) {
			if ( 'post_title' === $target_key ) {
				return strtoupper( $value );
			}
			return $value;
		};

		add_filter( 'wci_mapped_value', $filter, 10, 2 );

		$data    = [ 'voornaam' => 'Jan' ];
		$mapping = [
			'post_title' => [ 'template' => '{voornaam}', 'type' => 'text' ],
			'first_name' => [ 'template' => '{voornaam}', 'type' => 'text' ],
		];

		$result = $this->map( $data, $mapping );

		$this->assertSame( 'JAN', $result['post_title']['value'] );
		$this->assertSame( 'Jan', $result['first_name']['value'] );

		remove_filter( 'wci_mapped_value', $filter, 10 );
	}

	public function test_wci_mapped_value_filter_can_empty_to_skip(): void {
		$filter = function () {
			return '';
		};

		add_filter( 'wci_mapped_value', $filter );

		$data    = [ 'voornaam' => 'Jan' ];
		$mapping = [
			'post_title' => [ 'template' => '{voornaam}', 'type' => 'text' ],
		];

		$result = $this->map( $data, $mapping );

		$this->assertArrayNotHasKey( 'post_title', $result );

		remove_filter( 'wci_mapped_value', $filter );
	}

	public function test_wci_mapped_value_filter_receives_all_args(): void {
		$captured = [];

		$filter = function ( $value, $target_key, $data, $template ) use ( &$captured ) {
			$captured = compact( 'value', 'target_key', 'data', 'template' );
			return $value;
		};

		add_filter( 'wci_mapped_value', $filter, 10, 4 );

		$data    = [ 'voornaam' => 'Jan' ];
		$mapping = [
			'post_title' => [ 'template' => 'Hi {voornaam}', 'type' => 'text' ],
		];

		$this->map( $data, $mapping );

		$this->assertSame( 'Hi Jan', $captured['value'] );
		$this->assertSame( 'post_title', $captured['target_key'] );
		$this->assertSame( $data, $captured['data'] );
		$this->assertSame( 'Hi {voornaam}', $captured['template'] );

		remove_filter( 'wci_mapped_value', $filter, 10 );
	}

	public function test_placeholder_with_single_modifier(): void {
		$data    = [ 'voornaam' => 'jan' ];
		$mapping = [
			'post_title' => [ 'template' => '{voornaam|upper}', 'type' => 'text' ],
		];

		$result = $this->map( $data, $mapping );

		$this->assertSame( 'JAN', $result['post_title']['value'] );
	}

	public function test_placeholder_with_chained_modifiers(): void {
		$data    = [ 'voornaam' => '  jan  ' ];
		$mapping = [
			'post_title' => [ 'template' => '{voornaam|trim|upper}', 'type' => 'text' ],
		];

		$result = $this->map( $data, $mapping );

		$this->assertSame( 'JAN', $result['post_title']['value'] );
	}

	public function test_mixed_placeholders_with_and_without_modifiers(): void {
		$data    = [ 'voornaam' => 'jan', 'achternaam' => 'de smet' ];
		$mapping = [
			'post_title' => [ 'template' => '{voornaam|upper} {achternaam|capitalize}', 'type' => 'text' ],
		];

		$result = $this->map( $data, $mapping );

		$this->assertSame( 'JAN De smet', $result['post_title']['value'] );
	}

	public function test_modifier_with_static_arg_in_template(): void {
		$data    = [ 'datum' => '15-01-2024' ];
		$mapping = [
			'post_date' => [ 'template' => "{datum|date('Y-m-d')}", 'type' => 'text' ],
		];

		$result = $this->map( $data, $mapping );

		$this->assertSame( '2024-01-15', $result['post_date']['value'] );
	}

	public function test_plain_placeholders_still_work(): void {
		$data    = [ 'voornaam' => 'Jan', 'achternaam' => 'De Smet' ];
		$mapping = [
			'post_title' => [ 'template' => '{voornaam} {achternaam}', 'type' => 'text' ],
		];

		$result = $this->map( $data, $mapping );

		$this->assertSame( 'Jan De Smet', $result['post_title']['value'] );
	}

	public function test_ternary_in_mapping_template(): void {
		$data    = [ 'status' => 'gepubliceerd' ];
		$mapping = [
			'post_status' => [ 'template' => "{status == 'gepubliceerd' ? 'publish' : 'draft'}", 'type' => 'text' ],
		];

		$result = $this->map( $data, $mapping );

		$this->assertSame( 'publish', $result['post_status']['value'] );
	}

	public function test_ternary_false_branch_in_mapping(): void {
		$data    = [ 'status' => 'concept' ];
		$mapping = [
			'post_status' => [ 'template' => "{status == 'gepubliceerd' ? 'publish' : 'draft'}", 'type' => 'text' ],
		];

		$result = $this->map( $data, $mapping );

		$this->assertSame( 'draft', $result['post_status']['value'] );
	}

	public function test_ternary_with_nested_placeholders_in_mapping(): void {
		$data    = [ 'type' => 'person', 'voornaam' => 'Jan', 'achternaam' => 'De Smet', 'bedrijfsnaam' => 'Acme' ];
		$mapping = [
			'post_title' => [ 'template' => "{type == 'person' ? {voornaam} {achternaam} : {bedrijfsnaam}}", 'type' => 'text' ],
		];

		$result = $this->map( $data, $mapping );

		$this->assertSame( 'Jan De Smet', $result['post_title']['value'] );
	}

	public function test_ternary_resolving_to_empty_skips_field(): void {
		$data    = [ 'status' => 'concept' ];
		$mapping = [
			'post_excerpt' => [ 'template' => "{status == 'gepubliceerd' ? 'excerpt text'}", 'type' => 'text' ],
		];

		$result = $this->map( $data, $mapping );

		$this->assertArrayNotHasKey( 'post_excerpt', $result );
	}

	public function test_ternary_alongside_regular_placeholders(): void {
		$data    = [ 'voornaam' => 'Jan', 'type' => 'vip' ];
		$mapping = [
			'post_title' => [ 'template' => "{voornaam} - {type == 'vip' ? 'VIP' : 'Regular'}", 'type' => 'text' ],
		];

		$result = $this->map( $data, $mapping );

		$this->assertSame( 'Jan - VIP', $result['post_title']['value'] );
	}

	public function test_build_post_args_includes_menu_order_as_integer(): void {
		$mapped = [
			'menu_order' => [ 'value' => '5', 'type' => 'integer' ],
		];

		$args = $this->build_args( $mapped );

		$this->assertSame( 5, $args['menu_order'] );
	}

	public function test_build_post_args_skips_empty_menu_order(): void {
		$mapped = [
			'menu_order' => [ 'value' => '', 'type' => 'integer' ],
		];

		$args = $this->build_args( $mapped );

		$this->assertArrayNotHasKey( 'menu_order', $args );
	}
}
