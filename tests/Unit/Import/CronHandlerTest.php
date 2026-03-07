<?php

namespace AchttienVijftien\WpContentImporter\Tests\Unit\Import;

use AchttienVijftien\WpContentImporter\Import\CronHandler;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class CronHandlerTest extends TestCase {

	public function test_batch_size_default(): void {
		$this->assertSame( 50, CronHandler::BATCH_SIZE );
	}

	public function test_wci_batch_size_filter_overrides_default(): void {
		$filter = function () {
			return 25;
		};

		add_filter( 'wci_batch_size', $filter );

		$size = (int) apply_filters( 'wci_batch_size', CronHandler::BATCH_SIZE );

		$this->assertSame( 25, $size );

		remove_filter( 'wci_batch_size', $filter );
	}

	public function test_wci_batch_size_filter_receives_default_value(): void {
		$captured = null;

		$filter = function ( $value ) use ( &$captured ) {
			$captured = $value;
			return $value;
		};

		add_filter( 'wci_batch_size', $filter );

		apply_filters( 'wci_batch_size', CronHandler::BATCH_SIZE );

		$this->assertSame( 50, $captured );

		remove_filter( 'wci_batch_size', $filter );
	}

	public function test_cron_hook_constant(): void {
		$this->assertSame( 'wci_process_import', CronHandler::CRON_HOOK );
	}

	public function test_register_interval_adds_every_minute(): void {
		$schedules = CronHandler::register_interval( [] );

		$this->assertArrayHasKey( 'every_minute', $schedules );
		$this->assertSame( 60, $schedules['every_minute']['interval'] );
	}

	public function test_register_interval_preserves_existing(): void {
		$existing  = [ 'hourly' => [ 'interval' => 3600, 'display' => 'Hourly' ] ];
		$schedules = CronHandler::register_interval( $existing );

		$this->assertArrayHasKey( 'hourly', $schedules );
		$this->assertArrayHasKey( 'every_minute', $schedules );
	}
}
