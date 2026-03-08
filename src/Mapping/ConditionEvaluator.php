<?php
/**
 * Condition evaluator.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\Mapping;

/**
 * Evaluates boolean conditions for ternary expressions in mapping templates.
 */
class ConditionEvaluator {

	/**
	 * Evaluate a condition.
	 *
	 * @param string      $left     Left operand value.
	 * @param string      $operator Comparison operator.
	 * @param string|null $right    Right operand value (null for unary operators).
	 *
	 * @return bool
	 */
	public function evaluate( string $left, string $operator, ?string $right ): bool {
		return match ( $operator ) {
			'=='           => $left === $right,
			'!='           => $left !== $right,
			'>'            => $this->numeric_compare( $left, $right ) > 0,
			'<'            => $this->numeric_compare( $left, $right ) < 0,
			'>='           => $this->numeric_compare( $left, $right ) >= 0,
			'<='           => $this->numeric_compare( $left, $right ) <= 0,
			'is empty'     => '' === $left,
			'is not empty' => '' !== $left,
			default        => false,
		};
	}

	/**
	 * Compare two values numerically if possible, otherwise as strings.
	 *
	 * @param string      $left  Left value.
	 * @param string|null $right Right value.
	 *
	 * @return int|float Negative if left < right, positive if left > right, zero if equal.
	 */
	private function numeric_compare( string $left, ?string $right ): int|float {
		if ( is_numeric( $left ) && is_numeric( $right ) ) {
			return (float) $left - (float) $right;
		}

		return strcmp( $left, $right ?? '' );
	}
}
