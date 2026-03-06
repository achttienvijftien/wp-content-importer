<?php

namespace AchttienVijftien\WpContentImporter\Tests\Unit\Parser;

use AchttienVijftien\WpContentImporter\Parser\CsvParser;
use AchttienVijftien\WpContentImporter\Parser\ParseException;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class CsvParserTest extends TestCase {

	private string $tmp_dir;

	protected function setUp(): void {
		$this->tmp_dir = sys_get_temp_dir() . '/wci_test_' . uniqid();
		mkdir( $this->tmp_dir );
	}

	protected function tearDown(): void {
		array_map( 'unlink', glob( $this->tmp_dir . '/*' ) );
		rmdir( $this->tmp_dir );
	}

	private function create_csv( string $content ): string {
		$path = $this->tmp_dir . '/test.csv';
		file_put_contents( $path, $content );

		return $path;
	}

	public function test_parses_valid_csv(): void {
		$path   = $this->create_csv( "title,status\nHello,publish\nWorld,draft\n" );
		$parser = new CsvParser();
		$result = $parser->parse( $path );

		$this->assertSame( [ 'title', 'status' ], $result['headers'] );
		$this->assertCount( 2, $result['rows'] );
		$this->assertSame( 'Hello', $result['rows'][0]['title'] );
		$this->assertSame( 'draft', $result['rows'][1]['status'] );
	}

	public function test_strips_bom(): void {
		$path   = $this->create_csv( "\xEF\xBB\xBFtitle,status\nHello,publish\n" );
		$parser = new CsvParser();
		$result = $parser->parse( $path );

		$this->assertSame( 'title', $result['headers'][0] );
	}

	public function test_throws_on_empty_file(): void {
		$path = $this->create_csv( "title,status\n" );

		$this->expectException( ParseException::class );
		( new CsvParser() )->parse( $path );
	}

	public function test_skips_rows_with_wrong_column_count(): void {
		$path   = $this->create_csv( "title,status\nHello,publish\nBad\nWorld,draft\n" );
		$parser = new CsvParser();
		$result = $parser->parse( $path );

		$this->assertCount( 2, $result['rows'] );
	}
}
