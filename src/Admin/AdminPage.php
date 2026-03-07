<?php
/**
 * Admin page.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\Admin;

use AchttienVijftien\WpContentImporter\Import\ImportJob;
use AchttienVijftien\WpContentImporter\Import\ImportRow;
use AchttienVijftien\WpContentImporter\Mapping\FieldResolver;

/**
 * Handles the admin page registration, asset enqueuing, and view rendering
 * for the content importer wizard.
 */
class AdminPage {

	/**
	 * Admin page slug.
	 *
	 * @var string
	 */
	private const SLUG = 'wp-content-importer';

	/**
	 * Constructor. Registers admin menu and asset hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Register the admin menu item.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'Content Importer', 'wp-content-importer' ),
			__( 'Content Importer', 'wp-content-importer' ),
			'import',
			self::SLUG,
			[ $this, 'render' ],
			'dashicons-upload',
			80
		);
	}

	/**
	 * Enqueue CSS and JS assets on the importer admin page.
	 *
	 * @param string $hook The current admin page hook suffix.
	 *
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_' . self::SLUG !== $hook ) {
			return;
		}

		$plugin_file = dirname( __DIR__ ) . '/../wp-content-importer.php';

		$css_path = dirname( $plugin_file ) . '/assets/css/wizard.css';
		wp_enqueue_style(
			'wci-wizard',
			plugins_url( 'assets/css/wizard.css', $plugin_file ),
			[],
			(string) filemtime( $css_path )
		);

		$js_path = dirname( $plugin_file ) . '/assets/js/wizard.js';
		wp_enqueue_script(
			'wci-wizard',
			plugins_url( 'assets/js/wizard.js', $plugin_file ),
			[],
			(string) filemtime( $js_path ),
			true
		);

		$script_data = [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wci_nonce' ),
		];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$step = isset( $_GET['step'] ) ? sanitize_text_field( wp_unslash( $_GET['step'] ) ) : 'upload';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$job_id = isset( $_GET['job_id'] ) ? (int) $_GET['job_id'] : 0;

		if ( 'mapping' === $step && $job_id ) {
			$job = ImportJob::find( $job_id );

			if ( $job ) {
				$rows    = ImportRow::get_by_job( $job->id, 5 );
				$headers = ! empty( $rows ) && is_array( $rows[0]->data ) ? array_keys( $rows[0]->data ) : [];
				$preview = array_map( fn( $r ) => $r->data, $rows );

				$resolver = FieldResolver::create();
				$fields   = $resolver->resolve( $job->post_type );

				$script_data['headers'] = $headers;
				$script_data['fields']  = $fields;
				$script_data['preview'] = $preview;
				$script_data['mapping'] = $job->mapping;
			}
		}

		if ( 'import' === $step && $job_id ) {
			$script_data['jobId'] = $job_id;
		}

		wp_localize_script( 'wci-wizard', 'wciData', $script_data );
	}

	/**
	 * Render the admin page, dispatching to the appropriate view.
	 *
	 * @return void
	 */
	public function render(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Content Importer', 'wp-content-importer' ) . '</h1>';

		if ( 'history' === $view ) {
			include __DIR__ . '/views/history.php';
		} elseif ( 'job' === $view ) {
			include __DIR__ . '/views/job-detail.php';
		} else {
			$this->render_wizard();
		}

		echo '</div>';
	}

	/**
	 * Render the wizard step views.
	 *
	 * @return void
	 */
	private function render_wizard(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$current_step = isset( $_GET['step'] ) ? sanitize_text_field( wp_unslash( $_GET['step'] ) ) : 'upload';
		$job_id       = isset( $_GET['job_id'] ) ? (int) $_GET['job_id'] : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$job      = $job_id ? ImportJob::find( $job_id ) : null;
		$job_data = $job ? $this->get_job_data( $job ) : [];

		// Validate step prerequisites.
		if ( 'upload' !== $current_step && ! $job ) {
			$current_step = 'upload';
			$job_id       = 0;
		}

		if ( 'mapping' === $current_step && empty( $job_data['post_type'] ) ) {
			$current_step = 'configure';
		}

		if ( 'import' === $current_step && empty( $job_data['mapping'] ) ) {
			$current_step = 'mapping';
		}

		switch ( $current_step ) {
			case 'configure':
				include __DIR__ . '/views/step-configure.php';
				break;
			case 'mapping':
				$rows    = ImportRow::get_by_job( $job->id, 5 );
				$headers = ! empty( $rows ) && is_array( $rows[0]->data ) ? array_keys( $rows[0]->data ) : [];
				$fields  = FieldResolver::create()->resolve( $job->post_type );
				$preview = array_map( fn( $r ) => $r->data, $rows );
				include __DIR__ . '/views/step-mapping.php';
				break;
			case 'import':
				include __DIR__ . '/views/step-import.php';
				break;
			default:
				include __DIR__ . '/views/step-upload.php';
				break;
		}
	}

	/**
	 * Extract job data into an array for use in views.
	 *
	 * @param ImportJob $job The import job.
	 *
	 * @return array Job data.
	 */
	private function get_job_data( ImportJob $job ): array {
		return [
			'name'        => $job->name,
			'post_type'   => $job->post_type,
			'mode'        => $job->mode,
			'match_field' => $job->match_field,
			'mapping'     => $job->mapping,
			'total_rows'  => $job->total_rows,
		];
	}
}
