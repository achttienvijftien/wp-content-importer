<?php

namespace AchttienVijftien\WpContentImporter\Tests\Unit\Import;

use AchttienVijftien\WpContentImporter\Import\ImportRow;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class ImportRowTest extends TestCase {

	private function make_row( array $overrides = [] ): ImportRow {
		$data = (object) array_merge(
			[
				'id'         => 1,
				'job_id'     => 1,
				'data'       => '{"col":"val"}',
				'status'     => 'pending',
				'post_id'    => null,
				'error'      => null,
				'created_at' => '2026-01-01 00:00:00',
			],
			$overrides
		);

		return new ImportRow( $data );
	}

	public function test_mark_partial_sets_status_and_post_id_and_error(): void {
		$row = $this->make_row();

		$row->mark_partial( 42, 'Term "Sport" not found in category' );

		$this->assertSame( 'partial', $row->status );
		$this->assertSame( 42, $row->post_id );
		$this->assertSame( 'Term "Sport" not found in category', $row->error );
	}

	public function test_mark_done_sets_status_done(): void {
		$row = $this->make_row();

		$row->mark_done( 42 );

		$this->assertSame( 'done', $row->status );
		$this->assertSame( 42, $row->post_id );
	}

	public function test_mark_failed_sets_status_failed(): void {
		$row = $this->make_row();

		$row->mark_failed( 'Something went wrong' );

		$this->assertSame( 'failed', $row->status );
		$this->assertSame( 'Something went wrong', $row->error );
	}
}
