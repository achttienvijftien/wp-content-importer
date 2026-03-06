<?php
/**
 * Field provider interface.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\FieldProvider;

interface FieldProviderInterface {

	/**
	 * Get available fields for a post type.
	 *
	 * @param string $post_type Post type slug.
	 *
	 * @return array<int, array{name: string, key: string, type: string, group: string}>
	 */
	public function get_fields( string $post_type ): array;
}
