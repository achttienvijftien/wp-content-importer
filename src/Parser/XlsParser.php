<?php
/**
 * XLS/XLSX parser.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\Parser;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Parses XLS and XLSX spreadsheet files into structured arrays.
 */
class XlsParser implements ParserInterface {

	/**
	 * Parse a spreadsheet file into headers and rows.
	 *
	 * @param string $file_path Path to the XLS/XLSX file.
	 *
	 * @return array{headers: string[], rows: array<int, array<string, string>>} Parsed data.
	 *
	 * @throws ParseException If the file cannot be read or contains no data.
	 */
	public function parse( string $file_path ): array {
		try {
			$spreadsheet = IOFactory::load( $file_path );
		} catch ( \Throwable $e ) {
			throw new ParseException(
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				"Cannot read spreadsheet: {$e->getMessage()}"
			);
		}

		$worksheet = $spreadsheet->getActiveSheet();
		$data      = $worksheet->toArray( null, true, true, false );

		if ( count( $data ) < 2 ) {
			throw new ParseException( 'Spreadsheet contains no data rows.' );
		}

		$headers = array_map(
			fn( $h ) => trim( (string) $h ),
			array_shift( $data )
		);
		$rows    = [];

		foreach ( $data as $row ) {
			if ( count( $row ) !== count( $headers ) ) {
				continue;
			}

			$rows[] = array_combine(
				$headers,
				array_map( fn( $v ) => (string) ( $v ?? '' ), $row )
			);
		}

		if ( empty( $rows ) ) {
			throw new ParseException( 'Spreadsheet contains no valid data rows.' );
		}

		return [
			'headers' => $headers,
			'rows'    => $rows,
		];
	}
}
