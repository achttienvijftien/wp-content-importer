<?php

namespace AchttienVijftien\WpContentImporter\Tests\Unit\Mapping;

use AchttienVijftien\WpContentImporter\Mapping\ModifierRegistry;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class ModifierRegistryTest extends TestCase {

	private ModifierRegistry $registry;

	protected function set_up(): void {
		parent::set_up();
		$this->registry = new ModifierRegistry();
	}

	public function test_register_and_apply(): void {
		$this->registry->register( 'upper', fn( string $value ): string => strtoupper( $value ) );

		$this->assertSame( 'HELLO', $this->registry->apply( 'upper', 'hello', [] ) );
	}

	public function test_has_returns_true_for_registered(): void {
		$this->registry->register( 'upper', fn( string $value ): string => strtoupper( $value ) );

		$this->assertTrue( $this->registry->has( 'upper' ) );
	}

	public function test_has_returns_false_for_unregistered(): void {
		$this->assertFalse( $this->registry->has( 'nope' ) );
	}

	public function test_unregistered_modifier_returns_value_unchanged(): void {
		$this->assertSame( 'hello', $this->registry->apply( 'unknown', 'hello', [] ) );
	}

	public function test_apply_passes_args_to_callable(): void {
		$this->registry->register( 'repeat', fn( string $value, array $args ): string => str_repeat( $value, (int) ( $args[0] ?? 1 ) ) );

		$this->assertSame( 'aaa', $this->registry->apply( 'repeat', 'a', [ '3' ] ) );
	}

	public function test_filter_fires_after_callable(): void {
		$this->registry->register( 'upper', fn( string $value ): string => strtoupper( $value ) );

		$filter = fn( string $result ): string => $result . '!';
		add_filter( 'wci_modifier_upper', $filter );

		$this->assertSame( 'HELLO!', $this->registry->apply( 'upper', 'hello', [] ) );

		remove_filter( 'wci_modifier_upper', $filter );
	}

	public function test_filter_fires_for_unregistered_modifier(): void {
		$filter = fn( string $value ): string => 'custom:' . $value;
		add_filter( 'wci_modifier_custom', $filter );

		$this->assertSame( 'custom:hello', $this->registry->apply( 'custom', 'hello', [] ) );

		remove_filter( 'wci_modifier_custom', $filter );
	}

	public function test_filter_receives_original_value_and_args(): void {
		$this->registry->register( 'upper', fn( string $value ): string => strtoupper( $value ) );

		$captured = [];
		$filter   = function ( string $result, string $original, array $args ) use ( &$captured ): string {
			$captured = compact( 'result', 'original', 'args' );
			return $result;
		};
		add_filter( 'wci_modifier_upper', $filter, 10, 3 );

		$this->registry->apply( 'upper', 'hello', [ 'arg1' ] );

		$this->assertSame( 'HELLO', $captured['result'] );
		$this->assertSame( 'hello', $captured['original'] );
		$this->assertSame( [ 'arg1' ], $captured['args'] );

		remove_filter( 'wci_modifier_upper', $filter, 10 );
	}

	public function test_custom_modifier_applies_and_appears_in_get_all(): void {
		$this->registry->register(
			'reverse',
			fn( string $value ): string => strrev( $value ),
			'Reverse a string',
			'{column|reverse}'
		);

		$this->assertSame( 'olleh', $this->registry->apply( 'reverse', 'hello', [] ) );

		$all = $this->registry->get_all();
		$this->assertArrayHasKey( 'reverse', $all );
		$this->assertSame( 'Reverse a string', $all['reverse']['description'] );
		$this->assertSame( '{column|reverse}', $all['reverse']['example'] );
	}

	public function test_get_all_includes_modifiers_without_metadata(): void {
		$this->registry->register( 'noop', fn( string $v ): string => $v );

		$all = $this->registry->get_all();
		$this->assertArrayHasKey( 'noop', $all );
		$this->assertSame( '', $all['noop']['description'] );
		$this->assertSame( '', $all['noop']['example'] );
	}

	public function test_register_defaults_registers_built_in_modifiers(): void {
		$this->registry->register_defaults();

		$expected = [ 'upper', 'lower', 'capitalize', 'trim', 'slug', 'date', 'number_format', 'replace', 'truncate', 'striptags' ];
		foreach ( $expected as $name ) {
			$this->assertTrue( $this->registry->has( $name ), "Built-in modifier '{$name}' should be registered." );
		}
	}
}
