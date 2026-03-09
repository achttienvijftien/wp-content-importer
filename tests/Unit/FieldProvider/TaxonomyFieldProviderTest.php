<?php

namespace AchttienVijftien\WpContentImporter\Tests\Unit\FieldProvider;

use AchttienVijftien\WpContentImporter\FieldProvider\TaxonomyFieldProvider;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class TaxonomyFieldProviderTest extends TestCase {

	public function test_returns_taxonomies_for_post(): void {
		$provider = new TaxonomyFieldProvider();
		$fields   = $provider->get_fields( 'post' );
		$keys     = array_column( $fields, 'key' );

		$this->assertContains( 'tax_category', $keys );
		$this->assertContains( 'tax_post_tag', $keys );
	}

	public function test_excludes_non_public_taxonomies(): void {
		register_taxonomy( 'wci_internal', 'post', [ 'public' => false ] );

		$provider = new TaxonomyFieldProvider();
		$keys     = array_column( $provider->get_fields( 'post' ), 'key' );

		$this->assertNotContains( 'tax_wci_internal', $keys );
	}

	public function test_returns_empty_for_type_with_no_taxonomies(): void {
		register_post_type( 'wci_no_tax', [ 'supports' => [ 'title' ] ] );

		$provider = new TaxonomyFieldProvider();
		$fields   = $provider->get_fields( 'wci_no_tax' );

		$this->assertEmpty( $fields );
	}

	public function test_fields_have_taxonomy_group(): void {
		$provider = new TaxonomyFieldProvider();
		$fields   = $provider->get_fields( 'post' );

		foreach ( $fields as $field ) {
			$this->assertSame( 'Taxonomy', $field['group'] );
		}
	}

	public function test_fields_have_required_keys(): void {
		$provider = new TaxonomyFieldProvider();
		$fields   = $provider->get_fields( 'post' );

		foreach ( $fields as $field ) {
			$this->assertArrayHasKey( 'name', $field );
			$this->assertArrayHasKey( 'key', $field );
			$this->assertArrayHasKey( 'type', $field );
			$this->assertArrayHasKey( 'group', $field );
		}
	}

	public function test_fields_have_taxonomy_type(): void {
		$provider = new TaxonomyFieldProvider();
		$fields   = $provider->get_fields( 'post' );

		foreach ( $fields as $field ) {
			$this->assertSame( 'taxonomy', $field['type'] );
		}
	}

	public function test_uses_taxonomy_label_as_name(): void {
		$provider = new TaxonomyFieldProvider();
		$fields   = $provider->get_fields( 'post' );

		$category = array_values( array_filter( $fields, fn( $f ) => 'tax_category' === $f['key'] ) );

		$this->assertNotEmpty( $category );
		$this->assertSame( 'Categories', $category[0]['name'] );
	}

	public function test_discovers_custom_taxonomy(): void {
		register_taxonomy( 'wci_city', 'post', [
			'public' => true,
			'labels' => [ 'name' => 'Cities' ],
		] );

		$provider = new TaxonomyFieldProvider();
		$fields   = $provider->get_fields( 'post' );
		$city     = array_values( array_filter( $fields, fn( $f ) => 'tax_wci_city' === $f['key'] ) );

		$this->assertNotEmpty( $city );
		$this->assertSame( 'Cities', $city[0]['name'] );
	}
}
