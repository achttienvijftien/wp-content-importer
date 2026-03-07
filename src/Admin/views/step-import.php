<?php
/**
 * Step 4: Review and import.
 *
 * @package AchttienVijftien\WpContentImporter
 *
 * @var string $current_step Current step slug.
 * @var int    $job_id       Current job ID.
 * @var array  $job_data     Job data array.
 */

defined( 'ABSPATH' ) || exit;

require __DIR__ . '/partials/step-nav.php';

$mapping_count = is_array( $job_data['mapping'] ) ? count( $job_data['mapping'] ) : 0;
?>

<h2><?php esc_html_e( 'Review & Import', 'wp-content-importer' ); ?></h2>

<table class="form-table">
	<tr>
		<th><?php esc_html_e( 'Post Type', 'wp-content-importer' ); ?></th>
		<td><?php echo esc_html( $job_data['post_type'] ); ?></td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Mode', 'wp-content-importer' ); ?></th>
		<td><?php echo esc_html( $job_data['mode'] ); ?></td>
	</tr>
	<?php if ( ! empty( $job_data['match_field'] ) ) : ?>
		<tr>
			<th><?php esc_html_e( 'Match Field', 'wp-content-importer' ); ?></th>
			<td><?php echo esc_html( $job_data['match_field'] ); ?></td>
		</tr>
	<?php endif; ?>
	<tr>
		<th><?php esc_html_e( 'Total Rows', 'wp-content-importer' ); ?></th>
		<td><?php echo esc_html( $job_data['total_rows'] ); ?></td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Mapped Fields', 'wp-content-importer' ); ?></th>
		<td><?php echo esc_html( $mapping_count ); ?></td>
	</tr>
</table>

<button type="button" class="button button-primary" id="wci-start-btn">
	<?php esc_html_e( 'Start Import', 'wp-content-importer' ); ?>
</button>

<div id="wci-progress" style="display:none;">
	<h3><?php esc_html_e( 'Progress', 'wp-content-importer' ); ?></h3>
	<div class="wci-progress-bar-wrap">
		<div class="wci-progress-bar" style="width:0%"></div>
	</div>
	<p id="wci-progress-text"></p>
</div>

<div id="wci-complete" style="display:none;">
	<h3><?php esc_html_e( 'Import Complete', 'wp-content-importer' ); ?></h3>
	<div id="wci-complete-summary"></div>
</div>
