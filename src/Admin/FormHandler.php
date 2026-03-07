<?php
/**
 * Form handler for wizard POST submissions.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\Admin;

use AchttienVijftien\WpContentImporter\Import\ImportJob;
use AchttienVijftien\WpContentImporter\Import\ImportRow;
use AchttienVijftien\WpContentImporter\Mapping\Template;
use AchttienVijftien\WpContentImporter\Parser\ParserFactory;

/**
 * Handles wizard form POST submissions using the Post-Redirect-Get pattern.
 */
class FormHandler {

	/**
	 * Admin page slug.
	 *
	 * @var string
	 */
	private const SLUG = 'wp-content-importer';

	/**
	 * Constructor. Registers admin_init hook for form handling.
	 */
	public function __construct() {
		add_action( 'admin_init', [ $this, 'handle_post' ] );
	}

	/**
	 * Dispatch POST submissions based on the wci_action field.
	 *
	 * @return void
	 */
	public function handle_post(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in each handler.
		if ( ! isset( $_POST['wci_action'] ) ) {
			return;
		}

		if ( ! current_user_can( 'import' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wp-content-importer' ) );
		}

		$action = sanitize_text_field( wp_unslash( $_POST['wci_action'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		switch ( $action ) {
			case 'upload':
				$this->handle_upload();
				break;
			case 'configure':
				$this->handle_configure();
				break;
			case 'save_mapping':
				$this->handle_save_mapping();
				break;
		}
	}

	/**
	 * Handle file upload form submission.
	 *
	 * @return void
	 */
	private function handle_upload(): void {
		check_admin_referer( 'wci_upload' );

		if ( empty( $_FILES['file'] ) || UPLOAD_ERR_OK !== $_FILES['file']['error'] ) {
			$this->redirect( [ 'error' => 'upload_failed' ] );
		}

		$file = $_FILES['file'];

		try {
			$parser = ParserFactory::create( $file['name'] );
			$result = $parser->parse( $file['tmp_name'] );
		} catch ( \Throwable $e ) {
			$this->redirect( [ 'error' => 'parse_failed' ] );
		}

		$name = sanitize_text_field( $file['name'] );
		$job  = ImportJob::create( [ 'name' => $name ] );

		$count = ImportRow::bulk_insert( $job->id, $result['rows'] );
		$job->update( [ 'total_rows' => $count ] );

		$this->redirect(
			[
				'step'   => 'configure',
				'job_id' => $job->id,
			]
		);
	}

	/**
	 * Handle configure form submission.
	 *
	 * @return void
	 */
	private function handle_configure(): void {
		check_admin_referer( 'wci_configure' );

		$job_id = isset( $_POST['job_id'] ) ? (int) $_POST['job_id'] : 0;
		$job    = ImportJob::find( $job_id );

		if ( ! $job ) {
			$this->redirect( [ 'error' => 'job_not_found' ] );
		}

		$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$post_type   = isset( $_POST['post_type'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : '';
		$mode        = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'create';
		$match_field = isset( $_POST['match_field'] ) ? sanitize_text_field( wp_unslash( $_POST['match_field'] ) ) : null;

		$update_data = [
			'name'        => $name,
			'post_type'   => $post_type,
			'mode'        => $mode,
			'match_field' => $match_field,
		];

		// Apply template mapping if one was selected.
		$template_id = isset( $_POST['template_id'] ) ? (int) $_POST['template_id'] : 0;

		if ( $template_id ) {
			$template = Template::get( $template_id );

			if ( $template && ! empty( $template['mapping'] ) ) {
				$update_data['mapping'] = wp_json_encode( $template['mapping'] );
			}
		}

		$job->update( $update_data );

		$this->redirect(
			[
				'step'   => 'mapping',
				'job_id' => $job->id,
			]
		);
	}

	/**
	 * Handle mapping form submission.
	 *
	 * @return void
	 */
	private function handle_save_mapping(): void {
		check_admin_referer( 'wci_save_mapping' );

		$job_id = isset( $_POST['job_id'] ) ? (int) $_POST['job_id'] : 0;
		$job    = ImportJob::find( $job_id );

		if ( ! $job ) {
			$this->redirect( [ 'error' => 'job_not_found' ] );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$mapping = isset( $_POST['mapping'] ) ? json_decode( wp_unslash( $_POST['mapping'] ), true ) : [];

		// Sanitize mapping keys (target field names).
		$sanitized = [];
		foreach ( $mapping as $key => $config ) {
			$sanitized[ sanitize_text_field( $key ) ] = $config;
		}
		$mapping = $sanitized;

		$job->update( [ 'mapping' => wp_json_encode( $mapping ) ] );

		// Save template if requested.
		$save_template = isset( $_POST['save_template'] ) && '1' === $_POST['save_template'];
		$template_name = isset( $_POST['template_name'] ) ? sanitize_text_field( wp_unslash( $_POST['template_name'] ) ) : '';

		if ( $save_template && $template_name ) {
			Template::save( $template_name, $job->post_type, $job->mode, $job->match_field, $mapping );
		}

		$this->redirect(
			[
				'step'   => 'import',
				'job_id' => $job->id,
			]
		);
	}

	/**
	 * Redirect to the wizard page with the given query parameters.
	 *
	 * @param array $args Query parameters.
	 *
	 * @return void
	 */
	private function redirect( array $args = [] ): void {
		$url = add_query_arg(
			array_merge( [ 'page' => self::SLUG ], $args ),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}
}
