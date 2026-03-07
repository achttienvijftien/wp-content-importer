<?php
/**
 * Step 1: Upload file.
 *
 * @package AchttienVijftien\WpContentImporter
 *
 * @var string $current_step Current step slug.
 * @var int    $job_id       Current job ID.
 * @var array  $job_data     Job data array.
 */

defined( 'ABSPATH' ) || exit;

require __DIR__ . '/partials/step-nav.php';

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$upload_error = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : '';
?>

<?php if ( 'upload_failed' === $upload_error ) : ?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'File upload failed. Please try again.', 'wp-content-importer' ); ?></p>
	</div>
<?php elseif ( 'parse_failed' === $upload_error ) : ?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'Could not parse the uploaded file. Please check the format.', 'wp-content-importer' ); ?></p>
	</div>
<?php endif; ?>

<h2><?php esc_html_e( 'Upload File', 'wp-content-importer' ); ?></h2>
<p><?php esc_html_e( 'Select a CSV or Excel file to import.', 'wp-content-importer' ); ?></p>

<form method="post" enctype="multipart/form-data">
	<?php wp_nonce_field( 'wci_upload' ); ?>
	<input type="hidden" name="wci_action" value="upload" />

	<p>
		<input type="file" name="file" accept=".csv,.xls,.xlsx" required />
	</p>
	<p>
		<button type="submit" class="button button-primary">
			<?php esc_html_e( 'Upload & Parse', 'wp-content-importer' ); ?>
		</button>
	</p>
</form>

<p>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-content-importer&view=history' ) ); ?>">
		<?php esc_html_e( 'View Import History', 'wp-content-importer' ); ?>
	</a>
</p>
