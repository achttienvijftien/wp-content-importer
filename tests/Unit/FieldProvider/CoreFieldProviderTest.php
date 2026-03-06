<?php

namespace AchttienVijftien\WpContentImporter\Tests\Unit\FieldProvider;

use AchttienVijftien\WpContentImporter\FieldProvider\CoreFieldProvider;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class CoreFieldProviderTest extends TestCase {

	public function test_returns_core_fields(): void {
		$provider = new CoreFieldProvider();
		$fields   = $provider->get_fields( 'post' );

		$keys = array_column( $fields, 'key' );

		$this->assertContains( 'post_title', $keys );
		$this->assertContains( 'post_content', $keys );
		$this->assertContains( 'post_status', $keys );
		$this->assertContains( 'post_date', $keys );
		$this->assertContains( 'post_author', $keys );
		$this->assertContains( 'post_name', $keys );
	}

	public function test_all_fields_have_core_group(): void {
		$provider = new CoreFieldProvider();
		$fields   = $provider->get_fields( 'post' );

		foreach ( $fields as $field ) {
			$this->assertSame( 'Core', $field['group'] );
		}
	}

	public function test_fields_have_required_keys(): void {
		$provider = new CoreFieldProvider();
		$fields   = $provider->get_fields( 'post' );

		foreach ( $fields as $field ) {
			$this->assertArrayHasKey( 'name', $field );
			$this->assertArrayHasKey( 'key', $field );
			$this->assertArrayHasKey( 'type', $field );
			$this->assertArrayHasKey( 'group', $field );
		}
	}

	public function test_returns_same_fields_for_any_post_type(): void {
		$provider = new CoreFieldProvider();

		$this->assertSame(
			$provider->get_fields( 'post' ),
			$provider->get_fields( 'page' )
		);
	}
}
