<?php

namespace AchttienVijftien\WpContentImporter\Tests\Integration\Mapping;

use AchttienVijftien\WpContentImporter\Mapping\Template;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class TemplateTest extends TestCase {

	protected function set_up(): void {
		parent::set_up();
		Template::register_post_type();
	}

	protected function tear_down(): void {
		$posts = get_posts(
			[
				'post_type'   => Template::POST_TYPE,
				'numberposts' => -1,
				'fields'      => 'ids',
			]
		);

		foreach ( $posts as $id ) {
			wp_delete_post( $id, true );
		}

		parent::tear_down();
	}

	public function test_save_creates_template(): void {
		$mapping = [ 'post_title' => [ 'template' => '{name}', 'type' => 'text' ] ];
		$id      = Template::save( 'My Template', 'post', 'create', null, $mapping );

		$this->assertGreaterThan( 0, $id );

		$template = Template::get( $id );

		$this->assertSame( 'My Template', $template['name'] );
		$this->assertSame( 'post', $template['post_type'] );
		$this->assertSame( $mapping, $template['mapping'] );
	}

	public function test_exists_returns_false_when_no_template(): void {
		$this->assertFalse( Template::exists( 'Nonexistent' ) );
	}

	public function test_exists_returns_true_for_saved_template(): void {
		Template::save( 'Unique Name', 'post', 'create', null, [] );

		$this->assertTrue( Template::exists( 'Unique Name' ) );
	}

	public function test_exists_is_case_insensitive(): void {
		Template::save( 'My Template', 'post', 'create', null, [] );

		$this->assertTrue( Template::exists( 'My Template' ) );
		$this->assertTrue( Template::exists( 'my template' ) );
	}

	public function test_get_all_returns_saved_templates(): void {
		Template::save( 'First', 'post', 'create', null, [] );
		Template::save( 'Second', 'page', 'update', 'post_name', [] );

		$all = Template::get_all();

		$this->assertCount( 2, $all );

		$names = array_column( $all, 'name' );
		$this->assertContains( 'First', $names );
		$this->assertContains( 'Second', $names );
	}

	public function test_delete_removes_template(): void {
		$id = Template::save( 'To Delete', 'post', 'create', null, [] );

		Template::delete( $id );

		$this->assertNull( Template::get( $id ) );
		$this->assertFalse( Template::exists( 'To Delete' ) );
	}
}
