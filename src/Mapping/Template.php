<?php
/**
 * Mapping template model.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\Mapping;

/**
 * Model for persisting and retrieving import mapping templates as custom post types.
 */
class Template {

	/**
	 * Custom post type slug for templates.
	 *
	 * @var string
	 */
	public const POST_TYPE = 'import_template';

	/**
	 * Meta key for the field mapping data.
	 *
	 * @var string
	 */
	public const META_MAPPING = '_wci_mapping';

	/**
	 * Meta key for the target post type.
	 *
	 * @var string
	 */
	public const META_POST_TYPE = '_wci_post_type';

	/**
	 * Meta key for the import mode.
	 *
	 * @var string
	 */
	public const META_MODE = '_wci_mode';

	/**
	 * Meta key for the match field.
	 *
	 * @var string
	 */
	public const META_MATCH = '_wci_match_field';

	/**
	 * Register the custom post type used to store import templates.
	 *
	 * @return void
	 */
	public static function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'       => [
					'name'          => __( 'Import Templates', 'wp-content-importer' ),
					'singular_name' => __( 'Import Template', 'wp-content-importer' ),
				],
				'public'       => false,
				'show_ui'      => false,
				'supports'     => [ 'title' ],
				'map_meta_cap' => true,
			]
		);
	}

	/**
	 * Save a new import template.
	 *
	 * @param string      $name        Template name.
	 * @param string      $post_type   Target post type.
	 * @param string      $mode        Import mode (create or update).
	 * @param string|null $match_field Field used for matching in update mode.
	 * @param array       $mapping     Field mapping data.
	 *
	 * @return int The new post ID.
	 */
	public static function save( string $name, string $post_type, string $mode, ?string $match_field, array $mapping ): int {
		$post_id = wp_insert_post(
			[
				'post_type'   => self::POST_TYPE,
				'post_title'  => $name,
				'post_status' => 'publish',
			]
		);

		update_post_meta( $post_id, self::META_MAPPING, $mapping );
		update_post_meta( $post_id, self::META_POST_TYPE, $post_type );
		update_post_meta( $post_id, self::META_MODE, $mode );
		update_post_meta( $post_id, self::META_MATCH, $match_field );

		return $post_id;
	}

	/**
	 * Retrieve all saved import templates.
	 *
	 * @return array[] List of template data arrays.
	 */
	public static function get_all(): array {
		$posts = get_posts(
			[
				'post_type'   => self::POST_TYPE,
				'numberposts' => -1,
				'orderby'     => 'title',
				'order'       => 'ASC',
			]
		);

		return array_map(
			fn( $post ) => [
				'id'          => $post->ID,
				'name'        => $post->post_title,
				'post_type'   => get_post_meta( $post->ID, self::META_POST_TYPE, true ),
				'mode'        => get_post_meta( $post->ID, self::META_MODE, true ),
				'match_field' => get_post_meta( $post->ID, self::META_MATCH, true ),
				'mapping'     => get_post_meta( $post->ID, self::META_MAPPING, true ),
			],
			$posts
		);
	}

	/**
	 * Retrieve a single template by ID.
	 *
	 * @param int $id The template post ID.
	 *
	 * @return array|null Template data array, or null if not found.
	 */
	public static function get( int $id ): ?array {
		$post = get_post( $id );

		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return null;
		}

		return [
			'id'          => $post->ID,
			'name'        => $post->post_title,
			'post_type'   => get_post_meta( $post->ID, self::META_POST_TYPE, true ),
			'mode'        => get_post_meta( $post->ID, self::META_MODE, true ),
			'match_field' => get_post_meta( $post->ID, self::META_MATCH, true ),
			'mapping'     => get_post_meta( $post->ID, self::META_MAPPING, true ),
		];
	}

	/**
	 * Delete a template by ID.
	 *
	 * @param int $id The template post ID.
	 *
	 * @return void
	 */
	public static function delete( int $id ): void {
		wp_delete_post( $id, true );
	}
}
