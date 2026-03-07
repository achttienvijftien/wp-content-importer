<?php

namespace AchttienVijftien\WpContentImporter\Tests\Unit\Admin;

use AchttienVijftien\WpContentImporter\Admin\FormHandler;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class FormHandlerTest extends TestCase {

	private FormHandler $handler;

	protected function set_up(): void {
		parent::set_up();
		$this->handler = new FormHandler();
	}

	protected function tear_down(): void {
		unset( $_POST['wci_action'] );
		parent::tear_down();
	}

	public function test_handle_post_returns_early_without_wci_action(): void {
		unset( $_POST['wci_action'] );

		// Should return without doing anything (no die/redirect).
		$this->handler->handle_post();

		$this->assertTrue( true );
	}

	public function test_constructor_registers_admin_init_hook(): void {
		$this->assertIsInt(
			has_action( 'admin_init', [ $this->handler, 'handle_post' ] )
		);
	}

	public function test_handle_post_does_nothing_for_unknown_action(): void {
		$_POST['wci_action'] = 'nonexistent';

		// Grant the import capability.
		$user = wp_get_current_user();
		$user->add_cap( 'import' );

		// Should return without error for unknown action.
		$this->handler->handle_post();

		$user->remove_cap( 'import' );

		$this->assertTrue( true );
	}
}
