<?php

namespace AchttienVijftien\WpContentImporter\Tests\Integration\Import;

use AchttienVijftien\WpContentImporter\Import\ImportJob;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class ImportJobTest extends TestCase {

	private function make_row( array $overrides = [] ): object {
		return (object) array_merge(
			[
				'id'             => 1,
				'name'           => 'test-import',
				'status'         => 'draft',
				'post_type'      => 'post',
				'mode'           => 'create',
				'match_field'    => null,
				'mapping'        => null,
				'total_rows'     => 10,
				'processed_rows' => 0,
				'failed_rows'    => 0,
				'created_at'     => '2026-01-01 00:00:00',
				'updated_at'     => '2026-01-01 00:00:00',
			],
			$overrides
		);
	}

	public function test_constructor_maps_name(): void {
		$job = new ImportJob( $this->make_row( [ 'name' => 'My Import' ] ) );

		$this->assertSame( 'My Import', $job->name );
	}

	public function test_constructor_defaults_name_when_missing(): void {
		$row = $this->make_row();
		unset( $row->name );

		$job = new ImportJob( $row );

		$this->assertSame( '', $job->name );
	}

	public function test_constructor_casts_id_to_int(): void {
		$job = new ImportJob( $this->make_row( [ 'id' => '42' ] ) );

		$this->assertSame( 42, $job->id );
	}

	public function test_constructor_decodes_mapping_json(): void {
		$mapping = [ 'post_title' => [ 'template' => '{name}', 'type' => 'text' ] ];

		$job = new ImportJob( $this->make_row( [ 'mapping' => wp_json_encode( $mapping ) ] ) );

		$this->assertSame( $mapping, $job->mapping );
	}

	public function test_constructor_null_mapping_stays_null(): void {
		$job = new ImportJob( $this->make_row( [ 'mapping' => null ] ) );

		$this->assertNull( $job->mapping );
	}

	public function test_constructor_maps_all_properties(): void {
		$job = new ImportJob( $this->make_row(
			[
				'status'         => 'completed',
				'post_type'      => 'page',
				'mode'           => 'update',
				'match_field'    => 'post_name',
				'total_rows'     => 50,
				'processed_rows' => 45,
				'failed_rows'    => 5,
			]
		) );

		$this->assertSame( 'completed', $job->status );
		$this->assertSame( 'page', $job->post_type );
		$this->assertSame( 'update', $job->mode );
		$this->assertSame( 'post_name', $job->match_field );
		$this->assertSame( 50, $job->total_rows );
		$this->assertSame( 45, $job->processed_rows );
		$this->assertSame( 5, $job->failed_rows );
	}
}
