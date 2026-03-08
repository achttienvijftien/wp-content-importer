<?php
/**
 * Modifier pipeline.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\Mapping;

/**
 * Parses `{column|mod1|mod2(arg)}` syntax and chains modifiers.
 */
class ModifierPipeline {

	/**
	 * Modifier registry instance.
	 *
	 * @var ModifierRegistry
	 */
	private ModifierRegistry $registry;

	/**
	 * Constructor.
	 *
	 * @param ModifierRegistry $registry Modifier registry.
	 */
	public function __construct( ModifierRegistry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Process an expression against a row of data.
	 *
	 * The expression is the content inside `{}` braces, e.g. `name|upper|trim`.
	 *
	 * @param string $expression The expression to process.
	 * @param array  $row_data   The row data array.
	 *
	 * @return string
	 */
	public function process( string $expression, array $row_data ): string {
		$parts  = explode( '|', $expression );
		$column = trim( array_shift( $parts ) );
		$value  = $row_data[ $column ] ?? '';

		foreach ( $parts as $part ) {
			$part = trim( $part );

			if ( '' === $part ) {
				continue;
			}

			[ $name, $args ] = $this->parse_modifier( $part, $row_data );
			$value = $this->registry->apply( $name, $value, $args );
		}

		return $value;
	}

	/**
	 * Parse a modifier expression into a name and arguments.
	 *
	 * @param string $expression The modifier expression (e.g. `replace('a', 'b')`).
	 * @param array  $row_data   The row data array for resolving column references.
	 *
	 * @return array{0: string, 1: array}
	 */
	private function parse_modifier( string $expression, array $row_data ): array {
		if ( ! preg_match( '/^(\w+)(?:\((.+)\))?$/', $expression, $matches ) ) {
			return [ $expression, [] ];
		}

		$name = $matches[1];
		$args = [];

		if ( isset( $matches[2] ) ) {
			$args = $this->parse_args( $matches[2], $row_data );
		}

		return [ $name, $args ];
	}

	/**
	 * Parse a raw arguments string into an array of resolved values.
	 *
	 * Quoted strings are treated as static values. Bare words are resolved
	 * from row data, falling back to the literal string if no column matches.
	 *
	 * @param string $raw      The raw arguments string.
	 * @param array  $row_data The row data array for resolving column references.
	 *
	 * @return array
	 */
	private function parse_args( string $raw, array $row_data ): array {
		$args  = [];
		$parts = str_getcsv( $raw, ',', "'", '' );

		foreach ( $parts as $part ) {
			$part = trim( $part );

			// If the original raw string had this part quoted, use it as static.
			if ( preg_match( "/'" . preg_quote( $part, '/' ) . "'/", $raw ) ) {
				$args[] = $part;
			} else {
				// Bare word — resolve from row data, fall back to literal.
				$args[] = $row_data[ $part ] ?? $part;
			}
		}

		return $args;
	}
}
