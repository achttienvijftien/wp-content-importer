<?php

namespace AchttienVijftien\WpContentImporter\Tests\Unit\Mapping;

use AchttienVijftien\WpContentImporter\Mapping\ConditionEvaluator;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class ConditionEvaluatorTest extends TestCase {

	private ConditionEvaluator $evaluator;

	protected function set_up(): void {
		parent::set_up();
		$this->evaluator = new ConditionEvaluator();
	}

	public function test_equals_matching_strings(): void {
		$this->assertTrue( $this->evaluator->evaluate( 'publish', '==', 'publish' ) );
	}

	public function test_equals_non_matching_strings(): void {
		$this->assertFalse( $this->evaluator->evaluate( 'draft', '==', 'publish' ) );
	}

	public function test_not_equals_matching(): void {
		$this->assertTrue( $this->evaluator->evaluate( 'draft', '!=', 'publish' ) );
	}

	public function test_not_equals_same_value(): void {
		$this->assertFalse( $this->evaluator->evaluate( 'publish', '!=', 'publish' ) );
	}

	public function test_greater_than_numeric(): void {
		$this->assertTrue( $this->evaluator->evaluate( '200', '>', '100' ) );
	}

	public function test_greater_than_false(): void {
		$this->assertFalse( $this->evaluator->evaluate( '50', '>', '100' ) );
	}

	public function test_less_than_numeric(): void {
		$this->assertTrue( $this->evaluator->evaluate( '50', '<', '100' ) );
	}

	public function test_less_than_false(): void {
		$this->assertFalse( $this->evaluator->evaluate( '200', '<', '100' ) );
	}

	public function test_greater_than_or_equal_when_greater(): void {
		$this->assertTrue( $this->evaluator->evaluate( '200', '>=', '100' ) );
	}

	public function test_greater_than_or_equal_when_equal(): void {
		$this->assertTrue( $this->evaluator->evaluate( '100', '>=', '100' ) );
	}

	public function test_greater_than_or_equal_when_less(): void {
		$this->assertFalse( $this->evaluator->evaluate( '50', '>=', '100' ) );
	}

	public function test_less_than_or_equal_when_less(): void {
		$this->assertTrue( $this->evaluator->evaluate( '50', '<=', '100' ) );
	}

	public function test_less_than_or_equal_when_equal(): void {
		$this->assertTrue( $this->evaluator->evaluate( '100', '<=', '100' ) );
	}

	public function test_less_than_or_equal_when_greater(): void {
		$this->assertFalse( $this->evaluator->evaluate( '200', '<=', '100' ) );
	}

	public function test_is_empty_with_empty_string(): void {
		$this->assertTrue( $this->evaluator->evaluate( '', 'is empty', null ) );
	}

	public function test_is_empty_with_non_empty_string(): void {
		$this->assertFalse( $this->evaluator->evaluate( 'hello', 'is empty', null ) );
	}

	public function test_is_not_empty_with_non_empty_string(): void {
		$this->assertTrue( $this->evaluator->evaluate( 'hello', 'is not empty', null ) );
	}

	public function test_is_not_empty_with_empty_string(): void {
		$this->assertFalse( $this->evaluator->evaluate( '', 'is not empty', null ) );
	}

	public function test_numeric_string_comparison(): void {
		$this->assertTrue( $this->evaluator->evaluate( '100', '>', '50' ) );
		$this->assertFalse( $this->evaluator->evaluate( '100', '>', '200' ) );
	}

	public function test_decimal_comparison(): void {
		$this->assertTrue( $this->evaluator->evaluate( '10.5', '>', '10.1' ) );
		$this->assertTrue( $this->evaluator->evaluate( '9.99', '<', '10.00' ) );
	}

	public function test_equals_with_empty_strings(): void {
		$this->assertTrue( $this->evaluator->evaluate( '', '==', '' ) );
	}

	public function test_is_empty_with_whitespace_only(): void {
		$this->assertFalse( $this->evaluator->evaluate( '   ', 'is empty', null ) );
	}

	public function test_unknown_operator_returns_false(): void {
		$this->assertFalse( $this->evaluator->evaluate( 'a', 'LIKE', 'b' ) );
	}
}
