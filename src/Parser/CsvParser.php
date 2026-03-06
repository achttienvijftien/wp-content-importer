<?php
/**
 * CSV parser.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\Parser;

/**
 * Parses CSV files into structured arrays.
 */
class CsvParser implements ParserInterface {

	/**
	 * Parse a CSV file into headers and rows.
	 *
	 * @param string $file_path Path to the CSV file.
	 *
	 * @return array{headers: string[], rows: array<int, array<string, string>>} Parsed data.
	 *
	 * @throws ParseException If the file cannot be opened or contains no data.
	 */
	public function parse( string $file_path ): array {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$handle = fopen( $file_path, 'r' );

		if ( false === $handle ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new ParseException( "Cannot open file: {$file_path}" );
		}

		$headers = fgetcsv( $handle, null, ',', '"', '' );

		if ( false === $headers || null === $headers ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose( $handle );
			throw new ParseException( 'Cannot read CSV headers.' );
		}

		// Remove BOM from first header if present.
		$headers[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $headers[0] );
		$headers    = array_map( 'trim', $headers );

		$rows = [];

		// phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
		while ( ( $row = fgetcsv( $handle, null, ',', '"', '' ) ) !== false ) {
			if ( count( $row ) !== count( $headers ) ) {
				continue;
			}

			$rows[] = array_combine( $headers, $row );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $handle );

		if ( empty( $rows ) ) {
			throw new ParseException( 'CSV file contains no data rows.' );
		}

		return [
			'headers' => $headers,
			'rows'    => $rows,
		];
	}
}
