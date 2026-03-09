<?php
/**
 * Taxonomy field provider.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\FieldProvider;

/**
 * Provides taxonomy fields for mapping by discovering
 * public taxonomies registered for the given post type.
 */
class TaxonomyFieldProvider implements FieldProviderInterface {

	/**
	 * Get available taxonomy fields for a post type.
	 *
	 * @param string $post_type The post type slug.
	 *
	 * @return array[] List of field definitions.
	 */
	public function get_fields( string $post_type ): array {
		$taxonomies = get_object_taxonomies( $post_type, 'objects' );
		$fields     = [];

		foreach ( $taxonomies as $taxonomy ) {
			if ( ! $taxonomy->public ) {
				continue;
			}

			$fields[] = [
				'name'  => $taxonomy->labels->name,
				'key'   => 'tax_' . $taxonomy->name,
				'type'  => 'taxonomy',
				'group' => 'Taxonomy',
			];
		}

		return $fields;
	}
}
