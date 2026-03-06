<?php
/**
 * Wizard view.
 *
 * @package AchttienVijftien\WpContentImporter
 */

use AchttienVijftien\WpContentImporter\Mapping\Template;

$templates = Template::get_all();
?>
<div id="wci-wizard">
	<nav class="wci-steps">
		<span class="wci-step active" data-step="1"><?php esc_html_e( '1. Upload', 'wp-content-importer' ); ?></span>
		<span class="wci-step" data-step="2"><?php esc_html_e( '2. Configure', 'wp-content-importer' ); ?></span>
		<span class="wci-step" data-step="3"><?php esc_html_e( '3. Map Fields', 'wp-content-importer' ); ?></span>
		<span class="wci-step" data-step="4"><?php esc_html_e( '4. Import', 'wp-content-importer' ); ?></span>
	</nav>

	<!-- Step 1: Upload -->
	<div class="wci-panel" data-step="1">
		<h2><?php esc_html_e( 'Upload File', 'wp-content-importer' ); ?></h2>
		<p><?php esc_html_e( 'Select a CSV or Excel file to import.', 'wp-content-importer' ); ?></p>
		<input type="file" id="wci-file" accept=".csv,.xls,.xlsx" />
		<button type="button" class="button button-primary" id="wci-upload-btn" disabled>
			<?php esc_html_e( 'Upload & Parse', 'wp-content-importer' ); ?>
		</button>
		<div id="wci-upload-status"></div>
	</div>

	<!-- Step 2: Configure -->
	<div class="wci-panel" data-step="2" style="display:none;">
		<h2><?php esc_html_e( 'Configure Import', 'wp-content-importer' ); ?></h2>

		<table class="form-table">
			<tr>
				<th><label for="wci-name"><?php esc_html_e( 'Name', 'wp-content-importer' ); ?></label></th>
				<td><input type="text" id="wci-name" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="wci-post-type"><?php esc_html_e( 'Post Type', 'wp-content-importer' ); ?></label></th>
				<td><select id="wci-post-type"></select></td>
			</tr>
			<tr>
				<th><label for="wci-mode"><?php esc_html_e( 'Mode', 'wp-content-importer' ); ?></label></th>
				<td>
					<select id="wci-mode">
						<option value="create"><?php esc_html_e( 'Create new posts', 'wp-content-importer' ); ?></option>
						<option value="update"><?php esc_html_e( 'Update existing posts', 'wp-content-importer' ); ?></option>
					</select>
				</td>
			</tr>
			<tr class="wci-match-row" style="display:none;">
				<th><label for="wci-match-field"><?php esc_html_e( 'Match Field', 'wp-content-importer' ); ?></label></th>
				<td><select id="wci-match-field"></select></td>
			</tr>
			<tr>
				<th><label for="wci-template"><?php esc_html_e( 'Load Template', 'wp-content-importer' ); ?></label></th>
				<td>
					<select id="wci-template">
						<option value=""><?php esc_html_e( '— None —', 'wp-content-importer' ); ?></option>
						<?php foreach ( $templates as $template ) : ?>
							<option value="<?php echo esc_attr( $template['id'] ); ?>"
								data-mapping="<?php echo esc_attr( wp_json_encode( $template['mapping'] ) ); ?>"
								data-post-type="<?php echo esc_attr( $template['post_type'] ); ?>"
								data-mode="<?php echo esc_attr( $template['mode'] ); ?>"
								data-match-field="<?php echo esc_attr( $template['match_field'] ); ?>">
								<?php echo esc_html( $template['name'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>

		<button type="button" class="button button-primary" id="wci-configure-btn">
			<?php esc_html_e( 'Next: Map Fields', 'wp-content-importer' ); ?>
		</button>
	</div>

	<!-- Step 3: Map Fields -->
	<div class="wci-panel" data-step="3" style="display:none;">
		<h2><?php esc_html_e( 'Map Fields', 'wp-content-importer' ); ?></h2>
		<p>
			<?php
			esc_html_e(
				'Click a column to insert it. Use {column} syntax to combine columns, or type a static value.',
				'wp-content-importer'
			);
			?>
		</p>

		<div id="wci-data-preview"></div>

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
			<input type="checkbox" id="wci-save-template" />
			<?php esc_html_e( 'Save this mapping for future use', 'wp-content-importer' ); ?>
		</label>
		<div id="wci-template-name-wrap" style="display:none;">
			<input type="text" id="wci-template-name"
				placeholder="<?php esc_attr_e( 'Template name', 'wp-content-importer' ); ?>"
				class="regular-text" />
		</div>

		<br /><br />
		<button type="button" class="button button-primary" id="wci-map-btn">
			<?php esc_html_e( 'Next: Review & Import', 'wp-content-importer' ); ?>
		</button>
	</div>

	<!-- Step 4: Confirm & Progress -->
	<div class="wci-panel" data-step="4" style="display:none;">
		<h2><?php esc_html_e( 'Review & Import', 'wp-content-importer' ); ?></h2>
		<div id="wci-summary"></div>
		<button type="button" class="button button-primary" id="wci-start-btn">
			<?php esc_html_e( 'Start Import', 'wp-content-importer' ); ?>
		</button>

		<div id="wci-progress" style="display:none;">
			<h3><?php esc_html_e( 'Progress', 'wp-content-importer' ); ?></h3>
			<div class="wci-progress-bar-wrap">
				<div class="wci-progress-bar" style="width:0%"></div>
			</div>
			<p id="wci-progress-text"></p>
			<div id="wci-progress-errors"></div>
		</div>

		<div id="wci-complete" style="display:none;">
			<h3><?php esc_html_e( 'Import Complete', 'wp-content-importer' ); ?></h3>
			<div id="wci-complete-summary"></div>
		</div>
	</div>
</div>

<p>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-content-importer&view=history' ) ); ?>">
		<?php esc_html_e( 'View Import History', 'wp-content-importer' ); ?>
	</a>
</p>
