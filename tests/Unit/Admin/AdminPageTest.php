<?php

namespace AchttienVijftien\WpContentImporter\Tests\Unit\Admin;

use AchttienVijftien\WpContentImporter\Admin\AdminPage;
use AchttienVijftien\WpContentImporter\Import\ImportJob;
use ReflectionMethod;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class AdminPageTest extends TestCase {

	private AdminPage $page;
	private ReflectionMethod $get_job_data;

	protected function set_up(): void {
		parent::set_up();
		$this->page         = new AdminPage();
		$this->get_job_data = new ReflectionMethod( AdminPage::class, 'get_job_data' );
	}

	private function make_job( array $overrides = [] ): ImportJob {
		$row = (object) array_merge(
			[
				'id'             => 1,
				'name'           => 'test.csv',
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

		return new ImportJob( $row );
	}

	public function test_get_job_data_returns_all_keys(): void {
		$job  = $this->make_job();
		$data = $this->get_job_data->invoke( $this->page, $job );

		$this->assertArrayHasKey( 'name', $data );
		$this->assertArrayHasKey( 'post_type', $data );
		$this->assertArrayHasKey( 'mode', $data );
		$this->assertArrayHasKey( 'match_field', $data );
		$this->assertArrayHasKey( 'mapping', $data );
		$this->assertArrayHasKey( 'total_rows', $data );
	}

	public function test_get_job_data_maps_values_correctly(): void {
		$mapping = [ 'post_title' => [ 'template' => '{name}', 'type' => 'text' ] ];
		$job     = $this->make_job(
			[
				'name'        => 'My Import',
				'post_type'   => 'page',
				'mode'        => 'update',
				'match_field' => 'post_name',
				'mapping'     => wp_json_encode( $mapping ),
				'total_rows'  => 25,
			]
		);

		$data = $this->get_job_data->invoke( $this->page, $job );

		$this->assertSame( 'My Import', $data['name'] );
		$this->assertSame( 'page', $data['post_type'] );
		$this->assertSame( 'update', $data['mode'] );
		$this->assertSame( 'post_name', $data['match_field'] );
		$this->assertSame( $mapping, $data['mapping'] );
		$this->assertSame( 25, $data['total_rows'] );
	}

	public function test_get_job_data_null_mapping(): void {
		$job  = $this->make_job( [ 'mapping' => null ] );
		$data = $this->get_job_data->invoke( $this->page, $job );

		$this->assertNull( $data['mapping'] );
	}
}
