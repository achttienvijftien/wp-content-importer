<?php
/**
 * Core field provider.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\FieldProvider;

/**
 * Provides the standard WordPress core post fields for mapping.
 */
class CoreFieldProvider implements FieldProviderInterface {

	/**
	 * Core post fields definition.
	 *
	 * @var array[]
	 */
	private const FIELDS = [
		[
			'name' => 'Title',
			'key'  => 'post_title',
			'type' => 'text',
		],
		[
			'name' => 'Content',
			'key'  => 'post_content',
			'type' => 'textarea',
		],
		[
			'name' => 'Excerpt',
			'key'  => 'post_excerpt',
			'type' => 'textarea',
		],
		[
			'name' => 'Status',
			'key'  => 'post_status',
			'type' => 'select',
		],
		[
			'name' => 'Date',
			'key'  => 'post_date',
			'type' => 'date',
		],
		[
			'name' => 'Author',
			'key'  => 'post_author',
			'type' => 'author',
		],
		[
			'name' => 'Slug',
			'key'  => 'post_name',
			'type' => 'text',
		],
		[
			'name' => 'Parent',
			'key'  => 'post_parent',
			'type' => 'integer',
		],
	];

	/**
	 * Get core fields for a given post type.
	 *
	 * @param string $post_type The post type slug.
	 *
	 * @return array[] List of field definitions.
	 */
	public function get_fields( string $post_type ): array {
		return array_map(
			fn( $field ) => array_merge( $field, [ 'group' => 'Core' ] ),
			self::FIELDS
		);
	}
}
