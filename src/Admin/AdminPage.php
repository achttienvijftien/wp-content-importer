<?php
/**
 * Admin page.
 *
 * @package AchttienVijftien\WpContentImporter
 */

namespace AchttienVijftien\WpContentImporter\Admin;

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

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$edit_job_id = isset( $_GET['view'], $_GET['job_id'] ) && 'edit' === $_GET['view']
			? (int) $_GET['job_id']
			: 0;

		wp_localize_script(
			'wci-wizard',
			'wciData',
			[
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'wci_nonce' ),
				'editJobId' => $edit_job_id,
			]
		);
	}

	/**
	 * Render the admin page, dispatching to the appropriate view.
	 *
	 * @return void
	 */
	public function render(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : 'wizard';

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Content Importer', 'wp-content-importer' ) . '</h1>';

		switch ( $view ) {
			case 'job':
				include __DIR__ . '/views/job-detail.php';
				break;
			case 'history':
				include __DIR__ . '/views/history.php';
				break;
			case 'edit':
				include __DIR__ . '/views/wizard.php';
				break;
			default:
				include __DIR__ . '/views/wizard.php';
				break;
		}

		echo '</div>';
	}
}
