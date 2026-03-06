<?php
/**
 * Registered meta field provider.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\FieldProvider;

/**
 * Provides registered post meta fields for mapping, with optional prefix exclusion.
 */
class RegisteredMetaProvider implements FieldProviderInterface {

	/**
	 * Prefixes to exclude from field listing.
	 *
	 * @var string[]
	 */
	private array $exclude_prefixes;

	/**
	 * Constructor.
	 *
	 * @param string[] $exclude_prefixes Meta key prefixes to exclude.
	 */
	public function __construct( array $exclude_prefixes = [] ) {
		$this->exclude_prefixes = $exclude_prefixes;
	}

	/**
	 * Get registered meta fields for a given post type.
	 *
	 * @param string $post_type The post type slug.
	 *
	 * @return array[] List of field definitions.
	 */
	public function get_fields( string $post_type ): array {
		$meta_keys = get_registered_meta_keys( 'post', $post_type );
		$global    = get_registered_meta_keys( 'post', '' );
		$all       = array_merge( $global, $meta_keys );

		$fields = [];

		foreach ( $all as $key => $args ) {
			if ( $this->is_excluded( $key ) ) {
				continue;
			}

			$fields[] = [
				'name'  => $key,
				'key'   => $key,
				'type'  => $args['type'] ?? 'string',
				'group' => 'Post Meta',
			];
		}

		return $fields;
	}

	/**
	 * Check whether a meta key should be excluded.
	 *
	 * @param string $key The meta key to check.
	 *
	 * @return bool True if the key matches an excluded prefix.
	 */
	private function is_excluded( string $key ): bool {
		foreach ( $this->exclude_prefixes as $prefix ) {
			if ( str_starts_with( $key, $prefix ) ) {
				return true;
			}
		}

		return false;
	}
}
