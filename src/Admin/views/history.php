<?php
/**
 * Import history view.
 *
 * @package AchttienVijftien\WpContentImporter
 */

use AchttienVijftien\WpContentImporter\Import\ImportJob;

$jobs     = ImportJob::all( 20, 0 );
$total    = ImportJob::count();
$page_url = admin_url( 'admin.php?page=wp-content-importer' );
?>

<p>
	<a href="<?php echo esc_url( $page_url ); ?>" class="button">
		<?php esc_html_e( 'New Import', 'wp-content-importer' ); ?>
	</a>
</p>

<table class="widefat striped">
	<thead>
		<tr>
			<th><?php esc_html_e( 'ID', 'wp-content-importer' ); ?></th>
			<th><?php esc_html_e( 'Name', 'wp-content-importer' ); ?></th>
			<th><?php esc_html_e( 'Status', 'wp-content-importer' ); ?></th>
			<th><?php esc_html_e( 'Post Type', 'wp-content-importer' ); ?></th>
			<th><?php esc_html_e( 'Mode', 'wp-content-importer' ); ?></th>
			<th><?php esc_html_e( 'Total', 'wp-content-importer' ); ?></th>
			<th><?php esc_html_e( 'Processed', 'wp-content-importer' ); ?></th>
			<th><?php esc_html_e( 'Failed', 'wp-content-importer' ); ?></th>
			<th><?php esc_html_e( 'Date', 'wp-content-importer' ); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php if ( empty( $jobs ) ) : ?>
			<tr><td colspan="10"><?php esc_html_e( 'No imports yet.', 'wp-content-importer' ); ?></td></tr>
		<?php else : ?>
			<?php foreach ( $jobs as $job ) : ?>
				<tr>
					<td><?php echo esc_html( $job->id ); ?></td>
					<td><?php echo esc_html( $job->name ?: '—' ); ?></td>
					<td>
						<span class="wci-status wci-status-<?php echo esc_attr( $job->status ); ?>">
							<?php echo esc_html( $job->status ); ?>
						</span>
					</td>
					<td><?php echo esc_html( $job->post_type ); ?></td>
					<td><?php echo esc_html( $job->mode ); ?></td>
					<td><?php echo esc_html( $job->total_rows ); ?></td>
					<td><?php echo esc_html( $job->processed_rows ); ?></td>
					<td><?php echo esc_html( $job->failed_rows ); ?></td>
					<td><?php echo esc_html( $job->created_at ); ?></td>
					<td>
						<a href="
						<?php
						echo esc_url(
							add_query_arg(
								[
									'view'   => 'job',
									'job_id' => $job->id,
								],
								$page_url
							)
						);
						?>
									">
							<?php esc_html_e( 'Details', 'wp-content-importer' ); ?>
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
	</tbody>
</table>
