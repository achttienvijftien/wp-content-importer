<?php
/**
 * Parser interface.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\Parser;

interface ParserInterface {

	/**
	 * Parse a file and return rows as associative arrays keyed by header.
	 *
	 * @param string $file_path Path to the uploaded file.
	 *
	 * @return array{headers: string[], rows: array<int, array<string, string>>}
	 * @throws ParseException When the file cannot be parsed.
	 */
	public function parse( string $file_path ): array;
}
