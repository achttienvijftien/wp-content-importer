<?php

namespace AchttienVijftien\WpContentImporter\Tests\Unit\Mapping;

use AchttienVijftien\WpContentImporter\FieldProvider\FieldProviderInterface;
use AchttienVijftien\WpContentImporter\Mapping\FieldResolver;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class FieldResolverTest extends TestCase {

	public function test_merges_fields_from_multiple_providers(): void {
		$provider_a = $this->createMock( FieldProviderInterface::class );
		$provider_a->method( 'get_fields' )->willReturn( [
			[ 'name' => 'Title', 'key' => 'post_title', 'type' => 'text', 'group' => 'Core' ],
		] );

		$provider_b = $this->createMock( FieldProviderInterface::class );
		$provider_b->method( 'get_fields' )->willReturn( [
			[ 'name' => 'custom_field', 'key' => 'custom_field', 'type' => 'string', 'group' => 'Post Meta' ],
		] );

		$resolver = new FieldResolver( [ $provider_a, $provider_b ] );
		$fields   = $resolver->resolve( 'post' );

		$this->assertCount( 2, $fields );
		$this->assertSame( 'post_title', $fields[0]['key'] );
		$this->assertSame( 'custom_field', $fields[1]['key'] );
	}

	public function test_returns_empty_array_with_no_providers(): void {
		$resolver = new FieldResolver( [] );

		$this->assertSame( [], $resolver->resolve( 'post' ) );
	}

	public function test_passes_post_type_to_providers(): void {
		$provider = $this->createMock( FieldProviderInterface::class );
		$provider->expects( $this->once() )
			->method( 'get_fields' )
			->with( 'page' )
			->willReturn( [] );

		$resolver = new FieldResolver( [ $provider ] );
		$resolver->resolve( 'page' );
	}

	public function test_create_includes_taxonomy_fields(): void {
		$resolver = FieldResolver::create();
		$fields   = $resolver->resolve( 'post' );
		$keys     = array_column( $fields, 'key' );

		$this->assertContains( 'tax_category', $keys );
		$this->assertContains( 'tax_post_tag', $keys );
	}
}
