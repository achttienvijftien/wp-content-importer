<?php
/**
 * Import job model.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\Import;

use AchttienVijftien\WpContentImporter\Database\Migrator;

/**
 * Import job model.
 *
 * Represents a single content import job stored in the database.
 */
class ImportJob {

	/**
	 * The job ID.
	 *
	 * @var int
	 */
	public int $id;

	/**
	 * The job name.
	 *
	 * @var string
	 */
	public string $name;

	/**
	 * The job status.
	 *
	 * @var string
	 */
	public string $status;

	/**
	 * The target post type.
	 *
	 * @var string
	 */
	public string $post_type;

	/**
	 * The import mode (create or update).
	 *
	 * @var string
	 */
	public string $mode;

	/**
	 * The field used to match existing posts during update mode.
	 *
	 * @var string|null
	 */
	public ?string $match_field;

	/**
	 * The column-to-field mapping configuration.
	 *
	 * @var array|null
	 */
	public ?array $mapping;

	/**
	 * The total number of rows in this job.
	 *
	 * @var int
	 */
	public int $total_rows;

	/**
	 * The number of rows that have been processed.
	 *
	 * @var int
	 */
	public int $processed_rows;

	/**
	 * The number of rows that failed processing.
	 *
	 * @var int
	 */
	public int $failed_rows;

	/**
	 * The creation timestamp.
	 *
	 * @var string
	 */
	public string $created_at;

	/**
	 * The last updated timestamp.
	 *
	 * @var string
	 */
	public string $updated_at;

	/**
	 * Constructor.
	 *
	 * @param object $row The database row object.
	 */
	public function __construct( object $row ) {
		$this->id             = (int) $row->id;
		$this->name           = $row->name ?? '';
		$this->status         = $row->status;
		$this->post_type      = $row->post_type;
		$this->mode           = $row->mode;
		$this->match_field    = $row->match_field;
		$this->mapping        = $row->mapping ? json_decode( $row->mapping, true ) : null;
		$this->total_rows     = (int) $row->total_rows;
		$this->processed_rows = (int) $row->processed_rows;
		$this->failed_rows    = (int) $row->failed_rows;
		$this->created_at     = $row->created_at;
		$this->updated_at     = $row->updated_at;
	}

	/**
	 * Create a new import job.
	 *
	 * @param array $data Optional job data to override defaults.
	 * @return self The newly created import job.
	 */
	public static function create( array $data = [] ): self {
		global $wpdb;

		$defaults = [
			'name'      => '',
			'status'    => 'draft',
			'post_type' => '',
			'mode'      => 'create',
		];

		$data = array_merge( $defaults, $data );
		$wpdb->insert( Migrator::jobs_table(), $data );

		return self::find( (int) $wpdb->insert_id );
	}

	/**
	 * Find an import job by its ID.
	 *
	 * @param int $id The job ID.
	 * @return self|null The import job, or null if not found.
	 */
	public static function find( int $id ): ?self {
		global $wpdb;

		$table = Migrator::jobs_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safely built via Migrator helper.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );

		return $row ? new self( $row ) : null;
	}

	/**
	 * Find the next pending or processing import job.
	 *
	 * @return self|null The next pending job, or null if none found.
	 */
	public static function find_next_pending(): ?self {
		global $wpdb;

		$table = Migrator::jobs_table();
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name is safely built via Migrator helper.
		$row = $wpdb->get_row(
			"SELECT * FROM {$table} WHERE status IN ('pending', 'processing') ORDER BY created_at ASC LIMIT 1"
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		return $row ? new self( $row ) : null;
	}

	/**
	 * Retrieve all import jobs with pagination.
	 *
	 * @param int $per_page Number of jobs per page.
	 * @param int $offset   Offset for pagination.
	 * @return array Array of ImportJob instances.
	 */
	public static function all( int $per_page = 20, int $offset = 0 ): array {
		global $wpdb;

		$table = Migrator::jobs_table();
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safely built via Migrator helper.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array_map( fn( $row ) => new self( $row ), $rows );
	}

	/**
	 * Count the total number of import jobs.
	 *
	 * @return int The total job count.
	 */
	public static function count(): int {
		global $wpdb;

		$table = Migrator::jobs_table();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name is safely built via Migrator helper.
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	/**
	 * Update the import job with the given data.
	 *
	 * @param array $data Key-value pairs of fields to update.
	 * @return void
	 */
	public function update( array $data ): void {
		global $wpdb;

		$wpdb->update( Migrator::jobs_table(), $data, [ 'id' => $this->id ] );

		foreach ( $data as $key => $value ) {
			if ( 'mapping' === $key && is_string( $value ) ) {
				$this->mapping = json_decode( $value, true );
			} elseif ( property_exists( $this, $key ) ) {
				$this->$key = $value;
			}
		}
	}

	/**
	 * Delete the import job and all associated rows.
	 *
	 * @return void
	 */
	public function delete(): void {
		global $wpdb;

		$wpdb->delete( Migrator::rows_table(), [ 'job_id' => $this->id ] );
		$wpdb->delete( Migrator::jobs_table(), [ 'id' => $this->id ] );
	}
}
