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
	 * Core post fields and the feature they require.
	 *
	 * A null feature means the field is always available.
	 *
	 * @var array[]
	 */
	private const FIELDS = [
		[
			'name'    => 'Title',
			'key'     => 'post_title',
			'type'    => 'text',
			'feature' => 'title',
		],
		[
			'name'    => 'Content',
			'key'     => 'post_content',
			'type'    => 'textarea',
			'feature' => 'editor',
		],
		[
			'name'    => 'Excerpt',
			'key'     => 'post_excerpt',
			'type'    => 'textarea',
			'feature' => 'excerpt',
		],
		[
			'name'    => 'Status',
			'key'     => 'post_status',
			'type'    => 'select',
			'feature' => null,
		],
		[
			'name'    => 'Date',
			'key'     => 'post_date',
			'type'    => 'date',
			'feature' => null,
		],
		[
			'name'    => 'Author',
			'key'     => 'post_author',
			'type'    => 'author',
			'feature' => 'author',
		],
		[
			'name'    => 'Slug',
			'key'     => 'post_name',
			'type'    => 'text',
			'feature' => null,
		],
		[
			'name'    => 'Parent',
			'key'     => 'post_parent',
			'type'    => 'integer',
			'feature' => 'page-attributes',
		],
		[
			'name'    => 'Menu order',
			'key'     => 'menu_order',
			'type'    => 'integer',
			'feature' => 'page-attributes',
		],
		[
			'name'    => 'Post format',
			'key'     => 'post_format',
			'type'    => 'select',
			'feature' => 'post-formats',
		],
	];

	/**
	 * Get core fields for a given post type, filtered by supported features.
	 *
	 * @param string $post_type The post type slug.
	 *
	 * @return array[] List of field definitions.
	 */
	public function get_fields( string $post_type ): array {
		$fields = [];

		foreach ( self::FIELDS as $field ) {
			if ( null !== $field['feature'] && ! post_type_supports( $post_type, $field['feature'] ) ) {
				continue;
			}

			$fields[] = [
				'name'  => $field['name'],
				'key'   => $field['key'],
				'type'  => $field['type'],
				'group' => 'Core',
			];
		}

		return $fields;
	}
}
