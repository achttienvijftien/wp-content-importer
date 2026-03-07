<?php
/**
 * Step 2: Configure import.
 *
 * @package AchttienVijftien\WpContentImporter
 *
 * @var string $current_step Current step slug.
 * @var int    $job_id       Current job ID.
 * @var array  $job_data     Job data array.
 */

use AchttienVijftien\WpContentImporter\Mapping\FieldResolver;
use AchttienVijftien\WpContentImporter\Mapping\Template;

require __DIR__ . '/partials/step-nav.php';

$post_types = get_post_types( [], 'objects' );
$templates  = Template::get_all();

$fields = [];
if ( ! empty( $job_data['post_type'] ) ) {
	$resolver = FieldResolver::create();
	$fields   = $resolver->resolve( $job_data['post_type'] );
}
?>

<h2><?php esc_html_e( 'Configure Import', 'wp-content-importer' ); ?></h2>

<form method="post" id="wci-configure-form">
	<?php wp_nonce_field( 'wci_configure' ); ?>
	<input type="hidden" name="wci_action" value="configure" />
	<input type="hidden" name="job_id" value="<?php echo esc_attr( $job_id ); ?>" />
	<input type="hidden" name="template_id" id="wci-template-id" value="" />

	<table class="form-table">
		<tr>
			<th>
				<label for="wci-name">
					<?php esc_html_e( 'Name', 'wp-content-importer' ); ?>
				</label>
			</th>
			<td>
				<input type="text" id="wci-name" name="name" class="regular-text"
					value="<?php echo esc_attr( $job_data['name'] ?? '' ); ?>" />
			</td>
		</tr>
		<tr>
			<th>
				<label for="wci-post-type">
					<?php esc_html_e( 'Post Type', 'wp-content-importer' ); ?>
				</label>
			</th>
			<td>
				<select id="wci-post-type" name="post_type">
					<?php foreach ( $post_types as $pt ) : ?>
						<option value="<?php echo esc_attr( $pt->name ); ?>"
							<?php selected( $job_data['post_type'] ?? '', $pt->name ); ?>>
							<?php echo esc_html( $pt->labels->singular_name . ' (' . $pt->name . ')' ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th>
				<label for="wci-mode">
					<?php esc_html_e( 'Mode', 'wp-content-importer' ); ?>
				</label>
			</th>
			<td>
				<select id="wci-mode" name="mode">
					<option value="create"
						<?php selected( $job_data['mode'] ?? 'create', 'create' ); ?>>
						<?php esc_html_e( 'Create new posts', 'wp-content-importer' ); ?>
					</option>
					<option value="update"
						<?php selected( $job_data['mode'] ?? '', 'update' ); ?>>
						<?php esc_html_e( 'Update existing posts', 'wp-content-importer' ); ?>
					</option>
					<option value="upsert"
						<?php selected( $job_data['mode'] ?? '', 'upsert' ); ?>>
						<?php esc_html_e( 'Update or create (upsert)', 'wp-content-importer' ); ?>
					</option>
				</select>
			</td>
		</tr>
		<?php $needs_match = in_array( $job_data['mode'] ?? '', [ 'update', 'upsert' ], true ); ?>
		<tr class="wci-match-row"
			style="<?php echo $needs_match ? '' : 'display:none;'; ?>">
			<th>
				<label for="wci-match-field">
					<?php esc_html_e( 'Match Field', 'wp-content-importer' ); ?>
				</label>
			</th>
			<td>
				<select id="wci-match-field" name="match_field">
					<?php foreach ( $fields as $field ) : ?>
						<option value="<?php echo esc_attr( $field['key'] ); ?>"
							<?php selected( $job_data['match_field'] ?? '', $field['key'] ); ?>>
							<?php echo esc_html( $field['name'] . ' (' . $field['group'] . ')' ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th>
				<label for="wci-template">
					<?php esc_html_e( 'Load Template', 'wp-content-importer' ); ?>
				</label>
			</th>
			<td>
				<select id="wci-template">
					<option value="">
						<?php esc_html_e( '— None —', 'wp-content-importer' ); ?>
					</option>
					<?php foreach ( $templates as $template ) : ?>
						<option value="<?php echo esc_attr( $template['id'] ); ?>">
							<?php echo esc_html( $template['name'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
	</table>

	<button type="submit" class="button button-primary">
		<?php esc_html_e( 'Next: Map Fields', 'wp-content-importer' ); ?>
	</button>
</form>
