<?php
/**
 * Parser factory.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\Parser;

/**
 * Factory for creating file parsers based on file extension.
 */
class ParserFactory {

	/**
	 * Create a parser instance for the given file.
	 *
	 * @param string $file_path Path to the file to parse.
	 *
	 * @return ParserInterface A parser capable of handling the file type.
	 *
	 * @throws ParseException If the file type is not supported.
	 */
	public static function create( string $file_path ): ParserInterface {
		$extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );

		return match ( $extension ) {
			'csv'          => new CsvParser(),
			'xls', 'xlsx'  => new XlsParser(),
			default        => throw new ParseException(
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				"Unsupported file type: {$extension}"
			),
		};
	}
}
