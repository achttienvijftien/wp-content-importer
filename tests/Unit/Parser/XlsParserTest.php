<?php

namespace AchttienVijftien\WpContentImporter\Tests\Unit\Parser;

use AchttienVijftien\WpContentImporter\Parser\ParseException;
use AchttienVijftien\WpContentImporter\Parser\XlsParser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class XlsParserTest extends TestCase {

	private string $tmp_dir;

	protected function setUp(): void {
		$this->tmp_dir = sys_get_temp_dir() . '/wci_test_' . uniqid();
		mkdir( $this->tmp_dir );
	}

	protected function tearDown(): void {
		array_map( 'unlink', glob( $this->tmp_dir . '/*' ) );
		rmdir( $this->tmp_dir );
	}

	private function create_xlsx( array $headers, array $rows ): string {
		$spreadsheet = new Spreadsheet();
		$sheet       = $spreadsheet->getActiveSheet();

		foreach ( $headers as $col => $header ) {
			$sheet->setCellValue( [ $col + 1, 1 ], $header );
		}

		foreach ( $rows as $row_index => $row ) {
			foreach ( $row as $col => $value ) {
				$sheet->setCellValue( [ $col + 1, $row_index + 2 ], $value );
			}
		}

		$path   = $this->tmp_dir . '/test.xlsx';
		$writer = new Xlsx( $spreadsheet );
		$writer->save( $path );

		return $path;
	}

	public function test_parses_valid_xlsx(): void {
		$path   = $this->create_xlsx(
			[ 'title', 'status' ],
			[
				[ 'Hello', 'publish' ],
				[ 'World', 'draft' ],
			]
		);
		$parser = new XlsParser();
		$result = $parser->parse( $path );

		$this->assertSame( [ 'title', 'status' ], $result['headers'] );
		$this->assertCount( 2, $result['rows'] );
		$this->assertSame( 'Hello', $result['rows'][0]['title'] );
		$this->assertSame( 'draft', $result['rows'][1]['status'] );
	}

	public function test_throws_on_empty_spreadsheet(): void {
		$spreadsheet = new Spreadsheet();
		$sheet       = $spreadsheet->getActiveSheet();
		$sheet->setCellValue( 'A1', 'title' );

		$path   = $this->tmp_dir . '/empty.xlsx';
		$writer = new Xlsx( $spreadsheet );
		$writer->save( $path );

		$this->expectException( ParseException::class );
		( new XlsParser() )->parse( $path );
	}

	public function test_casts_values_to_strings(): void {
		$path   = $this->create_xlsx(
			[ 'name', 'age' ],
			[
				[ 'Alice', 30 ],
			]
		);
		$parser = new XlsParser();
		$result = $parser->parse( $path );

		$this->assertSame( '30', $result['rows'][0]['age'] );
	}

	public function test_throws_on_invalid_file(): void {
		$path = $this->tmp_dir . '/bad.xlsx';
		file_put_contents( $path, 'not a valid spreadsheet' );

		$this->expectException( ParseException::class );
		( new XlsParser() )->parse( $path );
	}
}
