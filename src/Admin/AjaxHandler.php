<?php
/**
 * AJAX handler for dynamic endpoints.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\Admin;

use AchttienVijftien\WpContentImporter\Import\ImportJob;
use AchttienVijftien\WpContentImporter\Import\ImportRow;
use AchttienVijftien\WpContentImporter\Mapping\FieldResolver;

/**
 * Handles AJAX endpoints that require dynamic client-side interaction.
 */
class AjaxHandler {
	// phpcs:disable WordPress.Security.NonceVerification.Missing

	/**
	 * Constructor. Registers AJAX action hooks.
	 */
	public function __construct() {
		add_action( 'wp_ajax_wci_get_fields', [ $this, 'get_fields' ] );
		add_action( 'wp_ajax_wci_start_import', [ $this, 'start_import' ] );
		add_action( 'wp_ajax_wci_job_status', [ $this, 'job_status' ] );
	}

	/**
	 * Return available fields for a post type via AJAX.
	 *
	 * @return void
	 */
	public function get_fields(): void {
		$this->verify_nonce();
		$this->verify_capability();

		$post_type = isset( $_POST['post_type'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : '';

		if ( ! $post_type ) {
			wp_send_json_error( [ 'message' => __( 'Post type required.', 'wp-content-importer' ) ] );
		}

		$resolver = FieldResolver::create();
		$fields   = $resolver->resolve( $post_type );

		wp_send_json_success( $fields );
	}

	/**
	 * Start an import job via AJAX.
	 *
	 * @return void
	 */
	public function start_import(): void {
		$this->verify_nonce();
		$this->verify_capability();

		$job_id = isset( $_POST['job_id'] ) ? (int) $_POST['job_id'] : 0;
		$job    = ImportJob::find( $job_id );

		if ( ! $job ) {
			wp_send_json_error( [ 'message' => __( 'Job not found.', 'wp-content-importer' ) ] );
		}

		// Reset rows and counters for re-runs.
		ImportRow::reset_by_job( $job->id );
		$job->update(
			[
				'status'         => 'pending',
				'processed_rows' => 0,
				'failed_rows'    => 0,
			]
		);

		// Spawn a cron run immediately.
		spawn_cron();

		wp_send_json_success( [ 'job_id' => $job->id ] );
	}

	/**
	 * Return the status of an import job via AJAX.
	 *
	 * @return void
	 */
	public function job_status(): void {
		$this->verify_nonce();
		$this->verify_capability();

		$job_id = isset( $_POST['job_id'] ) ? (int) $_POST['job_id'] : 0;
		$job    = ImportJob::find( $job_id );

		if ( ! $job ) {
			wp_send_json_error( [ 'message' => __( 'Job not found.', 'wp-content-importer' ) ] );
		}

		wp_send_json_success(
			[
				'status'         => $job->status,
				'total_rows'     => $job->total_rows,
				'processed_rows' => $job->processed_rows,
				'failed_rows'    => $job->failed_rows,
			]
		);
	}

	/**
	 * Verify the AJAX nonce. Sends an error response on failure.
	 *
	 * @return void
	 */
	private function verify_nonce(): void {
		if ( ! check_ajax_referer( 'wci_nonce', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce.', 'wp-content-importer' ) ], 403 );
		}
	}

	/**
	 * Verify the current user has the import capability. Sends an error response on failure.
	 *
	 * @return void
	 */
	private function verify_capability(): void {
		if ( ! current_user_can( 'import' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'wp-content-importer' ) ], 403 );
		}
	}

	// phpcs:enable WordPress.Security.NonceVerification.Missing
}
