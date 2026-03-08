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
		$ternary_result = $this->parse_ternary( $expression, $row_data );

		if ( null !== $ternary_result ) {
			return $ternary_result;
		}

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
	 * Parse and evaluate a ternary expression.
	 *
	 * @param string $expression The expression to parse.
	 * @param array  $row_data   The row data array.
	 *
	 * @return string|null The resolved value, or null if not a ternary expression.
	 */
	private function parse_ternary( string $expression, array $row_data ): ?string {
		if ( false === strpos( $expression, '?' ) ) {
			return null;
		}

		$parts = explode( ' ? ', $expression, 2 );

		if ( count( $parts ) < 2 ) {
			return null;
		}

		$condition_str = $parts[0];
		$branches_str  = $parts[1];

		$condition = $this->parse_condition( $condition_str, $row_data );

		if ( null === $condition ) {
			return null;
		}

		$branch_parts = explode( ' : ', $branches_str, 2 );
		$true_branch  = $branch_parts[0];
		$false_branch = $branch_parts[1] ?? '';

		$evaluator = new ConditionEvaluator();
		$result    = $evaluator->evaluate( $condition['left'], $condition['operator'], $condition['right'] );

		return $this->resolve_branch( $result ? $true_branch : $false_branch, $row_data );
	}

	/**
	 * Parse a condition string into its components.
	 *
	 * @param string $condition The condition string.
	 * @param array  $row_data  The row data array.
	 *
	 * @return array{left: string, operator: string, right: string|null}|null Parsed condition or null.
	 */
	private function parse_condition( string $condition, array $row_data ): ?array {
		if ( ! preg_match( '/^(\w+)\s+(==|!=|<=|>=|<|>|is\s+not\s+empty|is\s+empty)\s*(.*)$/', $condition, $matches ) ) {
			return null;
		}

		$column   = $matches[1];
		$operator = preg_replace( '/\s+/', ' ', $matches[2] );
		$right    = trim( $matches[3] );

		$left = $row_data[ $column ] ?? '';

		if ( 'is empty' === $operator || 'is not empty' === $operator ) {
			return [
				'left'     => $left,
				'operator' => $operator,
				'right'    => null,
			];
		}

		// Strip quotes from the right operand if present, otherwise resolve from row data.
		if ( preg_match( "/^'(.*)'$/", $right, $quote_matches ) ) {
			$right = $quote_matches[1];
		} else {
			$right = $row_data[ $right ] ?? '';
		}

		return [
			'left'     => $left,
			'operator' => $operator,
			'right'    => $right,
		];
	}

	/**
	 * Resolve a ternary branch value.
	 *
	 * @param string $branch   The branch expression.
	 * @param array  $row_data The row data array.
	 *
	 * @return string The resolved branch value.
	 */
	private function resolve_branch( string $branch, array $row_data ): string {
		$branch = trim( $branch );

		if ( preg_match( "/^'(.*)'$/s", $branch, $matches ) ) {
			return $matches[1];
		}

		// Single bare word — resolve as column reference.
		if ( preg_match( '/^\w+$/', $branch ) ) {
			return $row_data[ $branch ] ?? $branch;
		}

		return preg_replace_callback(
			'/\{([^}]+)\}/',
			function ( $matches ) use ( $row_data ) {
				return $this->process( $matches[1], $row_data );
			},
			$branch
		);
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
