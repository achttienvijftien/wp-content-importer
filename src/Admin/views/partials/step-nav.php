<?php
/**
 * Step navigation partial.
 *
 * @package AchttienVijftien\WpContentImporter
 *
 * @var string $current_step The current step slug.
 * @var int    $job_id       The current job ID (0 if none).
 * @var array  $job_data     Optional job data for determining reachable steps.
 */

$page_url = admin_url( 'admin.php?page=wp-content-importer' );

$steps = [
	'upload'    => __( '1. Upload', 'wp-content-importer' ),
	'configure' => __( '2. Configure', 'wp-content-importer' ),
	'mapping'   => __( '3. Map Fields', 'wp-content-importer' ),
	'import'    => __( '4. Import', 'wp-content-importer' ),
];

// Determine which steps are reachable based on job state.
$reachable = [ 'upload' ];

if ( $job_id ) {
	$reachable[] = 'configure';

	if ( ! empty( $job_data['post_type'] ) ) {
		$reachable[] = 'mapping';
	}

	if ( ! empty( $job_data['mapping'] ) ) {
		$reachable[] = 'import';
	}
}
?>
<nav class="wci-steps">
	<?php foreach ( $steps as $slug => $label ) : ?>
		<?php
		$is_current   = $slug === $current_step;
		$is_reachable = in_array( $slug, $reachable, true );

		$classes = 'wci-step';
		if ( $is_current ) {
			$classes .= ' active';
		} elseif ( $is_reachable ) {
			$classes .= ' completed';
		}

		$step_url = $page_url;
		if ( 'upload' !== $slug && $job_id ) {
			$step_url = add_query_arg(
				[
					'step'   => $slug,
					'job_id' => $job_id,
				],
				$page_url
			);
		}
		?>
		<?php if ( $is_reachable && ! $is_current ) : ?>
			<a href="<?php echo esc_url( $step_url ); ?>" class="<?php echo esc_attr( $classes ); ?>">
				<?php echo esc_html( $label ); ?>
			</a>
		<?php else : ?>
			<span class="<?php echo esc_attr( $classes ); ?>">
				<?php echo esc_html( $label ); ?>
			</span>
		<?php endif; ?>
	<?php endforeach; ?>
</nav>
