<?php
/**
 * Row processor.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\Import;

use AchttienVijftien\WpContentImporter\FieldProvider\AcfFieldProvider;
use AchttienVijftien\WpContentImporter\Mapping\ModifierPipeline;
use AchttienVijftien\WpContentImporter\Mapping\ModifierRegistry;

/**
 * Processes a single import row: maps values, creates or updates posts,
 * and sets ACF and meta fields.
 */
class RowProcessor {

	/**
	 * WordPress core post fields supported for mapping.
	 *
	 * @var string[]
	 */
	private const CORE_FIELDS = [
		'post_title',
		'post_content',
		'post_excerpt',
		'post_status',
		'post_date',
		'post_author',
		'post_name',
		'post_parent',
		'menu_order',
	];

	/**
	 * Valid post statuses for import.
	 *
	 * @var string[]
	 */
	private const VALID_STATUSES = [
		'publish',
		'draft',
		'pending',
		'private',
		'future',
	];

	private ModifierPipeline $pipeline;

	public function __construct( ?ModifierPipeline $pipeline = null ) {
		$this->pipeline = $pipeline ?? new ModifierPipeline( ModifierRegistry::instance() );
	}

	/**
	 * Process a single import row against the given job configuration.
	 *
	 * @param ImportRow $row The row to process.
	 * @param ImportJob $job The parent import job.
	 *
	 * @return void
	 */
	public function process( ImportRow $row, ImportJob $job ): void {
		try {
			do_action( 'wci_before_process_row', $row, $job );

			$mapped = $this->map_values( $row->data, $job->mapping );
			$mapped = apply_filters( 'wci_mapped_values', $mapped, $row->data, $job );

			$post_args = $this->build_post_args( $mapped, $job->post_type );
			$post_args = apply_filters( 'wci_post_args', $post_args, $mapped, $job );

			$post_id = $this->resolve_post( $post_args, $mapped, $job );

			if ( null === $post_id ) {
				$row->mark_failed( 'No matching post found and mode is update-only.' );
				return;
			}

			$this->set_acf_fields( $post_id, $mapped );
			$this->set_meta_fields( $post_id, $mapped );

			do_action( 'wci_after_process_row', $post_id, $mapped, $row, $job );

			$row->mark_done( $post_id );
		} catch ( \Throwable $e ) {
			$row->mark_failed( $e->getMessage() );
		}
	}

	/**
	 * Map raw row data to target fields using the job mapping.
	 *
	 * Each mapping entry is keyed by target field and contains a template
	 * string where {column_name} placeholders are replaced with row values,
	 * and a field type.
	 *
	 * @param array $data    Raw row data keyed by column name.
	 * @param array $mapping Target-to-template mapping configuration.
	 *
	 * @return array Mapped field data with value and type.
	 */
	private function map_values( array $data, array $mapping ): array {
		$mapped = [];

		foreach ( $mapping as $target_key => $config ) {
			$template = $config['template'] ?? '';
			$type     = $config['type'] ?? 'text';

			// Replace {column_name} placeholders with actual row values.
			$value = preg_replace_callback(
				'/\{([^{}]*(?:\{[^}]*\}[^{}]*)*)\}/',
				function ( $matches ) use ( $data ) {
					return $this->pipeline->process( $matches[1], $data );
				},
				$template
			);

			$value = apply_filters( 'wci_mapped_value', $value, $target_key, $data, $template );

			if ( '' === $value ) {
				continue;
			}

			$mapped[ $target_key ] = [
				'value' => $value,
				'type'  => $type,
			];
		}

		return $mapped;
	}

	/**
	 * Build wp_insert_post / wp_update_post arguments from mapped data.
	 *
	 * @param array  $mapped    Mapped field data.
	 * @param string $post_type Target post type.
	 *
	 * @return array Post arguments suitable for wp_insert_post.
	 */
	private function build_post_args( array $mapped, string $post_type ): array {
		$args = [ 'post_type' => $post_type ];

		foreach ( self::CORE_FIELDS as $field ) {
			if ( ! isset( $mapped[ $field ] ) ) {
				continue;
			}

			$value = $mapped[ $field ]['value'];

			if ( '' === $value ) {
				continue;
			}

			$args[ $field ] = match ( $field ) {
				'post_date'   => gmdate( 'Y-m-d H:i:s', strtotime( $value ) ),
				'post_author' => $this->resolve_author( $value ),
				'post_status' => $this->validate_status( $value ),
				'post_parent',
				'menu_order'  => (int) $value,
				default       => $value,
			};
		}

		return $args;
	}

	/**
	 * Create or find an existing post depending on the job mode.
	 *
	 * @param array     $post_args Post arguments.
	 * @param array     $mapped    Mapped field data.
	 * @param ImportJob $job       The parent import job.
	 *
	 * @return int|null Post ID on success, null if no match found in update mode.
	 *
	 * @throws \RuntimeException When post insertion or update fails.
	 */
	private function resolve_post( array $post_args, array $mapped, ImportJob $job ): ?int {
		if ( 'create' === $job->mode ) {
			return $this->create_post( $post_args );
		}

		// Update or upsert mode — find existing post.
		$existing_id = $this->find_existing_post( $job, $post_args, $mapped );

		if ( ! $existing_id && 'upsert' === $job->mode ) {
			return $this->create_post( $post_args );
		}

		if ( ! $existing_id ) {
			return null;
		}

		$post_args['ID'] = $existing_id;

		// Remove empty values to avoid overwriting with blank.
		$post_args = array_filter( $post_args, fn( $v ) => '' !== $v && null !== $v );

		$result = wp_update_post( $post_args, true );

		if ( is_wp_error( $result ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \RuntimeException( $result->get_error_message() );
		}

		return $existing_id;
	}

	/**
	 * Insert a new post.
	 *
	 * @param array $post_args Post arguments for wp_insert_post.
	 *
	 * @return int The new post ID.
	 *
	 * @throws \RuntimeException When post insertion fails.
	 */
	private function create_post( array $post_args ): int {
		$post_id = wp_insert_post( $post_args, true );

		if ( is_wp_error( $post_id ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \RuntimeException( $post_id->get_error_message() );
		}

		return $post_id;
	}

	/**
	 * Find an existing post by the configured match field.
	 *
	 * @param ImportJob $job       The parent import job.
	 * @param array     $post_args Post arguments containing potential match values.
	 * @param array     $mapped    Mapped field data.
	 *
	 * @return int|null Existing post ID, or null if not found.
	 */
	private function find_existing_post( ImportJob $job, array $post_args, array $mapped ): ?int {
		$match_field = $job->match_field;

		if ( ! $match_field ) {
			return null;
		}

		if ( 'post_name' === $match_field && ! empty( $post_args['post_name'] ) ) {
			$posts = get_posts(
				[
					'name'        => $post_args['post_name'],
					'post_type'   => $job->post_type,
					'post_status' => 'any',
					'numberposts' => 1,
					'fields'      => 'ids',
				]
			);

			return $posts[0] ?? null;
		}

		if ( 'ID' === $match_field && ! empty( $post_args['ID'] ) ) {
			$post = get_post( (int) $post_args['ID'] );

			return $post ? $post->ID : null;
		}

		// Other core post field match — query the posts table directly.
		if ( in_array( $match_field, self::CORE_FIELDS, true ) ) {
			$match_value = $post_args[ $match_field ] ?? null;

			if ( ! $match_value ) {
				return null;
			}

			global $wpdb;

			$column = sanitize_key( $match_field );

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $column is sanitized and validated against CORE_FIELDS whitelist.
			$post_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} WHERE `{$column}` = %s AND post_type = %s AND post_status != %s LIMIT 1",
					$match_value,
					$job->post_type,
					'trash'
				)
			);

			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			return $post_id ? (int) $post_id : null;
		}

		// Meta field match — use mapped data which includes non-core fields.
		$match_value = isset( $mapped[ $match_field ] ) ? $mapped[ $match_field ]['value'] : null;

		if ( $match_value ) {
			$posts = get_posts(
				[
					'post_type'   => $job->post_type,
					'post_status' => 'any',
					'numberposts' => 1,
					'fields'      => 'ids',
					'meta_query'  => [
						[
							'key'   => $match_field,
							'value' => $match_value,
						],
					],
				]
			);

			return $posts[0] ?? null;
		}

		return null;
	}

	/**
	 * Set ACF field values on the post.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $mapped  Mapped field data.
	 *
	 * @return void
	 */
	private function set_acf_fields( int $post_id, array $mapped ): void {
		if ( ! AcfFieldProvider::is_available() ) {
			return;
		}

		foreach ( $mapped as $key => $entry ) {
			// ACF field keys start with 'field_'.
			if ( ! str_starts_with( $key, 'field_' ) ) {
				continue;
			}

			if ( '' === $entry['value'] ) {
				continue;
			}

			update_field( $key, $entry['value'], $post_id );
		}
	}

	/**
	 * Set generic post meta field values on the post.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $mapped  Mapped field data.
	 *
	 * @return void
	 */
	private function set_meta_fields( int $post_id, array $mapped ): void {
		foreach ( $mapped as $key => $entry ) {
			// Skip core fields and ACF fields.
			if ( in_array( $key, self::CORE_FIELDS, true ) || str_starts_with( $key, 'field_' ) ) {
				continue;
			}

			if ( '' === $entry['value'] ) {
				continue;
			}

			update_post_meta( $post_id, sanitize_text_field( $key ), $entry['value'] );
		}
	}

	/**
	 * Resolve an author value to a user ID.
	 *
	 * @param string $value Author identifier (ID, login, or email).
	 *
	 * @return int Resolved user ID, or current user ID as fallback.
	 */
	private function resolve_author( string $value ): int {
		if ( is_numeric( $value ) ) {
			$user = get_user_by( 'id', (int) $value );

			return $user ? $user->ID : get_current_user_id();
		}

		$user = get_user_by( 'login', $value );

		if ( ! $user ) {
			$user = get_user_by( 'email', $value );
		}

		return $user ? $user->ID : get_current_user_id();
	}

	/**
	 * Validate and normalise a post status value.
	 *
	 * @param string $value The raw status value.
	 *
	 * @return string Valid post status, defaults to 'draft'.
	 */
	private function validate_status( string $value ): string {
		$value = strtolower( trim( $value ) );

		return in_array( $value, self::VALID_STATUSES, true ) ? $value : 'draft';
	}
}
