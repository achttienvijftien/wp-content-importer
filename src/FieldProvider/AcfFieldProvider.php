<?php
/**
 * ACF field provider adapter.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\FieldProvider;

/**
 * Provides ACF (Advanced Custom Fields) fields for mapping.
 */
class AcfFieldProvider implements FieldProviderInterface {

	/**
	 * ACF field types supported for import mapping.
	 *
	 * @var string[]
	 */
	private const SUPPORTED_TYPES = [
		'text',
		'number',
		'email',
		'url',
		'textarea',
		'wysiwyg',
		'select',
		'date_picker',
		'true_false',
		'image',
	];

	/**
	 * Check whether ACF is active and available.
	 *
	 * @return bool True if ACF functions are available.
	 */
	public static function is_available(): bool {
		return function_exists( 'acf_get_field_groups' );
	}

	/**
	 * Get ACF fields for a given post type.
	 *
	 * @param string $post_type The post type slug.
	 *
	 * @return array[] List of field definitions.
	 */
	public function get_fields( string $post_type ): array {
		if ( ! self::is_available() ) {
			return [];
		}

		$groups = acf_get_field_groups( [ 'post_type' => $post_type ] );
		$fields = [];

		foreach ( $groups as $group ) {
			$group_fields = acf_get_fields( $group['key'] );

			if ( ! is_array( $group_fields ) ) {
				continue;
			}

			foreach ( $group_fields as $field ) {
				if ( ! in_array( $field['type'], self::SUPPORTED_TYPES, true ) ) {
					continue;
				}

				$fields[] = [
					'name'  => $field['label'],
					'key'   => $field['key'],
					'type'  => $field['type'],
					'group' => 'ACF: ' . $group['title'],
				];
			}
		}

		return $fields;
	}

	/**
	 * Get meta keys managed by ACF for a given post type.
	 *
	 * @param string $post_type The post type slug.
	 *
	 * @return string[] List of meta keys.
	 */
	public function get_meta_keys( string $post_type ): array {
		$fields = $this->get_fields( $post_type );

		return array_map(
			fn( $field ) => preg_replace( '/^field_/', '', $field['key'] ),
			$fields
		);
	}
}
