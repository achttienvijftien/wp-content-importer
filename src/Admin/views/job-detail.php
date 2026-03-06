<?php
/**
 * Job detail view.
 *
 * @package AchttienVijftien\WpContentImporter
 */

use AchttienVijftien\WpContentImporter\Import\ImportJob;
use AchttienVijftien\WpContentImporter\Import\ImportRow;

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$job_id = isset( $_GET['job_id'] ) ? (int) $_GET['job_id'] : 0;
$job    = ImportJob::find( $job_id );

if ( ! $job ) {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'Job not found.', 'wp-content-importer' ) . '</p></div>';
	return;
}

$rows     = ImportRow::get_by_job( $job->id, 100 );
$page_url = admin_url( 'admin.php?page=wp-content-importer' );
?>

<p>
	<a href="<?php echo esc_url( add_query_arg( 'view', 'history', $page_url ) ); ?>" class="button">
		<?php esc_html_e( 'Back to History', 'wp-content-importer' ); ?>
	</a>
</p>

<h2>
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Value is cast to int.
	printf(
		/* translators: %d: job ID */
		esc_html__( 'Import Job #%d', 'wp-content-importer' ),
		(int) $job->id
	);
	?>
</h2>

<table class="form-table">
	<tr>
		<th><?php esc_html_e( 'Name', 'wp-content-importer' ); ?></th>
		<td><?php echo esc_html( $job->name ?: '—' ); ?></td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Status', 'wp-content-importer' ); ?></th>
		<td><?php echo esc_html( $job->status ); ?></td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Post Type', 'wp-content-importer' ); ?></th>
		<td><?php echo esc_html( $job->post_type ); ?></td>
	</tr>
	<tr><th><?php esc_html_e( 'Mode', 'wp-content-importer' ); ?></th><td><?php echo esc_html( $job->mode ); ?></td></tr>
	<tr>
		<th><?php esc_html_e( 'Progress', 'wp-content-importer' ); ?></th>
		<td>
			<?php
			echo esc_html( $job->processed_rows . ' / ' . $job->total_rows );
			?>
			(<?php echo esc_html( $job->failed_rows ); ?> failed)
		</td>
	</tr>
</table>

<h3><?php esc_html_e( 'Rows', 'wp-content-importer' ); ?></h3>
<table class="widefat striped">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Row', 'wp-content-importer' ); ?></th>
			<th><?php esc_html_e( 'Status', 'wp-content-importer' ); ?></th>
			<th><?php esc_html_e( 'Post', 'wp-content-importer' ); ?></th>
			<th><?php esc_html_e( 'Error', 'wp-content-importer' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $rows as $index => $row ) : ?>
			<tr>
				<td><?php echo esc_html( $index + 1 ); ?></td>
				<td>
					<span class="wci-status wci-status-<?php echo esc_attr( $row->status ); ?>">
						<?php echo esc_html( $row->status ); ?>
					</span>
				</td>
				<td>
					<?php if ( $row->post_id ) : ?>
						<a href="<?php echo esc_url( get_edit_post_link( $row->post_id ) ); ?>">
							#<?php echo esc_html( $row->post_id ); ?>
						</a>
					<?php else : ?>
						—
					<?php endif; ?>
				</td>
				<td><?php echo esc_html( $row->error ?? '—' ); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
