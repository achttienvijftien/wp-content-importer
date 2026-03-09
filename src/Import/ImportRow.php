<?php
/**
 * Import row model.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\Import;

use AchttienVijftien\WpContentImporter\Database\Migrator;

/**
 * Import row model.
 *
 * Represents a single data row within an import job.
 */
class ImportRow {

	/**
	 * The row ID.
	 *
	 * @var int
	 */
	public int $id;

	/**
	 * The parent job ID.
	 *
	 * @var int
	 */
	public int $job_id;

	/**
	 * The row data decoded from JSON.
	 *
	 * @var array|null
	 */
	public ?array $data;

	/**
	 * The row processing status.
	 *
	 * @var string
	 */
	public string $status;

	/**
	 * The WordPress post ID created or updated for this row.
	 *
	 * @var int|null
	 */
	public ?int $post_id;

	/**
	 * The error message if processing failed.
	 *
	 * @var string|null
	 */
	public ?string $error;

	/**
	 * The creation timestamp.
	 *
	 * @var string
	 */
	public string $created_at;

	/**
	 * Constructor.
	 *
	 * @param object $row The database row object.
	 */
	public function __construct( object $row ) {
		$this->id         = (int) $row->id;
		$this->job_id     = (int) $row->job_id;
		$this->data       = $row->data ? json_decode( $row->data, true ) : null;
		$this->status     = $row->status;
		$this->post_id    = $row->post_id ? (int) $row->post_id : null;
		$this->error      = $row->error;
		$this->created_at = $row->created_at;
	}

	/**
	 * Bulk insert rows for a given job.
	 *
	 * @param int   $job_id The parent job ID.
	 * @param array $rows   Array of row data arrays to insert.
	 * @return int The number of rows inserted.
	 */
	public static function bulk_insert( int $job_id, array $rows ): int {
		global $wpdb;

		$table  = Migrator::rows_table();
		$values = [];
		$count  = 0;

		foreach ( $rows as $row_data ) {
			$values[] = $wpdb->prepare(
				'(%d, %s, %s)',
				$job_id,
				wp_json_encode( $row_data ),
				'pending'
			);

			++$count;

			// Insert in batches of 100 to avoid query size limits.
			if ( 0 === $count % 100 ) {
				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name is safely built via Migrator helper; values are prepared above.
				$wpdb->query(
					"INSERT INTO {$table} (job_id, data, status) VALUES " . implode( ', ', $values )
				);
				// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
				$values = [];
			}
		}

		if ( ! empty( $values ) ) {
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name is safely built via Migrator helper; values are prepared above.
			$wpdb->query(
				"INSERT INTO {$table} (job_id, data, status) VALUES " . implode( ', ', $values )
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		}

		return $count;
	}

	/**
	 * Get a batch of pending rows for a given job.
	 *
	 * @param int $job_id The parent job ID.
	 * @param int $limit  Maximum number of rows to retrieve.
	 * @return array Array of ImportRow instances.
	 */
	public static function get_pending_batch( int $job_id, int $limit = 50 ): array {
		global $wpdb;

		$table = Migrator::rows_table();
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safely built via Migrator helper.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE job_id = %d AND status = 'pending' ORDER BY id ASC LIMIT %d",
				$job_id,
				$limit
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array_map( fn( $row ) => new self( $row ), $rows );
	}

	/**
	 * Get rows for a given job with pagination.
	 *
	 * @param int $job_id   The parent job ID.
	 * @param int $per_page Number of rows per page.
	 * @param int $offset   Offset for pagination.
	 * @return array Array of ImportRow instances.
	 */
	public static function get_by_job( int $job_id, int $per_page = 50, int $offset = 0 ): array {
		global $wpdb;

		$table = Migrator::rows_table();
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safely built via Migrator helper.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE job_id = %d ORDER BY id ASC LIMIT %d OFFSET %d",
				$job_id,
				$per_page,
				$offset
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array_map( fn( $row ) => new self( $row ), $rows );
	}

	/**
	 * Mark this row as done with the given post ID.
	 *
	 * @param int $post_id The WordPress post ID that was created or updated.
	 * @return void
	 */
	public function mark_done( int $post_id ): void {
		global $wpdb;

		$wpdb->update(
			Migrator::rows_table(),
			[
				'status'  => 'done',
				'post_id' => $post_id,
			],
			[ 'id' => $this->id ]
		);

		$this->status  = 'done';
		$this->post_id = $post_id;
	}

	/**
	 * Mark this row as partially successful.
	 *
	 * The post was created or updated, but some secondary operations
	 * (e.g. taxonomy term assignment) did not fully succeed.
	 *
	 * @param int    $post_id The WordPress post ID that was created or updated.
	 * @param string $error   Description of what partially failed.
	 * @return void
	 */
	public function mark_partial( int $post_id, string $error ): void {
		global $wpdb;

		$wpdb->update(
			Migrator::rows_table(),
			[
				'status'  => 'partial',
				'post_id' => $post_id,
				'error'   => $error,
			],
			[ 'id' => $this->id ]
		);

		$this->status  = 'partial';
		$this->post_id = $post_id;
		$this->error   = $error;
	}

	/**
	 * Mark this row as failed with the given error message.
	 *
	 * @param string $error The error message describing the failure.
	 * @return void
	 */
	public function mark_failed( string $error ): void {
		global $wpdb;

		$wpdb->update(
			Migrator::rows_table(),
			[
				'status' => 'failed',
				'error'  => $error,
			],
			[ 'id' => $this->id ]
		);

		$this->status = 'failed';
		$this->error  = $error;
	}

	/**
	 * Reset all rows for a job back to pending.
	 *
	 * @param int $job_id The job ID.
	 *
	 * @return void
	 */
	public static function reset_by_job( int $job_id ): void {
		global $wpdb;

		$wpdb->update(
			Migrator::rows_table(),
			[
				'status'  => 'pending',
				'post_id' => null,
				'error'   => null,
			],
			[ 'job_id' => $job_id ]
		);
	}
}
