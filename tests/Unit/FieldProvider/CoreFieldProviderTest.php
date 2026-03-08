<?php

namespace AchttienVijftien\WpContentImporter\Tests\Unit\FieldProvider;

use AchttienVijftien\WpContentImporter\FieldProvider\CoreFieldProvider;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class CoreFieldProviderTest extends TestCase {

	public function test_returns_core_fields_for_post(): void {
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

	public function test_excludes_parent_for_non_hierarchical_type(): void {
		$provider = new CoreFieldProvider();
		$keys     = array_column( $provider->get_fields( 'post' ), 'key' );

		$this->assertNotContains( 'post_parent', $keys );
	}

	public function test_includes_parent_for_hierarchical_type(): void {
		$provider = new CoreFieldProvider();
		$keys     = array_column( $provider->get_fields( 'page' ), 'key' );

		$this->assertContains( 'post_parent', $keys );
	}

	public function test_excludes_unsupported_features(): void {
		register_post_type( 'wci_minimal', [ 'supports' => [ 'title' ] ] );

		$provider = new CoreFieldProvider();
		$keys     = array_column( $provider->get_fields( 'wci_minimal' ), 'key' );

		$this->assertContains( 'post_title', $keys );
		$this->assertNotContains( 'post_content', $keys );
		$this->assertNotContains( 'post_excerpt', $keys );
		$this->assertNotContains( 'post_author', $keys );
		$this->assertNotContains( 'post_parent', $keys );
		// Always-available fields.
		$this->assertContains( 'post_status', $keys );
		$this->assertContains( 'post_date', $keys );
		$this->assertContains( 'post_name', $keys );
	}

	public function test_includes_menu_order_for_page(): void {
		$provider = new CoreFieldProvider();
		$keys     = array_column( $provider->get_fields( 'page' ), 'key' );

		$this->assertContains( 'menu_order', $keys );
	}

	public function test_excludes_menu_order_for_post(): void {
		$provider = new CoreFieldProvider();
		$keys     = array_column( $provider->get_fields( 'post' ), 'key' );

		$this->assertNotContains( 'menu_order', $keys );
	}

	public function test_menu_order_has_integer_type(): void {
		$provider = new CoreFieldProvider();
		$fields   = $provider->get_fields( 'page' );
		$field    = array_values( array_filter( $fields, fn( $f ) => 'menu_order' === $f['key'] ) );

		$this->assertNotEmpty( $field );
		$this->assertSame( 'integer', $field[0]['type'] );
	}
}
