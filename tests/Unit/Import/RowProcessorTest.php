<?php

namespace AchttienVijftien\WpContentImporter\Tests\Unit\Import;

use AchttienVijftien\WpContentImporter\Import\RowProcessor;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use ReflectionMethod;

class RowProcessorTest extends TestCase {

	private RowProcessor $processor;
	private ReflectionMethod $map_values;

	protected function setUp(): void {
		$this->processor  = new RowProcessor();
		$this->map_values = new ReflectionMethod( RowProcessor::class, 'map_values' );
	}

	private function map( array $data, array $mapping ): array {
		return $this->map_values->invoke( $this->processor, $data, $mapping );
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
}
