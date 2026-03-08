<?php
/**
 * Step 3: Map fields.
 *
 * @package AchttienVijftien\WpContentImporter
 *
 * @var string $current_step Current step slug.
 * @var int    $job_id       Current job ID.
 * @var array  $job_data     Job data array.
 * @var array  $headers      Column headers from the imported file.
 * @var array  $fields       Available target fields for the post type.
 * @var array  $preview      Preview data (first rows).
 */

defined( 'ABSPATH' ) || exit;

require __DIR__ . '/partials/step-nav.php';

// phpcs:disable WordPress.Security.NonceVerification.Recommended
$mapping_error      = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : '';
$prev_template_name = isset( $_GET['template_name'] ) ? sanitize_text_field( wp_unslash( $_GET['template_name'] ) ) : '';
$template_checked   = 'template_exists' === $mapping_error;
// phpcs:enable WordPress.Security.NonceVerification.Recommended
?>

<?php if ( 'template_exists' === $mapping_error ) : ?>
	<div class="notice notice-error">
		<p>
			<?php
			esc_html_e(
				'A template with that name already exists. Please choose a different name.',
				'wp-content-importer'
			);
			?>
		</p>
	</div>
<?php endif; ?>

<h2><?php esc_html_e( 'Map Fields', 'wp-content-importer' ); ?></h2>
<p>
	<?php
	esc_html_e(
		'Click a column to insert it. Use {column} syntax to combine columns, or type a static value. Use {column|modifier} to transform values.',
		'wp-content-importer'
	);
	?>
</p>

<div id="wci-data-preview"></div>

<details class="wci-preview-details">
	<summary><?php esc_html_e( 'Available modifiers', 'wp-content-importer' ); ?></summary>
	<?php $modifiers = \AchttienVijftien\WpContentImporter\Mapping\ModifierRegistry::instance()->get_all(); ?>
	<table class="widefat striped wci-modifier-ref">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Modifier', 'wp-content-importer' ); ?></th>
				<th><?php esc_html_e( 'Example', 'wp-content-importer' ); ?></th>
				<th><?php esc_html_e( 'Description', 'wp-content-importer' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $modifiers as $name => $meta ) : ?>
				<tr>
					<td><code><?php echo esc_html( $name ); ?></code></td>
					<td><code><?php echo esc_html( $meta['example'] ); ?></code></td>
					<td><?php echo esc_html( $meta['description'] ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<p class="description">
		<?php esc_html_e( 'Chain modifiers with pipes: {name|trim|upper}. Arguments can be static (quoted) or column references (bare).', 'wp-content-importer' ); ?>
	</p>
</details>

<form method="post" id="wci-mapping-form">
	<?php wp_nonce_field( 'wci_save_mapping' ); ?>
	<input type="hidden" name="wci_action" value="save_mapping" />
	<input type="hidden" name="job_id" value="<?php echo esc_attr( $job_id ); ?>" />
	<input type="hidden" name="mapping" id="wci-mapping-data" value="" />

	<table class="widefat" id="wci-mapping-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Target Field', 'wp-content-importer' ); ?></th>
				<th><?php esc_html_e( 'Value', 'wp-content-importer' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody></tbody>
	</table>

	<p>
		<button type="button" class="button" id="wci-add-mapping">
			+ <?php esc_html_e( 'Add Field Mapping', 'wp-content-importer' ); ?>
		</button>
	</p>

	<h3><?php esc_html_e( 'Save as Template', 'wp-content-importer' ); ?></h3>
	<label>
		<input type="checkbox" name="save_template" id="wci-save-template"
			value="1" <?php checked( $template_checked ); ?> />
		<?php esc_html_e( 'Save this mapping for future use', 'wp-content-importer' ); ?>
	</label>
	<div id="wci-template-name-wrap"
		style="<?php echo $template_checked ? '' : 'display:none;'; ?>">
		<input type="text" name="template_name" id="wci-template-name"
			placeholder="<?php esc_attr_e( 'Template name', 'wp-content-importer' ); ?>"
			class="regular-text"
			value="<?php echo esc_attr( $prev_template_name ); ?>" />
	</div>

	<br /><br />
	<button type="submit" class="button button-primary">
		<?php esc_html_e( 'Next: Review & Import', 'wp-content-importer' ); ?>
	</button>
</form>
