<?php
/**
 * WP-Cron batch processing handler.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\Import;

/**
 * Manages WP-Cron scheduling and batch processing of import jobs.
 */
class CronHandler {

	/**
	 * WP-Cron hook name for import processing.
	 *
	 * @var string
	 */
	public const CRON_HOOK = 'wci_process_import';

	/**
	 * Number of rows to process per cron batch.
	 *
	 * @var int
	 */
	public const BATCH_SIZE = 50;

	/**
	 * Constructor. Registers cron action and schedule hooks.
	 */
	public function __construct() {
		add_action( self::CRON_HOOK, [ $this, 'process_batch' ] );
		add_action( 'init', [ $this, 'schedule_cron' ] );
	}

	/**
	 * Schedule the recurring cron event if not already scheduled.
	 *
	 * @return void
	 */
	public function schedule_cron(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			// phpcs:ignore WordPress.WP.CronInterval.ChangeDetected
			wp_schedule_event( time(), 'every_minute', self::CRON_HOOK );
		}
	}

	/**
	 * Register a custom 'every_minute' cron interval.
	 *
	 * @param array $schedules Existing cron schedules.
	 *
	 * @return array Modified cron schedules.
	 */
	public static function register_interval( array $schedules ): array {
		$schedules['every_minute'] = [
			'interval' => 60,
			'display'  => __( 'Every Minute', 'wp-content-importer' ),
		];

		return $schedules;
	}

	/**
	 * Process a batch of pending import rows for the next available job.
	 *
	 * @return void
	 */
	public function process_batch(): void {
		$job = ImportJob::find_next_pending();

		if ( ! $job ) {
			return;
		}

		if ( 'pending' === $job->status ) {
			$job->update( [ 'status' => 'processing' ] );
		}

		$rows      = ImportRow::get_pending_batch( $job->id, self::BATCH_SIZE );
		$processor = new RowProcessor();

		$processed = 0;
		$failed    = 0;

		foreach ( $rows as $row ) {
			$processor->process( $row, $job );

			if ( 'done' === $row->status ) {
				++$processed;
			} else {
				++$failed;
			}
		}

		$job->update(
			[
				'processed_rows' => $job->processed_rows + $processed,
				'failed_rows'    => $job->failed_rows + $failed,
			]
		);

		// Check if all rows are done.
		$remaining = ImportRow::get_pending_batch( $job->id, 1 );

		if ( empty( $remaining ) ) {
			$job->update( [ 'status' => 'completed' ] );
		}
	}
}
