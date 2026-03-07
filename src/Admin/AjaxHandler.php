<?php
/**
 * AJAX handler for wizard endpoints.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\Admin;

use AchttienVijftien\WpContentImporter\Import\ImportJob;
use AchttienVijftien\WpContentImporter\Import\ImportRow;
use AchttienVijftien\WpContentImporter\Mapping\FieldResolver;
use AchttienVijftien\WpContentImporter\Mapping\Template;
use AchttienVijftien\WpContentImporter\Parser\ParserFactory;

/**
 * Handles all AJAX endpoints for the content importer wizard.
 */
class AjaxHandler {
	// phpcs:disable WordPress.Security.NonceVerification.Missing

	/**
	 * Constructor. Registers AJAX action hooks.
	 */
	public function __construct() {
		add_action( 'wp_ajax_wci_upload_file', [ $this, 'upload_file' ] );
		add_action( 'wp_ajax_wci_get_post_types', [ $this, 'get_post_types' ] );
		add_action( 'wp_ajax_wci_get_fields', [ $this, 'get_fields' ] );
		add_action( 'wp_ajax_wci_configure_job', [ $this, 'configure_job' ] );
		add_action( 'wp_ajax_wci_save_mapping', [ $this, 'save_mapping' ] );
		add_action( 'wp_ajax_wci_start_import', [ $this, 'start_import' ] );
		add_action( 'wp_ajax_wci_job_status', [ $this, 'job_status' ] );
		add_action( 'wp_ajax_wci_get_job', [ $this, 'get_job' ] );
	}

	/**
	 * Handle file upload and parsing via AJAX.
	 *
	 * @return void
	 */
	public function upload_file(): void {
		$this->verify_nonce();
		$this->verify_capability();

		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( [ 'message' => __( 'No file uploaded.', 'wp-content-importer' ) ] );
		}

		$file = $_FILES['file'];

		if ( UPLOAD_ERR_OK !== $file['error'] ) {
			wp_send_json_error( [ 'message' => __( 'File upload error.', 'wp-content-importer' ) ] );
		}

		try {
			$parser = ParserFactory::create( $file['name'] );
			$result = $parser->parse( $file['tmp_name'] );
		} catch ( \Throwable $e ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}

		$name  = pathinfo( $file['name'], PATHINFO_FILENAME );
		$job   = ImportJob::create( [ 'name' => sanitize_text_field( $name ) ] );
		$count = ImportRow::bulk_insert( $job->id, $result['rows'] );
		$job->update( [ 'total_rows' => $count ] );

		wp_send_json_success(
			[
				'job_id'  => $job->id,
				'name'    => $job->name,
				'headers' => $result['headers'],
				'preview' => array_slice( $result['rows'], 0, 5 ),
				'total'   => $count,
			]
		);
	}

	/**
	 * Return available post types via AJAX.
	 *
	 * @return void
	 */
	public function get_post_types(): void {
		$this->verify_nonce();
		$this->verify_capability();

		$post_types = get_post_types( [], 'objects' );

		$result = [];

		foreach ( $post_types as $post_type ) {
			$result[] = [
				'name'  => $post_type->name,
				'label' => $post_type->labels->singular_name,
			];
		}

		wp_send_json_success( $result );
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
	 * Configure an import job with post type, mode, and match field via AJAX.
	 *
	 * @return void
	 */
	public function configure_job(): void {
		$this->verify_nonce();
		$this->verify_capability();

		$job_id      = isset( $_POST['job_id'] ) ? (int) $_POST['job_id'] : 0;
		$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$post_type   = isset( $_POST['post_type'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : '';
		$mode        = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'create';
		$match_field = isset( $_POST['match_field'] ) ? sanitize_text_field( wp_unslash( $_POST['match_field'] ) ) : null;

		$job = ImportJob::find( $job_id );

		if ( ! $job ) {
			wp_send_json_error( [ 'message' => __( 'Job not found.', 'wp-content-importer' ) ] );
		}

		$job->update(
			[
				'name'        => $name,
				'post_type'   => $post_type,
				'mode'        => $mode,
				'match_field' => $match_field,
			]
		);

		$resolver = FieldResolver::create();
		$fields   = $resolver->resolve( $post_type );

		wp_send_json_success(
			[
				'fields' => $fields,
			]
		);
	}

	/**
	 * Save field mapping for an import job via AJAX.
	 *
	 * @return void
	 */
	public function save_mapping(): void {
		$this->verify_nonce();
		$this->verify_capability();

		$job_id = isset( $_POST['job_id'] ) ? (int) $_POST['job_id'] : 0;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$mapping = isset( $_POST['mapping'] ) ? json_decode( wp_unslash( $_POST['mapping'] ), true ) : [];

		$job = ImportJob::find( $job_id );

		if ( ! $job ) {
			wp_send_json_error( [ 'message' => __( 'Job not found.', 'wp-content-importer' ) ] );
		}

		$job->update( [ 'mapping' => wp_json_encode( $mapping ) ] );

		// Save template if requested.
		$save_template = isset( $_POST['save_template'] ) && '1' === $_POST['save_template'];
		$template_name = isset( $_POST['template_name'] ) ? sanitize_text_field( wp_unslash( $_POST['template_name'] ) ) : '';

		if ( $save_template && $template_name ) {
			Template::save( $template_name, $job->post_type, $job->mode, $job->match_field, $mapping );
		}

		wp_send_json_success(
			[
				'summary' => [
					'post_type'   => $job->post_type,
					'mode'        => $job->mode,
					'match_field' => $job->match_field,
					'total_rows'  => $job->total_rows,
					'mappings'    => count( $mapping ),
				],
			]
		);
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
	 * Return full job data for editing via AJAX.
	 *
	 * @return void
	 */
	public function get_job(): void {
		$this->verify_nonce();
		$this->verify_capability();

		$job_id = isset( $_POST['job_id'] ) ? (int) $_POST['job_id'] : 0;
		$job    = ImportJob::find( $job_id );

		if ( ! $job ) {
			wp_send_json_error( [ 'message' => __( 'Job not found.', 'wp-content-importer' ) ] );
		}

		$resolver = FieldResolver::create();
		$fields   = $resolver->resolve( $job->post_type );

		wp_send_json_success(
			[
				'job_id'      => $job->id,
				'name'        => $job->name,
				'post_type'   => $job->post_type,
				'mode'        => $job->mode,
				'match_field' => $job->match_field,
				'mapping'     => $job->mapping,
				'total_rows'  => $job->total_rows,
				'fields'      => $fields,
				'headers'     => $this->get_job_headers( $job ),
			]
		);
	}

	/**
	 * Get column headers from the first row of a job.
	 *
	 * @param ImportJob $job The import job.
	 *
	 * @return array Column headers.
	 */
	private function get_job_headers( ImportJob $job ): array {
		$rows = ImportRow::get_by_job( $job->id, 1 );

		if ( empty( $rows ) ) {
			return [];
		}

		$data = $rows[0]->data;

		return is_array( $data ) ? array_keys( $data ) : [];
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
