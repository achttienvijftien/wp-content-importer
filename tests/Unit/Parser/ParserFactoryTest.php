<?php

namespace AchttienVijftien\WpContentImporter\Tests\Unit\Parser;

use AchttienVijftien\WpContentImporter\Parser\CsvParser;
use AchttienVijftien\WpContentImporter\Parser\ParseException;
use AchttienVijftien\WpContentImporter\Parser\ParserFactory;
use AchttienVijftien\WpContentImporter\Parser\XlsParser;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class ParserFactoryTest extends TestCase {

	public function test_creates_csv_parser_for_csv_extension(): void {
		$parser = ParserFactory::create( '/tmp/test.csv' );

		$this->assertInstanceOf( CsvParser::class, $parser );
	}

	public function test_creates_xls_parser_for_xls_extension(): void {
		$parser = ParserFactory::create( '/tmp/test.xls' );

		$this->assertInstanceOf( XlsParser::class, $parser );
	}

	public function test_creates_xls_parser_for_xlsx_extension(): void {
		$parser = ParserFactory::create( '/tmp/test.xlsx' );

		$this->assertInstanceOf( XlsParser::class, $parser );
	}

	public function test_throws_for_unsupported_extension(): void {
		$this->expectException( ParseException::class );
		$this->expectExceptionMessage( 'Unsupported file type: txt' );

		ParserFactory::create( '/tmp/test.txt' );
	}

	public function test_handles_uppercase_extension(): void {
		$parser = ParserFactory::create( '/tmp/test.CSV' );

		$this->assertInstanceOf( CsvParser::class, $parser );
	}
}
